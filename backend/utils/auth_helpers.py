"""
WorkBridge - Auth Helpers
Password hashing (bcrypt) and JWT token creation/verification.
"""

import bcrypt
import jwt
import datetime

def hash_password(plain_password: str) -> str:
    """Hash a plain-text password for storing in the database."""
    if len(plain_password.encode("utf-8")) > 72:
        raise ValueError("Password is too long (max 72 bytes)")
    hashed = bcrypt.hashpw(plain_password.encode("utf-8"), bcrypt.gensalt())
    return hashed.decode("utf-8")


def check_password(plain_password: str, hashed_password: str) -> bool:
    """Verify a plain-text password against the stored hash."""
    if len(plain_password.encode("utf-8")) > 72:
        return False
    return bcrypt.checkpw(
        plain_password.encode("utf-8"), hashed_password.encode("utf-8")
    )


def generate_token(user_id: int, role: str, secret_key: str, expiry_hours: int = 24) -> str:
    """Generate a JWT token for an authenticated user."""
    now = datetime.datetime.now(datetime.timezone.utc)
    payload = {
        "sub": user_id,
        "user_id": user_id,  # Keeping for backwards compatibility if needed elsewhere
        "role": role,
        "exp": now + datetime.timedelta(hours=1), # Changed to 1 hour token
        "iat": now,
    }
    return jwt.encode(payload, secret_key, algorithm="HS256")


def decode_token(token: str, secret_key: str):
    """Decode and validate a JWT token. Returns payload dict or None if invalid/expired."""
    try:
        payload = jwt.decode(token, secret_key, algorithms=["HS256"])
        # Standardize on using 'user_id' for routes to keep them working without massive rewrites
        if 'sub' in payload and 'user_id' not in payload:
            payload['user_id'] = payload['sub']
        return payload
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None
