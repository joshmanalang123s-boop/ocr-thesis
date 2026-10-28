@extends('layouts.app')

@section('title', 'Membership Management - SABINO')
@section('page-title', 'Monthly Parking Memberships')

@section('additional-styles')
<style>
    /* Header Area */
    .membership-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .membership-header h1 {
        font-size: 1.625rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 0.35rem 0;
        letter-spacing: -0.02em;
    }

    .membership-header p {
        color: #6B7280;
        font-size: 0.875rem;
        margin: 0;
    }

    .membership-actions {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        flex-wrap: wrap;
    }

    .btn-membership-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1.15rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        background: #2563EB;
        color: #FFFFFF;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s ease;
    }

    .btn-membership-primary:hover {
        background: #1D4ED8;
    }

    .btn-membership-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1rem;
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

    .btn-membership-outline:hover {
        background: #F9FAFB;
        border-color: #D1D5DB;
    }

    /* Summary KPI Cards */
    .membership-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1024px) {
        .membership-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .membership-kpi-grid { grid-template-columns: 1fr; }
    }

    .mem-card {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 110px;
    }

    .mem-card-title {
        font-size: 0.6875rem;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .mem-card-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #111827;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 0.35rem;
    }

    .mem-card-sub {
        font-size: 0.75rem;
        color: #6B7280;
    }

    /* Filter & Search Bar */
    .mem-filter-bar {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .mem-search-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        max-width: 380px;
    }

    .mem-search-input {
        width: 100%;
        padding: 0.45rem 0.75rem;
        border-radius: 6px;
        border: 1px solid #E5E7EB;
        font-size: 0.8125rem;
        color: #111827;
        outline: none;
    }

    .mem-search-input:focus {
        border-color: #3B82F6;
    }

    .mem-filter-pills {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .mem-filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .mem-filter-pill:hover {
        background: #F3F4F6;
        color: #111827;
    }

    .mem-filter-pill.active {
        background: #EFF6FF;
        color: #2563EB;
        border: 1.5px solid #60A5FA;
        border-radius: 9999px;
        padding: 0.3rem 0.95rem;
        font-weight: 600;
    }

    /* Table Container */
    .mem-table-card {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .mem-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .mem-table th {
        background: #F9FAFB;
        padding: 0.85rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.725rem;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #E5E7EB;
    }

    .mem-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #F3F4F6;
        color: #111827;
        vertical-align: middle;
    }

    .mem-table tr:hover td {
        background: #F9FAFB;
    }

    /* Status Badges */
    .member-badge-active {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #BBF7D0;
    }

    .member-badge-warning {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FDE68A;
    }

    .member-badge-expired {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FECACA;
    }

    .member-badge-unpaid {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #F1F5F9;
        color: #64748B;
        border: 1px solid #E2E8F0;
    }

    .member-badge-suspended {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #F3F4F6;
        color: #4B5563;
        border: 1px solid #D1D5DB;
    }

    .plate-text-pill {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        background: #F3F4F6;
        border: 1px solid #E5E7EB;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        color: #111827;
        display: inline-block;
    }

    /* Modal Dialogs */
    .mem-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(2px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .mem-modal.active {
        display: flex;
    }

    .mem-modal-content {
        background: #FFFFFF;
        border-radius: 10px;
        max-width: 580px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .mem-modal-content.wide {
        max-width: 780px;
    }

    .mem-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mem-modal-header h3 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
    }

    .mem-modal-body {
        padding: 1.5rem;
    }

    .mem-modal-footer {
        padding: 1rem 1.5rem;
        background: #F9FAFB;
        border-top: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .form-group {
        margin-bottom: 1.1rem;
    }

    .form-label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 0.875rem;
        color: #111827;
        background: #FFFFFF;
        outline: none;
    }

    .form-control:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .table-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-action-sm {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 5px;
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
        color: #374151;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .btn-action-sm:hover {
        background: #F3F4F6;
        color: #111827;
    }
</style>
@endsection

@section('content')
<!-- Header -->
<div class="membership-header">
    <div>
        <h1>Monthly Parking Memberships</h1>
        <p>Register recurring parking members, configure pricing plans, automate OCR gate access, and track subscriptions.</p>
    </div>
    <div class="membership-actions">
        <button type="button" class="btn-membership-primary" onclick="openRegisterModal()">
            <i class="ri-user-add-line"></i>
            <span>Register Member</span>
        </button>
        <button type="button" class="btn-membership-outline" onclick="openPricingModal()">
            <i class="ri-settings-3-line"></i>
            <span>Pricing Settings</span>
        </button>
        <a href="{{ route('memberships.export', ['status' => $statusFilter, 'q' => $search]) }}" class="btn-membership-outline">
            <i class="ri-download-line"></i>
            <span>Export CSV</span>
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="membership-kpi-grid">
    <div class="mem-card">
        <div class="mem-card-title">Total Members</div>
        <div class="mem-card-value">{{ number_format($totalMembers) }}</div>
        <div class="mem-card-sub">All registered subscriber accounts</div>
    </div>
    <div class="mem-card">
        <div class="mem-card-title">Active Members</div>
        <div class="mem-card-value" style="color: #15803D;">{{ number_format($activeMembers) }}</div>
        <div class="mem-card-sub">Granted ₱0 gate pass on OCR read</div>
    </div>
    <div class="mem-card">
        <div class="mem-card-title">Expired & Unpaid</div>
        <div class="mem-card-value" style="color: #B91C1C;">
            {{ number_format($expiredMembers) }}
            @if($unpaidMembers > 0)
                <span style="font-size: 0.9rem; font-weight: 600; color: #64748B;">(+{{ $unpaidMembers }} unpaid)</span>
            @endif
        </div>
        <div class="mem-card-sub">Assessed regular tariff upon entry</div>
    </div>
    <div class="mem-card">
        <div class="mem-card-title">Membership Revenue</div>
        <div class="mem-card-value">₱{{ number_format($totalMembershipRevenue, 2) }}</div>
        <div class="mem-card-sub">Total dues collected (separate ledger)</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="mem-filter-bar">
    <form method="GET" action="{{ route('memberships.index') }}" class="mem-search-form">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search name, plate number, contact..." class="mem-search-input">
        @if($statusFilter !== 'all')
            <input type="hidden" name="status" value="{{ $statusFilter }}">
        @endif
        <button type="submit" class="btn-membership-outline" style="padding: 0.45rem 0.85rem;">Search</button>
        @if($search)
            <a href="{{ route('memberships.index', ['status' => $statusFilter]) }}" class="btn-membership-outline" style="padding: 0.45rem 0.65rem;" title="Clear">×</a>
        @endif
    </form>

    <div class="mem-filter-pills">
        <span style="font-size: 0.75rem; font-weight: 600; color: #6B7280; margin-right: 0.25rem;">STATUS:</span>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'all']) }}" class="mem-filter-pill {{ $statusFilter === 'all' ? 'active' : '' }}">All</a>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'active']) }}" class="mem-filter-pill {{ $statusFilter === 'active' ? 'active' : '' }}">Active</a>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'expiring']) }}" class="mem-filter-pill {{ $statusFilter === 'expiring' ? 'active' : '' }}">
            Expiring Soon ({{ $expiringSoonMembers }})
        </a>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'expired']) }}" class="mem-filter-pill {{ $statusFilter === 'expired' ? 'active' : '' }}">Expired</a>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'unpaid']) }}" class="mem-filter-pill {{ $statusFilter === 'unpaid' ? 'active' : '' }}">Unpaid</a>
        <a href="{{ route('memberships.index', ['q' => $search, 'status' => 'suspended']) }}" class="mem-filter-pill {{ $statusFilter === 'suspended' ? 'active' : '' }}">Suspended</a>
    </div>
