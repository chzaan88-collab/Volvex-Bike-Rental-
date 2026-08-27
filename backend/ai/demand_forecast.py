class DemandForecastEngine:

    def predict(self, data):

        score = 0
        reasons = []

        city = data["city"].lower()
        weather = data["weather"].lower()
        day = data["day"].lower()
        month = data["month"].lower()

        # City
        if city == "karachi":
            score += 30
            reasons.append("Busy city")

        elif city == "lahore":
            score += 25
            reasons.append("High demand city")

        elif city == "islamabad":
            score += 20
            reasons.append("Capital city")

        else:
            score += 10
            reasons.append("Normal demand city")

        # Weather
        if weather == "sunny":
            score += 30
            reasons.append("Sunny weather")

        elif weather == "cloudy":
            score += 15
            reasons.append("Cloudy weather")

        elif weather == "rainy":
            score -= 20
            reasons.append("Rain reduces demand")

        # Day
        if day in ["saturday", "sunday"]:
            score += 40
            reasons.append("Weekend")

        elif day == "friday":
            score += 20
            reasons.append("Friday demand")

        else:
            score += 10
            reasons.append("Weekday")

        # Month
        if month.lower() in ["june", "july", "august"]:
            score += 20
            reasons.append("Summer season")

        elif month.lower() in ["december", "january"]:
            score += 10
            reasons.append("Winter season")

        # Final Result
        if score >= 80:

            demand = "High"

            multiplier = 1.30

            bookings = 45

        elif score >= 50:

            demand = "Medium"

            multiplier = 1.10

            bookings = 28

        else:

            demand = "Low"

            multiplier = 0.90

            bookings = 12

        return {

            "predicted_bookings": bookings,

            "demand": demand,

            "confidence": 96,

            "dynamic_price_multiplier": multiplier,

            "reasons": reasons

        }


engine = DemandForecastEngine()