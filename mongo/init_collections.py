"""
WorkBridge - MongoDB Collection Initializer
Run this once to set up the 'messages' and 'portfolios' collections
with proper indexes for fast lookups.

Usage:
    python init_collections.py
"""
from pymongo import MongoClient, ASCENDING
import os
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), "..", ".env"))

MONGO_URI = os.environ.get("MONGO_URI", "mongodb://localhost:27017/")
DB_NAME = os.environ.get("MONGO_DB_NAME", "workbridge")

def init_collections():
    client = MongoClient(MONGO_URI)
    db = client[DB_NAME]

    # ---------------- messages ----------------
    # Chat messages tied to a specific job between client and freelancer
    messages = db["messages"]
    messages.create_index([("job_id", ASCENDING)])
    messages.create_index([("timestamp", ASCENDING)])
    print("✅ 'messages' collection ready with indexes on job_id and timestamp")

    # ---------------- portfolios ----------------
    # Freelancer portfolio data — flexible schema (skills, past work, bio)
    portfolios = db["portfolios"]
    portfolios.create_index([("user_id", ASCENDING)], unique=True)
    print("✅ 'portfolios' collection ready with unique index on user_id")

    # Insert a sample portfolio so there's something to query right away
    sample_portfolio = {
        "user_id": 2,  # matches Rohan Mehta (freelancer) from seed_data.sql
        "skills": ["Python", "Flask", "React", "MongoDB"],
        "past_work": [
            {
                "title": "E-commerce dashboard",
                "link": "https://github.com/example/ecommerce-dashboard",
                "description": "Built an admin dashboard for tracking orders and inventory."
            }
        ],
        "bio": "Full-stack developer with 3 years of freelance experience."
    }

    if portfolios.count_documents({"user_id": 2}) == 0:
        portfolios.insert_one(sample_portfolio)
        print("✅ Sample portfolio inserted for user_id 2")
    else:
        print("ℹ️  Sample portfolio already exists, skipping insert")

    print("\nMongoDB setup complete. Collections:", db.list_collection_names())


if __name__ == "__main__":
    init_collections()