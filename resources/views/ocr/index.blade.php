@extends('layouts.app')

@section('title', 'OCR Tool - Text Extraction')
@section('page-title', 'OCR Tool')

@section('additional-styles')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 0.9375rem;
    }

    /* Upload Area */
    .upload-container {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        background: var(--bg-primary);
        transition: all 0.2s ease;
        cursor: pointer;
        margin: 1.5rem;
    }

    .upload-area:hover {
        background: var(--primary-light);
        border-color: var(--primary-color);
    }

    .upload-area.dragover {
        background: var(--primary-light);
        border-color: var(--primary-color);
        border-style: solid;
    }

    .upload-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1.5rem;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
    }

    .upload-icon i {
        font-size: 2rem;
        line-height: 1;
    }

    .upload-area h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .upload-area p {
        color: var(--text-secondary);
        font-size: 0.9375rem;
        margin-bottom: 0.5rem;
    }

    .upload-note {
        font-size: 0.8125rem;
        color: var(--text-tertiary);
        margin-top: 1rem;
    }

    input[type="file"] {
        display: none;
    }

    /* Preview Section */
    #imagePreview {
        display: none;
        padding: 1.5rem;
    }

    .preview-container {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        align-items: flex-start;
    }

    .preview-image {
        position: relative;
    }

    .preview-image img {
        width: 100%;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }

    .preview-info h4 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    .file-details {
        background: var(--bg-primary);
        padding: 1.25rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }

    .file-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        font-size: 0.9375rem;
    }

    .file-detail-row:not(:last-child) {
        border-bottom: 1px solid var(--border-color);
    }

    .file-detail-row .label {
        color: var(--text-tertiary);
        font-weight: 500;
    }

    .file-detail-row .value {
        color: var(--text-primary);
        font-weight: 600;
    }

    .ready-indicator {
        background: #D1FAE5;
        color: #065F46;
        padding: 1rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9375rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .ready-indicator i {
        font-size: 1.25rem;
    }

    .button-group {
        display: flex;
        gap: 0.75rem;
    }

    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .feature-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.2s ease;
    }

    .feature-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .feature-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        background: var(--primary-light);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
    }

    .feature-icon i {
        font-size: 1.5rem;
        line-height: 1;
    }

    .feature-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .feature-card p {
        color: var(--text-secondary);
        font-size: 0.875rem;
        line-height: 1.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .preview-container {
            grid-template-columns: 1fr;
        }

        .upload-area {
            padding: 2rem 1rem;
        }

        .button-group {
            flex-direction: column;
        }

        .button-group .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1><i class="ri-scan-line" style="margin-right: 0.5rem;"></i>OCR Text Extraction</h1>
    <p>Extract text from images using advanced Optical Character Recognition technology</p>
</div>

<!-- Upload Container -->
<div class="upload-container">
    <form id="ocrForm" action="{{ route('ocr.process') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Upload Area -->
        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">
                <i class="ri-upload-cloud-line"></i>
            </div>
            <h3>Upload Image</h3>
            <p>Drag and drop your image here, or click to browse</p>
            <p class="upload-note">
                <i class="ri-image-line"></i> Supports: JPEG, PNG, GIF, WebP • Max size: 5MB
            </p>
            <input type="file" id="imageInput" name="image" accept="image/*">
        </div>

        <!-- Image Preview -->
        <div id="imagePreview">
            <div class="preview-container">
                <div class="preview-image">
                    <img id="previewImage" src="" alt="Preview">
                </div>
                <div class="preview-info">
                    <h4>Image Details</h4>

                    <div class="file-details">
                        <div class="file-detail-row">
                            <span class="label"><i class="ri-file-line"></i> Filename</span>
                            <span class="value" id="fileName">-</span>
                        </div>
                        <div class="file-detail-row">
                            <span class="label"><i class="ri-dashboard-line"></i> File Size</span>
                            <span class="value" id="fileSize">-</span>
                        </div>
                        <div class="file-detail-row">
                            <span class="label"><i class="ri-aspect-ratio-line"></i> Dimensions</span>
                            <span class="value" id="fileDimensions">-</span>
                        </div>
                    </div>

                    <div class="ready-indicator">
                        <i class="ri-checkbox-circle-line"></i>
                        <span>Image ready for processing</span>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="ri-scan-line"></i>
                            <span>Extract Text</span>
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelBtn">
                            <i class="ri-close-line"></i>
                            <span>Cancel</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Features Section -->
<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">
            <i class="ri-flashlight-line"></i>
        </div>
        <h4>Fast Processing</h4>
        <p>Quick and accurate text extraction from your images in seconds</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon">
            <i class="ri-pie-chart-line"></i>
        </div>
        <h4>High Accuracy</h4>
        <p>Advanced OCR technology ensures reliable and precise results</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon">
            <i class="ri-shield-check-line"></i>
        </div>
        <h4>Secure & Private</h4>
        <p>Your images are processed securely and not stored permanently</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon">
            <i class="ri-download-2-line"></i>
        </div>
        <h4>Easy Export</h4>
        <p>Download extracted text in multiple formats instantly</p>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const cancelBtn = document.getElementById('cancelBtn');
    const ocrForm = document.getElementById('ocrForm');

    // Click to upload
    uploadArea.addEventListener('click', () => imageInput.click());

    // Drag and drop handlers
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            imageInput.files = files;
            handleImageSelect();
        }
    });

    // File input change
    imageInput.addEventListener('change', handleImageSelect);

    // Cancel button
    cancelBtn.addEventListener('click', () => {
        imageInput.value = '';
        imagePreview.style.display = 'none';
        uploadArea.style.display = 'block';
    });

    function handleImageSelect() {
        const file = imageInput.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            imageInput.value = '';
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            imageInput.value = '';
            return;
        }

        // Display preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;

            // Get image dimensions
            const img = new Image();
            img.onload = () => {
                document.getElementById('fileDimensions').textContent =
                    `${img.width} × ${img.height} px`;
            };
            img.src = e.target.result;

            // Update file details
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent =
                (file.size / 1024).toFixed(2) + ' KB';

            // Show preview, hide upload area
            uploadArea.style.display = 'none';
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Form submission validation
    ocrForm.addEventListener('submit', function(e) {
        if (!imageInput.files.length) {
            e.preventDefault();
            alert('Please select an image first');
        }
    });

    // Prevent default drag behavior on document
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        document.body.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });
});
</script>
@endsection
