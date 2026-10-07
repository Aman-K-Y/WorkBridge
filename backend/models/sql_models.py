"""
WorkBridge - SQLAlchemy Models
"""

from flask_sqlalchemy import SQLAlchemy
from sqlalchemy import CheckConstraint, UniqueConstraint, Index


db = SQLAlchemy()


class User(db.Model):
    __tablename__ = "users"
    __table_args__ = (
        {'mysql_charset': 'utf8mb4', 'mysql_collate': 'utf8mb4_unicode_ci'}
    )

    user_id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(150), nullable=False, unique=True)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(db.Enum("client", "freelancer"), nullable=False)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    created_at = db.Column(db.TIMESTAMP, server_default=db.func.now())
    updated_at = db.Column(db.TIMESTAMP, server_default=db.func.now(), onupdate=db.func.now())
    deleted_at = db.Column(db.TIMESTAMP, nullable=True)

    def to_dict(self):
        return {
            "user_id": self.user_id,
            "name": self.name,
            "email": self.email,
            "role": self.role,
            "is_active": self.is_active,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }


class Job(db.Model):
    __tablename__ = "jobs"
    __table_args__ = (
        CheckConstraint('budget > 0', name='check_budget_positive'),
        Index('idx_jobs_status_created_at', 'status', 'created_at'),
        Index('idx_jobs_fulltext', 'title', 'description', mysql_prefix='FULLTEXT'),
        {'mysql_charset': 'utf8mb4', 'mysql_collate': 'utf8mb4_unicode_ci'}
    )

    job_id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    client_id = db.Column(db.Integer, db.ForeignKey("users.user_id"), nullable=False)
    title = db.Column(db.String(150), nullable=False)
    description = db.Column(db.Text, nullable=False)
    budget = db.Column(db.Numeric(10, 2), nullable=False)
    category = db.Column(db.String(100))
    required_skills = db.Column(db.String(255))
    status = db.Column(db.Enum("open", "in_progress", "completed"), default="open")
    deadline = db.Column(db.Date)
    created_at = db.Column(db.TIMESTAMP, server_default=db.func.now())
    updated_at = db.Column(db.TIMESTAMP, server_default=db.func.now(), onupdate=db.func.now())

    def to_dict(self):
        return {
            "job_id": self.job_id,
            "client_id": self.client_id,
            "title": self.title,
            "description": self.description,
            "budget": float(self.budget),
            "category": self.category,
            "required_skills": self.required_skills,
            "status": self.status,
            "deadline": self.deadline.isoformat() if self.deadline else None,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }


class Proposal(db.Model):
    __tablename__ = "proposals"
    __table_args__ = (
        UniqueConstraint('job_id', 'freelancer_id', name='unique_bid_per_job'),
        CheckConstraint('bid_amount > 0', name='check_bid_positive'),
        {'mysql_charset': 'utf8mb4', 'mysql_collate': 'utf8mb4_unicode_ci'}
    )

    proposal_id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    job_id = db.Column(db.Integer, db.ForeignKey("jobs.job_id"), nullable=False)
    freelancer_id = db.Column(db.Integer, db.ForeignKey("users.user_id"), nullable=False)
    bid_amount = db.Column(db.Numeric(10, 2), nullable=False)
    cover_note = db.Column(db.Text)
    status = db.Column(db.Enum("pending", "accepted", "rejected"), default="pending")
    created_at = db.Column(db.TIMESTAMP, server_default=db.func.now())
    updated_at = db.Column(db.TIMESTAMP, server_default=db.func.now(), onupdate=db.func.now())

    def to_dict(self):
        return {
            "proposal_id": self.proposal_id,
            "job_id": self.job_id,
            "freelancer_id": self.freelancer_id,
            "bid_amount": float(self.bid_amount),
            "cover_note": self.cover_note,
            "status": self.status,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }


class Review(db.Model):
    __tablename__ = "reviews"
    __table_args__ = (
        UniqueConstraint('job_id', 'reviewer_id', name='uq_review'),
        CheckConstraint('rating BETWEEN 1 AND 5', name='check_rating_range'),
        {'mysql_charset': 'utf8mb4', 'mysql_collate': 'utf8mb4_unicode_ci'}
    )

    review_id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    job_id = db.Column(db.Integer, db.ForeignKey("jobs.job_id"), nullable=False)
    reviewer_id = db.Column(db.Integer, db.ForeignKey("users.user_id"), nullable=False)
    reviewee_id = db.Column(db.Integer, db.ForeignKey("users.user_id"), nullable=False)
    rating = db.Column(db.Integer, nullable=False)
    comment = db.Column(db.Text)
    created_at = db.Column(db.TIMESTAMP, server_default=db.func.now())
    updated_at = db.Column(db.TIMESTAMP, server_default=db.func.now(), onupdate=db.func.now())

    def to_dict(self):
        return {
            "review_id": self.review_id,
            "job_id": self.job_id,
            "reviewer_id": self.reviewer_id,
            "reviewee_id": self.reviewee_id,
            "rating": self.rating,
            "comment": self.comment,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }
