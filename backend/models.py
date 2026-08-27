from sqlalchemy import Column, Integer, String, Float, ForeignKey, DateTime, Text
from sqlalchemy.orm import relationship
from database import Base
from datetime import datetime, UTC


class User(Base):
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)

    full_name = Column(String)

    email = Column(String, unique=True)

    phone = Column(String)

    cnic = Column(String)

    password = Column(String)

    role = Column(String)

    provider_type = Column(String, default="Individual")

    company_name = Column(String, nullable=True)

    company_address = Column(String, nullable=True)

    company_logo = Column(String, nullable=True)

    wallet_balance = Column(Float, default=2500.0)

    reward_points = Column(Integer, default=120)

    account_mode = Column(String, default="rider")

    location = Column(String, default="Karachi")

    

class Bike(Base):
    __tablename__ = "bikes"

    id = Column(Integer, primary_key=True, index=True)

    owner_id = Column(Integer, ForeignKey("users.id"))

    bike_name = Column(String)

    brand = Column(String)

    model = Column(String)

    bike_type = Column(String)

    registration_number = Column(String)

    color = Column(String)

    city = Column(String)

    price_per_hour = Column(Float)

    price_per_day = Column(Float)

    price_per_month = Column(Float, default=0.0)

    engine_cc = Column(String)

    fuel_type = Column(String)

    transmission = Column(String)

    description = Column(String)

    gps = Column(String)

    helmet = Column(String)

    image = Column(String)

    documents = Column(String)

    status = Column(String, default="available")



    
class Booking(Base):
    __tablename__ = "bookings"

    id = Column(Integer, primary_key=True, index=True)

    customer_id = Column(Integer, ForeignKey("users.id"))

    bike_id = Column(Integer, ForeignKey("bikes.id"))

    booking_type = Column(String)      # Hourly / Daily

    start_date = Column(String)

    end_date = Column(String)

    start_time = Column(String)

    end_time = Column(String)

    total_amount = Column(Float)

    # Price breakdown (auditable trail of the dynamic pricing + discount calc)
    base_amount = Column(Float, default=0.0)
    discount_amount = Column(Float, default=0.0)
    time_multiplier = Column(Float, default=1.0)
    discount_code = Column(String, default="")

    status = Column(String, default="Pending")
    

class Review(Base):
    __tablename__ = "reviews"

    id = Column(Integer, primary_key=True, index=True)

    booking_id = Column(Integer, ForeignKey("bookings.id"))

    bike_id = Column(Integer, ForeignKey("bikes.id"))

    customer_id = Column(Integer, ForeignKey("users.id"))

    owner_id = Column(Integer, ForeignKey("users.id"))

    rating = Column(Integer)

    review = Column(Text)

    sentiment = Column(String)

    ai_score = Column(Float)

    created_at = Column(DateTime, default=datetime.utcnow)


class WithdrawRequest(Base):
    __tablename__ = "withdraw_requests"

    id = Column(Integer, primary_key=True, index=True)

    owner_id = Column(Integer, ForeignKey("users.id"))

    amount = Column(Float)

    status = Column(String, default="Pending")    



class Agreement(Base):
    __tablename__ = "agreements"

    id = Column(Integer, primary_key=True, index=True)

    booking_id = Column(Integer, ForeignKey("bookings.id"))

    customer_id = Column(Integer, ForeignKey("users.id"))

    owner_id = Column(Integer, ForeignKey("users.id"))

    agreement_file = Column(String)

    accepted = Column(String, default="Pending")


class WalletTransaction(Base):

    __tablename__ = "wallet_transactions"

    id = Column(Integer, primary_key=True)

    user_id = Column(Integer)

    amount = Column(Float)

    transaction_type = Column(String)

    description = Column(String)

    created_at = Column(DateTime, default=datetime.utcnow)



