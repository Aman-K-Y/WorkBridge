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
from routes.users import users_bp


def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)

    origins = app.config.get("CORS_ORIGINS", "").split(",")
    origins = [o.strip() for o in origins if o.strip()]
    if origins:
        CORS(app, resources={r"/api/*": {"origins": origins}})

    db.init_app(app)

    # Initialize Flask-Migrate
    from flask_migrate import Migrate
    Migrate(app, db)

    # Initialize Flask-Limiter
    from routes.auth import limiter
    limiter.init_app(app)

    # Register route blueprints
    app.register_blueprint(auth_bp, url_prefix="/api/auth")
    app.register_blueprint(jobs_bp, url_prefix="/api/jobs")
    app.register_blueprint(proposals_bp, url_prefix="/api/proposals")
    app.register_blueprint(messages_bp, url_prefix="/api/messages")
    app.register_blueprint(reviews_bp, url_prefix="/api/reviews")
    app.register_blueprint(users_bp, url_prefix="/api/users")
    # Core backend routes are now complete.

    # Error Handlers
    from sqlalchemy.exc import IntegrityError

    @app.errorhandler(400)
    def bad_request(e):
        return jsonify(error=str(e.description) if e.description else "Bad Request"), 400

    @app.errorhandler(404)
    def not_found(e):
        return jsonify(error="Not Found"), 404

    @app.errorhandler(405)
    def method_not_allowed(e):
        return jsonify(error="Method Not Allowed"), 405

    @app.errorhandler(500)
    def internal_server_error(e):
        return jsonify(error="Internal Server Error"), 500

    @app.errorhandler(IntegrityError)
    def handle_integrity_error(e):
        db.session.rollback()
        return jsonify(error="Database conflict or duplicate entry"), 409

    @app.route("/")
    def health_check():
        return jsonify({"status": "WorkBridge API is running"})

    return app

if __name__ == "__main__":
    app = create_app()
    import os
    is_debug = os.environ.get("FLASK_ENV") != "production"
    app.run(debug=is_debug, port=5000)
