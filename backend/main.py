from fastapi import FastAPI, Request, Form, Depends, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from fastapi.responses import RedirectResponse, FileResponse
from sqlalchemy.orm import Session
from sqlalchemy import text, inspect
import shutil
import uuid
import os
from ai.recommendation import RecommendationRequest
from ai.recommendation_service import recommend_bikes
from database import engine, get_db
import models
import crud
import schemas
from pdf.generator import generate_agreement
from ai.agreement_analyzer import analyze_agreement
from ai.review_sentiment import analyze_review
from schemas import ReviewRequest, PricePredictionRequest
from ai.price_service import predict_price
from ai.fraud_service import detect_fraud
from ai.maintenance_service import predict_maintenance
from pydantic import BaseModel
from ai.demand_service import predict_demand
from schemas import DemandForecastRequest
from ai.chat_service import chat
from schemas import ChatRequest
from database import get_db
from datetime import datetime

from routers import auth as auth_router
from routers import bikes as bikes_router
from routers import bookings as bookings_router
from routers import wallet as wallet_router
from routers import users as users_router
from routers import favorites as favorites_router
from routers import offers as offers_router
from routers import reviews as reviews_router
from routers import agreements as agreements_router
from routers import ai as ai_router

# Ensure database tables exist
models.Base.metadata.create_all(bind=engine)

# Database schema migrations for wallet_balance and reward_points.
# Uses PRAGMA/inspector to check column existence first instead of a bare
# try/except that would silently swallow *any* error, not just "already exists".
inspector = inspect(engine)
existing_user_columns = {col["name"] for col in inspector.get_columns("users")}

with engine.connect() as conn:
    if "wallet_balance" not in existing_user_columns:
        conn.execute(text("ALTER TABLE users ADD COLUMN wallet_balance FLOAT DEFAULT 2500.0;"))
        conn.commit()
    if "reward_points" not in existing_user_columns:
        conn.execute(text("ALTER TABLE users ADD COLUMN reward_points INTEGER DEFAULT 120;"))
        conn.commit()
    if "account_mode" not in existing_user_columns:
        conn.execute(text("ALTER TABLE users ADD COLUMN account_mode VARCHAR DEFAULT 'rider';"))
        conn.commit()

    # --- bikes: monthly price (added for dynamic pricing support) ---
    existing_bike_columns = {col["name"] for col in inspector.get_columns("bikes")}
    if "price_per_month" not in existing_bike_columns:
        conn.execute(text("ALTER TABLE bikes ADD COLUMN price_per_month FLOAT DEFAULT 0.0;"))
        conn.commit()

    # --- bookings: auditable price-breakdown columns ---
    existing_booking_columns = {col["name"] for col in inspector.get_columns("bookings")}
    for _col, _ddl in [
        ("base_amount", "ALTER TABLE bookings ADD COLUMN base_amount FLOAT DEFAULT 0.0;"),
        ("discount_amount", "ALTER TABLE bookings ADD COLUMN discount_amount FLOAT DEFAULT 0.0;"),
        ("time_multiplier", "ALTER TABLE bookings ADD COLUMN time_multiplier FLOAT DEFAULT 1.0;"),
        ("discount_code", "ALTER TABLE bookings ADD COLUMN discount_code VARCHAR DEFAULT '';"),
    ]:
        if _col not in existing_booking_columns:
            conn.execute(text(_ddl))
            conn.commit()

app = FastAPI()

# CORS — required if any browser-based client (e.g. a JS frontend) calls this
# API directly from a different origin. Restrict allow_origins to real domains
# before deploying to production; "*" below is for local development only.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- New JSON API v1 routers (used by the Laravel frontend) ---
app.include_router(auth_router.router)
app.include_router(bikes_router.router)
app.include_router(bookings_router.router)
app.include_router(wallet_router.router)
app.include_router(users_router.router)
app.include_router(favorites_router.router)
app.include_router(offers_router.router)
app.include_router(reviews_router.router)
app.include_router(agreements_router.router)
app.include_router(ai_router.router)

# Mount static and templates
app.mount("/static", StaticFiles(directory="static"), name="static")
templates = Jinja2Templates(directory="templates")

# Helper: Get current authenticated user
def get_current_user(request: Request, db: Session):
    user_id = request.cookies.get("user_id")
    if not user_id:
        return None
    return crud.get_user_by_id(db, int(user_id))


@app.get("/")
async def root():
    return {"status": "online", "message": "Velex API Service operational."}

@app.get("/api/bikes/featured")
async def get_featured_bikes(db: Session = Depends(get_db)):
    bikes = crud.get_available_bikes(db)[:3]
    return {"bikes": bikes}

