<?php
// ===================================
// AUTHENTICATION & AUTHORIZATION
// ===================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

// === HTTPS ENFORCEMENT (auto-skips localhost) ===
enforceHttps();

// === SECURITY HEADERS ===
setSecurityHeaders();

// === GENERATE CSRF TOKEN ===
generateCsrfToken();

// === SESSION TIMEOUT CHECK ===
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    $timeout = (int) getSetting('session_timeout') * 60; // Convert minutes to seconds
    if ($timeout > 0 && (time() - $_SESSION['last_activity']) > $timeout) {
        // Session expired
        session_unset();
        session_destroy();
        session_start(); // Start fresh session for possible redirect
    }
}

// === ACCOUNT VALIDITY CHECK (inactive / locked / deleted) ===
// Forces immediate logout for users whose account was disabled or locked while
// they were already logged in. Runs on every authenticated request.
if (isset($_SESSION['user_id'])) {
    try {
        $_authDb = getDB();
        $_authStmt = $_authDb->prepare("SELECT status, locked_until FROM users WHERE id = ?");
        $_authStmt->execute([$_SESSION['user_id']]);
        $_authRow = $_authStmt->fetch();

        $_kickReason = null;
        if (!$_authRow) {
            $_kickReason = 'account_deleted';
        } elseif ($_authRow['status'] === 'inactive') {
            $_kickReason = 'account_inactive';
        } elseif (!empty($_authRow['locked_until'])) {
            $_kickReason = 'account_locked';
        }

        if ($_kickReason) {
            session_unset();
            session_destroy();
            // For API requests return JSON; for page requests redirect.
            $_isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
                   || (($_SERVER['HTTP_ACCEPT'] ?? '') && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($_isApi) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Your account has been disabled or locked. Please contact an administrator.',
                    'logout' => true,
                    'reason' => $_kickReason
                ]);
                exit;
            } else {
                header('Location: /normss/login.php?disabled=1');
                exit;
            }
        }
    } catch (Exception $_e) {
        // If DB check fails, do not break the page — just continue
    }
}

// Update last activity timestamp
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged-in user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'phone' => $_SESSION['user_phone'] ?? '',
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Require user to be logged in — redirects to login page if not
 */
function requireLogin($redirect = '/normss/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Redirect staff (admin/manager/rider) away from public pages to admin panel
 * Call this at the top of public-facing pages
 */
function redirectStaffToAdmin() {
    if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'manager', 'rider'])) {
        header('Location: /normss/admin/index.php');
        exit;
    }
}

/**
 * Check if the current user is a staff member
 */
function isStaffUser() {
    return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'manager', 'rider']);
}

/**
 * Require user to have one of the specified roles
 * @param array $allowedRoles e.g. ['admin', 'manager']
 */
function requireRole($allowedRoles, $redirect = '/normss/login.php') {
    requireLogin($redirect);

    // Enforce MFA for staff: if a staff user is logged in without MFA enabled,
    // force them to set it up before accessing any admin page (except setup page itself).
    $staffRoles = ['admin', 'manager', 'rider'];
    $currentRole = $_SESSION['user_role'] ?? '';
    $isOnSetupPage = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/mfa-setup-required.php') !== false;
    if (in_array($currentRole, $staffRoles, true) && !$isOnSetupPage) {
        try {
            $_db = getDB();
            $_stmt = $_db->prepare("SELECT mfa_enabled FROM users WHERE id = ?");
            $_stmt->execute([$_SESSION['user_id']]);
            $_mfa = (int) $_stmt->fetchColumn();
            if ($_mfa !== 1) {
                header('Location: /normss/admin/mfa-setup-required.php');
                exit;
            }
        } catch (Exception $_e) { /* fall through */ }
    }

    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title>
        <link rel="stylesheet" href="/normss/css/styles.css"></head>
        <body style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">
        <div><h1 style="color:var(--primary-color);font-size:3rem;">403</h1>
        <h2>Access Denied</h2><p style="color:#666;margin:1rem 0;">You do not have permission to access this page.</p>
        <a href="/normss/index.php" class="btn btn-primary">Go Home</a></div></body></html>';
        exit;
    }
}

/**
 * Set user session after successful login
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $rawPhone = $user['phone'] ?? '';
    $_SESSION['user_phone'] = $rawPhone ? decryptData($rawPhone) : '';
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
}

/**
 * Destroy user session (logout)
 */
function destroySession() {
    session_unset();
    session_destroy();
}

/**
 * Send JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Require JSON API auth — returns 401 JSON instead of redirect
 */
function requireApiAuth() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Authentication required'], 401);
    }
}

/**
 * Require JSON API role — returns 403 JSON
 */
function requireApiRole($allowedRoles) {
    requireApiAuth();
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
    }
}
