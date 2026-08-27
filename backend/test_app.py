"""
Real API tests for the Velex FastAPI backend.

This uses FastAPI's TestClient so no live server is needed.

Usage (from backend/):
    pip install -r requirements.txt
    python -m pytest test_app.py -v
"""
import os
import sys
import tempfile

# Ensure the database is a temp file so tests don't pollute dev data
TEST_DB = tempfile.NamedTemporaryFile(suffix=".db", delete=False).name
os.environ["DATABASE_URL"] = f"sqlite:///{TEST_DB}"

from fastapi.testclient import TestClient  # noqa: E402

import database  # noqa: E402

# Rebuild the engine against the temp DB
database.DATABASE_URL = f"sqlite:///{TEST_DB}"
database.engine = __import__("sqlalchemy").create_engine(
    database.DATABASE_URL,
    connect_args={"check_same_thread": False},
)
database.SessionLocal = __import__("sqlalchemy").orm.sessionmaker(
    autocommit=False, autoflush=False, bind=database.engine
)

import models  # noqa: E402

models.Base.metadata.create_all(bind=database.engine)

from main import app  # noqa: E402

client = TestClient(app)


def _register(email: str, role: str = "customer", **kw):
    payload = {
        "full_name": kw.get("full_name", "Test User"),
        "email": email,
        "phone": kw.get("phone", "03001234567"),
        "cnic": kw.get("cnic", "35202-1234567-1"),
        "password": "password123",
        "role": role,
    }
    return client.post("/api/v1/auth/register", json=payload)


def _login(email: str, password: str = "password123"):
    return client.post("/api/v1/auth/login", json={"email": email, "password": password})


def _auth_headers(token: str):
    return {"Authorization": f"Bearer {token}"}


# --- Auth Tests ---


def test_register_user():
    r = _register("u1@test.com", "customer")
    assert r.status_code == 201, r.text
    data = r.json()
    assert data["access_token"]
    assert data["user"]["email"] == "u1@test.com"
    assert data["user"]["wallet_balance"] >= 0


def test_register_duplicate_email():
    _register("dup@test.com")
    r = _register("dup@test.com")
    assert r.status_code == 409


def test_login_success():
    _register("login@test.com")
    r = _login("login@test.com")
    assert r.status_code == 200
    assert r.json()["access_token"]


def test_login_bad_password():
    _register("badpass@test.com")
    r = _login("badpass@test.com", "wrongpassword")
    assert r.status_code == 401


def test_me():
    _register("me@test.com")
    r = _login("me@test.com")
    token = r.json()["access_token"]
    r = client.get("/api/v1/auth/me", headers=_auth_headers(token))
    assert r.status_code == 200
    assert r.json()["email"] == "me@test.com"


def test_me_unauthorized():
    r = client.get("/api/v1/auth/me")
    assert r.status_code == 401


# --- Bike Tests ---


def test_list_bikes_empty():
    r = client.get("/api/v1/bikes")
    assert r.status_code == 200
    assert isinstance(r.json(), list)


def test_create_bike_as_customer_forbidden():
    _register("cust@test.com", "customer")
    token = _login("cust@test.com").json()["access_token"]
    payload = {
        "bike_name": "Honda CD70",
        "brand": "Honda",
        "model": "CD70",
        "bike_type": "Standard",
        "registration_number": "ABC-123",
        "color": "Red",
        "city": "Karachi",
        "price_per_hour": 5.0,
        "price_per_day": 18.5,
        "engine_cc": "72cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike.jpg",
        "documents": "",
    }
    r = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(token))
    assert r.status_code == 403


def test_create_bike_as_owner():
    _register("owner1@test.com", "owner")
    token = _login("owner1@test.com").json()["access_token"]
    payload = {
        "bike_name": "Honda CD70",
        "brand": "Honda",
        "model": "CD70",
        "bike_type": "Standard",
        "registration_number": "ABC-123",
        "color": "Red",
        "city": "Karachi",
        "price_per_hour": 5.0,
        "price_per_day": 18.5,
        "engine_cc": "72cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike.jpg",
        "documents": "",
    }
    r = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(token))
    assert r.status_code == 201, r.text
    data = r.json()
    assert data["bike_name"] == "Honda CD70"
    assert data["id"] is not None


def test_get_bike_by_id():
    _register("owner2@test.com", "owner")
    token = _login("owner2@test.com").json()["access_token"]
    payload = {
        "bike_name": "Yamaha YBR",
        "brand": "Yamaha",
        "model": "YBR125",
        "bike_type": "Sport",
        "registration_number": "XYZ-456",
        "color": "Blue",
        "city": "Lahore",
        "price_per_hour": 8.0,
        "price_per_day": 25.0,
        "engine_cc": "125cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike2.jpg",
        "documents": "",
    }
    created = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(token)).json()
    r = client.get(f"/api/v1/bikes/{created['id']}")
    assert r.status_code == 200
    assert r.json()["bike_name"] == "Yamaha YBR"