@app.get("/login")
async def login(
    request: Request,
    success: int = 0,
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)

    if user:
        if user.role == "customer":
            return RedirectResponse("/customer-dashboard", status_code=303)
        elif user.role == "owner":
            return RedirectResponse("/owner-dashboard", status_code=303)
        elif user.role == "admin":
            return RedirectResponse("/admin-dashboard", status_code=303)

    return templates.TemplateResponse(
        "login.html",
        {
            "request": request,
            "success": success
        }
    )

@app.post("/login")
async def login_user(
    request: Request,
    email: str = Form(...),
    password: str = Form(...),
    db: Session = Depends(get_db)
):
    user = crud.login_user(db, email, password)
    if not user:
        return templates.TemplateResponse(
            "login.html",
            {
                "request": request,
                "message": "Invalid Email or Password"
            }
        )

    if user.role == "customer":
        response = RedirectResponse(url="/customer-dashboard", status_code=303)
    elif user.role == "owner":
        response = RedirectResponse(url="/owner-dashboard", status_code=303)
    elif user.role == "admin":
        response = RedirectResponse(url="/admin-dashboard", status_code=303)
    else:
        return templates.TemplateResponse(
            "login.html",
            {
                "request": request,
                "message": "Invalid User Role"
            }
        )

    response.set_cookie(
    key="user_id",
    value=str(user.id),
    httponly=True,
    samesite="lax",
    secure=False
)

    response.set_cookie(
    key="user_role",
    value=user.role,
    httponly=True,
    samesite="lax",
    secure=False
)
    return response

@app.get("/logout")
async def logout():
    response = RedirectResponse(url="/", status_code=303)
    response.delete_cookie("user_id")
    response.delete_cookie("user_role")
    return response

@app.get("/signup")
async def signup(request: Request):
    return templates.TemplateResponse(
        "signup.html",
        {"request": request}
    )

@app.post("/signup")
async def signup_user(
    request: Request,
    full_name: str = Form(...),
    email: str = Form(...),
    phone: str = Form(...),
    cnic: str = Form(...),
    password: str = Form(...),
    confirm_password: str = Form(...),
    role: str = Form(...),
    provider_type: str = Form("Individual"),
    company_name: str = Form(None),
    company_address: str = Form(None),
    company_logo: str = Form(None),
    db: Session = Depends(get_db)
):
    if password != confirm_password:
        return templates.TemplateResponse(
            "signup.html",
            {
                "request": request,
                "message": "Passwords do not match!"
            }
        )

    existing_user = crud.get_user_by_email(db, email)
    if existing_user:
        return templates.TemplateResponse(
            "signup.html",
            {
                "request": request,
                "message": "Email already exists!"
            }
        )

    if role != "owner":
        provider_type = "Individual"
        company_name = None
        company_address = None
        company_logo = None

    user = schemas.UserCreate(
        full_name=full_name,
        email=email,
        phone=phone,
        cnic=cnic,
        password=password,
        role=role,
        provider_type=provider_type,
        company_name=company_name,
        company_address=company_address,
        company_logo=company_logo
    )
    crud.create_user(db, user)
    return RedirectResponse(
        url="/login?success=1",
        status_code=303
    )

@app.get("/forgot-password")
async def forgot(request: Request):
    return templates.TemplateResponse(
        "forgot_password.html",
        {"request": request}
    )

@app.post("/forgot-password")
async def forgot_post(request: Request, email: str = Form(...)):
    # Simply redirect to verification screen
    return RedirectResponse(url="/verify-email", status_code=303)

@app.get("/verify-email")
async def verify(request: Request):
    return templates.TemplateResponse(
        "email_verification.html",
        {"request": request}
    )

@app.post("/verify-email")
async def verify_post(request: Request, otp: str = Form(...)):
    return RedirectResponse(url="/login?success=1", status_code=303)

