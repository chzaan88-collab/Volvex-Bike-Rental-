import os
import shutil
import uuid

from fastapi import APIRouter, Depends, File, Form, HTTPException, UploadFile, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db

router = APIRouter(prefix="/api/v1/bikes", tags=["bikes"])


@router.get("", response_model=list[schemas.BikeOut])
def list_bikes(city: str | None = None, bike_type: str | None = None, db: Session = Depends(get_db)):
    bikes = crud.get_available_bikes(db)
    if city:
        bikes = [b for b in bikes if b.city.lower() == city.lower()]
    if bike_type:
        bikes = [b for b in bikes if b.bike_type.lower() == bike_type.lower()]
    return bikes


@router.get("/mine", response_model=list[schemas.BikeOut])
def my_bikes(
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    return crud.get_owner_bikes(db, current_user.id)


@router.get("/recommendations", response_model=list[schemas.BikeOut])
def recommend_bikes_by_location(
    location: str = "Karachi",
    current_user: models.User = Depends(auth.get_optional_user),
    db: Session = Depends(get_db),
):
    """Return bike recommendations based on the user's location.

    Bikes from the user's city are ranked first. If not enough bikes exist
    in that city, bikes from all available cities fill the rest so the user
    always gets a full recommendation list.
    """
    location = (location or "").strip().lower()
    all_bikes = crud.get_available_bikes(db)

    # Prioritize bikes in the user's location.
    local = [b for b in all_bikes if b.city and b.city.lower() == location]
    others = [b for b in all_bikes if not (b.city and b.city.lower() == location)]

    # Keep same-city bikes first, then fill with the rest for variety.
    return (local + others)[:12]


@router.get("/{bike_id}", response_model=schemas.BikeOut)
def get_bike(bike_id: int, db: Session = Depends(get_db)):
    bike = crud.get_bike_by_id(db, bike_id)
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")
    return bike


@router.post("", response_model=schemas.BikeOut, status_code=status.HTTP_201_CREATED)
def create_bike(
    payload: schemas.BikeCreate,
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    payload = payload.model_copy(update={"owner_id": current_user.id})
    return crud.create_bike(db, payload)


@router.post("/upload", response_model=schemas.BikeOut, status_code=status.HTTP_201_CREATED)
async def create_bike_with_image(
    bike_name: str = Form(...),
    brand: str = Form(...),
    model: str = Form(...),
    bike_type: str = Form(...),
    registration_number: str = Form(...),
    color: str = Form(...),
    city: str = Form(...),
    price_per_hour: float = Form(...),
    price_per_day: float = Form(...),
    price_per_month: float = Form(0),
    engine_cc: str = Form(...),
    fuel_type: str = Form(...),
    transmission: str = Form(...),
    description: str = Form(""),
    gps: str = Form("No"),
    helmet: str = Form("No"),
    image: UploadFile = File(...),
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    """Create a bike with a required image file upload."""
    if not image or not image.filename:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Bike image is required.",
        )

    # Ensure uploads directory exists
    upload_dir = os.path.join("static", "uploads")
    os.makedirs(upload_dir, exist_ok=True)

    ext = os.path.splitext(image.filename)[1] or ".jpg"
    filename = f"{uuid.uuid4()}{ext}"
    file_path = os.path.join(upload_dir, filename)

    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(image.file, buffer)

    image_url = f"/static/uploads/{filename}"

    payload = schemas.BikeCreate(
        owner_id=current_user.id,
        bike_name=bike_name,
        brand=brand,
        model=model,
        bike_type=bike_type,
        registration_number=registration_number,
        color=color,
        city=city,
        price_per_hour=price_per_hour,
        price_per_day=price_per_day,
        price_per_month=price_per_month,
        engine_cc=engine_cc,
        fuel_type=fuel_type,
        transmission=transmission,
        description=description,
        gps=gps,
        helmet=helmet,
        image=image_url,
        documents="",
    )
    return crud.create_bike(db, payload)


@router.delete("/{bike_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_bike(
    bike_id: int,
    current_user: models.User = Depends(auth.require_role("owner", "admin")),
    db: Session = Depends(get_db),
):
    bike = crud.get_bike_by_id(db, bike_id)
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")
    if current_user.role == "owner" and bike.owner_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="You don't own this bike.")

    crud.delete_bike(db, bike_id)
