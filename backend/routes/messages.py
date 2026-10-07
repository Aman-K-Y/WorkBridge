"""
WorkBridge - Messages Routes
Chat between client and freelancer, scoped to a specific job.
Uses MongoDB since message threads are variable-length and high-write-frequency.
"""

from flask import Blueprint, request, jsonify, current_app, g
from pymongo import MongoClient, ASCENDING
from models.sql_models import Job, Proposal
from utils.decorators import login_required
import datetime

messages_bp = Blueprint("messages", __name__)

_mongo_client = None

from pymongo.errors import PyMongoError
def get_mongo_db():
    """Returns the MongoDB database handle, reusing one client connection."""
    global _mongo_client
    if _mongo_client is None:
        _mongo_client = MongoClient(
            current_app.config["MONGO_URI"],
            serverSelectionTimeoutMS=5000
        )
    return _mongo_client[current_app.config["MONGO_DB_NAME"]]

def get_authorized_counterpart(job, user_id):
    """Returns the ID of the other party if the user is authorized, else None."""
    if job.client_id == user_id:
        # Find the accepted proposal
        accepted = Proposal.query.filter_by(job_id=job.id, status="accepted").first()
        return accepted.freelancer_id if accepted else None
    else:
        # Check if the user is the accepted freelancer
        accepted = Proposal.query.filter_by(job_id=job.id, freelancer_id=user_id, status="accepted").first()
        return job.client_id if accepted else None

@messages_bp.route("/<int:job_id>", methods=["GET"])
@login_required()
def get_messages(job_id):
    """Get the full message thread for a job."""
    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    user_id = g.user.id
    if not get_authorized_counterpart(job, user_id):
        return jsonify({"error": "Not authorized to view this conversation"}), 403

    db = get_mongo_db()
    messages = list(
        db.messages.find({"job_id": job_id}, {"_id": 0}).sort("timestamp", ASCENDING)
    )

    return jsonify({"messages": messages}), 200


@messages_bp.route("", methods=["POST"])
@login_required()
def send_message():
    """Send a message tied to a job, from the logged-in user to a specific receiver."""
    data = request.get_json()
    job_id = data.get("job_id")
    text = data.get("text", "").strip()

    if not all([job_id, text]):
        return jsonify({"error": "job_id and text are required"}), 400
    
    if len(text) > 2000:
        return jsonify({"error": "Message is too long (max 2000 characters)"}), 400

    job = Job.query.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    user_id = g.user.id
    counterpart_id = get_authorized_counterpart(job, user_id)
    if not counterpart_id:
        return jsonify({"error": "Not authorized to send a message on this job"}), 403

    message_doc = {
        "job_id": job_id,
        "sender_id": user_id,
        "receiver_id": counterpart_id,
        "text": text,
        "timestamp": datetime.datetime.now(datetime.timezone.utc).isoformat(),
    }

    db = get_mongo_db()
    db.messages.insert_one(message_doc)

    message_doc.pop("_id", None)
    return jsonify({"message": "Sent", "data": message_doc}), 201


@messages_bp.route("/conversations", methods=["GET"])
@login_required()
def get_conversations():
    """Get a list of distinct job_ids the logged-in user has messages in."""
    user_id = g.user.id
    db = get_mongo_db()

    job_ids = db.messages.distinct(
        "job_id",
        {"$or": [{"sender_id": user_id}, {"receiver_id": user_id}]},
    )

    return jsonify({"job_ids": job_ids}), 200
