"""
WorkBridge - Jobs Routes
Handles job posting (clients), browsing/filtering (freelancers), and job details.
"""

from flask import Blueprint, request, jsonify, g
from models.sql_models import db, Job, User, Proposal
from utils.decorators import login_required
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


@jobs_bp.route("", methods=["POST"])
@login_required(role="client")
def create_job():
    """Client posts a new job."""
    data = request.get_json()
    title = data.get("title")
    description = data.get("description")
    budget = data.get("budget")
    category = data.get("category")
    required_skills = data.get("required_skills")
    deadline = data.get("deadline")

    if not all([title, description, budget]):
        return jsonify({"error": "title, description, and budget are required"}), 400

    new_job = Job(
        client_id=g.user.id,
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
@login_required(role="client")
def update_job_status(job_id):
    """Client updates job status (e.g. mark as completed)."""
    job = Job.query.with_for_update().get(job_id)
    if not job:
        db.session.rollback()
        return jsonify({"error": "Job not found"}), 404

    if job.client_id != g.user.id:
        db.session.rollback()
        return jsonify({"error": "You can only update your own jobs"}), 403

    data = request.get_json()
    new_status = data.get("status")

    if new_status not in ("open", "in_progress", "completed"):
        db.session.rollback()
        return jsonify({"error": "Invalid status value"}), 400

    # Enforce state machine
    if new_status == job.status:
        db.session.rollback()
        return jsonify({"message": "Job status unchanged", "job": job.to_dict()}), 200

    if new_status == "open":
        db.session.rollback()
        return jsonify({"error": "Cannot revert a job to open status"}), 409

    if new_status == "in_progress":
        db.session.rollback()
        return jsonify({"error": "in_progress status can only be set by accepting a proposal"}), 409

    if new_status == "completed":
        if job.status != "in_progress":
            db.session.rollback()
            return jsonify({"error": "Only in_progress jobs can be marked as completed"}), 409
        
        accepted = Proposal.query.filter_by(job_id=job.id, status="accepted").first()
        if not accepted:
            db.session.rollback()
            return jsonify({"error": "Cannot complete a job without an accepted proposal"}), 409

    job.status = new_status
    try:
        db.session.commit()
    except Exception:
        db.session.rollback()
        return jsonify({"error": "Failed to update job status"}), 500

    return jsonify({"message": "Job status updated", "job": job.to_dict()}), 200


@jobs_bp.route("/my-jobs", methods=["GET"])
@login_required(role="client")
def my_jobs():
    """Get all jobs posted by the currently logged-in client."""
    jobs = Job.query.filter_by(client_id=g.user.id).order_by(Job.created_at.desc()).all()
    return jsonify({"jobs": [job.to_dict() for job in jobs]}), 200


@jobs_bp.route("/<int:job_id>/recommended-freelancers", methods=["GET"])
@login_required(role="client")
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
    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404
    if job.client_id != g.user.id:
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
