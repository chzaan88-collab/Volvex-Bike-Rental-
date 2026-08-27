"""
Migration: Add missing columns to the reviews table.
Run from backend/: python migrate_reviews.py
"""
import sqlite3

conn = sqlite3.connect("bike_sharing.db")
cursor = conn.cursor()

# Check existing columns
cursor.execute("PRAGMA table_info(reviews)")
existing = {r[1] for r in cursor.fetchall()}
print("Existing reviews columns:", existing)

# Add missing columns
migrations = [
    ("owner_id", "INTEGER"),
    ("sentiment", "VARCHAR"),
    ("ai_score", "FLOAT"),
    ("created_at", "DATETIME"),
]

for col, col_type in migrations:
    if col not in existing:
        print(f"Adding column: {col} {col_type}")
        cursor.execute(f"ALTER TABLE reviews ADD COLUMN {col} {col_type}")
    else:
        print(f"Column already exists: {col}")

conn.commit()

# Verify
cursor.execute("PRAGMA table_info(reviews)")
print("\nFinal reviews columns:", [r[1] for r in cursor.fetchall()])
conn.close()
print("\nMigration complete!")