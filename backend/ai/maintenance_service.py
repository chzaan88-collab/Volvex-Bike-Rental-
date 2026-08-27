from ai.maintenance_prediction import engine


def predict_maintenance(

    bike,

    booking_count,

    average_rating

):

    return engine.predict(

        bike,

        booking_count,

        average_rating

    )