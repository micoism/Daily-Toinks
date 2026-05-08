<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$pageTitle = "Register";
require_once __DIR__ . '/config/security.php';
$siteKey = RECAPTCHA_SITE_KEY;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* Password Strength Indicator */
        .pwd-requirements {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .pwd-requirements .pwd-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #555;
            margin-bottom: 0.4rem;
        }
        .pwd-req-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            padding: 0.15rem 0;
            color: #999;
            transition: color 0.2s;
        }
        .pwd-req-item.met {
            color: #2E7D32;
        }
        .pwd-req-item .icon {
            font-size: 0.85rem;
            width: 18px;
            text-align: center;
        }
        .pwd-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .pwd-strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
        .pwd-strength-text {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.25rem;
            text-align: right;
        }
        /* reCAPTCHA styling */
        .captcha-container {
            display: flex;
            justify-content: center;
            margin: 0.5rem 0;
        }
        .activation-link-box {
            margin-top: 1rem;
            padding: 1rem;
            background: #E8F5E9;
            border: 1px solid #C8E6C9;
            border-radius: 8px;
            word-break: break-all;
        }
        .activation-link-box a {
            color: #1565C0;
            font-weight: 600;
            text-decoration: underline;
        }
        .activation-link-box .label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.4rem;
        }
    </style>
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <span class="logo-secure">Daily</span><span class="logo-store">Toinks</span>
            </a>
            <h1>Create Account</h1>
            <p style="color: var(--text-light);">Join DailyToinks today</p>
        </div>

        <form id="register-form">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" id="fullname" class="form-input" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" id="email" class="form-input" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" id="phone" class="form-input" required placeholder="e.g. 09171234567" minlength="11" maxlength="11" pattern="[0-9]{11}">
                <small style="color:var(--text-light);font-size:0.75rem;">Must be exactly 11 digits (e.g. 09171234567)</small>
            </div>

            <div class="form-group">
                <label class="form-label">Password *</label>
                <input type="password" id="password" class="form-input" required placeholder="Create a strong password" autocomplete="new-password">
                
                <!-- Password Strength Indicator -->
                <div class="pwd-requirements" id="pwd-requirements">
                    <div class="pwd-title">Password Requirements:</div>
                    <div class="pwd-req-item" id="req-length">
                        <span class="icon">○</span>
                        <span>At least 12 characters</span>
                    </div>
                    <div class="pwd-req-item" id="req-upper">
                        <span class="icon">○</span>
                        <span>At least one uppercase letter (A-Z)</span>
                    </div>
                    <div class="pwd-req-item" id="req-lower">
                        <span class="icon">○</span>
                        <span>At least one lowercase letter (a-z)</span>
                    </div>
                    <div class="pwd-req-item" id="req-number">
                        <span class="icon">○</span>
                        <span>At least one number (0-9)</span>
                    </div>
                    <div class="pwd-req-item" id="req-special">
                        <span class="icon">○</span>
                        <span>At least one special character (!@#$...)</span>
                    </div>
                    <div class="pwd-strength-bar">
                        <div class="pwd-strength-fill" id="pwd-strength-fill"></div>
                    </div>
                    <div class="pwd-strength-text" id="pwd-strength-text"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password *</label>
                <input type="password" id="confirm-password" class="form-input" required placeholder="Confirm your password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="terms" required>
                    <span>I agree to the <a href="#" style="color: var(--primary-color);">Terms & Conditions</a></span>
                </label>
            </div>

            <!-- reCAPTCHA v2 Checkbox -->
            <div style="display:flex;justify-content:center;margin:0.5rem 0;">
                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
            </div>

            <div id="error-message" class="error-message" style="display: none;"></div>
            <div id="success-message" class="success-message" style="display: none;"></div>
            <div id="activation-link-container" style="display: none;"></div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                Register
            </button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        // === Password Strength Checker ===
        const passwordInput = document.getElementById('password');
        const requirements = {
            length: { el: document.getElementById('req-length'), test: (p) => p.length >= 12 },
            upper:  { el: document.getElementById('req-upper'),  test: (p) => /[A-Z]/.test(p) },
            lower:  { el: document.getElementById('req-lower'),  test: (p) => /[a-z]/.test(p) },
            number: { el: document.getElementById('req-number'), test: (p) => /[0-9]/.test(p) },
            special:{ el: document.getElementById('req-special'),test: (p) => /[^a-zA-Z0-9]/.test(p) }
        };

        passwordInput.addEventListener('input', function() {
            const pwd = this.value;
            let metCount = 0;
            const total = Object.keys(requirements).length;

            for (const [key, req] of Object.entries(requirements)) {
                const met = req.test(pwd);
                req.el.classList.toggle('met', met);
                req.el.querySelector('.icon').textContent = met ? '✓' : '○';
                if (met) metCount++;
            }

            // Strength bar
            const fill = document.getElementById('pwd-strength-fill');
            const text = document.getElementById('pwd-strength-text');
            const pct = (metCount / total) * 100;
            fill.style.width = pct + '%';

            if (metCount === 0) {
                fill.style.background = '#e0e0e0';
                text.textContent = '';
                text.style.color = '#999';
            } else if (metCount <= 2) {
                fill.style.background = '#F44336';
                text.textContent = 'Weak';
                text.style.color = '#F44336';
            } else if (metCount <= 3) {
                fill.style.background = '#FF9800';
                text.textContent = 'Fair';
                text.style.color = '#FF9800';
            } else if (metCount <= 4) {
                fill.style.background = '#FFC107';
                text.textContent = 'Good';
                text.style.color = '#F9A825';
            } else {
                fill.style.background = '#4CAF50';
                text.textContent = 'Strong';
                text.style.color = '#2E7D32';
            }
        });

        // === Form Submit ===
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;
            const captchaResponse = grecaptcha.getResponse();
            const errorMsg = document.getElementById('error-message');
            const successMsg = document.getElementById('success-message');
            const activationContainer = document.getElementById('activation-link-container');
            const submitBtn = e.target.querySelector('button[type="submit"]');

            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            activationContainer.style.display = 'none';

            if (!fullname || !email || !phone || !password || !confirmPassword) {
                errorMsg.textContent = 'Please fill in all required fields';
                errorMsg.style.display = 'block';
                return;
            }

            // Phone validation - exactly 11 digits
            const phoneDigits = phone.replace(/\D/g, '');
            if (phoneDigits.length !== 11) {
                errorMsg.textContent = 'Phone number must be exactly 11 digits';
                errorMsg.style.display = 'block';
                return;
            }

            // Client-side password validation
            let pwdErrors = [];
            if (password.length < 12) pwdErrors.push('At least 12 characters');
            if (!/[A-Z]/.test(password)) pwdErrors.push('At least one uppercase letter');
            if (!/[a-z]/.test(password)) pwdErrors.push('At least one lowercase letter');
            if (!/[0-9]/.test(password)) pwdErrors.push('At least one number');
            if (!/[^a-zA-Z0-9]/.test(password)) pwdErrors.push('At least one special character');
            if (pwdErrors.length > 0) {
                errorMsg.textContent = 'Password must have: ' + pwdErrors.join(', ');
                errorMsg.style.display = 'block';
                return;
            }

            if (password !== confirmPassword) {
                errorMsg.textContent = 'Passwords do not match';
                errorMsg.style.display = 'block';
                return;
            }

            if (!terms) {
                errorMsg.textContent = 'Please accept the Terms & Conditions';
                errorMsg.style.display = 'block';
                return;
            }

            if (!captchaResponse) {
                errorMsg.textContent = 'Please complete the CAPTCHA verification';
                errorMsg.style.display = 'block';
                return;
            }

            submitBtn.textContent = 'Creating account...';
            submitBtn.disabled = true;

            // Send form data with captcha
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('name', fullname);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('password', password);
            formData.append('g-recaptcha-response', captchaResponse);

            try {
                formData.append('csrf_token', window.DailyToinks.getCsrfToken());
                const res = await fetch('/normss/api/auth.php', { method: 'POST', body: formData, headers: {'X-CSRF-Token': window.DailyToinks.getCsrfToken()} });
                const result = await res.json();

                if (result.success) {
                    successMsg.textContent = result.message;
                    successMsg.style.display = 'block';

                    // Show email confirmation message
                    activationContainer.innerHTML = `
                        <div class="activation-link-box">
                            <div class="label">📧 An activation link has been sent to your email address. Please check your inbox (and spam folder) and click the link to activate your account.</div>
                        </div>
                    `;
                    activationContainer.style.display = 'block';

                    window.DailyToinks.showNotification('Account created! Check your email for the activation link.', 'success');
                    
                    // Redirect to login after 4 seconds
                    setTimeout(() => { window.location.href = 'login.php'; }, 4000);
                } else {
                    errorMsg.textContent = result.message;
                    errorMsg.style.display = 'block';
                    grecaptcha.reset();
                    submitBtn.textContent = 'Register';
                    submitBtn.disabled = false;
                }
            } catch (err) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
                grecaptcha.reset();
                submitBtn.textContent = 'Register';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>

</html>