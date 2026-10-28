<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SABINO Parking Management</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Remix Icon (Outline Set) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-black: #111827;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #9CA3AF;
            --bg-primary: #FFFFFF;
            --border-color: #E5E7EB;
            --status-green: #10B981;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #FAFAFA;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2.25rem 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-logo-img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 0.65rem;
        }

        .brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.01em;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 0.6875rem;
            color: #6B7280;
            margin-top: 0.25rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 1.15rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.45rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 0.85rem;
            color: #9CA3AF;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.65rem 0.85rem 0.65rem 2.5rem;
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: #111827;
            font-size: 0.875rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-input::placeholder {
            color: #9CA3AF;
        }

        .form-input:focus {
            border-color: #111827;
        }

        .password-toggle {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: #9CA3AF;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .password-toggle:hover {
            color: #111827;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            font-size: 0.8125rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            color: #4B5563;
            cursor: pointer;
            user-select: none;
        }

        .remember-checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
        }

        .alert {
            padding: 0.65rem 0.85rem;
            border-radius: 6px;
            margin-bottom: 1.15rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.8125rem;
            line-height: 1.4;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #B91C1C;
        }

        .alert-success {
            background: #F0FDF4;
            border: 1px solid #86EFAC;
            color: #15803D;
        }

        .btn-login {
            width: 100%;
            padding: 0.7rem 1.25rem;
            background: #111827;
            color: #FFFFFF;
            border: 1px solid #111827;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .btn-login:hover {
            background: #000000;
            border-color: #000000;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">

            <!-- Brand Header -->
            <div class="brand-header">
                <img src="{{ asset('images/sabino-logo.png') }}" alt="SABINO Logo" class="brand-logo-img">
                <h1 class="brand-name">SABINO</h1>
                <p class="brand-subtitle">PARKING MANAGEMENT</p>
            </div>

            <!-- Validation & Session Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <i class="ri-error-warning-line"></i>
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
                            placeholder="Enter username or email"
                            value="{{ old('login', 'admin') }}"
                            required
                            autofocus
                        />
                        <i class="ri-user-line input-icon"></i>
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
                        <i class="ri-lock-line input-icon"></i>
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
