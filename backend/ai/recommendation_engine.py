from typing import List


class BikeRecommendationEngine:

    def calculate_score(self, bike, request):

        score = 0
        reasons = []

        # -------------------------
        # Budget Match (40)
        # -------------------------
        if bike.price_per_day <= request["budget"]:
            score += 40
            reasons.append("Within your budget")

        # -------------------------
        # Bike Type Match (20)
        # -------------------------
        if (
            bike.bike_type
            and bike.bike_type.lower()
            == request["category"].lower()
        ):
            score += 20
            reasons.append("Preferred bike type")

        # -------------------------
        # City Match (15)
        # -------------------------
        if (
            bike.city
            and bike.city.lower()
            == request["city"].lower()
        ):
            score += 15
            reasons.append("Available in your city")

        # -------------------------
        # Availability (15)
        # -------------------------
        if (
            bike.status
            and bike.status.lower() == "available"
        ):
            score += 15
            reasons.append("Available now")

        # -------------------------
        # Temporary Rating Score (10)
        # Replace later with real reviews
        # -------------------------
        rating = 4.5

        score += int(rating * 2)

        reasons.append(f"Rating {rating}")

        # -------------------------
        # Print Score in Console
        # -------------------------
        print("\n==============================")
        print("Bike :", bike.bike_name)
        print("Brand:", bike.brand)

        print(
            "Budget Score:",
            40 if bike.price_per_day <= request["budget"] else 0
        )

        print(
            "Bike Type Score:",
            20 if bike.bike_type and bike.bike_type.lower() == request["category"].lower() else 0
        )

        print(
            "City Score:",
            15 if bike.city and bike.city.lower() == request["city"].lower() else 0
        )

        print(
            "Availability Score:",
            15 if bike.status and bike.status.lower() == "available" else 0
        )

        print("Rating Score:", int(rating * 2))

        print("Total Score:", score)
        print("==============================\n")

        return score, reasons

    def recommend(self, bikes: List, request):

        recommendations = []

        for bike in bikes:

            score, reasons = self.calculate_score(
                bike,
                request
            )

            recommendations.append(
                {
                    "bike": bike,
                    "score": score,
                    "reasons": reasons,
                }
            )

        recommendations.sort(
            key=lambda x: x["score"],
            reverse=True
        )

        return recommendations[:5]