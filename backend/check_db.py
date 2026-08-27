import sqlite3

conn = sqlite3.connect("bike_sharing.db")
cursor = conn.cursor()

# List all tables
cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
tables = [r[0] for r in cursor.fetchall()]
print("Tables:", tables)

# Check reviews table
if "reviews" in tables:
    cursor.execute("SELECT * FROM reviews")
    rows = cursor.fetchall()
    print(f"\nReviews ({len(rows)}):")
    for r in rows:
        print(r)

# Check bookings table
if "bookings" in tables:
    cursor.execute("SELECT * FROM bookings")
    rows = cursor.fetchall()
    print(f"\nBookings ({len(rows)}):")
    for r in rows:
        print(r)

# Check users
if "users" in tables:
    cursor.execute("SELECT id, full_name, email, role, account_mode FROM users")
    rows = cursor.fetchall()
    print(f"\nUsers ({len(rows)}):")
    for r in rows:
        print(r)

# Check bikes
if "bikes" in tables:
    cursor.execute("SELECT id, bike_name, owner_id, status FROM bikes")
    rows = cursor.fetchall()
    print(f"\nBikes ({len(rows)}):")
    for r in rows:
        print(r)

conn.close()