from sqlalchemy.orm import Session
import models
import schemas

import bcrypt


def hash_password(password: str) -> str:
    """Hash a password using bcrypt directly (bcrypt 5.x compatible)."""
    if isinstance(password, str):
        password = password.encode("utf-8")
    return bcrypt.hashpw(password, bcrypt.gensalt()).decode("utf-8")


def verify_password(password: str, hashed: str) -> bool:
    """Verify a plain password against a bcrypt hash."""
    if isinstance(password, str):
        password = password.encode("utf-8")
    if isinstance(hashed, str):
        hashed = hashed.encode("utf-8")
    try:
        return bcrypt.checkpw(password, hashed)
    except (ValueError, TypeError):
        return False


def get_user_by_email(db: Session, email: str):
    return db.query(models.User).filter(models.User.email == email).first()


def create_user(db: Session, user: schemas.UserCreate):

    db_user = models.User(
        full_name=user.full_name,
        email=user.email,
        phone=user.phone,
        cnic=user.cnic,
        password=hash_password(user.password),
        role=user.role,

        # New Fields
        provider_type=user.provider_type,
        company_name=user.company_name,
        company_address=user.company_address,
        company_logo=user.company_logo,
        location=user.location
    )

    db.add(db_user)
    db.commit()
    db.refresh(db_user)

    return db_user


def login_user(db: Session, email: str, password: str):

    user = db.query(models.User).filter(
        models.User.email == email
    ).first()

    if not user:
        return None

    if not verify_password(password, user.password):
        return None

    return user


def create_bike(db: Session, bike: schemas.BikeCreate):

    try:

        db_bike = models.Bike(
            owner_id=bike.owner_id,
            bike_name=bike.bike_name,
            brand=bike.brand,
            model=bike.model,
            bike_type=bike.bike_type,
            registration_number=bike.registration_number,
            color=bike.color,
            city=bike.city,
            price_per_hour=bike.price_per_hour,
            price_per_day=bike.price_per_day,
            price_per_month=bike.price_per_month,
            engine_cc=bike.engine_cc,
            fuel_type=bike.fuel_type,
            transmission=bike.transmission,
            description=bike.description,
            gps=bike.gps,
            helmet=bike.helmet,
            image=bike.image,
            documents=bike.documents
        )

        db.add(db_bike)
        db.commit()
        db.refresh(db_bike)

        print("Bike Saved Successfully:", db_bike.id)

        return db_bike

    except Exception as e:
        db.rollback()
        print("ERROR:", e)
        raise



def get_all_bikes(db: Session):

    bikes = db.query(models.Bike).all()

    print("========== BIKES ==========")
    print(bikes)
    print("===========================")

    return bikes

def delete_bike(db: Session, bike_id: int):

    bike = db.query(models.Bike).filter(
        models.Bike.id == bike_id
    ).first()

    if bike:
        db.delete(bike)
        db.commit()


def get_available_bikes(db: Session):

    return db.query(models.Bike).filter(
        models.Bike.status == "available"
    ).all()


def get_bike_by_id(db: Session, bike_id: int):
    return db.query(models.Bike).filter(
        models.Bike.id == bike_id
    ).first()


def create_booking(db: Session, booking: schemas.BookingCreate):

    db_booking = models.Booking(

        customer_id=booking.customer_id,

        bike_id=booking.bike_id,

        booking_type=booking.booking_type,

        start_date=booking.start_date,

        end_date=booking.end_date,

        start_time=booking.start_time,

        end_time=booking.end_time,

        total_amount=booking.total_amount,

        # Price breakdown (auditable trail of dynamic pricing + discount)
        base_amount=booking.base_amount,
        discount_amount=booking.discount_amount,
        time_multiplier=booking.time_multiplier,
        discount_code=booking.discount_code,

    )

    db.add(db_booking)

    db.commit()

    db.refresh(db_booking)

    return db_booking


