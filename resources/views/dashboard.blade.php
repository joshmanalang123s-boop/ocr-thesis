@extends('layouts.app')

@section('title', 'Parking System Dashboard')
@section('page-title', 'Parking Facility Overview')

@section('additional-styles')
<style>
    /* ══════════════════════════════════════════════════════════════
       MINIMALIST BLACK & WHITE DASHBOARD THEME (SABINO)
       ══════════════════════════════════════════════════════════════ */
    :root {
        --dash-bg: #FFFFFF;
        --card-bg: #FFFFFF;
        --card-border: #E5E7EB;
        --card-border-hover: #D1D5DB;
        --text-head: #111827;
        --text-body: #4B5563;
        --text-muted: #9CA3AF;
        --primary-black: #111827;
        --status-green: #10B981;
        --status-red: #EF4444;
        --card-radius: 8px;
    }

    /* ─── Minimalist Action Toolbar ─────────────────────────────── */
    .dashboard-action-toolbar {
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        border-radius: var(--card-radius);
        padding: 0.75rem 1.25rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .toolbar-left {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .toolbar-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .session-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.3rem 0.65rem;
        background: #F9FAFB;
        border: 1px solid var(--card-border);
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #111827;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #10B981;
        display: inline-block;
        flex-shrink: 0;
    }

    .grace-rule-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        background: #F9FAFB;
        border: 1px solid var(--card-border);
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #111827;
    }

    .grace-rule-pill i {
        color: #10B981;
    }

    /* ─── Topbar Action Buttons ─────────────────────────────────── */
    .btn-topbar {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        line-height: 1.25;
    }

    .btn-topbar.primary-fee {
        background: #111827;
        color: #FFFFFF;
        border: 1px solid #111827;
    }

    .btn-topbar.primary-fee:hover {
        background: #000000;
        border-color: #000000;
    }

    .btn-topbar.outline {
        background: #FFFFFF;
        color: #111827;
        border: 1px solid var(--card-border);
    }

    .btn-topbar.outline:hover {
        background: #F9FAFB;
        border-color: #D1D5DB;
    }

    .btn-topbar.soft-danger {
        background: #FFFFFF;
        color: #EF4444;
        border: 1px solid #FCA5A5;
    }

    .btn-topbar.soft-danger:hover {
        background: #FEF2F2;
    }

    /* ─── Gate 1 & Gate 2 Status Bar (Requirement 3) ─────────────── */
    .gates-status-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    @media (max-width: 768px) {
        .gates-status-row { grid-template-columns: 1fr; }
    }

    .gate-status-card {
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        border-radius: var(--card-radius);
        padding: 0.95rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .gate-card-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .gate-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.55rem;
        background: #F9FAFB;
        border: 1px solid var(--card-border);
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #111827;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .gate-title-wrap h3 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .gate-title-wrap p {
        font-size: 0.75rem;
        color: #6B7280;
        margin: 2px 0 0 0;
    }

    .btn-gate-quick {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.85rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #111827;
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        text-decoration: none;
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .btn-gate-quick:hover {
        background: #F9FAFB;
        border-color: #111827;
    }

    /* ─── Metric Cards Grid (Minimalist Black & White) ──────────── */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1400px) {
        .kpi-row { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .kpi-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .kpi-row { grid-template-columns: 1fr; }
    }

    .kpi-card-clean {
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        border-radius: var(--card-radius);
        padding: 1.15rem 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 145px;
        transition: border-color 0.15s ease;
    }

    .kpi-card-clean:hover {
        border-color: var(--card-border-hover);
    }

    .kpi-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .kpi-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6B7280;
    }

    .kpi-icon-pill {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: #F9FAFB;
        border: 1px solid var(--card-border);
        color: #111827;
    }

    .kpi-main-val {
        font-size: 1.75rem;
        font-weight: 800;
        color: #111827;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 0.35rem;
    }

    .kpi-subtext {
        font-size: 0.75rem;
        color: #6B7280;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .kpi-progress-wrap {
        width: 100%;
        height: 5px;
        background: #F3F4F6;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.4rem;
    }

    .kpi-progress-bar {
        height: 100%;
        background: #111827;
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    /* ─── Main Content Layout (Table + Quick Sidebar) ──────────── */
    .dashboard-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 1.25rem;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .dashboard-layout { grid-template-columns: 1fr; }
    }

    /* ─── Clean Table Card ──────────────────────────────────────── */
    .activity-card {
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        border-radius: var(--card-radius);
        overflow: hidden;
    }

    .activity-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        background: #FFFFFF;
    }

    .activity-header-title h2 {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .activity-header-title p {
        font-size: 0.75rem;
        color: #6B7280;
        margin: 2px 0 0 0;
    }

    /* Clean Filter Bar */
    .table-filter-bar {
        padding: 0.75rem 1.25rem;
        background: #F9FAFB;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .filter-form-clean {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        flex-wrap: wrap;
    }

    .input-search-clean {
        flex: 1;
        min-width: 200px;
        padding: 0.45rem 0.75rem;
        border: 1px solid var(--card-border);
        border-radius: 6px;
        font-size: 0.8125rem;
        background: #FFFFFF;
        color: #111827;
    }

    .input-search-clean:focus {
        outline: none;
        border-color: #111827;
    }

    .select-clean {
        padding: 0.45rem 0.75rem;
        border: 1px solid var(--card-border);
        border-radius: 6px;
        font-size: 0.8125rem;
        background: #FFFFFF;
        color: #111827;
        cursor: pointer;
    }

    .btn-filter-clean {
        padding: 0.45rem 0.85rem;
        border-radius: 6px;
        background: #111827;
        color: #FFFFFF;
        font-weight: 600;
        font-size: 0.8125rem;
        border: 1px solid #111827;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: background 0.15s ease;
    }

    .btn-filter-clean:hover {
        background: #000000;
    }

    /* Clean Modern Table */
    .clean-table-wrap {
        overflow-x: auto;
    }

    .clean-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }

    .clean-table th {
        background: #F9FAFB;
        padding: 0.65rem 1rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--card-border);
        white-space: nowrap;
    }

    .clean-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--card-border);
        color: #111827;
        vertical-align: middle;
    }

    .clean-table tr:last-child td {
        border-bottom: none;
    }

    .clean-table tr:hover td {
        background: #F9FAFB;
    }

    /* License Plate Chip */
    .plate-chip {
        display: inline-flex;
        align-items: center;
        background: #FFFFFF;
        border: 1px solid #111827;
        border-radius: 4px;
        padding: 0.2rem 0.55rem;
    }

    .plate-chip-text {
        font-family: ui-monospace, SFMono-Regular, monospace;
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.06em;
        color: #111827;
    }

    /* Badges */
    .badge-soft-green {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 600;
        background: #F9FAFB;
        color: #111827;
        border: 1px solid var(--card-border);
        white-space: nowrap;
    }

    .badge-soft-slate {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 500;
        background: #F9FAFB;
        color: #4B5563;
        border: 1px solid var(--card-border);
        white-space: nowrap;
    }

    .badge-soft-amber {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 600;
        background: #FFFBEB;
        color: #B45309;
        border: 1px solid #FDE68A;
        cursor: pointer;
        white-space: nowrap;
    }

    /* Table Action Icon Buttons */
    .table-actions-group {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .btn-action-icon {
        width: 28px;
        height: 28px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        color: #374151;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .btn-action-icon:hover {
        background: #F3F4F6;
        color: #111827;
        border-color: #9CA3AF;
    }

    .btn-action-icon.exit-action:hover {
        background: #FEF2F2;
        border-color: #FCA5A5;
        color: #EF4444;
    }

    /* ─── Right Sidebar Widgets ─────────────────────────────────── */
    .sidebar-col {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .widget-box {
        background: #FFFFFF;
        border: 1px solid var(--card-border);
        border-radius: var(--card-radius);
        overflow: hidden;
    }

    .widget-box-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--card-border);
        font-size: 0.75rem;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .terminal-link-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        text-decoration: none;
        color: #111827;
        border-bottom: 1px solid var(--card-border);
        transition: background 0.15s ease;
    }

    .terminal-link-card:last-child {
        border-bottom: none;
    }

    .terminal-link-card:hover {
        background: #F9FAFB;
    }

    .terminal-avatar {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: #F9FAFB;
        border: 1px solid var(--card-border);
        color: #111827;
        flex-shrink: 0;
    }

    .terminal-info h4 {
        font-size: 0.8125rem;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .terminal-info p {
        font-size: 0.6875rem;
        color: #6B7280;
        margin: 1px 0 0 0;
    }

    /* Recent Exits Stream */
    .recent-exit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 1rem;
        border-bottom: 1px solid #F3F4F6;
        font-size: 0.8125rem;
    }

    .recent-exit-row:last-child {
        border-bottom: none;
    }

    /* ─── Modal Dialogs ─────────────────────────────────────────── */
    .session-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
    }

    .session-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .session-modal-dialog {
        background: #FFFFFF;
        border-radius: 8px;
        max-width: 580px;
        width: 100%;
        overflow: hidden;
        border: 1px solid var(--card-border);
    }

    .session-modal-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
    }

    .session-modal-title {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .session-modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: #6B7280;
        cursor: pointer;
        line-height: 1;
        padding: 2px;
    }

    .session-modal-close:hover {
        color: #111827;
    }

    .session-modal-body {
        padding: 1.25rem;
    }

    .session-modal-footer {
        padding: 0.85rem 1.25rem;
        border-top: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        background: #F9FAFB;
    }

    /* Modal Summary Cards Grid */
    .summary-grid-4 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .summary-tile {
        padding: 0.85rem 1rem;
        border-radius: 6px;
        border: 1px solid var(--card-border);
        background: #F9FAFB;
    }

    .summary-tile-label {
        font-size: 0.6875rem;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .summary-tile-val {
        font-size: 1.35rem;
        font-weight: 800;
        color: #111827;
    }
</style>
@endsection

@section('content')

@php
    $maxCapacity = 50;
    $occupiedCount = $currentlyInside ?? 0;
    $availableSlots = max(0, $maxCapacity - $occupiedCount);
    $occupancyPercent = min(100, round(($occupiedCount / $maxCapacity) * 100));
@endphp

<!-- ═══════════════════════════════════════════════════════════════
     MINIMALIST ACTION TOOLBAR
     ═══════════════════════════════════════════════════════════════ -->
<div class="dashboard-action-toolbar">
    <div class="toolbar-left">
        <div class="session-indicator">
            <span class="status-dot"></span>
            <span id="sessionPillText">Session: <strong>{{ $activeSession->session_code ?? 'ACTIVE' }}</strong></span>
        </div>
        <div class="grace-rule-pill">
            <i class="ri-shield-check-line"></i>
            <span>15-Min Free Grace Active (₱0.00)</span>
        </div>
    </div>

    <div class="toolbar-right">
        <button type="button" class="btn-topbar primary-fee" onclick="openChangeFeeModal()" id="btnChangeFee" title="Manage facility parking rates and grace rules">
            <i class="ri-edit-line"></i>
            <span>Change Parking Fee</span>
        </button>
        <button type="button" class="btn-topbar outline" onclick="openSessionHistoryModal()" title="View session archive history">
            <i class="ri-history-line"></i>
            <span>Session History</span>
        </button>
        <button type="button" class="btn-topbar soft-danger" onclick="promptEndSession()" id="btnEndSession" title="Finalize current active session">
            <i class="ri-stop-circle-line"></i>
            <span>End Session</span>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     CURRENT STATUS OF GATE 1 & GATE 2 + QUICK ACCESS (Req 3)
     ═══════════════════════════════════════════════════════════════ -->
<div class="gates-status-row">
    <!-- Gate 1 (Entry Cam) -->
    <div class="gate-status-card">
        <div class="gate-card-left">
            <div class="gate-status-pill">
                <span class="status-dot"></span>
                <span>Operational</span>
            </div>
            <div class="gate-title-wrap">
                <h3><i class="ri-login-box-line"></i> Gate 1 • Entry Camera</h3>
                <p>Automatic vehicle plate detection & QR gate pass issuance</p>
            </div>
        </div>
        <div class="gate-card-right">
            <a href="{{ route('plate-ocr.index') }}" class="btn-gate-quick" title="Open Gate 1 Live Entry Camera">
                <i class="ri-camera-line"></i>
                <span>Open Entry Cam</span>
            </a>
        </div>
    </div>

    <!-- Gate 2 (Exit Cam) -->
    <div class="gate-status-card">
        <div class="gate-card-left">
            <div class="gate-status-pill">
                <span class="status-dot"></span>
                <span>Operational</span>
            </div>
            <div class="gate-title-wrap">
                <h3><i class="ri-logout-box-r-line"></i> Gate 2 • Exit Camera</h3>
                <p>Exit plate recognition, 15m grace evaluation & fee settlement</p>
            </div>
        </div>
        <div class="gate-card-right">
            <a href="{{ route('plate-ocr.exit-scan') }}" class="btn-gate-quick" title="Open Gate 2 Live Exit Camera">
                <i class="ri-camera-line"></i>
                <span>Open Exit Cam</span>
            </a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MINIMALIST METRIC CARDS (Requirement 3: Total, Parked, Exited, Revenue)
     ═══════════════════════════════════════════════════════════════ -->
<div class="kpi-row">
    <!-- Card 1: Total Vehicles Recorded -->
    <div class="kpi-card-clean">
        <div class="kpi-card-top">
            <span class="kpi-label">Total Vehicles</span>
            <div class="kpi-icon-pill"><i class="ri-car-line"></i></div>
        </div>
        <div>
            <div class="kpi-main-val" id="statTotalVehicles">{{ $total ?? 0 }}</div>
        </div>
        <div class="kpi-subtext">
            <span>Recorded in active session</span>
        </div>
    </div>

    <!-- Card 2: Vehicles Currently Parked -->
    <div class="kpi-card-clean">
        <div class="kpi-card-top">
            <span class="kpi-label">Currently Parked</span>
            <div class="kpi-icon-pill"><i class="ri-parking-box-line"></i></div>
        </div>
        <div>
            <div class="kpi-main-val">
                <span id="statCurrentlyParked">{{ $occupiedCount }}</span>
                <span style="font-size: 0.875rem; font-weight: 600; color: #6B7280;">/ {{ $maxCapacity }} slots</span>
                <span id="capacityOccupiedText" style="display: none;">{{ $occupiedCount }}</span>
            </div>
            <div class="kpi-progress-wrap">
                <div class="kpi-progress-bar" id="capacityBarFill" style="width: {{ $occupancyPercent }}%;"></div>
            </div>
        </div>
        <div class="kpi-subtext">
            <span style="font-weight: 600; color: #111827;" id="capacityAvailableText">{{ $availableSlots }} slots available</span>
            <span>•</span>
            <span id="capacityPercentText">{{ $occupancyPercent }}% occupied</span>
        </div>
    </div>

    <!-- Card 3: Vehicles That Have Exited -->
    <div class="kpi-card-clean">
        <div class="kpi-card-top">
            <span class="kpi-label">Vehicles Exited</span>
            <div class="kpi-icon-pill"><i class="ri-logout-box-r-line"></i></div>
        </div>
        <div>
            <div class="kpi-main-val" id="statCompletedSessions">{{ $completedSessions ?? 0 }}</div>
        </div>
        <div class="kpi-subtext">
            <span>Completed parking cycles</span>
        </div>
    </div>

    <!-- Card 4: Total Parking Revenue -->
    <div class="kpi-card-clean">
        <div class="kpi-card-top">
            <span class="kpi-label">Total Revenue</span>
            <div class="kpi-icon-pill"><i class="ri-money-dollar-circle-line"></i></div>
        </div>
        <div>
            <div class="kpi-main-val" id="statSessionRevenue">₱{{ number_format($sessionRevenue ?? 0, 2) }}</div>
        </div>
        <div class="kpi-subtext">
            <span style="font-weight: 600; color: #111827;"><span id="statPaidSessions">{{ $paidSessionsCount ?? 0 }}</span> paid</span>
            <span>•</span>
            <span style="font-weight: 600; color: #10B981;"><span id="statFreeSessions">{{ $freeSessionsCount ?? 0 }}</span> free grace</span>
        </div>
    </div>

    <!-- Card 5: 15-Min Free Grace & Accuracy -->
    <div class="kpi-card-clean">
        <div class="kpi-card-top">
            <span class="kpi-label">15M Grace & OCR</span>
            <div class="kpi-icon-pill"><i class="ri-shield-check-line"></i></div>
        </div>
        <div>
            <div class="kpi-main-val" id="statOcrAccuracy">{{ $successRate ?? 98.5 }}%</div>
        </div>
        <div class="kpi-subtext">
            <span style="color: #10B981; font-weight: 600;">15m Grace Active</span>
            <span>• ₱0.00 assessed</span>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     DASHBOARD MAIN LAYOUT (ACTIVITY STREAM + TERMINALS SIDEBAR)
     ═══════════════════════════════════════════════════════════════ -->
<div class="dashboard-layout">
    <!-- Main Activity Stream Table Card -->
    <div class="activity-card">
        <div class="activity-header">
            <div class="activity-header-title">
                <h2><i class="ri-time-line"></i> Recent Parking Transactions</h2>
                <p>Live stream of incoming and outgoing vehicle detections</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('plate-ocr.index') }}" class="btn-topbar outline" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
                    <i class="ri-camera-line"></i>
                    <span>Gate 1 (Entry)</span>
                </a>
                <a href="{{ route('plate-ocr.exit-scan') }}" class="btn-topbar outline" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
                    <i class="ri-camera-line"></i>
                    <span>Gate 2 (Exit)</span>
                </a>
            </div>
        </div>

        <!-- Clean Search & Filter Bar -->
        <div class="table-filter-bar">
            <form method="GET" action="{{ route('dashboard') }}" class="filter-form-clean">
                <input
                    type="text"
                    name="plate"
                    value="{{ request('plate') }}"
                    placeholder="Search license plate number..."
                    class="input-search-clean"
                />
                <select name="date_filter" class="select-clean">
                    <option value="" {{ request('date_filter') == '' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>This Month</option>
                </select>
                <button type="submit" class="btn-filter-clean">
                    <i class="ri-search-line"></i> Filter
                </button>
            </form>
        </div>

        <!-- Clean Table Content -->
        <div class="clean-table-wrap">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Vehicle Plate</th>
                        <th>Timestamps</th>
                        <th>Duration & Grace</th>
                        <th>Fee & Status</th>
                        <th>State</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="gateActivityTbody">
                    @forelse ($entries as $entry)
                        @php
                            $avgConf = $entry->average_confidence;
                            $isLowConf = $entry->isLowConfidence();
                            $isFree = $entry->isFreeGrace();
                            $fee = (float)($entry->parking_fee ?? 0);
                        @endphp
                        <tr>
                            <!-- Vehicle Plate & Type -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.45rem;">
                                        <div class="plate-chip">
                                            <span class="plate-chip-text">{{ $entry->plate_number }}</span>
                                        </div>
                                        @if($entry->is_manually_corrected)
                                            <span class="badge-soft-green" title="Manually verified by staff">Verified</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.725rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                                        <span style="text-transform: capitalize;">{{ $entry->vehicle_type ?? 'Car' }}</span>
                                        <span>•</span>
                                        @if($isLowConf)
                                            <span class="badge-soft-amber" onclick="openCorrectPlateModal('{{ $entry->id }}', '{{ $entry->plate_number }}', '{{ optional($entry->entry_time)->format('M d, Y • h:i A') }}', '{{ $entry->gate_entry }}', '{{ $avgConf }}', '')" title="Confidence < 70%. Click to correct.">
                                                <i class="ri-alert-line"></i> Check OCR
                                            </span>
                                        @elseif($avgConf)
                                            <span style="color: var(--emerald); font-weight: 600;">{{ round($avgConf, 1) }}% AI</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Timestamps (In & Out) -->
                            <td>
                                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-head);">
                                    {{ optional($entry->entry_time)->format('h:i A') ?? '—' }}
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-muted);">
                                    {{ optional($entry->entry_time)->format('M d, Y') ?? '' }}
                                    @if($entry->exit_time)
                                        • Out: <strong style="color: var(--text-body);">{{ $entry->exit_time->format('h:i A') }}</strong>
                                    @endif
                                </div>
                            </td>

                            <!-- Duration & Grace Rule Status -->
                            <td>
                                @if($entry->status === 'exited')
                                    <div style="font-weight: 700; color: var(--text-head); font-size: 0.85rem;">
                                        {{ $entry->duration_formatted }}
                                    </div>
                                    @if($isFree)
                                        <span class="badge-soft-green" title="Departed within 15-minute free window">
                                            <i class="ri-gift-line"></i> Free Grace (≤15m)
                                        </span>
                                    @else
                                        <span class="badge-soft-slate">Standard Tariff</span>
                                    @endif
                                @else
                                    @php
                                        $minsNow = (int) (optional($entry->entry_time)->diffInMinutes(now()) ?? 0);
                                    @endphp
                                    <div style="font-weight: 700; color: var(--primary-blue); font-size: 0.85rem;">
                                        {{ $minsNow }}m elapsed
                                    </div>
                                    @if($minsNow <= 15)
                                        <span class="badge-soft-green" title="Eligible for ₱0.00 fee if departing within 15 minutes">
                                            <i class="ri-timer-line"></i> Grace Active ({{ 15 - $minsNow }}m left)
                                        </span>
                                    @else
                                        <span class="badge-soft-slate">Base Tariff Active</span>
                                    @endif
                                @endif
                            </td>

                            <!-- Parking Fee & Payment Status -->
                            <td>
                                @if($entry->status === 'exited')
                                    <div style="font-weight: 800; font-size: 0.95rem; color: {{ $isFree ? 'var(--emerald)' : 'var(--text-head)' }};">
                                        {{ $isFree ? '₱0.00 (Free)' : '₱' . number_format($fee, 2) }}
                                    </div>
                                    <span class="badge-soft-{{ $entry->payment_status === 'paid' ? 'green' : ($isFree ? 'slate' : 'amber') }}">
                                        {{ ucfirst($entry->payment_status ?? 'Unpaid') }}
                                    </span>
                                @else
                                    <span style="font-size: 0.775rem; color: var(--text-muted);">Assessed at exit</span>
                                @endif
                            </td>

                            <!-- State: Parked or Exited -->
                            <td>
                                @if($entry->status === 'exited')
                                    <span class="badge-soft-slate"><i class="ri-checkbox-circle-line"></i> Exited</span>
                                @else
                                    <span class="badge-soft-green"><i class="ri-parking-box-line"></i> Parked</span>
                                @endif
                            </td>

                            <!-- Gate Actions -->
                            <td>
                                <div class="table-actions-group">
                                    <button type="button" class="btn-action-icon edit-action" title="Correct Misread Plate" onclick="openCorrectPlateModal('{{ $entry->id }}', '{{ $entry->plate_number }}', '{{ optional($entry->entry_time)->format('M d, Y • h:i A') }}', '{{ $entry->gate_entry }}', '{{ $avgConf }}', '')">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    @if($entry->status === 'entered')
                                        <form method="POST" action="{{ route('plate-ocr.exit', $entry->id) }}" style="display:inline;" onsubmit="return confirm('Process checkout and record exit for vehicle {{ $entry->plate_number }}?')">
                                            @csrf
                                            <button type="submit" class="btn-action-icon exit-action" title="Manual Exit Gate Checkout">
                                                <i class="ri-logout-box-r-line"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('plate-ocr.download', ['plate' => $entry->plate_number, 'timestamp' => optional($entry->entry_time)->format('Y-m-d H:i:s')]) }}" class="btn-action-icon" title="Download QR Gate Pass">
                                        <i class="ri-download-line"></i>
                                    </a>
                                    <button type="button" class="btn-action-icon" title="Print Ticket" onclick="printTicket('{{ route('plate-ocr.print', $entry->id) }}')">
                                        <i class="ri-printer-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                                <i class="ri-car-line" style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                <strong style="color: var(--text-head); font-size: 1rem;">No Gate Activity Recorded</strong>
                                <p style="font-size: 0.85rem; margin-top: 0.25rem;">Vehicles entering Gate 1 or exiting Gate 2 will appear here automatically.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Clean Pagination -->
        <div id="tablePaginationContainer">
            @if($entries->count() > 0)
                <div style="padding: 1rem 1.5rem; background: #F8FAFC; border-top: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="font-size: 0.8125rem; color: var(--text-body);">
                        Showing {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} records
                    </div>
                    <div style="display: flex; gap: 0.4rem;">
                        @if($entries->onFirstPage())
                            <button class="btn-topbar outline" disabled style="opacity: 0.5; padding: 0.35rem 0.75rem; font-size: 0.775rem;">Previous</button>
                        @else
                            <a href="{{ $entries->previousPageUrl() }}" class="btn-topbar outline" style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">Previous</a>
                        @endif

                        @if($entries->hasMorePages())
                            <a href="{{ $entries->nextPageUrl() }}" class="btn-topbar outline" style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">Next</a>
                        @else
                            <button class="btn-topbar outline" disabled style="opacity: 0.5; padding: 0.35rem 0.75rem; font-size: 0.775rem;">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Sidebar: Terminals & Recent Exits -->
    <div class="sidebar-col">
        <!-- Quick Terminals Card -->
        <div class="widget-box">
            <div class="widget-box-header">
                <span><i class="ri-sensor-line"></i> Gate Terminals</span>
            </div>
            <a href="{{ route('plate-ocr.index') }}" class="terminal-link-card">
                <div class="terminal-avatar"><i class="ri-login-box-line"></i></div>
                <div class="terminal-info">
                    <h4>Gate 1 • Entry Camera</h4>
                    <p>Detect vehicle & issue pass</p>
                </div>
            </a>
            <a href="{{ route('plate-ocr.exit-scan') }}" class="terminal-link-card">
                <div class="terminal-avatar"><i class="ri-logout-box-r-line"></i></div>
                <div class="terminal-info">
                    <h4>Gate 2 • Exit Camera</h4>
                    <p>Scan exit & calculate fee</p>
                </div>
            </a>
            <a href="{{ route('plate-ocr.history') }}" class="terminal-link-card">
                <div class="terminal-avatar"><i class="ri-file-list-3-line"></i></div>
                <div class="terminal-info">
                    <h4>Parking Logs Archive</h4>
                    <p>Complete logs & CSV export</p>
                </div>
            </a>
        </div>

        <!-- Recent Exits Stream -->
        <div class="widget-box">
            <div class="widget-box-header">
                <span><i class="ri-logout-box-r-line"></i> Recent Checkouts</span>
            </div>
            <div id="recentExitsList">
                @forelse($recentExited as $exited)
                    <div class="recent-exit-row">
                        <div>
                            <strong style="font-family: monospace; letter-spacing: 0.05em; color: var(--text-head);">{{ $exited->plate_number }}</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 1px;">
                                {{ optional($exited->exit_time)->format('h:i A') ?? '—' }}
                            </div>
                        </div>
                        <span class="badge-soft-slate">Exited</span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.8125rem;">
                        <i class="ri-car-line" style="font-size: 1.5rem; display: block; margin-bottom: 0.25rem; opacity: 0.3;"></i>
                        No vehicles have exited yet in this session
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     CONFIRM END SESSION MODAL (Clean, Gentle)
     ═══════════════════════════════════════════════════════════════ -->
