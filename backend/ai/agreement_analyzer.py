import io
import json
import os
from dotenv import load_dotenv
from google import genai

load_dotenv()

API_KEY = os.getenv("GEMINI_API_KEY")

# Initialize client if API key is present
client = genai.Client(api_key=API_KEY) if API_KEY else None


def extract_pdf_text(pdf_bytes):
    """Extract text from a PDF file using PyPDF2 or pdfplumber."""
    try:
        from PyPDF2 import PdfReader
        reader = PdfReader(io.BytesIO(pdf_bytes))
        text = "\n".join(page.extract_text() or "" for page in reader.pages)
        return text.strip()
    except ImportError:
        try:
            import pdfplumber
            with pdfplumber.open(io.BytesIO(pdf_bytes)) as pdf:
                return "\n".join(page.extract_text() or "" for page in pdf.pages).strip()
        except ImportError:
            return None


def analyze_agreement_pdf(pdf_bytes):
    """Analyze a PDF agreement file by extracting text first."""
    text = extract_pdf_text(pdf_bytes)
    if not text:
        return {
            "risk_score": 0,
            "risk_level": "Unknown",
            "summary": "Could not extract text from the PDF. Ensure it is a text-based PDF (not scanned images).",
            "important_points": [],
            "warnings": [],
            "recommendations": []
        }
    return analyze_agreement(text)


def analyze_agreement(agreement_text):
    if not client:
        return {
            "risk_score": 0,
            "risk_level": "Unknown",
            "summary": "Gemini API Key not configured.",
            "important_points": [],
            "warnings": [],
            "recommendations": []
        }

    prompt = f"""
You are a Legal AI Expert.

Analyze this Bike Rental Agreement.

Return ONLY JSON.

Format:
{{
  "risk_score": 85,
  "risk_level": "Medium",
  "summary": "Summary here",
  "important_points": ["point1", "point2"],
  "warnings": ["warning1", "warning2"],
  "recommendations": ["recommendation1", "recommendation2"]
}}

Agreement:
{agreement_text}
"""

    try:
        # Correct New Google GenAI SDK Execution
        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=prompt,
        )

        res_text = response.text.strip()

        # Clean markdown wrappers if present
        res_text = res_text.replace("```json", "").replace("```", "").strip()

        return json.loads(res_text)

    except Exception as e:
        return {
            "risk_score": 0,
            "risk_level": "Unknown",
            "summary": f"Analysis failed: {str(e)}",
            "important_points": [],
            "warnings": [],
            "recommendations": []
        }