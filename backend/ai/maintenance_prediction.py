class MaintenancePredictionEngine:

    def predict(
        self,
        bike,
        booking_count,
        average_rating
    ):

        health_score = 100

        issues = []

        estimated_cost = 500

        # Booking Count

        if booking_count > 30:
            health_score -= 30
            issues.append("Bike has been rented many times")
            estimated_cost += 2000

        elif booking_count > 15:
            health_score -= 20
            issues.append("Moderate bike usage")
            estimated_cost += 1000

        # Rating

        if average_rating is not None:

            if average_rating < 3:
                health_score -= 30
                issues.append("Poor customer reviews")
                estimated_cost += 1500

            elif average_rating < 4:
                health_score -= 15
                issues.append("Average customer reviews")
                estimated_cost += 700

        # Engine

        try:
            cc = int(bike.engine_cc)
        except:
            cc = 70

        if cc >= 150:
            health_score -= 10
            issues.append("High power engine requires inspection")

        # Final Result

        if health_score >= 80:

            urgency = "Low"

            maintenance_required = False

            next_service = 60

        elif health_score >= 60:

            urgency = "Medium"

            maintenance_required = True

            next_service = 30

        else:

            urgency = "High"

            maintenance_required = True

            next_service = 7

        if not issues:

            issues.append("Bike is in excellent condition")

        return {

            "maintenance_required": maintenance_required,

            "urgency": urgency,

            "health_score": health_score,

            "estimated_cost": estimated_cost,

            "next_service_days": next_service,

            "issues": issues,

            "recommendation":
                "Inspect engine oil, brakes, tyres and chain."

        }


engine = MaintenancePredictionEngine()