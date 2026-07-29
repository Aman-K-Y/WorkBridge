"""
WorkBridge - Proposals Routes
Freelancers submit bids on jobs; clients accept or reject them.
"""

from flask import Blueprint, request, jsonify, current_app
from models.sql_models import db, Proposal, Job, User
from utils.auth_helpers import decode_token

proposals_bp = Blueprint("proposals", __name__)


def get_authenticated_user():
    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Bearer "):
        return None
    token = auth_header.split(" ")[1]
    return decode_token(token, current_app.config["SECRET_KEY"])


@proposals_bp.route("", methods=["POST"])
def submit_proposal():
    """Freelancer submits a proposal/bid on a job."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401
    if user_payload["role"] != "freelancer":
        return jsonify({"error": "Only freelancers can submit proposals"}), 403

    data = request.get_json()
    job_id = data.get("job_id")
    bid_amount = data.get("bid_amount")
    cover_note = data.get("cover_note", "")

    if not all([job_id, bid_amount]):
        return jsonify({"error": "job_id and bid_amount are required"}), 400

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404
    if job.status != "open":
        return jsonify({"error": "This job is no longer accepting proposals"}), 400

    existing = Proposal.query.filter_by(job_id=job_id, freelancer_id=user_payload["user_id"]).first()
    if existing:
        return jsonify({"error": "You've already submitted a proposal for this job"}), 409

    new_proposal = Proposal(
        job_id=job_id,
        freelancer_id=user_payload["user_id"],
        bid_amount=bid_amount,
        cover_note=cover_note,
        status="pending",
    )
    db.session.add(new_proposal)
    db.session.commit()

    return jsonify({"message": "Proposal submitted", "proposal": new_proposal.to_dict()}), 201


@proposals_bp.route("/job/<int:job_id>", methods=["GET"])
def get_proposals_for_job(job_id):
    """Client views all proposals submitted for one of their jobs."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404
    if job.client_id != user_payload["user_id"]:
        return jsonify({"error": "You can only view proposals for your own jobs"}), 403

    proposals = Proposal.query.filter_by(job_id=job_id).order_by(Proposal.created_at.desc()).all()

    results = []
    for p in proposals:
        freelancer = User.query.get(p.freelancer_id)
        p_data = p.to_dict()
        p_data["freelancer_name"] = freelancer.name if freelancer else "Unknown"
        results.append(p_data)

    return jsonify({"proposals": results}), 200


@proposals_bp.route("/my-proposals", methods=["GET"])
def my_proposals():
    """Freelancer views all proposals they've submitted."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401
    if user_payload["role"] != "freelancer":
        return jsonify({"error": "Only freelancers have proposals to view here"}), 403

    proposals = Proposal.query.filter_by(freelancer_id=user_payload["user_id"]).order_by(
        Proposal.created_at.desc()
    ).all()

    return jsonify({"proposals": [p.to_dict() for p in proposals]}), 200


@proposals_bp.route("/<int:proposal_id>", methods=["PUT"])
def update_proposal_status(proposal_id):
    """Client accepts or rejects a proposal. Accepting also marks the job as in_progress
    and auto-rejects all other pending proposals for that job."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    proposal = Proposal.query.get(proposal_id)
    if not proposal:
        return jsonify({"error": "Proposal not found"}), 404

    job = Job.query.get(proposal.job_id)
    if not job or job.client_id != user_payload["user_id"]:
        return jsonify({"error": "You can only manage proposals on your own jobs"}), 403

    data = request.get_json()
    new_status = data.get("status")
    if new_status not in ("accepted", "rejected"):
        return jsonify({"error": "status must be 'accepted' or 'rejected'"}), 400

    proposal.status = new_status

    if new_status == "accepted":
        job.status = "in_progress"
        # Auto-reject all other pending proposals for this job
        other_proposals = Proposal.query.filter(
            Proposal.job_id == job.job_id,
            Proposal.proposal_id != proposal.proposal_id,
            Proposal.status == "pending",
        ).all()
        for p in other_proposals:
            p.status = "rejected"

    db.session.commit()

    return jsonify({"message": f"Proposal {new_status}", "proposal": proposal.to_dict()}), 200
