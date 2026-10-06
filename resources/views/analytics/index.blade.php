@extends('layouts.app')

@section('title', 'Analytics & Reports - Autotrace')
@section('page-title', 'Analytics & Operational Intelligence')

@section('additional-styles')
<style>
    /* Hero Header */
    .analytics-hero {
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

    .analytics-hero::before {
        content: '';
        position: absolute;
        right: -60px;
        top: -60px;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.22) 0%, rgba(0, 0, 0, 0) 70%);
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
        font-size: 0.875rem;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        z-index: 2;
    }

    .btn-report-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-report-action.primary {
        background: #8B5CF6;
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .btn-report-action.primary:hover {
        background: #7C3AED;
        transform: translateY(-1px);
    }

    .btn-report-action.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
    }

    .btn-report-action.secondary:hover {
        background: rgba(255, 255, 255, 0.18);
        transform: translateY(-1px);
    }

    /* Filter Bar */
    .filter-bar {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: var(--card-shadow);
    }

    .filter-pills {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.95rem;
        border-radius: 20px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.15s ease;
    }

    .filter-pill:hover {
        color: var(--text-primary);
        background: #F1F5F9;
    }

    .filter-pill.active {
        background: #8B5CF6;
        color: white;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    .custom-date-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .date-input {
        padding: 0.4rem 0.65rem;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-primary);
        font-size: 0.8125rem;
        font-weight: 500;
    }

    /* KPI Cards Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1400px) {
        .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .kpi-grid { grid-template-columns: 1fr; }
    }

    .kpi-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: var(--card-shadow);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .kpi-card.purple::before { background: #8B5CF6; }
    .kpi-card.green::before { background: #10B981; }
    .kpi-card.blue::before { background: #3B82F6; }
    .kpi-card.orange::before { background: #F97316; }
    .kpi-card.cyan::before { background: #06B6D4; }
    .kpi-card.amber::before { background: #F59E0B; }

    .kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .kpi-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .kpi-icon.purple { background: #F5F3FF; color: #8B5CF6; }
    .kpi-icon.green { background: #ECFDF5; color: #10B981; }
    .kpi-icon.blue { background: #EFF6FF; color: #3B82F6; }
    .kpi-icon.orange { background: #FFF7ED; color: #F97316; }
    .kpi-icon.cyan { background: #ECFEFF; color: #06B6D4; }
    .kpi-icon.amber { background: #FEF3C7; color: #D97706; }

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 0.35rem;
    }

    .kpi-sub {
        font-size: 0.725rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Charts Layout */
    .charts-grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1024px) {
        .charts-grid-2 { grid-template-columns: 1fr; }
    }

    .charts-grid-3 {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1200px) {
        .charts-grid-3 { grid-template-columns: 1fr; }
    }

    .chart-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        display: flex;
        flex-direction: column;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .chart-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .chart-badge {
        font-size: 0.725rem;
        font-weight: 700;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        background: #F1F5F9;
        color: var(--text-secondary);
    }

    .chart-container {
        position: relative;
        width: 100%;
        min-height: 280px;
        flex: 1;
    }

    /* Table Card */
    .report-table-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .report-table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .report-table-title h3 {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .report-table-title p {
        font-size: 0.8125rem;
        color: var(--text-secondary);
    }

    .report-table-wrap {
        overflow-x: auto;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .report-table th {
        background: #F8FAFC;
        padding: 0.85rem 1.25rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.75rem;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border-color);
    }

    .report-table td {
        padding: 0.95rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        vertical-align: middle;
    }

    .report-table tr:hover td {
        background: #F8FAFC;
    }

    .rate-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.75rem;
    }

    .rate-badge.high { background: #ECFDF5; color: #059669; }
    .rate-badge.medium { background: #EFF6FF; color: #2563EB; }
    .rate-badge.low { background: #FFF7ED; color: #EA580C; }

    /* Print Styles */
    @media print {
        body { background: white !important; color: black !important; }
        .sidebar, .navbar, .analytics-hero::before, .filter-bar, .hero-actions { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .kpi-card, .chart-card, .report-table-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        .page-header h1 { font-size: 1.5rem !important; }
    }
</style>
@endsection

@section('content')
<!-- Analytics Hero Header -->
<div class="analytics-hero">
    <div class="hero-content">
        <h1>
            <i class="ri-bar-chart-grouped-line" style="color: #A78BFA;"></i>
            Analytics & Reports
        </h1>
        <p>Operational volume, gate utilization, revenue tracking, and OCR recognition intelligence</p>
    </div>
    <div class="hero-actions">
        <a href="{{ route('analytics.export', ['range' => $range, 'from' => $customFrom, 'to' => $customTo]) }}" class="btn-report-action primary">
            <i class="ri-file-excel-2-line"></i>
            <span>Export CSV</span>
        </a>
        <button type="button" class="btn-report-action secondary" onclick="window.print()">
            <i class="ri-printer-line"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Date Range Filter Bar -->
<div class="filter-bar">
    <div class="filter-pills">
        <span style="font-size: 0.8125rem; font-weight: 700; color: var(--text-tertiary); margin-right: 0.35rem;">PERIOD:</span>
        <a href="{{ route('analytics', ['range' => 'today']) }}" class="filter-pill {{ $range === 'today' ? 'active' : '' }}">Today</a>
        <a href="{{ route('analytics', ['range' => '7days']) }}" class="filter-pill {{ $range === '7days' ? 'active' : '' }}">Last 7 Days</a>
        <a href="{{ route('analytics', ['range' => '30days']) }}" class="filter-pill {{ $range === '30days' ? 'active' : '' }}">Last 30 Days</a>
        <a href="{{ route('analytics', ['range' => 'month']) }}" class="filter-pill {{ $range === 'month' ? 'active' : '' }}">This Month</a>
        <a href="{{ route('analytics', ['range' => 'all']) }}" class="filter-pill {{ $range === 'all' ? 'active' : '' }}">All Time</a>
    </div>

    <form method="GET" action="{{ route('analytics') }}" class="custom-date-form">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="{{ $customFrom }}" class="date-input" title="From Date">
        <span style="color: var(--text-tertiary); font-size: 0.8125rem;">to</span>
        <input type="date" name="to" value="{{ $customTo }}" class="date-input" title="To Date">
        <button type="submit" class="btn-report-action primary" style="padding: 0.4rem 0.85rem; font-size: 0.8125rem;">
            Filter
        </button>
    </form>
</div>

<!-- Executive KPI Metrics Grid -->
<div class="kpi-grid">
    <!-- Revenue -->
    <div class="kpi-card purple">
        <div class="kpi-header">
            <span class="kpi-title">Gross Revenue</span>
            <div class="kpi-icon purple"><i class="ri-wallet-3-line"></i></div>
        </div>
        <div class="kpi-value">₱{{ number_format($totalRevenue, 2) }}</div>
        <div class="kpi-sub">
            <span style="color: #10B981; font-weight: 700;">₱{{ number_format($paidRevenue, 2) }} paid</span>
            <span>• ₱{{ number_format($unpaidRevenue, 2) }} pending</span>
        </div>
    </div>

    <!-- Total Volume -->
    <div class="kpi-card green">
        <div class="kpi-header">
            <span class="kpi-title">Total Entries</span>
            <div class="kpi-icon green"><i class="ri-login-box-line"></i></div>
        </div>
        <div class="kpi-value">{{ number_format($totalEntries) }}</div>
        <div class="kpi-sub">
            <span style="color: #10B981; font-weight: 700;">{{ $totalExits }} completed checkouts</span>
        </div>
    </div>

    <!-- Live Parked -->
    <div class="kpi-card blue">
        <div class="kpi-header">
            <span class="kpi-title">Current Occupancy</span>
            <div class="kpi-icon blue"><i class="ri-car-line"></i></div>
        </div>
        <div class="kpi-value">{{ number_format($currentlyParked) }}</div>
        <div class="kpi-sub">
            <span>Vehicles parked inside right now</span>
        </div>
    </div>

    <!-- Dwell Duration -->
    <div class="kpi-card orange">
        <div class="kpi-header">
            <span class="kpi-title">Average Dwell Time</span>
            <div class="kpi-icon orange"><i class="ri-time-line"></i></div>
        </div>
        <div class="kpi-value">{{ $avgDurationFormatted }}</div>
        <div class="kpi-sub">
            <span>Average stay per session</span>
        </div>
    </div>

    <!-- OCR Accuracy -->
    <div class="kpi-card cyan">
        <div class="kpi-header">
            <span class="kpi-title">OCR Read Accuracy</span>
            <div class="kpi-icon cyan"><i class="ri-focus-3-line"></i></div>
        </div>
        <div class="kpi-value">{{ $avgConfidence }}%</div>
        <div class="kpi-sub">
            <span>YOLOv8 & Fast-Plate-OCR average</span>
        </div>
    </div>

    <!-- Peak Hour -->
    <div class="kpi-card amber">
        <div class="kpi-header">
            <span class="kpi-title">Busiest Rush Window</span>
            <div class="kpi-icon amber"><i class="ri-dashboard-3-line"></i></div>
        </div>
        <div class="kpi-value" style="font-size: 1.15rem;">{{ $peakHourLabel }}</div>
        <div class="kpi-sub">
            <span>{{ $peakHourCount }} vehicle movements</span>
        </div>
    </div>
</div>

<!-- Primary Charts: Traffic Trends & Revenue Collection -->
<div class="charts-grid-2">
    <!-- Traffic Velocity Trend -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-line-chart-line" style="color: #3B82F6;"></i>
                <span>Vehicle Traffic Flow Trend</span>
            </div>
            <span class="chart-badge">Entries vs Exits Daily</span>
        </div>
        <div class="chart-container">
            <canvas id="trafficTrendChart"></canvas>
        </div>
    </div>

    <!-- Revenue Over Time -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-wallet-3-fill" style="color: #8B5CF6;"></i>
                <span>Revenue Collection</span>
            </div>
            <span class="chart-badge">Daily Fee Inflow (₱)</span>
        </div>
        <div class="chart-container">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- Secondary Insights: Hourly Heatmap, Fleet Breakdown & Gate Turnover -->
<div class="charts-grid-3">
    <!-- 24-Hour Traffic Distribution -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-time-zone-line" style="color: #F59E0B;"></i>
                <span>24-Hour Peak Heatmap</span>
            </div>
            <span class="chart-badge">Traffic by Hour</span>
        </div>
        <div class="chart-container">
            <canvas id="hourlyTrafficChart"></canvas>
        </div>
    </div>

    <!-- Vehicle Types -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-pie-chart-2-line" style="color: #10B981;"></i>
                <span>Vehicle Fleet Ratio</span>
            </div>
            <span class="chart-badge">Type Distribution</span>
        </div>
        <div class="chart-container">
            <canvas id="vehicleTypesChart"></canvas>
        </div>
    </div>

    <!-- Gate Utilization -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-dual-sim-1-line" style="color: #06B6D4;"></i>
                <span>Gate Turnout Balance</span>
            </div>
            <span class="chart-badge">Terminal Activity</span>
        </div>
        <div class="chart-container">
            <canvas id="gateBalanceChart"></canvas>
        </div>
    </div>
</div>

<!-- Operational Reports Breakdown Table -->
<div class="report-table-card">
    <div class="report-table-header">
        <div class="report-table-title">
            <h3><i class="ri-file-list-3-line" style="color: #8B5CF6;"></i> Daily Operational Audit Log</h3>
            <p>Aggregated daily statistics for period: <strong>{{ $rangeLabel }}</strong></p>
        </div>
        <div style="font-size: 0.8125rem; color: var(--text-tertiary);">
            Showing {{ count($dailyReports) }} operational periods
        </div>
    </div>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle Entries</th>
                    <th>Vehicle Exits</th>
                    <th>Gross Revenue (₱)</th>
                    <th>Avg Duration</th>
                    <th>OCR Read Quality</th>
                    <th>Volume Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dailyReports as $report)
                    @php
                        $dm = (int)$report->avg_mins;
                        $dh = intdiv($dm, 60);
                        $drem = $dm % 60;
                        $formattedDwell = $dh > 0 ? "{$dh}h {$drem}m" : ($dm > 0 ? "{$dm}m" : "—");
                        $conf = round((float)$report->avg_conf, 1);
                        $vol = (int)$report->entries_count;
                        $grade = $vol >= 5 ? 'High Volume' : ($vol >= 2 ? 'Normal' : 'Low Volume');
                        $gradeClass = $vol >= 5 ? 'high' : ($vol >= 2 ? 'medium' : 'low');
                    @endphp
                    <tr>
                        <td>
                            <strong style="color: var(--text-primary);">{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</strong>
                            <div style="font-size: 0.725rem; color: var(--text-tertiary);">{{ \Carbon\Carbon::parse($report->report_date)->format('l') }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: #10B981;"><i class="ri-login-box-line"></i> {{ $report->entries_count }}</span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: #F97316;"><i class="ri-logout-box-r-line"></i> {{ $report->exits_count }}</span>
                        </td>
                        <td>
                            <strong style="color: var(--text-primary);">₱{{ number_format((float)$report->daily_revenue, 2) }}</strong>
                        </td>
                        <td>
                            <span>{{ $formattedDwell }}</span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: {{ $conf >= 90 ? '#10B981' : '#F59E0B' }};">
                                {{ $conf }}%
                            </span>
                        </td>
                        <td>
                            <span class="rate-badge {{ $gradeClass }}">{{ $grade }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-tertiary);">
                            <i class="ri-inbox-line" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No operational logs found in this date window.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('additional-scripts')
<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart Default Styles
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = '#64748B';

    const trendLabels = @json($trendLabels);
    const trendEntries = @json($trendEntries);
    const trendExits = @json($trendExits);
    const trendRevenue = @json($trendRevenue);
    const hourlyData = @json($hourlyTraffic);
    const vehicleTypes = @json($vehicleTypes);

    // 1. Traffic Velocity Line Chart
    const ctxTraffic = document.getElementById('trafficTrendChart');
    if (ctxTraffic) {
        new Chart(ctxTraffic, {
            type: 'line',
            data: {
                labels: trendLabels.length ? trendLabels : ['Today'],
                datasets: [
                    {
                        label: 'Entries (Gate In)',
                        data: trendEntries.length ? trendEntries : [0],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#10B981',
                        pointRadius: 4,
                        borderWidth: 2.5
                    },
                    {
                        label: 'Exits (Gate Out)',
                        data: trendExits.length ? trendExits : [0],
                        borderColor: '#F97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.08)',
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#F97316',
                        pointRadius: 4,
                        borderWidth: 2.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { weight: '600' } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Revenue Collection Bar Chart
    const ctxRevenue = document.getElementById('revenueTrendChart');
    if (ctxRevenue) {
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: trendLabels.length ? trendLabels : ['Today'],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: trendRevenue.length ? trendRevenue : [0],
                    backgroundColor: '#8B5CF6',
                    borderRadius: 6,
                    barThickness: 24,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return ' Revenue: ₱' + ctx.parsed.y.toFixed(2); }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F1F5F9' },
                        ticks: { callback: function(val) { return '₱' + val; } }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 3. Hourly Traffic Heatmap (24 Hours)
    const ctxHourly = document.getElementById('hourlyTrafficChart');
    if (ctxHourly) {
        const hoursLabels = [
            '12A', '1A', '2A', '3A', '4A', '5A', '6A', '7A', '8A', '9A', '10A', '11A',
            '12P', '1P', '2P', '3P', '4P', '5P', '6P', '7P', '8P', '9P', '10P', '11P'
        ];

        new Chart(ctxHourly, {
            type: 'bar',
            data: {
                labels: hoursLabels,
                datasets: [{
                    label: 'Movements',
                    data: hourlyData,
                    backgroundColor: '#3B82F6',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0 } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // 4. Vehicle Fleet Distribution (Donut Chart)
    const ctxVehicles = document.getElementById('vehicleTypesChart');
    if (ctxVehicles) {
        new Chart(ctxVehicles, {
            type: 'doughnut',
            data: {
                labels: ['Car', 'Motorcycle', 'Truck', 'Van'],
                datasets: [{
                    data: [
                        vehicleTypes.car || 0,
                        vehicleTypes.motorcycle || 0,
                        vehicleTypes.truck || 0,
                        vehicleTypes.van || 0
                    ],
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { weight: '600' } } }
                },
                cutout: '68%'
            }
        });
    }

    // 5. Gate Turnout Balance (Bar Chart)
    const ctxGate = document.getElementById('gateBalanceChart');
    if (ctxGate) {
        const gateEntries = {{ $totalEntries }};
        const gateExits = {{ $totalExits }};
        new Chart(ctxGate, {
            type: 'bar',
            data: {
                labels: ['Gate 1 (Entry)', 'Gate 2 (Exit)'],
                datasets: [{
                    label: 'Traffic Count',
                    data: [gateEntries, gateExits],
                    backgroundColor: ['#10B981', '#F97316'],
                    borderRadius: 8,
                    barThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endsection
