from datetime import datetime

from pydantic import BaseModel


class UserCreate(BaseModel):
    full_name: str
    email: str
    phone: str
    cnic: str
    password: str
    role: str

    provider_type: str = "Individual"

    company_name: str | None = None

    company_address: str | None = None

    company_logo: str | None = None

    location: str = "Karachi"


class BikeCreate(BaseModel):
    owner_id: int | None = None

    bike_name: str
    brand: str
    model: str
    bike_type: str
    registration_number: str
    color: str
    city: str

    price_per_hour: float
    price_per_day: float
    price_per_month: float = 0.0

    engine_cc: str
    fuel_type: str
    transmission: str

    description: str

    gps: str
    helmet: str

    image: str
    documents: str


class BookingCreate(BaseModel):

    customer_id: int

    bike_id: int

    booking_type: str

    start_date: str

    end_date: str

    start_time: str

    end_time: str

    total_amount: float

    # Price breakdown (auditable trail of the dynamic pricing + discount calc)
    base_amount: float = 0.0
    discount_amount: float = 0.0
    time_multiplier: float = 1.0
    discount_code: str = ""


class WithdrawCreate(BaseModel):

    owner_id: int

    amount: float


class ReviewCreate(BaseModel):

    booking_id: int

    bike_id: int

    customer_id: int | None = None

    rating: int

    review: str


class AgreementCreate(BaseModel):

    booking_id: int

    customer_id: int

    owner_id: int

    agreement_file: str

    accepted: str = "Pending"


class AgreementOut(BaseModel):
    id: int
    booking_id: int
    customer_id: int
    owner_id: int
    agreement_file: str | None = None
    accepted: str = "Pending"

    model_config = {"from_attributes": True}


from pydantic import BaseModel


class ReviewRequest(BaseModel):
    review: str



class FraudRequest(BaseModel):

    customer_id: int

    bike_id: int

    booking_amount: float




class MaintenanceRequest(BaseModel):

    bike_id: int



class PricePredictionRequest(BaseModel):
    brand: str
    engine_cc: int
    bike_type: str
    gps: str
    helmet: str
    city: str


from pydantic import BaseModel


class DemandForecastRequest(BaseModel):

    city: str

    weather: str

    day: str

    month: str


class ChatRequest(BaseModel):

    message: str


class RecommendationRequest(BaseModel):

    budget: float

    city: str

    category: str

    ride_type: str

# --- API v1 response / request schemas (for Laravel integration) ---

class LoginRequest(BaseModel):
    email: str
    password: str


class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: "UserOut"


class UserOut(BaseModel):
    id: int
    full_name: str
    email: str
    phone: str | None = None
    cnic: str | None = None
    role: str
    account_mode: str = "rider"
    wallet_balance: float = 0.0
    reward_points: int = 0
    location: str = "Karachi"

    model_config = {"from_attributes": True}


class BikeOut(BaseModel):
    id: int
    owner_id: int | None = None
    bike_name: str
    brand: str
    model: str
    bike_type: str
    city: str
    color: str | None = None
    price_per_hour: float
    price_per_day: float
    price_per_month: float = 0.0
    engine_cc: str | None = None
    fuel_type: str | None = None
    transmission: str | None = None
    description: str | None = None
    image: str | None = None
    status: str

    model_config = {"from_attributes": True}


class BookingRequest(BaseModel):
    booking_type: str          # "Hourly", "Daily" or "Monthly"
    start_date: str
    end_date: str
    start_time: str
    end_time: str
    # Optional claimed offer code applied as a discount at checkout.
    offer_code: str | None = None
    # total_amount is intentionally NOT accepted from the client —
    # it is always calculated server-side from the bike's real rate.


class BookingPriceBreakdown(BaseModel):
    """Full price breakdown returned for both bookings and live quotes."""
    quantity: int
    unit: str
    base_amount: float
    time_multiplier: float
    time_label: str
    subtotal: float
    long_term_discount: float = 0.0
    long_term_label: str = "Standard rate"
    discount_amount: float
    discount_code: str
    discount_description: str
    total_amount: float
    offer_price_per_unit: float


class BookingOut(BaseModel):
    id: int
    customer_id: int
    bike_id: int
    booking_type: str
    start_date: str
    end_date: str
    start_time: str
    end_time: str
    total_amount: float
    status: str
    bike_name: str | None = None
    bike_model: str | None = None
    city: str | None = None
    registration_number: str | None = None

    # Price breakdown
    base_amount: float = 0.0
    discount_amount: float = 0.0
    time_multiplier: float = 1.0
    discount_code: str = ""

    model_config = {"from_attributes": True}


class WalletTransactionOut(BaseModel):
    id: int
    user_id: int
    amount: float
    transaction_type: str
    description: str
    created_at: datetime | None = None

    model_config = {"from_attributes": True}


class ExtendBookingRequest(BaseModel):
    extra_hours: int = 1


class WalletTopupRequest(BaseModel):
    amount: float


class ProfileUpdateRequest(BaseModel):
    full_name: str | None = None
    phone: str | None = None
    cnic: str | None = None
    location: str | None = None


class PasswordUpdateRequest(BaseModel):
    current_password: str
    new_password: str
