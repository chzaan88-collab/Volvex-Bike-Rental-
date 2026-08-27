"""Demand-aware pricing engine for bike rentals.

Implements two things on top of a bike's flat rates:

1. Time-of-day dynamic pricing - prices automatically rise during
   high-demand windows (morning & evening commutes, when riders need
   bikes most) and fall during low-demand night hours. This is applied
   deterministically (no AI / API-key required) so bookings are always
   reproducible and auditable.

2. Offer discount application - a claimed offer (percent or fixed amount)
   is applied to the dynamically-priced subtotal.

The function :func:`calculate_price` returns a full breakdown so the
frontend can display why a price changed and the stored booking keeps an
auditable trail of the calculation.
"""
import math
from datetime import datetime


# ---------------------------------------------------------------------------
# Time-of-day demand windows.
# Each entry: (start_hour_inclusive, end_hour_exclusive, multiplier, label)
# Peak windows (commute hours) increase the rate; night hours decrease it.
# ---------------------------------------------------------------------------
DEFAULT_PEAK_WINDOWS = [
    (0, 6, 0.80, "Late Night"),        # lowest demand - discount
    (6, 9, 0.95, "Early Morning"),     # ramping up
    (9, 12, 1.20, "Morning Peak"),     # high demand (commute + errands)
    (12, 17, 1.00, "Midday"),          # moderate flat demand
    (17, 20, 1.30, "Evening Peak"),    # highest demand (commute home)
    (20, 24, 0.85, "Night"),           # winding down
]

# ---------------------------------------------------------------------------
# Location-aware demand profiles.
#
# Different cities have different commute patterns, traffic congestion, and
# ride demand. Each city gets its own set of time-of-day multipliers so the
# base rate can rise more in a city's rush hour and drop more in its dead
# hours. City names are case-insensitive; city names that are not present
# fall back to the default profile.
# ---------------------------------------------------------------------------
CITY_PEAK_PROFILES = {
    # Karachi: heavy traffic, very long commutes, late-evening rush.
    "karachi": [
        (0, 5, 0.70, "Late Night"),        # deep discount - very low demand
        (5, 9, 1.35, "Morning Commute"),   # intense rush hour
        (9, 12, 1.10, "Late Morning"),     # still busy
        (12, 17, 1.05, "Afternoon"),       # moderate
        (17, 22, 1.45, "Evening Rush"),    # heaviest return commute
        (22, 24, 0.90, "Night"),           # winding down
    ],
    # Lahore: similar heavy rush, slightly shorter morning window.
    "lahore": [
        (0, 5, 0.75, "Late Night"),
        (5, 9, 1.30, "Morning Rush"),
        (9, 12, 1.10, "Late Morning"),
        (12, 17, 1.00, "Afternoon"),
        (17, 21, 1.40, "Evening Rush"),
        (21, 24, 0.90, "Night"),
    ],
    # Islamabad: office-heavy demand, shorter commute windows.
    "islamabad": [
        (0, 6, 0.80, "Late Night"),
        (6, 9, 1.20, "Morning Commute"),   # government/office rush
        (9, 12, 1.05, "Late Morning"),
        (12, 17, 0.95, "Afternoon"),       # mild lull
        (17, 20, 1.30, "Evening Commute"),
        (20, 24, 0.85, "Night"),
    ],
    # Rawalpindi: twin-city with Islamabad, similar but busier evenings.
    "rawalpindi": [
        (0, 5, 0.75, "Late Night"),
        (5, 9, 1.25, "Morning Commute"),
        (9, 12, 1.10, "Late Morning"),
        (12, 17, 1.00, "Afternoon"),
        (17, 21, 1.35, "Evening Commute"),
        (21, 24, 0.90, "Night"),
    ],
    # Faisalabad & industrial cities: factory shift-driven demand.
    "faisalabad": [
        (0, 5, 0.75, "Late Night"),
        (5, 9, 1.25, "Factory Shift Start"),
        (9, 12, 1.05, "Late Morning"),
        (12, 17, 1.00, "Afternoon"),
        (17, 21, 1.30, "Factory Shift End"),
        (21, 24, 0.90, "Night"),
    ],
    # Multan / smaller cities: closer to the default profile.
    "multan": [
        (0, 6, 0.80, "Late Night"),
        (6, 9, 1.15, "Morning Commute"),
        (9, 12, 1.00, "Late Morning"),
        (12, 17, 0.95, "Afternoon"),
        (17, 20, 1.20, "Evening Commute"),
        (20, 24, 0.85, "Night"),
    ],
}


