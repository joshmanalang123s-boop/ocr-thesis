import os
import re
import sys
import urllib.request


def download_model(model_path):
    """Download the license plate detection YOLOv8 weights from Hugging Face if not present."""
    model_dir = os.path.dirname(model_path)
    if not os.path.exists(model_dir):
        os.makedirs(model_dir, exist_ok=True)

    if not os.path.exists(model_path):
        url = "https://huggingface.co/Koushim/yolov8-license-plate-detection/resolve/main/best.pt"
        print(f"[{model_path}] Downloading license plate model weights...", file=sys.stderr)
        try:
            urllib.request.urlretrieve(url, model_path)
            print(f"[{model_path}] Download complete.", file=sys.stderr)
        except Exception as e:
            print(f"[{model_path}] Error downloading model: {e}", file=sys.stderr)
            raise


def sanitize_plate_number(text):
    """Sanitize detected license plate text (keep alphanumeric, space, and hyphen, convert to uppercase)."""
    text = text.upper()

    # Filter out common temporary/slogan/regional words
    blacklist = {
        "REGISTERED", "REGISTRATION", "TEMPORARY", "CONDUCTION", "STICKER",
        "DEALER", "MV", "FILE", "MVFILE", "FILENO", "NO", "FRONT", "REAR",
        "MATATAG", "PILIPINAS", "BAGONG", "NCR", "REGION", "PHILIPPINES"
    }

    words = text.split()
    filtered_words = []
    for word in words:
        # Strip non-alphanumeric to check against blacklist (e.g. "NO." -> "NO")
        cleaned_word = re.sub(r'[^A-Z0-9]', '', word)
        if cleaned_word not in blacklist:
            filtered_words.append(word)

    text = " ".join(filtered_words)

    cleaned = re.sub(r'[^a-zA-Z0-9\s-]', '', text)
    cleaned = re.sub(r'[\s-]+', ' ', cleaned)
    return cleaned.strip().upper()


def score_text(text):
    """Score a text candidate based on how well it fits a license plate format."""
    s = re.sub(r'[^A-Z0-9]', '', text.upper())
    if not s:
        return 0.0

    has_letters = any(c.isalpha() for c in s)
    has_numbers = any(c.isdigit() for c in s)

    score = 1.0
    length = len(s)

    if 5 <= length <= 8:
        score += 2.0
    elif length == 3 or length == 4:
        score += 1.0
    else:
        score -= 2.0

    if has_letters and has_numbers:
        score += 3.0
    elif has_letters:
        score += 0.5
    elif has_numbers:
        score += 0.5

    return score


def get_merged_lines(paddle_result, img_height):
    """Group PaddleOCR text blocks horizontally and merge them left-to-right.

    paddle_result format: [ [ [[x1,y1],[x2,y2],[x3,y3],[x4,y4]], ('text', conf) ], ... ]

    Text blocks under 45% of the tallest block's height are dropped first
    (filters small background noise/region labels like "NCR" that would
    otherwise pollute the merged line or its length-based score).
    """
    if not paddle_result:
        return []

    parsed_items = []
    max_height = 0.0

    for line in paddle_result:
        if not line or len(line) < 2:
            continue
        bbox = line[0]
        val = line[1][0]
        conf = line[1][1]

        xs = [pt[0] for pt in bbox]
        ys = [pt[1] for pt in bbox]
        x_min = min(xs)
        y_min, y_max = min(ys), max(ys)
        y_center = (y_min + y_max) / 2
        height = y_max - y_min

        if height > max_height:
            max_height = height
        parsed_items.append({
            'text': val,
            'x_min': x_min,
            'y_center': y_center,
            'height': height,
            'conf': conf,
        })

    height_threshold = max_height * 0.45
    filtered_items = [item for item in parsed_items if item['height'] >= height_threshold]

    if not filtered_items:
        return []

    lines = []
    y_threshold = max(40, img_height * 0.20)

    for item in filtered_items:
        added = False
        for group in lines:
            if abs(group['y_center'] - item['y_center']) < y_threshold:
                group['items'].append(item)
                group['y_center'] = (group['y_center'] * (len(group['items']) - 1) + item['y_center']) / len(group['items'])
                added = True
                break
        if not added:
            lines.append({
                'y_center': item['y_center'],
                'items': [item],
            })

    merged_texts = []
    for group in lines:
        sorted_items = sorted(group['items'], key=lambda x: x['x_min'])
        merged_text = " ".join([item['text'] for item in sorted_items])
        avg_conf = sum([item['conf'] for item in sorted_items]) / len(sorted_items)
        merged_texts.append((merged_text, avg_conf))

    return merged_texts