<div class="session-modal-overlay" id="confirmEndModal">
    <div class="session-modal-dialog" style="max-width: 460px;">
        <div class="session-modal-header" style="background: #FEF2F2; border-bottom: 1px solid #FEE2E2;">
            <div class="session-modal-title" style="color: #991B1B;">
                <i class="ri-alert-line"></i>
                <span>Confirm End Session</span>
            </div>
            <button class="session-modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="session-modal-body" style="text-align: center; padding: 1.75rem 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-head); margin-bottom: 0.5rem;">
                End Current Parking Session?
            </h3>
            <p style="font-size: 0.875rem; color: var(--text-body); line-height: 1.5; margin-bottom: 1.25rem;">
                This will finalize shift totals, archive all transactions, and begin a fresh session for incoming attendants.
            </p>
            <div style="background: #F8FAFC; border: 1px solid var(--card-border); border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.775rem; color: var(--text-muted); text-align: left;">
                <i class="ri-information-line" style="color: var(--primary-blue);"></i> 
                All vehicles recorded will remain preserved in historical logs.
            </div>
        </div>
        <div class="session-modal-footer">
            <button type="button" class="btn-topbar outline" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn-topbar soft-danger" id="btnConfirmEndSession" onclick="executeEndSession()">
                <i class="ri-check-line"></i>
                <span id="btnConfirmEndText">Confirm & End Session</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     SESSION SUMMARY MODAL
     ═══════════════════════════════════════════════════════════════ -->
