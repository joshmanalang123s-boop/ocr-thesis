/**
 * OCR Application JavaScript
 * Handles client-side OCR operations
 */

class OCRManager {
    constructor() {
        this.uploadArea = null;
        this.imageInput = null;
        this.imagePreview = null;
        this.currentImage = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Upload area
        this.uploadArea = document.getElementById('uploadArea');
        this.imageInput = document.getElementById('imageInput');
        this.imagePreview = document.getElementById('imagePreview');

        if (this.uploadArea) {
            this.uploadArea.addEventListener('click', () => this.imageInput.click());
            this.uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
            this.uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            this.uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
        }

        if (this.imageInput) {
            this.imageInput.addEventListener('change', () => this.handleImageSelect());
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        this.uploadArea.classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        this.uploadArea.classList.remove('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        this.uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.imageInput.files = files;
            this.handleImageSelect();
        }
    }

    handleImageSelect() {
        const file = this.imageInput.files[0];
        if (!file) return;

        // Validate file
        if (!this.validateFile(file)) {
            return;
        }

        this.currentImage = file;
        this.displayPreview(file);
    }

    validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (file.size > maxSize) {
            this.showError('File size exceeds 5MB limit');
            this.imageInput.value = '';
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            this.showError('Invalid file type. Allowed: JPEG, PNG, GIF, WebP');
            this.imageInput.value = '';
            return false;
        }

        return true;
    }

    displayPreview(file) {
        const reader = new FileReader();

        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                this.updatePreviewInfo(file, img.width, img.height);
                document.getElementById('previewImage').src = e.target.result;

                if (this.uploadArea) {
                    this.uploadArea.style.display = 'none';
                }
                if (this.imagePreview) {
                    this.imagePreview.style.display = 'block';
                }
            };
            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    }

    updatePreviewInfo(file, width, height) {
        const fileNameEl = document.getElementById('fileName');
        const fileSizeEl = document.getElementById('fileSize');
        const fileDimensionsEl = document.getElementById('fileDimensions');

        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileSizeEl) fileSizeEl.textContent = (file.size / 1024).toFixed(2) + ' KB';
        if (fileDimensionsEl) fileDimensionsEl.textContent = `${width} × ${height} px`;
    }

    showError(message) {
        alert('Error: ' + message);
    }

    reset() {
        if (this.imageInput) {
            this.imageInput.value = '';
        }
        if (this.imagePreview) {
            this.imagePreview.style.display = 'none';
        }
        if (this.uploadArea) {
            this.uploadArea.style.display = 'block';
        }
        this.currentImage = null;
    }
}

// Text utilities for result page
class TextUtilities {
    static updateStats(textElement) {
        const text = textElement.value;

        const charCount = text.length;
        const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
        const lineCount = text.trim().split('\n').length;

        const charEl = document.getElementById('charCount');
        const wordEl = document.getElementById('wordCount');
        const lineEl = document.getElementById('lineCount');

        if (charEl) charEl.textContent = charCount;
        if (wordEl) wordEl.textContent = wordCount;
        if (lineEl) lineEl.textContent = lineCount;
    }

    static copyToClipboard(text) {
        return new Promise((resolve, reject) => {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(resolve)
                    .catch(reject);
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    resolve();
                } catch (e) {
                    reject(e);
                }
                document.body.removeChild(textarea);
            }
        });
    }

    static downloadAsFile(text, filename = 'extracted-text.txt') {
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    static printText(text) {
        const printWindow = window.open('', '', 'height=400,width=800');
        printWindow.document.write(`
            <html>
            <head>
                <title>Print - Extracted Text</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    pre { font-family: monospace; line-height: 1.6; white-space: pre-wrap; }
                </style>
            </head>
            <body>
                <h2>Extracted Text</h2>
                <pre>${this.escapeHtml(text)}</pre>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    static sanitizeText(text) {
        // Remove extra whitespace
        return text.replace(/\s+/g, ' ').trim();
    }

    static formatText(text, format = 'default') {
        switch(format) {
            case 'uppercase':
                return text.toUpperCase();
            case 'lowercase':
                return text.toLowerCase();
            case 'capitalize':
                return text.replace(/\b\w/g, char => char.toUpperCase());
            default:
                return text;
        }
    }
}

// History management
class HistoryManager {
    static saveExtraction(text, filename = 'extraction') {
        const item = {
            id: Date.now(),
            text: text,
            filename: filename,
            date: new Date().toISOString()
        };

        let history = this.getHistory();
        history.unshift(item);

        // Keep only last 50 items
        history = history.slice(0, 50);

        localStorage.setItem('ocrHistory', JSON.stringify(history));
        return item;
    }

    static getHistory() {
        const history = localStorage.getItem('ocrHistory');
        return history ? JSON.parse(history) : [];
    }

    static getHistoryItem(id) {
        const history = this.getHistory();
        return history.find(item => item.id === id);
    }

    static deleteHistoryItem(id) {
        let history = this.getHistory();
        history = history.filter(item => item.id !== id);
        localStorage.setItem('ocrHistory', JSON.stringify(history));
    }

    static clearHistory() {
        localStorage.removeItem('ocrHistory');
    }

    static searchHistory(query) {
        const history = this.getHistory();
        const lowerQuery = query.toLowerCase();
        return history.filter(item =>
            item.text.toLowerCase().includes(lowerQuery) ||
            item.filename.toLowerCase().includes(lowerQuery)
        );
    }
}

// Initialize OCR Manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize OCR Manager if upload area exists
    if (document.getElementById('uploadArea')) {
        window.ocrManager = new OCRManager();
    }

    // Update stats if on result page
    const textElement = document.getElementById('extractedText');
    if (textElement) {
        TextUtilities.updateStats(textElement);
        textElement.addEventListener('input', () => TextUtilities.updateStats(textElement));
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        OCRManager,
        TextUtilities,
        HistoryManager
    };
}
