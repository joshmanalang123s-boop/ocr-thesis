@extends('layouts.app')

@section('title', 'Cash Flow & Analytics - SABINO')
@section('page-title', 'Cash Flow & Operational Analytics')

@section('additional-styles')
<style>
    /* Header */
    .analytics-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .analytics-title {
        font-size: 1.625rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 0.35rem 0;
        letter-spacing: -0.02em;
    }

    .analytics-subtitle {
        color: #6B7280;
        font-size: 0.875rem;
        margin: 0;
        line-height: 1.4;
    }

    .analytics-actions {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        flex-wrap: wrap;
    }

    .btn-action-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 0.95rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 500;
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        color: #111827;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .btn-action-outline:hover {
        background: #F9FAFB;
        border-color: #D1D5DB;
        color: #111827;
    }

    .btn-action-outline.blue-border {
        border-color: #BFDBFE;
        color: #2563EB;
        font-weight: 600;
    }

    .btn-action-outline.blue-border:hover {
        background: #EFF6FF;
        border-color: #93C5FD;
        color: #1D4ED8;
    }

    .btn-action-outline i {
        font-size: 1rem;
    }

    /* Grace Rule Guarantee Alert */
    .grace-guarantee-alert {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-left: 3px solid #2563EB;
        border-radius: 8px;
        padding: 0.9rem 1.25rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .grace-guarantee-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .grace-guarantee-info i {
        font-size: 1.25rem;
        color: #2563EB;
        flex-shrink: 0;
    }

    /* Filter Bar */
    .filter-bar {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 0.65rem 1rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .filter-pills {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: wrap;
    }

    .filter-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .filter-pill:hover {
        color: #111827;
        background: #F3F4F6;
    }

    .filter-pill.active {
        background: #EFF6FF;
        color: #2563EB;
        border: 1.5px solid #60A5FA;
        border-radius: 9999px;
        padding: 0.3rem 0.95rem;
        font-weight: 600;
    }

    .custom-date-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .date-input {
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
        color: #374151;
        font-size: 0.8125rem;
        font-weight: 500;
        outline: none;
    }

    .btn-filter-submit {
        background: #BFDBFE;
        color: #1D4ED8;
        font-weight: 600;
        font-size: 0.8125rem;
        border: none;
        border-radius: 6px;
        padding: 0.38rem 1rem;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .btn-filter-submit:hover {
        background: #93C5FD;
    }

    /* 8 Summary KPI Cards Grid */
    .kpi-grid-7 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1200px) {
        .kpi-grid-7 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .kpi-grid-7 { grid-template-columns: 1fr; }
    }

    .kpi-card {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 116px;
    }

    .kpi-header {
        margin-bottom: 0.5rem;
    }

    .kpi-title {
        font-size: 0.6875rem;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #111827;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 0.35rem;
    }

    .kpi-value.compact {
        font-size: 1.35rem;
    }

    .kpi-sub {
        font-size: 0.75rem;
        color: #6B7280;
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

    .chart-container {
        position: relative;
        width: 100%;
        min-height: 270px;
        flex: 1;
    }

    /* Report Tabs Navigation */
    .report-tabs-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
    }

    .report-tab-btn {
        background: none;
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 3px solid transparent;
        margin-bottom: -0.25rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .report-tab-btn:hover {
        color: var(--text-primary);
    }

    .report-tab-btn.active {
        color: #2563EB;
        border-bottom-color: #2563EB;
        font-weight: 700;
    }

    .report-tab-panel {
        display: none;
    }

    .report-tab-panel.active {
        display: block;
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
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.2rem;
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
        padding: 0.85rem 1.15rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.75rem;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border-color);
    }

    .report-table td {
        padding: 0.9rem 1.15rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        vertical-align: middle;
    }

    .report-table tr:hover td {
        background: #F8FAFC;
    }

    /* Free Grace Period Badge */
    .grace-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.725rem;
        font-weight: 800;
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .paid-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.725rem;
        font-weight: 700;
        background: #EFF6FF;
        color: #1D4ED8;
    }

    .unpaid-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.725rem;
        font-weight: 700;
        background: #FEF3C7;
        color: #B45309;
    }

    /* Print Styles */
    @media print {
        body { background: white !important; color: black !important; }
        .sidebar, .navbar, .filter-bar, .analytics-actions, .report-tabs-nav { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .kpi-card, .chart-card, .report-table-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        .report-tab-panel { display: block !important; margin-bottom: 2rem !important; }
    }
</style>
@endsection

@section('content')
<!-- Analytics Header -->
<div class="analytics-header">
    <div>
        <h1 class="analytics-title">Cash Flow & Operational Revenue Analytics</h1>
        <p class="analytics-subtitle">Facility cash inflow tracking, 15-minute free grace auditing, rate performance, and vehicle throughput intelligence.</p>
    </div>
    <div class="analytics-actions">
        <button type="button" class="btn-action-outline blue-border" onclick="openChangeFeeModal()">
            <i class="ri-edit-line"></i>
            <span>Change Parking Fee</span>
        </button>
        <a href="{{ route('analytics.export', ['range' => $range, 'from' => $customFrom, 'to' => $customTo]) }}" class="btn-action-outline">
            <i class="ri-download-line"></i>
            <span>Export CSV</span>
        </a>
        <button type="button" class="btn-action-outline" onclick="window.print()">
            <i class="ri-printer-line"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Mandatory 15-Minute Free Grace Banner Guarantee -->
<div class="grace-guarantee-alert">
    <div class="grace-guarantee-info">
        <i class="ri-information-line"></i>
        <div>
            <div style="font-weight: 700; color: #111827; font-size: 0.875rem; margin-bottom: 0.15rem;">Mandatory 15-Minute Free Parking Grace Rule Enforced</div>
            <div style="font-size: 0.8125rem; color: #4B5563;">
                Vehicles exiting in 15 minutes or less are assessed <strong>₱0.00</strong> and contribute ₱0.00 to revenue collections while remaining fully audited in the ledger.
            </div>
        </div>
    </div>
    <div style="font-size: 0.8125rem; color: #374151; font-weight: 500; white-space: nowrap;">
        Active Base: <strong>₱{{ number_format($activeRate->base_rate ?? 50, 2) }}</strong> (First {{ $activeRate->base_hours ?? 3 }}h) • Succeeding: <strong>₱{{ number_format($activeRate->hourly_rate ?? 20, 2) }}/h</strong>
    </div>
</div>

<!-- Date Range Filter Bar -->
<div class="filter-bar">
    <div class="filter-pills">
        <span style="font-size: 0.75rem; font-weight: 600; color: #6B7280; margin-right: 0.5rem; letter-spacing: 0.03em;">REPORT PERIOD:</span>
        <a href="{{ route('analytics', ['range' => 'today']) }}" class="filter-pill {{ $range === 'today' ? 'active' : '' }}">Today</a>
        <a href="{{ route('analytics', ['range' => '7days']) }}" class="filter-pill {{ $range === '7days' ? 'active' : '' }}">Last 7 Days</a>
        <a href="{{ route('analytics', ['range' => '30days']) }}" class="filter-pill {{ $range === '30days' ? 'active' : '' }}">Last 30 Days</a>
        <a href="{{ route('analytics', ['range' => 'month']) }}" class="filter-pill {{ $range === 'month' ? 'active' : '' }}">This Month</a>
        <a href="{{ route('analytics', ['range' => 'all']) }}" class="filter-pill {{ $range === 'all' ? 'active' : '' }}">All Time</a>
    </div>

    <form method="GET" action="{{ route('analytics') }}" class="custom-date-form">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="{{ $customFrom }}" class="date-input" title="From Date">
        <span style="color: #6B7280; font-size: 0.8125rem;">to</span>
        <input type="date" name="to" value="{{ $customTo }}" class="date-input" title="To Date">
        <button type="submit" class="btn-filter-submit">
            Filter
        </button>
    </form>
</div>

<!-- 8 Report Summary Cards (Grid) -->
<div class="kpi-grid-7">
    <!-- Card 1: Total Vehicles Recorded -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">1. TOTAL VEHICLES</span>
        </div>
        <div class="kpi-value">{{ number_format($totalVehicles) }}</div>
        <div class="kpi-sub">All vehicles recorded in period</div>
    </div>

    <!-- Card 2: Completed Sessions -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">2. COMPLETED SESSIONS</span>
        </div>
        <div class="kpi-value">{{ number_format($totalCompletedSessions) }}</div>
        <div class="kpi-sub">{{ $currentlyParked }} currently inside</div>
    </div>

    <!-- Card 3: Free Sessions (Grace Period) -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">3. FREE SESSIONS (≤15M)</span>
        </div>
        <div class="kpi-value">{{ number_format($totalFreeSessions) }}</div>
        <div class="kpi-sub">₱0.00 assessed (Grace)</div>
    </div>

    <!-- Card 4: Paid Sessions -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">4. PAID SESSIONS (>15M)</span>
        </div>
        <div class="kpi-value">{{ number_format($totalPaidSessions) }}</div>
        <div class="kpi-sub">Billable parking exits</div>
    </div>

    <!-- Card 5: Fees Assessed -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">5. TOTAL FEES ASSESSED</span>
        </div>
        <div class="kpi-value">₱{{ number_format($totalFeesAssessed, 2) }}</div>
        <div class="kpi-sub">Gross tariff computation</div>
    </div>

    <!-- Card 6: Revenue Collected -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">6. REVENUE COLLECTED</span>
        </div>
        <div class="kpi-value">₱{{ number_format($totalRevenueCollected, 2) }}</div>
        <div class="kpi-sub">Actual cash in hand</div>
    </div>

    <!-- Card 7: Unpaid / Report Range -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">7. UNPAID & SCOPE</span>
        </div>
        <div class="kpi-value compact">₱{{ number_format($totalUnpaidAssessed, 2) }} Unpaid</div>
        <div class="kpi-sub">Scope: {{ $rangeLabel }}</div>
    </div>

    <!-- Operational Dwell Card -->
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">AVG STAY & ACCURACY</span>
        </div>
        <div class="kpi-value compact">{{ $avgDurationFormatted }}</div>
        <div class="kpi-sub">OCR Confidence: {{ $avgConfidence }}%</div>
    </div>
</div>

<!-- Primary Charts: Traffic Trends & Cash Flow Inflow -->
<div class="charts-grid-2">
    <!-- Traffic Trend Line Chart -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-line-chart-line" style="color: #3B82F6;"></i>
                <span>Vehicle Movement & Free vs Paid Volume</span>
            </div>
            <span style="font-size: 0.75rem; font-weight: 700; background: #EFF6FF; color: #2563EB; padding: 0.25rem 0.6rem; border-radius: 8px;">Entries vs Exits Daily</span>
        </div>
        <div class="chart-container">
            <canvas id="trafficTrendChart"></canvas>
        </div>
    </div>

    <!-- Cash Flow Inflow Bar Chart -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="ri-wallet-3-fill" style="color: #10B981;"></i>
                <span>Revenue Flow (₱)</span>
            </div>
            <span style="font-size: 0.75rem; font-weight: 700; background: #ECFDF5; color: #059669; padding: 0.25rem 0.6rem; border-radius: 8px;">Daily Inflow</span>
        </div>
        <div class="chart-container">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- Cash Flow Reports Navigation Tabs -->
<div class="report-tabs-nav">
    <button type="button" class="report-tab-btn active" onclick="switchReportTab('daily', this)">
        <i class="ri-calendar-line"></i> Daily Cash Flow
    </button>
    <button type="button" class="report-tab-btn" onclick="switchReportTab('weekly', this)">
        <i class="ri-calendar-todo-line"></i> Weekly Cash Flow
    </button>
    <button type="button" class="report-tab-btn" onclick="switchReportTab('monthly', this)">
        <i class="ri-calendar-2-line"></i> Monthly Cash Flow
    </button>
    <button type="button" class="report-tab-btn" onclick="switchReportTab('transactions', this)">
        <i class="ri-file-list-3-line"></i> Transaction Ledger (Audit)
    </button>
    <button type="button" class="report-tab-btn" onclick="switchReportTab('memberships', this)">
        <i class="ri-user-star-line"></i> Membership Revenue Ledger
    </button>
</div>

<!-- TAB 1: DAILY CASH FLOW TABLE -->
<div id="tab-daily" class="report-tab-panel active">
    <div class="report-table-card">
        <div class="report-table-header">
            <div class="report-table-title">
                <h3><i class="ri-calendar-line" style="color: #2563EB;"></i> Daily Cash Flow Breakdown</h3>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--text-secondary);">Summary by calendar day showing volume, 15-minute free sessions, assessed dues, and collected cash</p>
            </div>
            <span style="font-size: 0.8125rem; color: var(--text-tertiary);">{{ count($dailyCashFlow) }} days recorded</span>
        </div>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicles Entered</th>
                        <th>Sessions Completed</th>
                        <th>15m Free Grace (₱0.00)</th>
                        <th>Paid Sessions</th>
                        <th>Fees Assessed</th>
                        <th>Revenue Collected</th>
                        <th>Unpaid Fees</th>
                        <th>Avg Stay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailyCashFlow as $row)
                        @php
                            $dm = (int)$row->avg_mins;
                            $dh = intdiv($dm, 60);
                            $drem = $dm % 60;
                            $stay = $dh > 0 ? "{$dh}h {$drem}m" : ($dm > 0 ? "{$dm}m" : "—");
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($row->report_date)->format('M d, Y') }}</strong>
                                <div style="font-size: 0.725rem; color: var(--text-tertiary);">{{ \Carbon\Carbon::parse($row->report_date)->format('l') }}</div>
                            </td>
                            <td><strong style="color: #2563EB;">{{ $row->total_vehicles }}</strong></td>
                            <td><strong>{{ $row->completed_sessions }}</strong></td>
                            <td>
                                <span class="grace-badge">
                                    <i class="ri-gift-line"></i> {{ $row->free_sessions }} free
                                </span>
                            </td>
                            <td><strong style="color: #7C3AED;">{{ $row->paid_sessions }}</strong></td>
                            <td><strong>₱{{ number_format((float)$row->fees_assessed, 2) }}</strong></td>
                            <td>
                                <strong style="color: #059669; font-size: 0.95rem;">
                                    ₱{{ number_format((float)$row->revenue_collected, 2) }}
                                </strong>
                            </td>
                            <td>
                                @if((float)$row->unpaid_fees > 0)
                                    <span style="color: #D97706; font-weight: 700;">₱{{ number_format((float)$row->unpaid_fees, 2) }}</span>
                                @else
                                    <span style="color: #94A3B8;">₱0.00</span>
                                @endif
                            </td>
                            <td>{{ $stay }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                                No daily cash flow records found in this date window.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: WEEKLY CASH FLOW TABLE -->
<div id="tab-weekly" class="report-tab-panel">
    <div class="report-table-card">
        <div class="report-table-header">
            <div class="report-table-title">
                <h3><i class="ri-calendar-todo-line" style="color: #7C3AED;"></i> Weekly Cash Flow Breakdown</h3>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--text-secondary);">Aggregated week-by-week financial totals and free grace metrics</p>
            </div>
            <span style="font-size: 0.8125rem; color: var(--text-tertiary);">{{ count($weeklyCashFlow) }} weeks recorded</span>
        </div>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Week Interval</th>
                        <th>Total Vehicles</th>
                        <th>Completed</th>
                        <th>15m Free Sessions</th>
                        <th>Paid Sessions</th>
                        <th>Fees Assessed</th>
                        <th>Revenue Collected</th>
                        <th>Unpaid Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($weeklyCashFlow as $row)
                        <tr>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($row->week_start)->format('M d') }} – {{ \Carbon\Carbon::parse($row->week_end)->format('M d, Y') }}</strong>
                                <div style="font-size: 0.725rem; color: var(--text-tertiary);">ISO Week {{ substr($row->year_week, -2) }}</div>
                            </td>
                            <td><strong style="color: #2563EB;">{{ $row->total_vehicles }}</strong></td>
                            <td><strong>{{ $row->completed_sessions }}</strong></td>
                            <td>
                                <span class="grace-badge">
                                    <i class="ri-gift-line"></i> {{ $row->free_sessions }} free
                                </span>
                            </td>
                            <td><strong style="color: #7C3AED;">{{ $row->paid_sessions }}</strong></td>
                            <td><strong>₱{{ number_format((float)$row->fees_assessed, 2) }}</strong></td>
                            <td>
                                <strong style="color: #059669; font-size: 0.95rem;">
                                    ₱{{ number_format((float)$row->revenue_collected, 2) }}
                                </strong>
                            </td>
                            <td>
                                @if((float)$row->unpaid_fees > 0)
                                    <span style="color: #D97706; font-weight: 700;">₱{{ number_format((float)$row->unpaid_fees, 2) }}</span>
                                @else
                                    <span style="color: #94A3B8;">₱0.00</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                                No weekly records found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 3: MONTHLY CASH FLOW TABLE -->
