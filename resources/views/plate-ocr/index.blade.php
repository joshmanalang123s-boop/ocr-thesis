@extends('layouts.app')

@section('title', 'Gate 1 Entry Terminal')
@section('page-title', 'Gate 1 Entry Camera Terminal')

@section('additional-styles')
<style>
    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h1 {
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .gate-badge-entry {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        background: #ECFDF5;
        color: #059669;
        border: 1px solid #A7F3D0;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* Simple Camera Card */
    .camera-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .camera-container {
        position: relative;
        width: 100%;
        background: #0F172A;
        aspect-ratio: 16 / 9;
        max-height: 450px;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    #cameraVideo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }

    .camera-controls {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .control-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.75rem 1.75rem;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.9375rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .control-btn.btn-entry-capture {
        background: #10B981;
        color: white;
    }

    .control-btn.btn-entry-capture:hover {
        background: #059669;
    }

    .control-btn.btn-camera-stop {
        background: #F1F5F9;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .control-btn.btn-camera-stop:hover {
        background: #E2E8F0;
    }

    /* Status Message Banner */
    .status-message {
        padding: 1rem 1.25rem;
        border-radius: 10px;
        display: none;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .status-message.active { display: flex; }
    .status-message.processing { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
    .status-message.success { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
    .status-message.error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }

    .loading-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top-color: currentColor;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* File Upload Option */
    .upload-section {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
    }

    .upload-section h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        background: #F8FAFC;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .upload-area:hover, .upload-area.dragover {
        background: #ECFDF5;
        border-color: #10B981;
    }

    .upload-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto 1rem;
        background: #ECFDF5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #10B981;
        font-size: 1.5rem;
    }

    #plateUpload { display: none; }

    .file-selected {
        margin-top: 1rem;
        padding: 1rem;
        background: #F8FAFC;
        border-radius: 8px;
        display: none;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid var(--border-color);
    }

    .file-selected.active { display: flex; }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="ri-login-box-line" style="color: #10B981;"></i>
        Gate 1 • Entry Camera Terminal
        <span class="gate-badge-entry"><i class="ri-checkbox-circle-fill"></i> GATE IN ONLINE</span>
    </h1>
    <p>Position incoming vehicle in front of Gate 1 camera to scan license plate and issue entry QR pass</p>
</div>

<!-- Status Banner -->
<div class="status-message" id="statusMessage">
    <i class="ri-loader-4-line loading-spinner"></i>
    <span id="statusText">Ready...</span>
</div>

<!-- Camera Card -->
<div class="camera-card">
    <div class="camera-container" id="cameraContainer">
        <video id="cameraVideo" playsinline autoplay></video>
    </div>

    <div class="camera-controls">
        <button type="button" class="control-btn btn-entry-capture" id="captureBtn">
            <i class="ri-camera-3-line"></i>
            <span>Capture Entry Plate</span>
        </button>
        <button type="button" class="control-btn btn-camera-stop" id="toggleCameraBtn">
            <i class="ri-camera-off-line"></i>
            <span>Stop Camera Feed</span>
        </button>
    </div>
</div>

<!-- Upload Section -->
<div class="upload-section">
    <h3><i class="ri-upload-cloud-line" style="color: #10B981;"></i> Manual Photo Entry Upload</h3>
    <form id="uploadForm" action="{{ route('plate-ocr.detect') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">
                <i class="ri-image-add-line"></i>
            </div>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary);">Click or Drag Vehicle Photo Here</h4>
            <p style="font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem;">Supports JPG, PNG, WEBP photo captures up to 10MB</p>
            <input type="file" id="plateUpload" name="image" accept="image/*">
        </div>

        <div class="file-selected" id="fileSelected">
            <i class="ri-checkbox-circle-fill" style="color: #10B981; font-size: 1.25rem;"></i>
            <div style="flex: 1;">
                <div id="fileName" style="font-weight: 700; font-size: 0.875rem; color: var(--text-primary);">filename.jpg</div>
                <div id="fileSize" style="font-size: 0.75rem; color: var(--text-tertiary);">2.5 MB</div>
            </div>
            <button type="submit" class="btn btn-entry">
                <i class="ri-scan-line"></i>
                <span>Process Entry</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('additional-scripts')
<script>
class PlateScanner {
    constructor() {
        this.video = document.getElementById('cameraVideo');
        this.canvas = document.createElement('canvas');
        this.stream = null;
        this.isCameraActive = false;
        this.isCapturing = false;
        this.torchEnabled = false;

        this.init();
    }

    init() {
        // Camera controls
        const captureBtn = document.getElementById('captureBtn');
        const toggleCamBtn = document.getElementById('toggleCameraBtn');
        const torchBtn = document.getElementById('torchToggle');

        if (captureBtn) captureBtn.addEventListener('click', () => this.capture());
        if (toggleCamBtn) toggleCamBtn.addEventListener('click', () => this.toggleCamera());
        if (torchBtn) torchBtn.addEventListener('click', () => this.toggleTorch());

        // Upload controls
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('plateUpload');

        uploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                this.handleFileSelect({ target: fileInput });
            }
        });

        // Start camera automatically
        this.startCamera();
    }

    async startCamera() {
        try {
            const constraints = {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            this.isCameraActive = true;

            // Check for torch/flashlight support
            const track = this.stream.getVideoTracks()[0];
            const capabilities = track.getCapabilities();

            if (capabilities.torch) {
                const torchBtn = document.getElementById('torchToggle');
                if (torchBtn) torchBtn.style.display = 'flex';
            }

            this.updateCameraButton();

        } catch (error) {
            console.error('Camera error:', error);
            this.showStatus('Camera not available. Please use file upload.', 'error');
        }
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.video.srcObject = null;
            this.isCameraActive = false;
            this.updateCameraButton();
        }
    }

    toggleCamera() {
        if (this.isCameraActive) {
            this.stopCamera();
        } else {
            this.startCamera();
        }
    }

    updateCameraButton() {
        const btn = document.getElementById('toggleCameraBtn');
        if (this.isCameraActive) {
            btn.innerHTML = '<i class="ri-camera-off-line"></i><span>Stop Camera</span>';
        } else {
            btn.innerHTML = '<i class="ri-camera-line"></i><span>Start Camera</span>';
        }
    }

    async toggleTorch() {
        if (!this.stream) return;

        try {
            const track = this.stream.getVideoTracks()[0];
            this.torchEnabled = !this.torchEnabled;

            await track.applyConstraints({
                advanced: [{ torch: this.torchEnabled }]
            });

            const btn = document.getElementById('torchToggle');
            if (this.torchEnabled) {
                btn.innerHTML = '<i class="ri-flashlight-fill"></i><span>Flashlight On</span>';
            } else {
                btn.innerHTML = '<i class="ri-flashlight-line"></i><span>Flashlight</span>';
            }

        } catch (error) {
            console.error('Torch error:', error);
        }
    }

    async capture() {
        if (!this.isCameraActive || this.isCapturing) return;

        this.isCapturing = true;
        this.showStatus('Processing plate detection...', 'processing');

        // Set canvas dimensions
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;

        // Draw video frame
        const ctx = this.canvas.getContext('2d');
        ctx.scale(-1, 1);
        ctx.drawImage(this.video, -this.canvas.width, 0);

        // Convert to blob and submit as a real form post so the browser
        // navigates to the rendered result page (POST /plate-ocr/detect
        // returns a view, not a redirect — fetch()'s response.url doesn't
        // point at the result page).
        this.canvas.toBlob((blob) => {
            const file = new File([blob], 'plate-capture.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            const fileInput = document.getElementById('plateUpload');
            fileInput.files = dataTransfer.files;

            document.getElementById('uploadForm').submit();
        }, 'image/jpeg', 0.95);
    }

    handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showStatus('Please select a valid image file', 'error');
            e.target.value = '';
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            this.showStatus('File size exceeds 10MB limit', 'error');
            e.target.value = '';
            return;
        }

        // Show file details
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById('fileSelected').classList.add('active');
    }

    showStatus(message, type = 'processing') {
        const statusEl = document.getElementById('statusMessage');
        const textEl = document.getElementById('statusText');

        statusEl.className = 'status-message active ' + type;

        if (type === 'processing') {
            statusEl.innerHTML = '<i class="ri-loader-4-line loading-spinner"></i><span>' + message + '</span>';
        } else if (type === 'success') {
            statusEl.innerHTML = '<i class="ri-checkbox-circle-line"></i><span>' + message + '</span>';
        } else if (type === 'error') {
            statusEl.innerHTML = '<i class="ri-error-warning-line"></i><span>' + message + '</span>';
        }

        if (type !== 'processing') {
            setTimeout(() => {
                statusEl.classList.remove('active');
            }, 4000);
        }
    }
}

// Initialize scanner
document.addEventListener('DOMContentLoaded', () => {
    new PlateScanner();
});
</script>
@endsection
