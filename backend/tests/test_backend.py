import os
import sys
from decimal import Decimal
import pytest

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from utils.auth_helpers import hash_password, check_password, generate_token, decode_token
from utils.validation import (
    validate_string,
    validate_decimal,
    normalize_skills,
)
from models.sql_models import User


def test_auth_hash_and_check_password():
    raw = "SecureP@ssw0rd123!"
    hashed = hash_password(raw)
    assert hashed != raw
    assert check_password(raw, hashed) is True
    assert check_password("WrongPassword!", hashed) is False


def test_jwt_token_generation_and_decoding():
    secret = "my-test-secret-key-12345"
    token = generate_token(user_id=42, role="freelancer", secret_key=secret, expiry_hours=2)
    assert isinstance(token, str)

    payload = decode_token(token, secret_key=secret)
    assert payload is not None
    assert payload.get("sub") == 42
    assert payload.get("role") == "freelancer"


def test_jwt_invalid_secret_fails():
    secret = "valid-secret"
    token = generate_token(user_id=1, role="client", secret_key=secret)
    assert decode_token(token, "wrong-secret") is None


def test_validate_string_success():
    assert validate_string("  Hello World  ", "title", max_length=50) == "Hello World"
    assert validate_string("Sample", "title") == "Sample"


def test_validate_string_errors():
    with pytest.raises(ValueError, match="required"):
        validate_string(None, "name")

    with pytest.raises(ValueError, match="cannot exceed 5 characters"):
        validate_string("TooLongString", "code", max_length=5)


def test_validate_decimal_success():
    assert validate_decimal(100.5) == Decimal("100.5")
    assert validate_decimal("250.75") == Decimal("250.75")


def test_validate_decimal_errors():
    with pytest.raises(ValueError, match="required"):
        validate_decimal(None, "budget")

    with pytest.raises(ValueError, match="must be greater than 0"):
        validate_decimal(0, "budget", min_val=0)

    with pytest.raises(ValueError, match="must be a valid number"):
        validate_decimal("invalid_number", "budget")


def test_normalize_skills():
    assert normalize_skills("Python, React, Flask, python") == "Python, React, Flask"
    assert normalize_skills("") == ""
    assert normalize_skills("   ") == ""


def test_client_completion_score():
    client = User(
        user_id=1,
        name="Alice Client",
        email="alice@example.com",
        password_hash="hash",
        role="client",
        status=None,
        phone_number=None,
    )
    info = client.get_completion_info()
    assert info["percentage"] == 50
    assert info["is_complete"] is False
    assert len(info["missing_fields"]) == 2

    client.status = "professional"
    client.phone_number = "+91 9876543210"
    info_full = client.get_completion_info()
    assert info_full["percentage"] == 100
    assert info_full["is_complete"] is True


def test_freelancer_completion_score():
    freelancer = User(
        user_id=2,
        name="Bob Freelancer",
        email="bob@example.com",
        password_hash="hash",
        role="freelancer",
        status=None,
        higher_qualification=None,
        skills=None,
        phone_number=None,
        resume_url=None,
    )
    info = freelancer.get_completion_info()
    assert info["percentage"] < 100
    assert info["is_complete"] is False

    freelancer.status = "professional"
    freelancer.higher_qualification = "B.Tech Computer Science"
    freelancer.skills = "Python, Flask"
    freelancer.phone_number = "+91 1234567890"
    freelancer.resume_url = "uploads/resumes/resume.pdf"
    freelancer.avatar_url = "uploads/avatars/avatar.jpg"
    info_full = freelancer.get_completion_info()
    assert info_full["percentage"] == 100
    assert info_full["is_complete"] is True

    d = freelancer.to_dict()
    assert d["user_id"] == 2
    assert d["avatar_url"] == "uploads/avatars/avatar.jpg"
    assert d["completion"]["percentage"] == 100
