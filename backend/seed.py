"""
Seed demo users and bikes for local development.

Usage (from backend/):
    python seed.py
"""
from database import SessionLocal, engine
import models
import schemas
import crud

models.Base.metadata.create_all(bind=engine)

DEMO_BIKES = [
    {
        "bike_name": "Honda CD70 Standard",
        "brand": "Honda",
        "model": "CD70",
        "bike_type": "Standard",
        "registration_number": "ABC-1234",
        "color": "Red",
        "city": "Karachi",
        "price_per_hour": 5.0,
        "price_per_day": 18.5,
        "engine_cc": "72cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Reliable urban commuter with legendary fuel efficiency.",
        "gps": "Yes",
        "helmet": "Included",
        "image": "https://placehold.co/600x450?text=Honda+CD70",
        "documents": "",
    },
    {
        "bike_name": "Yamaha YBR 125",
        "brand": "Yamaha",
        "model": "YBR 125",
        "bike_type": "Sport",
        "registration_number": "XYZ-5678",
        "color": "Blue",
        "city": "Lahore",
        "price_per_hour": 8.0,
        "price_per_day": 25.0,
        "engine_cc": "125cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Smooth ride for city and highway trips.",
        "gps": "Yes",
        "helmet": "Included",
        "image": "https://placehold.co/600x450?text=Yamaha+YBR",
        "documents": "",
    },
    {
        "bike_name": "Suzuki GS150",
        "brand": "Suzuki",
        "model": "GS150",
        "bike_type": "Touring",
        "registration_number": "DEF-9012",
        "color": "Black",
        "city": "Islamabad",
        "price_per_hour": 10.0,
        "price_per_day": 32.0,
        "engine_cc": "150cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Comfortable touring bike for longer rides.",
        "gps": "Yes",
        "helmet": "Included",
        "image": "https://placehold.co/600x450?text=Suzuki+GS150",
        "documents": "",
    },
    {
        "bike_name": "Honda CG125",
        "brand": "Honda",
        "model": "CG125",
        "bike_type": "Standard",
        "registration_number": "GHI-3456",
        "color": "Green",
        "city": "Karachi",
        "price_per_hour": 6.0,
        "price_per_day": 20.0,
        "engine_cc": "125cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Classic workhorse for daily commuting.",
        "gps": "No",
        "helmet": "Included",
        "image": "https://placehold.co/600x450?text=Honda+CG125",
        "documents": "",
    },
    {
        "bike_name": "United US125",
        "brand": "United",
        "model": "US125",
        "bike_type": "Economy",
        "registration_number": "JKL-7890",
        "color": "Silver",
        "city": "Lahore",
        "price_per_hour": 4.0,
        "price_per_day": 15.0,
        "engine_cc": "125cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Budget-friendly option for short trips.",
        "gps": "No",
        "helmet": "Optional",
        "image": "https://placehold.co/600x450?text=United+US125",
        "documents": "",
    },
    {
        "bike_name": "Honda Pridor",
        "brand": "Honda",
        "model": "Pridor",
        "bike_type": "Standard",
        "registration_number": "MNO-1122",
        "color": "White",
        "city": "Islamabad",
        "price_per_hour": 7.0,
        "price_per_day": 22.0,
        "engine_cc": "100cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Efficient and easy to handle in traffic.",
        "gps": "Yes",
        "helmet": "Included",
        "image": "https://placehold.co/600x450?text=Honda+Pridor",
        "documents": "",
    },
]


def seed():
    db = SessionLocal()
    try:
        owner = crud.get_user_by_email(db, "owner@velex.test")
        if not owner:
            owner = crud.create_user(db, schemas.UserCreate(
                full_name="Demo Owner",
                email="owner@velex.test",
                phone="03001234567",
                cnic="35202-1234567-1",
                password="password",
                role="owner",
            ))
            owner.account_mode = "owner"
            db.commit()
            print("Created owner@velex.test / password")
        else:
            print("Owner already exists")

        rider = crud.get_user_by_email(db, "rider@velex.test")
        if not rider:
            rider = crud.create_user(db, schemas.UserCreate(
                full_name="Demo Rider",
                email="rider@velex.test",
                phone="03007654321",
                cnic="35202-7654321-9",
                password="password",
                role="customer",
            ))
            rider.account_mode = "rider"
            db.commit()
            print("Created rider@velex.test / password")
        else:
            print("Rider already exists")

        existing = db.query(models.Bike).filter(models.Bike.owner_id == owner.id).count()
        if existing == 0:
            for bike_data in DEMO_BIKES:
                payload = schemas.BikeCreate(owner_id=owner.id, **bike_data)
                crud.create_bike(db, payload)
            print("Created", len(DEMO_BIKES), "demo bikes")
        else:
            print("Owner already has bikes, skipping bike seed")

        print("Seed complete.")
    finally:
        db.close()


if __name__ == "__main__":
    seed()
