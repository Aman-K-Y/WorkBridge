"""
WorkBridge - Reviews Routes
Two-way reviews left after a job is marked completed:
client rates freelancer, freelancer rates client.
"""

from flask import Blueprint, request, jsonify, current_app
from models.sql_models import db, Review, Job, Proposal, User
from utils.auth_helpers import decode_token

reviews_bp = Blueprint("reviews", __name__)


def get_authenticated_user():
    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Bearer "):
        return None
    token = auth_header.split(" ")[1]
    return decode_token(token, current_app.config["SECRET_KEY"])


def get_job_counterpart(job, user_id):
    """Given a job and the user leaving a review, figure out who the review is about.
    If the reviewer is the client, the reviewee is the accepted freelancer, and vice versa."""
    if job.client_id == user_id:
        accepted = Proposal.query.filter_by(job_id=job.job_id, status="accepted").first()
        return accepted.freelancer_id if accepted else None
    else:
        return job.client_id


@reviews_bp.route("", methods=["POST"])
def submit_review():
    """Leave a review for the other party on a completed job."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    data = request.get_json()
    job_id = data.get("job_id")
    rating = data.get("rating")
    comment = data.get("comment", "")

    if not all([job_id, rating]):
        return jsonify({"error": "job_id and rating are required"}), 400

    if not isinstance(rating, int) or not (1 <= rating <= 5):
        return jsonify({"error": "rating must be an integer between 1 and 5"}), 400

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    if job.status != "completed":
        return jsonify({"error": "You can only review jobs that are marked completed"}), 400

    reviewer_id = user_payload["user_id"]
    reviewee_id = get_job_counterpart(job, reviewer_id)

    if reviewee_id is None:
        return jsonify({"error": "Could not determine who this review is for"}), 400

    existing = Review.query.filter_by(job_id=job_id, reviewer_id=reviewer_id).first()
    if existing:
        return jsonify({"error": "You've already reviewed this job"}), 409

    new_review = Review(
        job_id=job_id,
        reviewer_id=reviewer_id,
        reviewee_id=reviewee_id,
        rating=rating,
        comment=comment,
    )
    db.session.add(new_review)
    db.session.commit()

    return jsonify({"message": "Review submitted", "review": new_review.to_dict()}), 201


@reviews_bp.route("/user/<int:user_id>", methods=["GET"])
def get_reviews_for_user(user_id):
    """Public endpoint — view all reviews received by a given user (their reputation)."""
    user = User.query.get(user_id)
    if not user:
        return jsonify({"error": "User not found"}), 404

    reviews = Review.query.filter_by(reviewee_id=user_id).order_by(Review.created_at.desc()).all()

    if reviews:
        avg_rating = sum(r.rating for r in reviews) / len(reviews)
    else:
        avg_rating = None

    results = []
    for r in reviews:
        reviewer = User.query.get(r.reviewer_id)
        r_data = r.to_dict()
        r_data["reviewer_name"] = reviewer.name if reviewer else "Unknown"
        results.append(r_data)

    return jsonify({
        "user_name": user.name,
        "average_rating": round(avg_rating, 2) if avg_rating else None,
        "total_reviews": len(reviews),
        "reviews": results,
    }), 200


@reviews_bp.route("/job/<int:job_id>", methods=["GET"])
def get_reviews_for_job(job_id):
    """View both reviews (if they exist) tied to a specific job."""
    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    reviews = Review.query.filter_by(job_id=job_id).all()
    return jsonify({"reviews": [r.to_dict() for r in reviews]}), 200
