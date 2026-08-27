import json
import os
from .embeddings import get_embedding, cosine_similarity

VECTOR_STORE_PATH = os.path.join(os.path.dirname(__file__), "vector_store.json")


def _load_store() -> dict:
    """Load the vector store from disk."""
    if os.path.exists(VECTOR_STORE_PATH):
        try:
            with open(VECTOR_STORE_PATH, "r") as f:
                return json.load(f)
        except (json.JSONDecodeError, OSError):
            return {"documents": []}
    return {"documents": []}


def _save_store(store: dict) -> None:
    """Persist the vector store to disk."""
    with open(VECTOR_STORE_PATH, "w") as f:
        json.dump(store, f, indent=2)


def add_document(doc_id: str, text: str, metadata: dict | None = None) -> None:
    """Add a document with its embedding to the vector store."""
    store = _load_store()
    embedding = get_embedding(text)

    # Remove existing doc with same id
    store["documents"] = [d for d in store["documents"] if d.get("id") != doc_id]

    store["documents"].append({
        "id": doc_id,
        "text": text,
        "embedding": embedding,
        "metadata": metadata or {},
    })
    _save_store(store)


def search(query: str, top_k: int = 5) -> list[dict]:
    """Search the vector store for documents most similar to the query."""
    store = _load_store()
    if not store["documents"]:
        return []

    query_embedding = get_embedding(query)

    scored = []
    for doc in store["documents"]:
        score = cosine_similarity(query_embedding, doc.get("embedding", []))
        scored.append({
            "id": doc.get("id"),
            "text": doc.get("text", ""),
            "metadata": doc.get("metadata", {}),
            "score": round(score, 4),
        })

    scored.sort(key=lambda x: x["score"], reverse=True)
    return scored[:top_k]


def index_bikes(bikes) -> None:
    """Index all bikes into the vector store for semantic search."""
    for bike in bikes:
        text = (
            f"{bike.bike_name} {bike.brand} {bike.model} "
            f"{bike.bike_type} {bike.city} {bike.description or ''} "
            f"Rs.{bike.price_per_hour}/hr Rs.{bike.price_per_day}/day"
        )
        add_document(
            doc_id=f"bike_{bike.id}",
            text=text,
            metadata={
                "type": "bike",
                "bike_id": bike.id,
                "bike_name": bike.bike_name,
                "brand": bike.brand,
                "model": bike.model,
                "bike_type": bike.bike_type,
                "city": bike.city,
                "price_per_hour": bike.price_per_hour,
                "price_per_day": bike.price_per_day,
            },
        )


def clear_store() -> None:
    """Clear the vector store."""
    _save_store({"documents": []})