</div>

<!-- Members Table -->
<div class="mem-table-card">
    <div style="overflow-x: auto;">
        <table class="mem-table">
            <thead>
                <tr>
                    <th>Member Details</th>
                    <th>Vehicle Plate</th>
                    <th>Validity Period</th>
                    <th>Duration & Fee</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #111827;">{{ $m->full_name }}</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">
                                {{ $m->contact_number ?? 'No phone' }}
                                @if($m->email) • {{ $m->email }} @endif
                            </div>
                        </td>
                        <td>
                            <div class="plate-text-pill">{{ $m->plate_number }}</div>
                            <div style="font-size: 0.725rem; color: #6B7280; text-transform: capitalize; margin-top: 2px;">
                                <i class="ri-car-line"></i> {{ $m->vehicle_type }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                {{ $m->start_date ? $m->start_date->format('M d, Y') : '—' }} to {{ $m->end_date ? $m->end_date->format('M d, Y') : '—' }}
                            </div>
                            <div style="font-size: 0.725rem; color: #6B7280;">
                                @if($m->isExpired())
                                    <span style="color: #B91C1C;">Expired {{ \Carbon\Carbon::today()->diffInDays($m->end_date) }}d ago</span>
                                @elseif($m->isExpiringSoon($settings->expiration_warning_days))
                                    <span style="color: #B45309; font-weight: 600;">{{ \Carbon\Carbon::today()->diffInDays($m->end_date, false) }} days remaining</span>
                                @else
                                    <span>{{ \Carbon\Carbon::today()->diffInDays($m->end_date, false) }} days left</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700;">₱{{ number_format($m->price, 2) }}</div>
                            <div style="font-size: 0.725rem; color: #6B7280;">
                                {{ $m->duration_months }} Month(s) • 
                                @if($m->payment_status === 'paid')
                                    <span style="color: #15803D; font-weight: 600;">Paid</span>
                                @else
                                    <span style="color: #B91C1C; font-weight: 600;">Unpaid</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="{{ $m->badge_class }}">
                                @if($m->computed_status === 'active')
                                    <i class="ri-checkbox-circle-fill"></i>
                                @elseif($m->computed_status === 'expiring_soon')
                                    <i class="ri-alert-fill"></i>
                                @elseif($m->computed_status === 'expired')
                                    <i class="ri-close-circle-fill"></i>
                                @else
                                    <i class="ri-information-fill"></i>
                                @endif
                                {{ $m->computed_status_label }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content: flex-end;">
                                <button type="button" class="btn-action-sm" onclick="viewMemberDetails({{ $m->id }})" title="View Details">
                                    <i class="ri-eye-line"></i> View
                                </button>
                                <button type="button" class="btn-action-sm" onclick="openRenewModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', '{{ $m->plate_number }}', '{{ $m->end_date ? $m->end_date->format('M d, Y') : '' }}')" title="Renew Membership">
                                    <i class="ri-restart-line"></i> Renew
                                </button>
                                <button type="button" class="btn-action-sm" onclick="openEditModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', '{{ $m->plate_number }}', '{{ $m->vehicle_type }}', '{{ $m->contact_number }}', '{{ $m->email }}', '{{ addslashes($m->notes ?? '') }}')" title="Edit Info">
                                    <i class="ri-edit-line"></i>
                                </button>
                                @if($m->payment_status === 'unpaid')
                                    <button type="button" class="btn-action-sm" style="background: #ECFDF5; color: #047857; border-color: #A7F3D0;" onclick="openPaymentModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', {{ $m->price }})" title="Confirm Payment">
                                        <i class="ri-money-dollar-circle-line"></i> Pay
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('memberships.toggle-status', $m->id) }}" style="display: inline;" onsubmit="return confirm('Change status for {{ $m->full_name }}?');">
                                    @csrf
                                    <button type="submit" class="btn-action-sm" title="{{ $m->status === 'active' ? 'Suspend' : 'Reactivate' }}">
                                        <i class="ri-shield-user-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: #6B7280;">
                            No membership records found matching your filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($members->hasPages())
        <div style="padding: 1rem; border-top: 1px solid #E5E7EB;">
            {{ $members->links() }}
        </div>
    @endif
