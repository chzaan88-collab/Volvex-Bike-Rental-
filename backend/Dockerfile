# ─────────────────────────────────────────────
#  Bike Sharing System – Docker Build
# ─────────────────────────────────────────────
FROM python:3.11-slim

# Set working directory inside the container
WORKDIR /app

# Install system dependencies for ReportLab (needed for PDF generation)
RUN apt-get update && apt-get install -y \
    gcc \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install Python packages
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy the entire project into the container
COPY . .

# Create necessary directories for uploads and agreements
RUN mkdir -p static/uploads agreements static/css

# Copy style.css into the css subfolder if it exists
RUN test -f static/style.css && cp static/style.css static/css/style.css || true

# Expose port 8000 for FastAPI
EXPOSE 8000

# Start the FastAPI application with Uvicorn
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]
