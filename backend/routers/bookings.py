import math
from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db
from ai.fraud_service import detect_fraud
from pricing import calculate_price

router = APIRouter(prefix="/api/v1/bookings", tags=["bookings"])


def _resolve_offer(db: Session, code: str | None, user_id: int) -> dict | None:
    """Return the claimed offer (as a dict) matching ``code`` for ``user_id``.

    Returns ``None`` when no code is given. Raises an HTTP 422 when a code
    is supplied but is not valid / has not been claimed by the user.
    """
    if not code:
        return None
    # Lazy import to avoid a potential circular import at module load time.
    from routers.offers import get_claimed_offer

    offer = get_claimed_offer(db, code, user_id)
    if not offer:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Offer code '{code}' is not valid or has not been claimed.",
        )
    return offer


def _calculate_price(
    db: Session,
    bike: models.Bike,
    payload: schemas.BookingRequest,
    user_id: int,
) -> dict:
    """Compute the full price breakdown for a booking request.

    Uses the demand-aware :mod:`pricing` engine which applies a
    time-of-day multiplier (morning/evening peaks rise, night falls) and
    any claimed offer discount. Raises HTTPException(422) on bad input.
    """
    if payload.offer_code:
        # Validate the offer early so an invalid code fails loudly.
        _resolve_offer(db, payload.offer_code, user_id)

    try:
        offer = _resolve_offer(db, payload.offer_code, user_id)
        return calculate_price(
            bike,
            payload.booking_type,
            payload.start_date,
            payload.start_time,
            payload.end_date,
            payload.end_time,
            offer,
        )
    except ValueError as exc:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=str(exc),
        )


def _booking_out(db: Session, booking: models.Booking) -> schemas.BookingOut:
    return schemas.BookingOut(**crud.enrich_booking(db, booking))


@router.post("/quote/{bike_id}", response_model=schemas.BookingPriceBreakdown)
def quote_booking(
    bike_id: int,
    payload: schemas.BookingRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    """Live price-quote endpoint (does NOT create a booking).

    Lets the frontend show the dynamic price breakdown - demand
    multiplier + discount - in real time before the user confirms.
    """
    bike = crud.get_bike_by_id(db, bike_id)
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")
    return _calculate_price(db, bike, payload, current_user.id)


@router.post("/{bike_id}", response_model=schemas.BookingOut, status_code=status.HTTP_201_CREATED)
def create_booking(
    bike_id: int,
    payload: schemas.BookingRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    bike = crud.get_bike_by_id(db, bike_id)
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")
    if bike.status != "available":
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="This bike is not currently available.")

    # Full price breakdown (dynamic time-of-day multiplier + claimed offer).
    breakdown = _calculate_price(db, bike, payload, current_user.id)
    total_amount = breakdown["total_amount"]

    # --- AI Fraud Detection ---
    booking_count = (
        db.query(models.Booking)
        .filter(models.Booking.customer_id == current_user.id)
        .count()
    )
    fraud_result = detect_fraud(
        current_user,
        bike,
        total_amount,
        booking_count,
    )

    if not fraud_result.get("allow_booking", True):
        raise HTTPException(
            status_code=status.HTTP_402_PAYMENT_REQUIRED,
            detail=f"Booking blocked by AI fraud detection: {fraud_result.get('risk', 'High')} risk. {', '.join(fraud_result.get('reasons', []))}",
        )

    booking_create = schemas.BookingCreate(
        customer_id=current_user.id,
        bike_id=bike_id,
        booking_type=payload.booking_type,
        start_date=payload.start_date,
        end_date=payload.end_date,
        start_time=payload.start_time,
        end_time=payload.end_time,
        total_amount=total_amount,
        base_amount=breakdown["base_amount"],
        discount_amount=breakdown["discount_amount"],
        time_multiplier=breakdown["time_multiplier"],
        discount_code=breakdown["discount_code"],
    )
    booking = crud.create_booking(db, booking_create)
    crud.update_bike_status(db, bike_id, "rented")

    return _booking_out(db, booking)


@router.get("/me", response_model=list[schemas.BookingOut])
def my_bookings(current_user: models.User = Depends(auth.get_current_user), db: Session = Depends(get_db)):
    bookings = crud.get_customer_bookings(db, current_user.id)
    return [_booking_out(db, b) for b in bookings]


@router.get("/owner", response_model=list[schemas.BookingOut])
def owner_bookings(
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    bookings = crud.get_owner_bookings(db, current_user.id)
    return [_booking_out(db, b) for b in bookings]


def _get_booking_or_404(db: Session, booking_id: int) -> models.Booking:
    booking = db.query(models.Booking).filter(models.Booking.id == booking_id).first()
    if not booking:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found.")
    return booking


def _assert_owns_booking_bike(db: Session, booking: models.Booking, current_user: models.User):
    if current_user.role == "admin":
        return
    bike = crud.get_bike_by_id(db, booking.bike_id)
    if not bike or bike.owner_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="This booking isn't for one of your bikes.")


def _assert_customer_owns_booking(booking: models.Booking, current_user: models.User):
    if booking.customer_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="This is not your booking.")


@router.post("/{booking_id}/approve", response_model=schemas.BookingOut)
def approve_booking(
    booking_id: int,
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    booking = _get_booking_or_404(db, booking_id)
    _assert_owns_booking_bike(db, booking, current_user)
    try:
        crud.update_booking_status(db, booking_id, "Approved")
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_402_PAYMENT_REQUIRED, detail=str(e))
    return _booking_out(db, _get_booking_or_404(db, booking_id))


@router.post("/{booking_id}/reject", response_model=schemas.BookingOut)
def reject_booking(
    booking_id: int,
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    booking = _get_booking_or_404(db, booking_id)
    _assert_owns_booking_bike(db, booking, current_user)
    crud.update_booking_status(db, booking_id, "Rejected")
    bike = crud.get_bike_by_id(db, booking.bike_id)
    if bike:
        crud.update_bike_status(db, bike.id, "available")
    return _booking_out(db, _get_booking_or_404(db, booking_id))


@router.post("/{booking_id}/complete", response_model=schemas.BookingOut)
def complete_booking(
    booking_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    booking = _get_booking_or_404(db, booking_id)
    _assert_customer_owns_booking(booking, current_user)
    completed = crud.complete_booking(db, booking_id)
    if not completed:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found.")
    return _booking_out(db, completed)


@router.post("/{booking_id}/extend", response_model=schemas.BookingOut)
def extend_booking(
    booking_id: int,
    payload: schemas.ExtendBookingRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    booking = _get_booking_or_404(db, booking_id)
    _assert_customer_owns_booking(booking, current_user)
    extended = crud.extend_booking(db, booking_id, max(1, payload.extra_hours))
    if not extended:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found.")
    return _booking_out(db, extended)
