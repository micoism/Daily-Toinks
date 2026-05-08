<?php
$currentUser = getCurrentUser();
$_topbarCsrf = getCsrfToken();
?>
<script>
// Ensure CSRF meta tag exists for all admin pages
(function() {
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const m = document.createElement('meta');
        m.name = 'csrf-token';
        m.content = <?= json_encode($_topbarCsrf) ?>;
        document.head.appendChild(m);
    }
})();
</script>
<!-- Admin Topbar -->
<div class="admin-topbar">
    <div class="topbar-title-wrap">
        <h1 class="topbar-title">
            <?= $pageTitle ?? 'Admin Panel' ?>
        </h1>
        <div class="topbar-subtitle">DailyToinks Operations Workspace</div>
    </div>
    <div class="topbar-actions">
        <div class="topbar-user-pill">
            <span class="topbar-user-name"><?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'User')[0]) ?></span>
            <span class="topbar-user-role"><?= htmlspecialchars(ucfirst($currentUser['role'] ?? 'staff')) ?></span>
        </div>
        <button type="button" class="topbar-btn topbar-btn-secondary" onclick="openMyPwdModal()">🔑 Change Password</button>
        <a href="/normss/index.php" class="topbar-btn topbar-btn-secondary" target="_blank">🌐 View Store</a>
        <a href="/normss/api/auth.php?action=logout" class="topbar-btn topbar-btn-secondary"
            onclick="event.preventDefault(); fetch('/normss/api/auth.php?action=logout').then(() => window.location.href='/normss/login.php');">Logout</a>
    </div>
</div>

<!-- === STAFF SELF-SERVICE: CHANGE MY PASSWORD === -->
<div class="modal-overlay" id="my-pwd-modal">
    <div class="modal" style="max-width:450px;">
        <div class="modal-header">
            <h3 class="modal-title">Change My Password</h3>
            <button class="modal-close" type="button" onclick="closeMyPwdModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#666;">Update the password for your account (<strong><?= htmlspecialchars($currentUser['email']) ?></strong>).</p>
            <div class="admin-form-group">
                <label class="admin-form-label">Current Password</label>
                <input type="password" id="my-pwd-current" class="admin-form-input" autocomplete="current-password">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">New Password</label>
                <input type="password" id="my-pwd-new" class="admin-form-input" autocomplete="new-password" placeholder="Min 12 chars, upper, lower, number, special">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Confirm New Password</label>
                <input type="password" id="my-pwd-confirm" class="admin-form-input" autocomplete="new-password">
            </div>
            <div id="my-pwd-msg" style="font-size:0.82rem;margin-top:0.5rem;display:none;"></div>
        </div>
        <div class="modal-footer">
            <button class="topbar-btn topbar-btn-secondary" type="button" onclick="closeMyPwdModal()">Cancel</button>
            <button class="topbar-btn topbar-btn-primary" type="button" onclick="submitMyPwdChange()">Change Password</button>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('my-pwd-modal');
    const msg = document.getElementById('my-pwd-msg');
    const inputs = ['my-pwd-current', 'my-pwd-new', 'my-pwd-confirm'];

    window.openMyPwdModal = function() {
        inputs.forEach(id => document.getElementById(id).value = '');
        msg.style.display = 'none';
        modal.classList.add('active');
    };
    window.closeMyPwdModal = function() {
        modal.classList.remove('active');
    };

    function showMsg(text, type) {
        msg.textContent = text;
        msg.style.color = type === 'error' ? '#B71C1C' : '#2E7D32';
        msg.style.display = 'block';
    }

    window.submitMyPwdChange = async function() {
        const current = document.getElementById('my-pwd-current').value;
        const newPwd = document.getElementById('my-pwd-new').value;
        const confirmPwd = document.getElementById('my-pwd-confirm').value;

        if (!current || !newPwd || !confirmPwd) return showMsg('All fields are required', 'error');
        if (newPwd !== confirmPwd) return showMsg('New passwords do not match', 'error');
        if (newPwd === current) return showMsg('New password must be different from current', 'error');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('/normss/api/profile.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({
                    action: 'change-password',
                    current_password: current,
                    new_password: newPwd,
                    confirm_password: confirmPwd
                })
            });
            const data = await res.json();
            if (data.success) {
                showMsg('Password changed successfully. You will be logged out.', 'success');
                setTimeout(() => {
                    fetch('/normss/api/auth.php?action=logout').finally(() => {
                        window.location.replace('/normss/login.php');
                    });
                }, 1500);
            } else {
                showMsg(data.message || 'Failed to change password', 'error');
            }
        } catch (e) {
            showMsg('Network error. Please try again.', 'error');
        }
    };
})();
</script>

