@extends('layouts.app')

@section('title', 'Dual Live Cameras - Autotrace')
@section('page-title', 'Dual Live Gate Cameras')

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

    .gate-badge-dual {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        background: #EFF6FF;
        color: #2563EB;
        border: 1px solid #BFDBFE;
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

    /* Stats Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 900px) {
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .stats-row { grid-template-columns: 1fr; }
    }

    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        box-shadow: var(--card-shadow);
    }

    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .stat-icon.scan { background: #EEF2FF; color: #4F46E5; }
    .stat-icon.entry { background: #ECFDF5; color: #10B981; }
    .stat-icon.exit { background: #FFF7ED; color: #F97316; }
    .stat-icon.skipped { background: #FEF9C3; color: #CA8A04; }

    .stat-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .stat-label {
        font-size: 0.725rem;
        color: var(--text-tertiary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Dual Camera Grid */
    .dual-camera-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.75rem;
    }

    @media (max-width: 1024px) {
        .dual-camera-grid {
            grid-template-columns: 1fr;
        }
    }

    .dual-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
    }

    .dual-card.entry {
        border-top: 3px solid #10B981;
    }

    .dual-card.exit {
        border-top: 3px solid #F97316;
    }

    .dual-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.85rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .dual-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dual-card-title.entry { color: #10B981; }
    .dual-card-title.exit { color: #F97316; }

    .camera-select-box {
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: #F8FAFC;
        color: var(--text-primary);
        font-weight: 600;
        max-width: 200px;
    }

    .dual-video-wrapper {
        position: relative;
        width: 100%;
        background: #0B1120;
        aspect-ratio: 16 / 9;
        max-height: 380px;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 0.85rem;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .dual-video-wrapper video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: none;
    }

    /* Clean CCTV Status Pill */
    .camera-status-pill {
        position: absolute;
        top: 10px;
        left: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 6px;
        font-size: 0.675rem;
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
        background: #64748B;
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

    /* Gate Status Box */
    .gate-status-box {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.85rem;
        transition: all 0.3s ease;
    }

    .gate-status-box.idle {
        background: #F8FAFC;
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
    }

    .gate-status-box.scanning {
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        color: #1D4ED8;
    }

    .gate-status-box.found-entry {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #047857;
    }

    .gate-status-box.found-exit {
        background: #FFF7ED;
        border: 1px solid #FFD8A8;
        color: #C2410C;
    }

    .gate-status-box.error {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #B91C1C;
    }

    /* Control Buttons */
    .gate-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.6rem;
    }

    .btn-gate-ctrl {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.55rem 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8125rem;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-gate-ctrl:hover {
        background: #F1F5F9;
        color: var(--text-primary);
    }

    .btn-gate-ctrl.paused {
        background: #FEF3C7;
        border-color: #FCD34D;
        color: #92400E;
    }

    /* Detection Event Log */
    .detection-log {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .detection-log-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
    }

    .detection-log-header h3 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detection-log-header .count-badge {
        font-size: 0.725rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
        background: #EEF2FF;
        color: #4F46E5;
    }

    .detection-log-body {
        max-height: 380px;
        overflow-y: auto;
        padding: 0.5rem;
    }

    .log-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--text-tertiary);
    }

    .log-empty i {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        display: block;
        opacity: 0.4;
    }

    .log-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 0.35rem;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        transition: background 0.15s ease;
        animation: fadeIn 0.3s ease;
    }

    .log-item:hover {
        background: #F8FAFC;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .log-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .log-icon.entry { background: #ECFDF5; color: #10B981; }
    .log-icon.exit { background: #FFF7ED; color: #F97316; }
    .log-icon.exit-matched { background: #ECFDF5; color: #059669; }
    .log-icon.duplicate { background: #FEF9C3; color: #CA8A04; }

    .log-details {
        flex: 1;
        min-width: 0;
    }

    .log-plate {
        font-weight: 800;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
        font-size: 0.95rem;
        color: var(--text-primary);
    }

    .log-meta {
        font-size: 0.725rem;
        color: var(--text-tertiary);
        margin-top: 2px;
    }

    .log-tag {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .log-tag.entry { background: #ECFDF5; color: #059669; }
    .log-tag.exit { background: #FFF7ED; color: #C2410C; }

    .loading-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top-color: currentColor;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="ri-vidicon-line" style="color: #2563EB;"></i>
        Dual Live Cameras • Automated Gate Control
        <span class="gate-badge-dual"><i class="ri-checkbox-circle-fill"></i> DUAL GATES ONLINE</span>
        <span class="auto-badge" id="autoBadge"><i class="ri-robot-2-line"></i> AUTO-DETECT ACTIVE</span>
    </h1>
    <p>Simultaneous automated license plate detection for Gate 1 (Entry) and Gate 2 (Exit)</p>
</div>

<!-- Session Stats Row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon scan"><i class="ri-scan-2-line"></i></div>
        <div>
            <div class="stat-value" id="statScans">0</div>
            <div class="stat-label">Frames Scanned</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon entry"><i class="ri-login-box-line"></i></div>
        <div>
            <div class="stat-value" id="statEntries">0</div>
            <div class="stat-label">Gate 1 Entries</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon exit"><i class="ri-logout-box-r-line"></i></div>
        <div>
            <div class="stat-value" id="statExits">0</div>
            <div class="stat-label">Gate 2 Exits</div>
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

<!-- Dual Camera Grid -->
<div class="dual-camera-grid">
    <!-- GATE 1: Entry Camera -->
    <div class="dual-card entry">
        <div class="dual-card-header">
            <div class="dual-card-title entry">
                <i class="ri-login-box-line"></i>
                <span>Gate 1 • Entry Camera</span>
            </div>
            <select class="camera-select-box" id="gate1CamSelect">
                <option value="">Detecting Cameras...</option>
            </select>
        </div>

        <div class="dual-video-wrapper">
            <video id="gate1Video" playsinline autoplay></video>

            <!-- Status Pill -->
            <div class="camera-status-pill" id="gate1StatusPill">
                <span class="dot" id="gate1Dot"></span>
                <span id="gate1StatusText">CONNECTING</span>
            </div>
        </div>

        <!-- Gate 1 Status Box -->
        <div class="gate-status-box idle" id="gate1StatusBox">
            <i class="ri-radar-line"></i>
            <span id="gate1StatusBoxText">Initializing Gate 1 Entry Scanner...</span>
        </div>

        <!-- Gate 1 Controls -->
        <div class="gate-controls">
            <button type="button" class="btn-gate-ctrl" id="gate1AutoToggleBtn">
                <i class="ri-pause-circle-line"></i>
                <span>Pause Auto-Scan</span>
            </button>
            <button type="button" class="btn-gate-ctrl" id="gate1CameraToggleBtn">
                <i class="ri-camera-off-line"></i>
                <span>Stop Camera</span>
            </button>
        </div>
    </div>

    <!-- GATE 2: Exit Camera -->
    <div class="dual-card exit">
        <div class="dual-card-header">
            <div class="dual-card-title exit">
                <i class="ri-logout-box-r-line"></i>
                <span>Gate 2 • Exit Camera</span>
            </div>
            <select class="camera-select-box" id="gate2CamSelect">
                <option value="">Detecting Cameras...</option>
            </select>
        </div>

        <div class="dual-video-wrapper">
            <video id="gate2Video" playsinline autoplay></video>

            <!-- Status Pill -->
            <div class="camera-status-pill" id="gate2StatusPill">
                <span class="dot" id="gate2Dot"></span>
                <span id="gate2StatusText">CONNECTING</span>
            </div>
        </div>

        <!-- Gate 2 Status Box -->
        <div class="gate-status-box idle" id="gate2StatusBox">
            <i class="ri-radar-line"></i>
            <span id="gate2StatusBoxText">Initializing Gate 2 Exit Scanner...</span>
        </div>

        <!-- Gate 2 Controls -->
        <div class="gate-controls">
            <button type="button" class="btn-gate-ctrl" id="gate2AutoToggleBtn">
                <i class="ri-pause-circle-line"></i>
                <span>Pause Auto-Scan</span>
            </button>
            <button type="button" class="btn-gate-ctrl" id="gate2CameraToggleBtn">
                <i class="ri-camera-off-line"></i>
                <span>Stop Camera</span>
            </button>
        </div>
    </div>
</div>

<!-- Unified Detection Event Log -->
<div class="detection-log">
    <div class="detection-log-header">
        <h3>
            <i class="ri-list-check-3" style="color: #2563EB;"></i>
            Real-Time Gate Activity Log
            <span class="count-badge" id="logCount">0</span>
        </h3>
    </div>
    <div class="detection-log-body" id="detectionLogBody">
        <div class="log-empty" id="logEmpty">
            <i class="ri-radar-line"></i>
            <div style="font-weight: 700; font-size: 0.9rem;">Waiting for vehicles at Gate 1 or Gate 2...</div>
            <div style="font-size: 0.8rem; margin-top: 0.25rem;">Automated detections will stream here in real time</div>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
/**
 * Global stats tracker for dual camera terminal
 */
const DualStats = {
    scans: 0,
    entries: 0,
    exits: 0,
    skipped: 0,
    logs: [],

    addScan() {
        this.scans++;
        document.getElementById('statScans').textContent = this.scans;
    },

    addEntry(result) {
        this.entries++;
        document.getElementById('statEntries').textContent = this.entries;
        this.addLog(result, 'entry');
    },

    addExit(result) {
        this.exits++;
        document.getElementById('statExits').textContent = this.exits;
        this.addLog(result, 'exit');
    },

    addSkip() {
        this.skipped++;
        document.getElementById('statSkipped').textContent = this.skipped;
    },

    addLog(result, type) {
        const logBody = document.getElementById('detectionLogBody');
        const logEmpty = document.getElementById('logEmpty');
        if (logEmpty) logEmpty.style.display = 'none';

        const isEntry = type === 'entry';
        const iconClass = isEntry ? 'entry' : (result.is_match_found ? 'exit-matched' : 'exit');
        const iconName = isEntry ? 'ri-login-box-line' : 'ri-logout-box-r-line';
        const tagClass = isEntry ? 'entry' : 'exit';
        const tagText = isEntry ? 'ENTRY' : (result.formatted_fee || 'EXIT');

        const item = document.createElement('div');
        item.className = 'log-item';
        item.innerHTML = `
            <div class="log-icon ${iconClass}"><i class="${iconName}"></i></div>
            <div class="log-details">
                <div class="log-plate">${result.plate}</div>
                <div class="log-meta">
                    ${result.formatted_time} • ${result.gate}
                    ${!isEntry && result.duration ? ' • ' + result.duration : ''}
                    • ${result.confidence}% conf
                </div>
            </div>
            <span class="log-tag ${tagClass}">${tagText}</span>
        `;

        logBody.insertBefore(item, logBody.firstChild);
        this.logs.unshift(result);
        document.getElementById('logCount').textContent = this.logs.length;

        while (logBody.children.length > 50) {
            logBody.removeChild(logBody.lastChild);
        }
    }
};

/**
 * GateCameraScanner handles video capture and automated detection loop
 */
class GateCameraScanner {
    constructor(options) {
        this.gateKey = options.gateKey; // 'gate1' or 'gate2'
        this.gateTitle = options.gateTitle; // 'Gate 1' or 'Gate 2'
        this.cameraId = options.cameraId;
        this.cameraType = options.cameraType; // 'entry' or 'exit'
        this.apiUrl = options.apiUrl;
        this.csrfToken = options.csrfToken;

        // Elements
        this.video = document.getElementById(this.gateKey + 'Video');
        this.select = document.getElementById(this.gateKey + 'CamSelect');
        this.dot = document.getElementById(this.gateKey + 'Dot');
        this.statusText = document.getElementById(this.gateKey + 'StatusText');
        this.statusBox = document.getElementById(this.gateKey + 'StatusBox');
        this.statusBoxText = document.getElementById(this.gateKey + 'StatusBoxText');
        this.autoToggleBtn = document.getElementById(this.gateKey + 'AutoToggleBtn');
        this.cameraToggleBtn = document.getElementById(this.gateKey + 'CameraToggleBtn');

        this.canvas = document.createElement('canvas');
        this.stream = null;
        this.isCameraActive = false;
        this.isAutoScanEnabled = true;
        this.isPendingRequest = false;

        this.scanIntervalMs = 2000;
        this.cooldownMs = 5000;
        this.scanTimer = null;
        this.cooldownUntil = 0;

        this.bindEvents();
    }

    bindEvents() {
        this.autoToggleBtn.addEventListener('click', () => this.toggleAutoScan());
        this.cameraToggleBtn.addEventListener('click', () => this.toggleCamera());
        this.select.addEventListener('change', () => this.startCamera(this.select.value));
    }

    async startCamera(deviceId = null) {
        this.stopCameraStream();

        const constraints = {
            video: {
                deviceId: deviceId ? { exact: deviceId } : undefined,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        };

        try {
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            try {
                await this.video.play();
            } catch (playErr) {
                console.log('Video autoplay note:', playErr);
            }
            this.isCameraActive = true;
            this.updateCameraUI();
            this.updateStatusBox('idle', `${this.gateTitle} active — scanning for vehicles...`);

            if (this.isAutoScanEnabled) {
                this.startAutoScan();
            }
        } catch (err) {
            console.error(`Error starting ${this.gateKey} camera:`, err);
            this.updateStatusBox('error', `Camera error: ${err.message}`);
            this.setDot('paused');
            this.statusText.textContent = 'ERROR';
        }
    }

    stopCameraStream() {
        if (this.stream) {
            this.stream.getTracks().forEach(t => t.stop());
            this.stream = null;
        }
        this.video.srcObject = null;
        this.isCameraActive = false;
    }

    stopCamera() {
        this.stopAutoScan();
        this.stopCameraStream();
        this.updateCameraUI();
        this.updateStatusBox('idle', `${this.gateTitle} camera stopped`);
    }

    toggleCamera() {
        if (this.isCameraActive) {
            this.stopCamera();
        } else {
            this.startCamera(this.select.value || null);
        }
    }

    toggleAutoScan() {
        if (this.isAutoScanEnabled) {
            this.isAutoScanEnabled = false;
            this.stopAutoScan();
            this.updateAutoToggleUI();
            this.updateStatusBox('idle', 'Auto-scan paused by operator');
        } else {
            this.isAutoScanEnabled = true;
            this.updateAutoToggleUI();
            if (this.isCameraActive) {
                this.startAutoScan();
            }
        }
    }

    startAutoScan() {
        this.stopAutoScan();
        this.updateStatusBox('scanning', `${this.gateTitle} monitoring camera feed...`);

        this.scanTimer = setInterval(() => this.autoScanFrame(), this.scanIntervalMs);
    }

    stopAutoScan() {
        if (this.scanTimer) {
            clearInterval(this.scanTimer);
            this.scanTimer = null;
        }
    }

    async autoScanFrame() {
        if (!this.isCameraActive || !this.isAutoScanEnabled || this.isPendingRequest) return;
        if (Date.now() < this.cooldownUntil) return;
        if (!this.video.videoWidth || !this.video.videoHeight) return;

        this.isPendingRequest = true;
        DualStats.addScan();

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
                    DualStats.addSkip();
                    this.setDot('active');
                } else {
                    if (this.cameraType === 'entry') {
                        DualStats.addEntry(result);
                        this.updateStatusBox('found-entry', `✓ Entry recorded: ${result.plate} (${result.confidence}%)`);
                    } else {
                        DualStats.addExit(result);
                        this.updateStatusBox('found-exit', `✓ Exit recorded: ${result.plate} — Fee: ${result.formatted_fee || '₱5.00'}`);
                    }

                    this.cooldownUntil = Date.now() + this.cooldownMs;
                    this.setDot('active');
                }
            } else {
                this.setDot('active');
                if (this.isAutoScanEnabled) {
                    this.updateStatusBox('scanning', `${this.gateTitle} scanning for vehicles...`);
                }
            }
        } catch (err) {
            console.error(`Auto-detect error on ${this.gateKey}:`, err);
            this.updateStatusBox('error', `Detection error: ${err.message}`);
            this.setDot('active');
        } finally {
            this.isPendingRequest = false;
        }
    }

    updateCameraUI() {
        if (this.isCameraActive) {
            this.cameraToggleBtn.innerHTML = '<i class="ri-camera-off-line"></i><span>Stop Camera</span>';
            this.setDot('active');
            this.statusText.textContent = 'LIVE';
        } else {
            this.cameraToggleBtn.innerHTML = '<i class="ri-camera-line"></i><span>Start Camera</span>';
            this.setDot('paused');
            this.statusText.textContent = 'OFFLINE';
        }
    }

    updateAutoToggleUI() {
        if (this.isAutoScanEnabled) {
            this.autoToggleBtn.innerHTML = '<i class="ri-pause-circle-line"></i><span>Pause Auto-Scan</span>';
            this.autoToggleBtn.classList.remove('paused');
        } else {
            this.autoToggleBtn.innerHTML = '<i class="ri-play-circle-line"></i><span>Resume Auto-Scan</span>';
            this.autoToggleBtn.classList.add('paused');
        }
    }

    setDot(state) {
        this.dot.className = 'dot ' + state;
    }

    setScanOverlay(active) {
        // Visual scanning overlay removed for clean CCTV feed appearance
    }

    updateStatusBox(type, message) {
        this.statusBox.className = 'gate-status-box ' + type;
        const icons = {
            idle: '<i class="ri-radar-line"></i>',
            scanning: '<span class="loading-spinner"></span>',
            'found-entry': '<i class="ri-checkbox-circle-fill"></i>',
            'found-exit': '<i class="ri-checkbox-circle-fill"></i>',
            error: '<i class="ri-error-warning-fill"></i>',
        };
        this.statusBox.innerHTML = (icons[type] || '') + `<span>${message}</span>`;
    }
}

/**
 * Initialize dual cameras and enumerate devices
 */
document.addEventListener('DOMContentLoaded', async () => {
    const gate1Scanner = new GateCameraScanner({
        gateKey: 'gate1',
        gateTitle: 'Gate 1 (Entry)',
        cameraId: 'gate1-entry',
        cameraType: 'entry',
        apiUrl: '{{ route("api.auto-detect") }}',
        csrfToken: '{{ csrf_token() }}',
    });

    const gate2Scanner = new GateCameraScanner({
        gateKey: 'gate2',
        gateTitle: 'Gate 2 (Exit)',
        cameraId: 'gate2-exit',
        cameraType: 'exit',
        apiUrl: '{{ route("api.auto-detect") }}',
        csrfToken: '{{ csrf_token() }}',
    });

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');

        const sel1 = document.getElementById('gate1CamSelect');
        const sel2 = document.getElementById('gate2CamSelect');
        sel1.innerHTML = '';
        sel2.innerHTML = '';

        if (videoDevices.length === 0) {
            sel1.innerHTML = '<option value="">Default Camera</option>';
            sel2.innerHTML = '<option value="">Default Camera</option>';
        } else {
            videoDevices.forEach((dev, idx) => {
                const label = dev.label || `Camera ${idx + 1}`;
                sel1.appendChild(new Option(label, dev.deviceId, false, idx === 0));
                sel2.appendChild(new Option(label, dev.deviceId, false, idx === (videoDevices.length > 1 ? 1 : 0)));
            });
        }

        // Start Gate 1 with Device 0, Gate 2 with Device 1 (or 0 if only 1 device)
        const dev1Id = videoDevices.length > 0 ? videoDevices[0].deviceId : null;
        const dev2Id = videoDevices.length > 1 ? videoDevices[1].deviceId : dev1Id;

        await gate1Scanner.startCamera(dev1Id);
        await gate2Scanner.startCamera(dev2Id);

    } catch (err) {
        console.error('Error discovering video devices:', err);
        // Fallback to start with defaults
        await gate1Scanner.startCamera();
        await gate2Scanner.startCamera();
    }
});
</script>
@endsection
