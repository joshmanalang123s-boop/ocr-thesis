@extends('layouts.app')

@section('title', 'Parking System Dashboard')
@section('page-title', 'Parking Facility Overview')

@section('additional-styles')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        border-radius: 16px;
        padding: 1.75rem 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-hero::before {
        content: '';
        position: absolute;
        right: -50px;
        top: -50px;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
        pointer-events: none;
    }

    .hero-content h1 {
        font-size: 1.65rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .hero-content p {
        color: #94A3B8;
        font-size: 0.9rem;
    }

    .hero-gates-status {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .gate-pill {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.625rem 1.125rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        text-decoration: none;
        color: white;
        transition: all 0.2s ease;
        backdrop-filter: blur(4px);
    }

    .gate-pill:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .gate-pill.entry-gate {
        border-left: 4px solid #10B981;
    }

    .gate-pill.exit-gate {
        border-left: 4px solid #F97316;
    }

    .gate-pill-title {
        font-size: 0.75rem;
        color: #94A3B8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .gate-pill-name {
        font-size: 0.875rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Capacity & Stats Bar */
    .capacity-overview {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
    }

    .capacity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .capacity-header h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .capacity-metrics {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        font-weight: 800;
    }

    .capacity-occupied {
        font-size: 1.75rem;
        color: #2563EB;
    }

    .capacity-total {
        font-size: 1.125rem;
        color: var(--text-tertiary);
    }

    .capacity-bar-bg {
        height: 12px;
        background: #F1F5F9;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .capacity-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10B981 0%, #2563EB 70%, #F97316 100%);
        border-radius: 6px;
        transition: width 0.5s ease;
    }

    .capacity-footer {
        display: flex;
        justify-content: space-between;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: var(--card-shadow);
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-shadow-hover);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .stat-icon.primary { background: #EFF6FF; color: #2563EB; }
    .stat-icon.success { background: #ECFDF5; color: #10B981; }
    .stat-icon.warning { background: #FEF3C7; color: #D97706; }
    .stat-icon.info { background: #E0F2FE; color: #0284C7; }
    .stat-icon.danger { background: #FEF2F2; color: #EF4444; }
    .stat-icon.orange { background: #FFF7ED; color: #F97316; }

    .stat-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .stat-footer {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Main Grid */
    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.75rem;
    }

    @media (min-width: 1200px) {
        .content-grid {
            grid-template-columns: 2.3fr 1fr;
        }
    }

    /* Table Card */
    .table-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .table-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        background: #FAFAFA;
    }

    .table-card-title h2 {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -0.01em;
    }

    .table-card-title p {
        font-size: 0.8125rem;
        color: var(--text-secondary);
    }

    .filter-bar {
        padding: 0.85rem 1.5rem;
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
    }

    .filter-bar input,
    .filter-bar select {
        padding: 0.5rem 0.85rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.875rem;
        background: var(--bg-secondary);
        color: var(--text-primary);
    }

    .filter-bar input:focus,
    .filter-bar select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: #F8FAFC;
        border-bottom: 1px solid var(--border-color);
    }

    .data-table th {
        padding: 0.85rem 1.25rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .data-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s ease;
    }

    .data-table tbody tr:hover {
        background: #F1F5F9;
    }

    .data-table td {
        padding: 0.9rem 1.25rem;
        font-size: 0.875rem;
        color: var(--text-primary);
        vertical-align: middle;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        border-radius: 6px;
        font-size: 0.78125rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-badge.entered {
        background: #ECFDF5;
        color: #047857;
        border: 1px solid #A7F3D0;
    }

    .status-badge.exited {
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FFD8A8;
    }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.4rem;
        align-items: center;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
        background: var(--bg-secondary);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        color: var(--text-secondary);
        text-decoration: none;
    }

    .action-btn:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }

    .action-btn.exit-btn:hover {
        background: #F97316;
        border-color: #F97316;
        color: white;
    }

    /* Sidebar Cards */
    .sidebar-widget {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }

    .sidebar-widget-header {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .terminal-quick-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .terminal-quick-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid var(--border-color);
        background: #FAFAFA;
    }

    .terminal-quick-card:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .terminal-quick-card.entry-cam:hover {
        border-color: #10B981;
        background: #ECFDF5;
    }

    .terminal-quick-card.exit-cam:hover {
        border-color: #F97316;
        background: #FFF7ED;
    }

    .terminal-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .terminal-icon-box.entry { background: #ECFDF5; color: #10B981; }
    .terminal-icon-box.exit { background: #FFF7ED; color: #F97316; }

    .terminal-details h4 {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.1rem;
    }

    .terminal-details p {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .recent-exits-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .exit-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0.85rem;
        background: #F8FAFC;
        border-radius: 8px;
        border-left: 3px solid #F97316;
    }

    .exit-item-plate {
        font-weight: 800;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: var(--text-primary);
    }

    .exit-item-time {
        font-size: 0.75rem;
        color: var(--text-tertiary);
    }

    .empty-state {
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--text-tertiary);
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    /* Pagination */
    .pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        background: #FAFAFA;
    }

    /* End Session and Controls */
    .btn-end-session {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-end-session:hover {
        background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.45);
    }
    .btn-session-history {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 1.1rem;
        background: rgba(255, 255, 255, 0.12);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        backdrop-filter: blur(4px);
        transition: all 0.2s ease;
    }
    .btn-session-history:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-2px);
    }
    .session-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 10px;
        color: #34D399;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .live-dot {
        width: 8px;
        height: 8px;
        background: #10B981;
        border-radius: 50%;
        box-shadow: 0 0 8px #10B981;
    }
    .pulse {
        animation: pulseDotAnim 2s infinite;
    }
    @keyframes pulseDotAnim {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.25); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* Modal Overlays & Dialogs */
    .session-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(6px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.25s ease;
    }
    .session-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .session-modal-dialog {
        background: white;
        border-radius: 20px;
        max-width: 650px;
        width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        transform: scale(0.95) translateY(10px);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid var(--border-color);
    }
    .session-modal-overlay.active .session-modal-dialog {
        transform: scale(1) translateY(0);
    }
    .session-modal-header {
        padding: 1.5rem 1.75rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #F8FAFC;
    }
    .session-modal-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .session-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-tertiary);
        cursor: pointer;
        padding: 4px;
        line-height: 1;
        border-radius: 6px;
        transition: color 0.15s ease;
    }
    .session-modal-close:hover {
        color: var(--text-primary);
    }
    .session-modal-body {
        padding: 1.75rem;
    }
    .session-modal-footer {
        padding: 1.25rem 1.75rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        background: #F8FAFC;
    }

    /* Summary Cards Grid */
    .summary-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 560px) {
        .summary-cards-grid {
            grid-template-columns: 1fr;
        }
    }
    .summary-card {
        padding: 1.25rem;
        border-radius: 14px;
        border: 1px solid transparent;
        transition: transform 0.15s ease;
    }
    .summary-card.revenue {
        background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
        border-color: #DDD6FE;
    }
    .summary-card.vehicles {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border-color: #BFDBFE;
    }
    .summary-card.parked {
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border-color: #A7F3D0;
    }
    .summary-card.completed {
        background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%);
        border-color: #FED7AA;
    }
    .summary-card-label {
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    .summary-card.revenue .summary-card-label { color: #6D28D9; }
    .summary-card.vehicles .summary-card-label { color: #1D4ED8; }
    .summary-card.parked .summary-card-label { color: #047857; }
    .summary-card.completed .summary-card-label { color: #C2410C; }

    .summary-card-val {
        font-size: 1.85rem;
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .summary-card.revenue .summary-card-val { color: #5B21B6; }
    .summary-card.vehicles .summary-card-val { color: #1E40AF; }
    .summary-card.parked .summary-card-val { color: #065F46; }
    .summary-card.completed .summary-card-val { color: #9A3412; }

    .summary-meta-strip {
        background: #F8FAFC;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        font-size: 0.85rem;
    }
    .summary-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-secondary);
    }
    .summary-meta-row strong {
        color: var(--text-primary);
    }
</style>
@endsection

@section('content')
<!-- Hero Header -->
<div class="dashboard-hero">
    <div class="hero-content">
        <h1><i class="ri-parking-box-line" style="color: #0EA5E9;"></i> Parking Facility Control Center</h1>
        <p>Real-time vehicle detection, automated gate entry & exit management</p>
    </div>
    <div class="hero-gates-status">
        <div class="session-pill">
            <span class="live-dot pulse"></span>
            <span id="sessionPillText">Session: <strong>{{ $activeSession->session_code ?? 'ACTIVE' }}</strong></span>
        </div>
        <button type="button" class="btn-session-history" onclick="openSessionHistoryModal()" title="View past session records">
            <i class="ri-archive-line"></i>
            <span>Session History</span>
        </button>
        <button type="button" class="btn-end-session" onclick="promptEndSession()" id="btnEndSession">
            <i class="ri-stop-circle-fill"></i>
            <span>End Session</span>
        </button>
        <a href="{{ route('plate-ocr.index') }}" class="gate-pill entry-gate">
            <div>
                <div class="gate-pill-title">Gate 1 • Entry</div>
                <div class="gate-pill-name"><i class="ri-login-box-line" style="color: #10B981;"></i> Camera Live</div>
            </div>
        </a>
        <a href="{{ route('plate-ocr.exit-scan') }}" class="gate-pill exit-gate">
            <div>
                <div class="gate-pill-title">Gate 2 • Exit</div>
                <div class="gate-pill-name"><i class="ri-logout-box-r-line" style="color: #F97316;"></i> Camera Live</div>
            </div>
        </a>
    </div>
</div>

@php
    $maxCapacity = 50; // Total parking slots standard
    $occupiedCount = $currentlyInside ?? 0;
    $availableSlots = max(0, $maxCapacity - $occupiedCount);
    $occupancyPercent = min(100, round(($occupiedCount / $maxCapacity) * 100));
@endphp

<!-- Capacity Overview Card -->
<div class="capacity-overview">
    <div class="capacity-header">
        <h3><i class="ri-dashboard-2-line" style="color: #2563EB;"></i> Live Parking Capacity</h3>
        <div class="capacity-metrics">
            <span class="capacity-occupied" id="capacityOccupiedText">{{ $occupiedCount }}</span>
            <span class="capacity-total">/ {{ $maxCapacity }} Slots Occupied</span>
        </div>
    </div>
    <div class="capacity-bar-bg">
        <div class="capacity-bar-fill" id="capacityBarFill" style="width: {{ $occupancyPercent }}%;"></div>
    </div>
    <div class="capacity-footer">
        <span><strong style="color: #10B981;" id="capacityAvailableText">{{ $availableSlots }} Slots Available</strong></span>
        <span>Occupancy Rate: <strong id="capacityPercentText">{{ $occupancyPercent }}%</strong></span>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <!-- Active Session Revenue -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #F5F3FF; color: #8B5CF6;"><i class="ri-wallet-3-line"></i></div>
        </div>
        <div class="stat-title">Session Revenue</div>
        <div class="stat-value" id="statSessionRevenue">₱{{ number_format($sessionRevenue ?? 0, 2) }}</div>
        <div class="stat-footer">
            <span style="color: #8B5CF6; font-weight:700;"><i class="ri-money-dollar-circle-fill"></i> Inflow</span>
            <span>Active collected fees</span>
        </div>
    </div>

    <!-- Total Vehicles -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon primary"><i class="ri-car-line"></i></div>
        </div>
        <div class="stat-title">Total Vehicles</div>
        <div class="stat-value" id="statTotalVehicles">{{ $total ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #2563EB; font-weight:700;"><i class="ri-login-circle-fill"></i> Active</span>
            <span>Entered this session</span>
        </div>
    </div>

    <!-- Currently Parked -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon success"><i class="ri-parking-box-line"></i></div>
        </div>
        <div class="stat-title">Currently Parked</div>
        <div class="stat-value" id="statCurrentlyParked">{{ $currentlyInside ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #10B981; font-weight:700;">On premises</span>
            <span>Occupying slots</span>
        </div>
    </div>

    <!-- Completed Sessions -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon orange"><i class="ri-checkbox-circle-line"></i></div>
        </div>
        <div class="stat-title">Completed Sessions</div>
        <div class="stat-value" id="statCompletedSessions">{{ $completedSessions ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #F97316; font-weight:700;">Completed</span>
            <span>Entered & exited</span>
        </div>
    </div>

    <!-- OCR Accuracy -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon warning"><i class="ri-focus-3-line"></i></div>
        </div>
        <div class="stat-title">OCR Accuracy</div>
        <div class="stat-value" id="statOcrAccuracy">{{ $successRate ?? 98.5 }}%</div>
        <div class="stat-footer">
            <span style="color: #10B981; font-weight:700;">Optimal</span>
            <span>AI Recognition Rate</span>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="content-grid">
    <!-- Detection History Table -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <h2>Real-Time Gate Activity Log</h2>
                <p>Live stream of incoming and outgoing vehicle detections</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('plate-ocr.index') }}" class="btn btn-entry">
                    <i class="ri-camera-line"></i>
                    <span>Entry Cam</span>
                </a>
                <a href="{{ route('plate-ocr.exit-scan') }}" class="btn btn-exit">
                    <i class="ri-logout-box-r-line"></i>
                    <span>Exit Cam</span>
                </a>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="{{ route('dashboard') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; flex: 1;">
                <input
                    type="text"
                    name="plate"
                    value="{{ request('plate') }}"
                    placeholder="Search plate number..."
                    style="flex: 1; min-width: 200px;"
                />
                <select name="date_filter">
                    <option value="" {{ request('date_filter') == '' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>This Month</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i> Filter</button>
            </form>
        </div>

        <div class="table-wrapper" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehicle Plate</th>
                        <th>Detection Timestamp</th>
                        <th>Status</th>
                        <th>Gate Action</th>
                    </tr>
                </thead>
                <tbody id="gateActivityTbody">
                    @forelse ($entries as $entry)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @php
                                        $displayImage = null;
                                        $croppedPath = $entry->entry_image_path ? str_replace('.', '_cropped.', $entry->entry_image_path) : null;
                                        $exitCroppedPath = $entry->exit_image_path ? str_replace('.', '_cropped.', $entry->exit_image_path) : null;

                                        if ($croppedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($croppedPath)) {
                                            $displayImage = asset('storage/' . $croppedPath);
                                        } elseif ($entry->entry_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($entry->entry_image_path)) {
                                            $displayImage = asset('storage/' . $entry->entry_image_path);
                                        } elseif ($exitCroppedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($exitCroppedPath)) {
                                            $displayImage = asset('storage/' . $exitCroppedPath);
                                        } elseif ($entry->exit_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($entry->exit_image_path)) {
                                            $displayImage = asset('storage/' . $entry->exit_image_path);
                                        }
                                    @endphp
                                    
                                    @if($displayImage)
                                        <div class="plate-thumbnail-container" onclick="openPlatePreview('{{ $displayImage }}', '{{ $entry->plate_number }}')" title="Click to view plate photo for {{ $entry->plate_number }}">
                                            <img src="{{ $displayImage }}" alt="Plate Photo {{ $entry->plate_number }}" class="plate-thumbnail-img">
                                        </div>
                                    @else
                                        <div class="plate-thumbnail-container" title="Plate: {{ $entry->plate_number }}">
                                            <div class="plate-graphic-fallback">
                                                <span class="plate-sub-text">AUTOTRACE</span>
                                                <span class="plate-main-text">{{ $entry->plate_number }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <span class="plate-badge" style="margin: 0; font-family: 'Courier New', monospace; font-weight: 800; letter-spacing: 1px;">{{ $entry->plate_number }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    {{ optional($entry->entry_time)->format('M d, Y') ?? '—' }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-tertiary);">
                                    <i class="ri-time-line"></i> {{ optional($entry->entry_time)->format('h:i A') ?? '' }}
                                </div>
                            </td>
                            <td>
                                @if($entry->status === 'exited')
                                    <span class="status-badge exited"><i class="ri-logout-box-r-line"></i> Exited</span>
                                @else
                                    <span class="status-badge entered"><i class="ri-login-box-line"></i> Parked (Entered)</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-btns">
                                    @if($entry->status === 'entered')
                                        <form method="POST" action="{{ route('plate-ocr.exit', $entry->id) }}" style="display:inline;" onsubmit="return confirm('Mark vehicle {{ $entry->plate_number }} as EXITED?')">
                                            @csrf
                                            <button type="submit" class="action-btn exit-btn" title="Manual Exit Gate Checkout"><i class="ri-logout-box-r-line"></i></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('plate-ocr.download', ['plate' => $entry->plate_number, 'timestamp' => optional($entry->entry_time)->format('Y-m-d H:i:s')]) }}"
                                       class="action-btn"
                                       title="Download QR Gate Pass"><i class="ri-download-line"></i></a>
                                    <button type="button" class="action-btn" title="Print Ticket" onclick="printTicket('{{ route('plate-ocr.print', $entry->id) }}')"><i class="ri-printer-line"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ri-car-line"></i></div>
                                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">No Gate Activity Recorded</h3>
                                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">Position vehicle at Gate 1 or Gate 2 camera to detect license plates.</p>
                                    <a href="{{ route('plate-ocr.index') }}" class="btn btn-entry">
                                        <i class="ri-camera-line"></i>
                                        <span>Open Gate 1 (Entry Cam)</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="tablePaginationContainer">
            @if($entries->count() > 0)
                <div class="pagination">
                    <div style="font-size: 0.8125rem; color: var(--text-secondary);">
                        Showing {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} gate detections in active session
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        @if($entries->onFirstPage())
                            <button class="btn btn-secondary" disabled style="opacity: 0.5;">Previous</button>
                        @else
                            <a href="{{ $entries->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
                        @endif

                        @if($entries->hasMorePages())
                            <a href="{{ $entries->nextPageUrl() }}" class="btn btn-secondary">Next</a>
                        @else
                            <button class="btn btn-secondary" disabled style="opacity: 0.5;">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Terminals Sidebar -->
    <div>
        <div class="sidebar-widget">
            <div class="sidebar-widget-header">
                <i class="ri-sensor-line" style="color: #2563EB;"></i> Gate Terminals
            </div>
            <div class="terminal-quick-grid">
                <a href="{{ route('plate-ocr.index') }}" class="terminal-quick-card entry-cam">
                    <div class="terminal-icon-box entry"><i class="ri-login-box-line"></i></div>
                    <div class="terminal-details">
                        <h4>Gate 1 • Entry Camera</h4>
                        <p>Scan incoming vehicle & issue QR</p>
                    </div>
                </a>
                <a href="{{ route('plate-ocr.exit-scan') }}" class="terminal-quick-card exit-cam">
                    <div class="terminal-icon-box exit"><i class="ri-logout-box-r-line"></i></div>
                    <div class="terminal-details">
                        <h4>Gate 2 • Exit Camera</h4>
                        <p>Scan outgoing vehicle & checkout</p>
                    </div>
                </a>
                <a href="{{ route('plate-ocr.history') }}" class="terminal-quick-card">
                    <div class="terminal-icon-box" style="background: #EFF6FF; color: #2563EB;"><i class="ri-history-line"></i></div>
                    <div class="terminal-details">
                        <h4>Parking Logs Report</h4>
                        <p>Full archive & csv export</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recently Exited Stream -->
        <div class="sidebar-widget">
            <div class="sidebar-widget-header">
                <i class="ri-logout-box-r-line" style="color: #F97316;"></i> Recent Checkout Exits
            </div>
            <div class="recent-exits-list" id="recentExitsList">
                @forelse($recentExited as $exited)
                    <div class="exit-item">
                        <div>
                            <div class="exit-item-plate">{{ $exited->plate_number }}</div>
                            <div class="exit-item-time">
                                <i class="ri-time-line"></i> {{ optional($exited->exit_time)->format('M d, h:i A') ?? '—' }}
                            </div>
                        </div>
                        <span class="status-badge exited" style="font-size: 0.7rem;">Exited</span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 1.5rem 1rem; color: var(--text-tertiary); font-size: 0.8125rem;">
                        <i class="ri-car-line" style="font-size: 1.75rem; display: block; margin-bottom: 0.25rem; opacity: 0.4;"></i>
                        No vehicles have exited yet in this session
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- End Session Confirmation Modal -->
<div class="session-modal-overlay" id="confirmEndModal">
    <div class="session-modal-dialog" style="max-width: 480px;">
        <div class="session-modal-header" style="background: #FEF2F2; border-bottom: 1px solid #FECACA;">
            <div class="session-modal-title" style="color: #991B1B;">
                <i class="ri-alert-line" style="font-size: 1.35rem;"></i>
                <span>Confirm End Session</span>
            </div>
            <button class="session-modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="session-modal-body" style="text-align: center; padding: 2rem 1.75rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.25rem;">
                <i class="ri-stop-circle-fill"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.6rem;">
                End Current Parking Session?
            </h3>
            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 1.25rem;">
                Are you sure you want to end this parking session? This will generate the session summary and start a new session.
            </p>
            <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.85rem 1rem; font-size: 0.8rem; color: var(--text-tertiary); text-align: left;">
                <i class="ri-information-line" style="color: #2563EB;"></i> 
                All vehicles from this session will be preserved in the archive logs. The active dashboard counters will reset to 0 for the incoming shift.
            </div>
        </div>
        <div class="session-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="btnConfirmEndSession" onclick="executeEndSession()" style="background: #DC2626; color: white;">
                <i class="ri-check-line"></i>
                <span id="btnConfirmEndText">Confirm & End Session</span>
            </button>
        </div>
    </div>
</div>

<!-- Session Summary Modal -->
<div class="session-modal-overlay" id="sessionSummaryModal">
    <div class="session-modal-dialog">
        <div class="session-modal-header" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; border-bottom: none;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="background: rgba(16, 185, 129, 0.2); color: #34D399; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.4);">
                        <i class="ri-checkbox-circle-fill"></i> SESSION FINALIZED
                    </span>
                    <span id="summarySessionCodeBadge" style="color: #94A3B8; font-family: monospace; font-size: 0.8rem; font-weight: 600;">SES-001</span>
                </div>
                <div class="session-modal-title" style="color: white; font-size: 1.35rem;">
                    Parking Session Summary
                </div>
            </div>
            <button class="session-modal-close" style="color: #94A3B8;" onclick="closeSummaryAndStartNew()">&times;</button>
        </div>

        <div class="session-modal-body">
            <!-- Session Timestamps Banner -->
            <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <div style="font-size: 0.72rem; color: var(--text-tertiary); font-weight: 600; text-transform: uppercase;">Session Start Time</div>
                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-primary);" id="summaryStartTime">—</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.72rem; color: var(--text-tertiary); font-weight: 600; text-transform: uppercase;">Session End Time</div>
                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-primary);" id="summaryEndTime">—</div>
                </div>
            </div>

            <!-- Large 4-Card Summary Grid -->
            <div class="summary-cards-grid">
                <!-- Total Revenue -->
                <div class="summary-card revenue">
                    <div class="summary-card-label">
                        <i class="ri-wallet-3-line"></i> TOTAL REVENUE
                    </div>
                    <div class="summary-card-val" id="summaryRevenue">₱0.00</div>
                    <div style="font-size: 0.75rem; color: #7C3AED; margin-top: 0.25rem; font-weight: 600;">
                        Collected during session
                    </div>
                </div>

                <!-- Total Vehicles -->
                <div class="summary-card vehicles">
                    <div class="summary-card-label">
                        <i class="ri-car-line"></i> TOTAL VEHICLES
                    </div>
                    <div class="summary-card-val" id="summaryVehicles">0 Cars</div>
                    <div style="font-size: 0.75rem; color: #2563EB; margin-top: 0.25rem; font-weight: 600;">
                        Entered parking lot
                    </div>
                </div>

                <!-- Currently Parked -->
                <div class="summary-card parked">
                    <div class="summary-card-label">
                        <i class="ri-parking-box-line"></i> CURRENTLY PARKED
                    </div>
                    <div class="summary-card-val" id="summaryParked">0 Cars</div>
                    <div style="font-size: 0.75rem; color: #059669; margin-top: 0.25rem; font-weight: 600;">
                        Vehicles inside parking lot
                    </div>
                </div>

                <!-- Completed Parking Sessions -->
                <div class="summary-card completed">
                    <div class="summary-card-label">
                        <i class="ri-checkbox-circle-line"></i> COMPLETED SESSIONS
                    </div>
                    <div class="summary-card-val" id="summaryCompleted">0 Cars</div>
                    <div style="font-size: 0.75rem; color: #EA580C; margin-top: 0.25rem; font-weight: 600;">
                        Entered & checked out
                    </div>
                </div>
            </div>

            <!-- Accumulated Parking Duration -->
            <div class="summary-meta-strip">
                <div class="summary-meta-row">
                    <span><i class="ri-timer-line" style="color: #F59E0B;"></i> Total Parking Duration</span>
                    <strong style="color: #F59E0B; font-size: 1rem;" id="summaryDuration">0m</strong>
                </div>
                <div class="summary-meta-row" style="font-size: 0.78rem; border-top: 1px dashed var(--border-color); padding-top: 0.5rem;">
                    <span><i class="ri-database-2-line" style="color: #10B981;"></i> Archival Status</span>
                    <strong style="color: #10B981;">Saved to Historical Database Records</strong>
                </div>
            </div>
        </div>

        <div class="session-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="ri-printer-line"></i> Print Summary
            </button>
            <button type="button" class="btn btn-primary" onclick="closeSummaryAndStartNew()" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border-color: #059669; padding: 0.65rem 1.4rem; font-weight: 800;">
                <i class="ri-restart-line"></i> Close / Start New Session
            </button>
        </div>
    </div>
</div>

<!-- Session History Archive Modal -->
<div class="session-modal-overlay" id="sessionHistoryModal">
    <div class="session-modal-dialog" style="max-width: 800px;">
        <div class="session-modal-header">
            <div class="session-modal-title">
                <i class="ri-archive-line" style="color: #2563EB;"></i>
                <span>Historical Parking Sessions</span>
            </div>
            <button class="session-modal-close" onclick="closeSessionHistoryModal()">&times;</button>
        </div>
        <div class="session-modal-body" style="max-height: 480px; overflow-y: auto; padding: 1.25rem;">
            <div id="sessionHistoryLoading" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                <i class="ri-loader-4-line ri-spin" style="font-size: 1.8rem;"></i>
                <p style="margin-top: 0.5rem; font-size: 0.85rem;">Loading session archives...</p>
            </div>
            <div id="sessionHistoryContent" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Time Window</th>
                            <th>Total Vehicles</th>
                            <th>Exits</th>
                            <th>Parked</th>
                            <th>Total Revenue</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody id="sessionHistoryTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="session-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSessionHistoryModal()">Close</button>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

function promptEndSession() {
    document.getElementById('confirmEndModal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmEndModal').classList.remove('active');
}

async function executeEndSession() {
    const btn = document.getElementById('btnConfirmEndSession');
    const btnText = document.getElementById('btnConfirmEndText');
    btn.disabled = true;
    btnText.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Finalizing...';

    try {
        const response = await fetch("{{ route('parking-session.end') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({})
        });

        const data = await response.json();

        if (data.success) {
            closeConfirmModal();

            // Populate summary modal
            document.getElementById('summarySessionCodeBadge').textContent = data.summary.session_code;
            document.getElementById('summaryStartTime').textContent = data.summary.started_at;
            document.getElementById('summaryEndTime').textContent = data.summary.ended_at;
            document.getElementById('summaryRevenue').textContent = data.summary.formatted_revenue;
            document.getElementById('summaryVehicles').textContent = data.summary.total_vehicles + ' Cars';
            document.getElementById('summaryParked').textContent = data.summary.currently_parked + ' Cars';
            document.getElementById('summaryCompleted').textContent = data.summary.completed_sessions + ' Cars';
            document.getElementById('summaryDuration').textContent = data.summary.formatted_duration;

            // Open Summary Modal
            document.getElementById('sessionSummaryModal').classList.add('active');

            // Reset Dashboard in-place smoothly
            resetDashboardState(data.new_session);
        } else {
            alert('Error ending session: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        console.error('Failed to end session:', err);
        alert('Network or server error while ending session: ' + err.message);
    } finally {
        btn.disabled = false;
        btnText.innerHTML = 'Confirm & End Session';
    }
}

function resetDashboardState(newSession) {
    // 1. Reset Stats Grid Counters
    const revEl = document.getElementById('statSessionRevenue');
    if (revEl) revEl.textContent = '₱0.00';

    const vehEl = document.getElementById('statTotalVehicles');
    if (vehEl) vehEl.textContent = '0';

    const parkedEl = document.getElementById('statCurrentlyParked');
    if (parkedEl) parkedEl.textContent = '0';

    const compEl = document.getElementById('statCompletedSessions');
    if (compEl) compEl.textContent = '0';

    // 2. Reset Capacity Bar
    const occEl = document.getElementById('capacityOccupiedText');
    if (occEl) occEl.textContent = '0';

    const availEl = document.getElementById('capacityAvailableText');
    if (availEl) availEl.textContent = '50 Slots Available';

    const pctEl = document.getElementById('capacityPercentText');
    if (pctEl) pctEl.textContent = '0%';

    const fillEl = document.getElementById('capacityBarFill');
    if (fillEl) fillEl.style.width = '0%';

    // 3. Clear Table
    const tbody = document.getElementById('gateActivityTbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="ri-car-line"></i></div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">No Gate Activity Recorded in This Session</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">Gate 1 and Gate 2 cameras are active and ready to detect arriving and departing vehicles.</p>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="{{ route('plate-ocr.index') }}" class="btn btn-entry">
                                <i class="ri-camera-line"></i>
                                <span>Gate 1 (Entry Cam)</span>
                            </a>
                            <a href="{{ route('plate-ocr.exit-scan') }}" class="btn btn-exit">
                                <i class="ri-logout-box-r-line"></i>
                                <span>Gate 2 (Exit Cam)</span>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    // 4. Hide pagination
    const pag = document.getElementById('tablePaginationContainer');
    if (pag) pag.style.display = 'none';

    // 5. Clear Recent Exits List
    const exitsList = document.getElementById('recentExitsList');
    if (exitsList) {
        exitsList.innerHTML = `
            <div style="text-align: center; padding: 1.5rem 1rem; color: var(--text-tertiary); font-size: 0.8125rem;">
                <i class="ri-car-line" style="font-size: 1.75rem; display: block; margin-bottom: 0.25rem; opacity: 0.4;"></i>
                No vehicles have exited yet in this session
            </div>
        `;
    }

    // 6. Update Active Session Pill
    const pill = document.getElementById('sessionPillText');
    if (pill && newSession) {
        pill.innerHTML = 'Session: <strong>' + newSession.session_code + '</strong>';
    }
}

function closeSummaryAndStartNew() {
    document.getElementById('sessionSummaryModal').classList.remove('active');
}

async function openSessionHistoryModal() {
    const modal = document.getElementById('sessionHistoryModal');
    const loading = document.getElementById('sessionHistoryLoading');
    const content = document.getElementById('sessionHistoryContent');
    const tbody = document.getElementById('sessionHistoryTableBody');

    modal.classList.add('active');
    loading.style.display = 'block';
    content.style.display = 'none';

    try {
        const response = await fetch("{{ route('parking-session.history') }}");
        const data = await response.json();

        loading.style.display = 'none';
        content.style.display = 'block';

        if (data.sessions && data.sessions.length > 0) {
            tbody.innerHTML = data.sessions.map(s => `
                <tr>
                    <td><strong style="font-family: monospace; color: var(--primary-color);">${s.session_code}</strong></td>
                    <td>
                        <div style="font-weight: 600; font-size: 0.8rem;">${s.started_at}</div>
                        <div style="font-size: 0.725rem; color: var(--text-tertiary);">to ${s.ended_at}</div>
                    </td>
                    <td><strong>${s.total_vehicles}</strong> cars</td>
                    <td><span style="color: #F97316; font-weight: 700;">${s.completed_sessions}</span></td>
                    <td><span style="color: #10B981; font-weight: 700;">${s.currently_parked}</span></td>
                    <td><strong style="color: #059669;">${s.total_revenue}</strong></td>
                    <td>${s.duration}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                        No ended sessions archived yet.
                    </td>
                </tr>
            `;
        }
    } catch (err) {
        loading.innerHTML = '<p style="color: #EF4444;">Failed to load session history: ' + err.message + '</p>';
    }
}

function closeSessionHistoryModal() {
    document.getElementById('sessionHistoryModal').classList.remove('active');
}

function printTicket(url) {
    // Remove existing iframe if present
    let oldFrame = document.getElementById('hiddenPrintFrame');
    if (oldFrame) oldFrame.remove();

    // Create a new iframe that is small so it doesn't stretch the print layout height
    let frame = document.createElement('iframe');
    frame.id = 'hiddenPrintFrame';
    frame.style.position = 'fixed';
    frame.style.top = '0';
    frame.style.left = '0';
    frame.style.width = '58mm';
    frame.style.height = '10px';
    frame.style.zIndex = '-9999';
    frame.style.opacity = '0';
    frame.style.pointerEvents = 'none';
    
    frame.onload = function() {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    };
    
    frame.src = url;
    document.body.appendChild(frame);
}
</script>
@endsection

@section('additional-scripts')
<script>
    function viewDetails(id) {
        alert('Gate Record #' + id + ' detail overview.');
    }
</script>
@endsection

