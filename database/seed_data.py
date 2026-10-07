import os
import sys
import datetime
from sqlalchemy.orm import sessionmaker
from sqlalchemy import create_engine
from dotenv import load_dotenv

# Add backend to path so we can import models and auth helpers
sys.path.append(os.path.join(os.path.dirname(__file__), "..", "backend"))

from models.sql_models import User, Job, Proposal, Review
from utils.auth_helpers import hash_password

load_dotenv(os.path.join(os.path.dirname(__file__), "..", ".env"))

def seed_database():
    if os.environ.get("FLASK_ENV") == "production":
        print("Never run seed_data.py in production!")
        return

    # Use the same URI logic as backend config
    MYSQL_USER = os.environ.get("MYSQL_USER", "root")
    MYSQL_PASSWORD = os.environ.get("MYSQL_PASSWORD", "")
    MYSQL_HOST = os.environ.get("MYSQL_HOST", "localhost")
    MYSQL_PORT = os.environ.get("MYSQL_PORT", "3306")
    MYSQL_DB = os.environ.get("MYSQL_DB", "workbridge")

    db_uri = f"mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DB}"
    engine = create_engine(db_uri)
    Session = sessionmaker(bind=engine)
    session = Session()

    try:
        # Clear existing data (respect foreign key constraints by deleting in correct order)
        session.query(Review).delete()
        session.query(Proposal).delete()
        session.query(Job).delete()
        session.query(User).delete()
        session.commit()

        # 1. Create Users
        # Using real bcrypt hashes
        pass_hash = hash_password("password123")
        
        users = [
            User(name="Alice Client", email="alice@example.com", password_hash=pass_hash, role="client"),
            User(name="Bob Freelancer", email="bob@example.com", password_hash=pass_hash, role="freelancer"),
            User(name="Charlie Client", email="charlie@example.com", password_hash=pass_hash, role="client"),
            User(name="Diana Freelancer", email="diana@example.com", password_hash=pass_hash, role="freelancer"),
        ]
        session.add_all(users)
        session.commit()

        # Map emails to IDs
        alice = session.query(User).filter_by(email="alice@example.com").first()
        bob = session.query(User).filter_by(email="bob@example.com").first()
        charlie = session.query(User).filter_by(email="charlie@example.com").first()
        diana = session.query(User).filter_by(email="diana@example.com").first()

        now = datetime.datetime.now()
        future = now + datetime.timedelta(days=30)
        past = now - datetime.timedelta(days=10)

        # 2. Create Jobs
        job1 = Job(
            client_id=alice.user_id,
            title="Build a website",
            description="Looking for a Flask expert.",
            budget=500.00,
            category="web development",
            required_skills="Python, Flask",
            status="open",
            deadline=future.date()
        )
        job2 = Job(
            client_id=charlie.user_id,
            title="Design a logo",
            description="Need a modern logo.",
            budget=150.00,
            category="design",
            required_skills="Photoshop, Illustrator",
            status="in_progress",
            deadline=future.date()
        )
        job3 = Job(
            client_id=alice.user_id,
            title="Data analysis script",
            description="Analyze some CSV files.",
            budget=300.00,
            category="data science",
            required_skills="Python, Pandas",
            status="completed",
            deadline=past.date()
        )
        session.add_all([job1, job2, job3])
        session.commit()

        # 3. Create Proposals
        prop1 = Proposal(
            job_id=job1.job_id,
            freelancer_id=bob.user_id,
            bid_amount=450.00,
            cover_note="I can build this website.",
            status="pending"
        )
        prop2 = Proposal(
            job_id=job2.job_id,
            freelancer_id=diana.user_id,
            bid_amount=150.00,
            cover_note="I will design a great logo.",
            status="accepted"
        )
        prop3 = Proposal(
            job_id=job3.job_id,
            freelancer_id=bob.user_id,
            bid_amount=300.00,
            cover_note="I am a data expert.",
            status="accepted"
        )
        session.add_all([prop1, prop2, prop3])
        session.commit()

        # 4. Create Reviews (only for completed jobs)
        rev1 = Review(
            job_id=job3.job_id,
            reviewer_id=alice.user_id,
            reviewee_id=bob.user_id,
            rating=5,
            comment="Great work on the data script!"
        )
        rev2 = Review(
            job_id=job3.job_id,
            reviewer_id=bob.user_id,
            reviewee_id=alice.user_id,
            rating=4,
            comment="Good client, clear requirements."
        )
        session.add_all([rev1, rev2])
        session.commit()

        print("Database seeded successfully with dev data.")

    except Exception as e:
        session.rollback()
        print(f"Error seeding database: {e}")
    finally:
        session.close()

if __name__ == "__main__":
    seed_database()
