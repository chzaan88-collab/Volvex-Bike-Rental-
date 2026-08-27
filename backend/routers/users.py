from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

import auth
import crud
import models
import schemas
from database import get_db

router = APIRouter(prefix="/api/v1/users", tags=["users"])


@router.patch("/me", response_model=schemas.UserOut)
def update_profile(
    payload: schemas.ProfileUpdateRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    if payload.full_name is not None:
        current_user.full_name = payload.full_name
    if payload.phone is not None:
        current_user.phone = payload.phone
    if payload.cnic is not None:
        current_user.cnic = payload.cnic
    if payload.location is not None:
        current_user.location = payload.location

    db.commit()
    db.refresh(current_user)
    return current_user


@router.post("/me/password", status_code=status.HTTP_204_NO_CONTENT)
def update_password(
    payload: schemas.PasswordUpdateRequest,
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    if not crud.verify_password(payload.current_password, current_user.password):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Current password is incorrect.")

    current_user.password = crud.hash_password(payload.new_password)
    db.commit()


@router.post("/me/switch-mode", response_model=schemas.UserOut)
def switch_mode(
    current_user: models.User = Depends(auth.get_current_user),
    db: Session = Depends(get_db),
):
    return crud.toggle_account_mode(db, current_user)
