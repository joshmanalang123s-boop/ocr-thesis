@extends('layouts.app')

@section('title', 'Gate 2 Exit Terminal')
@section('page-title', 'Gate 2 Exit Camera Terminal')

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
        flex-wrap: wrap;
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .gate-badge-exit {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FFD8A8;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .auto-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        background: #EEF2FF;
        color: #4338CA;
        border: 1px solid #C7D2FE;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* Camera Card - Clean CCTV View */
    .camera-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .camera-container {
        position: relative;
        width: 100%;
        background: #0B1120;
        aspect-ratio: 16 / 9;
        max-height: 450px;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    #cameraVideo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: none;
    }

    /* Clean CCTV Status Badge */
    .camera-status-pill {
        position: absolute;
        top: 12px;
        left: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        color: #E2E8F0;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        z-index: 10;
        font-family: monospace;
    }

    .camera-status-pill .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #10B981;
    }

    .camera-status-pill .dot.active {
        background: #10B981;
    }

    .camera-status-pill .dot.scanning {
        background: #10B981;
    }

    .camera-status-pill .dot.paused {
        background: #94A3B8;
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

    .control-btn.btn-auto-toggle {
        background: #F97316;
        color: white;
    }

    .control-btn.btn-auto-toggle:hover {
        background: #EA580C;
    }

    .control-btn.btn-auto-toggle.paused {
        background: #F59E0B;
        color: white;
    }

    .control-btn.btn-camera-stop {
        background: #F1F5F9;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .control-btn.btn-camera-stop:hover {
        background: #E2E8F0;
    }

    /* Detection Status Banner */
    .detection-status {
        padding: 1rem 1.25rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .detection-status.idle { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
    .detection-status.scanning { background: #FFF7ED; color: #C2410C; border: 1px solid #FFD8A8; }
    .detection-status.found { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; animation: resultFlash 0.5s ease; }
    .detection-status.error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }

    @keyframes resultFlash {
        0% { transform: scale(1.02); }
        50% { transform: scale(1); }
    }

    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top-color: currentColor;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Detection Log */
    .detection-log {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .detection-log-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FAFAFA;
    }

    .detection-log-header h3 {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detection-log-header .count-badge {
        background: #F97316;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .detection-log-body {
        max-height: 380px;
        overflow-y: auto;
    }

    .log-empty {
        padding: 3rem 2rem;
        text-align: center;
        color: var(--text-tertiary);
    }

    .log-empty i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        display: block;
        opacity: 0.4;
    }

    .log-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F1F5F9;
        animation: slideIn 0.3s ease;
    }

    .log-item:last-child { border-bottom: none; }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .log-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .log-icon.exit { background: #FFF7ED; color: #F97316; }
    .log-icon.exit-matched { background: #ECFDF5; color: #10B981; }
    .log-icon.duplicate { background: #FEF9C3; color: #CA8A04; }

    .log-details { flex: 1; min-width: 0; }

    .log-plate {
        font-weight: 800;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
        font-size: 1rem;
        color: var(--text-primary);
    }

    .log-meta {
        font-size: 0.75rem;
        color: var(--text-tertiary);
        margin-top: 2px;
    }

    .log-fee {
        font-size: 0.9rem;
        font-weight: 800;
        color: #10B981;
        flex-shrink: 0;
    }

    .log-confidence {
        font-size: 0.8rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .log-confidence.high { background: #ECFDF5; color: #059669; }
    .log-confidence.medium { background: #FFF7ED; color: #C2410C; }

    /* Stats Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } }

    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--card-shadow);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .stat-icon.scan { background: #FFF7ED; color: #F97316; }
    .stat-icon.detected { background: #ECFDF5; color: #10B981; }
    .stat-icon.skipped { background: #FEF9C3; color: #CA8A04; }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-tertiary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="ri-logout-box-r-line" style="color: #F97316;"></i>
        Gate 2 • Exit Camera Terminal
        <span class="gate-badge-exit"><i class="ri-checkbox-circle-fill"></i> GATE OUT ONLINE</span>
        <span class="auto-badge" id="autoBadge"><i class="ri-robot-2-line"></i> AUTO-DETECT ACTIVE</span>
    </h1>
    <p>Automated license plate detection — exiting vehicles are scanned, matched, and fees calculated automatically</p>
</div>

<!-- Detection Status -->
<div class="detection-status idle" id="detectionStatus">
    <i class="ri-radar-line"></i>
    <span id="detectionStatusText">Initializing camera and auto-detection...</span>
</div>

<!-- Session Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon scan"><i class="ri-scan-2-line"></i></div>
        <div>
            <div class="stat-value" id="statScans">0</div>
            <div class="stat-label">Frames Scanned</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon detected"><i class="ri-car-line"></i></div>
        <div>
            <div class="stat-value" id="statDetected">0</div>
            <div class="stat-label">Exits Processed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon skipped"><i class="ri-skip-forward-line"></i></div>
        <div>
            <div class="stat-value" id="statSkipped">0</div>
            <div class="stat-label">Duplicates Skipped</div>
        </div>
    </div>
</div>

<!-- Camera Card -->
<div class="camera-card">
    <div class="camera-container" id="cameraContainer">
        <video id="cameraVideo" playsinline autoplay></video>

        <!-- Camera Status Pill -->
        <div class="camera-status-pill" id="cameraStatusPill">
            <span class="dot" id="cameraDot"></span>
            <span id="cameraStatusText">CONNECTING</span>
        </div>
    </div>

    <div class="camera-controls">
        <button type="button" class="control-btn btn-auto-toggle" id="autoToggleBtn">
            <i class="ri-pause-circle-line"></i>
            <span>Pause Auto-Scan</span>
        </button>
        <button type="button" class="control-btn btn-camera-stop" id="toggleCameraBtn">
            <i class="ri-camera-off-line"></i>
            <span>Stop Camera Feed</span>
        </button>
    </div>
</div>

<!-- Detection Event Log -->
<div class="detection-log">
    <div class="detection-log-header">
        <h3>
            <i class="ri-list-check-3" style="color: #F97316;"></i>
            Auto-Detection Log (Exit)
            <span class="count-badge" id="logCount">0</span>
        </h3>
    </div>
    <div class="detection-log-body" id="detectionLogBody">
        <div class="log-empty" id="logEmpty">
            <i class="ri-radar-line"></i>
            <div style="font-weight: 700; font-size: 0.9rem;">Waiting for exiting vehicles...</div>
            <div style="font-size: 0.8rem; margin-top: 0.25rem;">Auto-detection will begin once camera is active</div>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
class AutoExitScanner {
    constructor(config) {
        this.cameraId = config.cameraId || 'gate2-exit';
        this.cameraType = 'exit';
        this.apiUrl = config.apiUrl;
        this.csrfToken = config.csrfToken;

        this.video = document.getElementById('cameraVideo');
        this.canvas = document.createElement('canvas');
        this.stream = null;
        this.isCameraActive = false;
        this.isAutoScanEnabled = true;
        this.isPendingRequest = false;

        this.scanIntervalMs = 1200; // scan every 1.2s
        this.cooldownMs = 4000;
        this.scanTimer = null;
        this.cooldownUntil = 0;

        this.stats = { scans: 0, detected: 0, skipped: 0 };
        this.logEntries = [];

        this.init();
    }

    init() {
        document.getElementById('autoToggleBtn').addEventListener('click', () => this.toggleAutoScan());
        document.getElementById('toggleCameraBtn').addEventListener('click', () => this.toggleCamera());
        this.startCamera();
    }

    async startCamera() {
        try {
            const constraints = {
                video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            };
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            try {
                await this.video.play();
            } catch (playErr) {
                console.log('Video autoplay note:', playErr);
            }
            this.isCameraActive = true;
            this.updateCameraUI();
            this.updateDetectionStatus('idle', 'Camera active — auto-scanning for exiting vehicles...');
            if (this.isAutoScanEnabled) this.startAutoScan();
        } catch (error) {
            console.error('Camera error:', error);
            this.updateDetectionStatus('error', 'Camera not available — ' + error.message);
        }
    }

    stopCamera() {
        this.stopAutoScan();
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.video.srcObject = null;
            this.isCameraActive = false;
            this.updateCameraUI();
            this.updateDetectionStatus('idle', 'Camera stopped');
        }
    }

    toggleCamera() {
        this.isCameraActive ? this.stopCamera() : this.startCamera();
    }

    toggleAutoScan() {
        if (this.isAutoScanEnabled) {
            this.isAutoScanEnabled = false;
            this.stopAutoScan();
            this.updateAutoToggleUI();
            this.updateDetectionStatus('idle', 'Auto-scan paused by operator');
        } else {
            this.isAutoScanEnabled = true;
            this.updateAutoToggleUI();
            if (this.isCameraActive) this.startAutoScan();
        }
    }

    startAutoScan() {
        this.stopAutoScan();
        this.updateDetectionStatus('scanning', 'Monitoring camera feed for exiting vehicles...');
        this.scanTimer = setInterval(() => this.autoScanFrame(), this.scanIntervalMs);
    }

    stopAutoScan() {
        if (this.scanTimer) { clearInterval(this.scanTimer); this.scanTimer = null; }
    }

    async autoScanFrame() {
        if (!this.isCameraActive || !this.isAutoScanEnabled || this.isPendingRequest) return;
        if (Date.now() < this.cooldownUntil) return;
        if (!this.video.videoWidth || !this.video.videoHeight) return;

        this.isPendingRequest = true;
        this.stats.scans++;
        this.updateStats();

        try {
            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;
            const ctx = this.canvas.getContext('2d');
            ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

            const frameData = this.canvas.toDataURL('image/jpeg', 0.88);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    frame: frameData,
                    camera_id: this.cameraId,
                    camera_type: this.cameraType,
                }),
            });

            const result = await response.json();

            if (result.success && !result.no_plate) {
                if (result.duplicate) {
                    this.stats.skipped++;
                    this.updateStats();
                    this.setCameraDot('active');
                } else {
                    this.stats.detected++;
                    this.updateStats();
                    this.addLogEntry(result);

                    const feeText = result.formatted_fee ? ' — Fee: ' + result.formatted_fee : '';
                    const matchText = result.is_match_found
                        ? ` (Matched ${result.entry_gate || 'Entry'} • ${result.duration})`
                        : ' (no matching entry)';
                    this.updateDetectionStatus('found',
                        '✓ Exit: ' + result.plate + matchText + feeText);

                    this.cooldownUntil = Date.now() + this.cooldownMs;
                    this.setCameraDot('active');
                }
            } else {
                this.setCameraDot('active');
                if (this.isAutoScanEnabled) {
                    this.updateDetectionStatus('scanning', 'Scanning for exiting vehicles...');
                }
            }
        } catch (error) {
            console.error('Auto-detect error:', error);
            this.updateDetectionStatus('error', 'Detection error: ' + error.message);
            this.setCameraDot('active');
        } finally {
            this.isPendingRequest = false;
        }
    }

    // ─── UI Helpers ─────────────────────────────────────────────

    addLogEntry(result) {
        const logBody = document.getElementById('detectionLogBody');
        const logEmpty = document.getElementById('logEmpty');
        if (logEmpty) logEmpty.style.display = 'none';

        const iconClass = result.is_match_found ? 'exit-matched' : 'exit';
        const iconName = result.is_match_found ? 'ri-checkbox-circle-fill' : 'ri-logout-box-r-fill';
        const durationText = result.duration || 'N/A';
        const feeText = result.formatted_fee || '₱5.00';
        const metaText = result.is_match_found
            ? ` • Matched ${result.entry_gate || 'Entry'} • Duration: ${durationText}`
            : ' • No matching entry';

        const item = document.createElement('div');
        item.className = 'log-item';
        item.innerHTML = `
            <div class="log-icon ${iconClass}"><i class="${iconName}"></i></div>
            <div class="log-details">
                <div class="log-plate">${result.plate}</div>
                <div class="log-meta">
                    ${result.formatted_time} • ${result.gate}${metaText}
                </div>
            </div>
            <span class="log-fee">${feeText}</span>
        `;

        logBody.insertBefore(item, logBody.firstChild);
        this.logEntries.unshift(result);
        document.getElementById('logCount').textContent = this.logEntries.length;

        while (logBody.children.length > 50) {
            logBody.removeChild(logBody.lastChild);
        }
    }

    updateStats() {
        document.getElementById('statScans').textContent = this.stats.scans;
        document.getElementById('statDetected').textContent = this.stats.detected;
        document.getElementById('statSkipped').textContent = this.stats.skipped;
    }

    updateDetectionStatus(type, message) {
        const el = document.getElementById('detectionStatus');
        el.className = 'detection-status ' + type;
        const icons = {
            idle: '<i class="ri-radar-line"></i>',
            scanning: '<span class="loading-spinner"></span>',
            found: '<i class="ri-checkbox-circle-fill"></i>',
            error: '<i class="ri-error-warning-fill"></i>',
        };
        el.innerHTML = (icons[type] || '') + '<span>' + message + '</span>';
    }

    updateCameraUI() {
        const btn = document.getElementById('toggleCameraBtn');
        if (this.isCameraActive) {
            btn.innerHTML = '<i class="ri-camera-off-line"></i><span>Stop Camera</span>';
            this.setCameraDot('active');
            document.getElementById('cameraStatusText').textContent = 'LIVE';
        } else {
            btn.innerHTML = '<i class="ri-camera-line"></i><span>Start Camera</span>';
            this.setCameraDot('paused');
            document.getElementById('cameraStatusText').textContent = 'OFFLINE';
        }
    }

    updateAutoToggleUI() {
        const btn = document.getElementById('autoToggleBtn');
        const badge = document.getElementById('autoBadge');
        if (this.isAutoScanEnabled) {
            btn.innerHTML = '<i class="ri-pause-circle-line"></i><span>Pause Auto-Scan</span>';
            btn.classList.remove('paused');
            badge.innerHTML = '<i class="ri-robot-2-line"></i> AUTO-DETECT ACTIVE';
            badge.style.background = '#EEF2FF';
            badge.style.color = '#4338CA';
            badge.style.borderColor = '#C7D2FE';
        } else {
            btn.innerHTML = '<i class="ri-play-circle-line"></i><span>Resume Auto-Scan</span>';
            btn.classList.add('paused');
            badge.innerHTML = '<i class="ri-pause-mini-line"></i> AUTO-DETECT PAUSED';
            badge.style.background = '#FEF9C3';
            badge.style.color = '#A16207';
            badge.style.borderColor = '#FDE68A';
        }
    }

    setCameraDot(state) {
        document.getElementById('cameraDot').className = 'dot ' + state;
    }

    setScanOverlay(active) {
        // Visual scanning overlay removed for clean CCTV feed appearance
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new AutoExitScanner({
        cameraId: 'gate2-exit',
        apiUrl: '{{ route("api.auto-detect") }}',
        csrfToken: '{{ csrf_token() }}',
    });
});
</script>
@endsection