</div>

<!-- MODAL 1: REGISTER NEW MEMBER -->
<div class="mem-modal" id="registerModal">
    <div class="mem-modal-content">
        <form method="POST" action="{{ route('memberships.store') }}" id="registerMemberForm">
            @csrf
            <div class="mem-modal-header">
                <h3>Register Monthly Member</h3>
                <button type="button" class="btn-action-sm" onclick="closeModal('registerModal')">✕</button>
            </div>
            <div class="mem-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. Juan Dela Cruz">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Vehicle Plate Number *</label>
                        <input type="text" name="plate_number" class="form-control" required placeholder="e.g. ABC 1234" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vehicle Type *</label>
                        <select name="vehicle_type" class="form-control" required>
                            <option value="car">Car / Sedan / SUV</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="truck">Truck / Van</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" id="regStartDate" class="form-control" required value="{{ date('Y-m-d') }}" onchange="calculateRegistrationEndDate()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration *</label>
                        <select name="duration_months" id="regDuration" class="form-control" required onchange="calculateRegistrationPriceAndDate()">
                            <option value="1">1 Month (Default)</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">12 Months (1 Year)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Membership Price (₱) *</label>
                        <input type="number" step="0.01" name="price" id="regPrice" class="form-control" required value="{{ $settings->default_monthly_price }}">
                        <span style="font-size: 0.725rem; color: #6B7280;">Default is ₱{{ number_format($settings->default_monthly_price, 2) }}/mo. Admin overridable.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Computed Expiration Date</label>
                        <input type="text" id="regComputedEnd" class="form-control" readonly style="background: #F9FAFB; font-weight: 600; color: #111827;">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Payment Status *</label>
                        <select name="payment_status" id="regPaymentStatus" class="form-control" required onchange="togglePaymentFields('reg')">
                            <option value="paid">Paid (Activate immediately)</option>
                            <option value="unpaid">Unpaid (Hold benefits until paid)</option>
                        </select>
                    </div>
                    <div class="form-group" id="regMethodGroup">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="regRefGroup">
                    <label class="form-label">Payment Reference / Receipt #</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="e.g. OR-84920 or GCash Ref #">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" placeholder="0917-000-0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="member@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="mem-modal-footer">
                <button type="button" class="btn-membership-outline" onclick="closeModal('registerModal')">Cancel</button>
                <button type="submit" class="btn-membership-primary">Register Member</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: RENEW MEMBERSHIP -->
