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
            <span class="capacity-occupied">{{ $occupiedCount }}</span>
            <span class="capacity-total">/ {{ $maxCapacity }} Slots Occupied</span>
        </div>
    </div>
    <div class="capacity-bar-bg">
        <div class="capacity-bar-fill" style="width: {{ $occupancyPercent }}%;"></div>
    </div>
    <div class="capacity-footer">
        <span><strong style="color: #10B981;">{{ $availableSlots }} Slots Available</strong></span>
        <span>Occupancy Rate: <strong>{{ $occupancyPercent }}%</strong></span>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon primary"><i class="ri-car-line"></i></div>
        </div>
        <div class="stat-title">Total Detections</div>
        <div class="stat-value">{{ $total ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #10B981; font-weight:700;">↑ 12%</span>
            <span>vs last month</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon success"><i class="ri-login-box-line"></i></div>
        </div>
        <div class="stat-title">Today's Scans</div>
        <div class="stat-value">{{ $today ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #10B981; font-weight:700;">↑ 8%</span>
            <span>vs yesterday</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon info"><i class="ri-parking-line"></i></div>
        </div>
        <div class="stat-title">Currently Parked</div>
        <div class="stat-value">{{ $currentlyInside ?? 0 }}</div>
        <div class="stat-footer">
            <span style="color: #0284C7; font-weight:600;">On premises</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon danger"><i class="ri-logout-box-r-line"></i></div>
        </div>
        <div class="stat-title">Exited Today</div>
        <div class="stat-value">{{ $exitedToday ?? 0 }}</div>
        <div class="stat-footer">
            <span>Completed visits</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon orange"><i class="ri-checkbox-circle-line"></i></div>
        </div>
        <div class="stat-title">Total Exits</div>
        <div class="stat-value">{{ $totalExited ?? 0 }}</div>
        <div class="stat-footer">
            <span>All time exits</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon warning"><i class="ri-focus-3-line"></i></div>
        </div>
        <div class="stat-title">OCR Accuracy</div>
        <div class="stat-value">{{ $successRate ?? 95 }}%</div>
        <div class="stat-footer">
            <span style="color: #10B981; font-weight:700;">Optimal</span>
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
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @php
                                        $croppedPath = $entry->entry_image_path ? str_replace('.', '_cropped.', $entry->entry_image_path) : null;
                                        $hasCropped = $croppedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($croppedPath);
                                    @endphp
                                    
                                    @if($hasCropped)
                                        <div style="background: #0F172A; border-radius: 4px; padding: 2px; border: 1px solid #334155; width: 80px; height: 35px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.15);" title="YOLOv8 Detected Plate Crop">
                                            <img src="{{ asset('storage/' . $croppedPath) }}" alt="Crop" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                        </div>
                                    @else
                                        <div style="background: #F1F5F9; border-radius: 4px; border: 1px dashed #CBD5E1; width: 80px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #94A3B8;" title="No crop available">
                                            <span>No crop</span>
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

        @if($entries->count() > 0)
            <div class="pagination">
                <div style="font-size: 0.8125rem; color: var(--text-secondary);">
                    Showing {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} gate detections
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
            <div class="recent-exits-list">
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
                        No vehicles have exited yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
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

