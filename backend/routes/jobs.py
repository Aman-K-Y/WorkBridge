"""
WorkBridge - Jobs Routes
Handles job posting (clients), browsing/filtering (freelancers), and job details.
"""

from flask import Blueprint, request, jsonify, g
from models.sql_models import db, Job, User, Proposal
from utils.decorators import login_required
from utils.validation import get_safe_json, validate_decimal, validate_string, normalize_skills
from flask import current_app
import datetime
import requests

jobs_bp = Blueprint("jobs", __name__)

import os

MATCHING_SERVICE_URL = os.environ.get("MATCHING_SERVICE_URL", "http://localhost:8081")


def parse_deadline(deadline_str):
    """Converts a 'YYYY-MM-DD' string into a Python date object."""
    if not deadline_str:
        return None
    try:
        dt = datetime.datetime.strptime(deadline_str, "%Y-%m-%d").date()
        if dt < datetime.date.today():
            raise ValueError("Deadline cannot be in the past")
        return dt
    except (ValueError, TypeError):
        raise ValueError("Invalid deadline format (expected YYYY-MM-DD) or past date")


@jobs_bp.route("", methods=["POST"])
@login_required(role="client")
def create_job():
    """Client posts a new job."""
    data = get_safe_json()
    try:
        title = validate_string(data.get("title"), "title", 150)
        description = validate_string(data.get("description"), "description")
        budget = validate_decimal(data.get("budget"), "budget")
        category = validate_string(data.get("category"), "category", 100)
        required_skills = normalize_skills(data.get("required_skills"))
        deadline = parse_deadline(data.get("deadline"))
    except ValueError as e:
        return jsonify({"error": str(e)}), 400

    new_job = Job(
        client_id=g.user.id,
        title=title,
        description=description,
        budget=budget,
        category=category,
        required_skills=required_skills,
        deadline=deadline,
        status="open",
    )
    db.session.add(new_job)
    db.session.commit()

    return jsonify({"message": "Job posted successfully", "job": new_job.to_dict()}), 201


@jobs_bp.route("", methods=["GET"])
def browse_jobs():
    """
    Browse/filter jobs with pagination and search.
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
    if status in ("open", "in_progress", "completed"):
        query = query.filter(Job.status == status)

    q = request.args.get("q")
    if q:
        search = f"%{q}%"
        query = query.filter(Job.title.ilike(search) | Job.description.ilike(search))

    page = request.args.get("page", 1, type=int)
    per_page = min(request.args.get("per_page", 20, type=int), 50)

    pagination = query.order_by(Job.created_at.desc()).paginate(page=page, per_page=per_page, error_out=False)
    
    return jsonify({
        "jobs": [job.to_dict() for job in pagination.items],
        "total": pagination.total,
        "pages": pagination.pages,
        "current_page": page
    }), 200


@jobs_bp.route("/<int:job_id>", methods=["GET"])
def get_job_details(job_id):
    """Get a single job's full details, including the client's name."""
    job = db.session.get(Job, job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    client = db.session.get(User, job.client_id)
    job_data = job.to_dict()
    job_data["client_name"] = client.name if client else "Unknown"
    job_data["created_at"] = job.created_at.isoformat() if job.created_at else None

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

    data = get_safe_json()
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
    """
    job = db.session.get(Job, job_id)
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
        # 3.10 Return generic error message and don't expose hostnames
        current_app.logger.error(f"Matching service failure: {str(e)}")
        return jsonify({
            "error": "Matching service is temporarily unavailable."
        }), 503

    # Fix N+1 queries by fetching all matches users in one query
    match_user_ids = [match["userId"] for match in match_data.get("matches", [])]
    if match_user_ids:
        freelancers = User.query.filter(User.id.in_(match_user_ids)).all()
        freelancer_map = {f.id: f.name for f in freelancers}
    else:
        freelancer_map = {}

    enriched_matches = []
    for match in match_data.get("matches", []):
        enriched_matches.append({
            **match,
            "freelancer_name": freelancer_map.get(match["userId"], "Unknown"),
        })

    return jsonify({
        "job_id": job_id,
        "required_skills": job.required_skills,
        "matches": enriched_matches,
    }), 200