def test_get_bike_not_found():
    r = client.get("/api/v1/bikes/99999")
    assert r.status_code == 404


# --- Booking Tests ---


def test_create_booking_flow():
    _register("owner3@test.com", "owner")
    owner_token = _login("owner3@test.com").json()["access_token"]
    payload = {
        "bike_name": "Suzuki GS150",
        "brand": "Suzuki",
        "model": "GS150",
        "bike_type": "Touring",
        "registration_number": "GS-789",
        "color": "Black",
        "city": "Islamabad",
        "price_per_hour": 10.0,
        "price_per_day": 32.0,
        "engine_cc": "150cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike3.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    _register("rider1@test.com", "customer")
    rider_token = _login("rider1@test.com").json()["access_token"]
    booking_payload = {
        "booking_type": "Hourly",
        "start_date": "2026-08-20",
        "end_date": "2026-08-20",
        "start_time": "09:00",
        "end_time": "11:00",
    }
    r = client.post(
        f"/api/v1/bookings/{bike['id']}",
        json=booking_payload,
        headers=_auth_headers(rider_token),
    )
    assert r.status_code == 201, r.text
    data = r.json()
    # 2 hours * 10/hr = 20 base, then the city-aware demand multiplier
    # applies. The bike is in Islamabad and the ride starts at 09:00,
    # which falls in Islamabad's "Late Morning" window (1.05x).
    assert data["total_amount"] == 21.0  # 20 * 1.05 (Islamabad 09:00)


def test_create_booking_invalid_dates():
    _register("owner4@test.com", "owner")
    owner_token = _login("owner4@test.com").json()["access_token"]
    payload = {
        "bike_name": "Honda Pridor",
        "brand": "Honda",
        "model": "Pridor",
        "bike_type": "Standard",
        "registration_number": "PR-111",
        "color": "White",
        "city": "Karachi",
        "price_per_hour": 7.0,
        "price_per_day": 22.0,
        "engine_cc": "100cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike4.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    _register("rider2@test.com", "customer")
    rider_token = _login("rider2@test.com").json()["access_token"]
    booking_payload = {
        "booking_type": "Hourly",
        "start_date": "2026-08-20",
        "end_date": "2026-08-20",
        "start_time": "11:00",
        "end_time": "09:00",
    }
    r = client.post(
        f"/api/v1/bookings/{bike['id']}",
        json=booking_payload,
        headers=_auth_headers(rider_token),
    )
    assert r.status_code == 422


# --- Wallet Tests ---


def test_wallet_balance():
    _register("wallet@test.com", "customer")
    token = _login("wallet@test.com").json()["access_token"]
    r = client.get("/api/v1/wallet/balance", headers=_auth_headers(token))
    assert r.status_code == 200
    assert r.json()["wallet_balance"] >= 0


def test_wallet_topup():
    _register("topup@test.com", "customer")
    token = _login("topup@test.com").json()["access_token"]
    r = client.post("/api/v1/wallet/topup", json={"amount": 500}, headers=_auth_headers(token))
    assert r.status_code == 200
    assert r.json()["wallet_balance"] >= 500


def test_wallet_topup_negative():
    _register("neg@test.com", "customer")
    token = _login("neg@test.com").json()["access_token"]
    r = client.post("/api/v1/wallet/topup", json={"amount": -100}, headers=_auth_headers(token))
    assert r.status_code == 422


# --- Favorites Tests (new) ---


def test_favorites_add_remove():
    _register("owner5@test.com", "owner")
    owner_token = _login("owner5@test.com").json()["access_token"]
    payload = {
        "bike_name": "Cafe Racer",
        "brand": "Royal Enfield",
        "model": "Classic 350",
        "bike_type": "Retro",
        "registration_number": "CR-555",
        "color": "Green",
        "city": "Karachi",
        "price_per_hour": 12.0,
        "price_per_day": 40.0,
        "engine_cc": "350cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike5.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    _register("fav@test.com", "customer")
    token = _login("fav@test.com").json()["access_token"]

    r = client.post(f"/api/v1/favorites/{bike['id']}", headers=_auth_headers(token))
    assert r.status_code == 200

    r = client.get("/api/v1/favorites", headers=_auth_headers(token))
    assert r.status_code == 200
    assert any(b["id"] == bike["id"] for b in r.json())

    r = client.delete(f"/api/v1/favorites/{bike['id']}", headers=_auth_headers(token))
    assert r.status_code == 200

    r = client.get("/api/v1/favorites", headers=_auth_headers(token))
    assert all(b["id"] != bike["id"] for b in r.json())


# --- Offers Tests (new) ---


def test_offers_list_and_claim():
    _register("offer@test.com", "customer")
    token = _login("offer@test.com").json()["access_token"]

    r = client.get("/api/v1/offers", headers=_auth_headers(token))
    assert r.status_code == 200
    assert isinstance(r.json(), list)
    assert len(r.json()) >= 2  # WEEKEND20 and WELCOME10 seeded

    r = client.post("/api/v1/offers/claim", json={"code": "WEEKEND20"}, headers=_auth_headers(token))
    assert r.status_code == 200
    assert r.json()["offer"]["claimed"] is True


def test_offers_duplicate_claim():
    _register("offer2@test.com", "customer")
    token = _login("offer2@test.com").json()["access_token"]

    client.post("/api/v1/offers/claim", json={"code": "WELCOME10"}, headers=_auth_headers(token))
    r = client.post("/api/v1/offers/claim", json={"code": "WELCOME10"}, headers=_auth_headers(token))
    assert r.status_code == 409


# --- Reviews Tests (new) ---


def test_create_review_requires_completed_booking():
    # Create owner + bike
    _register("owner6@test.com", "owner")
    owner_token = _login("owner6@test.com").json()["access_token"]
    payload = {
        "bike_name": "KTM Duke",
        "brand": "KTM",
        "model": "Duke 200",
        "bike_type": "Sport",
        "registration_number": "KT-999",
        "color": "Orange",
        "city": "Lahore",
        "price_per_hour": 15.0,
        "price_per_day": 50.0,
        "engine_cc": "200cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike6.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    _register("review@test.com", "customer")
    rider_token = _login("review@test.com").json()["access_token"]

    # Create a booking
    booking_payload = {
        "booking_type": "Hourly",
        "start_date": "2026-08-20",
        "end_date": "2026-08-20",
        "start_time": "09:00",
        "end_time": "10:00",
    }
    booking = client.post(
        f"/api/v1/bookings/{bike['id']}",
        json=booking_payload,
        headers=_auth_headers(rider_token),
    ).json()

    # Review should fail because booking is not completed
    review_payload = {
        "booking_id": booking["id"],
        "bike_id": bike["id"],
        "rating": 5,
        "review": "Great ride!",
    }
    r = client.post("/api/v1/reviews", json=review_payload, headers=_auth_headers(rider_token))
    assert r.status_code == 422


def test_complete_booking_and_review():
    _register("owner7@test.com", "owner")
    owner_token = _login("owner7@test.com").json()["access_token"]
    payload = {
        "bike_name": "Triumph Speed",
        "brand": "Triumph",
        "model": "Speed 400",
        "bike_type": "Roadster",
        "registration_number": "TR-400",
        "color": "Silver",
        "city": "Islamabad",
        "price_per_hour": 18.0,
        "price_per_day": 60.0,
        "engine_cc": "400cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike7.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    _register("review2@test.com", "customer")
    rider_token = _login("review2@test.com").json()["access_token"]

    booking_payload = {
        "booking_type": "Hourly",
        "start_date": "2026-08-20",
        "end_date": "2026-08-20",
        "start_time": "09:00",
        "end_time": "10:00",
    }
    booking = client.post(
        f"/api/v1/bookings/{bike['id']}",
        json=booking_payload,
        headers=_auth_headers(rider_token),
    ).json()

    # Complete the booking
    r = client.post(
        f"/api/v1/bookings/{booking['id']}/complete",
        headers=_auth_headers(rider_token),
    )
    assert r.status_code == 200
    assert r.json()["status"] == "Completed"

    # Approve first (required for wallet debit path via owner)
    _register("owner7_approver@test.com", "customer")
    # Just use direct db call for simplicity - not needed here since complete can be called by customer

    # Now review should work
    review_payload = {
        "booking_id": booking["id"],
        "bike_id": bike["id"],
        "rating": 5,
        "review": "Absolutely brilliant!",
    }
    r = client.post("/api/v1/reviews", json=review_payload, headers=_auth_headers(rider_token))
    assert r.status_code == 201, r.text

    # Duplicate review should fail
    r = client.post("/api/v1/reviews", json=review_payload, headers=_auth_headers(rider_token))
    assert r.status_code == 409


# --- Root Test ---


def test_root():
    r = client.get("/")
    assert r.status_code == 200
    assert r.json()["status"] == "online"


# --- AI Feature Tests ---


def test_ai_chat():
    r = client.post("/api/v1/ai/chat", json={"message": "What bikes are available?"})
    assert r.status_code == 200
    assert "reply" in r.json()


def test_ai_recommend_bike():
    _register("aiowner@test.com", "owner")
    owner_token = _login("aiowner@test.com").json()["access_token"]
    payload = {
        "bike_name": "AI Honda",
        "brand": "Honda",
        "model": "CD70",
        "bike_type": "Standard",
        "registration_number": "AI-001",
        "color": "Red",
        "city": "Karachi",
        "price_per_hour": 5.0,
        "price_per_day": 18.5,
        "engine_cc": "72cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike.jpg",
        "documents": "",
    }
    client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token))

    r = client.post("/api/v1/ai/recommend-bike", json={
        "budget": 100,
        "city": "Karachi",
        "category": "Standard",
        "ride_type": "daily",
    })
    assert r.status_code == 200
    assert "recommendations" in r.json()


def test_ai_price_prediction():
    r = client.post("/api/v1/ai/price-prediction", json={
        "brand": "Honda",
        "engine_cc": 150,
        "bike_type": "Standard",
        "gps": "Yes",
        "helmet": "Yes",
        "city": "Karachi",
    })
    assert r.status_code == 200
    assert "prediction" in r.json()


def test_ai_demand_forecast():
    r = client.post("/api/v1/ai/demand-forecast", json={
        "city": "Karachi",
        "weather": "sunny",
        "day": "saturday",
        "month": "june",
    })
    assert r.status_code == 200
    assert "forecast" in r.json()


def test_ai_fraud_detection():
    _register("aifraud@test.com", "customer")
    token = _login("aifraud@test.com").json()["access_token"]
    user_id = client.get("/api/v1/auth/me", headers=_auth_headers(token)).json()["id"]

    _register("aifraudowner@test.com", "owner")
    owner_token = _login("aifraudowner@test.com").json()["access_token"]
    payload = {
        "bike_name": "Fraud Test",
        "brand": "Suzuki",
        "model": "GS150",
        "bike_type": "Standard",
        "registration_number": "FR-001",
        "color": "Black",
        "city": "Lahore",
        "price_per_hour": 10.0,
        "price_per_day": 32.0,
        "engine_cc": "150cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    r = client.post("/api/v1/ai/fraud-detection", json={
        "customer_id": user_id,
        "bike_id": bike["id"],
        "booking_amount": 500,
    })
    assert r.status_code == 200
    assert "analysis" in r.json()


def test_ai_maintenance_prediction():
    _register("aimaint@test.com", "owner")
    owner_token = _login("aimaint@test.com").json()["access_token"]
    payload = {
        "bike_name": "Maint Test",
        "brand": "Yamaha",
        "model": "YBR125",
        "bike_type": "Sport",
        "registration_number": "MT-001",
        "color": "Blue",
        "city": "Karachi",
        "price_per_hour": 8.0,
        "price_per_day": 25.0,
        "engine_cc": "125cc",
        "fuel_type": "Petrol",
        "transmission": "Manual",
        "description": "Test",
        "gps": "Yes",
        "helmet": "Yes",
        "image": "https://example.com/bike.jpg",
        "documents": "",
    }
    bike = client.post("/api/v1/bikes", json=payload, headers=_auth_headers(owner_token)).json()

    r = client.post("/api/v1/ai/maintenance-prediction", json={"bike_id": bike["id"]})
    assert r.status_code == 200
    assert "analysis" in r.json()


def test_ai_review_analysis():
    r = client.post("/api/v1/ai/review-analysis", json={"review": "Great bike, very comfortable ride!"})
    assert r.status_code == 200
    assert "analysis" in r.json()


def test_ai_agreement_analysis():
    r = client.post("/api/v1/ai/agreement-analysis", json={"agreement": "This is a test agreement for bike rental."})
    assert r.status_code == 200
    assert "analysis" in r.json()


def test_ai_semantic_search():
    r = client.post("/api/v1/ai/semantic-search", json={"query": "sports bike in Karachi", "top_k": 5})
    assert r.status_code == 200
    assert "results" in r.json()


if __name__ == "__main__":
    # Run a quick smoke test
    print("Running smoke tests...")
    test_register_user()
    print("test_register_user: OK")
    test_login_success()
    print("test_login_success: OK")
    test_list_bikes_empty()
    print("test_list_bikes_empty: OK")
    test_create_bike_as_owner()
    print("test_create_bike_as_owner: OK")
    print("All smoke tests passed!")