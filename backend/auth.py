"""
JWT-based authentication for the JSON API layer.

Laravel authenticates once via POST /api/v1/auth/login, receives a bearer
token, stores it server-side (in the PHP session), and attaches it as
`Authorization: Bearer <token>` on every subsequent request made on behalf
of that logged-in user.
"""
from datetime import datetime, timedelta, timezone

import jwt
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy.orm import Session

import config
import crud
import models
from database import get_db

bearer_scheme = HTTPBearer(auto_error=False)


def create_access_token(user_id: int, role: str) -> str:
    expire = datetime.now(timezone.utc) + timedelta(minutes=config.ACCESS_TOKEN_EXPIRE_MINUTES)
    payload = {"sub": str(user_id), "role": role, "exp": expire}
    return jwt.encode(payload, config.SECRET_KEY, algorithm=config.ALGORITHM)


def decode_access_token(token: str) -> dict:
    try:
        return jwt.decode(token, config.SECRET_KEY, algorithms=[config.ALGORITHM])
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Token expired")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token")


def get_current_user(
    credentials: HTTPAuthorizationCredentials = Depends(bearer_scheme),
    db: Session = Depends(get_db),
) -> models.User:
    """Required auth — raises 401 if no valid token is present."""
    if credentials is None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Not authenticated")

    payload = decode_access_token(credentials.credentials)
    user = crud.get_user_by_id(db, int(payload["sub"]))
    if user is None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="User no longer exists")

    return user


def get_optional_user(
    credentials: HTTPAuthorizationCredentials = Depends(bearer_scheme),
    db: Session = Depends(get_db),
) -> models.User | None:
    """Optional auth — returns None instead of raising, for public-ish endpoints."""
    if credentials is None:
        return None
    try:
        payload = decode_access_token(credentials.credentials)
        return crud.get_user_by_id(db, int(payload["sub"]))
    except HTTPException:
        return None


def require_role(*roles: str):
    """Dependency factory: require_role("owner", "admin") guards a route to those roles."""
    def _check(user: models.User = Depends(get_current_user)) -> models.User:
        is_owner_capable = user.role in ("owner", "admin") or getattr(user, "account_mode", "rider") == "owner"
        if "owner" in roles and is_owner_capable:
            return user
        if user.role in roles:
            return user
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Not authorized for this action")
    return _check
