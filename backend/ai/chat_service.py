from .llm_chat import ask_llm
from .db_chat import get_available_bikes
from .intent_router import detect_intent


def chat(message, db):

    # Detect user intent for smarter routing
    intent = detect_intent(message)

    bikes = get_available_bikes(db)

    bike_info = ""

    if bikes:

        bike_info = "Available Bikes:\n"

        for b in bikes:

            bike_info += f"""
Bike Name: {b['name']}
Brand: {b['brand']}
City: {b['city']}
Type: {b['type']}
Price/Hour: {b['hour']}
Price/Day: {b['day']}
Engine: {b['cc']}cc

"""

    # Intent-specific guidance for the LLM
    intent_guidance = {
        "recommendation": "The user is asking for a bike recommendation. Suggest the best matching bikes from the available list based on their needs (budget, city, type).",
        "agreement": "The user is asking about agreements or contracts. Explain the rental agreement process, terms, and what customers should review before accepting.",
        "maintenance": "The user is asking about maintenance or service. Provide maintenance guidance and mention that owners can use AI predictive maintenance on the platform.",
        "price": "The user is asking about pricing. Reference the actual prices from the available bikes list and explain dynamic pricing based on demand.",
        "general": "Answer the user's question helpfully using the available bike data when relevant.",
    }

    guidance = intent_guidance.get(intent, intent_guidance["general"])

    prompt = f"""
You are Bike Sharing AI Assistant.

These bikes are currently available.

{bike_info}

Intent detected: {intent}

{guidance}

Answer the user's question using ONLY these bikes whenever the user asks about bikes.

User:

{message}
"""

    reply = ask_llm(prompt)

    return {

        "reply": reply,
        "intent": intent

    }
