@extends('layouts.app')

@section('title', 'Parking Logs & History')
@section('page-title', 'Parking Records & Gate History')

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
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 0.875rem;
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
        box-shadow: var(--card-shadow);
        padding: 1.25rem;
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
    .stat-icon.orange { background: #FFF7ED; color: #F97316; }
    .stat-icon.info { background: #E0F2FE; color: #0284C7; }

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

    .status-badge.entered { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
    .status-badge.exited { background: #FFF7ED; color: #C2410C; border: 1px solid #FFD8A8; }

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

    /* Dual Latest Cards */
    .latest-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    @media (max-width: 900px) {
        .latest-sections { grid-template-columns: 1fr; }
    }

    .latest-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .latest-card-header {
        padding: 1.125rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .latest-card-header.entered { border-left: 4px solid #10B981; }
    .latest-card-header.exited { border-left: 4px solid #F97316; }

    .latest-card-header h3 {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .latest-list {
        padding: 0.5rem 0;
    }

    .latest-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid #F1F5F9;
    }

    .latest-item:last-child { border-bottom: none; }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="ri-history-line" style="color: #2563EB;"></i>
        Parking Records & Gate Logs
    </h1>
    <p>Complete historical archive of vehicle entries at Gate 1 and exit checkouts at Gate 2</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon primary"><i class="ri-file-list-3-line"></i></div>
        </div>
        <div class="stat-title">Total Records</div>
        <div class="stat-value">{{ $total ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon success"><i class="ri-login-box-line"></i></div>
        </div>
        <div class="stat-title">Currently Parked</div>
        <div class="stat-value">{{ $currentlyInside ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon orange"><i class="ri-logout-box-r-line"></i></div>
        </div>
        <div class="stat-title">Total Exited</div>
        <div class="stat-value">{{ $totalExited ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon info"><i class="ri-focus-3-line"></i></div>
        </div>
        <div class="stat-title">OCR Read Rate</div>
        <div class="stat-value">{{ $successRate ?? 95 }}%</div>
    </div>
</div>

<!-- History Table Card -->
<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h2>Complete Parking Log Archive</h2>
            <p>Filter by plate number, status, or date range</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportHistory()" class="btn btn-secondary">
                <i class="ri-download-line"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" action="{{ route('plate-ocr.history') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; flex: 1;">
            <input
                type="text"
                name="plate"
                value="{{ request('plate') }}"
                placeholder="Search plate number..."
                style="flex: 1; min-width: 200px;"
            />
            <select name="status_filter">
                <option value="" {{ request('status_filter') == '' ? 'selected' : '' }}>All Gate Status</option>
                <option value="entered" {{ request('status_filter') == 'entered' ? 'selected' : '' }}>Parked (Entered)</option>
                <option value="exited" {{ request('status_filter') == 'exited' ? 'selected' : '' }}>Exited</option>
            </select>
            <select name="date_filter">
                <option value="" {{ request('date_filter') == '' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>This Week</option>
                <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>This Month</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i> Filter Logs</button>
        </form>
    </div>

    <!-- Table -->
    <div class="table-wrapper" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vehicle Plate</th>
                    <th>Entry Timestamp</th>
                    <th>Exit Timestamp</th>
                    <th>Gate Status</th>
                    <th>Actions</th>
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
                            @if($entry->exit_time)
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    {{ optional($entry->exit_time)->format('M d, Y') }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-tertiary);">
                                    <i class="ri-time-line"></i> {{ optional($entry->exit_time)->format('h:i A') }}
                                </div>
                            @else
                                <span style="font-size: 0.8125rem; color: var(--text-tertiary);">Still Parked</span>
                            @endif
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
                                   title="Download Gate Pass"><i class="ri-download-line"></i></a>
                                <button class="action-btn" title="Print Log" onclick="window.print()"><i class="ri-printer-line"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div style="text-align: center; padding: 3rem 1.5rem; color: var(--text-tertiary);">
                                <i class="ri-history-line" style="font-size: 3rem; display: block; margin-bottom: 0.5rem; opacity: 0.4;"></i>
                                <h3>No Parking Log Records Found</h3>
                                <p style="font-size: 0.875rem; margin-top: 0.25rem;">Adjust search filters or start scanning at Gate 1 & Gate 2.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($entries) && $entries->count() > 0)
        <div style="padding: 1rem 1.5rem; background: #FAFAFA; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.8125rem; color: var(--text-secondary);">
                Showing {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} records
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

<!-- Latest Entered & Exited Summaries -->
<div class="latest-sections">
    <!-- Latest Gate 1 Entries -->
    <div class="latest-card">
        <div class="latest-card-header entered">
            <h3><i class="ri-login-box-line" style="color: #10B981;"></i> Recent Gate 1 Entries (Currently Parked)</h3>
            <span style="font-size: 0.75rem; font-weight: 700; background: #ECFDF5; color: #047857; padding: 0.25rem 0.6rem; border-radius: 6px;">{{ $currentlyInside ?? 0 }} inside</span>
        </div>
        <div class="latest-list">
            @forelse($latestEntered as $entered)
                <div class="latest-item">
                    <div>
                        <div class="plate-badge" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">{{ $entered->plate_number }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 0.25rem;">
                            <i class="ri-time-line"></i> {{ optional($entered->entry_time)->format('M d, h:i A') ?? '—' }}
                        </div>
                    </div>
                    <span class="status-badge entered" style="font-size: 0.7rem;">Parked</span>
                </div>
            @empty
                <div style="text-align: center; padding: 2rem 1rem; color: var(--text-tertiary); font-size: 0.85rem;">
                    No parked vehicles
                </div>
            @endforelse
        </div>
    </div>

    <!-- Latest Gate 2 Exits -->
    <div class="latest-card">
        <div class="latest-card-header exited">
            <h3><i class="ri-logout-box-r-line" style="color: #F97316;"></i> Recent Gate 2 Exits</h3>
            <span style="font-size: 0.75rem; font-weight: 700; background: #FFF7ED; color: #C2410C; padding: 0.25rem 0.6rem; border-radius: 6px;">{{ $totalExited ?? 0 }} exited</span>
        </div>
        <div class="latest-list">
            @forelse($latestExited as $exited)
                <div class="latest-item">
                    <div>
                        <div class="plate-badge" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">{{ $exited->plate_number }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 0.25rem;">
                            <i class="ri-time-line"></i> {{ optional($exited->exit_time)->format('M d, h:i A') ?? '—' }}
                        </div>
                    </div>
                    <span class="status-badge exited" style="font-size: 0.7rem;">Exited</span>
                </div>
            @empty
                <div style="text-align: center; padding: 2rem 1rem; color: var(--text-tertiary); font-size: 0.85rem;">
                    No exited vehicles
                </div>
            @endforelse
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
    function exportHistory() {
        const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
        const data = [['Plate Number', 'Entry Time', 'Exit Time', 'Status']];

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 4) return;

            const plateNum = cells[0]?.textContent.trim();
            const entryTime = cells[1]?.textContent.trim().replace(/\s+/g, ' ');
            const exitTime = cells[2]?.textContent.trim().replace(/\s+/g, ' ');
            const status = cells[3]?.textContent.trim();

            if (plateNum && plateNum !== '') {
                data.push([plateNum, entryTime, exitTime, status]);
            }
        });

        const csv = data.map(row => row.map(col => `"${col.replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', 'parking-logs-report-' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection
