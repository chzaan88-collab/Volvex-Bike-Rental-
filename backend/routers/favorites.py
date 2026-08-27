from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db

router = APIRouter(prefix="/api/v1/favorites", tags=["favorites"])

FAVORITES_TABLE = "favorites"


def _ensure_favorites_table(db: Session):
    """Create the favorites junction table if it does not exist yet."""
    from sqlalchemy import inspect, text
    inspector = inspect(db.get_bind())
    if FAVORITES_TABLE not in inspector.get_table_names():
        db.execute(text("""
            CREATE TABLE favorites (
                user_id INTEGER NOT NULL,
                bike_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, bike_id)
            )
        """))
        db.commit()


@router.get("", response_model=list[schemas.BikeOut])
def my_favorites(
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    _ensure_favorites_table(db)
    from sqlalchemy import text
    rows = db.execute(
        text("SELECT bike_id FROM favorites WHERE user_id = :uid ORDER BY created_at DESC"),
        {"uid": current_user.id},
    ).fetchall()
    bike_ids = [r[0] for r in rows]
    bikes = []
    for bid in bike_ids:
        b = crud.get_bike_by_id(db, bid)
        if b:
            bikes.append(b)
    return bikes


@router.post("/{bike_id}", status_code=status.HTTP_200_OK)
def add_favorite(
    bike_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    _ensure_favorites_table(db)
    bike = crud.get_bike_by_id(db, bike_id)
    if not bike:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Bike not found.")

    from sqlalchemy import text
    existing = db.execute(
        text("SELECT 1 FROM favorites WHERE user_id = :uid AND bike_id = :bid"),
        {"uid": current_user.id, "bid": bike_id},
    ).fetchone()
    if not existing:
        db.execute(
            text("INSERT INTO favorites (user_id, bike_id, created_at) VALUES (:uid, :bid, CURRENT_TIMESTAMP)"),
            {"uid": current_user.id, "bid": bike_id},
        )
        db.commit()
    return {"message": "Bike added to favorites."}


@router.delete("/{bike_id}", status_code=status.HTTP_200_OK)
def remove_favorite(
    bike_id: int,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    _ensure_favorites_table(db)
    from sqlalchemy import text
    db.execute(
        text("DELETE FROM favorites WHERE user_id = :uid AND bike_id = :bid"),
        {"uid": current_user.id, "bid": bike_id},
    )
    db.commit()
    return {"message": "Bike removed from favorites."}