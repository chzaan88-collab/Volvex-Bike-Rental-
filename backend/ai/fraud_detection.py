class FraudDetectionEngine:

    def detect(self, customer, bike, booking_amount, booking_count):

        score = 0
        reasons = []

        # Booking Amount
        if booking_amount > 10000:
            score += 30
            reasons.append("Very high booking amount")

        # Bike Price
        if bike.price_per_day and bike.price_per_day > 8000:
            score += 25
            reasons.append("Premium bike")

        # Too many bookings
        if booking_count >= 5:
            score += 25
            reasons.append("Too many bookings")

        # Customer Verification
        if not customer.cnic:
            score += 20
            reasons.append("CNIC missing")

        if score >= 70:
            risk = "High"
            allow = False

        elif score >= 40:
            risk = "Medium"
            allow = True

        else:
            risk = "Low"
            allow = True

        if not reasons:
            reasons.append("No suspicious activity detected")

        return {
            "fraud_score": score,
            "risk": risk,
            "allow_booking": allow,
            "reasons": reasons
        }