<div id="tab-monthly" class="report-tab-panel">
    <div class="report-table-card">
        <div class="report-table-header">
            <div class="report-table-title">
                <h3><i class="ri-calendar-2-line" style="color: #059669;"></i> Monthly Cash Flow Breakdown</h3>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--text-secondary);">High-level fiscal month performance, assessing paid revenue vs zero-rate grace period stays</p>
            </div>
            <span style="font-size: 0.8125rem; color: var(--text-tertiary);">{{ count($monthlyCashFlow) }} months recorded</span>
        </div>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Vehicles</th>
                        <th>Completed</th>
                        <th>15m Free Sessions</th>
                        <th>Paid Sessions</th>
                        <th>Fees Assessed</th>
                        <th>Revenue Collected</th>
                        <th>Unpaid Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($monthlyCashFlow as $row)
                        <tr>
                            <td>
                                <strong style="font-size: 0.95rem;">{{ $row->month_label }}</strong>
                            </td>
                            <td><strong style="color: #2563EB;">{{ $row->total_vehicles }}</strong></td>
                            <td><strong>{{ $row->completed_sessions }}</strong></td>
                            <td>
                                <span class="grace-badge">
                                    <i class="ri-gift-line"></i> {{ $row->free_sessions }} free
                                </span>
                            </td>
                            <td><strong style="color: #7C3AED;">{{ $row->paid_sessions }}</strong></td>
                            <td><strong>₱{{ number_format((float)$row->fees_assessed, 2) }}</strong></td>
                            <td>
                                <strong style="color: #059669; font-size: 0.95rem;">
                                    ₱{{ number_format((float)$row->revenue_collected, 2) }}
                                </strong>
                            </td>
                            <td>
                                @if((float)$row->unpaid_fees > 0)
                                    <span style="color: #D97706; font-weight: 700;">₱{{ number_format((float)$row->unpaid_fees, 2) }}</span>
                                @else
                                    <span style="color: #94A3B8;">₱0.00</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                                No monthly cash flow logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 4: DETAILED TRANSACTION LEDGER -->
