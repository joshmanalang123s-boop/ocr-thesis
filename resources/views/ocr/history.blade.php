@extends('layouts.app')

@section('title', 'Extraction History')

@section('additional-styles')
<style>
    .history-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .history-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
    }

    .history-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .history-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .history-content {
        padding: 1rem;
    }

    .history-date {
        font-size: 0.85rem;
        color: #999;
        margin-bottom: 0.5rem;
    }

    .history-text {
        font-size: 0.9rem;
        color: #666;
        line-height: 1.4;
        margin-bottom: 1rem;
        max-height: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .history-actions {
        display: flex;
        gap: 0.5rem;
    }

    .history-actions button,
    .history-actions a {
        flex: 1;
        padding: 0.5rem;
        border: none;
        background: #f0f0f0;
        color: #333;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        text-decoration: none;
        text-align: center;
        transition: background 0.3s;
    }

    .history-actions button:hover,
    .history-actions a:hover {
        background: #667eea;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #999;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        color: #666;
        margin-bottom: 1rem;
    }

    .filter-controls {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .filter-controls input,
    .filter-controls select {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .filter-controls button {
        padding: 0.75rem 1.5rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .history-wrapper {
            grid-template-columns: 1fr;
        }

        .filter-controls {
            flex-direction: column;
        }

        .filter-controls input,
        .filter-controls select {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="card">
    <h1 style="color: #333; margin-bottom: 2rem; font-size: 2rem;">📚 Extraction History</h1>

    <div class="filter-controls">
        <input type="text" id="searchInput" placeholder="Search extractions...">
        <select id="sortSelect">
            <option value="recent">Most Recent</option>
            <option value="oldest">Oldest First</option>
            <option value="name">By Name</option>
        </select>
        <button onclick="clearHistory()">🗑️ Clear History</button>
    </div>

    <div class="history-wrapper" id="historyContainer">
        <!-- History items will be displayed here -->
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No Extractions Yet</h3>
            <p>Start by uploading an image to see your extraction history here.</p>
            <a href="{{ route('ocr.index') }}" class="btn btn-primary" style="margin-top: 1rem;">
                🔄 Go to OCR
            </a>
        </div>
    </div>
</div>

<!-- Sample History Items (Placeholder) -->
<div style="margin-top: 3rem;">
    <h2 style="color: #333; margin-bottom: 2rem;">💡 Tips</h2>
    <div style="display: grid; gap: 1rem;">
        <div class="card" style="padding: 1.5rem; border-left: 4px solid #667eea;">
            <h4 style="color: #333; margin-bottom: 0.5rem;">📸 Best Results</h4>
            <p style="color: #666; font-size: 0.9rem;">
                Use clear, well-lit images with good contrast for better OCR accuracy
            </p>
        </div>
        <div class="card" style="padding: 1.5rem; border-left: 4px solid #764ba2;">
            <h4 style="color: #333; margin-bottom: 0.5rem;">🔤 Language Support</h4>
            <p style="color: #666; font-size: 0.9rem;">
                OCR supports multiple languages. Configure in settings for better accuracy
            </p>
        </div>
        <div class="card" style="padding: 1.5rem; border-left: 4px solid #7cb342;">
            <h4 style="color: #333; margin-bottom: 0.5rem;">📥 Export Options</h4>
            <p style="color: #666; font-size: 0.9rem;">
                Download extractions in various formats including TXT, PDF, and DOCX
            </p>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadHistory();
});

function loadHistory() {
    // This is a placeholder function
    // In a real implementation, this would fetch from the server
    const container = document.getElementById('historyContainer');

    // Check if there's any saved history in localStorage
    const history = localStorage.getItem('ocrHistory');

    if (!history) {
        // Show empty state
        return;
    }

    const items = JSON.parse(history);
    container.innerHTML = '';

    items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'history-card';
        card.innerHTML = `
            <div class="history-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
            <div class="history-content">
                <div class="history-date">${formatDate(item.date)}</div>
                <div class="history-text">${escapeHtml(item.text)}</div>
                <div class="history-actions">
                    <button onclick="copyHistoryItem('${escapeJs(item.text)}')">Copy</button>
                    <button onclick="deleteHistoryItem('${item.id}')">Delete</button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function copyHistoryItem(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

function deleteHistoryItem(id) {
    if (confirm('Delete this item?')) {
        let history = JSON.parse(localStorage.getItem('ocrHistory') || '[]');
        history = history.filter(item => item.id !== id);
        localStorage.setItem('ocrHistory', JSON.stringify(history));
        loadHistory();
    }
}

function clearHistory() {
    if (confirm('Clear all history? This cannot be undone.')) {
        localStorage.removeItem('ocrHistory');
        loadHistory();
    }
}

document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.history-card');
    cards.forEach(card => {
        const text = card.querySelector('.history-text').textContent.toLowerCase();
        card.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

document.getElementById('sortSelect')?.addEventListener('change', function(e) {
    // Implement sorting logic
    loadHistory();
});
</script>
@endsection
