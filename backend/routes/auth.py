"""
WorkBridge - Auth Routes
Handles user signup and login.
"""

from flask import Blueprint, request, jsonify, current_app
from models.sql_models import db, User
from utils.auth_helpers import hash_password, check_password, generate_token

auth_bp = Blueprint("auth", __name__)


@auth_bp.route("/signup", methods=["POST"])
def signup():
    data = request.get_json()

    name = data.get("name")
    email = data.get("email")
    password = data.get("password")
    role = data.get("role")  # "client" or "freelancer"

    if not all([name, email, password, role]):
        return jsonify({"error": "name, email, password, and role are all required"}), 400

    if role not in ("client", "freelancer"):
        return jsonify({"error": "role must be 'client' or 'freelancer'"}), 400

    if User.query.filter_by(email=email).first():
        return jsonify({"error": "An account with this email already exists"}), 409

    new_user = User(
        name=name,
        email=email,
        password_hash=hash_password(password),
        role=role,
    )
    db.session.add(new_user)
    db.session.commit()

    token = generate_token(
        new_user.user_id, new_user.role,
        current_app.config["SECRET_KEY"],
        current_app.config["JWT_EXPIRY_HOURS"],
    )

    return jsonify({
        "message": "Account created successfully",
        "user": new_user.to_dict(),
        "token": token,
    }), 201


@auth_bp.route("/login", methods=["POST"])
def login():
    data = request.get_json()
    email = data.get("email")
    password = data.get("password")

    if not all([email, password]):
        return jsonify({"error": "email and password are required"}), 400

    user = User.query.filter_by(email=email).first()

    if not user or not check_password(password, user.password_hash):
        return jsonify({"error": "Invalid email or password"}), 401

    token = generate_token(
        user.user_id, user.role,
        current_app.config["SECRET_KEY"],
        current_app.config["JWT_EXPIRY_HOURS"],
    )

    return jsonify({
        "message": "Login successful",
        "user": user.to_dict(),
        "token": token,
    }), 200