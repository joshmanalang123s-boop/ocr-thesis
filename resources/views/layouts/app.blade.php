<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Autotrace Parking Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Base Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563EB;
            --primary-dark: #1D4ED8;
            --primary-light: #EFF6FF;
            --entry-color: #10B981;
            --entry-dark: #059669;
            --entry-light: #ECFDF5;
            --exit-color: #F97316;
            --exit-dark: #EA580C;
            --exit-light: #FFF7ED;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --text-tertiary: #94A3B8;
            --bg-primary: #F8FAFC;
            --bg-secondary: #FFFFFF;
            --border-color: #E2E8F0;
            --sidebar-width: 260px;
            --header-height: 70px;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #0F172A;
            color: #F8FAFC;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #FFFFFF;
        }

        .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563EB 0%, #0EA5E9 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .sidebar-brand-details {
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand-text {
            font-size: 1.125rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #FFFFFF;
        }

        .sidebar-brand-sub {
            font-size: 0.7rem;
            color: #94A3B8;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .system-status-pill {
            margin: 1.25rem 1.5rem 0.5rem 1.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #34D399;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10B981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
            100% { opacity: 1; transform: scale(1); }
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .nav-section {
            margin-bottom: 1.75rem;
        }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748B;
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.08em;
        }

        .nav-items {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #94A3B8;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.9375rem;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #FFFFFF;
        }

        .nav-link.active {
            background: rgba(37, 99, 235, 0.16);
            color: #FFFFFF;
            font-weight: 600;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #3B82F6;
            border-radius: 0 4px 4px 0;
        }

        .nav-link.entry-link.active::before {
            background: #10B981;
        }

        .nav-link.exit-link.active::before {
            background: #F97316;
        }

        .nav-icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        /* Main Content */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            height: var(--header-height);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .header-title-container {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .header-subtitle {
            font-size: 0.78125rem;
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .live-clock-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0.85rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .header-user:hover {
            border-color: #CBD5E1;
            background: #F1F5F9;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563EB 0%, #0EA5E9 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8125rem;
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
                <div class="sidebar-brand-icon"><i class="ri-parking-box-line"></i></div>
                <div class="sidebar-brand-details">
                    <span class="sidebar-brand-text">Autotrace</span>
                    <span class="sidebar-brand-sub">Parking Management</span>
                </div>
            </a>
        </div>

        <div class="system-status-pill">
            <div class="status-dot"></div>
            <span>Gates Operational</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3 class="nav-section-title">Gate Management</h3>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-dashboard-3-line"></i></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.dual') }}" class="nav-link {{ request()->routeIs('plate-ocr.dual') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-vidicon-line" style="color: #3B82F6;"></i></span>
                            <span>Dual Live Cameras</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.index') }}" class="nav-link entry-link {{ request()->routeIs('plate-ocr.index') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-login-box-line" style="color: #10B981;"></i></span>
                            <span>Gate 1 (Entry Cam)</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.exit-scan') }}" class="nav-link exit-link {{ request()->routeIs('plate-ocr.exit-scan') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-logout-box-r-line" style="color: #F97316;"></i></span>
                            <span>Gate 2 (Exit Cam)</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('plate-ocr.history') }}" class="nav-link {{ request()->routeIs('plate-ocr.history') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-history-line"></i></span>
                            <span>Parking Logs</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h3 class="nav-section-title">System</h3>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ri-settings-4-line"></i></span>
                            <span>Gate Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

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
                    <i class="ri-time-line" style="color: #2563EB;"></i>
                    <span id="liveClockDisplay">--:--:--</span>
                </div>
                <div class="header-user">
                    <div class="user-avatar">OP</div>
                    <div class="user-info">
                        <div class="user-name">Gate Operator</div>
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
    </script>

    @yield('additional-scripts')
</body>
</html>