<div class="mem-modal" id="renewModal">
    <div class="mem-modal-content">
        <form method="POST" id="renewMemberForm">
            @csrf
            <div class="mem-modal-header">
                <h3>Renew Membership</h3>
                <button type="button" class="btn-action-sm" onclick="closeModal('renewModal')">✕</button>
            </div>
            <div class="mem-modal-body">
                <div style="background: #F9FAFB; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; border: 1px solid #E5E7EB;">
                    <div style="font-weight: 700; color: #111827;" id="renewMemberName">—</div>
                    <div style="font-size: 0.8125rem; color: #6B7280;">Plate: <span id="renewPlate" class="plate-text-pill" style="font-size: 0.75rem;">—</span> • Current Expiration: <strong id="renewCurrentEnd">—</strong></div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Renewal Duration *</label>
                        <select name="duration_months" id="renewDuration" class="form-control" required onchange="calculateRenewPriceAndDate()">
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">12 Months (1 Year)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Renewal Price (₱) *</label>
                        <input type="number" step="0.01" name="price" id="renewPrice" class="form-control" required value="{{ $settings->default_monthly_price }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Computed Expiration Date</label>
                    <input type="text" id="renewComputedEnd" class="form-control" readonly style="background: #EFF6FF; font-weight: 700; color: #1D4ED8; border-color: #BFDBFE;">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Payment Status *</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="paid">Paid (Record Revenue)</option>
                            <option value="unpaid">Unpaid (Awaiting payment)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Reference / Receipt #</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="e.g. RENEW-OR-1234">
                </div>
            </div>
            <div class="mem-modal-footer">
                <button type="button" class="btn-membership-outline" onclick="closeModal('renewModal')">Cancel</button>
                <button type="submit" class="btn-membership-primary">Confirm Renewal</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: VIEW MEMBER DETAILS -->
<div class="mem-modal" id="detailsModal">
    <div class="mem-modal-content wide">
        <div class="mem-modal-header">
            <h3>Member Profile & Audit Ledger</h3>
            <button type="button" class="btn-action-sm" onclick="closeModal('detailsModal')">✕</button>
        </div>
        <div class="mem-modal-body" id="detailsModalBody">
            <div style="text-align: center; padding: 2rem;">Loading member details...</div>
        </div>
        <div class="mem-modal-footer">
            <button type="button" class="btn-membership-outline" onclick="closeModal('detailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- MODAL 4: EDIT MEMBER -->