<div class="session-modal-overlay" id="sessionSummaryModal">
    <div class="session-modal-dialog">
        <div class="session-modal-header">
            <div>
                <span class="badge-soft-green" style="margin-bottom: 0.25rem;">
                    <i class="ri-checkbox-circle-fill"></i> SESSION FINALIZED
                </span>
                <div class="session-modal-title">
                    Session Summary: <span id="summarySessionCodeBadge" style="font-family: monospace; color: var(--primary-blue);">SES-001</span>
                </div>
            </div>
            <button class="session-modal-close" onclick="closeSummaryAndStartNew()">&times;</button>
        </div>

        <div class="session-modal-body">
            <div class="summary-grid-4">
                <div class="summary-tile">
                    <div class="summary-tile-label"><i class="ri-wallet-3-line"></i> Total Revenue</div>
                    <div class="summary-tile-val" id="summaryRevenue" style="color: var(--purple);">₱0.00</div>
                </div>
                <div class="summary-tile">
                    <div class="summary-tile-label"><i class="ri-car-line"></i> Total Vehicles</div>
                    <div class="summary-tile-val" id="summaryVehicles">0 Cars</div>
                </div>
                <div class="summary-tile">
                    <div class="summary-tile-label"><i class="ri-parking-box-line"></i> Left Inside</div>
                    <div class="summary-tile-val" id="summaryParked" style="color: var(--emerald);">0 Cars</div>
                </div>
                <div class="summary-tile">
                    <div class="summary-tile-label"><i class="ri-checkbox-circle-line"></i> Completed Exits</div>
                    <div class="summary-tile-val" id="summaryCompleted" style="color: #EA580C;">0 Cars</div>
                </div>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--card-border); border-radius: 10px; padding: 0.85rem 1rem; font-size: 0.8125rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-body);"><i class="ri-time-line"></i> Total Dwell Duration:</span>
                <strong style="color: var(--text-head);" id="summaryDuration">0m</strong>
            </div>
        </div>

        <div class="session-modal-footer">
            <button type="button" class="btn-topbar outline" onclick="window.print()">
                <i class="ri-printer-line"></i> Print
            </button>
            <button type="button" class="btn-topbar primary-fee" onclick="closeSummaryAndStartNew()">
                <i class="ri-restart-line"></i> Start New Session
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     SESSION HISTORY ARCHIVE MODAL
     ═══════════════════════════════════════════════════════════════ -->