<div id="tab-transactions" class="report-tab-panel">
    <div class="report-table-card">
        <div class="report-table-header">
            <div class="report-table-title">
                <h3><i class="ri-file-list-3-line" style="color: #64748B;"></i> Individual Transactions Audit Ledger</h3>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--text-secondary);">Itemized vehicle session ledger showing timestamps, duration, rate applied, and staff corrections</p>
            </div>
            <span style="font-size: 0.8125rem; color: var(--text-tertiary);">Showing recent transactions</span>
        </div>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Ticket / Plate</th>
                        <th>Vehicle Type</th>
                        <th>Entry Timestamp</th>
                        <th>Exit Timestamp</th>
                        <th>Dwell Time</th>
                        <th>Grace Rule Applied</th>
                        <th>Assessed Fee</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        @php
                            $isFree = $tx->isFreeGrace();
                            $fee = (float)($tx->parking_fee ?? 0);
                            $isLowConf = $tx->isLowConfidence();
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <strong style="font-family: monospace; font-size: 0.95rem; letter-spacing: 0.05em;">{{ $tx->plate_number }}</strong>
                                    @if($isLowConf)
                                        <span title="Low OCR confidence (<70%)" style="background: #FEF3C7; color: #D97706; font-size: 0.65rem; font-weight: 800; padding: 0.15rem 0.35rem; border-radius: 4px;">
                                            <i class="ri-alert-line"></i> CHECK
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-tertiary);">ID #{{ $tx->id }} • {{ $tx->gate_entry ?? 'Gate 1' }}</div>
                            </td>
                            <td>
                                <span style="font-size: 0.8125rem; text-transform: capitalize;">{{ $tx->vehicle_type ?? 'car' }}</span>
                            </td>
                            <td>
                                <div>{{ $tx->entry_time ? $tx->entry_time->format('M d, Y') : '—' }}</div>
                                <div style="font-size: 0.725rem; color: var(--text-tertiary);">{{ $tx->entry_time ? $tx->entry_time->format('h:i:s A') : '' }}</div>
                            </td>
                            <td>
                                @if($tx->exit_time)
                                    <div>{{ $tx->exit_time->format('M d, Y') }}</div>
                                    <div style="font-size: 0.725rem; color: var(--text-tertiary);">{{ $tx->exit_time->format('h:i:s A') }}</div>
                                @else
                                    <span style="color: #2563EB; font-weight: 600; font-size: 0.75rem;">Currently Parked</span>
                                @endif
                            </td>
                            <td>
                                @if($tx->duration_minutes !== null)
                                    @php
                                        $dm = (int)$tx->duration_minutes;
                                        $dh = intdiv($dm, 60);
                                        $drem = $dm % 60;
                                    @endphp
                                    <span style="font-weight: 600;">{{ $dh > 0 ? "{$dh}h {$drem}m" : "{$dm}m" }}</span>
                                @else
                                    <span style="color: #94A3B8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isFree)
                                    <span class="grace-badge">
                                        <i class="ri-gift-line"></i> FREE (≤15m Grace)
                                    </span>
                                @elseif($tx->status === 'exited')
                                    <span style="font-size: 0.75rem; color: #64748B;">Standard Tariff</span>
                                @else
                                    <span style="font-size: 0.75rem; color: #94A3B8;">In Progress</span>
                                @endif
                            </td>
                            <td>
                                @if($isFree)
                                    <span style="color: #059669; font-weight: 800;">₱0.00</span>
                                @else
                                    <strong style="color: var(--text-primary);">₱{{ number_format($fee, 2) }}</strong>
                                @endif
                            </td>
                            <td>
                                @if($tx->payment_status === 'paid')
                                    <span class="paid-badge"><i class="ri-check-line"></i> Paid</span>
                                @elseif($isFree)
                                    <span class="grace-badge"><i class="ri-check-line"></i> Free</span>
                                @else
                                    <span class="unpaid-badge"><i class="ri-time-line"></i> {{ ucfirst($tx->payment_status ?? 'Unpaid') }}</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;" 
                                        onclick="openCorrectPlateModal('{{ $tx->id }}', '{{ $tx->plate_number }}', '{{ $tx->entry_image ? asset('storage/'.$tx->entry_image) : '' }}')">
                                    <i class="ri-edit-line"></i> Correct
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                                No individual transaction records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 5: MEMBERSHIP REVENUE LEDGER -->
<div id="tab-memberships" class="report-tab-panel">
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-title">MEMBERSHIP REVENUE</span></div>
            <div class="kpi-value">₱{{ number_format($totalMembershipRevenue, 2) }}</div>
            <div class="kpi-sub">Total dues collected in scope</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-title">ACTIVE MEMBERS</span></div>
            <div class="kpi-value" style="color: #15803D;">{{ number_format($activeMembersCount) }}</div>
            <div class="kpi-sub">Total active subscriber accounts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-title">EXPIRED SUBSCRIBERS</span></div>
            <div class="kpi-value" style="color: #B91C1C;">{{ number_format($expiredMembersCount) }}</div>
            <div class="kpi-sub">Subscribers requiring renewal</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-title">RENEWALS RECORDED</span></div>
            <div class="kpi-value">{{ number_format($membershipRenewalsCount) }}</div>
            <div class="kpi-sub">{{ number_format($memberSessionsCount) }} member gate sessions</div>
        </div>
    </div>

    <div class="report-table-card">
        <div class="report-table-header">
            <div class="report-table-title">
                <h3><i class="ri-user-star-line" style="color: #2563EB;"></i> Membership Subscription Payments Ledger</h3>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--text-secondary);">Separate accounting ledger of recurring monthly membership fees (strictly separated from parking session tariffs)</p>
            </div>
            <a href="{{ route('memberships.export') }}" class="btn-action-outline">
                <i class="ri-download-line"></i> Export Membership Ledger
            </a>
        </div>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Member Name</th>
                        <th>Plate Number</th>
                        <th>Payment Type</th>
                        <th>Subscription Period</th>
                        <th>Amount Paid</th>
                        <th>Method & Reference</th>
                        <th>Cashier / Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMemberPayments as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->paid_at ? $p->paid_at->format('M d, Y') : $p->created_at->format('M d, Y') }}</strong>
                                <div style="font-size: 0.725rem; color: var(--text-tertiary);">{{ $p->paid_at ? $p->paid_at->format('h:i A') : '' }}</div>
                            </td>
                            <td><strong>{{ $p->member->full_name ?? 'Subscriber' }}</strong></td>
                            <td><span style="font-family: monospace; font-weight: 700;">{{ $p->member->plate_number ?? '—' }}</span></td>
                            <td>
                                <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #2563EB;">
                                    {{ $p->payment_type }}
                                </span>
                            </td>
                            <td>{{ $p->start_date ? $p->start_date->format('M d') : '' }} – {{ $p->end_date ? $p->end_date->format('M d, Y') : '' }} ({{ $p->duration_months }}m)</td>
                            <td><strong style="color: #059669; font-size: 0.95rem;">₱{{ number_format($p->amount, 2) }}</strong></td>
                            <td>{{ strtoupper($p->payment_method) }} @if($p->payment_reference)<span style="font-size: 0.75rem; color: #6B7280;">({{ $p->payment_reference }})</span>@endif</td>
                            <td>{{ $p->received_by ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                                No membership payments recorded in this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.change-fee-modal')
@include('partials.correct-plate-modal')
@endsection

@section('additional-scripts')
<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
function switchReportTab(tabId, btn) {
    document.querySelectorAll('.report-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.report-tab-panel').forEach(p => p.classList.remove('active'));

    btn.classList.add('active');
    const panel = document.getElementById('tab-' + tabId);
    if (panel) {
        panel.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = '#64748B';

    const trendLabels = @json($trendLabels);
    const trendEntries = @json($trendEntries);
    const trendExits = @json($trendExits);
    const trendRevenue = @json($trendRevenue);
    const trendFree = @json($trendFree);

    // 1. Traffic Velocity Line Chart
    const ctxTraffic = document.getElementById('trafficTrendChart');
    if (ctxTraffic) {
        new Chart(ctxTraffic, {
            type: 'line',
            data: {
                labels: trendLabels.length ? trendLabels : ['Today'],
                datasets: [
                    {
                        label: 'Entries',
                        data: trendEntries.length ? trendEntries : [0],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.08)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: 'Paid Exits',
                        data: trendExits.length ? trendExits : [0],
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.08)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: '15m Free Sessions',
                        data: trendFree.length ? trendFree : [0],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        borderWidth: 2
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
                    backgroundColor: '#10B981',
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
});
</script>
@endsection
