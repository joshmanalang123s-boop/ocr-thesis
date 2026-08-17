import time
from collections import OrderedDict


class Telemetry:
    """Tracks named timing phases (e.g. yolo, paddle) plus overall wall-clock total.

    Calling phase() more than once with the same name accumulates elapsed time
    under that name, since a single request can call the same phase (e.g.
    PaddleOCR) more than once (crop OCR, then full-image OCR fallback).
    """

    def __init__(self):
        self._phases = OrderedDict()
        self._start = time.perf_counter()

    def phase(self, name):
        return _Phase(self, name)

    def _record(self, name, elapsed_ms):
        self._phases[name] = self._phases.get(name, 0.0) + elapsed_ms

    def summary(self):
        total_ms = round((time.perf_counter() - self._start) * 1000, 2)
        result = {f"{name}_ms": round(ms, 2) for name, ms in self._phases.items()}
        result["total_ms"] = total_ms
        return result

    def print_report(self, prefix="[telemetry]"):
        parts = [f"{k}={v}" for k, v in self.summary().items()]
        print(f"{prefix} " + " ".join(parts))


class _Phase:
    def __init__(self, telemetry, name):
        self._telemetry = telemetry
        self._name = name
        self._start = None

    def __enter__(self):
        self._start = time.perf_counter()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        elapsed_ms = (time.perf_counter() - self._start) * 1000
        self._telemetry._record(self._name, elapsed_ms)
        return False
