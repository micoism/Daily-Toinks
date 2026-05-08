<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$pageTitle = "Forgot Password";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <a href="/normss/login.php" class="logo" style="justify-content:center;margin-bottom:1.5rem;">
                <span class="logo-secure">Secure</span><span class="logo-store">Store</span>
            </a>
            <h1>Reset Password</h1>
            <p>We'll help you get back in.</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator" id="step-indicator">
            <div class="step-item active" id="si-1">
                <div class="step-dot">1</div>
                <div class="step-label">Email</div>
            </div>
            <div class="step-item" id="si-2">
                <div class="step-dot">2</div>
                <div class="step-label">Verify</div>
            </div>
            <div class="step-item" id="si-3">
                <div class="step-dot">3</div>
                <div class="step-label">Reset</div>
            </div>
        </div>

        <!-- Step 1: Email -->
        <div id="step1">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" id="reset-email" class="form-input" placeholder="Enter your email">
            </div>
            <div id="error1" class="error-message" style="display: none;"></div>
            <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="sendCode()">Send Reset
                Code</button>
        </div>

        <!-- Step 2: Code Verification -->
        <div id="step2" style="display: none;">
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" id="reset-code" class="form-input" placeholder="Enter 6-digit code" maxlength="6">
                <small style="color: var(--text-light);">Check your email for the code</small>
            </div>
            <div id="error2" class="error-message" style="display: none;"></div>
            <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="verifyCode()">Verify
                Code</button>
        </div>

        <!-- Step 3: New Password -->
        <div id="step3" style="display: none;">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" id="new-password" class="form-input" placeholder="Enter new password"
                    minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" id="confirm-password-reset" class="form-input"
                    placeholder="Confirm new password">
            </div>
            <div id="error3" class="error-message" style="display: none;"></div>
            <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="resetPassword()">Reset
                Password</button>
        </div>

        <div class="auth-links">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script src="/normss/js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        let resetEmail = '';
        let resetCode = '';

        async function sendCode() {
            const email = document.getElementById('reset-email').value.trim();
            const errDiv = document.getElementById('error1');
            errDiv.style.display = 'none';

            if (!email) { errDiv.textContent = 'Please enter your email'; errDiv.style.display = 'block'; return; }

            const result = await window.DailyToinks.requestPasswordReset(email);
            if (result.success) {
                resetEmail = email;
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                // Advance step indicator
                document.getElementById('si-1').classList.remove('active');
                document.getElementById('si-1').classList.add('done');
                document.getElementById('si-2').classList.add('active');
                window.DailyToinks.showNotification('Reset code sent! (Check console for demo)', 'success');
                if (result.code) console.log('Demo reset code:', result.code);
            } else {
                errDiv.textContent = result.message;
                errDiv.style.display = 'block';
            }
        }

        async function verifyCode() {
            const code = document.getElementById('reset-code').value.trim();
            const errDiv = document.getElementById('error2');
            errDiv.style.display = 'none';

            if (!code) { errDiv.textContent = 'Please enter the code'; errDiv.style.display = 'block'; return; }

            const result = await window.DailyToinks.verifyResetCode(resetEmail, code);
            if (result.success) {
                resetCode = code;
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                // Advance step indicator
                document.getElementById('si-2').classList.remove('active');
                document.getElementById('si-2').classList.add('done');
                document.getElementById('si-3').classList.add('active');
                window.DailyToinks.showNotification('Code verified!', 'success');
            } else {
                errDiv.textContent = result.message;
                errDiv.style.display = 'block';
            }
        }

        async function resetPassword() {
            const newPass = document.getElementById('new-password').value;
            const confirmPass = document.getElementById('confirm-password-reset').value;
            const errDiv = document.getElementById('error3');
            errDiv.style.display = 'none';

            if (!newPass || newPass.length < 6) { errDiv.textContent = 'Password must be at least 6 characters'; errDiv.style.display = 'block'; return; }
            if (newPass !== confirmPass) { errDiv.textContent = 'Passwords do not match'; errDiv.style.display = 'block'; return; }

            const result = await window.DailyToinks.resetUserPassword(resetEmail, resetCode, newPass);
            if (result.success) {
                window.DailyToinks.showNotification('Password reset successfully!', 'success');
                setTimeout(() => { window.location.href = 'login.php'; }, 1500);
            } else {
                errDiv.textContent = result.message;
                errDiv.style.display = 'block';
            }
        }
    </script>
</body>

</html>