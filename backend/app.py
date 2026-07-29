"""
WorkBridge - Backend Entry Point
Run with: python app.py
"""

from flask import Flask, jsonify
from flask_cors import CORS
from config import Config
from models.sql_models import db
from routes.auth import auth_bp
from routes.jobs import jobs_bp
from routes.proposals import proposals_bp
from routes.messages import messages_bp
from routes.reviews import reviews_bp


def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)

    CORS(app)  # allows the PHP frontend to call this API from a different port/origin

    db.init_app(app)

    # Register route blueprints
    app.register_blueprint(auth_bp, url_prefix="/api/auth")
    app.register_blueprint(jobs_bp, url_prefix="/api/jobs")
    app.register_blueprint(proposals_bp, url_prefix="/api/proposals")
    app.register_blueprint(messages_bp, url_prefix="/api/messages")
    app.register_blueprint(reviews_bp, url_prefix="/api/reviews")
    # Core backend routes are now complete.

    @app.route("/")
    def health_check():
        return jsonify({"status": "WorkBridge API is running"})

    return app


if __name__ == "__main__":
    app = create_app()
    app.run(debug=True, port=5000)
