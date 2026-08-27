from .price_prediction import engine


def predict_price(bike):
    """
    Unified price prediction entry point.
    Accepts either a Bike ORM model or a PricePredictionRequest dict.
    """
    return engine.predict(bike)
