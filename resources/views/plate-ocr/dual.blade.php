@extends('layouts.app')

@section('title', 'Dual Live Cameras - Autotrace')
@section('page-title', 'Dual Live Gate Cameras')

@section('additional-styles')
<style>
    .dual-camera-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.75rem;
        margin-bottom: 2rem;
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
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
    }

    .dual-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .dual-card-title {
        font-size: 1.1rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dual-card-title.entry { color: #10B981; }
    .dual-card-title.exit { color: #F97316; }

    .camera-select-box {
        font-size: 0.8125rem;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: #F8FAFC;
        color: var(--text-primary);
        font-weight: 600;
        max-width: 220px;
    }

    .dual-video-wrapper {
        position: relative;
        width: 100%;
        background: #0F172A;
        aspect-ratio: 16 / 9;
        max-height: 420px;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    .dual-video-wrapper video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }

    .dual-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
    }

    .btn-dual-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.9375rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
    }

    .btn-dual-action.entry {
        background: #10B981;
        color: white;
    }

    .btn-dual-action.entry:hover {
        background: #059669;
    }

    .btn-dual-action.exit {
        background: #F97316;
        color: white;
    }

    .btn-dual-action.exit:hover {
        background: #EA580C;
    }

    /* Result Modal Notification */
    .result-toast {
        display: none;
        padding: 1rem 1.25rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        align-items: center;
        gap: 0.75rem;
    }

    .result-toast.active { display: flex; }
    .result-toast.success { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
    .result-toast.error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="ri-vidicon-line" style="color: #2563EB;"></i>
        Dual Live Cameras • Gate Control
    </h1>
    <p>Simultaneous live stream for Gate 1 (Entry Camera) and Gate 2 (Exit Camera)</p>
</div>

<!-- Live Result Banner -->
<div class="result-toast" id="liveToast">
    <i class="ri-checkbox-circle-fill" style="font-size: 1.25rem;"></i>
    <span id="toastText">System Ready</span>
</div>

<div class="dual-camera-grid">
    <!-- GATE 1: Entry Camera -->
    <div class="dual-card">
        <div class="dual-card-header">
            <div class="dual-card-title entry">
                <i class="ri-login-box-line"></i>
                <span>Gate 1 • Entry Camera</span>
            </div>
            <select class="camera-select-box" id="gate1CamSelect" onchange="switchGateCamera('gate1', this.value)">
                <option value="">Detecting Cameras...</option>
            </select>
        </div>

        <div class="dual-video-wrapper">
            <video id="gate1Video" playsinline autoplay></video>
        </div>

        <div class="dual-actions">
            <button type="button" class="btn-dual-action entry" onclick="captureGatePlate('gate1')">
                <i class="ri-camera-3-line"></i>
                <span>Capture Entry Plate</span>
            </button>
        </div>
    </div>

    <!-- GATE 2: Exit Camera -->
    <div class="dual-card">
        <div class="dual-card-header">
            <div class="dual-card-title exit">
                <i class="ri-logout-box-r-line"></i>
                <span>Gate 2 • Exit Camera</span>
            </div>
            <select class="camera-select-box" id="gate2CamSelect" onchange="switchGateCamera('gate2', this.value)">
                <option value="">Detecting Cameras...</option>
            </select>
        </div>

        <div class="dual-video-wrapper">
            <video id="gate2Video" playsinline autoplay></video>
        </div>

        <div class="dual-actions">
            <button type="button" class="btn-dual-action exit" onclick="captureGatePlate('gate2')">
                <i class="ri-logout-box-r-line"></i>
                <span>Scan & Checkout Exit</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
let gate1Stream = null;
let gate2Stream = null;
let availableDevices = [];

document.addEventListener('DOMContentLoaded', async () => {
    await initDualCameras();
});

async function initDualCameras() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        availableDevices = devices.filter(device => device.kind === 'videoinput');

        populateSelects();

        // Default Gate 1 to Camera 0, Gate 2 to Camera 1 if available
        if (availableDevices.length > 0) {
            await startCameraForGate('gate1', availableDevices[0].deviceId);
        }
        if (availableDevices.length > 1) {
            await startCameraForGate('gate2', availableDevices[1].deviceId);
        } else if (availableDevices.length === 1) {
            // If only 1 device (e.g. webcam), stream same device for testing
            await startCameraForGate('gate2', availableDevices[0].deviceId);
        }
    } catch (err) {
        console.error('Error initializing dual cameras:', err);
    }
}

function populateSelects() {
    const sel1 = document.getElementById('gate1CamSelect');
    const sel2 = document.getElementById('gate2CamSelect');
    
    sel1.innerHTML = '';
    sel2.innerHTML = '';

    availableDevices.forEach((dev, idx) => {
        const label = dev.label || `Camera ${idx + 1}`;
        sel1.appendChild(new Option(label, dev.deviceId, false, idx === 0));
        sel2.appendChild(new Option(label, dev.deviceId, false, idx === (availableDevices.length > 1 ? 1 : 0)));
    });
}

async function startCameraForGate(gate, deviceId) {
    const video = document.getElementById(gate === 'gate1' ? 'gate1Video' : 'gate2Video');
    const constraints = {
        video: { deviceId: deviceId ? { exact: deviceId } : undefined, width: { ideal: 1920 }, height: { ideal: 1080 } },
        audio: false
    };

    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        if (gate === 'gate1') {
            if (gate1Stream) gate1Stream.getTracks().forEach(t => t.stop());
            gate1Stream = stream;
        } else {
            if (gate2Stream) gate2Stream.getTracks().forEach(t => t.stop());
            gate2Stream = stream;
        }
        video.srcObject = stream;
    } catch (err) {
        console.error(`Error starting ${gate} camera:`, err);
    }
}

async function switchGateCamera(gate, deviceId) {
    await startCameraForGate(gate, deviceId);
}

async function captureGatePlate(gate) {
    const video = document.getElementById(gate === 'gate1' ? 'gate1Video' : 'gate2Video');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
    const formData = new FormData();
    formData.append('image', blob, `${gate}-capture.jpg`);

    const toast = document.getElementById('liveToast');
    const toastText = document.getElementById('toastText');
    toast.className = 'result-toast active';
    toastText.innerText = `Processing ${gate === 'gate1' ? 'Gate 1 Entry' : 'Gate 2 Exit'} plate detection...`;

    const endpoint = gate === 'gate1' ? '{{ route("plate-ocr.detect") }}' : '{{ route("plate-ocr.exit-detect") }}';
    
    // Create hidden form to submit image
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = endpoint;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    const dataTransfer = new DataTransfer();
    const file = new File([blob], `${gate}_capture.jpg`, { type: 'image/jpeg' });
    dataTransfer.items.add(file);

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.name = 'image';
    fileInput.files = dataTransfer.files;
    form.appendChild(fileInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
