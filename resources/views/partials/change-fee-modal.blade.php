<!-- Change Parking Fee Modal -->
<div class="rate-modal-overlay" id="changeFeeModalOverlay" onclick="if(event.target === this) closeChangeFeeModal()">
    <div class="rate-modal-dialog">
        <div class="rate-modal-header">
            <div class="rate-modal-title">
                <div class="rate-icon-badge"><i class="ri-money-dollar-circle-line"></i></div>
                <div>
                    <h3>Change Parking Fee Rates</h3>
                    <p>Configure facility billing rates & succeeding hourly charges</p>
                </div>
            </div>
            <button type="button" class="rate-modal-close" onclick="closeChangeFeeModal()" aria-label="Close modal">&times;</button>
        </div>

        <form id="changeFeeForm" method="POST" action="{{ route('parking-rates.update') }}" onsubmit="return handleFeeFormSubmit(event)">
            @csrf
            <div class="rate-modal-body">
                <!-- Mandatory 15-Minute Free Grace Period Banner -->
                <div class="grace-period-banner">
                    <div class="grace-badge">
                        <i class="ri-shield-check-line"></i>
                        <span>MANDATORY RULE</span>
                    </div>
                    <div class="grace-content">
                        <strong>15-Minute Free Parking Grace Period</strong>
                        <p>Vehicles staying 15 minutes or less are automatically charged <strong>₱0.00 (FREE)</strong>. This rule is permanently enforced and remains active regardless of rate updates.</p>
                    </div>
                </div>

                <!-- Rate Configuration Inputs Grid -->
                <div class="rate-form-grid">
                    <div class="rate-field-group">
                        <label for="modal_base_rate" class="rate-label">
                            <i class="ri-money-dollar-box-line"></i> Base Parking Fee (₱) <span class="required">*</span>
                        </label>
                        <div class="rate-input-wrap">
                            <span class="currency-prefix">₱</span>
                            <input type="number" step="0.50" min="0" max="10000" id="modal_base_rate" name="base_rate" class="rate-input" value="{{ $activeRate->base_rate ?? 50.00 }}" required>
                        </div>
                        <span class="rate-help">Initial charge covering first N hours.</span>
                    </div>

                    <div class="rate-field-group">
                        <label for="modal_base_hours" class="rate-label">
                            <i class="ri-time-line"></i> Base Coverage Period <span class="required">*</span>
                        </label>
                        <div class="rate-input-wrap">
                            <input type="number" min="1" max="24" id="modal_base_hours" name="base_hours" class="rate-input" value="{{ $activeRate->base_hours ?? 3 }}" required>
                            <span class="unit-suffix">Hours</span>
                        </div>
                        <span class="rate-help">E.g., first 3 hours covered by base fee.</span>
                    </div>

                    <div class="rate-field-group">
                        <label for="modal_hourly_rate" class="rate-label">
                            <i class="ri-speed-up-line"></i> Succeeding Hourly Rate (₱) <span class="required">*</span>
                        </label>
                        <div class="rate-input-wrap">
                            <span class="currency-prefix">₱</span>
                            <input type="number" step="0.50" min="0" max="10000" id="modal_hourly_rate" name="hourly_rate" class="rate-input" value="{{ $activeRate->hourly_rate ?? 20.00 }}" required>
                        </div>
                        <span class="rate-help">Charged per succeeding hour or fraction thereof.</span>
                    </div>

                    <div class="rate-field-group">
                        <label for="modal_grace_period" class="rate-label">
                            <i class="ri-gift-line"></i> Free Grace Period (Mins)
                        </label>
                        <div class="rate-input-wrap">
                            <input type="number" min="15" max="120" id="modal_grace_period" name="grace_period_minutes" class="rate-input" value="{{ max(15, $activeRate->grace_period_minutes ?? 15) }}" readonly title="Grace period is guaranteed at minimum 15 minutes">
                            <span class="unit-suffix">Mins (Min: 15)</span>
                        </div>
                        <span class="rate-help">Locked to mandatory free parking threshold.</span>
                    </div>
                </div>

                <!-- Advanced Vehicle Category Rates (Collapsible) -->
                <div class="category-toggle-wrap">
                    <button type="button" class="btn-toggle-categories" onclick="toggleVehicleRates()">
                        <i class="ri-sound-module-line"></i>
                        <span>Vehicle Type Rates & Additional Charges</span>
                        <i class="ri-arrow-down-s-line" id="categoryToggleIcon"></i>
                    </button>
                </div>

                <div class="vehicle-rates-panel" id="vehicleRatesPanel" style="display: none;">
                    <div class="rate-form-grid" style="margin-top: 1rem;">
                        <div class="rate-field-group">
                            <label for="modal_motorcycle_base" class="rate-label"><i class="ri-riding-line"></i> Motorcycle Base (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="0.50" min="0" id="modal_motorcycle_base" name="motorcycle_base_rate" class="rate-input" value="{{ $activeRate->motorcycle_base_rate ?? 30.00 }}">
                            </div>
                        </div>

                        <div class="rate-field-group">
                            <label for="modal_motorcycle_hourly" class="rate-label"><i class="ri-riding-line"></i> Motorcycle Hourly (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="0.50" min="0" id="modal_motorcycle_hourly" name="motorcycle_hourly_rate" class="rate-input" value="{{ $activeRate->motorcycle_hourly_rate ?? 15.00 }}">
                            </div>
                        </div>

                        <div class="rate-field-group">
                            <label for="modal_truck_base" class="rate-label"><i class="ri-truck-line"></i> Truck / Bus Base (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="0.50" min="0" id="modal_truck_base" name="truck_base_rate" class="rate-input" value="{{ $activeRate->truck_base_rate ?? 80.00 }}">
                            </div>
                        </div>

                        <div class="rate-field-group">
                            <label for="modal_truck_hourly" class="rate-label"><i class="ri-truck-line"></i> Truck / Bus Hourly (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="0.50" min="0" id="modal_truck_hourly" name="truck_hourly_rate" class="rate-input" value="{{ $activeRate->truck_hourly_rate ?? 30.00 }}">
                            </div>
                        </div>

                        <div class="rate-field-group">
                            <label for="modal_overnight_fee" class="rate-label"><i class="ri-moon-line"></i> Overnight Fee (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="1.00" min="0" id="modal_overnight_fee" name="overnight_fee" class="rate-input" value="{{ $activeRate->overnight_fee ?? 200.00 }}">
                            </div>
                        </div>

                        <div class="rate-field-group">
                            <label for="modal_lost_ticket" class="rate-label"><i class="ri-coupon-3-line"></i> Lost Ticket Penalty (₱)</label>
                            <div class="rate-input-wrap">
                                <span class="currency-prefix">₱</span>
                                <input type="number" step="1.00" min="0" id="modal_lost_ticket" name="lost_ticket_fee" class="rate-input" value="{{ $activeRate->lost_ticket_fee ?? 150.00 }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reason for Modification (Audit Trail) -->
                <div class="rate-field-group" style="margin-top: 1rem;">
                    <label for="modal_change_reason" class="rate-label">
                        <i class="ri-file-text-line"></i> Reason for Modification <span style="font-weight: normal; color: #94A3B8;">(Optional)</span>
                    </label>
                    <input type="text" id="modal_change_reason" name="change_reason" class="rate-input" placeholder="E.g., Updated rate policy approved by management">
                </div>

                <!-- Simulation Preview Callout -->
                <div class="rate-preview-box">
                    <div class="preview-header">
                        <i class="ri-calculator-line"></i>
                        <span>Live Rate Schedule Preview</span>
                    </div>
                    <div class="preview-pills">
                        <span class="preview-pill free">≤ 15 mins: <strong>₱0.00 (FREE)</strong></span>
                        <span class="preview-pill base">16m – <span id="previewBaseHours">3</span>h: <strong>₱<span id="previewBaseRate">50.00</span></strong></span>
                        <span class="preview-pill hourly">Each succeeding hour: <strong>+₱<span id="previewHourlyRate">20.00</span></strong></span>
                    </div>
                </div>
            </div>

            <div class="rate-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeChangeFeeModal()">
                    <i class="ri-close-line"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary btn-save-rates" id="btnSaveRates">
                    <i class="ri-check-line"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Change Fee Modal Styles */
    .rate-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .rate-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .rate-modal-dialog {
        background: #FFFFFF;
        border-radius: 8px;
        max-width: 680px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid #E5E7EB;
        display: flex;
        flex-direction: column;
        animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalSlideUp {
        from { transform: scale(0.98) translateY(8px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }

    .rate-modal-header {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
    }

    .rate-modal-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .rate-icon-badge {
        width: 38px;
        height: 38px;
        background: #111827;
        color: #FFFFFF;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .rate-modal-title h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        line-height: 1.2;
    }

    .rate-modal-title p {
        font-size: 0.78rem;
        color: #6B7280;
        margin: 0;
    }

    .rate-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9CA3AF;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        line-height: 1;
        transition: all 0.15s ease;
    }

    .rate-modal-close:hover {
        color: #111827;
        background: #F3F4F6;
    }

    .rate-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
    }

    .grace-period-banner {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-left: 3px solid #10B981;
        border-radius: 6px;
        padding: 0.85rem 1rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .grace-badge {
        background: #F9FAFB;
        color: #111827;
        border: 1px solid var(--border-color);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .grace-content strong {
        display: block;
        color: #111827;
        font-size: 0.875rem;
        margin-bottom: 0.2rem;
    }

    .grace-content p {
        color: #374151;
        font-size: 0.8rem;
        margin: 0;
        line-height: 1.4;
    }

    .rate-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .rate-field-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .rate-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .rate-label .required {
        color: #EF4444;
    }

    .rate-input-wrap {
        display: flex;
        align-items: center;
        background: #FFFFFF;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        overflow: hidden;
        transition: border-color 0.15s ease;
    }

    .rate-input-wrap:focus-within {
        border-color: #111827;
        box-shadow: 0 0 0 1px #111827;
    }

    .currency-prefix,
    .unit-suffix {
        padding: 0.55rem 0.75rem;
        font-size: 0.825rem;
        font-weight: 600;
        color: #6B7280;
        background: #F9FAFB;
        user-select: none;
        border-right: 1px solid #E5E7EB;
    }
    .unit-suffix {
        border-right: none;
        border-left: 1px solid #E5E7EB;
    }

    .rate-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.55rem 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #111827;
        outline: none;
        width: 100%;
    }

    .rate-help {
        font-size: 0.72rem;
        color: #6B7280;
        line-height: 1.3;
    }

    .category-toggle-wrap {
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #E5E7EB;
    }

    .btn-toggle-categories {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        padding: 0.55rem 0.85rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        transition: all 0.15s ease;
    }

    .btn-toggle-categories:hover {
        background: #F3F4F6;
        color: #111827;
    }

    .rate-preview-box {
        margin-top: 1.25rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        padding: 0.85rem 1rem;
    }

    .preview-header {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6B7280;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .preview-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .preview-pill {
        padding: 0.35rem 0.65rem;
        border-radius: 4px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .preview-pill.free {
        background: #FFFFFF;
        color: #10B981;
        border: 1px solid #10B981;
    }

    .preview-pill.base {
        background: #FFFFFF;
        color: #111827;
        border: 1px solid #D1D5DB;
    }

    .preview-pill.hourly {
        background: #FFFFFF;
        color: #4B5563;
        border: 1px solid #E5E7EB;
    }

    .rate-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        background: #FFFFFF;
    }

    .btn-save-rates {
        background: #111827;
        color: #FFFFFF;
        padding: 0.55rem 1.25rem;
        border-radius: 6px;
        font-size: 0.825rem;
        font-weight: 600;
        border: 1px solid #111827;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background 0.15s ease;
    }

    .btn-save-rates:hover {
        background: #000000;
    }

    @media (max-width: 640px) {
        .rate-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function openChangeFeeModal() {
        const modal = document.getElementById('changeFeeModalOverlay');
        if (modal) {
            modal.classList.add('active');
            updateRatePreview();
        }
    }

    function closeChangeFeeModal() {
        const modal = document.getElementById('changeFeeModalOverlay');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function toggleVehicleRates() {
        const panel = document.getElementById('vehicleRatesPanel');
        const icon = document.getElementById('categoryToggleIcon');
        if (panel) {
            const isHidden = panel.style.display === 'none';
            panel.style.display = isHidden ? 'block' : 'none';
            if (icon) {
                icon.className = isHidden ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line';
            }
        }
    }

    function updateRatePreview() {
        const baseRate = document.getElementById('modal_base_rate')?.value || '50.00';
        const baseHours = document.getElementById('modal_base_hours')?.value || '3';
        const hourlyRate = document.getElementById('modal_hourly_rate')?.value || '20.00';

        const pBaseRate = document.getElementById('previewBaseRate');
        const pBaseHours = document.getElementById('previewBaseHours');
        const pHourlyRate = document.getElementById('previewHourlyRate');

        if (pBaseRate) pBaseRate.textContent = parseFloat(baseRate).toFixed(2);
        if (pBaseHours) pBaseHours.textContent = baseHours;
        if (pHourlyRate) pHourlyRate.textContent = parseFloat(hourlyRate).toFixed(2);
    }

    document.getElementById('modal_base_rate')?.addEventListener('input', updateRatePreview);
    document.getElementById('modal_base_hours')?.addEventListener('input', updateRatePreview);
    document.getElementById('modal_hourly_rate')?.addEventListener('input', updateRatePreview);

    function handleFeeFormSubmit(e) {
        const baseRate = parseFloat(document.getElementById('modal_base_rate').value);
        const baseHours = parseInt(document.getElementById('modal_base_hours').value);
        const hourlyRate = parseFloat(document.getElementById('modal_hourly_rate').value);

        if (isNaN(baseRate) || baseRate < 0) {
            alert('Please enter a valid non-negative base rate.');
            e.preventDefault();
            return false;
        }

        if (isNaN(baseHours) || baseHours < 1) {
            alert('Base coverage period must be at least 1 hour.');
            e.preventDefault();
            return false;
        }

        if (isNaN(hourlyRate) || hourlyRate < 0) {
            alert('Please enter a valid non-negative hourly rate.');
            e.preventDefault();
            return false;
        }

        if (!confirm(`Confirm updated parking rates?\n\n• Base Fee: ₱${baseRate.toFixed(2)} (First ${baseHours}h)\n• Succeeding Rate: ₱${hourlyRate.toFixed(2)}/hour\n• Free Grace Period: 15 Minutes (Mandatory ₱0.00)`)) {
            e.preventDefault();
            return false;
        }

        return true;
    }
</script>
