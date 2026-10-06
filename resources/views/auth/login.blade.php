<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - Autotrace Parking Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

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
            --accent-cyan: #0EA5E9;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --text-muted: #94A3B8;
            --bg-dark: #0B0F17;
            --card-bg: rgba(15, 23, 42, 0.75);
            --card-border: rgba(255, 255, 255, 0.12);
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background-color: var(--bg-dark);
            color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 1.5rem;
        }

        /* Ambient Glow Background Effects */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            filter: blur(60px);
            animation: pulseGlow 8s infinite alternate ease-in-out;
        }

        .glow-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            filter: blur(70px);
            animation: pulseGlow 10s infinite alternate-reverse ease-in-out;
        }

        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.8;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.7; }
            100% { transform: scale(1.1) translate(20px, 20px); opacity: 1; }
        }

        /* Container & Card */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6),
                        0 0 0 1px rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        /* Header Section */
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563EB 0%, #0EA5E9 100%);
            border-radius: 14px;
            font-size: 1.85rem;
            color: white;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            margin-bottom: 1rem;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
            font-weight: 400;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.35rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #E2E8F0;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: #64748B;
            font-size: 1.15rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 0.9375rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input::placeholder {
            color: #475569;
        }

        .form-input:focus {
            border-color: #3B82F6;
            background: rgba(15, 23, 42, 0.85);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #3B82F6;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: #64748B;
            font-size: 1.15rem;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #94A3B8;
        }

        /* Row options */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.8125rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #94A3B8;
            cursor: pointer;
            user-select: none;
        }

        .remember-checkbox {
            appearance: none;
            width: 16px;
            height: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            background: rgba(15, 23, 42, 0.6);
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.2s ease;
        }

        .remember-checkbox::before {
            content: "\EB7B";
            font-family: 'remixicon';
            font-size: 0.75rem;
            color: white;
            transform: scale(0);
            transition: transform 0.15s ease-in-out;
        }

        .remember-checkbox:checked {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .remember-checkbox:checked::before {
            transform: scale(1);
        }

        /* Alert styling */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.85rem;
            line-height: 1.4;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6EE7B7;
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 0.85rem 1.25rem;
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            transition: all 0.25 ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.55);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 1.1rem;
        }

        /* Footer Status Pill */
        .system-status-footer {
            margin-top: 1.75rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #64748B;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10B981;
            animation: statusPulse 2s infinite;
        }

        @keyframes statusPulse {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- Ambient Glow Background -->
    <div class="ambient-bg">
        <div class="glow-1"></div>
        <div class="glow-2"></div>
        <div class="grid-overlay"></div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">

            <!-- Brand Header -->
            <div class="brand-header">
                <div class="brand-logo">
                    <i class="ri-parking-box-line"></i>
                </div>
                <h1 class="brand-name">Autotrace System</h1>
                <p class="brand-subtitle">License Plate Recognition & Gate Control</p>
            </div>


            <!-- Validation & Session Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success">
                    <i class="ri-checkbox-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <i class="ri-error-warning-fill"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" id="loginForm">
                @csrf

                <!-- Username Input -->
                <div class="form-group">
                    <label for="loginInput" class="form-label">Username or Email</label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="loginInput"
                            name="login"
                            class="form-input"
                            placeholder="Enter 'admin'"
                            value="{{ old('login', 'admin') }}"
                            required
                            autofocus
                        />
                        <i class="ri-user-3-line input-icon"></i>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="passwordInput" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="passwordInput"
                            name="password"
                            class="form-input"
                            placeholder="Enter password"
                            required
                        />
                        <i class="ri-lock-password-line input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility()" aria-label="Toggle Password Visibility">
                            <i class="ri-eye-line" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Options: Remember Me -->
                <div class="form-options">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" class="remember-checkbox" checked>
                        <span>Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="btnLogin">
                    <span>Sign In</span>
                    <i class="ri-arrow-right-line"></i>
                </button>
            </form>

            <!-- Footer Status -->
            <div class="system-status-footer">
                <span class="status-dot"></span>
                <span>Gate Systems Operational &bull; Version 2.0</span>
            </div>

        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.className = 'ri-eye-off-line';
            } else {
                pwdInput.type = 'password';
                eyeIcon.className = 'ri-eye-line';
            }
        }


        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Authenticating...';
        });
    </script>
</body>
</html>