<div class="session-modal-overlay" id="sessionHistoryModal">
    <div class="session-modal-dialog" style="max-width: 780px;">
        <div class="session-modal-header">
            <div class="session-modal-title">
                <i class="ri-archive-line" style="color: var(--primary-blue);"></i>
                <span>Historical Parking Sessions</span>
            </div>
            <button class="session-modal-close" onclick="closeSessionHistoryModal()">&times;</button>
        </div>
        <div class="session-modal-body" style="max-height: 480px; overflow-y: auto; padding: 1.25rem;">
            <div id="sessionHistoryLoading" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <i class="ri-loader-4-line ri-spin" style="font-size: 1.8rem;"></i>
                <p style="margin-top: 0.5rem; font-size: 0.85rem;">Loading session archives...</p>
            </div>
            <div id="sessionHistoryContent" style="display: none;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Time Window</th>
                            <th>Vehicles</th>
                            <th>Exits</th>
                            <th>Parked</th>
                            <th>Revenue</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody id="sessionHistoryTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="session-modal-footer">
            <button type="button" class="btn-topbar outline" onclick="closeSessionHistoryModal()">Close</button>
        </div>
    </div>
</div>

<!-- Change Parking Fee & Plate Correction Modals -->
@include('partials.change-fee-modal')
@include('partials.correct-plate-modal')

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
    const revEl = document.getElementById('statSessionRevenue');
    if (revEl) revEl.textContent = '₱0.00';

    const vehEl = document.getElementById('statTotalVehicles');
    if (vehEl) vehEl.textContent = '0';

    const parkedEl = document.getElementById('statCurrentlyParked');
    if (parkedEl) parkedEl.textContent = '0';

    const compEl = document.getElementById('statCompletedSessions');
    if (compEl) compEl.textContent = '0';

    const occEl = document.getElementById('capacityOccupiedText');
    if (occEl) occEl.textContent = '0';

    const availEl = document.getElementById('capacityAvailableText');
    if (availEl) availEl.textContent = '50 slots available';

    const pctEl = document.getElementById('capacityPercentText');
    if (pctEl) pctEl.textContent = '0% occupied';

    const fillEl = document.getElementById('capacityBarFill');
    if (fillEl) fillEl.style.width = '0%';

    const tbody = document.getElementById('gateActivityTbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                    <i class="ri-car-line" style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                    <strong style="color: var(--text-head); font-size: 1rem;">No Gate Activity Recorded in This Session</strong>
                    <p style="font-size: 0.85rem; margin-top: 0.25rem;">Gate 1 and Gate 2 cameras are active and ready to detect arriving and departing vehicles.</p>
                </td>
            </tr>
        `;
    }

    const pag = document.getElementById('tablePaginationContainer');
    if (pag) pag.style.display = 'none';

    const exitsList = document.getElementById('recentExitsList');
    if (exitsList) {
        exitsList.innerHTML = `
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.8125rem;">
                <i class="ri-car-line" style="font-size: 1.5rem; display: block; margin-bottom: 0.25rem; opacity: 0.3;"></i>
                No vehicles have exited yet in this session
            </div>
        `;
    }

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
                    <td><strong style="font-family: monospace; color: var(--primary-blue);">${s.session_code}</strong></td>
                    <td>
                        <div style="font-weight: 600; font-size: 0.8rem;">${s.started_at}</div>
                        <div style="font-size: 0.725rem; color: var(--text-muted);">to ${s.ended_at}</div>
                    </td>
                    <td><strong>${s.total_vehicles}</strong> cars</td>
                    <td><span style="color: #EA580C; font-weight: 700;">${s.completed_sessions}</span></td>
                    <td><span style="color: var(--emerald); font-weight: 700;">${s.currently_parked}</span></td>
                    <td><strong style="color: var(--emerald);">${s.total_revenue}</strong></td>
                    <td>${s.duration}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
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
    let oldFrame = document.getElementById('hiddenPrintFrame');
    if (oldFrame) oldFrame.remove();

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
