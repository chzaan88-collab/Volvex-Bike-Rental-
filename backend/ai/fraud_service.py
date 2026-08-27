from ai.fraud_detection import FraudDetectionEngine

engine = FraudDetectionEngine()

def detect_fraud(customer, bike, booking_amount, booking_count):

    return engine.detect(
        customer,
        bike,
        booking_amount,
        booking_count
    )