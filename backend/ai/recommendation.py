from pydantic import BaseModel


class RecommendationRequest(BaseModel):

    budget: float

    city: str

    category: str

    ride_type: str