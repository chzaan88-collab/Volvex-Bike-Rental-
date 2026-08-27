def detect_intent(message):

    msg = message.lower()

    if any(word in msg for word in [
        "recommend",
        "bike",
        "rent",
        "book",
        "ride",
        "بائیک",
        "bike chahiye",
        "bike chaiye",
        "sasti bike"
    ]):
        return "recommendation"

    elif any(word in msg for word in [
        "agreement",
        "contract",
        "policy"
    ]):
        return "agreement"

    elif any(word in msg for word in [
        "maintenance",
        "service",
        "oil",
        "engine"
    ]):
        return "maintenance"

    elif any(word in msg for word in [
        "price",
        "rent price",
        "hour",
        "day"
    ]):
        return "price"

    return "general"