<div class="mem-modal" id="editModal">
    <div class="mem-modal-content">
        <form method="POST" id="editMemberForm">
            @csrf
            @method('PUT')
            <div class="mem-modal-header">
                <h3>Edit Member Information</h3>
                <button type="button" class="btn-action-sm" onclick="closeModal('editModal')">✕</button>
            </div>
            <div class="mem-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" id="editFullName" class="form-control" required>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Vehicle Plate Number *</label>
                        <input type="text" name="plate_number" id="editPlate" class="form-control" required style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vehicle Type *</label>
                        <select name="vehicle_type" id="editVehicleType" class="form-control" required>
                            <option value="car">Car / Sedan / SUV</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="truck">Truck / Van</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" id="editContact" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="editEmail" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mem-modal-footer">
                <button type="button" class="btn-membership-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-membership-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 5: CONFIRM PAYMENT -->
<div class="mem-modal" id="paymentModal">
    <div class="mem-modal-content">
        <form method="POST" id="confirmPaymentForm">
            @csrf
            <div class="mem-modal-header">
                <h3>Record Membership Payment</h3>
                <button type="button" class="btn-action-sm" onclick="closeModal('paymentModal')">✕</button>
            </div>
            <div class="mem-modal-body">
                <p style="color: #4B5563; font-size: 0.875rem; margin-top: 0;">
                    Confirming payment for <strong id="payMemberName">—</strong> activates full member gate access with ₱0 parking dues.
                </p>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Amount Collected (₱) *</label>
                        <input type="number" step="0.01" name="amount" id="payAmount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Receipt / Transaction Reference</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="e.g. REC-2026-001">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="mem-modal-footer">
                <button type="button" class="btn-membership-outline" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" class="btn-membership-primary">Confirm & Activate</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 6: PRICING SETTINGS -->
<div class="mem-modal" id="pricingModal">
    <div class="mem-modal-content">
        <form method="POST" action="{{ route('memberships.settings.update') }}">
            @csrf
            <div class="mem-modal-header">
                <h3>Membership Pricing & Policy Settings</h3>
                <button type="button" class="btn-action-sm" onclick="closeModal('pricingModal')">✕</button>
            </div>
            <div class="mem-modal-body">
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Default 1-Month Fee (₱) *</label>
                        <input type="number" step="0.01" name="default_monthly_price" class="form-control" required value="{{ $settings->default_monthly_price }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">3-Month Price (₱)</label>
                        <input type="number" step="0.01" name="price_3_months" class="form-control" value="{{ $settings->price_3_months }}">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">6-Month Price (₱)</label>
                        <input type="number" step="0.01" name="price_6_months" class="form-control" value="{{ $settings->price_6_months }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">12-Month Price (₱)</label>
                        <input type="number" step="0.01" name="price_12_months" class="form-control" value="{{ $settings->price_12_months }}">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Expiration Warning (Days) *</label>
                        <input type="number" name="expiration_warning_days" class="form-control" required value="{{ $settings->expiration_warning_days }}" min="1" max="60">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Overnight Policy</label>
                        <select name="allow_overnight" class="form-control">
                            <option value="1" {{ $settings->allow_overnight ? 'selected' : '' }}>Included in Membership</option>
                            <option value="0" {{ !$settings->allow_overnight ? 'selected' : '' }}>Exclude (Standard Overnight Applies)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Coverage Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ $settings->notes }}</textarea>
                </div>
            </div>
            <div class="mem-modal-footer">
                <button type="button" class="btn-membership-outline" onclick="closeModal('pricingModal')">Cancel</button>
                <button type="submit" class="btn-membership-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
const pricingConfig = {
    1: {{ (float)$settings->default_monthly_price }},
    3: {{ (float)$settings->price_3_months }},
    6: {{ (float)$settings->price_6_months }},
    12: {{ (float)$settings->price_12_months }}
};

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openRegisterModal() {
    calculateRegistrationPriceAndDate();
    openModal('registerModal');
}

function openPricingModal() {
    openModal('pricingModal');
}

