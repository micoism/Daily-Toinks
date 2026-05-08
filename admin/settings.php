<?php
$pageTitle = "Security Settings";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Settings - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .settings-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            overflow: hidden;
        }
        .settings-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e8e8e8;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .settings-card-header .card-icon {
            font-size: 1.3rem;
        }
        .settings-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
        }
        .settings-card-body {
            padding: 1.5rem;
        }
        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #333;
        }
        .setting-desc {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.15rem;
        }
        .setting-input {
            width: 80px;
            padding: 0.4rem 0.6rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            font-family: inherit;
        }
        .setting-input-wide {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            font-family: 'Courier New', monospace;
            margin-top: 0.5rem;
        }
        .setting-input-wide:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(204,0,0,0.1);
        }
        .setting-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(204,0,0,0.1);
        }
        .setting-toggle {
            position: relative;
            width: 44px;
            height: 24px;
        }
        .setting-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .setting-toggle .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #ccc;
            border-radius: 24px;
            transition: 0.3s;
        }
        .setting-toggle .slider:before {
            content: "";
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .setting-toggle input:checked + .slider {
            background: var(--primary-color);
        }
        .setting-toggle input:checked + .slider:before {
            transform: translateX(20px);
        }
        .save-btn-container {
            margin-top: 1.5rem;
            text-align: right;
        }
        .save-success {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: #2E7D32;
            font-weight: 600;
            margin-right: 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .save-success.show { opacity: 1; }
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="settings-grid">
                    
                    <!-- Login Security -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <span class="card-icon">🔒</span>
                            <h3>Login Security</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Max Login Attempts</div>
                                    <div class="setting-desc">Lock account after this many failed attempts</div>
                                </div>
                                <input type="number" class="setting-input" id="s-max_login_attempts" min="1" max="20" value="3">
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Lockout Duration (minutes)</div>
                                    <div class="setting-desc">How long the account stays locked</div>
                                </div>
                                <input type="number" class="setting-input" id="s-lockout_duration" min="1" max="1440" value="15">
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Session Timeout (minutes)</div>
                                    <div class="setting-desc">Auto-logout after inactivity</div>
                                </div>
                                <input type="number" class="setting-input" id="s-session_timeout" min="1" max="1440" value="2">
                            </div>
                        </div>
                    </div>

                    <!-- Password Policy -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <span class="card-icon">🔑</span>
                            <h3>Password Policy</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Minimum Password Length</div>
                                    <div class="setting-desc">Minimum number of characters</div>
                                </div>
                                <input type="number" class="setting-input" id="s-min_password_length" min="6" max="64" value="12">
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Require Uppercase Letter</div>
                                    <div class="setting-desc">At least one A-Z character</div>
                                </div>
                                <label class="setting-toggle">
                                    <input type="checkbox" id="s-require_uppercase" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Require Lowercase Letter</div>
                                    <div class="setting-desc">At least one a-z character</div>
                                </div>
                                <label class="setting-toggle">
                                    <input type="checkbox" id="s-require_lowercase" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Require Number</div>
                                    <div class="setting-desc">At least one 0-9 digit</div>
                                </div>
                                <label class="setting-toggle">
                                    <input type="checkbox" id="s-require_number" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Require Special Character</div>
                                    <div class="setting-desc">At least one symbol (!@#$%...)</div>
                                </div>
                                <label class="setting-toggle">
                                    <input type="checkbox" id="s-require_special_char" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-row">
                                <div>
                                    <div class="setting-label">Password Expiry (days)</div>
                                    <div class="setting-desc">Force password change (0 = never)</div>
                                </div>
                                <input type="number" class="setting-input" id="s-password_expiry_days" min="0" max="365" value="90">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PayMongo / Payment Gateway -->
                <div class="settings-grid" style="margin-top:1.5rem;">
                    <div class="settings-card" style="grid-column:1/-1;">
                        <div class="settings-card-header">
                            <span class="card-icon">💳</span>
                            <h3>Payment Gateway (PayMongo + GCash)</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="setting-row" style="flex-direction:column;align-items:stretch;">
                                <div>
                                    <div class="setting-label">PayMongo Secret Key</div>
                                    <div class="setting-desc">Starts with sk_test_ (test) or sk_live_ (production)</div>
                                </div>
                                <input type="text" class="setting-input-wide" id="s-paymongo_secret_key" placeholder="sk_test_...">
                            </div>
                            <div class="setting-row" style="flex-direction:column;align-items:stretch;">
                                <div>
                                    <div class="setting-label">PayMongo Public Key</div>
                                    <div class="setting-desc">Starts with pk_test_ (test) or pk_live_ (production)</div>
                                </div>
                                <input type="text" class="setting-input-wide" id="s-paymongo_public_key" placeholder="pk_test_...">
                            </div>
                            <div class="setting-row" style="flex-direction:column;align-items:stretch;">
                                <div>
                                    <div class="setting-label">Ngrok URL (for webhooks)</div>
                                    <div class="setting-desc">Run <code>ngrok http 80</code> and paste the HTTPS URL here. PayMongo webhooks need a public URL.</div>
                                </div>
                                <input type="text" class="setting-input-wide" id="s-ngrok_url" placeholder="https://xxxx-xxxx.ngrok-free.app">
                            </div>
                            <div style="margin-top:1rem;padding:1rem;background:#FFF8E1;border-radius:8px;font-size:0.82rem;color:#5D4037;">
                                <strong>Setup Guide:</strong><br>
                                1. Create a <a href="https://dashboard.paymongo.com" target="_blank" style="color:var(--primary-color);">PayMongo account</a> and get your test API keys<br>
                                2. Install ngrok: <code>npm install -g ngrok</code> or download from <a href="https://ngrok.com" target="_blank" style="color:var(--primary-color);">ngrok.com</a><br>
                                3. Run: <code>ngrok http 80</code><br>
                                4. Copy the HTTPS forwarding URL and paste above<br>
                                5. Register webhook in PayMongo dashboard pointing to: <code>[ngrok-url]/normss/api/payment.php?webhook=1</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="save-btn-container">
                    <span class="save-success" id="save-success">✓ Settings saved</span>
                    <button class="topbar-btn topbar-btn-primary" onclick="saveSettings()" id="save-btn">
                        💾 Save All Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load current settings
        async function loadSettings() {
            try {
                const res = await fetch('/normss/api/settings.php');
                const data = await res.json();
                if (!data.success) return;

                data.settings.forEach(s => {
                    const el = document.getElementById('s-' + s.setting_key);
                    if (!el) return;

                    if (el.type === 'checkbox') {
                        el.checked = s.setting_value === '1';
                    } else {
                        el.value = s.setting_value;
                    }
                });
            } catch (err) {
                console.error('Failed to load settings:', err);
            }
        }

        async function saveSettings() {
            const btn = document.getElementById('save-btn');
            const successMsg = document.getElementById('save-success');
            btn.textContent = 'Saving...';
            btn.disabled = true;

            const payload = {};
            const keys = [
                'max_login_attempts', 'lockout_duration', 'session_timeout',
                'min_password_length', 'require_uppercase', 'require_lowercase',
                'require_number', 'require_special_char', 'password_expiry_days',
                'paymongo_secret_key', 'paymongo_public_key', 'ngrok_url'
            ];

            keys.forEach(key => {
                const el = document.getElementById('s-' + key);
                if (el.type === 'checkbox') {
                    payload[key] = el.checked ? '1' : '0';
                } else {
                    payload[key] = el.value;
                }
            });

            try {
                const res = await fetch('/normss/api/settings.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    successMsg.classList.add('show');
                    setTimeout(() => successMsg.classList.remove('show'), 3000);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('Network error. Please try again.');
            }

            btn.innerHTML = '💾 Save All Settings';
            btn.disabled = false;
        }

        loadSettings();
    </script>
</body>

</html>
