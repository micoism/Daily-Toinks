<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if (in_array($role, ['admin', 'manager', 'rider'])) {
        header('Location: /normss/admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}
$pageTitle = "Login";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .mfa-step {
            display: none;
        }
        .mfa-step.active {
            display: block;
        }
        .mfa-code-input {
            font-size: 1.5rem !important;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
        .lock-warning {
            background: #FFF3E0;
            border: 1px solid #FFE0B2;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #E65100;
        }
        .lock-warning.locked {
            background: #FFEBEE;
            border-color: #FFCDD2;
            color: #B71C1C;
        }
        .attempts-warning {
            font-size: 0.8rem;
            color: #E65100;
            margin-top: 0.4rem;
            font-weight: 600;
        }
    </style>
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <span class="logo-secure">Daily</span><span class="logo-store">Toinks</span>
            </a>
            <h1 id="auth-title">Welcome Back</h1>
            <p id="auth-subtitle" style="color: var(--text-light);">Login to your account</p>
        </div>

        <!-- Step 1: Credentials -->
        <div id="login-step" class="mfa-step active">
            <form id="login-form">
                <div id="lock-warning" style="display:none;"></div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="email" class="form-input" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="password" class="form-input" required placeholder="Enter your password">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <div id="error-message" class="error-message" style="display: none;"></div>
                <div id="success-message" class="success-message" style="display: none;"></div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="login-btn">
                    Login
                </button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot-password.php">Forgot password?</a></p>
            </div>
        </div>

        <!-- Step 2: MFA Verification -->
        <div id="mfa-step" class="mfa-step">
            <form id="mfa-form">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">🔐</div>
                    <p style="color: var(--text-light); font-size: 0.9rem;">
                        Open your <strong>Google Authenticator</strong> app and enter the 6-digit code.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label">Authenticator Code</label>
                    <input type="text" id="mfa-code" class="form-input mfa-code-input" required
                        placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code">
                </div>

                <div id="mfa-error" class="error-message" style="display: none;"></div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="mfa-btn">
                    Verify Code
                </button>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="#" onclick="backToLogin()" style="color: var(--text-light); font-size: 0.85rem;">← Back to login</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        let mfaToken = '';

        // Show banner if redirected here due to session timeout / disabled account
        (function() {
            const params = new URLSearchParams(window.location.search);
            const errEl = document.getElementById('error-message');
            const successEl = document.getElementById('success-message');
            if (params.get('expired') === '1') {
                errEl.textContent = 'Your session expired due to inactivity. Please log in again.';
                errEl.style.display = 'block';
            } else if (params.get('disabled') === '1') {
                errEl.textContent = 'Your account has been disabled or locked. Please contact an administrator.';
                errEl.style.display = 'block';
            }
        })();

        function backToLogin() {
            document.getElementById('login-step').classList.add('active');
            document.getElementById('mfa-step').classList.remove('active');
            document.getElementById('auth-title').textContent = 'Welcome Back';
            document.getElementById('auth-subtitle').textContent = 'Login to your account';
        }

        // === Login Form ===
        document.getElementById('login-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const { escapeHtml } = window.DailyToinks;
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('error-message');
            const successMsg = document.getElementById('success-message');
            const lockWarning = document.getElementById('lock-warning');
            const submitBtn = document.getElementById('login-btn');

            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            lockWarning.style.display = 'none';

            if (!email || !password) {
                errorMsg.textContent = 'Please fill in all fields';
                errorMsg.style.display = 'block';
                return;
            }

            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;

            const result = await window.DailyToinks.loginUser(email, password);

            if (result.success) {
                // Check if MFA is required
                if (result.mfa_required) {
                    mfaToken = result.mfa_token;
                    document.getElementById('login-step').classList.remove('active');
                    document.getElementById('mfa-step').classList.add('active');
                    document.getElementById('auth-title').textContent = 'Two-Factor Authentication';
                    document.getElementById('auth-subtitle').textContent = 'Enter your authenticator code';
                    document.getElementById('mfa-code').focus();
                    submitBtn.textContent = 'Login';
                    submitBtn.disabled = false;
                    return;
                }

                // Staff MFA setup required (first-time login for admin/manager/rider)
                if (result.mfa_setup_required) {
                    window.DailyToinks.showNotification(result.message || 'MFA setup required', 'info');
                    setTimeout(() => {
                        window.location.href = '/normss/admin/mfa-setup.php';
                    }, 600);
                    return;
                }

                successMsg.textContent = 'Login successful! Redirecting...';
                successMsg.style.display = 'block';
                window.DailyToinks.showNotification('Welcome back!', 'success');

                // Merge localStorage cart to server cart
                const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
                if (localCart.length > 0) {
                    for (const item of localCart) {
                        await fetch('/normss/api/cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ product_id: item.id, quantity: item.quantity })
                        });
                    }
                    localStorage.setItem('cart', '[]');
                }

                // Redirect based on role
                setTimeout(() => {
                    if (['admin', 'manager', 'rider'].includes(result.user?.role)) {
                        window.location.href = '/normss/admin/index.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                }, 800);
            } else {
                // Show lock warning
                if (result.locked) {
                    lockWarning.innerHTML = `<div class="lock-warning locked">🔒 ${result.message}</div>`;
                    lockWarning.style.display = 'block';
                    errorMsg.style.display = 'none';
                } else if (result.email_not_verified) {
                    lockWarning.innerHTML = `<div class="lock-warning">📧 ${result.message}</div>`;
                    lockWarning.style.display = 'block';
                } else {
                    errorMsg.textContent = result.message || 'Invalid email or password.';
                    errorMsg.style.display = 'block';
                }
                submitBtn.textContent = 'Login';
                submitBtn.disabled = false;
            }
        });

        // === MFA Form ===
        document.getElementById('mfa-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const code = document.getElementById('mfa-code').value.trim();
            const mfaError = document.getElementById('mfa-error');
            const mfaBtn = document.getElementById('mfa-btn');

            mfaError.style.display = 'none';

            if (!code || code.length !== 6) {
                mfaError.textContent = 'Please enter a 6-digit code';
                mfaError.style.display = 'block';
                return;
            }

            mfaBtn.textContent = 'Verifying...';
            mfaBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'verify-mfa');
            formData.append('code', code);
            formData.append('mfa_token', mfaToken);
            formData.append('csrf_token', window.DailyToinks.getCsrfToken());

            try {
                const res = await fetch('/normss/api/auth.php', { method: 'POST', body: formData, headers: {'X-CSRF-Token': window.DailyToinks.getCsrfToken()} });
                const result = await res.json();

                if (result.success) {
                    window.DailyToinks.showNotification('Welcome back!', 'success');

                    // Merge cart
                    const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
                    if (localCart.length > 0) {
                        for (const item of localCart) {
                            await fetch('/normss/api/cart.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DailyToinks.getCsrfToken() },
                                body: JSON.stringify({ product_id: item.id, quantity: item.quantity })
                            });
                        }
                        localStorage.setItem('cart', '[]');
                    }

                    setTimeout(() => {
                        if (['admin', 'manager', 'rider'].includes(result.user?.role)) {
                            window.location.href = '/normss/admin/index.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    }, 800);
                } else {
                    mfaError.textContent = result.message;
                    mfaError.style.display = 'block';
                    document.getElementById('mfa-code').value = '';
                    document.getElementById('mfa-code').focus();
                    mfaBtn.textContent = 'Verify Code';
                    mfaBtn.disabled = false;
                }
            } catch (err) {
                mfaError.textContent = 'Network error. Please try again.';
                mfaError.style.display = 'block';
                mfaBtn.textContent = 'Verify Code';
                mfaBtn.disabled = false;
            }
        });
    </script>
</body>

</html>