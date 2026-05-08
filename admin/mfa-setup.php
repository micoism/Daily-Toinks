<?php
// ===================================
// MANDATORY MFA SETUP FOR STAFF (admin/manager/rider)
// Reached after first-time staff login when mfa_enabled = 0
// ===================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

session_start();

// Require an active "pending MFA setup" session
if (empty($_SESSION['mfa_setup_user_id']) || empty($_SESSION['mfa_setup_token'])) {
    header('Location: /normss/login.php');
    exit;
}

// Expire after 10 minutes
if (time() - ($_SESSION['mfa_setup_timestamp'] ?? 0) > 600) {
    unset($_SESSION['mfa_setup_user_id'], $_SESSION['mfa_setup_token'], $_SESSION['mfa_setup_timestamp']);
    header('Location: /normss/login.php?expired=1');
    exit;
}

$mfaToken = $_SESSION['mfa_setup_token'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Setup Required - DailyToinks</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .mfa-card {
            background: #fff;
            max-width: 540px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }
        .mfa-card h1 {
            font-size: 1.5rem;
            margin: 0 0 0.5rem;
            color: #C1001A;
            text-align: center;
        }
        .mfa-card .subtitle {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .mfa-banner {
            background: #FFF3E0;
            border-left: 4px solid #E65100;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #5D2C00;
            margin-bottom: 1.5rem;
        }
        .step {
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #eee;
        }
        .step:last-child { border-bottom: none; }
        .step-num {
            display: inline-flex;
            width: 26px;
            height: 26px;
            background: #C1001A;
            color: #fff;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }
        .step strong { font-size: 0.95rem; }
        .step p { font-size: 0.85rem; color: #666; margin: 0.4rem 0 0 2.2rem; }
        .qr-box {
            text-align: center;
            padding: 1rem;
            background: #fafafa;
            border-radius: 10px;
            margin: 0.75rem 0 0.5rem 2.2rem;
        }
        .qr-box img { width: 200px; height: 200px; }
        .secret-key {
            background: #f0f0f0;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            text-align: center;
            margin-left: 2.2rem;
            word-break: break-all;
            user-select: all;
        }
        .verify-row {
            margin-left: 2.2rem;
            margin-top: 0.75rem;
            display: flex;
            gap: 0.6rem;
        }
        .verify-row input {
            flex: 1;
            padding: 0.7rem 0.9rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.2rem;
            font-family: monospace;
            letter-spacing: 4px;
            text-align: center;
        }
        .verify-row input:focus {
            outline: none;
            border-color: #C1001A;
        }
        .btn-primary {
            background: #C1001A;
            color: #fff;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .err-msg {
            color: #B71C1C;
            background: #FFEBEE;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            margin: 0.75rem 0 0 2.2rem;
            display: none;
        }
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #999;
            font-size: 0.8rem;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="mfa-card">
        <h1>🔐 Setup Required</h1>
        <p class="subtitle">Two-Factor Authentication is mandatory for staff accounts</p>

        <div class="mfa-banner">
            <strong>Security Requirement:</strong> Admin, Manager, and Rider accounts must enable Google Authenticator
            before accessing the system. This is a one-time setup.
        </div>

        <div class="step">
            <strong><span class="step-num">1</span>Install Google Authenticator</strong>
            <p>Download from the App Store or Google Play Store on your phone.</p>
        </div>

        <div class="step">
            <strong><span class="step-num">2</span>Scan the QR Code</strong>
            <div class="qr-box" id="qr-box">
                <div style="color:#999;padding:2rem;">Generating QR code...</div>
            </div>
            <p style="margin-top:0.5rem;">Or enter this key manually if you can't scan:</p>
            <div class="secret-key" id="secret-display">Loading...</div>
        </div>

        <div class="step">
            <strong><span class="step-num">3</span>Enter the 6-digit code</strong>
            <p>Open Google Authenticator and type the current code below to confirm setup:</p>
            <div class="verify-row">
                <input type="text" id="mfa-code" maxlength="6" placeholder="000000" autocomplete="off"
                    inputmode="numeric">
                <button class="btn-primary" id="enable-btn" onclick="enableMFA()">Verify</button>
            </div>
            <div class="err-msg" id="err-msg"></div>
        </div>

        <a href="/normss/api/auth.php?action=logout" class="logout-link">← Cancel and logout</a>
    </div>

    <script src="/normss/js/app.js"></script>
    <script>
        const mfaToken = <?php echo json_encode($mfaToken); ?>;

        // Load setup data on page load
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function loadSetup() {
            const fd = new FormData();
            fd.append('action', 'pending-mfa-setup');
            fd.append('csrf_token', csrfToken);
            const qrBox = document.getElementById('qr-box');
            const secretDisplay = document.getElementById('secret-display');
            try {
                const res = await fetch('/normss/api/auth.php', {
                    method: 'POST', body: fd,
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    qrBox.innerHTML = '<div style="color:#B71C1C;padding:1rem;font-size:0.8rem;">Server error (HTTP ' + res.status + '): ' + text.substring(0, 200).replace(/</g,'&lt;') + '</div>';
                    secretDisplay.textContent = 'Error';
                    return;
                }
                if (!data.success) {
                    qrBox.innerHTML = '<div style="color:#B71C1C;padding:1rem;font-size:0.85rem;">' + (data.message || 'Failed to load setup') + '</div>';
                    secretDisplay.textContent = 'Failed';
                    return;
                }
                qrBox.innerHTML = '<img src="' + data.qr_url + '" alt="QR Code">';
                secretDisplay.textContent = data.secret;
            } catch (e) {
                qrBox.innerHTML = '<div style="color:#B71C1C;padding:1rem;font-size:0.85rem;">Network error: ' + e.message + '</div>';
                secretDisplay.textContent = 'Error';
            }
        }

        async function enableMFA() {
            const code = document.getElementById('mfa-code').value.trim();
            const errBox = document.getElementById('err-msg');
            const btn = document.getElementById('enable-btn');
            errBox.style.display = 'none';

            if (!code || code.length !== 6) {
                errBox.textContent = 'Please enter a 6-digit code.';
                errBox.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            const fd = new FormData();
            fd.append('action', 'pending-mfa-enable');
            fd.append('code', code);
            fd.append('mfa_token', mfaToken);
            fd.append('csrf_token', csrfToken);

            try {
                const res = await fetch('/normss/api/auth.php', {
                    method: 'POST', body: fd,
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                const data = await res.json();
                if (data.success) {
                    setTimeout(() => { window.location.href = '/normss/admin/index.php'; }, 600);
                } else {
                    errBox.textContent = data.message || 'Failed to enable MFA';
                    errBox.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Verify';
                    document.getElementById('mfa-code').value = '';
                    document.getElementById('mfa-code').focus();
                }
            } catch (e) {
                errBox.textContent = 'Network error. Try again.';
                errBox.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        }

        loadSetup();

        document.getElementById('mfa-code').addEventListener('keypress', e => {
            if (e.key === 'Enter') enableMFA();
        });
    </script>
</body>

</html>
