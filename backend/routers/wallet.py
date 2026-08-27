from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db

router = APIRouter(prefix="/api/v1/wallet", tags=["wallet"])

MAX_TOPUP_AMOUNT = 10000.0


@router.get("/balance", response_model=schemas.UserOut)
def get_balance(current_user: models.User = Depends(auth.get_current_user)):
    return current_user


@router.get("/transactions", response_model=list[schemas.WalletTransactionOut])
def list_transactions(
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    return crud.get_wallet_transactions(db, current_user.id)


@router.post("/topup", response_model=schemas.UserOut)
def topup(
    payload: schemas.WalletTopupRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    if payload.amount <= 0:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Amount must be positive.")
    if payload.amount > MAX_TOPUP_AMOUNT:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Top-ups are capped at {MAX_TOPUP_AMOUNT} until real payment processing is wired in.",
        )

    current_user.wallet_balance = (current_user.wallet_balance or 0.0) + payload.amount
    crud.create_wallet_transaction(
        db,
        current_user.id,
        payload.amount,
        "credit",
        "Wallet top-up",
    )
    db.commit()
    db.refresh(current_user)
    return current_user
