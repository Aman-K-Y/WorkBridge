"""
WorkBridge - Jobs Routes
Handles job posting (clients), browsing/filtering (freelancers), and job details.
"""

from flask import Blueprint, request, jsonify
from models.sql_models import db, Job, User
from utils.auth_helpers import decode_token
from flask import current_app
import datetime
import requests

jobs_bp = Blueprint("jobs", __name__)

import os

MATCHING_SERVICE_URL = os.environ.get("MATCHING_SERVICE_URL", "http://localhost:8081")


def parse_deadline(deadline_str):
    """Converts a 'YYYY-MM-DD' string into a Python date object, or None if empty/invalid."""
    if not deadline_str:
        return None
    try:
        return datetime.datetime.strptime(deadline_str, "%Y-%m-%d").date()
    except (ValueError, TypeError):
        return None


def get_authenticated_user():
    """Pulls the JWT from the Authorization header and returns the decoded payload, or None."""
    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Bearer "):
        return None
    token = auth_header.split(" ")[1]
    return decode_token(token, current_app.config["SECRET_KEY"])


@jobs_bp.route("", methods=["POST"])
def create_job():
    """Client posts a new job. Requires a valid JWT with role='client'."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401
    if user_payload["role"] != "client":
        return jsonify({"error": "Only clients can post jobs"}), 403

    data = request.get_json()
    title = data.get("title")
    description = data.get("description")
    budget = data.get("budget")
    category = data.get("category")
    required_skills = data.get("required_skills")  # comma-separated string, e.g. "Python, React"
    deadline = data.get("deadline")  # expected format: "YYYY-MM-DD"

    if not all([title, description, budget]):
        return jsonify({"error": "title, description, and budget are required"}), 400

    new_job = Job(
        client_id=user_payload["user_id"],
        title=title,
        description=description,
        budget=budget,
        category=category,
        required_skills=required_skills,
        deadline=parse_deadline(deadline),
        status="open",
    )
    db.session.add(new_job)
    db.session.commit()

    return jsonify({"message": "Job posted successfully", "job": new_job.to_dict()}), 201


@jobs_bp.route("", methods=["GET"])
def browse_jobs():
    """
    Browse/filter jobs. Public endpoint — no auth required, so anyone can see open jobs.
    Query params (all optional): category, min_budget, max_budget, status
    """
    query = Job.query

    category = request.args.get("category")
    if category:
        query = query.filter(Job.category == category)

    min_budget = request.args.get("min_budget", type=float)
    if min_budget is not None:
        query = query.filter(Job.budget >= min_budget)

    max_budget = request.args.get("max_budget", type=float)
    if max_budget is not None:
        query = query.filter(Job.budget <= max_budget)

    status = request.args.get("status", default="open")
    query = query.filter(Job.status == status)

    jobs = query.order_by(Job.created_at.desc()).all()
    return jsonify({"jobs": [job.to_dict() for job in jobs]}), 200


@jobs_bp.route("/<int:job_id>", methods=["GET"])
def get_job_details(job_id):
    """Get a single job's full details, including the client's name."""
    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    client = User.query.get(job.client_id)
    job_data = job.to_dict()
    job_data["client_name"] = client.name if client else "Unknown"

    return jsonify({"job": job_data}), 200


@jobs_bp.route("/<int:job_id>", methods=["PUT"])
def update_job_status(job_id):
    """Client updates job status (e.g. mark as in_progress or completed)."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    if job.client_id != user_payload["user_id"]:
        return jsonify({"error": "You can only update your own jobs"}), 403

    data = request.get_json()
    new_status = data.get("status")
    if new_status not in ("open", "in_progress", "completed"):
        return jsonify({"error": "Invalid status value"}), 400

    job.status = new_status
    db.session.commit()

    return jsonify({"message": "Job status updated", "job": job.to_dict()}), 200


@jobs_bp.route("/my-jobs", methods=["GET"])
def my_jobs():
    """Get all jobs posted by the currently logged-in client."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    jobs = Job.query.filter_by(client_id=user_payload["user_id"]).order_by(Job.created_at.desc()).all()
    return jsonify({"jobs": [job.to_dict() for job in jobs]}), 200


@jobs_bp.route("/<int:job_id>/recommended-freelancers", methods=["GET"])
def recommended_freelancers(job_id):
    """
    Recommends freelancers for a job based on skill overlap, by calling out
    to the Java matching microservice. Only the job's own client can see this.

    This endpoint demonstrates the microservice split: this Python API
    handles auth, fetches the job's required_skills from MySQL, then
    delegates the actual matching computation to the separate Java service,
    and finally enriches the Java service's response (which only knows
    Mongo user_ids) with real names from MySQL before returning to the client.
    """
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404
    if job.client_id != user_payload["user_id"]:
        return jsonify({"error": "You can only view recommendations for your own jobs"}), 403

    if not job.required_skills:
        return jsonify({"error": "This job has no required_skills set, so matching can't run", "matches": []}), 200

    try:
        response = requests.get(
            f"{MATCHING_SERVICE_URL}/api/match",
            params={"skills": job.required_skills, "limit": 5},
            timeout=5,
        )
        response.raise_for_status()
        match_data = response.json()
    except requests.exceptions.RequestException as e:
        return jsonify({
            "error": "Could not reach the matching service. Make sure it's running on port 8081.",
            "detail": str(e),
        }), 503

    # Enrich each match with the freelancer's real name from MySQL
    enriched_matches = []
    for match in match_data.get("matches", []):
        freelancer = User.query.get(match["userId"])
        enriched_matches.append({
            **match,
            "freelancer_name": freelancer.name if freelancer else "Unknown",
        })

    return jsonify({
        "job_id": job_id,
        "required_skills": job.required_skills,
        "matches": enriched_matches,
    }), 200