def get_all_bookings(db: Session):

    return db.query(models.Booking).all()


def update_bike_status(db: Session, bike_id: int, status: str):

    bike = db.query(models.Bike).filter(
        models.Bike.id == bike_id
    ).first()

    if bike:
        bike.status = status
        db.commit()
        db.refresh(bike)

    return bike





def get_customer_bookings(db: Session, customer_id: int):

    return db.query(models.Booking).filter(
        models.Booking.customer_id == customer_id
    ).all()


def update_booking_status(db: Session, booking_id: int, status: str):

    booking = db.query(models.Booking).filter(
        models.Booking.id == booking_id
    ).first()

    if booking:

        booking.status = status

        if status == "Approved":
            bike = db.query(models.Bike).filter(
                models.Bike.id == booking.bike_id
            ).first()

            if bike:
                bike.status = "rented"

            customer = db.query(models.User).filter(
                models.User.id == booking.customer_id
            ).first()

            if customer:
                balance = customer.wallet_balance or 0.0
                if balance < booking.total_amount:
                    raise ValueError("Insufficient wallet balance")
                customer.wallet_balance = balance - booking.total_amount
                create_wallet_transaction(
                    db,
                    customer.id,
                    -booking.total_amount,
                    "debit",
                    f"Booking #{booking.id} approved",
                )

        if status == "Rejected":
            bike = db.query(models.Bike).filter(
                models.Bike.id == booking.bike_id
            ).first()
            if bike and bike.status == "rented":
                bike.status = "available"

        db.commit()
        db.refresh(booking)

    return booking


def enrich_booking(db: Session, booking: models.Booking) -> dict:
    bike = get_bike_by_id(db, booking.bike_id)
    return {
        "id": booking.id,
        "customer_id": booking.customer_id,
        "bike_id": booking.bike_id,
        "booking_type": booking.booking_type,
        "start_date": booking.start_date,
        "end_date": booking.end_date,
        "start_time": booking.start_time,
        "end_time": booking.end_time,
        "total_amount": booking.total_amount,
        "status": booking.status,
        "base_amount": booking.base_amount or 0.0,
        "discount_amount": booking.discount_amount or 0.0,
        "time_multiplier": booking.time_multiplier if booking.time_multiplier is not None else 1.0,
        "discount_code": booking.discount_code or "",
        "bike_name": bike.bike_name if bike else None,
        "bike_model": bike.model if bike else None,
        "city": bike.city if bike else None,
        "registration_number": bike.registration_number if bike else None,
    }


def get_owner_bookings(db: Session, owner_id: int):
    bikes = db.query(models.Bike).filter(models.Bike.owner_id == owner_id).all()
    bike_ids = [b.id for b in bikes]
    if not bike_ids:
        return []
    return db.query(models.Booking).filter(
        models.Booking.bike_id.in_(bike_ids)
    ).all()


def get_owner_bikes(db: Session, owner_id: int):
    return db.query(models.Bike).filter(models.Bike.owner_id == owner_id).all()


def complete_booking(db: Session, booking_id: int):
    booking = db.query(models.Booking).filter(models.Booking.id == booking_id).first()
    if not booking:
        return None

    booking.status = "Completed"
    bike = db.query(models.Bike).filter(models.Bike.id == booking.bike_id).first()
    if bike:
        bike.status = "available"

    db.commit()
    db.refresh(booking)
    return booking


