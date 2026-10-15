from flask import Blueprint, jsonify, request, g, current_app
from models.sql_models import db, User, Job, Proposal
from utils.decorators import login_required
from utils.auth_helpers import decode_token
import os
import requests

users_bp = Blueprint("users", __name__)

MATCHING_SERVICE_URL = os.environ.get("MATCHING_SERVICE_URL", "http://localhost:8081")

@users_bp.route("/<int:user_id>", methods=["GET"])
def get_user_profile(user_id):
    """
    Fetch a user's profile with privacy rules:
    - Client:
      - email: visible
      - status (student/professional): visible
      - phone no.: hidden from freelancers UNLESS freelancer's proposal has been accepted
    - Freelancer:
      - higher_qualification: visible
      - status (student/unemployed/professional): visible
      - skills: visible
      - phone no.: visible
      - resume_url: visible
    - Completion percentage & pending info included.
    """
    user = User.query.get(user_id)
    if not user:
        return jsonify({"error": "User not found"}), 404

    # Determine viewer from optional Bearer token
    viewer_id = None
    viewer_role = None
    auth_header = request.headers.get("Authorization", "")
    if auth_header.startswith("Bearer "):
        token = auth_header.split(" ")[1]
        payload = decode_token(token, current_app.config["SECRET_KEY"])
        if payload:
            viewer_id = payload.get("sub") or payload.get("user_id")
            viewer_role = payload.get("role")

    # Determine phone visibility for client
    can_view_phone = True
    phone_hidden_reason = None
    if user.role == "client":
        if viewer_id == user.user_id:
            can_view_phone = True
        elif viewer_role == "freelancer":
            accepted_contract = (
                Proposal.query.join(Job, Proposal.job_id == Job.job_id)
                .filter(
                    Job.client_id == user.user_id,
                    Proposal.freelancer_id == viewer_id,
                    Proposal.status == "accepted",
                )
                .first()
            )
            can_view_phone = bool(accepted_contract)
            if not can_view_phone:
                phone_hidden_reason = "Phone number is confidential until your proposal is accepted by this client."
        else:
            can_view_phone = False
            phone_hidden_reason = "Contact phone number is shared only with contracted freelancers."

    profile = user.to_dict()
    if not can_view_phone:
        profile["phone_number"] = None
        profile["phone_hidden"] = True
        profile["phone_hidden_reason"] = phone_hidden_reason
    else:
        profile["phone_hidden"] = False

    # Freelancer portfolio integration
    if user.role == "freelancer":
        try:
            resp = requests.get(f"{MATCHING_SERVICE_URL}/api/portfolios/{user_id}", timeout=3)
            if resp.status_code == 200:
                profile["portfolio"] = resp.json()
            else:
                profile["portfolio"] = {}
        except Exception:
            profile["portfolio"] = {}

    return jsonify({"profile": profile}), 200


@users_bp.route("/profile", methods=["PUT"])
@login_required()
def update_profile():
    """Update personal and professional verification details."""
    data = request.get_json() or {}
    user = g.user

    if "status" in data:
        status_val = data["status"].strip().lower() if data["status"] else None
        if user.role == "client" and status_val and status_val not in ("student", "professional"):
            return jsonify({"error": "Status must be 'student' or 'professional'"}), 400
        if user.role == "freelancer" and status_val and status_val not in ("student", "unemployed", "professional"):
            return jsonify({"error": "Status must be 'student', 'unemployed', or 'professional'"}), 400
        user.status = status_val

    if "phone_number" in data:
        user.phone_number = data["phone_number"].strip() if data["phone_number"] else None

    if "avatar_url" in data:
        user.avatar_url = data["avatar_url"].strip() if data["avatar_url"] else None

    if user.role == "freelancer":
        if "higher_qualification" in data:
            user.higher_qualification = data["higher_qualification"].strip() if data["higher_qualification"] else None
        if "skills" in data:
            user.skills = data["skills"].strip() if data["skills"] else None
        if "resume_url" in data:
            user.resume_url = data["resume_url"].strip() if data["resume_url"] else None

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to save profile: {str(e)}"}), 500

    return jsonify({
        "message": "Profile updated successfully",
        "profile": user.to_dict()
    }), 200
