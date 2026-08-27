import os
from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db
from pdf.generator import generate_agreement

router = APIRouter(prefix="/api/v1/agreements", tags=["agreements"])


def _get_booking_or_404(db: Session, booking_id: int) -> models.Booking:
    booking = db.query(models.Booking).filter(models.Booking.id == booking_id).first()
    if not booking:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found.")
    return booking


def _generate_pdf_file(db: Session, booking: models.Booking) -> str:
    """Generate a professional agreement PDF and return the file path."""
    bike = crud.get_bike_by_id(db, booking.bike_id)
    customer = crud.get_user_by_id(db, booking.customer_id)
    owner = crud.get_user_by_id(db, bike.owner_id) if bike else None

    os.makedirs("agreements", exist_ok=True)
    agreement_id = f"AGR-{booking.id:05d}"
    pdf_path = f"agreements/{agreement_id}.pdf"

    generate_agreement(
        output_target=pdf_path,
        agreement_id=agreement_id,
        booking=booking,
        owner=owner,
        customer=customer,
        bike=bike,
        ai_risk=None,
    )
    return pdf_path


def _create_or_update_agreement(db: Session, booking: models.Booking, pdf_path: str):
    bike = crud.get_bike_by_id(db, booking.bike_id)
    owner = crud.get_user_by_id(db, bike.owner_id) if bike else None
    customer = crud.get_user_by_id(db, booking.customer_id)

    existing = crud.get_agreement(db, booking.id)
    if existing:
        existing.agreement_file = pdf_path
        existing.accepted = "Accepted"
        db.commit()
        db.refresh(existing)
        return existing

    new_agreement = schemas.AgreementCreate(
        booking_id=booking.id,
        customer_id=customer.id,
        owner_id=owner.id,
        agreement_file=pdf_path,
        accepted="Accepted",
    )
    return crud.create_agreement(db, new_agreement)


@router.post("/{booking_id}/generate", status_code=status.HTTP_201_CREATED)
def generate_booking_agreement(
    booking_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    """Generate / regenerate the PDF agreement for a booking (owner, customer, or admin)."""
    booking = _get_booking_or_404(db, booking_id)
    bike = crud.get_bike_by_id(db, booking.bike_id)

    is_admin = current_user.role == "admin"
    is_owner = bike and bike.owner_id == current_user.id
    is_customer = booking.customer_id == current_user.id
    is_owner_mode = getattr(current_user, "account_mode", "rider") == "owner"

    if not (is_admin or is_owner or is_owner_mode or is_customer):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Not authorized to generate agreement.")

    pdf_path = _generate_pdf_file(db, booking)
    agreement = _create_or_update_agreement(db, booking, pdf_path)

    return {
        "agreement_id": f"AGR-{booking.id:05d}",
        "booking_id": booking.id,
        "pdf_path": pdf_path,
        "accepted": agreement.accepted,
        "download_url": f"/api/v1/agreements/{booking.id}/download",
    }


@router.get("/{booking_id}/download")
def download_booking_agreement(
    booking_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    """Download the generated agreement PDF for a booking."""
    booking = _get_booking_or_404(db, booking_id)
    bike = crud.get_bike_by_id(db, booking.bike_id)

    is_admin = current_user.role == "admin"
    is_owner = bike and bike.owner_id == current_user.id
    is_customer = booking.customer_id == current_user.id
    is_owner_mode = getattr(current_user, "account_mode", "rider") == "owner"

    if not (is_admin or is_owner or is_owner_mode or is_customer):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Not authorized to download agreement.")

    agreement = crud.get_agreement(db, booking_id)
    if not agreement or not agreement.agreement_file:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Agreement PDF not found.")

    file_path = agreement.agreement_file
    if not os.path.exists(file_path):
        # Auto-regenerate if missing
        file_path = _generate_pdf_file(db, booking)
        agreement.agreement_file = file_path
        db.commit()

    return FileResponse(file_path, filename=f"Agreement_{booking_id}.pdf", media_type="application/pdf")


@router.get("/{booking_id}/status", response_model=schemas.AgreementOut)
def agreement_status(
    booking_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    """Check whether an agreement exists for a booking."""
    booking = _get_booking_or_404(db, booking_id)
    bike = crud.get_bike_by_id(db, booking.bike_id)

    is_admin = current_user.role == "admin"
    is_owner = bike and bike.owner_id == current_user.id
    is_customer = booking.customer_id == current_user.id
    is_owner_mode = getattr(current_user, "account_mode", "rider") == "owner"

    if not (is_admin or is_owner or is_owner_mode or is_customer):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Not authorized.")

    agreement = crud.get_agreement(db, booking_id)
    if not agreement:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Agreement not found.")

    return schemas.AgreementOut(
        id=agreement.id,
        booking_id=agreement.booking_id,
        customer_id=agreement.customer_id,
        owner_id=agreement.owner_id,
        agreement_file=agreement.agreement_file,
        accepted=agreement.accepted,
    )