from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db
from ai.review_sentiment import analyze_review

router = APIRouter(prefix="/api/v1/reviews", tags=["reviews"])


@router.post("", status_code=status.HTTP_201_CREATED)
def create_review(
    payload: schemas.ReviewCreate,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    # Ensure the booking belongs to this customer
    booking = db.query(models.Booking).filter(models.Booking.id == payload.booking_id).first()
    if not booking:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found.")
    if booking.customer_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="This is not your booking.")

    # Ensure the booking is completed
    if booking.status != "Completed":
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="You can only review completed rides.")

    # Check for duplicate review
    existing = db.query(models.Review).filter(models.Review.booking_id == payload.booking_id).first()
    if existing:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="You have already reviewed this ride.")

    bike = crud.get_bike_by_id(db, payload.bike_id)
    owner_id = bike.owner_id if bike else None

    # --- AI Sentiment Analysis ---
    sentiment = "Neutral"
    ai_score = None
    try:
        analysis = analyze_review(payload.review)
        sentiment = analysis.get("sentiment", "Neutral")
        ai_score = float(analysis.get("rating", 0) or 0)
    except Exception:
        # Non-fatal — review still saved without AI analysis
        pass

    review = models.Review(
        booking_id=payload.booking_id,
        bike_id=payload.bike_id,
        customer_id=current_user.id,
        owner_id=owner_id,
        rating=payload.rating,
        review=payload.review,
        sentiment=sentiment,
        ai_score=ai_score,
    )
    db.add(review)
    db.commit()
    db.refresh(review)

    return {
        "id": review.id,
        "booking_id": review.booking_id,
        "bike_id": review.bike_id,
        "customer_id": review.customer_id,
        "owner_id": review.owner_id,
        "rating": review.rating,
        "review": review.review,
        "sentiment": review.sentiment,
        "ai_score": review.ai_score,
        "created_at": str(review.created_at) if review.created_at else None,
    }


@router.get("/bike/{bike_id}")
def bike_reviews(bike_id: int, db: Session = Depends(get_db)):
    reviews = db.query(models.Review).filter(models.Review.bike_id == bike_id).all()
    return [
        {
            "id": r.id,
            "booking_id": r.booking_id,
            "bike_id": r.bike_id,
            "customer_id": r.customer_id,
            "owner_id": r.owner_id,
            "rating": r.rating,
            "review": r.review,
            "created_at": str(r.created_at) if r.created_at else None,
        }
        for r in reviews
    ]


@router.get("/mine")
def my_reviews(
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    reviews = db.query(models.Review).filter(models.Review.customer_id == current_user.id).all()
    return [
        {
            "id": r.id,
            "booking_id": r.booking_id,
            "bike_id": r.bike_id,
            "customer_id": r.customer_id,
            "owner_id": r.owner_id,
            "rating": r.rating,
            "review": r.review,
            "created_at": str(r.created_at) if r.created_at else None,
        }
        for r in reviews
    ]