function calculateRegistrationEndDate() {
    const startVal = document.getElementById('regStartDate').value;
    const duration = parseInt(document.getElementById('regDuration').value, 10);
    if (!startVal) return;

    const start = new Date(startVal);
    start.setMonth(start.getMonth() + duration);
    document.getElementById('regComputedEnd').value = start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function calculateRegistrationPriceAndDate() {
    const duration = parseInt(document.getElementById('regDuration').value, 10);
    document.getElementById('regPrice').value = pricingConfig[duration] || (pricingConfig[1] * duration);
    calculateRegistrationEndDate();
}

function togglePaymentFields(prefix) {
    const status = document.getElementById(prefix + 'PaymentStatus').value;
    const methodGrp = document.getElementById(prefix + 'MethodGroup');
    const refGrp = document.getElementById(prefix + 'RefGroup');
    if (status === 'unpaid') {
        if (methodGrp) methodGrp.style.display = 'none';
        if (refGrp) refGrp.style.display = 'none';
    } else {
        if (methodGrp) methodGrp.style.display = 'block';
        if (refGrp) refGrp.style.display = 'block';
    }
}

// Renew Modal Logic
let currentRenewEndDate = new Date();

function openRenewModal(id, name, plate, endFormatted) {
    document.getElementById('renewMemberForm').action = '/memberships/' + id + '/renew';
    document.getElementById('renewMemberName').innerText = name;
    document.getElementById('renewPlate').innerText = plate;
    document.getElementById('renewCurrentEnd').innerText = endFormatted || 'N/A';
    
    currentRenewEndDate = endFormatted ? new Date(endFormatted) : new Date();
    calculateRenewPriceAndDate();
    openModal('renewModal');
}

function calculateRenewPriceAndDate() {
    const duration = parseInt(document.getElementById('renewDuration').value, 10);
    document.getElementById('renewPrice').value = pricingConfig[duration] || (pricingConfig[1] * duration);

    const now = new Date();
    const base = (currentRenewEndDate && currentRenewEndDate >= now) ? new Date(currentRenewEndDate) : new Date();
    base.setMonth(base.getMonth() + duration);

    document.getElementById('renewComputedEnd').value = base.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Edit Modal
function openEditModal(id, name, plate, vtype, phone, email, notes) {
    document.getElementById('editMemberForm').action = '/memberships/' + id;
    document.getElementById('editFullName').value = name;
    document.getElementById('editPlate').value = plate;
    document.getElementById('editVehicleType').value = vtype;
    document.getElementById('editContact').value = phone || '';
    document.getElementById('editEmail').value = email || '';
    document.getElementById('editNotes').value = notes || '';
    openModal('editModal');
}

// Confirm Payment Modal
function openPaymentModal(id, name, amount) {
    document.getElementById('confirmPaymentForm').action = '/memberships/' + id + '/confirm-payment';
    document.getElementById('payMemberName').innerText = name;
    document.getElementById('payAmount').value = amount;
    openModal('paymentModal');
}

// View Details Modal via AJAX
function viewMemberDetails(id) {
    const container = document.getElementById('detailsModalBody');
    container.innerHTML = '<div style="text-align: center; padding: 2rem;">Loading details...</div>';
    openModal('detailsModal');

    fetch('/memberships/' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div style="color: red; padding: 1rem;">Failed to load member profile.</div>';
                return;
            }

            const m = data.member;
            let paymentsHtml = '';
            if (data.payments && data.payments.length > 0) {
                paymentsHtml = data.payments.map(p => `
                    <tr>
                        <td style="padding: 6px 8px; font-weight: 600;">${p.type}</td>
                        <td style="padding: 6px 8px;">${p.amount_formatted} (${p.duration})</td>
                        <td style="padding: 6px 8px;">${p.start_date} – ${p.end_date}</td>
                        <td style="padding: 6px 8px;"><span style="color: ${p.status === 'Paid' ? '#15803D' : '#B91C1C'}; font-weight: 700;">${p.status}</span></td>
                        <td style="padding: 6px 8px;">${p.method} (${p.reference})</td>
                        <td style="padding: 6px 8px; font-size: 0.75rem; color: #6B7280;">${p.paid_at}</td>
                    </tr>
                `).join('');
            } else {
                paymentsHtml = '<tr><td colspan="6" style="text-align: center; padding: 1rem; color: #6B7280;">No payments recorded.</td></tr>';
            }

            let visitsHtml = '';
            if (data.parking_entries && data.parking_entries.length > 0) {
                visitsHtml = data.parking_entries.map(v => `
                    <tr>
                        <td style="padding: 6px 8px;">${v.entry_time}</td>
                        <td style="padding: 6px 8px;">${v.exit_time}</td>
                        <td style="padding: 6px 8px;">${v.duration}</td>
                        <td style="padding: 6px 8px; font-weight: 700;">${v.fee}</td>
                        <td style="padding: 6px 8px;"><span style="color: #15803D; font-weight: 600;">${v.covered}</span></td>
                    </tr>
                `).join('');
            } else {
                visitsHtml = '<tr><td colspan="5" style="text-align: center; padding: 1rem; color: #6B7280;">No parking sessions logged yet.</td></tr>';
            }

            container.innerHTML = `
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.5rem; background: #F9FAFB; padding: 1rem; border-radius: 8px; border: 1px solid #E5E7EB;">
                    <div>
                        <h2 style="margin: 0 0 0.25rem 0; font-size: 1.25rem; font-weight: 700; color: #111827;">${m.full_name}</h2>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <span class="plate-text-pill">${m.plate_number}</span>
                            <span style="font-size: 0.8125rem; color: #6B7280; text-transform: capitalize;">${m.vehicle_type}</span>
                        </div>
                        <div style="font-size: 0.8125rem; color: #4B5563;">
                            Phone: <strong>${m.contact_number || 'N/A'}</strong> • Email: <strong>${m.email || 'N/A'}</strong>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="${m.badge_class}" style="font-size: 0.875rem; padding: 0.35rem 0.75rem;">${m.computed_status_label}</span>
                        <div style="margin-top: 0.5rem; font-size: 0.8125rem; color: #374151;">
                            Valid: <strong>${m.start_date_formatted}</strong> to <strong>${m.end_date_formatted}</strong>
                        </div>
                        <div style="font-size: 0.75rem; color: #6B7280;">Registered: ${m.created_at_formatted}</div>
                    </div>
                </div>

                <h4 style="font-size: 0.9rem; font-weight: 700; margin: 0 0 0.5rem 0; color: #111827;">Membership Payment History</h4>
                <div style="border: 1px solid #E5E7EB; border-radius: 6px; overflow: hidden; margin-bottom: 1.25rem;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead style="background: #F3F4F6;">
                            <tr>
                                <th style="padding: 6px 8px; text-align: left;">Type</th>
                                <th style="padding: 6px 8px; text-align: left;">Amount</th>
                                <th style="padding: 6px 8px; text-align: left;">Period</th>
                                <th style="padding: 6px 8px; text-align: left;">Status</th>
                                <th style="padding: 6px 8px; text-align: left;">Method</th>
                                <th style="padding: 6px 8px; text-align: left;">Date</th>
                            </tr>
                        </thead>
                        <tbody>${paymentsHtml}</tbody>
                    </table>
                </div>

                <h4 style="font-size: 0.9rem; font-weight: 700; margin: 0 0 0.5rem 0; color: #111827;">Recent Parking Visits (OCR Gates)</h4>
                <div style="border: 1px solid #E5E7EB; border-radius: 6px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead style="background: #F3F4F6;">
                            <tr>
                                <th style="padding: 6px 8px; text-align: left;">Time In</th>
                                <th style="padding: 6px 8px; text-align: left;">Time Out</th>
                                <th style="padding: 6px 8px; text-align: left;">Duration</th>
                                <th style="padding: 6px 8px; text-align: left;">Assessed Fee</th>
                                <th style="padding: 6px 8px; text-align: left;">Coverage</th>
                            </tr>
                        </thead>
                        <tbody>${visitsHtml}</tbody>
                    </table>
                </div>
            `;
        })
        .catch(err => {
            container.innerHTML = '<div style="color: red; padding: 1rem;">Network error fetching details.</div>';
        });
}

// Initial trigger
document.addEventListener('DOMContentLoaded', function() {
    calculateRegistrationPriceAndDate();
});
</script>
@endsection