@app.get("/customer-dashboard")
async def customer_dashboard(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "customer":
        return RedirectResponse("/login", status_code=303)

    # Get stats
    bookings = db.query(models.Booking).filter(models.Booking.customer_id == user.id).all()
    total_rides = sum(1 for b in bookings if b.status == "Approved")
    active_bookings = sum(1 for b in bookings if b.status == "Pending")
    
    available_bikes = crud.get_available_bikes(db)[:5]

    return templates.TemplateResponse(
        "customer_dashboard.html",
        {
            "request": request,
            "user": user,
            "total_rides": total_rides,
            "active_bookings": active_bookings,
            "available_bikes": available_bikes
        }
    )

@app.get("/nearby-bikes")
async def nearby_bikes(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
        
    bikes = crud.get_available_bikes(db)
    
    import random
    bike_list = []
    for b in bikes:
        dist = round(random.uniform(0.5, 4.5), 1)
        bike_list.append({"bike": b, "distance": dist})
        
    return templates.TemplateResponse(
        "nearby_bikes.html",
        {
            "request": request,
            "user": user,
            "bike_list": bike_list
        }
    )

@app.get("/bike-details/{bike_id}")
async def bike_details(bike_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
        
    bike = crud.get_bike_by_id(db, bike_id)
    if bike is None:
        return RedirectResponse("/search-bike", status_code=303)

    return templates.TemplateResponse(
        "bike_details.html",
        {
            "request": request,
            "user": user,
            "bike": bike
        }
    )

@app.get("/search-bike")
async def search_bike(
    request: Request,
    city: str = None,
    type: str = None,
    price_range: str = None,
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)
    query = db.query(models.Bike).filter(models.Bike.status == "available")
    if city:
        query = query.filter(models.Bike.city.ilike(f"%{city}%"))
    if type:
        query = query.filter(models.Bike.bike_type == type)
    if price_range:
        parts = price_range.split("-")
        if len(parts) == 2:
            try:
                min_p, max_p = float(parts[0]), float(parts[1])
                query = query.filter(models.Bike.price_per_hour >= min_p, models.Bike.price_per_hour <= max_p)
            except Exception:
                pass
            
    bikes = query.all()
    return templates.TemplateResponse(
        "search_bike.html",
        {
            "request": request,
            "user": user,
            "bikes": bikes,
            "city": city,
            "type": type,
            "price_range": price_range
        }
    )

@app.get("/book-bike/{bike_id}")
async def book_bike(bike_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
        
    bike = crud.get_bike_by_id(db, bike_id)
    return templates.TemplateResponse(
        "book_bike.html",
        {
            "request": request,
            "user": user,
            "bike": bike
        }
    )

@app.post("/book-bike/{bike_id}")
async def confirm_booking(
    bike_id: int,
    request: Request,
    booking_type: str = Form(...),
    start_date: str = Form(...),
    end_date: str = Form(...),
    start_time: str = Form(...),
    end_time: str = Form(...),
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)

    if not user:
        return RedirectResponse("/login", status_code=303)

    bike = crud.get_bike_by_id(db, bike_id)

    if bike is None:
        raise HTTPException(
            status_code=404,
            detail="Bike not found"
        )

    # ==========================
    # Debug Information
    # ==========================
    print("=" * 60)
    print("Bike ID:", bike_id)
    print("Booking Type:", booking_type)
    print("Start Date:", start_date)
    print("End Date:", end_date)
    print("Start Time:", start_time)
    print("End Time:", end_time)
    print("=" * 60)

    total_amount = 0

    booking_type = booking_type.lower().strip()

    # ==========================
    # Hour Booking
    # ==========================
    if booking_type == "hour":

        start = datetime.strptime(
            f"{start_date} {start_time}",
            "%Y-%m-%d %H:%M"
        )

        end = datetime.strptime(
            f"{end_date} {end_time}",
            "%Y-%m-%d %H:%M"
        )

        hours = (end - start).total_seconds() / 3600

        print("Calculated Hours:", hours)

        if hours <= 0:
            raise HTTPException(
                status_code=400,
                detail="End time must be greater than start time."
            )

        total_amount = round(hours * bike.price_per_hour, 2)

    # ==========================
    # Day Booking
    # ==========================
    elif booking_type == "day":

        start = datetime.strptime(start_date, "%Y-%m-%d")
        end = datetime.strptime(end_date, "%Y-%m-%d")

        days = (end - start).days + 1

        print("Calculated Days:", days)

        if days <= 0:
            raise HTTPException(
                status_code=400,
                detail="End date must be after start date."
            )

        total_amount = days * bike.price_per_day

    # ==========================
    # Month Booking
    # ==========================
    elif booking_type == "month":

        start = datetime.strptime(start_date, "%Y-%m-%d")
        end = datetime.strptime(end_date, "%Y-%m-%d")

        days = (end - start).days + 1

        print("Calculated Days:", days)

        if days <= 0:
            raise HTTPException(
                status_code=400,
                detail="End date must be after start date."
            )

        months = max(1, round(days / 30))

        monthly_price = bike.price_per_day * 25

        total_amount = months * monthly_price

    else:

        raise HTTPException(
            status_code=400,
            detail=f"Invalid booking type received: {booking_type}"
        )

    print("Total Amount:", total_amount)

    booking = schemas.BookingCreate(
        customer_id=user.id,
        bike_id=bike_id,
        booking_type=booking_type,
        start_date=start_date,
        end_date=end_date,
        start_time=start_time,
        end_time=end_time,
        total_amount=total_amount
    )

    crud.create_booking(db, booking)

    print("Booking Created Successfully")

    return RedirectResponse(
        "/my-bookings",
        status_code=303
    )

@app.get("/my-bookings")
async def my_bookings(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)

    bookings = crud.get_customer_bookings(db, user.id)
    return templates.TemplateResponse(
        "my_bookings.html",
        {
            "request": request,
            "user": user,
            "bookings": bookings
        }
    )

@app.get("/ride-history")
async def ride_history(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "customer":
        return RedirectResponse("/login", status_code=303)
        
    bookings = db.query(models.Booking).filter(models.Booking.customer_id == user.id).all()
    bookings_data = []
    for booking in bookings:
        bike = db.query(models.Bike).filter(models.Bike.id == booking.bike_id).first()
        bookings_data.append({
            "booking": booking,
            "bike": bike
        })
        
    return templates.TemplateResponse(
        "ride_history.html",
        {
            "request": request,
            "user": user,
            "bookings": bookings_data
        }
    )

@app.get("/wallet")
async def wallet(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
        
    bookings = db.query(models.Booking).filter(models.Booking.customer_id == user.id).all()
    transactions = []
    for b in bookings:
        transactions.append({
            "id": f"TR{b.id:03d}",
            "date": b.start_date,
            "type": "Bike Booking",
            "amount": f"-Rs.{b.total_amount}",
            "status": "Paid" if b.status == "Approved" else b.status
        })
    transactions.insert(0, {
        "id": "TR000",
        "date": "2026-07-01",
        "type": "Wallet Top-up",
        "amount": "+Rs.5000",
        "status": "Success"
    })
    return templates.TemplateResponse(
        "wallet.html",
        {
            "request": request,
            "user": user,
            "transactions": transactions
        }
    )

@app.post("/wallet/topup")
async def wallet_topup(
    request: Request,
    amount: int = Form(...),
    db: Session = Depends(get_db)
):

    user = get_current_user(request, db)

    if not user:
        return RedirectResponse("/login", status_code=303)

    # Minimum amount
    if amount < 100:

        return templates.TemplateResponse(
            "wallet.html",
            {
                "request": request,
                "user": user,
                "message": "Minimum top-up is Rs.100"
            }
        )

    # Maximum amount
    if amount > 5000:

        return templates.TemplateResponse(
            "wallet.html",
            {
                "request": request,
                "user": user,
                "message": "Maximum top-up is Rs.5000"
            }
        )

    user.wallet_balance += amount
    # Save Transaction History
    transaction = models.WalletTransaction(
    user_id=user.id,
    amount=amount,
    transaction_type="Credit",
    description="Wallet Top-up"
)

    db.add(transaction)

    db.commit()

    return RedirectResponse(
        "/wallet",
        status_code=303
    )

@app.get("/notifications")
async def notifications(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)

    if not user:
        return RedirectResponse("/login", status_code=303)

    bookings = (
        db.query(models.Booking)
        .filter(models.Booking.customer_id == user.id)
        .all()
    )

    notifs = []

    for b in bookings:

        bike = db.query(models.Bike).filter(
            models.Bike.id == b.bike_id
        ).first()

        bike_name = bike.bike_name if bike else "Bike"

        if b.status == "Approved":

            notifs.append({

                "title": "Booking Approved",

                "message": f"Your booking for {bike_name} has been approved. Please review and sign the agreement.",

                "class": "success",

                "time": "Recent",

                "booking_id": b.id

            })

        elif b.status == "Pending":

            notifs.append({

                "title": "Booking Pending",

                "message": f"Your booking request for {bike_name} is currently pending owner review.",

                "class": "warning",

                "time": "Recent",

                "booking_id": b.id

            })

        elif b.status == "Rejected":

            notifs.append({

                "title": "Booking Rejected",

                "message": f"Unfortunately your booking for {bike_name} was rejected by the owner.",

                "class": "danger",

                "time": "Recent",

                "booking_id": b.id

            })

        elif b.status == "Completed":

            notifs.append({

                "title": "Ride Completed",

                "message": f"Your ride for {bike_name} has been completed. Please leave a review.",

                "class": "info",

                "time": "Recent",

                "booking_id": b.id

            })

    if not notifs:

        notifs.append({

            "title": "Welcome to Bike Sharing",

            "message": "Explore available bikes and start booking your rides today!",

            "class": "primary",

            "time": "Just now",

            "booking_id": 0

        })

    return templates.TemplateResponse(

        "notifications.html",

        {

            "request": request,

            "user": user,

            "notifications": notifs

        }

    )

@app.get("/profile")
async def profile(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
    return templates.TemplateResponse(
        "profile.html",
        {"request": request, "user": user}
    )

@app.get("/settings")
async def settings(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
    return templates.TemplateResponse(
        "settings.html",
        {"request": request, "user": user}
    )

@app.post("/settings")
async def save_settings(
    request: Request,
    full_name: str = Form(...),
    email: str = Form(...),
    phone: str = Form(...),
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)
    user.full_name = full_name
    user.email = email
    user.phone = phone
    db.commit()
    return RedirectResponse("/settings", status_code=303)

@app.get("/owner-dashboard")
async def owner_dashboard(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)
        
    bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
    total_bikes = len(bikes)
    available_bikes = sum(1 for b in bikes if b.status == "available")
    
    bike_ids = [b.id for b in bikes]
    bookings = db.query(models.Booking).filter(models.Booking.bike_id.in_(bike_ids)).all() if bike_ids else []
    total_bookings = len(bookings)
    
    total_earnings = sum(b.total_amount for b in bookings if b.status == "Approved")
    
    recent_bookings_data = []
    sorted_bookings = sorted(bookings, key=lambda x: x.id, reverse=True)[:5]
    for booking in sorted_bookings:
        bike = next((b for b in bikes if b.id == booking.bike_id), None)
        customer = db.query(models.User).filter(models.User.id == booking.customer_id).first()
        recent_bookings_data.append({
            "booking": booking,
            "bike": bike,
            "customer": customer
        })
        
    return templates.TemplateResponse(
        "owner_dashboard.html",
        {
            "request": request,
            "user": user,
            "total_bikes": total_bikes,
            "available_bikes": available_bikes,
            "total_bookings": total_bookings,
            "total_earnings": total_earnings,
            "recent_bookings": recent_bookings_data
        }
    )

@app.get("/add-bike")
async def add_bike_get(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)
    return templates.TemplateResponse(
        "add_bike.html",
        {"request": request, "user": user}
    )

@app.post("/add-bike")
async def add_bike(
    request: Request,
    bike_name: str = Form(...),
    brand: str = Form(...),
    model: str = Form(...),
    bike_type: str = Form(...),
    registration_number: str = Form(...),
    color: str = Form(...),
    city: str = Form(...),
    price_per_hour: float = Form(...),
    price_per_day: float = Form(...),
    engine_cc: str = Form(...),
    fuel_type: str = Form(...),
    transmission: str = Form(...),
    description: str = Form(...),
    gps: str = Form("No"),
    helmet: str = Form("No"),
    image: UploadFile = File(...),
    documents: UploadFile = File(...),
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)

    os.makedirs(os.path.join("static", "uploads"), exist_ok=True)

    # Save Bike Image
    image_extension = os.path.splitext(image.filename)[1]
    image_filename = f"{uuid.uuid4()}{image_extension}"
    image_path = os.path.join("static", "uploads", image_filename)
    with open(image_path, "wb") as buffer:
        shutil.copyfileobj(image.file, buffer)

    # Save Bike Document
    document_extension = os.path.splitext(documents.filename)[1]
    document_filename = f"{uuid.uuid4()}{document_extension}"
    document_path = os.path.join("static", "uploads", document_filename)
    with open(document_path, "wb") as buffer:
        shutil.copyfileobj(documents.file, buffer)

    bike = schemas.BikeCreate(
        owner_id=user.id,
        bike_name=bike_name,
        brand=brand,
        model=model,
        bike_type=bike_type,
        registration_number=registration_number,
        color=color,
        city=city,
        price_per_hour=price_per_hour,
        price_per_day=price_per_day,
        engine_cc=engine_cc,
        fuel_type=fuel_type,
        transmission=transmission,
        description=description,
        gps=gps,
        helmet=helmet,
        image=image_filename,
        documents=document_filename
    )
    crud.create_bike(db, bike)
    return RedirectResponse(
        "/owner-dashboard",
        status_code=303
    )

@app.get("/manage-bikes")
async def manage_bikes(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or (user.role != "owner" and user.role != "admin"):
        return RedirectResponse("/login", status_code=303)

    if user.role == "admin":
        bikes = crud.get_all_bikes(db)
    else:
        bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()

    return templates.TemplateResponse(
        "manage_bikes.html",
        {
            "request": request,
            "user": user,
            "bikes": bikes
        }
    )

@app.get("/delete-bike/{bike_id}")
async def delete_bike(bike_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or (user.role != "owner" and user.role != "admin"):
        return RedirectResponse("/login", status_code=303)
        
    crud.delete_bike(db, bike_id)
    if user.role == "admin":
        return RedirectResponse("/bikes", status_code=303)
    return RedirectResponse("/manage-bikes", status_code=303)

@app.get("/earnings")
async def earnings(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)

    bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
    bike_ids = [b.id for b in bikes]
    bookings = db.query(models.Booking).filter(models.Booking.bike_id.in_(bike_ids)).all() if bike_ids else []
    withdraws = db.query(models.WithdrawRequest).filter(models.WithdrawRequest.owner_id == user.id).all()

    total_earnings = 0
    approved_bookings = 0
    for booking in bookings:
        if booking.status == "Approved":
            approved_bookings += 1
            total_earnings += booking.total_amount

    return templates.TemplateResponse(
        "earnings.html",
        {
            "request": request,
            "user": user,
            "bookings": bookings,
            "withdraws": withdraws,
            "total_earnings": total_earnings,
            "total_bookings": len(bookings),
            "approved_bookings": approved_bookings
        }
    )

@app.post("/withdraw")
async def withdraw(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)

    # Calculate current balance
    bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
    bike_ids = [b.id for b in bikes]
    bookings = db.query(models.Booking).filter(models.Booking.bike_id.in_(bike_ids)).all() if bike_ids else []
    total_earned = sum(b.total_amount for b in bookings if b.status == "Approved")
    
    withdraws = db.query(models.WithdrawRequest).filter(models.WithdrawRequest.owner_id == user.id).all()
    total_withdrawn = sum(w.amount for w in withdraws)
    
    balance = total_earned - total_withdrawn
    if balance > 0:
        crud.create_withdraw_request(db, user.id, balance)

    return RedirectResponse(
        "/earnings",
        status_code=303
    )

@app.get("/analytics")
async def analytics(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)
        
    bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
    bike_ids = [b.id for b in bikes]
    bookings = db.query(models.Booking).filter(models.Booking.bike_id.in_(bike_ids)).all() if bike_ids else []
    
    total_bookings = len(bookings)
    total_customers = len(set(b.customer_id for b in bookings))
    
    # Simple logic for most rented bike
    most_rented = "None"
    if bookings:
        counts = {}
        for b in bookings:
            counts[b.bike_id] = counts.get(b.bike_id, 0) + 1
        top_bike_id = max(counts, key=counts.get)
        top_bike = db.query(models.Bike).filter(models.Bike.id == top_bike_id).first()
        if top_bike:
            most_rented = top_bike.bike_name

    return templates.TemplateResponse(
        "analytics.html",
        {
            "request": request,
            "user": user,
            "total_bookings": total_bookings,
            "total_customers": total_customers,
            "most_rented": most_rented
        }
    )

@app.get("/maintenance")
async def maintenance(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "owner":
        return RedirectResponse("/login", status_code=303)
        
    bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
    records = []
    for b in bikes:
        records.append({
            "bike_name": b.bike_name,
            "condition": "Excellent" if b.status == "available" else "Good",
            "last_service": "2026-06-15",
            "next_service": "2026-08-15",
            "mechanic": "Standard Service Center",
            "cost": 1500 + (b.id * 200),
            "status": "Completed"
        })
        
    return templates.TemplateResponse(
        "maintenance.html",
        {
            "request": request,
            "user": user,
            "records": records
        }
    )

@app.get("/admin-dashboard")
async def admin_dashboard(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    total_users = db.query(models.User).count()
    total_bikes = db.query(models.Bike).count()
    total_bookings = db.query(models.Booking).count()
    
    total_earnings = 0
    bookings = db.query(models.Booking).all()
    for booking in bookings:
        if booking.status == "Approved":
            total_earnings += booking.total_amount

    pending_withdraws = db.query(models.WithdrawRequest).filter(models.WithdrawRequest.status == "Pending").count()

    return templates.TemplateResponse(
        "admin_dashboard.html",
        {
            "request": request,
            "user": user,
            "total_users": total_users,
            "total_bikes": total_bikes,
            "total_bookings": total_bookings,
            "total_earnings": total_earnings,
            "pending_withdraws": pending_withdraws
        }
    )

@app.get("/users")
async def users(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    users_list = crud.get_all_users(db)
    return templates.TemplateResponse(
        "users.html",
        {
            "request": request,
            "user": user,
            "users": users_list
        }
    )

@app.get("/delete-user/{user_id}")
async def delete_user(user_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    crud.delete_user(db, user_id)
    return RedirectResponse("/users", status_code=303)

@app.get("/view-user/{user_id}")
async def view_user(user_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    target_user = crud.get_user_by_id(db, user_id)
    return templates.TemplateResponse(
        "view_user.html",
        {
            "request": request,
            "user": user,
            "target_user": target_user
        }
    )    

@app.get("/owners")
async def owners(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    owners_list = crud.get_all_owners(db)
    return templates.TemplateResponse(
        "owners.html",
        {
            "request": request,
            "user": user,
            "owners": owners_list
        }
    )

@app.get("/bikes")
async def bikes(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    bikes_list = crud.get_all_bikes(db)
    return templates.TemplateResponse(
        "bikes.html",
        {
            "request": request,
            "user": user,
            "bikes": bikes_list
        }
    )

@app.get("/payments")
async def payments(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    bookings = crud.get_all_bookings(db)
    total_revenue = 0
    pending_amount = 0

    for booking in bookings:
        if booking.status == "Approved":
            total_revenue += booking.total_amount
        else:
            pending_amount += booking.total_amount

    return templates.TemplateResponse(
        "payments.html",
        {
            "request": request,
            "user": user,
            "bookings": bookings,
            "total_revenue": total_revenue,
            "pending_amount": pending_amount
        }
    )

@app.get("/reports")
async def reports(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)

    users_list = crud.get_all_users(db)
    bikes_list = crud.get_all_bikes(db)
    bookings = crud.get_all_bookings(db)

    total_revenue = 0
    approved = 0
    pending = 0
    rejected = 0

    for booking in bookings:
        if booking.status == "Approved":
            approved += 1
            total_revenue += booking.total_amount
        elif booking.status == "Pending":
            pending += 1
        else:
            rejected += 1

    return templates.TemplateResponse(
        "reports.html",
        {
            "request": request,
            "user": user,
            "users": users_list,
            "bikes": bikes_list,
            "bookings": bookings,
            "total_revenue": total_revenue,
            "approved": approved,
            "pending": pending,
            "rejected": rejected
        }
    )

@app.get("/admin-analytics")
async def admin_analytics(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)
        
    total_revenue = sum(b.total_amount for b in db.query(models.Booking).filter(models.Booking.status == "Approved").all())
    total_bookings = db.query(models.Booking).count()
    active_users = db.query(models.User).count()
    available_bikes = db.query(models.Bike).filter(models.Bike.status == "available").count()

    return templates.TemplateResponse(
        "admin_analytics.html",
        {
            "request": request,
            "user": user,
            "total_revenue": total_revenue,
            "total_bookings": total_bookings,
            "active_users": active_users,
            "available_bikes": available_bikes
        }
    )

@app.get("/ai-monitoring")
async def ai_monitoring(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or user.role != "admin":
        return RedirectResponse("/login", status_code=303)
        
    bikes = db.query(models.Bike).all()
    records = []
    fraud_count = 0
    gps_online = 0
    maint_count = 0
    
    for i, b in enumerate(bikes):
        if b.status == "available":
            status_lbl = "Normal"
            status_class = "success"
            battery = "94%"
            risk = "Low"
            action = "Monitor"
            gps_online += 1
        elif b.status == "booked":
            status_lbl = "Normal"
            status_class = "success"
            battery = "78%"
            risk = "Low"
            action = "Monitor"
            gps_online += 1
        else:
            status_lbl = "Fraud Alert"
            status_class = "danger"
            battery = "12%"
            risk = "High"
            action = "Investigate"
            fraud_count += 1
            
        if i % 3 == 0:
            status_lbl = "Maintenance Due"
            status_class = "warning"
            risk = "Medium"
            action = "Service"
            maint_count += 1
            
        records.append({
            "bike_name": b.bike_name,
            "status_lbl": status_lbl,
            "status_class": status_class,
            "city": b.city,
            "battery": battery,
            "risk": risk,
            "action": action
        })
        
    return templates.TemplateResponse(
        "ai_monitoring.html",
        {
            "request": request,
            "user": user,
            "records": records,
            "fraud_count": fraud_count,
            "gps_online": gps_online,
            "maint_count": maint_count
        }
    )

@app.get("/owner-bookings")
async def owner_bookings(request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)

    if user.role == "admin":
        bookings = crud.get_all_bookings(db)
    else:
        bikes = db.query(models.Bike).filter(models.Bike.owner_id == user.id).all()
        bike_ids = [b.id for b in bikes]
        bookings = db.query(models.Booking).filter(models.Booking.bike_id.in_(bike_ids)).all() if bike_ids else []

    booking_data = []
    for booking in bookings:
        bike = crud.get_bike_by_id(db, booking.bike_id)
        cust = crud.get_user_by_id(db, booking.customer_id)
        booking_data.append({
            "booking": booking,
            "bike": bike,
            "customer": cust
        })

    return templates.TemplateResponse(
        "owner_bookings.html",
        {
            "request": request,
            "user": user,
            "booking_data": booking_data
        }
    )

@app.get("/approve-booking/{booking_id}")
async def approve_booking(booking_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or (user.role != "owner" and user.role != "admin"):
        return RedirectResponse("/login", status_code=303)

    crud.update_booking_status(db, booking_id, "Approved")
    return RedirectResponse("/owner-bookings", status_code=303)

@app.get("/reject-booking/{booking_id}")
async def reject_booking(booking_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user or (user.role != "owner" and user.role != "admin"):
        return RedirectResponse("/login", status_code=303)

    crud.update_booking_status(db, booking_id, "Rejected")
    return RedirectResponse("/owner-bookings", status_code=303)

@app.get("/agreement/{booking_id}")
async def agreement(booking_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)

    booking = db.query(models.Booking).filter(models.Booking.id == booking_id).first()
    bike = db.query(models.Bike).filter(models.Bike.id == booking.bike_id).first()
    customer = db.query(models.User).filter(models.User.id == booking.customer_id).first()
    owner = db.query(models.User).filter(models.User.id == bike.owner_id).first()
    agreement_record = crud.get_agreement(db, booking_id)

    return templates.TemplateResponse(
        "agreement.html",
        {
            "request": request,
            "user": user,
            "booking": booking,
            "bike": bike,
            "customer": customer,
            "owner": owner,
            "agreement": agreement_record
        }
    )

@app.post("/accept-agreement/{booking_id}")
async def accept_agreement(
    booking_id: int,
    request: Request,
    db: Session = Depends(get_db)
):
    user = get_current_user(request, db)

    if not user:
        return RedirectResponse("/login", status_code=303)

    booking = db.query(models.Booking).filter(
        models.Booking.id == booking_id
    ).first()

    if not booking:
        raise HTTPException(status_code=404, detail="Booking not found")

    bike = db.query(models.Bike).filter(
        models.Bike.id == booking.bike_id
    ).first()

    customer = db.query(models.User).filter(
        models.User.id == booking.customer_id
    ).first()

    owner = db.query(models.User).filter(
        models.User.id == bike.owner_id
    ).first()

    # Create agreements folder
    os.makedirs("agreements", exist_ok=True)

    agreement_id = f"AGR-{booking.id:05d}"

    pdf_path = f"agreements/{agreement_id}.pdf"

    # Generate PDF
    generate_agreement(
        output_target=pdf_path,
        agreement_id=agreement_id,
        booking=booking,
        owner=owner,
        customer=customer,
        bike=bike,
        ai_risk=None
    )

    agreement = crud.get_agreement(db, booking_id)

    if agreement:

        agreement.accepted = "Accepted"
        agreement.agreement_file = pdf_path

        db.commit()

    else:

        new_agreement = schemas.AgreementCreate(

            booking_id=booking.id,
            customer_id=customer.id,
            owner_id=owner.id,
            agreement_file=pdf_path,
            accepted="Accepted"

        )

        crud.create_agreement(db, new_agreement)

    return RedirectResponse(
        url=f"/agreement/{booking_id}",
        status_code=303
    )


@app.get("/download-agreement/{booking_id}")
async def download_agreement(booking_id: int, request: Request, db: Session = Depends(get_db)):
    user = get_current_user(request, db)
    if not user:
        return RedirectResponse("/login", status_code=303)

    agreement_record = crud.get_agreement(db, booking_id)
    if not agreement_record:
         raise HTTPException(status_code=404, detail="Agreement PDF not found")
    return FileResponse(
        agreement_record.agreement_file,
        filename=f"Agreement_{booking_id}.pdf"
    )


@app.post("/ai/recommend-bike")

async def ai_recommend(

        request: RecommendationRequest,

        db: Session = Depends(get_db)

):

    bikes = db.query(models.Bike).all()

    result = recommend_bikes(

        bikes,

        request.dict()

    )

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

    "reasons": item["reasons"]

})



    return {

        "recommendations": response

    }


@app.post("/ai/agreement-analysis")
async def agreement_analysis(data: dict):

    result = analyze_agreement(
        data["agreement"]
    )

    return {
        "analysis": result
    }


@app.post("/ai/review-analysis")
async def review_analysis(data: ReviewRequest):

    result=analyze_review(data.review)

    return {
        "analysis":result
    }

@app.get("/ai/price-prediction/{bike_id}")

async def ai_price_prediction(

    bike_id: int,

    db: Session = Depends(get_db)

):

    bike = db.query(models.Bike).filter(

        models.Bike.id == bike_id

    ).first()

    if not bike:

        raise HTTPException(

            status_code=404,

            detail="Bike not found"

        )

    result = predict_price(bike)

    return result

@app.post("/ai/fraud-detection")
async def fraud_detection(
    data: schemas.FraudRequest,
    db: Session = Depends(get_db)
):

    customer = db.query(models.User).filter(
        models.User.id == data.customer_id
    ).first()

    bike = db.query(models.Bike).filter(
        models.Bike.id == data.bike_id
    ).first()

    booking_count = db.query(models.Booking).filter(
        models.Booking.customer_id == data.customer_id
    ).count()

    result = detect_fraud(
        customer,
        bike,
        data.booking_amount,
        booking_count
    )

    return {
        "analysis": result
    }



@app.post("/ai/maintenance-prediction")

async def maintenance_prediction(

    data: schemas.MaintenanceRequest,

    db: Session = Depends(get_db)

):

    bike = db.query(models.Bike).filter(

        models.Bike.id == data.bike_id

    ).first()

    if not bike:

        raise HTTPException(

            status_code=404,

            detail="Bike not found"

        )

    booking_count = db.query(models.Booking).filter(

        models.Booking.bike_id == bike.id

    ).count()

    reviews = db.query(models.Review).filter(

        models.Review.bike_id == bike.id

    ).all()

    average_rating = None

    if reviews:

        average_rating = sum(

            r.rating for r in reviews

        ) / len(reviews)

    result = predict_maintenance(

        bike,

        booking_count,

        average_rating

    )

    return {

        "analysis": result

    }



@app.post("/ai/price-prediction")
async def ai_price_prediction(request: PricePredictionRequest):

    result = predict_price(request)

    return {
        "prediction": result
    }


@app.post("/ai/demand-forecast")
async def ai_demand_forecast(request: DemandForecastRequest):

    result = predict_demand(request.dict())

    return {

        "forecast": result

    }


@app.post("/ai/chat")
async def ai_chat(
    request: ChatRequest,
    db: Session = Depends(get_db)
):

    result = chat(
        request.message,
        db
    )

    return result