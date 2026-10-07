"""
WorkBridge - Backend Configuration
Loads DB credentials and app settings from environment variables (.env file).
"""

import os
from dotenv import load_dotenv
from sqlalchemy.engine import URL

load_dotenv()

class Config:
    FLASK_ENV = os.environ.get("FLASK_ENV", "development")

    # --- Auth ---
    SECRET_KEY = os.environ.get("SECRET_KEY")
    if FLASK_ENV != "development":
        if not SECRET_KEY or len(SECRET_KEY) < 32:
            raise ValueError("A strong SECRET_KEY (>= 32 chars) is required outside development. Generate with secrets.token_urlsafe(48)")
    else:
        if not SECRET_KEY:
            SECRET_KEY = "dev-secret-change-this-in-production"
    
    JWT_EXPIRY_HOURS = 24

    # --- MySQL (via SQLAlchemy) ---
    MYSQL_USER = os.environ.get("MYSQL_USER", "root")
    MYSQL_PASSWORD = os.environ.get("MYSQL_PASSWORD", "")
    MYSQL_HOST = os.environ.get("MYSQL_HOST", "localhost")
    MYSQL_PORT = os.environ.get("MYSQL_PORT", "3306")
    MYSQL_DB = os.environ.get("MYSQL_DB", "workbridge")

    if FLASK_ENV != "development" and not MYSQL_PASSWORD:
        raise ValueError("MYSQL_PASSWORD is required outside development.")

    SQLALCHEMY_DATABASE_URI = URL.create(
        drivername="mysql+pymysql",
        username=MYSQL_USER,
        password=MYSQL_PASSWORD,
        host=MYSQL_HOST,
        port=MYSQL_PORT,
        database=MYSQL_DB
    ).render_as_string(hide_password=False)
    SQLALCHEMY_TRACK_MODIFICATIONS = False

    # --- MongoDB ---
    MONGO_URI = os.environ.get("MONGO_URI", "mongodb://localhost:27017/")
    MONGO_DB_NAME = os.environ.get("MONGO_DB_NAME", "workbridge")
