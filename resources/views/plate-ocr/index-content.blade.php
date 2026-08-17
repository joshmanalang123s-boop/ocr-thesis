<div class="card">
    <h3 style="color: #333; margin-bottom: 1rem; font-size: 1.6rem;">📸 Scan License Plate</h3>

    <!-- Camera Section -->
    <div class="camera-container" id="cameraContainer">
        <video id="cameraVideo" playsinline></video>
        <div class="camera-overlay"></div>
        <div class="plate-guide">
            Position plate here
        </div>
        <button class="torch-toggle" id="torchToggle" style="display: none;">💡 Torch</button>
    </div>

    <!-- Detection Status -->
    <div class="detection-status" id="detectionStatus">
        <span class="loading-spinner"></span> Processing...
    </div>

    <!-- Camera Controls -->
    <div class="camera-controls">
        <button type="button" class="capture-btn" id="captureBtn">
            📷 Capture Plate
        </button>
        <button type="button" class="capture-btn" id="toggleCameraBtn" style="background: #666;">
            🎥 Stop Camera
        </button>
    </div>

    <!-- Upload Fallback -->
    <div class="upload-fallback">
        <h4>📁 Or Upload Image</h4>
        <p style="color: #999; margin-bottom: 1rem;">
            No camera available? Upload a photo of the license plate
        </p>
        <form id="uploadForm" action="{{ route('plate-ocr.detect') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" id="plateUpload" name="image" accept="image/*">
            <button type="button" class="upload-btn" onclick="document.getElementById('plateUpload').click()">
                Choose Image
            </button>
            <button type="submit" class="upload-btn" style="background: #764ba2; margin-left: 0.5rem;">
                Process
            </button>
            <span id="uploadFileName" style="margin-left: 1rem; color: #666;"></span>
        </form>
    </div>

    <!-- Features -->
    <div class="features-grid">
        <div class="feature">
            <div class="feature-icon">⚡</div>
            <h4>Real-Time Detection</h4>
            <p>Instant plate recognition from camera</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🎯</div>
            <h4>Auto Detection</h4>
            <p>Automatic license plate localization</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🔗</div>
            <h4>QR Generation</h4>
            <p>Generate scannable QR codes instantly</p>
        </div>
        <div class="feature">
            <div class="feature-icon">⏱️</div>
            <h4>Timestamp</h4>
            <p>Date & time embedded in QR code</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🖨️</div>
            <h4>Print Ready</h4>
            <p>Print QR codes directly</p>
        </div>
        <div class="feature">
            <div class="feature-icon">📚</div>
            <h4>History</h4>
            <p>Track all detected plates</p>
        </div>
    </div>
</div>
