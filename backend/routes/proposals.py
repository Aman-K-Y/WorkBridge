"""
WorkBridge - Proposals Routes
Freelancers submit bids on jobs; clients accept or reject them.
"""

from flask import Blueprint, request, jsonify, g
from models.sql_models import db, Proposal, Job, User
from utils.decorators import login_required

proposals_bp = Blueprint("proposals", __name__)


@proposals_bp.route("", methods=["POST"])
@login_required(role="freelancer")
def submit_proposal():
    """Freelancer submits a proposal/bid on a job."""
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

    existing = Proposal.query.filter_by(job_id=job_id, freelancer_id=g.user.id).first()
    if existing:
        return jsonify({"error": "You've already submitted a proposal for this job"}), 409

    new_proposal = Proposal(
        job_id=job_id,
        freelancer_id=g.user.id,
        bid_amount=bid_amount,
        cover_note=cover_note,
        status="pending",
    )
    db.session.add(new_proposal)
    db.session.commit()

    return jsonify({"message": "Proposal submitted", "proposal": new_proposal.to_dict()}), 201


@proposals_bp.route("/job/<int:job_id>", methods=["GET"])
@login_required(role="client")
def get_proposals_for_job(job_id):
    """Client views all proposals submitted for one of their jobs."""
    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404
    if job.client_id != g.user.id:
        return jsonify({"error": "You can only view proposals for your own jobs"}), 403

    proposals = Proposal.query.filter_by(job_id=job_id).order_by(Proposal.created_at.desc()).all()

    freelancer_ids = [p.freelancer_id for p in proposals]
    if freelancer_ids:
        freelancers = User.query.filter(User.id.in_(freelancer_ids)).all()
        freelancer_map = {f.id: f.name for f in freelancers}
    else:
        freelancer_map = {}

    results = []
    for p in proposals:
        p_data = p.to_dict()
        p_data["freelancer_name"] = freelancer_map.get(p.freelancer_id, "Unknown")
        results.append(p_data)

    return jsonify({"proposals": results}), 200


@proposals_bp.route("/my-proposals", methods=["GET"])
@login_required(role="freelancer")
def my_proposals():
    """Freelancer views all proposals they've submitted."""
    proposals = Proposal.query.filter_by(freelancer_id=g.user.id).order_by(
        Proposal.created_at.desc()
    ).all()

    return jsonify({"proposals": [p.to_dict() for p in proposals]}), 200


@proposals_bp.route("/<int:proposal_id>", methods=["PUT"])
@login_required(role="client")
def update_proposal_status(proposal_id):
    """Client accepts or rejects a proposal. Accepting also marks the job as in_progress
    and auto-rejects all other pending proposals for that job."""
    # Start transaction, lock proposal and job
    proposal = Proposal.query.with_for_update().get(proposal_id)
    if not proposal:
        db.session.rollback()
        return jsonify({"error": "Proposal not found"}), 404

    job = Job.query.with_for_update().get(proposal.job_id)
    if not job or job.client_id != g.user.id:
        db.session.rollback()
        return jsonify({"error": "You can only manage proposals on your own jobs"}), 403

    data = request.get_json()
    new_status = data.get("status")
    if new_status not in ("accepted", "rejected"):
        db.session.rollback()
        return jsonify({"error": "status must be 'accepted' or 'rejected'"}), 400

    if proposal.status != "pending":
        db.session.rollback()
        return jsonify({"error": "Only pending proposals can be accepted or rejected"}), 409

    if new_status == "accepted" and job.status != "open":
        db.session.rollback()
        return jsonify({"error": "You can only accept proposals on open jobs"}), 409

    proposal.status = new_status

    if new_status == "accepted":
        job.status = "in_progress"
        # Auto-reject all other pending proposals for this job
        other_proposals = Proposal.query.filter(
            Proposal.job_id == job.id,
            Proposal.id != proposal.id,
            Proposal.status == "pending",
        ).all()
        for p in other_proposals:
            p.status = "rejected"

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": "Failed to update proposal"}), 500

    return jsonify({"message": f"Proposal {new_status}", "proposal": proposal.to_dict()}), 200
