class PricePredictionEngine:

    def _get(self, obj, key, default=None):
        """Safely extract a value from either a dict or an ORM object."""
        if obj is None:
            return default
        if isinstance(obj, dict):
            return obj.get(key, default)
        return getattr(obj, key, default)

    def predict(self, bike):

        score = 0
        reasons = []

        # Engine CC
        cc_raw = self._get(bike, "engine_cc", "70")
        try:
            cc = int(str(cc_raw).replace("cc", "").strip())
        except (ValueError, TypeError):
            cc = 70

        if cc >= 150:
            score += 150
            reasons.append("High engine capacity")
        elif cc >= 100:
            score += 100
            reasons.append("Medium engine capacity")
        else:
            score += 60
            reasons.append("Economy bike")

        # Brand
        brand = (self._get(bike, "brand", "") or "").lower()
        if brand in ["honda", "yamaha"]:
            score += 80
            reasons.append("Premium brand")
        elif brand == "suzuki":
            score += 60
            reasons.append("Popular brand")
        else:
            score += 40
            reasons.append("Standard brand")

        # Bike Type
        bike_type = (self._get(bike, "bike_type", "") or "").lower()
        if bike_type == "sports":
            score += 120
            reasons.append("Sports bike")
        elif bike_type == "cruiser":
            score += 100
            reasons.append("Cruiser bike")
        else:
            score += 70
            reasons.append("Standard bike")

        # GPS
        gps = (self._get(bike, "gps", "no") or "").lower()
        if gps in ["yes", "true", "1", "included"]:
            score += 20
            reasons.append("GPS installed")

        # Helmet
        helmet = (self._get(bike, "helmet", "no") or "").lower()
        if helmet in ["yes", "true", "1", "included"]:
            score += 10
            reasons.append("Helmet included")

        # City
        city = (self._get(bike, "city", "") or "").lower()
        if city == "karachi":
            score += 120
            reasons.append("High demand city")
        elif city == "lahore":
            score += 100
            reasons.append("Busy market")
        else:
            score += 70
            reasons.append("Normal demand")

        return {
            "predicted_price_per_hour": score,
            "predicted_price_per_day": score * 8,
            "confidence": 95,
            "reasons": reasons
        }

engine = PricePredictionEngine()