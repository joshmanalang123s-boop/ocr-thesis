<!-- Correct License Plate Modal -->
<div class="correct-modal-overlay" id="correctPlateModalOverlay" onclick="if(event.target === this) closeCorrectPlateModal()">
    <div class="correct-modal-dialog">
        <div class="correct-modal-header">
            <div class="correct-modal-title">
                <div class="correct-icon-badge"><i class="ri-edit-line"></i></div>
                <div>
                    <h3>Correct License Plate</h3>
                    <p>Verify and update misrecognized OCR license plate</p>
                </div>
            </div>
            <button type="button" class="correct-modal-close" onclick="closeCorrectPlateModal()" aria-label="Close modal">&times;</button>
        </div>

        <form id="correctPlateForm" method="POST" action="" onsubmit="return handleCorrectPlateSubmit(event)">
            @csrf
            <div class="correct-modal-body">
                <!-- Vehicle Preview Details -->
                <div class="plate-correction-preview">
                    <div class="preview-img-box" id="correctPlateImgWrap" style="display: none;">
                        <img id="correctPlateImg" src="" alt="Captured Vehicle">
                    </div>
                    <div class="preview-meta">
                        <div class="meta-row">
                            <span class="meta-label">Current OCR Read:</span>
                            <span class="meta-value current-plate" id="correctOldPlateText">—</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Detection Time:</span>
                            <span class="meta-value" id="correctEntryTimeText">—</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Gate / Camera:</span>
                            <span class="meta-value" id="correctGateText">—</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">OCR Confidence:</span>
                            <span class="meta-value" id="correctConfidenceText">—</span>
                        </div>
                    </div>
                </div>

                <!-- Input for Corrected Plate -->
                <div class="rate-field-group" style="margin-top: 1.25rem;">
                    <label for="corrected_plate_input" class="rate-label">
                        <i class="ri-car-line"></i> Corrected License Plate Number <span class="required">*</span>
                    </label>
                    <input type="text" id="corrected_plate_input" name="plate_number" class="rate-input" style="font-size: 1.15rem; letter-spacing: 2px; text-transform: uppercase;" required placeholder="ABC 1234">
                    <span class="rate-help">Standard alphanumeric plate format (e.g., ABC 1234, NEY 1228).</span>
                </div>

                <!-- Reason for correction (Audit trail) -->
                <div class="rate-field-group" style="margin-top: 1rem;">
                    <label for="correction_reason_input" class="rate-label">
                        <i class="ri-file-list-3-line"></i> Reason for Correction <span class="required">*</span>
                    </label>
                    <select id="correction_reason_select" class="rate-select" onchange="handleReasonChange(this)" style="margin-bottom: 0.5rem;">
                        <option value="Character misread by OCR (e.g. 0 vs O, 8 vs B)">Character misread by OCR (e.g. 0 vs O, 8 vs B)</option>
                        <option value="Dirty or shadowed license plate">Dirty or shadowed license plate</option>
                        <option value="Temporary or commemorative plate format">Temporary or commemorative plate format</option>
                        <option value="Angle/reflection glare distortion">Angle/reflection glare distortion</option>
                        <option value="Other">Other (specify below)...</option>
                    </select>
                    <input type="text" id="correction_reason_input" name="correction_reason" class="rate-input" placeholder="Specify correction reason..." value="Character misread by OCR">
                </div>
            </div>

            <div class="correct-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCorrectPlateModal()">
                    <i class="ri-close-line"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary btn-save-correction" id="btnSaveCorrection">
                    <i class="ri-check-line"></i> Save & Audit Record
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .correct-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        z-index: 10001;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .correct-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .correct-modal-dialog {
        background: #FFFFFF;
        border-radius: 8px;
        max-width: 580px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid #E5E7EB;
        display: flex;
        flex-direction: column;
        animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .correct-modal-header {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
    }

    .correct-modal-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .correct-icon-badge {
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

    .correct-modal-title h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .correct-modal-title p {
        font-size: 0.78rem;
        color: #6B7280;
        margin: 0;
    }

    .correct-modal-close {
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

    .correct-modal-close:hover {
        color: #111827;
        background: #F3F4F6;
    }

    .correct-modal-body {
        padding: 1.5rem;
    }

    .plate-correction-preview {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        padding: 0.85rem 1rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .preview-img-box {
        width: 100px;
        height: 70px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #D1D5DB;
        background: #111827;
        flex-shrink: 0;
    }

    .preview-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-meta {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        font-size: 0.8rem;
    }

    .meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .meta-label {
        color: #6B7280;
    }

    .meta-value {
        font-weight: 600;
        color: #111827;
    }

    .meta-value.current-plate {
        font-family: monospace;
        font-weight: 700;
        color: #111827;
        background: #F3F4F6;
        padding: 0.15rem 0.5rem;
        border-radius: 4px;
        border: 1px solid #E5E7EB;
    }

    .rate-select {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        background: #FFFFFF;
        font-size: 0.85rem;
        color: #111827;
        font-weight: 500;
        outline: none;
    }

    .rate-select:focus {
        border-color: #111827;
    }

    .correct-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        background: #FFFFFF;
    }

    .btn-save-correction {
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

    .btn-save-correction:hover {
        background: #000000;
    }
</style>

<script>
    function openCorrectPlateModal(id, plate, entryTime, gate, confidence, imageUrl) {
        const modal = document.getElementById('correctPlateModalOverlay');
        const form = document.getElementById('correctPlateForm');
        const oldPlateText = document.getElementById('correctOldPlateText');
        const entryTimeText = document.getElementById('correctEntryTimeText');
        const gateText = document.getElementById('correctGateText');
        const confText = document.getElementById('correctConfidenceText');
        const input = document.getElementById('corrected_plate_input');
        const imgWrap = document.getElementById('correctPlateImgWrap');
        const img = document.getElementById('correctPlateImg');

        if (modal && form) {
            form.action = `/plate-ocr/${id}/correct`;
            if (oldPlateText) oldPlateText.textContent = plate;
            if (entryTimeText) entryTimeText.textContent = entryTime || 'N/A';
            if (gateText) gateText.textContent = gate || 'Gate 1';
            if (confText) confText.textContent = confidence ? (confidence + '%') : 'N/A';
            if (input) {
                input.value = plate;
                setTimeout(() => input.focus(), 150);
            }

            if (imageUrl && img && imgWrap) {
                img.src = imageUrl;
                imgWrap.style.display = 'block';
            } else if (imgWrap) {
                imgWrap.style.display = 'none';
            }

            modal.classList.add('active');
        }
    }

    function closeCorrectPlateModal() {
        const modal = document.getElementById('correctPlateModalOverlay');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function handleReasonChange(select) {
        const reasonInput = document.getElementById('correction_reason_input');
        if (reasonInput) {
            if (select.value === 'Other') {
                reasonInput.value = '';
                reasonInput.focus();
            } else {
                reasonInput.value = select.value;
            }
        }
    }

    function handleCorrectPlateSubmit(e) {
        const input = document.getElementById('corrected_plate_input');
        const val = input ? input.value.trim() : '';

        if (!val || val.length < 3) {
            alert('Please enter a valid license plate number.');
            e.preventDefault();
            return false;
        }

        return true;
    }
</script>
