@extends('layouts.app')

@section('title', 'Exit Confirmed - Gate 2')
@section('page-title', 'Gate 2 Exit Checkout Result')

@section('additional-styles')
<style>
    .result-header {
        background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
        color: white;
        padding: 1.75rem 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
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

    .match-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .match-badge.matched {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }

    .match-badge.unmatched {
        background: rgba(255, 255, 255, 0.15);
        color: rgba(255, 255, 255, 0.9);
    }

    /* Cost & Summary Card */
    .cost-summary-box {
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
        color: white;
        border-radius: 14px;
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.3);
    }

    .cost-summary-main h3 {
        font-size: 0.8125rem;
        color: #94A3B8;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .cost-summary-amount {
        font-size: 2.5rem;
        font-weight: 900;
        color: #34D399;
        line-height: 1;
    }

    .cost-summary-details {
        display: flex;
        gap: 1.5rem;
        font-size: 0.875rem;
        color: #CBD5E1;
    }

    .cost-summary-details span {
        display: flex;
        flex-direction: column;
    }

    .cost-summary-details strong {
        color: white;
        font-size: 1rem;
    }

    /* Result Grid */
    .result-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.75rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 992px) {
        .result-grid { grid-template-columns: 1fr; }
        .cost-summary-box { flex-direction: column; text-align: center; }
        .cost-summary-details { justify-content: center; }
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

    .captured-photo {
        width: 100%;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
    }

    .details-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 1rem;
        background: #F8FAFC;
        border-radius: 8px;
        font-size: 0.875rem;
    }

    .payment-qr-box {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        border: 2px dashed #CBD5E1;
        display: inline-block;
        margin: 0.5rem auto 1rem;
    }

    .payment-qr-box img {
        width: 180px;
        height: 180px;
        display: block;
    }

    .action-buttons {
        display: flex;
        gap: 0.85rem;
        flex-wrap: wrap;
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
<!-- Result Header -->
<div class="result-header">
    <h1>
        <i class="ri-checkbox-circle-line"></i>
        Vehicle Exit Confirmed — Gate 2 Barrier Open
        @if($isMatchFound)
            <span class="match-badge matched"><i class="ri-link"></i> Entry Record Matched</span>
        @else
            <span class="match-badge unmatched"><i class="ri-link-unlink"></i> Direct Exit Record</span>
        @endif
    </h1>
    <p>License plate {{ $plateNumber }} successfully processed at Gate 2 checkout</p>
</div>

<!-- Parking Fee Total Cost Summary Banner -->
<div class="cost-summary-box">
    <div class="cost-summary-main">
        <h3>Total Parking Fee</h3>
        <div class="cost-summary-amount">{{ $formattedFee }}</div>
    </div>
    <div class="cost-summary-details">
        <span>
            Time In (Entry)
            <strong>{{ $entryTime ? $entryTime->format('h:i A') : 'N/A' }}</strong>
        </span>
        <span>
            Time Out (Exit)
            <strong>{{ $exitTimestamp->format('h:i A') }}</strong>
        </span>
        <span>
            Duration
            <strong>@if($duration && $duration->d > 0){{ $duration->d }}d @endif{{ $duration ? $duration->h : 0 }}h {{ $duration ? $duration->i : 0 }}m</strong>
        </span>
    </div>
</div>

<div class="result-grid">
    <!-- Captured Photo -->
    <div class="result-card">
        <div class="result-card-header">
            <i class="ri-camera-line" style="color: #F97316;"></i>
            <h3>Gate 2 Exit Photo Capture</h3>
        </div>
        <div class="result-card-body">
            <div style="position: relative;">
                <img src="{{ $imagePath }}" alt="Exit Plate Photo" class="captured-photo">
                <span style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.75); color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.15)">
                    <i class="ri-image-line"></i> Full Vehicle Capture
                </span>
            </div>
            
            @php
                $croppedPath = str_replace('.', '_cropped.', $entry->exit_image_path);
            @endphp
            
            @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($croppedPath))
                <div style="margin-top: 1.5rem; background: #0F172A; border-radius: 12px; border: 1px solid #334155; padding: 1.25rem; position: relative;">
                    <span style="position: absolute; top: -10px; left: 15px; background: #F97316; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
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
                        <i class="ri-cpu-line" style="color: #F97316;"></i> 
                        <strong>Detection:</strong> 
                        <span style="background: #FFEFE6; color: #EA580C; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">YOLOv8</span>
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

    <!-- Exit Summary & Mode of Payment QR -->
    <div class="result-card">
        <div class="result-card-header">
            <i class="ri-qr-code-line" style="color: #2563EB;"></i>
            <h3>Payment QR & Receipt Details</h3>
        </div>
        <div class="result-card-body" style="text-align: center;">
            <div style="margin-bottom: 1rem;">
                <span class="plate-badge" style="font-size: 1.5rem; padding: 0.6rem 1.25rem;">{{ $plateNumber }}</span>
            </div>

            <!-- Payment QR Code -->
            <div class="payment-qr-box">
                <img src="data:image/svg+xml;base64,{{ $paymentQrCode }}" alt="Payment QR Code">
            </div>
            <div style="font-size: 0.78125rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                Mode of Payment & Verification QR Code
            </div>

            <div class="details-list" style="text-align: left;">
                <div class="detail-item">
                    <span style="color: var(--text-secondary);"><i class="ri-login-box-line"></i> Time In (Entry)</span>
                    <strong style="color: var(--text-primary);">{{ $entryTime ? $entryTime->format('M d, Y • h:i:s A') : 'N/A' }}</strong>
                </div>

                <div class="detail-item">
                    <span style="color: var(--text-secondary);"><i class="ri-logout-box-r-line"></i> Time Out (Exit)</span>
                    <strong style="color: var(--text-primary);">{{ $exitTimestamp->format('M d, Y • h:i:s A') }}</strong>
                </div>

                <div class="detail-item">
                    <span style="color: var(--text-secondary);"><i class="ri-timer-line"></i> Total Duration</span>
                    <strong style="color: #F97316;">
                        @if($duration && $duration->d > 0){{ $duration->d }}d @endif{{ $duration ? $duration->h : 0 }}h {{ $duration ? $duration->i : 0 }}m {{ $duration ? $duration->s : 0 }}s
                    </strong>
                </div>

                <div class="detail-item" style="background: #ECFDF5; border: 1px solid #A7F3D0;">
                    <span style="color: #047857; font-weight: 700;"><i class="ri-money-dollar-circle-line"></i> Total Cost</span>
                    <strong style="color: #059669; font-size: 1.1rem;">{{ $formattedFee }}</strong>
                </div>
            </div>

            <div class="action-buttons" style="margin-top: 1.5rem; justify-content: center;">
                <button class="btn btn-exit" onclick="window.print()">
                    <i class="ri-printer-line"></i> Print POS Receipt
                </button>
                <a href="{{ route('plate-ocr.exit-scan') }}" class="btn btn-secondary">
                    <i class="ri-logout-box-r-line"></i> Scan Next Exit
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="ri-dashboard-3-line"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- POS 58mm Thermal Receipt Ticket (Print View Only) -->
<div class="pos-thermal-receipt">
    <!-- Mode of Payment QR Code -->
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="data:image/svg+xml;base64,{{ $paymentQrCode }}" style="width: 150px; height: 150px; display: block; margin: 0 auto;" alt="Payment QR">
        <div style="font-size: 11px; font-weight: 900; margin-top: 4px;">MODE OF PAYMENT</div>
    </div>

    <div style="text-align: center; font-size: 16px; font-weight: 900; font-family: monospace; letter-spacing: 1px; margin-bottom: 8px;">
        PLATE #: {{ $plateNumber }}
    </div>

    <div style="text-align: center; font-size: 12px; margin-bottom: 4px;">
        TIME IN: {{ $entryTime ? $entryTime->format('M d, Y h:i A') : 'N/A' }}
    </div>

    <div style="text-align: center; font-size: 12px; margin-bottom: 4px;">
        TIME OUT: {{ $exitTimestamp->format('M d, Y h:i A') }}
    </div>

    <div style="text-align: center; font-size: 12px; margin-bottom: 8px; font-weight: bold;">
        TIME PARKED: @if($duration && $duration->d > 0){{ $duration->d }}d @endif{{ $duration ? $duration->h : 0 }}h {{ $duration ? $duration->i : 0 }}m
    </div>

    <div style="text-align: center; font-size: 14px; font-weight: 900; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; margin-bottom: 8px;">
        TOTAL COST: {{ $formattedFee }}
    </div>

    <div style="text-align: center; font-size: 10px;">
        <div>THANK YOU FOR PARKING!</div>
        <div>AUTOTRACE PARKING</div>
    </div>
</div>
@endsection

