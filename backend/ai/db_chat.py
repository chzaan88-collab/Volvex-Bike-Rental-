from sqlalchemy.orm import Session
import models


def get_available_bikes(db: Session):

    bikes = (
        db.query(models.Bike)
        .filter(models.Bike.status == "available")
        .all()
    )

    result = []

    for bike in bikes:

        result.append({
            "id": bike.id,
            "name": bike.bike_name,
            "brand": bike.brand,
            "city": bike.city,
            "type": bike.bike_type,
            "hour": bike.price_per_hour,
            "day": bike.price_per_day,
            "cc": bike.engine_cc
        })

    return result