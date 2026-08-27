from google import genai
from config import GEMINI_API_KEY

client = genai.Client(api_key=GEMINI_API_KEY) if GEMINI_API_KEY else None


SYSTEM_PROMPT = """
You are Bike Sharing AI Assistant.

Rules:

- Detect user's language automatically.
- Reply in the SAME language.
- If user writes Urdu, reply in Urdu.
- If user writes Roman Urdu, reply in Roman Urdu.
- If user writes English, reply in English.
- Be friendly and professional.
- Keep replies concise unless user asks for details.
- Help with bikes, bookings, agreements, maintenance, pricing and platform usage.
"""


def ask_llm(message):

    if not client:
        return "I'm sorry, the AI assistant is currently unavailable because the Gemini API key is not configured. Please ask an administrator to set GEMINI_API_KEY. Meanwhile, I can still help you browse available bikes!"

    try:
        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=f"{SYSTEM_PROMPT}\n\nUser: {message}"
        )
        return response.text
    except Exception:
        return "I'm sorry, I'm having trouble connecting to the AI service right now. Please try again later or contact support."