def get_city_profile(city: str | None) -> list:
    """Return the demand-window table for a city (case-insensitive).

    Falls back to :data:`DEFAULT_PEAK_WINDOWS` for unknown / empty cities.
    """
    if not city:
        return DEFAULT_PEAK_WINDOWS
    return CITY_PEAK_PROFILES.get(city.strip().lower(), DEFAULT_PEAK_WINDOWS)


def time_of_day_multiplier(hour: int, city: str | None = None) -> float:
    """Return the demand multiplier for an hour in a city (0-23).

    ``city`` is optional; unknown cities use the default national profile.
    """
    windows = get_city_profile(city)
    for start, end, multiplier, _ in windows:
        if start <= hour < end:
            return multiplier
    return 1.0


def time_of_day_label(hour: int, city: str | None = None) -> str:
    """Human-readable label for the demand window of an hour in a city."""
    windows = get_city_profile(city)
    for start, end, _, label in windows:
        if start <= hour < end:
            return label
    return "Standard"


def _parse_datetime(date_str: str, time_str: str) -> datetime:
    return datetime.strptime(f"{date_str} {time_str}", "%Y-%m-%d %H:%M")


def _monthly_rate(bike) -> float:
    """Monthly rate: prefer the explicit price_per_month, fall back to
    price_per_day * 30 (a full 30-day month is more intuitive than 25)."""
    rate = getattr(bike, "price_per_month", None)
    if rate and float(rate) > 0:
        return float(rate)
    daily = getattr(bike, "price_per_day", 0) or 0
    return float(daily) * 30 if float(daily) > 0 else 0.0


# ---------------------------------------------------------------------------
# Long-term rental discount tiers.
# Longer rentals automatically earn a bigger discount. The discount is
# applied on top of the dynamic (time-of-day) subtotal, before any claimed
# offer code. Each entry: (min_quantity, max_quantity, percent, label)
# ---------------------------------------------------------------------------
LONG_TERM_DISCOUNTS = [
    (1, 1, 0.0, "Standard rate"),
    (2, 2, 0.0, "Standard rate"),
    (3, 6, 5.0, "3-6 day/week rental - 5% off"),
    (7, 13, 10.0, "7-13 day rental - 10% off"),
    (14, 29, 15.0, "2-4 week rental - 15% off"),
    (30, 10**9, 20.0, "Monthly subscription - 20% off"),
]


def long_term_discount(qty: int, unit: str) -> tuple[float, str]:
    """Return the automatic long-term discount (percent, label) for a quantity.

    ``unit`` is ``"hr"``, ``"day"`` or ``"month"``. Hourly rentals never
    qualify; daily and monthly rentals earn a tiered discount based on the
    number of days/months booked.
    """
    if unit == "hr":
        return 0.0, "Standard rate"

    # Convert months to an equivalent day count so the same tiers apply.
    days = qty * 30 if unit == "month" else qty

    for min_qty, max_qty, percent, label in LONG_TERM_DISCOUNTS:
        if min_qty <= days <= max_qty:
            return percent, label
    return 0.0, "Standard rate"


def _apply_offer(subtotal: float, offer: dict | None) -> tuple[float, str, str]:
    """Apply a claimed offer to the subtotal.

    Returns ``(discount_amount, discount_code, description)``.
    """
    if not offer:
        return 0.0, "", "No discount applied"

    discount_type = (offer.get("discount_type") or "percent").lower()
    value = float(offer.get("discount_value") or 0)

    if discount_type == "percent":
        discount = round(subtotal * value / 100.0, 2)
    else:  # "fixed" / "amount"
        discount = round(value, 2)

    # Never discount more than the subtotal itself
    discount = min(max(discount, 0.0), subtotal)
    code = offer.get("code", "") or ""
    description = offer.get("title") or (
        f"{code} - {value}{'%' if discount_type == 'percent' else ' Rs'} off"
    )
    return round(discount, 2), code, description


