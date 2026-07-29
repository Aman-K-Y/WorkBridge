"""
WorkBridge - Messages Routes
Chat between client and freelancer, scoped to a specific job.
Uses MongoDB since message threads are variable-length and high-write-frequency.
"""

from flask import Blueprint, request, jsonify, current_app
from pymongo import MongoClient, ASCENDING
from models.sql_models import Job
from utils.auth_helpers import decode_token
import datetime

messages_bp = Blueprint("messages", __name__)

# Reuse a single MongoClient across requests instead of opening a new
# connection every time — this is the correct pattern for production use.
_mongo_client = None


def get_authenticated_user():
    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Bearer "):
        return None
    token = auth_header.split(" ")[1]
    return decode_token(token, current_app.config["SECRET_KEY"])


def get_mongo_db():
    """Returns the MongoDB database handle, reusing one client connection."""
    global _mongo_client
    if _mongo_client is None:
        _mongo_client = MongoClient(current_app.config["MONGO_URI"])
    return _mongo_client[current_app.config["MONGO_DB_NAME"]]


@messages_bp.route("/<int:job_id>", methods=["GET"])
def get_messages(job_id):
    """Get the full message thread for a job. Only the client or the accepted
    freelancer on that job can view it."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    # Only the job's client, or someone who has an accepted proposal on it, can view messages.
    # (Simplified check here: allow the client, or allow any freelancer for now —
    # tighten this once proposal-acceptance checks are wired in fully.)
    user_id = user_payload["user_id"]
    is_client = job.client_id == user_id

    if not is_client and user_payload["role"] != "freelancer":
        return jsonify({"error": "Not authorized to view this conversation"}), 403

    db = get_mongo_db()
    messages = list(
        db.messages.find({"job_id": job_id}, {"_id": 0}).sort("timestamp", ASCENDING)
    )

    return jsonify({"messages": messages}), 200


@messages_bp.route("", methods=["POST"])
def send_message():
    """Send a message tied to a job, from the logged-in user to a specific receiver."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    data = request.get_json()
    job_id = data.get("job_id")
    receiver_id = data.get("receiver_id")
    text = data.get("text")

    if not all([job_id, receiver_id, text]):
        return jsonify({"error": "job_id, receiver_id, and text are all required"}), 400

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    message_doc = {
        "job_id": job_id,
        "sender_id": user_payload["user_id"],
        "receiver_id": receiver_id,
        "text": text,
        "timestamp": datetime.datetime.utcnow().isoformat(),
    }

    db = get_mongo_db()
    db.messages.insert_one(message_doc)

    # Remove the Mongo-internal _id before returning, it's not JSON serializable by default
    message_doc.pop("_id", None)

    return jsonify({"message": "Sent", "data": message_doc}), 201


@messages_bp.route("/conversations", methods=["GET"])
def get_conversations():
    """Get a list of distinct job_ids the logged-in user has messages in —
    useful for showing an inbox/conversation list."""
    user_payload = get_authenticated_user()
    if not user_payload:
        return jsonify({"error": "Authentication required"}), 401

    user_id = user_payload["user_id"]
    db = get_mongo_db()

    job_ids = db.messages.distinct(
        "job_id",
        {"$or": [{"sender_id": user_id}, {"receiver_id": user_id}]},
    )

    return jsonify({"job_ids": job_ids}), 200
