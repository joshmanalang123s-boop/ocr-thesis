@extends('layouts.app')

@section('title', 'OCR Results - Extracted Text')

@section('additional-styles')
<style>
    .result-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .result-image {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .result-image h3 {
        color: #333;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .result-image img {
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .result-text {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .result-text h3 {
        color: #333;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .text-editor {
        flex: 1;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        color: #333;
        line-height: 1.6;
        resize: vertical;
        min-height: 300px;
        margin-bottom: 1rem;
    }

    .text-editor:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .text-controls {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .text-controls .btn {
        flex: 1;
        min-width: 150px;
    }

    .stats {
        background: #f5f7ff;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #666;
        margin-top: 0.25rem;
    }

    .filename-info {
        background: #e8f5e9;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: #2e7d32;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .action-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #eee;
    }

    .btn-group {
        display: flex;
        gap: 0.5rem;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .result-container {
            grid-template-columns: 1fr;
        }

        .text-controls {
            flex-direction: column;
        }

        .text-controls .btn {
            width: 100%;
        }

        .action-buttons {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="card">
    <div class="filename-info">
        ✓ Successfully extracted text from: <strong>{{ $fileName }}</strong>
    </div>

    <div class="result-container">
        <!-- Original Image -->
        <div class="result-image">
            <h3>📸 Original Image</h3>
            <img src="{{ $imagePath }}" alt="Original Image">
        </div>

        <!-- Extracted Text -->
        <div class="result-text">
            <h3>📝 Extracted Text</h3>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value" id="charCount">0</div>
                    <div class="stat-label">Characters</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="wordCount">0</div>
                    <div class="stat-label">Words</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="lineCount">0</div>
                    <div class="stat-label">Lines</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Confidence</div>
                </div>
            </div>

            <textarea id="extractedText" class="text-editor" readonly>{{ $extractedText }}</textarea>

            <div class="text-controls">
                <button type="button" class="btn btn-secondary" onclick="copyToClipboard()">
                    <span class="btn-icon">📋 Copy Text</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="downloadText()">
                    <span class="btn-icon">📥 Download</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="enableEditing()">
                    <span class="btn-icon">✏️ Edit Text</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('ocr.index') }}" class="btn btn-primary">
            🔄 Extract Another Image
        </a>
        <button type="button" class="btn btn-secondary" onclick="printText()">
            🖨️ Print Results
        </button>
    </div>
</div>

<!-- Additional Features Section -->
<div style="margin-top: 3rem;">
    <h2 style="color: #333; margin-bottom: 2rem;">What's Next?</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
        <div class="card" style="padding: 1.5rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
            <h4 style="color: #333; margin-bottom: 0.5rem;">Advanced Options</h4>
            <p style="color: #666; font-size: 0.9rem;">
                Configure language settings, processing modes, and output formats
            </p>
        </div>
        <div class="card" style="padding: 1.5rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🗂️</div>
            <h4 style="color: #333; margin-bottom: 0.5rem;">Save to History</h4>
            <p style="color: #666; font-size: 0.9rem;">
                Your extractions are automatically saved for quick access
            </p>
        </div>
        <div class="card" style="padding: 1.5rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📤</div>
            <h4 style="color: #333; margin-bottom: 0.5rem;">Export Formats</h4>
            <p style="color: #666; font-size: 0.9rem;">
                Export as TXT, PDF, DOCX, or copy directly to clipboard
            </p>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});

function updateStats() {
    const text = document.getElementById('extractedText').value;

    // Character count
    document.getElementById('charCount').textContent = text.length;

    // Word count
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    document.getElementById('wordCount').textContent = words;

    // Line count
    const lines = text.trim().split('\n').length;
    document.getElementById('lineCount').textContent = lines;
}

function copyToClipboard() {
    const text = document.getElementById('extractedText');
    text.select();
    document.execCommand('copy');

    // Show feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="btn-icon">✓ Copied!</span>';
    setTimeout(() => {
        btn.innerHTML = originalText;
    }, 2000);
}

function downloadText() {
    const text = document.getElementById('extractedText').value;
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', 'extracted-text.txt');
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function enableEditing() {
    const textarea = document.getElementById('extractedText');
    textarea.removeAttribute('readonly');
    textarea.focus();

    const btn = event.target.closest('button');
    btn.innerHTML = '<span class="btn-icon">✓ Editing Enabled</span>';
    btn.style.background = '#4CAF50';
}

function printText() {
    const text = document.getElementById('extractedText').value;
    const printWindow = window.open('', '', 'height=400,width=800');
    printWindow.document.write('<pre style="font-family: monospace; line-height: 1.6;">' +
        text.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>
@endsection
