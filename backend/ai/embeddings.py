import os
import json
import hashlib
from dotenv import load_dotenv

load_dotenv()

API_KEY = os.getenv("GEMINI_API_KEY")


def get_embedding(text: str) -> list[float]:
    """
    Generate an embedding vector for the given text using Gemini embeddings.
    Falls back to a deterministic hash-based vector if the API key is missing.
    """
    if not API_KEY:
        return _fallback_embedding(text)

    try:
        import google.generativeai as genai

        genai.configure(api_key=API_KEY)
        result = genai.embed_content(
            model="models/text-embedding-004",
            content=text,
        )
        return result["embedding"]
    except Exception:
        return _fallback_embedding(text)


def _fallback_embedding(text: str, dim: int = 64) -> list[float]:
    """Deterministic hash-based embedding fallback (no external API needed)."""
    vector = [0.0] * dim
    words = text.lower().split()
    for word in words:
        h = int(hashlib.md5(word.encode()).hexdigest(), 16)
        idx = h % dim
        vector[idx] += 1.0
    # Normalize
    norm = sum(v * v for v in vector) ** 0.5
    if norm > 0:
        vector = [v / norm for v in vector]
    return vector


def cosine_similarity(a: list[float], b: list[float]) -> float:
    """Compute cosine similarity between two vectors."""
    if not a or not b or len(a) != len(b):
        return 0.0
    dot = sum(x * y for x, y in zip(a, b))
    norm_a = sum(x * x for x in a) ** 0.5
    norm_b = sum(y * y for y in b) ** 0.5
    if norm_a == 0 or norm_b == 0:
        return 0.0
    return dot / (norm_a * norm_b)