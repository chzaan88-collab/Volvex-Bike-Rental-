import os
from fastapi import APIRouter, Depends, File, HTTPException, UploadFile, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db

from ai.chat_service import chat
from ai.price_service import predict_price
from ai.fraud_service import detect_fraud
from ai.maintenance_service import predict_maintenance
from ai.demand_service import predict_demand
from ai.recommendation_service import recommend_bikes
from ai.review_sentiment import analyze_review
from ai.agreement_analyzer import analyze_agreement, analyze_agreement_pdf
from ai.vector_store import index_bikes, search

router = APIRouter(prefix="/api/v1/ai", tags=["ai"])


@router.post("/chat")
def ai_chat(
    request: schemas.ChatRequest,
    db: Session = Depends(get_db),
):
    """AI Chatbot with intent routing and DB context."""
    return chat(request.message, db)


@router.post("/recommend-bike")
def ai_recommend(
    request: schemas.RecommendationRequest,
    db: Session = Depends(get_db),
):
    """Personalized bike recommendations."""
    bikes = db.query(models.Bike).all()
    result = recommend_bikes(bikes, request.dict())

    response = []
    for item in result:
        bike = item["bike"]
        response.append({
            "bike_name": bike.bike_name,
            "brand": bike.brand,
            "model": bike.model,
            "bike_type": bike.bike_type,
            "city": bike.city,
            "price_per_day": bike.price_per_day,
            "score": item["score"],
            "reasons": item["reasons"],
        })

    return {"recommendations": response}


@router.post("/price-prediction")
def ai_price_prediction(
    request: schemas.PricePredictionRequest,
):
    """AI price prediction / cost estimation."""
    result = predict_price(request)
    return {"prediction": result}


@router.get("/price-prediction/{bike_id}")
def ai_price_prediction_by_bike(
    bike_id: int,
    db: Session = Depends(get_db),
):
    """AI price prediction for a specific bike."""
    bike = db.query(models.Bike).filter(models.Bike.id == bike_id).first()
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")
    result = predict_price(bike)
    return result


@router.post("/demand-forecast")
def ai_demand_forecast(
    request: schemas.DemandForecastRequest,
):
    """AI demand forecasting / trend analysis."""
    result = predict_demand(request.dict())
    return {"forecast": result}


@router.post("/fraud-detection")
def ai_fraud_detection(
    data: schemas.FraudRequest,
    db: Session = Depends(get_db),
):
    """AI fraud detection / anomaly detection."""
    customer = db.query(models.User).filter(models.User.id == data.customer_id).first()
    bike = db.query(models.Bike).filter(models.Bike.id == data.bike_id).first()
    booking_count = db.query(models.Booking).filter(
        models.Booking.customer_id == data.customer_id
    ).count()

    result = detect_fraud(customer, bike, data.booking_amount, booking_count)
    return {"analysis": result}


@router.post("/maintenance-prediction")
def ai_maintenance_prediction(
    data: schemas.MaintenanceRequest,
    db: Session = Depends(get_db),
):
    """AI predictive maintenance."""
    bike = db.query(models.Bike).filter(models.Bike.id == data.bike_id).first()
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")

    booking_count = db.query(models.Booking).filter(
        models.Booking.bike_id == bike.id
    ).count()

    reviews = db.query(models.Review).filter(models.Review.bike_id == bike.id).all()
    average_rating = None
    if reviews:
        average_rating = sum(r.rating for r in reviews) / len(reviews)

    result = predict_maintenance(bike, booking_count, average_rating)
    return {"analysis": result}


@router.post("/review-analysis")
def ai_review_analysis(data: schemas.ReviewRequest):
    """AI review sentiment analysis."""
    result = analyze_review(data.review)
    return {"analysis": result}


@router.post("/agreement-analysis")
def ai_agreement_analysis(data: dict):
    """AI agreement / document analysis (text input)."""
    result = analyze_agreement(data.get("agreement", ""))
    return {"analysis": result}


@router.post("/agreement-analysis/pdf")
async def ai_agreement_analysis_pdf(
    file: UploadFile = File(...),
):
    """AI agreement / document analysis (PDF upload)."""
    if not file.filename.lower().endswith(".pdf"):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Only PDF files are supported.",
        )

    pdf_bytes = await file.read()
    result = analyze_agreement_pdf(pdf_bytes)
    return {"analysis": result}


@router.post("/semantic-search")
def ai_semantic_search(
    data: dict,
    db: Session = Depends(get_db),
):
    """Semantic search over bikes using vector embeddings."""
    query = data.get("query", "")
    top_k = int(data.get("top_k", 5))

    if not query:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Query is required.")

    # Index bikes if the store is empty
    bikes = db.query(models.Bike).all()
    if bikes:
        index_bikes(bikes)

    results = search(query, top_k=top_k)
    return {"results": results}


@router.post("/index-bikes")
def ai_index_bikes(
    db: Session = Depends(get_db),
):
    """Index all bikes into the vector store."""
    bikes = db.query(models.Bike).all()
    index_bikes(bikes)
    return {"indexed": len(bikes)}