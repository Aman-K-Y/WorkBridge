from flask import request
from decimal import Decimal, InvalidOperation

def get_safe_json():
    """Returns the JSON body safely, or an empty dict if none/invalid."""
    return request.get_json(silent=True) or {}

def validate_decimal(value, name="value", min_val=0):
    """Parses a value into a positive Decimal."""
    try:
        if value is None:
            raise ValueError(f"{name} is required")
        val = Decimal(str(value))
        if val <= min_val:
            raise ValueError(f"{name} must be greater than {min_val}")
        return val
    except InvalidOperation:
        raise ValueError(f"{name} must be a valid number")

def validate_string(value, name="value", max_length=None):
    """Ensures a string is valid and optionally bounded."""
    if value is None:
        raise ValueError(f"{name} is required")
    val = str(value).strip()
    if max_length and len(val) > max_length:
        raise ValueError(f"{name} cannot exceed {max_length} characters")
    return val

def normalize_skills(skills_str):
    """Takes a comma-separated string of skills, trims, drops empties, and de-duplicates."""
    if not skills_str:
        return ""
    # Split, strip, lowercase for deduplication, but keep original case?
    # Actually, the instructions say 'Python,react' and 'python, React' differ. 
    # We should normalize them. Let's make them title case or lower case.
    skills = [s.strip() for s in skills_str.split(",") if s.strip()]
    unique_skills = []
    seen = set()
    for s in skills:
        lower_s = s.lower()
        if lower_s not in seen:
            seen.add(lower_s)
            unique_skills.append(s) # keep original casing of first occurrence, or just use lower
    return ", ".join(unique_skills)
