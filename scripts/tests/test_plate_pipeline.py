import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from plate_pipeline import sanitize_plate_number, score_text


def test_sanitize_plate_number_strips_symbols_and_uppercases():
    assert sanitize_plate_number("abc-123!!") == "ABC 123"


def test_sanitize_plate_number_collapses_whitespace_and_dashes():
    assert sanitize_plate_number("ab   --  12") == "AB 12"


def test_sanitize_plate_number_removes_temporary_plate_words():
    assert sanitize_plate_number("BAGONG PILIPINAS NDP 9668") == "NDP 9668"
    assert sanitize_plate_number("REGISTERED NCR REGION") == ""


def test_score_text_rewards_letter_number_mix_in_plate_length_range():
    assert score_text("ABC1234") > score_text("1234567890123")


def test_score_text_empty_or_symbols_only_is_zero():
    assert score_text("") == 0.0
    assert score_text("###") == 0.0
