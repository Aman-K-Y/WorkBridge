"""
WorkBridge - Auth Helpers
Password hashing (bcrypt) and JWT token creation/verification.
"""

import bcrypt
import jwt
import datetime


def hash_password(plain_password: str) -> str:
    """Hash a plain-text password for storing in the database."""
    hashed = bcrypt.hashpw(plain_password.encode("utf-8"), bcrypt.gensalt())
    return hashed.decode("utf-8")


def check_password(plain_password: str, hashed_password: str) -> bool:
    """Verify a plain-text password against the stored hash."""
    return bcrypt.checkpw(
        plain_password.encode("utf-8"), hashed_password.encode("utf-8")
    )


def generate_token(user_id: int, role: str, secret_key: str, expiry_hours: int = 24) -> str:
    """Generate a JWT token for an authenticated user."""
    payload = {
        "user_id": user_id,
        "role": role,
        "exp": datetime.datetime.utcnow() + datetime.timedelta(hours=expiry_hours),
        "iat": datetime.datetime.utcnow(),
    }
    return jwt.encode(payload, secret_key, algorithm="HS256")


def decode_token(token: str, secret_key: str):
    """Decode and validate a JWT token. Returns payload dict or None if invalid/expired."""
    try:
        return jwt.decode(token, secret_key, algorithms=["HS256"])
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None
