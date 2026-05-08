<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Activate Account";
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .activation-container {
            max-width: 480px;
            margin: 0 auto;
            text-align: center;
            padding: 2rem;
        }
        .activation-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .activation-icon.success { color: #2E7D32; }
        .activation-icon.error { color: #B71C1C; }
        .activation-icon.loading { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
</head>

<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <span class="logo-secure">Daily</span><span class="logo-store">Toinks</span>
            </a>
        </div>

        <div class="activation-container" id="activation-result">
            <div class="activation-icon loading">⏳</div>
            <h2>Activating your account...</h2>
            <p style="color: var(--text-light);">Please wait while we verify your activation link.</p>
        </div>
    </div>

    <script>
        (async function() {
            const token = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';
            const container = document.getElementById('activation-result');

            if (!token) {
                container.innerHTML = `
                    <div class="activation-icon error">❌</div>
                    <h2>Invalid Link</h2>
                    <p style="color: var(--text-light); margin-bottom: 1.5rem;">No activation token provided.</p>
                    <a href="register.php" class="btn btn-primary">Register Again</a>
                `;
                return;
            }

            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                const formData = new FormData();
                formData.append('action', 'activate');
                formData.append('token', token);
                formData.append('csrf_token', csrfToken);

                const res = await fetch('/normss/api/auth.php', { method: 'POST', body: formData, headers: {'X-CSRF-Token': csrfToken} });
                const result = await res.json();

                if (result.success) {
                    container.innerHTML = `
                        <div class="activation-icon success">✅</div>
                        <h2>Account Activated!</h2>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">${result.message}</p>
                        <a href="login.php" class="btn btn-primary" style="display: inline-block;">Go to Login</a>
                    `;
                    // Auto redirect after 3 seconds
                    setTimeout(() => { window.location.href = 'login.php'; }, 3000);
                } else {
                    container.innerHTML = `
                        <div class="activation-icon error">❌</div>
                        <h2>Activation Failed</h2>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">${result.message}</p>
                        <a href="register.php" class="btn btn-primary" style="display: inline-block;">Register Again</a>
                    `;
                }
            } catch (err) {
                container.innerHTML = `
                    <div class="activation-icon error">❌</div>
                    <h2>Network Error</h2>
                    <p style="color: var(--text-light); margin-bottom: 1.5rem;">Could not verify your activation link. Please try again.</p>
                    <a href="login.php" class="btn btn-secondary" style="display: inline-block;">Go to Login</a>
                `;
            }
        })();
    </script>
</body>

</html>
