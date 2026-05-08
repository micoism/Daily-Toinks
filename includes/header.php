<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? '';
$userRole   = $_SESSION['user_role'] ?? '';
$isStaff    = in_array($userRole, ['admin', 'manager', 'rider']);
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<header class="header">
    <!-- Top Bar -->
    <div class="header-top">
        <div class="container">
            <div class="header-top-links">
                <span>Welcome to DailyToinks!</span>
            </div>
            <div class="header-top-links">
                <?php if ($isLoggedIn && !$isStaff): ?>
                    <a href="/normss/order-history.php">My Orders</a>
                    <a href="/normss/my-tickets.php">My Tickets</a>
                    <a href="/normss/account.php">My Account</a>
                <?php elseif (!$isLoggedIn): ?>
                    <a href="/normss/login.php">Login</a>
                    <a href="/normss/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="header-main">
        <div class="container">
            <div class="header-content">

                <!-- Logo -->
                <a href="/normss/index.php" class="logo">
                    <span class="logo-secure">Daily</span><span class="logo-store">Toinks</span>
                </a>

                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="search-input" placeholder="Search products, brands and more...">
                    <button id="search-button" aria-label="Search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </div>

                <!-- Header Actions -->
                <div class="header-actions">
                    <?php if (!$isStaff): ?>
                    <!-- Cart Icon (hidden for staff) -->
                    <a href="/normss/cart.php" class="cart-icon" aria-label="Shopping cart">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <span class="cart-badge" id="cart-count">0</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <!-- User Dropdown -->
                        <div class="user-menu" id="user-menu">
                            <button class="user-menu-trigger" id="user-menu-trigger" aria-expanded="false">
                                <span class="user-avatar"><?= htmlspecialchars($userInitial) ?></span>
                                <span class="user-name-text"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
                                <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div class="user-dropdown" id="user-dropdown">
                                <div class="user-dropdown-header">
                                    <div class="user-dropdown-avatar"><?= htmlspecialchars($userInitial) ?></div>
                                    <div>
                                        <div class="user-dropdown-name"><?= htmlspecialchars($userName) ?></div>
                                        <div class="user-dropdown-role"><?= htmlspecialchars(ucfirst($userRole)) ?></div>
                                    </div>
                                </div>
                                <div class="user-dropdown-divider"></div>
                                <?php if ($isStaff): ?>
                                    <a href="/normss/admin/index.php" class="user-dropdown-item">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        Admin Panel
                                    </a>
                                <?php else: ?>
                                    <a href="/normss/account.php" class="user-dropdown-item">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        My Account
                                    </a>
                                    <a href="/normss/order-history.php" class="user-dropdown-item">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                        My Orders
                                    </a>
                                    <a href="/normss/my-tickets.php" class="user-dropdown-item">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><circle cx="9" cy="6" r="3"/><path d="M6 21v-2a4 4 0 0 1 4-4h0"/></svg>
                                        My Tickets
                                    </a>
                                <?php endif; ?>
                                <div class="user-dropdown-divider"></div>
                                <button class="user-dropdown-item user-dropdown-logout" onclick="fetch('/normss/api/auth.php?action=logout').then(()=>window.location.href='/normss/index.php')">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/normss/login.php" id="login-btn" class="btn btn-secondary">Login</a>
                        <a href="/normss/register.php" id="register-btn" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <ul class="nav-list">
                <li><a href="/normss/index.php">Home</a></li>
                <li><a href="/normss/products.php">All Products</a></li>
                <li><a href="/normss/products.php?category=Smartphones">Smartphones</a></li>
                <li><a href="/normss/products.php?category=Laptops">Laptops</a></li>
                <li><a href="/normss/products.php?category=Desktops">Desktops</a></li>
                <li><a href="/normss/products.php?category=Phone+Accessories">Accessories</a></li>
                <?php if (!$isStaff): ?>
                <li><a href="/normss/order-tracking.php">Track Order</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>

<script>
// Staff flag for JS (hide purchase buttons)
window.__IS_STAFF__ = <?= $isStaff ? 'true' : 'false' ?>;

// User dropdown toggle
(function() {
    const trigger = document.getElementById('user-menu-trigger');
    const dropdown = document.getElementById('user-dropdown');
    if (!trigger) return;
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen);
    });
    document.addEventListener('click', function() {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    });
})();

// === SESSION TIMEOUT AUTO-LOGOUT ===
<?php if ($isLoggedIn): ?>
(function() {
    let timeoutModal = null;
    let countdownInterval = null;
    let idleTimer = null;
    let pollInterval = null;
    let lastActivity = Date.now();
    let sessionTimeout = 2 * 60; // Default 2 minutes in seconds (will be updated from server)
    let warningShown = false;

    // Fetch timeout setting from server
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

    // Active monitor — runs every second, independent of user activity.
    // This guarantees auto-logout even when the user is completely idle.
    function startMonitoring() {
        clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            const idleSeconds = Math.floor((Date.now() - lastActivity) / 1000);

            // Show warning 30 seconds before timeout
            if (!warningShown && idleSeconds >= sessionTimeout - 30 && idleSeconds < sessionTimeout) {
                showWarning(sessionTimeout - idleSeconds);
            }

            // Force logout when timeout reached
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
            if (remaining <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    function doLogout() {
        clearInterval(countdownInterval);
        clearInterval(pollInterval);
        // Server logout, then force redirect — page does not need to be reloaded by user
        fetch('/normss/api/auth.php?action=logout')
            .finally(() => {
                window.location.replace('/normss/login.php?expired=1');
            });
    }

    function recordActivity() {
        lastActivity = Date.now();
        if (warningShown) {
            warningShown = false;
            if (timeoutModal) timeoutModal.style.display = 'none';
            clearInterval(countdownInterval);
        }
    }

    // Extend session — user clicked "Stay Logged In"
    window.extendSession = function() {
        recordActivity();
        fetch('/normss/api/auth.php?action=session-info').catch(() => {});
    };

    // Track activity (do NOT reset during warning — user must explicitly click button)
    ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            if (warningShown) return;
            recordActivity();
        }, { passive: true });
    });

    // Start monitoring immediately with default timeout (in case server fetch is slow)
    startMonitoring();
})();
<?php endif; ?>
</script>