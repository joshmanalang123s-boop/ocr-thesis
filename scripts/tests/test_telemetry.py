import os
import sys
import time

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from telemetry import Telemetry


def test_phase_records_elapsed_ms():
    telem = Telemetry()
    with telem.phase("yolo"):
        time.sleep(0.01)
    summary = telem.summary()
    assert "yolo_ms" in summary
    assert summary["yolo_ms"] >= 10.0


def test_summary_includes_total_ms():
    telem = Telemetry()
    with telem.phase("yolo"):
        time.sleep(0.005)
    with telem.phase("paddle"):
        time.sleep(0.005)
    summary = telem.summary()
    assert set(summary.keys()) == {"yolo_ms", "paddle_ms", "total_ms"}
    # total wall-clock must cover at least the two phases (small tolerance for perf_counter jitter)
    assert summary["total_ms"] >= summary["yolo_ms"] + summary["paddle_ms"] - 2


def test_multiple_distinct_phases_tracked_independently():
    telem = Telemetry()
    with telem.phase("a"):
        time.sleep(0.001)
    with telem.phase("b"):
        time.sleep(0.001)
    summary = telem.summary()
    assert summary["a_ms"] > 0
    assert summary["b_ms"] > 0


def test_repeated_phase_name_accumulates():
    telem = Telemetry()
    with telem.phase("paddle"):
        time.sleep(0.005)
    with telem.phase("paddle"):
        time.sleep(0.005)
    summary = telem.summary()
    assert summary["paddle_ms"] >= 10.0