<!-- === ADMIN SESSION AUTO-LOGOUT === -->
<script>
(function() {
    let timeoutModal = null;
    let countdownInterval = null;
    let pollInterval = null;
    let lastActivity = Date.now();
    let sessionTimeout = 2 * 60;
    let warningShown = false;

    fetch('/normss/api/auth.php?action=session-info')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.timeout_seconds) {
                sessionTimeout = data.timeout_seconds;
                startMonitoring();
            }
        })
        .catch(() => {});

    function createTimeoutModal() {
        if (document.getElementById('session-timeout-modal')) return;
        const modal = document.createElement('div');
        modal.id = 'session-timeout-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:99999;display:none;align-items:center;justify-content:center;font-family:Manrope,Inter,sans-serif;';
        modal.innerHTML = `
            <div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="font-size:3rem;margin-bottom:0.5rem;">⏰</div>
                <h3 style="margin-bottom:0.5rem;color:#333;">Session Expiring</h3>
                <p style="color:#666;font-size:0.9rem;margin-bottom:1rem;">Your session will expire in <strong id="timeout-countdown" style="color:var(--primary-color);">30</strong> seconds due to inactivity.</p>
                <button onclick="extendSession()" style="padding:0.6rem 1.5rem;background:var(--primary-color);color:#fff;border:none;border-radius:999px;font-weight:800;font-size:0.9rem;cursor:pointer;">Stay Logged In</button>
            </div>
        `;
        document.body.appendChild(modal);
        timeoutModal = modal;
    }

    function startMonitoring() {
        clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            const idleSeconds = Math.floor((Date.now() - lastActivity) / 1000);
            if (!warningShown && idleSeconds >= sessionTimeout - 30 && idleSeconds < sessionTimeout) {
                showWarning(sessionTimeout - idleSeconds);
            }
            if (idleSeconds >= sessionTimeout) {
                clearInterval(pollInterval);
                doLogout();
            }
        }, 1000);
    }

    function showWarning(initialRemaining) {
        if (warningShown) return;
        warningShown = true;
        createTimeoutModal();
        timeoutModal.style.display = 'flex';
        let remaining = Math.max(1, Math.floor(initialRemaining));
        const countdownEl = document.getElementById('timeout-countdown');
        countdownEl.textContent = remaining;
        clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            remaining--;
            if (countdownEl) countdownEl.textContent = remaining;
            if (remaining <= 0) clearInterval(countdownInterval);
        }, 1000);
    }

    function doLogout() {
        clearInterval(countdownInterval);
        clearInterval(pollInterval);
        fetch('/normss/api/auth.php?action=logout')
            .finally(() => { window.location.replace('/normss/login.php?expired=1'); });
    }

    function recordActivity() {
        lastActivity = Date.now();
        if (warningShown) {
            warningShown = false;
            if (timeoutModal) timeoutModal.style.display = 'none';
            clearInterval(countdownInterval);
        }
    }

    window.extendSession = function() {
        recordActivity();
        fetch('/normss/api/auth.php?action=session-info').catch(() => {});
    };

    ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            if (warningShown) return;
            recordActivity();
        }, { passive: true });
    });

    startMonitoring();
})();
</script>