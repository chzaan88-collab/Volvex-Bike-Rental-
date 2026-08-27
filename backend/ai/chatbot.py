class BikeAIChatbot:

    def reply(self, message):

        msg = message.lower()

        # Recommendation
        if "recommend" in msg or "bike" in msg:

            return {
                "reply": "I recommend Honda CG125. It offers excellent fuel economy, reliability and is one of the most booked bikes on the platform."
            }

        # Price
        elif "price" in msg or "rent" in msg:

            return {
                "reply": "Average rental prices range between Rs.250 and Rs.500 per hour depending on bike model and city."
            }

        # Maintenance
        elif "maintenance" in msg or "service" in msg:

            return {
                "reply": "Regular engine oil change every 2000 km and brake inspection every month are recommended."
            }

        # Agreement
        elif "agreement" in msg or "contract" in msg:

            return {
                "reply": "Please read all agreement terms carefully before accepting. Customers are responsible for damages and timely return."
            }

        # Fraud
        elif "fraud" in msg:

            return {
                "reply": "Our AI fraud detection checks suspicious booking behavior, fake users and unusual activities automatically."
            }

        # Demand
        elif "demand" in msg:

            return {
                "reply": "Demand is highest on weekends and during sunny weather. Dynamic pricing may increase accordingly."
            }

        # Reviews
        elif "review" in msg:

            return {
                "reply": "Customers highly appreciate clean bikes, polite owners and affordable prices."
            }

        else:

            return {
                "reply": "Hello 👋 I am Bike Sharing AI Assistant. Ask me about bikes, pricing, maintenance, agreements, demand forecast or fraud detection."
            }


chatbot = BikeAIChatbot()