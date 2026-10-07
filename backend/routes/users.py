from flask import Blueprint, jsonify
from models.sql_models import User
import os
import requests

users_bp = Blueprint("users", __name__)

MATCHING_SERVICE_URL = os.environ.get("MATCHING_SERVICE_URL", "http://localhost:8081")

@users_bp.route("/<int:user_id>", methods=["GET"])
def get_user_profile(user_id):
    """Fetch a user's public profile, including their portfolio if they are a freelancer."""
    user = User.query.get(user_id)
    if not user:
        return jsonify({"error": "User not found"}), 404

    profile = {
        "user_id": user.user_id,
        "name": user.name,
        "role": user.role,
        "created_at": user.created_at.isoformat() if user.created_at else None
    }

    if user.role == "freelancer":
        try:
            resp = requests.get(f"{MATCHING_SERVICE_URL}/api/portfolios/{user_id}", timeout=3)
            if resp.status_code == 200:
                profile["portfolio"] = resp.json()
            else:
                profile["portfolio"] = {}
        except Exception:
            profile["portfolio"] = {"error": "Could not load portfolio data"}

    return jsonify({"profile": profile}), 200
