import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from plate_pipeline import sanitize_plate_number, score_text, get_merged_lines


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


def test_get_merged_lines_groups_same_row_left_to_right():
    # Two blocks on the same row, given out of left-to-right order.
    # rec_boxes rows are [x1, y1, x2, y2] (PaddleOCR 3.x OCRResult schema).
    rec_texts = ["456", "ABC"]
    rec_scores = [0.9, 0.8]
    rec_boxes = [[50, 10, 90, 30], [0, 10, 40, 30]]
    merged = get_merged_lines(rec_texts, rec_scores, rec_boxes, img_height=40)
    assert len(merged) == 1
    text, conf = merged[0]
    assert text == "ABC 456"
    assert round(conf, 2) == 0.85


def test_get_merged_lines_separates_distinct_rows():
    rec_texts = ["TOP", "BOTTOM"]
    rec_scores = [0.9, 0.9]
    rec_boxes = [[0, 0, 40, 20], [0, 100, 40, 120]]
    merged = get_merged_lines(rec_texts, rec_scores, rec_boxes, img_height=140)
    assert len(merged) == 2


def test_get_merged_lines_filters_small_noise_by_height():
    rec_texts = ["NDP 9668", "NCR"]
    rec_scores = [0.9, 0.5]
    rec_boxes = [[0, 0, 100, 30], [0, 100, 30, 108]]  # heights 30 and 8 (<45% of 30)
    merged = get_merged_lines(rec_texts, rec_scores, rec_boxes, img_height=140)
    assert len(merged) == 1
    assert merged[0][0] == "NDP 9668"


def test_get_merged_lines_empty_input_returns_empty():
    assert get_merged_lines([], [], [], img_height=140) == []