def extend_booking(db: Session, booking_id: int, extra_hours: int):
    from datetime import datetime, timedelta
    from pricing import calculate_price

    booking = db.query(models.Booking).filter(models.Booking.id == booking_id).first()
    if not booking:
        return None

    bike = get_bike_by_id(db, booking.bike_id)
    if not bike:
        return None

    end = datetime.strptime(f"{booking.end_date} {booking.end_time}", "%Y-%m-%d %H:%M")
    new_end = end + timedelta(hours=extra_hours)
    booking.end_date = new_end.strftime("%Y-%m-%d")
    booking.end_time = new_end.strftime("%H:%M")

    # Extension cost is priced with the same demand-aware engine used for
    # bookings (time-of-day multiplier + correct unit rate) instead of
    # always charging the flat hourly rate regardless of booking type.
    ext = calculate_price(
        bike,
        "Hourly",
        end.strftime("%Y-%m-%d"),
        end.strftime("%H:%M"),
        new_end.strftime("%Y-%m-%d"),
        new_end.strftime("%H:%M"),
    )
    booking.total_amount = round((booking.total_amount or 0) + ext["total_amount"], 2)

    db.commit()
    db.refresh(booking)
    return booking


def create_wallet_transaction(db: Session, user_id: int, amount: float, transaction_type: str, description: str):
    tx = models.WalletTransaction(
        user_id=user_id,
        amount=amount,
        transaction_type=transaction_type,
        description=description,
    )
    db.add(tx)
    db.flush()
    return tx


def get_wallet_transactions(db: Session, user_id: int):
    return db.query(models.WalletTransaction).filter(
        models.WalletTransaction.user_id == user_id
    ).order_by(models.WalletTransaction.created_at.desc()).all()


def toggle_account_mode(db: Session, user: models.User) -> models.User:
    current = getattr(user, "account_mode", None) or "rider"
    user.account_mode = "owner" if current == "rider" else "rider"
    db.commit()
    db.refresh(user)
    return user


def get_total_earnings(db: Session):

    bookings = db.query(models.Booking).filter(
        models.Booking.status == "Approved"
    ).all()

    total = 0

    for booking in bookings:
        total += booking.total_amount

    return total



def create_withdraw_request(db: Session, owner_id: int, amount: float):

    request = models.WithdrawRequest(

        owner_id=owner_id,

        amount=amount,

        status="Pending"

    )

    db.add(request)

    db.commit()

    db.refresh(request)

    return request


def get_owner_withdraws(db: Session, owner_id: int):

    return db.query(models.WithdrawRequest).filter(

        models.WithdrawRequest.owner_id == owner_id

    ).all()


def get_all_withdraw_requests(db: Session):

    return db.query(models.WithdrawRequest).all()


def get_all_users(db: Session):

    return db.query(models.User).all()


def delete_user(db: Session, user_id: int):

    user = db.query(models.User).filter(
        models.User.id == user_id
    ).first()

    if user:

        db.delete(user)

        db.commit()


def get_user_by_id(db: Session, user_id: int):

    return db.query(models.User).filter(

        models.User.id == user_id

    ).first()        


def get_all_owners(db: Session):

    return db.query(models.User).filter(
        models.User.role == "owner"
    ).all()


def create_review(db: Session, review: schemas.ReviewCreate):

    db_review = models.Review(

        booking_id=review.booking_id,

        bike_id=review.bike_id,

        customer_id=review.customer_id,

        rating=review.rating,

        review=review.review

    )

    db.add(db_review)

    db.commit()

    db.refresh(db_review)

    return db_review


def get_reviews_by_bike(db: Session, bike_id: int):

    return db.query(models.Review).filter(

        models.Review.bike_id == bike_id

    ).all()



def create_agreement(db, agreement):

    db_agreement = models.Agreement(

        booking_id=agreement.booking_id,

        customer_id=agreement.customer_id,

        owner_id=agreement.owner_id,

        agreement_file=agreement.agreement_file,

        accepted=agreement.accepted

    )

    db.add(db_agreement)

    db.commit()

    db.refresh(db_agreement)

    return db_agreement


def get_agreement(db, booking_id):

    return db.query(models.Agreement).filter(

        models.Agreement.booking_id == booking_id

    ).first()


def accept_agreement(db, agreement_id):

    agreement = db.query(models.Agreement).filter(

        models.Agreement.id == agreement_id

    ).first()

    if agreement:

        agreement.accepted = "Accepted"

        db.commit()

    return agreement