from functools import wraps

from flask import current_app, g, jsonify, request

from models.sql_models import User
from utils.auth_helpers import decode_token


def login_required(role=None):
    """
    Decorator to require a valid JWT token.
    Optionally enforces a specific role ('client' or 'freelancer').
    Loads the user from MySQL into the Flask global 'g.user'.
    """
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            auth_header = request.headers.get("Authorization", "")
            if not auth_header.startswith("Bearer "):
                return jsonify({"error": "Authentication required"}), 401

            token = auth_header.split(" ")[1]
            payload = decode_token(token, current_app.config["SECRET_KEY"])

            if not payload:
                return jsonify({"error": "Invalid or expired token"}), 401

            user_id = payload.get("sub") or payload.get("user_id")
            if not user_id:
                return jsonify({"error": "Invalid token payload"}), 401

            user = User.query.get(user_id)
            if not user:
                return jsonify({"error": "User no longer exists"}), 401

            if role and user.role != role:
                return jsonify({"error": f"Only {role}s can perform this action"}), 403

            g.user = user
            return f(*args, **kwargs)
        return decorated_function
    return decorator
