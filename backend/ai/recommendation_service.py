from ai.recommendation_engine import BikeRecommendationEngine

engine = BikeRecommendationEngine()


def recommend_bikes(bikes, request):

    return engine.recommend(

        bikes,

        request

    )