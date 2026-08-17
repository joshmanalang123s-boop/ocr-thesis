<script>
class PlateOcrCamera {
    constructor() {
        this.video = document.getElementById('cameraVideo');
        this.canvas = document.createElement('canvas');
        this.stream = null;
        this.isCameraActive = false;
        this.isCapturing = false;

        this.init();
    }

    init() {
        document.getElementById('captureBtn').addEventListener('click', () => this.capture());
        document.getElementById('toggleCameraBtn').addEventListener('click', () => this.toggleCamera());
        document.getElementById('plateUpload').addEventListener('change', (e) => this.onFileSelect(e));

        // Request camera permission on load
        this.startCamera();
    }

    async startCamera() {
        try {
            const constraints = {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            this.isCameraActive = true;

            // Show torch button if available
            const track = this.stream.getVideoTracks()[0];
            if (track && track.getCapabilities && track.getCapabilities().torch) {
                document.getElementById('torchToggle').style.display = 'block';
                document.getElementById('torchToggle').addEventListener('click', () => this.toggleTorch());
            }

            document.getElementById('toggleCameraBtn').textContent = '🎥 Stop Camera';

        } catch (error) {
            console.error('Camera access denied:', error);
            this.showStatus('📷 Camera not available. Please use upload instead.', 'error');
        }
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.isCameraActive = false;
            document.getElementById('toggleCameraBtn').textContent = '🎥 Start Camera';
        }
    }

    toggleCamera() {
        if (this.isCameraActive) {
            this.stopCamera();
        } else {
            this.startCamera();
        }
    }

    async toggleTorch() {
        try {
            const track = this.stream.getVideoTracks()[0];
            const settings = track.getSettings();

            await track.applyConstraints({
                advanced: [{ torch: !settings.torch }]
            });

            const btn = document.getElementById('torchToggle');
            btn.textContent = settings.torch ? '💡 Torch Off' : '💡 Torch On';
        } catch (error) {
            console.error('Torch control failed:', error);
        }
    }

    capture() {
        if (!this.isCameraActive || this.isCapturing) return;

        this.isCapturing = true;
        this.showStatus('Processing plate...', 'active');

        // Draw video frame to canvas
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;

        const ctx = this.canvas.getContext('2d');
        ctx.scale(-1, 1);
        ctx.drawImage(this.video, -this.canvas.width, 0);

        // Convert canvas to blob and submit
        this.canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('image', blob, 'plate-capture.jpg');
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            fetch('{{ route("plate-ocr.detect") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = response.url;
                } else {
                    throw new Error('Detection failed');
                }
            })
            .catch(error => {
                this.showStatus('❌ Error: ' + error.message, 'error');
                this.isCapturing = false;
            });

        }, 'image/jpeg', 0.9);
    }

    onFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('uploadFileName').textContent = 'Selected: ' + file.name;
        }
    }

    showStatus(message, type = 'active') {
        const status = document.getElementById('detectionStatus');
        status.textContent = message;
        status.className = 'detection-status ' + type;
        
        if (type !== 'active') {
            setTimeout(() => {
                status.classList.remove('active', 'error', 'success');
            }, 3000);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    new PlateOcrCamera();
});
</script>
