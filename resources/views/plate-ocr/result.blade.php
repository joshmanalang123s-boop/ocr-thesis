@extends('layouts.app')

@section('title', 'Entry Gate Pass - Gate 1')
@section('page-title', 'Gate 1 Entry Pass Result')

@section('additional-styles')
<style>
    .result-header {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
        padding: 1.75rem 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
    }

    .result-header h1 {
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .result-header p {
        opacity: 0.95;
        font-size: 0.9rem;
    }

    .result-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.75rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 992px) {
        .result-grid { grid-template-columns: 1fr; }
    }

    .result-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .result-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background: #FAFAFA;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .result-card-header h3 {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary);
    }

    .result-card-body {
        padding: 1.5rem;
    }

    .plate-hero-badge {
        background: #0F172A;
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 1.5rem;
        border: 2px solid #334155;
    }

    .plate-hero-badge .number {
        font-size: 2.25rem;
        font-weight: 800;
        font-family: 'Courier New', monospace;
        letter-spacing: 4px;
        margin-bottom: 0.5rem;
        color: #F8FAFC;
    }

    .qr-box {
        background: white;
        padding: 1.25rem;
        border-radius: 12px;
        border: 2px dashed #CBD5E1;
        display: inline-block;
        margin: 0 auto;
    }

    .qr-box img {
        width: 220px;
        height: 220px;
        display: block;
    }

    .captured-photo {
        width: 100%;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
    }

    .action-buttons {
        display: flex;
        gap: 0.85rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }
    
    /* Hide POS thermal ticket element on normal web view */
    .pos-thermal-receipt {
        display: none;
    }

    /* POS 58 Thermal Printer Print Styles */
    @media print {
        /* Hide layout elements */
        .sidebar, .sidebar-overlay, .header {
            display: none !important;
        }

        /* Reset wrappers */
        .main-wrapper, .content, body, html {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }

        /* Hide everything in content except the receipt */
        .content > *:not(.pos-thermal-receipt) {
            display: none !important;
        }
        
        .pos-thermal-receipt {
            display: block !important;
            width: 58mm !important;
            padding: 2mm !important;
            font-family: 'Courier New', monospace, sans-serif !important;
            color: #000 !important;
            background: white !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
        }

        @page {
            size: 58mm auto;
            margin: 0;
        }
    }
</style>
@endsection

@section('content')
<!-- Result Banner -->
<div class="result-header">
    <h1><i class="ri-checkbox-circle-line"></i> Entry Registered — Gate 1 Barrier Open</h1>
    <p>Vehicle plate successfully read and entry QR gate pass issued</p>
</div>

