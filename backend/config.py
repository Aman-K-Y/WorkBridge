"""
WorkBridge - Backend Configuration
Loads DB credentials and app settings from environment variables (.env file).
"""

import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    # --- MySQL (via SQLAlchemy) ---
    MYSQL_USER = os.environ.get("MYSQL_USER", "root")
    MYSQL_PASSWORD = os.environ.get("MYSQL_PASSWORD", "")
    MYSQL_HOST = os.environ.get("MYSQL_HOST", "localhost")
    MYSQL_PORT = os.environ.get("MYSQL_PORT", "3306")
    MYSQL_DB = os.environ.get("MYSQL_DB", "workbridge")

    SQLALCHEMY_DATABASE_URI = (
        f"mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DB}"
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False

    # --- MongoDB ---
    MONGO_URI = os.environ.get("MONGO_URI", "mongodb://localhost:27017/")
    MONGO_DB_NAME = os.environ.get("MONGO_DB_NAME", "workbridge")

    # --- Auth ---
    SECRET_KEY = os.environ.get("SECRET_KEY", "dev-secret-change-this-in-production")
    JWT_EXPIRY_HOURS = 24
