import os
import json
from dotenv import load_dotenv
import google.generativeai as genai

load_dotenv()

genai.configure(api_key=os.getenv("GEMINI_API_KEY"))

model = genai.GenerativeModel("gemini-2.5-flash")


def analyze_review(review):

    try:
        prompt=f"""
You are an AI Review Analyzer.

Analyze this bike rental review.

Return ONLY JSON.

{{
"sentiment":"Positive",
"rating":5,
"confidence":98,
"summary":"One line summary.",
"keywords":[
"Comfortable",
"Fast",
"Clean"
]
}}

Review:

{review}
"""

        response=model.generate_content(prompt)

        text=response.text.replace("```json","").replace("```","")

        return json.loads(text)

    except Exception:
        # Fallback heuristic-based sentiment analysis when Gemini is unavailable
        positive_words = ["great", "good", "excellent", "amazing", "love", "comfortable", "fast", "clean", "best", "awesome", "perfect", "recommend"]
        negative_words = ["bad", "poor", "worst", "terrible", "awful", "dirty", "slow", "broken", "issue", "problem", "disappointed", "hate"]

        text = review.lower()
        pos_count = sum(1 for w in positive_words if w in text)
        neg_count = sum(1 for w in negative_words if w in text)

        if pos_count > neg_count:
            sentiment = "Positive"
            rating = 5
        elif neg_count > pos_count:
            sentiment = "Negative"
            rating = 1
        else:
            sentiment = "Neutral"
            rating = 3

        return {
            "sentiment": sentiment,
            "rating": rating,
            "confidence": 60,
            "summary": review[:80] + ("..." if len(review) > 80 else ""),
            "keywords": [],
        }
