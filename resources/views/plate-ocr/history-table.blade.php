<div class="filter-bar">
    <form method="GET" action="{{ route('dashboard') }}" style="display: flex; gap: 1rem; flex-wrap: wrap; flex: 1;">
        <input
            type="text"
            name="plate"
            value="{{ request('plate') }}"
            placeholder="Search plate number..."
            style="flex: 1; min-width: 250px;"
        />
        <select name="date_filter">
            <option value="" {{ request('date_filter') == '' ? 'selected' : '' }}>All Time</option>
            <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
            <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>This Week</option>
            <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>This Month</option>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="ri-search-line"></i>
            <span>Filter</span>
        </button>
        <button type="button" onclick="exportHistory()" class="btn btn-secondary">
            <i class="ri-download-line"></i>
            <span>Export</span>
        </button>
    </form>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Plate Number</th>
                <th>Detection Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
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
                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                            <span style="color: var(--text-primary); font-weight: 500;">
                                {{ optional($entry->entry_time)->format('M d, Y') ?? '—' }}
                            </span>
                            <span style="font-size: 0.8125rem; color: var(--text-tertiary);">
                                {{ optional($entry->entry_time)->format('h:i A') ?? '' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge success">
                            <i class="ri-checkbox-circle-line"></i>
                            <span>{{ ucfirst($entry->status) }}</span>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('plate-ocr.download', ['plate' => $entry->plate_number, 'timestamp' => optional($entry->entry_time)->format('Y-m-d H:i:s')]) }}"
                               class="action-btn"
                               title="Download QR Code"><i class="ri-download-line"></i></a>
                            <button class="action-btn" title="View Details" onclick="viewDetails({{ $entry->id }})"><i class="ri-eye-line"></i></button>
                            <button type="button" class="action-btn" title="Print Ticket" onclick="printTicket('{{ route('plate-ocr.print', $entry->id) }}')"><i class="ri-printer-line"></i></button>
                        </div>
                    </td>
                </tr>
            @empty
            <tr>
                <td colspan="4">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="ri-car-line"></i></div>
                            <h3>No detections found</h3>
                            <p>Try adjusting your filters or scan a new license plate to get started.</p>
                            <a href="{{ route('plate-ocr.index') }}" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="ri-camera-line"></i>
                                <span>Scan New Plate</span>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(isset($entries) && $entries->count() > 0)
    <div class="pagination">
        <div class="pagination-info">
            Showing {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} detections
        </div>
        <div class="pagination-controls">
            @if($entries->onFirstPage())
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>←</span>
                    <span>Previous</span>
                </button>
            @else
                <a href="{{ $entries->previousPageUrl() }}" class="btn btn-secondary">
                    <span>←</span>
                    <span>Previous</span>
                </a>
            @endif

            <span style="color: var(--text-secondary); font-weight: 600; padding: 0 1rem;">
                Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}
            </span>

            @if($entries->hasMorePages())
                <a href="{{ $entries->nextPageUrl() }}" class="btn btn-secondary">
                    <span>Next</span>
                    <span>→</span>
                </a>
            @else
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Next</span>
                    <span>→</span>
                </button>
            @endif
        </div>
    </div>
@endif

<style>
    .filter-bar {
        padding: 1rem 1.5rem;
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-bar input,
    .filter-bar select {
        padding: 0.625rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9375rem;
        background: var(--bg-secondary);
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .filter-bar input:focus,
    .filter-bar select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(91, 111, 237, 0.1);
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
    }

    .data-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .data-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.2s ease;
    }

    .data-table tbody tr:hover {
        background: var(--bg-primary);
    }

    .data-table td {
        padding: 1rem 1.5rem;
        font-size: 0.9375rem;
        color: var(--text-primary);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge i {
        font-size: 0.875rem;
        line-height: 1;
    }

    .badge.success {
        background: #D1FAE5;
        color: #065F46;
    }

    .plate-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: var(--text-primary);
        color: white;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        letter-spacing: 0.1em;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: var(--border-color);
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--primary-color);
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .progress-fill.success {
        background: var(--success-color);
    }

    .progress-fill.warning {
        background: var(--warning-color);
    }

    .action-btns {
        display: flex;
        gap: 0.5rem;
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
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 1rem;
    }

    .action-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .action-btn:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .empty-icon i {
        font-size: 4rem;
        line-height: 1;
    }

    .empty-state h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--text-tertiary);
        margin-bottom: 1.5rem;
    }

    .pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .pagination-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
        }

        .filter-bar input,
        .filter-bar select {
            width: 100%;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
        }

        .pagination {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
