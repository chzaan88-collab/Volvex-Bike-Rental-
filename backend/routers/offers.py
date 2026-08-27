from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import models
from database import get_db

router = APIRouter(prefix="/api/v1/offers", tags=["offers"])

OFFERS_TABLE = "offers"
OFFER_USER_TABLE = "offer_user"


def _ensure_offers_tables(db: Session):
    """Create the offers + offer_user junction tables if they do not exist yet."""
    from sqlalchemy import inspect, text
    inspector = inspect(db.get_bind())

    if OFFERS_TABLE not in inspector.get_table_names():
        db.execute(text("""
            CREATE TABLE offers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code VARCHAR NOT NULL UNIQUE,
                title VARCHAR NOT NULL,
                description TEXT,
                discount_type VARCHAR NOT NULL DEFAULT 'percent',
                discount_value FLOAT NOT NULL DEFAULT 0,
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        """))

    if OFFER_USER_TABLE not in inspector.get_table_names():
        db.execute(text("""
            CREATE TABLE offer_user (
                offer_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                claimed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (offer_id, user_id)
            )
        """))

    db.commit()


def _seed_default_offers(db: Session):
    from sqlalchemy import text
    count = db.execute(text("SELECT COUNT(*) FROM offers")).scalar()
    if count and count > 0:
        return

    default_offers = [
        {
            "code": "WEEKEND20",
            "title": "Weekend Getaway",
            "description": "Get 20% off long-distance rentals starting Friday evening.",
            "discount_type": "percent",
            "discount_value": 20.0,
        },
        {
            "code": "WELCOME10",
            "title": "Welcome Bonus",
            "description": "Enjoy 10% off your first rental.",
            "discount_type": "percent",
            "discount_value": 10.0,
        },
    ]
    for offer in default_offers:
        db.execute(
            text("""
                INSERT INTO offers (code, title, description, discount_type, discount_value)
                VALUES (:code, :title, :description, :discount_type, :discount_value)
            """),
            offer,
        )
    db.commit()


def get_claimed_offer(db: Session, code: str, user_id: int) -> dict | None:
    """Return a claimed, non-expired offer (as a dict) for the user.

    Returns ``None`` when the code is empty / not claimed / expired, so the
    caller can decide how to handle an invalid code.
    """
    _ensure_offers_tables(db)
    _seed_default_offers(db)
    from sqlalchemy import text

    code = (code or "").strip().upper()
    if not code:
        return None

    row = db.execute(
        text("""
            SELECT o.* FROM offers o
            JOIN offer_user ou ON ou.offer_id = o.id
            WHERE UPPER(o.code) = :code AND ou.user_id = :uid
        """),
        {"code": code, "uid": user_id},
    ).fetchone()

    if not row:
        return None

    # Honour expiry if one is set.
    if row.expires_at:
        try:
            exp = datetime.strptime(str(row.expires_at), "%Y-%m-%d %H:%M:%S")
        except (ValueError, TypeError):
            exp = None
        if exp and datetime.utcnow() > exp:
            return None

    return {
        "id": row.id,
        "code": row.code,
        "title": row.title,
        "description": row.description,
        "discount_type": row.discount_type,
        "discount_value": row.discount_value,
        "expires_at": str(row.expires_at) if row.expires_at else None,
    }


@router.get("")
def list_offers(
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    _ensure_offers_tables(db)
    _seed_default_offers(db)
    from sqlalchemy import text
    rows = db.execute(text("""
        SELECT o.*,
               EXISTS(SELECT 1 FROM offer_user ou WHERE ou.offer_id = o.id AND ou.user_id = :uid) AS claimed
        FROM offers o
        ORDER BY o.id DESC
    """), {"uid": current_user.id}).fetchall()
    return [
        {
            "id": r.id,
            "code": r.code,
            "title": r.title,
            "description": r.description,
            "discount_type": r.discount_type,
            "discount_value": r.discount_value,
            "expires_at": str(r.expires_at) if r.expires_at else None,
            "claimed": bool(r.claimed),
        }
        for r in rows
    ]


@router.post("/claim")
def claim_offer(
    payload: dict,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    _ensure_offers_tables(db)
    _seed_default_offers(db)

    code = (payload.get("code") or "").strip().upper()
    if not code:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Offer code is required.")

    from sqlalchemy import text
    row = db.execute(
        text("SELECT * FROM offers WHERE UPPER(code) = :code"),
        {"code": code},
    ).fetchone()
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Offer not found.")

    already = db.execute(
        text("SELECT 1 FROM offer_user WHERE offer_id = :oid AND user_id = :uid"),
        {"oid": row.id, "uid": current_user.id},
    ).fetchone()
    if already:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="You have already claimed this offer.")

    db.execute(
        text("INSERT INTO offer_user (offer_id, user_id, claimed_at) VALUES (:oid, :uid, CURRENT_TIMESTAMP)"),
        {"oid": row.id, "uid": current_user.id},
    )
    db.commit()

    return {"message": f"Offer '{row.title}' claimed successfully!", "offer": {
        "id": row.id,
        "code": row.code,
        "title": row.title,
        "description": row.description,
        "discount_type": row.discount_type,
        "discount_value": row.discount_value,
        "expires_at": str(row.expires_at) if row.expires_at else None,
        "claimed": True,
    }}
