@extends('layouts.app')

@section('title', 'Settings - Plate QR System')
@section('page-title', 'Settings')

@section('additional-styles')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 0.9375rem;
    }

    /* Settings Grid */
    .settings-grid {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 2rem;
    }

    /* Settings Sidebar */
    .settings-nav {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        height: fit-content;
        position: sticky;
        top: calc(var(--header-height) + 2rem);
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-weight: 500;
        font-size: 0.9375rem;
        margin-bottom: 0.25rem;
    }

    .settings-nav-item:hover {
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .settings-nav-item.active {
        background: var(--primary-light);
        color: var(--primary-color);
    }

    .settings-nav-item i {
        font-size: 1.125rem;
    }

    /* Settings Content */
    .settings-content {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .settings-section {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }

    .section-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .section-header h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-header h2 i {
        color: var(--primary-color);
    }

    .section-header p {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .section-body {
        padding: 1.5rem;
    }

    /* Setting Item */
    .setting-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .setting-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .setting-item:first-child {
        padding-top: 0;
    }

    .setting-info {
        flex: 1;
    }

    .setting-label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
        font-size: 0.9375rem;
    }

    .setting-description {
        font-size: 0.875rem;
        color: var(--text-tertiary);
        line-height: 1.5;
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 28px;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--border-color);
        transition: 0.3s;
        border-radius: 34px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .toggle-switch input:checked + .toggle-slider {
        background: var(--primary-color);
    }

    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 0.9375rem;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 0.625rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9375rem;
        background: var(--bg-secondary);
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(91, 111, 237, 0.1);
    }

    .form-help {
        font-size: 0.8125rem;
        color: var(--text-tertiary);
        margin-top: 0.5rem;
    }

    /* Danger Zone */
    .danger-zone {
        border: 1px solid #FEE2E2;
        background: #FEF2F2;
    }

    .danger-zone .section-header {
        background: #FEE2E2;
        border-bottom-color: #FECACA;
    }

    .danger-zone .section-header h2 {
        color: #991B1B;
    }

    .danger-zone .section-header h2 i {
        color: #DC2626;
    }

    .btn-danger {
        background: #DC2626;
        color: white;
    }

    .btn-danger:hover {
        background: #B91C1C;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }

        .settings-nav {
            position: static;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .settings-nav-item {
            margin-bottom: 0;
        }
    }

    @media (max-width: 640px) {
        .settings-nav {
            grid-template-columns: 1fr;
        }

        .setting-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1><i class="ri-settings-3-line" style="margin-right: 0.5rem;"></i>Settings</h1>
    <p>Manage your application preferences and configurations</p>
</div>

<!-- Settings Grid -->
<div class="settings-grid">
    <!-- Settings Navigation -->
    <nav class="settings-nav">
        <a href="#general" class="settings-nav-item active">
            <i class="ri-settings-2-line"></i>
            <span>General</span>
        </a>
        <a href="#camera" class="settings-nav-item">
            <i class="ri-camera-line"></i>
            <span>Camera</span>
        </a>
        <a href="#qr-codes" class="settings-nav-item">
            <i class="ri-qr-code-line"></i>
            <span>QR Codes</span>
        </a>
        <a href="#notifications" class="settings-nav-item">
            <i class="ri-notification-line"></i>
            <span>Notifications</span>
        </a>
        <a href="#data" class="settings-nav-item">
            <i class="ri-database-2-line"></i>
            <span>Data</span>
        </a>
    </nav>

    <!-- Settings Content -->
    <div class="settings-content">
        <!-- General Settings -->
        <div class="settings-section" id="general">
            <div class="section-header">
                <h2><i class="ri-settings-2-line"></i>General Settings</h2>
                <p>Configure basic application preferences</p>
            </div>
            <div class="section-body">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Auto-save detections</div>
                        <div class="setting-description">Automatically save all detected plates to history</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Sound effects</div>
                        <div class="setting-description">Play sound when plate is detected</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Date format</div>
                        <div class="setting-description">Choose how dates are displayed</div>
                    </div>
                    <select class="form-select" style="max-width: 200px;">
                        <option>MM/DD/YYYY</option>
                        <option>DD/MM/YYYY</option>
                        <option selected>YYYY-MM-DD</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Camera Settings -->
        <div class="settings-section" id="camera">
            <div class="section-header">
                <h2><i class="ri-camera-line"></i>Camera Settings</h2>
                <p>Configure camera and scanning preferences</p>
            </div>
            <div class="section-body">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Camera resolution</div>
                        <div class="setting-description">Higher resolution improves detection accuracy</div>
                    </div>
                    <select class="form-select" style="max-width: 200px;">
                        <option>720p (1280×720)</option>
                        <option selected>1080p (1920×1080)</option>
                        <option>4K (3840×2160)</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Auto-focus</div>
                        <div class="setting-description">Automatically focus camera on license plates</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Guide overlay</div>
                        <div class="setting-description">Show positioning guide when scanning</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- QR Code Settings -->
        <div class="settings-section" id="qr-codes">
            <div class="section-header">
                <h2><i class="ri-qr-code-line"></i>QR Code Settings</h2>
                <p>Customize QR code generation options</p>
            </div>
            <div class="section-body">
                <div class="form-group">
                    <label class="form-label">QR Code size</label>
                    <select class="form-select">
                        <option>Small (200×200)</option>
                        <option selected>Medium (400×400)</option>
                        <option>Large (600×600)</option>
                    </select>
                    <div class="form-help">Larger sizes are better for printing</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Error correction level</label>
                    <select class="form-select">
                        <option>Low (7%)</option>
                        <option selected>Medium (15%)</option>
                        <option>High (30%)</option>
                    </select>
                    <div class="form-help">Higher levels allow QR code to work even if partially damaged</div>
                </div>

                <div class="setting-item" style="border: none; padding: 0; margin-top: 1.5rem;">
                    <div class="setting-info">
                        <div class="setting-label">Include timestamp in QR code</div>
                        <div class="setting-description">Embed detection time in generated QR codes</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-section" id="notifications">
            <div class="section-header">
                <h2><i class="ri-notification-line"></i>Notification Settings</h2>
                <p>Manage how you receive notifications</p>
            </div>
            <div class="section-body">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Detection alerts</div>
                        <div class="setting-description">Show notification when plate is detected</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Error notifications</div>
                        <div class="setting-description">Alert when detection fails</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Email notifications</div>
                        <div class="setting-description">Receive detection summaries via email</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="settings-section" id="data">
            <div class="section-header">
                <h2><i class="ri-database-2-line"></i>Data Management</h2>
                <p>Manage your detection data and history</p>
            </div>
            <div class="section-body">
                <div class="form-group">
                    <label class="form-label">Auto-delete old records</label>
                    <select class="form-select">
                        <option selected>Never</option>
                        <option>After 30 days</option>
                        <option>After 60 days</option>
                        <option>After 90 days</option>
                        <option>After 1 year</option>
                    </select>
                    <div class="form-help">Automatically remove old detection records</div>
                </div>

                <div class="setting-item" style="border: none; padding: 0; margin-top: 1.5rem;">
                    <div class="setting-info">
                        <div class="setting-label">Backup data automatically</div>
                        <div class="setting-description">Create daily backups of detection history</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <button class="btn btn-secondary" style="width: 100%; margin-bottom: 0.75rem;">
                        <i class="ri-download-line"></i>
                        <span>Export All Data</span>
                    </button>
                    <button class="btn btn-secondary" style="width: 100%;">
                        <i class="ri-upload-line"></i>
                        <span>Import Data</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="settings-section danger-zone">
            <div class="section-header">
                <h2><i class="ri-alert-line"></i>Danger Zone</h2>
                <p>Irreversible actions that affect your data</p>
            </div>
            <div class="section-body">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Clear all detection history</div>
                        <div class="setting-description">Permanently delete all detection records and QR codes</div>
                    </div>
                    <button class="btn btn-danger" onclick="confirmClearHistory()">
                        <i class="ri-delete-bin-line"></i>
                        <span>Clear History</span>
                    </button>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Reset all settings</div>
                        <div class="setting-description">Restore all settings to default values</div>
                    </div>
                    <button class="btn btn-danger" onclick="confirmResetSettings()">
                        <i class="ri-restart-line"></i>
                        <span>Reset Settings</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-secondary">
                <i class="ri-close-line"></i>
                <span>Cancel</span>
            </button>
            <button class="btn btn-primary" onclick="saveSettings()">
                <i class="ri-save-line"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('additional-scripts')
<script>
    function saveSettings() {
        // Show success message
        alert('Settings saved successfully!');
        // In production, send AJAX request to save settings
    }

    function confirmClearHistory() {
        if (confirm('⚠️ Are you sure you want to clear all detection history?\n\nThis action cannot be undone and will permanently delete all records.')) {
            alert('History cleared successfully!');
            // In production, send request to clear history
        }
    }

    function confirmResetSettings() {
        if (confirm('⚠️ Are you sure you want to reset all settings to default?\n\nThis action cannot be undone.')) {
            alert('Settings reset to default!');
            // In production, send request to reset settings
            location.reload();
        }
    }

    // Smooth scroll to sections
    document.querySelectorAll('.settings-nav-item').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Update active state
                document.querySelectorAll('.settings-nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });

    // Intersection Observer to update active nav on scroll
    const sections = document.querySelectorAll('.settings-section');
    const navItems = document.querySelectorAll('.settings-nav-item');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                navItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('href') === `#${id}`) {
                        item.classList.add('active');
                    }
                });
            }
        });
    }, {
        rootMargin: '-100px 0px -66%',
        threshold: 0
    });

    sections.forEach(section => {
        observer.observe(section);
    });
</script>
@endsection