<div class="result-grid">
    <!-- Photo Capture -->
    <div class="result-card">
        <div class="result-card-header">
            <i class="ri-camera-line" style="color: #10B981;"></i>
            <h3>Gate 1 Photo Capture</h3>
        </div>
        <div class="result-card-body">
            <div style="position: relative;">
                <img src="{{ $imagePath }}" alt="License Plate Photo" class="captured-photo">
                <span style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.75); color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.15)">
                    <i class="ri-image-line"></i> Full Vehicle Capture
                </span>
            </div>
            
            @php
                $croppedPath = str_replace('.', '_cropped.', $entry->entry_image_path);
            @endphp
            
            @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($croppedPath))
                <div style="margin-top: 1.5rem; background: #0F172A; border-radius: 12px; border: 1px solid #334155; padding: 1.25rem; position: relative;">
                    <span style="position: absolute; top: -10px; left: 15px; background: #2563EB; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                        YOLOv8 Cropped Plate
                    </span>
                    <div style="display: flex; align-items: center; justify-content: center; background: #1E293B; border-radius: 8px; border: 1px dashed #475569; padding: 0.5rem; height: 90px; overflow: hidden;">
                        <img src="{{ asset('storage/' . $croppedPath) }}" alt="Cropped Plate" style="max-height: 80px; max-width: 100%; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.5)">
                    </div>
                </div>
            @endif

            <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-secondary); background: #F8FAFC; padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid #F1F5F9;">
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <i class="ri-cpu-line" style="color: #2563EB;"></i> 
                        <strong>Detection:</strong> 
                        <span style="background: #E0F2FE; color: #0369A1; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">YOLOv8</span>
                    </span>
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <i class="ri-translate-2" style="color: #10B981;"></i> 
                        <strong>OCR:</strong> 
                        <span style="background: #ECFDF5; color: #047857; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">{{ strtoupper($ocrEngine ?? 'paddleocr') }}</span>
                    </span>
                </div>
                
                <div style="background: #F8FAFC; padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary);">
                        <span><strong>OCR Confidence:</strong></span>
                        <span style="font-weight: 700; color: {{ ($confidence ?? 95) >= 75 ? '#10B981' : '#F59E0B' }}">{{ $confidence ?? '95' }}%</span>
                    </div>
                    <!-- Confidence Progress Bar -->
                    <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;">
                        <div style="width: {{ $confidence ?? 95 }}%; height: 100%; background: {{ ($confidence ?? 95) >= 75 ? 'linear-gradient(90deg, #34D399, #10B981)' : 'linear-gradient(90deg, #FBBF24, #F59E0B)' }}; border-radius: 3px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Pass Ticket -->
    <div class="result-card">
        <div class="result-card-header">
            <i class="ri-qr-code-line" style="color: #4F46E5;"></i>
            <h3>Digital Entry Gate Pass</h3>
        </div>
        <div class="result-card-body" style="text-align: center;">
            <div class="plate-hero-badge">
                <div class="number">{{ $plateNumber }}</div>
                <div style="font-size: 0.78125rem; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em;">
                    Entry Time: {{ $timestamp->format('M d, Y • h:i:s A') }}
                </div>
            </div>

            <div class="qr-box">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Entry QR Code" class="qr-image">
            </div>

            <div class="action-buttons" style="justify-content: center;">
                <button class="btn btn-entry" onclick="downloadQR('{{ $plateNumber }}')">
                    <i class="ri-download-line"></i> Download Pass
                </button>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="ri-printer-line"></i> Print POS Ticket
                </button>
                <a href="{{ route('plate-ocr.index') }}" class="btn btn-secondary">
                    <i class="ri-refresh-line"></i> Next Vehicle
                </a>
            </div>
        </div>
    </div>
</div>

<!-- POS 58mm Thermal Receipt Ticket (Print View Only) -->
<div class="pos-thermal-receipt">
    <!-- Entry Gate Pass QR Code -->
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="data:image/svg+xml;base64,{{ $qrCode }}" style="width: 150px; height: 150px; display: block; margin: 0 auto;" alt="Entry QR">
        <div style="font-size: 11px; font-weight: 900; margin-top: 4px;">ENTRY GATE PASS</div>
    </div>

    <div style="text-align: center; font-size: 16px; font-weight: 900; font-family: monospace; letter-spacing: 1px; margin-bottom: 8px;">
        PLATE #: {{ $plateNumber }}
    </div>

    <div style="text-align: center; font-size: 12px; margin-bottom: 6px;">
        TIME IN: {{ $timestamp->format('M d, Y h:i A') }}
    </div>

    <div style="text-align: center; font-size: 10px; border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px;">
        <div>KEEP TICKET UNTIL CHECKOUT</div>
        <div>AUTOTRACE PARKING</div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
function downloadQR(plateNumber) {
    const link = document.createElement('a');
    const qrImage = document.querySelector('.qr-box img');
    link.href = qrImage.src;
    link.download = 'EntryPass-' + plateNumber + '-' + new Date().getTime() + '.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection

@section('additional-scripts')
<script>
function downloadQR(plateNumber) {
    const link = document.createElement('a');
    const qrImage = document.querySelector('.qr-code-display img');

    link.href = qrImage.src;
    link.download = 'QR-' + plateNumber + '-' + new Date().getTime() + '.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showNotification('✓ QR code downloaded!');
}

function copyQRData() {
    const qrData = document.getElementById('qrData').textContent;
    
    navigator.clipboard.writeText(qrData).then(() => {
        showNotification('✓ QR data copied to clipboard!');
    }).catch(() => {
        alert('Failed to copy');
    });
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'copy-success';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease-in reverse';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 2000);
}

// Print optimization
window.addEventListener('beforeprint', () => {
    console.log('Preparing to print...');
});

window.addEventListener('afterprint', () => {
    console.log('Print completed');
});
</script>
@endsection