def calculate_price(
    bike,
    booking_type: str,
    start_date: str,
    start_time: str,
    end_date: str,
    end_time: str,
    offer: dict | None = None,
) -> dict:
    """Calculate a booking price with dynamic time-of-day pricing + discounts.

    Parameters
    ----------
    bike:
        ORM ``Bike`` object (or anything with ``price_per_hour`` /
        ``price_per_day`` / ``price_per_month`` attributes).
    booking_type:
        One of ``Hourly``/``hour``, ``Daily``/``day``, ``Monthly``/``month``.
    offer:
        Optional dict describing a claimed offer (with ``code``,
        ``discount_type`` and ``discount_value``).

    Returns a dict with the full breakdown including ``total_amount``.
    """
    start = _parse_datetime(start_date, start_time)
    end = _parse_datetime(end_date, end_time)
    if end <= start:
        raise ValueError("End must be after start.")

    btype = (booking_type or "").lower().strip()
    is_hourly = btype in ("hourly", "hour")
    is_daily = btype in ("daily", "day")
    is_monthly = btype in ("monthly", "month")

    if not (is_hourly or is_daily or is_monthly):
        raise ValueError("booking_type must be Hourly, Daily or Monthly.")

    # The demand multiplier is derived from the booking's start hour;
    # this is the time the rider begins the ride when demand is highest.
    # The multiplier is also city-aware: each city has its own demand
    # profile so rush-hour surges and late-night discounts reflect the
    # location's real commute patterns.
    bike_city = getattr(bike, "city", None)
    multiplier = time_of_day_multiplier(start.hour, bike_city)
    window_label = time_of_day_label(start.hour, bike_city)

    seconds = (end - start).total_seconds()

    if is_hourly:
        hours = max(1, math.ceil(seconds / 3600))
        base = round(hours * float(bike.price_per_hour), 2)
        unit = "hr"
        qty = hours
    elif is_daily:
        days = max(1, math.ceil(seconds / 86400))
        base = round(days * float(bike.price_per_day), 2)
        unit = "day"
        qty = days
    else:  # monthly
        days = max(1, math.ceil(seconds / 86400))
        months = max(1, math.ceil(days / 30))
        base = round(months * _monthly_rate(bike), 2)
        unit = "month"
        qty = months

    # Dynamic price = flat rate * demand multiplier
    subtotal = round(base * multiplier, 2)

    # Automatic long-term rental discount (tiered by days/months booked).
    lt_percent, lt_label = long_term_discount(qty, unit)
    lt_discount = round(subtotal * lt_percent / 100.0, 2) if lt_percent > 0 else 0.0

    # Apply the long-term discount first, then any claimed offer code.
    after_lt = round(subtotal - lt_discount, 2)
    offer_discount, offer_code, offer_description = _apply_offer(after_lt, offer)
    total_discount = round(lt_discount + offer_discount, 2)
    total = round(after_lt - offer_discount, 2)

    # Build a combined discount description for the breakdown.
    if lt_discount > 0 and offer_discount > 0:
        discount_description = f"{lt_label} + {offer_description}"
    elif lt_discount > 0:
        discount_description = lt_label
    else:
        discount_description = offer_description

    return {
        "quantity": qty,
        "unit": unit,
        "base_amount": base,
        "time_multiplier": multiplier,
        "time_label": window_label,
        "subtotal": subtotal,
        "long_term_discount": lt_discount,
        "long_term_label": lt_label,
        "discount_amount": total_discount,
        "discount_code": offer_code,
        "discount_description": discount_description,
        "total_amount": total,
        "offer_price_per_unit": base / qty if qty else 0.0,
    }
