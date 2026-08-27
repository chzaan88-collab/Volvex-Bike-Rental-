def predict_price(data):
    """
    Simple AI Price Predictor
    """

    score = 0

    # Engine CC
    engine = int(data.get("engine_cc", 70))

    if engine >= 1000:
        score += 4000
    elif engine >= 600:
        score += 3000
    elif engine >= 250:
        score += 2000
    elif engine >= 150:
        score += 1200
    else:
        score += 600

    # Bike Type
    bike_type = data.get("bike_type", "").lower()

    if bike_type == "sports":
        score += 800
    elif bike_type == "cruiser":
        score += 600
    elif bike_type == "adventure":
        score += 700
    else:
        score += 300

    # GPS
    if data.get("gps") == "Yes":
        score += 150

    # Helmet
    if data.get("helmet") == "Yes":
        score += 100

    # City
    city = data.get("city", "").lower()

    if city in ["karachi", "lahore", "islamabad"]:
        score += 250

    return {
        "recommended_price_per_day": score,
        "recommended_price_per_hour": round(score / 24, 2),
        "reason": [
            "Engine Size",
            "Bike Type",
            "GPS Availability",
            "Helmet Included",
            "City Demand"
        ]
    }