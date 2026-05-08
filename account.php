<?php
$pageTitle = "My Account";
require_once __DIR__ . '/config/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .account-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1.5rem;
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        /* Sidebar */
        .account-sidebar {
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            height: fit-content;
            position: sticky;
            top: 1rem;
        }
        .account-user-card {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(17, 19, 25, 0.08);
        }
        .account-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0 auto 0.75rem;
        }
        .account-user-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-dark);
        }
        .account-user-email {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.2rem;
        }
        .account-nav {
            padding: 0.5rem 0;
        }
        .account-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.88rem;
            color: var(--text-mid);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            transition: all 0.15s;
        }
        .account-nav-item:hover {
            background: #f9f9f9;
            color: var(--primary-color);
        }
        .account-nav-item.active {
            background: #FFF5F5;
            color: var(--primary-color);
            font-weight: 600;
            border-right: 3px solid var(--primary-color);
        }
        /* Content Panels */
        .account-content {
            min-height: 500px;
        }
        .account-panel {
            display: none;
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .account-panel.active {
            display: block;
        }
        .panel-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(17, 19, 25, 0.08);
        }
        .panel-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        .panel-header p {
            font-size: 0.8rem;
            color: var(--text-light);
            margin: 0.25rem 0 0;
        }
        .panel-body {
            padding: 1.5rem;
        }
        /* Form Styles */
        .profile-form-group {
            margin-bottom: 1.25rem;
        }
        .profile-form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 0.4rem;
        }
        .profile-form-group input,
        .profile-form-group textarea {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .profile-form-group input:focus,
        .profile-form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(204, 0, 0, 0.1);
        }
        .profile-form-group input:read-only {
            background: #f9f9f9;
            color: #999;
        }
        .profile-form-group .hint {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        .profile-save-btn {
            padding: 0.65rem 2rem;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .profile-save-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
        .profile-save-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .profile-msg {
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: none;
        }
        .profile-msg.success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        .profile-msg.error {
            background: #FFEBEE;
            color: #B71C1C;
            border: 1px solid #FFCDD2;
        }
        .profile-msg.show { display: block; }
        /* Security badges */
        .security-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .security-row:last-child { border-bottom: none; }
        .security-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
        }
        .security-desc {
            font-size: 0.78rem;
            color: #999;
            margin-top: 0.15rem;
        }
        .security-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .security-badge.on { background: #E8F5E9; color: #2E7D32; }
        .security-badge.off { background: #FFF3E0; color: #E65100; }
        /* MFA Setup */
        .mfa-setup-box {
            margin-top: 1rem;
            padding: 1.5rem;
            background: #FAFAFA;
            border-radius: 10px;
            border: 1px solid #e8e8e8;
        }
        .mfa-setup-box h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            color: #333;
        }
        .mfa-setup-box p {
            font-size: 0.82rem;
            color: #666;
            line-height: 1.5;
            margin: 0 0 1rem;
        }
        .mfa-steps {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .mfa-step {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        .mfa-step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .mfa-step-content {
            flex: 1;
        }
        .mfa-step-content strong {
            display: block;
            font-size: 0.88rem;
            margin-bottom: 0.25rem;
        }
        .mfa-step-content span {
            font-size: 0.8rem;
            color: #777;
        }
        .mfa-qr-container {
            text-align: center;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin: 0.5rem 0;
        }
        .mfa-qr-container img {
            width: 200px;
            height: 200px;
        }
        .mfa-secret-key {
            background: #f0f0f0;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            letter-spacing: 2px;
            word-break: break-all;
            margin: 0.5rem 0;
            text-align: center;
            color: #333;
            user-select: all;
        }
        .mfa-verify-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-top: 0.75rem;
        }
        .mfa-verify-row input {
            width: 160px;
            padding: 0.6rem 0.9rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.1rem;
            font-family: monospace;
            letter-spacing: 4px;
            text-align: center;
        }
        .mfa-verify-row input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .mfa-btn {
            padding: 0.55rem 1.5rem;
            border: none;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        .mfa-btn-enable {
            background: var(--primary-color);
            color: #fff;
        }
        .mfa-btn-enable:hover { filter: brightness(1.1); }
        .mfa-btn-disable {
            background: #fff;
            color: #B71C1C;
            border: 1px solid #FFCDD2;
        }
        .mfa-btn-disable:hover { background: #FFEBEE; }
        .mfa-btn-setup {
            background: var(--primary-color);
            color: #fff;
            padding: 0.5rem 1.25rem;
        }
        .mfa-btn-setup:hover { filter: brightness(1.1); }
        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            padding: 1.25rem;
            border-radius: 10px;
            background: #f9f9f9;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        .stat-label {
            font-size: 0.78rem;
            color: #999;
            margin-top: 0.25rem;
        }
        @media (max-width: 768px) {
            .account-layout {
                grid-template-columns: 1fr;
            }
            .account-sidebar {
                position: static;
            }
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <div class="account-layout">
        <!-- Sidebar -->
        <div class="account-sidebar">
            <div class="account-user-card">
                <div class="account-avatar" id="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div class="account-user-name" id="display-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="account-user-email"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <nav class="account-nav">
                <button class="account-nav-item active" onclick="showPanel('personal')" data-panel="personal">
                    Personal Info
                </button>
                <button class="account-nav-item" onclick="showPanel('address')" data-panel="address">
                    Address
                </button>
                <button class="account-nav-item" onclick="showPanel('password')" data-panel="password">
                    Change Password
                </button>
                <button class="account-nav-item" onclick="showPanel('security')" data-panel="security">
                    Security
                </button>
            </nav>
        </div>

        <!-- Content -->
        <div class="account-content">

            <!-- Personal Info Panel -->
            <div class="account-panel active" id="panel-personal">
                <div class="panel-header">
                    <h2>Personal Information</h2>
                    <p>Manage your name, email, and phone number</p>
                </div>
                <div class="panel-body">
                    <div id="stats-section" class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value" id="stat-orders">0</div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stat-spent">₱0</div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>

                    <div class="profile-msg" id="info-msg"></div>
                    <div class="profile-form-group">
                        <label>Full Name</label>
                        <input type="text" id="info-name" placeholder="Your full name">
                    </div>
                    <div class="profile-form-group">
                        <label>Email Address</label>
                        <input type="email" id="info-email" readonly>
                        <div class="hint">Email cannot be changed for security reasons</div>
                    </div>
                    <div class="profile-form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="info-phone" placeholder="e.g. 09171234567" maxlength="11">
                        <div class="hint">Must be exactly 11 digits</div>
                    </div>
                    <button class="profile-save-btn" onclick="savePersonalInfo()">Save Changes</button>
                </div>
            </div>

            <!-- Address Panel -->
            <div class="account-panel" id="panel-address">
                <div class="panel-header">
                    <h2>Shipping Address</h2>
                    <p>Set your default delivery address</p>
                </div>
                <div class="panel-body">
                    <div class="profile-msg" id="address-msg"></div>
                    <div class="profile-form-group">
                        <label>Street Address / Building / Unit</label>
                        <textarea id="addr-address" rows="3" placeholder="e.g. Blk 5 Lot 10, Sunrise Subdivision, Brgy. San Antonio"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="profile-form-group">
                            <label>City / Municipality</label>
                            <select id="addr-city" style="width:100%;padding:0.65rem 0.9rem;border:1px solid #ddd;border-radius:8px;font-size:0.9rem;font-family:inherit;">
                                <option value="">Select city</option>
                            </select>
                        </div>
                        <div class="profile-form-group">
                            <label>ZIP Code</label>
                            <input type="text" id="addr-zip" readonly placeholder="Auto-filled">
                        </div>
                    </div>
                    <button class="profile-save-btn" onclick="saveAddress()">Save Address</button>
                </div>
            </div>

            <!-- Change Password Panel -->
            <div class="account-panel" id="panel-password">
                <div class="panel-header">
                    <h2>Change Password</h2>
                    <p>Update your password to keep your account secure</p>
                </div>
                <div class="panel-body">
                    <div class="profile-msg" id="pwd-msg"></div>
                    <div class="profile-form-group">
                        <label>Current Password</label>
                        <input type="password" id="pwd-current" placeholder="Enter your current password">
                    </div>
                    <div class="profile-form-group">
                        <label>New Password</label>
                        <input type="password" id="pwd-new" placeholder="Enter new password (min 12 characters)">
                        <div class="hint">Must have uppercase, lowercase, number, and special character</div>
                    </div>
                    <div class="profile-form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="pwd-confirm" placeholder="Re-enter new password">
                    </div>
                    <button class="profile-save-btn" onclick="changePassword()">Update Password</button>
                </div>
            </div>

            <!-- Security Panel -->
            <div class="account-panel" id="panel-security">
                <div class="panel-header">
                    <h2>Security Settings</h2>
                    <p>Manage your account security and two-factor authentication</p>
                </div>
                <div class="panel-body">
                    <div class="profile-msg" id="sec-msg"></div>

                    <div class="security-row">
                        <div>
                            <div class="security-label">Email Verification</div>
                            <div class="security-desc">Your email has been verified for account recovery</div>
                        </div>
                        <span class="security-badge" id="sec-email-badge">Loading...</span>
                    </div>

                    <div class="security-row">
                        <div>
                            <div class="security-label">Two-Factor Authentication (MFA)</div>
                            <div class="security-desc">Add an extra layer of security with Google Authenticator</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span class="security-badge" id="sec-mfa-badge">Loading...</span>
                            <button class="mfa-btn mfa-btn-setup" id="mfa-toggle-btn" onclick="toggleMFA()" style="display:none;">Setup</button>
                        </div>
                    </div>

                    <!-- MFA Setup Area (hidden by default) -->
                    <div id="mfa-setup-area" style="display:none;">
                        <div class="mfa-setup-box">
                            <h3>🔐 Set Up Two-Factor Authentication</h3>
                            <p>Follow these steps to secure your account with Google Authenticator:</p>
                            <div class="mfa-steps">
                                <div class="mfa-step">
                                    <div class="mfa-step-num">1</div>
                                    <div class="mfa-step-content">
                                        <strong>Download Google Authenticator</strong>
                                        <span>Get it from the App Store or Google Play Store</span>
                                    </div>
                                </div>
                                <div class="mfa-step">
                                    <div class="mfa-step-num">2</div>
                                    <div class="mfa-step-content">
                                        <strong>Scan this QR Code</strong>
                                        <span>Open the app and tap "+" to scan the code below</span>
                                        <div class="mfa-qr-container" id="mfa-qr-container">
                                            <div style="color:#999;padding:2rem;">Generating QR code...</div>
                                        </div>
                                        <div style="font-size:0.78rem;color:#999;margin-top:0.25rem;">Or enter this key manually:</div>
                                        <div class="mfa-secret-key" id="mfa-secret-display">Loading...</div>
                                    </div>
                                </div>
                                <div class="mfa-step">
                                    <div class="mfa-step-num">3</div>
                                    <div class="mfa-step-content">
                                        <strong>Enter the 6-digit code</strong>
                                        <span>Type the code shown in your authenticator app to verify</span>
                                        <div class="mfa-verify-row">
                                            <input type="text" id="mfa-code" maxlength="6" placeholder="000000" autocomplete="off">
                                            <button class="mfa-btn mfa-btn-enable" onclick="enableMFA()">Verify & Enable</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MFA Disable Area (hidden by default) -->
                    <div id="mfa-disable-area" style="display:none;">
                        <div class="mfa-setup-box">
                            <h3>⚠️ Disable Two-Factor Authentication</h3>
                            <p>Enter the 6-digit code from your authenticator app to confirm disabling MFA:</p>
                            <div class="mfa-verify-row">
                                <input type="text" id="mfa-disable-code" maxlength="6" placeholder="000000" autocomplete="off">
                                <button class="mfa-btn mfa-btn-disable" onclick="disableMFA()">Disable MFA</button>
                            </div>
                        </div>
                    </div>

                    <div class="security-row">
                        <div>
                            <div class="security-label">Password Last Changed</div>
                            <div class="security-desc" id="sec-pwd-date">Loading...</div>
                        </div>
                    </div>
                    <div class="security-row">
                        <div>
                            <div class="security-label">Account Created</div>
                            <div class="security-desc" id="sec-created">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        let profileData = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Populate city dropdown from app.js PHILIPPINE_CITIES
        (function initCityDropdown() {
            const cities = window.DailyToinks?.PHILIPPINE_CITIES || [];
            const sel = document.getElementById('addr-city');
            cities.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                opt.dataset.postal = c.postal;
                opt.textContent = c.name;
                sel.appendChild(opt);
            });
            sel.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                document.getElementById('addr-zip').value = selected.dataset.postal || '';
            });
        })();

        // Panel navigation
        function showPanel(panel) {
            document.querySelectorAll('.account-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.account-nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById('panel-' + panel).classList.add('active');
            document.querySelector(`[data-panel="${panel}"]`).classList.add('active');
        }

        function showMsg(id, msg, type) {
            const el = document.getElementById(id);
            el.textContent = msg;
            el.className = 'profile-msg ' + type + ' show';
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        // Load profile
        async function loadProfile() {
            try {
                const res = await fetch('/normss/api/profile.php');
                const data = await res.json();
                if (!data.success) return;

                profileData = data.user;

                // Personal info
                document.getElementById('info-name').value = profileData.name || '';
                document.getElementById('info-email').value = profileData.email || '';
                document.getElementById('info-phone').value = profileData.phone || '';

                // Stats
                document.getElementById('stat-orders').textContent = profileData.order_count || '0';
                document.getElementById('stat-spent').textContent = '₱' + Number(profileData.total_spent || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

                // Address
                document.getElementById('addr-address').value = profileData.address || '';
                if (profileData.city) {
                    document.getElementById('addr-city').value = profileData.city;
                    // Also set postal from dropdown data
                    const cityOpt = document.querySelector(`#addr-city option[value="${profileData.city}"]`);
                    if (cityOpt && cityOpt.dataset.postal) {
                        document.getElementById('addr-zip').value = cityOpt.dataset.postal;
                    } else {
                        document.getElementById('addr-zip').value = profileData.zip_code || '';
                    }
                } else {
                    document.getElementById('addr-zip').value = profileData.zip_code || '';
                }

                // Security
                const emailBadge = document.getElementById('sec-email-badge');
                emailBadge.textContent = profileData.email_verified == 1 ? '✓ Verified' : '⚠ Not Verified';
                emailBadge.className = 'security-badge ' + (profileData.email_verified == 1 ? 'on' : 'off');

                const mfaBadge = document.getElementById('sec-mfa-badge');
                const mfaToggleBtn = document.getElementById('mfa-toggle-btn');
                const isMfaEnabled = profileData.mfa_enabled == 1;
                mfaBadge.textContent = isMfaEnabled ? '✓ Enabled' : 'Not Enabled';
                mfaBadge.className = 'security-badge ' + (isMfaEnabled ? 'on' : 'off');
                mfaToggleBtn.textContent = isMfaEnabled ? 'Disable' : 'Setup MFA';
                mfaToggleBtn.className = 'mfa-btn ' + (isMfaEnabled ? 'mfa-btn-disable' : 'mfa-btn-setup');
                mfaToggleBtn.style.display = 'inline-block';

                // Hide both areas initially
                document.getElementById('mfa-setup-area').style.display = 'none';
                document.getElementById('mfa-disable-area').style.display = 'none';

                document.getElementById('sec-created').textContent = new Date(profileData.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('sec-pwd-date').textContent = 'Keep your password updated regularly';

            } catch (err) {
                console.error('Failed to load profile:', err);
            }
        }

        // Save personal info
        async function savePersonalInfo() {
            const name = document.getElementById('info-name').value.trim();
            const phone = document.getElementById('info-phone').value.trim();

            if (!name) return showMsg('info-msg', 'Name is required', 'error');

            if (phone) {
                const digits = phone.replace(/\D/g, '');
                if (digits.length !== 11) return showMsg('info-msg', 'Phone number must be exactly 11 digits', 'error');
            }

            try {
                const res = await fetch('/normss/api/profile.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ action: 'update-info', name, phone })
                });
                const data = await res.json();
                if (data.success) {
                    showMsg('info-msg', data.message, 'success');
                    document.getElementById('display-name').textContent = name;
                    document.getElementById('user-avatar').textContent = name.charAt(0).toUpperCase();
                } else {
                    showMsg('info-msg', data.message, 'error');
                }
            } catch (err) { showMsg('info-msg', 'Network error', 'error'); }
        }

        // Save address
        async function saveAddress() {
            const address = document.getElementById('addr-address').value.trim();
            const city = document.getElementById('addr-city').value;
            const zip_code = document.getElementById('addr-zip').value.trim();

            if (!address || !city) {
                return showMsg('address-msg', 'Please fill in your address and city', 'error');
            }

            try {
                const res = await fetch('/normss/api/profile.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ action: 'update-address', address, city, province: '', zip_code })
                });
                const data = await res.json();
                showMsg('address-msg', data.message, data.success ? 'success' : 'error');
            } catch (err) { showMsg('address-msg', 'Network error', 'error'); }
        }

        // Change password
        async function changePassword() {
            const current = document.getElementById('pwd-current').value;
            const newPwd = document.getElementById('pwd-new').value;
            const confirm = document.getElementById('pwd-confirm').value;

            if (!current || !newPwd || !confirm) return showMsg('pwd-msg', 'All fields are required', 'error');
            if (newPwd !== confirm) return showMsg('pwd-msg', 'New passwords do not match', 'error');
            if (newPwd.length < 12) return showMsg('pwd-msg', 'Password must be at least 12 characters', 'error');

            try {
                const res = await fetch('/normss/api/profile.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        action: 'change-password',
                        current_password: current,
                        new_password: newPwd,
                        confirm_password: confirm
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showMsg('pwd-msg', data.message, 'success');
                    document.getElementById('pwd-current').value = '';
                    document.getElementById('pwd-new').value = '';
                    document.getElementById('pwd-confirm').value = '';
                } else {
                    showMsg('pwd-msg', data.message, 'error');
                }
            } catch (err) { showMsg('pwd-msg', 'Network error', 'error'); }
        }

        // MFA toggle
        async function toggleMFA() {
            const isMfaEnabled = profileData && profileData.mfa_enabled == 1;
            if (isMfaEnabled) {
                // Show disable area
                document.getElementById('mfa-disable-area').style.display = 'block';
                document.getElementById('mfa-setup-area').style.display = 'none';
                document.getElementById('mfa-disable-code').value = '';
                document.getElementById('mfa-disable-code').focus();
            } else {
                // Start MFA setup
                document.getElementById('mfa-setup-area').style.display = 'block';
                document.getElementById('mfa-disable-area').style.display = 'none';
                await setupMFA();
            }
        }

        async function setupMFA() {
            try {
                const formData = new FormData();
                formData.append('action', 'setup-mfa');
                const res = await fetch('/normss/api/auth.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
                const data = await res.json();
                if (data.success) {
                    // Show QR code
                    document.getElementById('mfa-qr-container').innerHTML = `<img src="${data.qr_url}" alt="Scan this QR code" />`;
                    document.getElementById('mfa-secret-display').textContent = data.secret;
                    document.getElementById('mfa-code').value = '';
                    document.getElementById('mfa-code').focus();
                } else {
                    showMsg('sec-msg', data.message, 'error');
                }
            } catch (err) { showMsg('sec-msg', 'Failed to set up MFA. Please try again.', 'error'); }
        }

        async function enableMFA() {
            const code = document.getElementById('mfa-code').value.trim();
            if (!code || code.length !== 6) {
                showMsg('sec-msg', 'Please enter the 6-digit code from your authenticator app', 'error');
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'enable-mfa');
                formData.append('code', code);
                const res = await fetch('/normss/api/auth.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
                const data = await res.json();
                if (data.success) {
                    showMsg('sec-msg', '✓ MFA has been enabled! You will need your authenticator for future logins.', 'success');
                    document.getElementById('mfa-setup-area').style.display = 'none';
                    profileData.mfa_enabled = 1;
                    loadProfile();
                } else {
                    showMsg('sec-msg', data.message, 'error');
                }
            } catch (err) { showMsg('sec-msg', 'Network error', 'error'); }
        }

        async function disableMFA() {
            const code = document.getElementById('mfa-disable-code').value.trim();
            if (!code || code.length !== 6) {
                showMsg('sec-msg', 'Please enter the 6-digit code from your authenticator app', 'error');
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'disable-mfa');
                formData.append('code', code);
                const res = await fetch('/normss/api/auth.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
                const data = await res.json();
                if (data.success) {
                    showMsg('sec-msg', '✓ MFA has been disabled.', 'success');
                    document.getElementById('mfa-disable-area').style.display = 'none';
                    profileData.mfa_enabled = 0;
                    loadProfile();
                } else {
                    showMsg('sec-msg', data.message, 'error');
                }
            } catch (err) { showMsg('sec-msg', 'Network error', 'error'); }
        }

        loadProfile();
    </script>
</body>

</html>
