<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - SABINO Parking Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Remix Icon (Outline Set) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Base Styles: Minimalist Black & White Theme -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-black: #111827;
            --primary-dark: #000000;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-tertiary: #9CA3AF;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F9FAFB;
            --border-color: #E5E7EB;
            --border-subtle: #F3F4F6;
            --sidebar-width: 240px;
            --header-height: 64px;
            --status-green: #10B981;
            --status-red: #EF4444;
            --radius-sm: 6px;
            --radius-md: 8px;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #FFFFFF;
            color: var(--text-primary);
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── Minimalist White Sidebar ───────────────────────────── */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #FFFFFF;
            color: #111827;
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.25s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.15rem 1.25rem 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #111827;
        }

        .sidebar-logo-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .sidebar-brand-details {
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand-text {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #111827;
            line-height: 1.1;
        }

        .sidebar-brand-sub {
            font-size: 0.625rem;
            color: #6B7280;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 0.85rem 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .nav-section {
            margin-bottom: 0.65rem;
        }

        .nav-section-title {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #9CA3AF;
            padding: 0 1.25rem;
            margin-bottom: 0.35rem;
            letter-spacing: 0.05em;
        }

        .nav-items {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 1px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 1.25rem;
            color: #4B5563;
            text-decoration: none;
            transition: all 0.15s ease;
            font-weight: 500;
            font-size: 0.875rem;
            position: relative;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: #F9FAFB;
            color: #111827;
        }

        .nav-link.active {
            background: #F3F4F6;
            color: #111827;
            font-weight: 600;
            border-left: 3px solid #111827;
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: inherit;
            flex-shrink: 0;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border-color);
            margin: 0.65rem 1.25rem;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--border-color);
            background: #FFFFFF;
        }

        .sidebar-logout-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.5rem 0.5rem;
            color: #EF4444;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 6px;
            transition: background 0.15s ease;
        }

        .sidebar-logout-link:hover {
            background: #FEF2F2;
        }

        /* ─── Main Content Wrapper & Header ──────────────────────── */

        /* Main Content */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            height: var(--header-height);
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-color);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title-container {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .header-subtitle {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .live-clock-badge {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.75rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.3rem 0.65rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .header-user:hover {
            border-color: #CBD5E1;
            background: #F1F5F9;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: #111827;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.7rem;
            color: var(--text-tertiary);
            font-weight: 500;
        }

        .content {
            flex: 1;
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9375rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .alert i {
            font-size: 1.25rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .alert-success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn i {
            font-size: 1.1rem;
            line-height: 1;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
        }

        .btn-entry {
            background: var(--entry-color);
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-entry:hover {
            background: var(--entry-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-exit {
            background: var(--exit-color);
            color: white;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.2);
        }

        .btn-exit:hover {
            background: var(--exit-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: #F1F5F9;
            color: var(--text-primary);
            border-color: #CBD5E1;
        }

        /* Card */
        .card {
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        /* License Plate Display Standard */
        .plate-badge {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            background: #1E293B;
            color: #FFFFFF;
            font-weight: 800;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 0.12em;
            border-radius: 6px;
            font-size: 0.875rem;
            border: 2px solid #475569;
            box-shadow: inset 0 0 4px rgba(0,0,0,0.5);
            text-transform: uppercase;
        }

        /* Plate Photo Thumbnail in Tables */
        .plate-thumbnail-container {
            background: #0B1120;
            border-radius: 6px;
            padding: 2px;
            border: 1px solid #334155;
            width: 90px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .plate-thumbnail-container:hover {
            transform: scale(1.08);
            border-color: #3B82F6;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
            z-index: 5;
        }

        .plate-thumbnail-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
            display: block;
        }

        .plate-graphic-fallback {
            background: #F8FAFC;
            color: #0F172A;
            width: 100%;
            height: 100%;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #1E293B;
            font-family: 'Courier New', monospace;
            font-weight: 900;
            line-height: 1.1;
            padding: 2px;
        }

        .plate-graphic-fallback .plate-sub-text {
            font-size: 0.45rem;
            color: #64748B;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .plate-graphic-fallback .plate-main-text {
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #0F172A;
        }

        /* Lightbox Modal for Plate Picture Preview */
        .plate-lightbox-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .plate-lightbox-modal.active {
            display: flex;
            opacity: 1;
        }

        .plate-lightbox-content {
            background: #1E293B;
            border: 1px solid #334155;
            border-radius: 14px;
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }

        .plate-lightbox-modal.active .plate-lightbox-content {
            transform: scale(1);
        }

        .plate-lightbox-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #334155;
            background: #0F172A;
        }

        .plate-lightbox-header h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .plate-lightbox-close {
            background: transparent;
            border: none;
            color: #94A3B8;
            font-size: 1.25rem;
            cursor: pointer;
            border-radius: 6px;
            padding: 2px 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .plate-lightbox-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
        }

        .plate-lightbox-body {
            padding: 1.25rem;
            background: #0B1120;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .plate-lightbox-img {
            max-width: 100%;
            max-height: 380px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            padding: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .header {
                padding: 0 1rem;
            }

            .content {
                padding: 1rem;
            }

            .live-clock-badge {
                display: none;
            }
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>

    @yield('additional-styles')
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                <img src="{{ asset('images/sabino-logo.png') }}" alt="SABINO Logo" class="sidebar-logo-img">
                <div class="sidebar-brand-details">
                    <span class="sidebar-brand-text">SABINO</span>
                    <span class="sidebar-brand-sub">PARKING MANAGEMENT</span>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3 class="nav-section-title">Gate Management</h3>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-dashboard-line"></i></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.dual') }}" class="nav-link {{ request()->routeIs('plate-ocr.dual') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-vidicon-line"></i></span>
                            <span>Dual Live Cameras</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.index') }}" class="nav-link entry-link {{ request()->routeIs('plate-ocr.index') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-login-box-line"></i></span>
                            <span>Gate 1 (Entry Cam)</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.exit-scan') }}" class="nav-link exit-link {{ request()->routeIs('plate-ocr.exit-scan') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-logout-box-r-line"></i></span>
                            <span>Gate 2 (Exit Cam)</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.history') }}" class="nav-link {{ request()->routeIs('plate-ocr.history') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-file-list-3-line"></i></span>
                            <span>Parking Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('memberships.index') }}" class="nav-link {{ request()->routeIs('memberships*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-user-star-line"></i></span>
                            <span>Memberships</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-divider"></div>

            <div class="nav-section">
                <h3 class="nav-section-title">System</h3>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="{{ route('analytics') }}" class="nav-link {{ request()->routeIs('analytics*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-bar-chart-2-line"></i></span>
                            <span>Analytics & Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-settings-3-line"></i></span>
                            <span>Gate Settings</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Log Out at bottom of sidebar -->
            <div class="sidebar-footer">
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="sidebar-logout-link" title="Log Out">
                    <i class="ri-logout-box-r-line"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Hidden Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu"><i class="ri-menu-line"></i></button>
                <div class="header-title-container">
                    <h1 class="header-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="live-clock-badge">
                    <i class="ri-time-line"></i>
                    <span id="liveClockDisplay">--:--:--</span>
                </div>
                <div class="header-user">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'admin', 0, 2)) }}</div>
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name ?? 'admin' }}</div>
                        <div class="user-role">System Admin</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="content">
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-error">
                        <i class="ri-error-warning-line"></i>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Live Clock
        function updateLiveClock() {
            const el = document.getElementById('liveClockDisplay');
            if (el) {
                const now = new Date();
                el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    toggleSidebar();
                }
            });
        });

        // Global Plate Preview Lightbox Handlers
        function openPlatePreview(imgUrl, plateNumber) {
            if (!imgUrl) return;
            const modal = document.getElementById('plateLightboxModal');
            const img = document.getElementById('plateLightboxImage');
            const title = document.getElementById('plateLightboxTitle');
            if (modal && img) {
                img.src = imgUrl;
                if (title) title.textContent = 'Plate Photo: ' + (plateNumber || '');
                modal.classList.add('active');
            }
        }

        function closePlatePreview(e) {
            const modal = document.getElementById('plateLightboxModal');
            if (modal) modal.classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePlatePreview();
        });
    </script>

    <!-- Global Plate Picture Lightbox Modal -->
    <div id="plateLightboxModal" class="plate-lightbox-modal" onclick="closePlatePreview(event)">
        <div class="plate-lightbox-content" onclick="event.stopPropagation()">
            <div class="plate-lightbox-header">
                <h4><i class="ri-car-line" style="color: #3B82F6;"></i> <span id="plateLightboxTitle">Plate Photo</span></h4>
                <button type="button" class="plate-lightbox-close" onclick="closePlatePreview()"><i class="ri-close-line"></i></button>
            </div>
            <div class="plate-lightbox-body">
                <img id="plateLightboxImage" src="" alt="License Plate Photo" class="plate-lightbox-img">
            </div>
        </div>
    </div>

    @yield('additional-scripts')
</body>
</html>

