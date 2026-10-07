"""
WorkBridge - Auth Routes
Handles user signup and login.
"""

from flask import Blueprint, request, jsonify, current_app
from models.sql_models import db, User
from utils.auth_helpers import hash_password, check_password, generate_token
from email_validator import validate_email, EmailNotValidError
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
import bcrypt

auth_bp = Blueprint("auth", __name__)

# Basic in-memory limiter for this blueprint.
# Note: In production, app-level limiter attached to Redis is better.
limiter = Limiter(key_func=get_remote_address)


@auth_bp.route("/signup", methods=["POST"])
@limiter.limit("5 per minute")
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

    if len(password) < 8:
        return jsonify({"error": "Password must be at least 8 characters"}), 400

    try:
        email_info = validate_email(email, check_deliverability=False)
        email = email_info.normalized
    except EmailNotValidError as e:
        return jsonify({"error": str(e)}), 400

    if User.query.filter_by(email=email).first():
        return jsonify({"error": "An account with this email already exists"}), 409

    try:
        hashed = hash_password(password)
    except ValueError as e:
        return jsonify({"error": str(e)}), 400

    new_user = User(
        name=name,
        email=email,
        password_hash=hashed,
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
@limiter.limit("5 per minute")
def login():
    data = request.get_json()
    email = data.get("email")
    password = data.get("password")

    if not all([email, password]):
        return jsonify({"error": "email and password are required"}), 400

    try:
        email_info = validate_email(email, check_deliverability=False)
        email = email_info.normalized
    except EmailNotValidError:
        pass # Will fail the lookup below

    user = User.query.filter_by(email=email).first()

    # Dummy check to mitigate timing attacks
    dummy_hash = "$2b$12$DUMMYDUMMYDUMMYDUMMYDUMMYDUMMYDUMMYDUMMYDUMMYDUMMYDUMMY"
    valid = False
    
    if user:
        valid = check_password(password, user.password_hash)
    else:
        # Dummy check always returns false but takes similar time
        check_password(password, dummy_hash)

    if not user or not valid:
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