import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from plate_pipeline import sanitize_plate_number, score_text, is_slogan_text, fix_green_plate_text


def test_sanitize_plate_number_strips_symbols_and_uppercases():
    assert sanitize_plate_number("abc-123!!") == "ABC 123"


def test_sanitize_plate_number_collapses_whitespace_and_dashes():
    assert sanitize_plate_number("ab   --  12") == "AB 12"


def test_sanitize_plate_number_removes_temporary_plate_words():
    assert sanitize_plate_number("BAGONG PILIPINAS NDP 9668") == "NDP 9668"
    assert sanitize_plate_number("REGISTERED NCR REGION") == ""
    assert sanitize_plate_number("MATATAG NA REPUBLIKA") == ""


def test_green_plate_obelisk_artifact_correction():
    # Rizal monument obelisk read as '1' or '4' in green plate series
    assert fix_green_plate_text("YEC1652", is_green=True) == "YEC 652"
    assert fix_green_plate_text("YEC4652", is_green=True) == "YEC 652"
    assert fix_green_plate_text("YEC652", is_green=True) == "YEC 652"
    assert fix_green_plate_text("EOJ69", is_green=True) == "EOJ 69"


def test_is_slogan_text_identifies_plate_slogans():
    assert is_slogan_text("MATATAG") is True
    assert is_slogan_text("REPUBLIKA") is True
    assert is_slogan_text("PU8LIK") is True
    assert is_slogan_text("EPUBLIK") is True
    assert is_slogan_text("YEC 652") is False
    assert is_slogan_text("NDP 9668") is False


def test_score_text_rewards_letter_number_mix_in_plate_length_range():
    assert score_text("ABC1234") > score_text("1234567890123")


def test_score_text_empty_or_symbols_only_is_zero():
    assert score_text("") == 0.0
    assert score_text("###") == 0.0
    assert score_text("MATATAG") == 0.0


def test_six_and_seven_digit_plate_preservation():
    # 7-character standard (3 letters + 4 digits) must never be stripped
    assert sanitize_plate_number("NBC1234") == "NBC 1234"
    assert sanitize_plate_number("NEY1228") == "NEY 1228"
    assert sanitize_plate_number("ABC1234") == "ABC 1234"

    # 6-character standard (3 letters + 3 digits)
    assert sanitize_plate_number("NBC123") == "NBC 123"
    assert sanitize_plate_number("YEC652") == "YEC 652"

    # Even if crop happens to have green background, standard 7-digit plates are preserved when no slogan
    assert sanitize_plate_number("NBC1234", is_green=False) == "NBC 1234"
    assert sanitize_plate_number("NEY1228", is_green=False) == "NEY 1228"

