<?php
// ===================================
// AUTH API ENDPOINT
// Handles: login, register, logout, password reset, MFA, email activation
// ===================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CSRF validation for state-changing actions
$csrfExempt = ['me', 'session-info'];
if (!in_array($action, $csrfExempt) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleMe();
        break;
    case 'activate':
        handleActivate();
        break;
    case 'verify-mfa':
        handleVerifyMFA();
        break;
    case 'setup-mfa':
        handleSetupMFA();
        break;
    case 'enable-mfa':
        handleEnableMFA();
        break;
    case 'disable-mfa':
        handleDisableMFA();
        break;
    case 'pending-mfa-setup':
        handlePendingMFASetup();
        break;
    case 'pending-mfa-enable':
        handlePendingMFAEnable();
        break;
    case 'request-reset':
        handleRequestReset();
        break;
    case 'verify-code':
        handleVerifyCode();
        break;
    case 'reset-password':
        handleResetPassword();
        break;
    case 'session-info':
        handleSessionInfo();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

// ===================================
// LOGIN
// ===================================
function handleLogin()
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Please fill in all fields'], 400);
    }

    $db = getDB();

    // Find user (include locked/inactive for proper error messages)
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }

    // Check if account is locked
    $lockStatus = checkAccountLock($user);
    if ($lockStatus['locked']) {
        jsonResponse([
            'success' => false,
            'message' => "Account is locked. Try again in {$lockStatus['minutes_remaining']} minute(s).",
            'locked' => true,
            'locked_until' => $lockStatus['locked_until']
        ], 403);
    }

    // Check if account is inactive (disabled by admin, NOT pending email verification)
    if ($user['status'] === 'inactive' && $user['email_verified'] == 1) {
        jsonResponse(['success' => false, 'message' => 'Account has been disabled. Contact administrator.'], 403);
    }

    // Check if email is verified
    if (isset($user['email_verified']) && $user['email_verified'] == 0) {
        jsonResponse([
            'success' => false,
            'message' => 'Please verify your email address first. Check your email for the activation link.',
            'email_not_verified' => true
        ], 403);
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Record failed attempt
        $result = recordFailedLogin($db, $user['id'], $email);

        if ($result['locked']) {
            jsonResponse([
                'success' => false,
                'message' => "Account is locked due to too many failed login attempts. Please contact an administrator to unlock.",
                'locked' => true
            ], 403);
        }

        // Generic message — no attempts remaining warning
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }

    // Password correct — reset failed logins
    resetFailedLogins($db, $user['id']);

    // Log successful attempt
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)");
    $stmt->execute([$email, $ip]);

    // Audit log: login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    logAudit('login', 'user', $user['id'], 'User logged in successfully');
    unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);

    // Check if MFA is enabled
    if (!empty($user['mfa_enabled']) && $user['mfa_enabled'] == 1 && !empty($user['totp_secret'])) {
        // Don't set session yet — require MFA verification
        $mfaToken = bin2hex(random_bytes(16));
        $_SESSION['mfa_pending_user_id'] = $user['id'];
        $_SESSION['mfa_token'] = $mfaToken;
        $_SESSION['mfa_timestamp'] = time();

        jsonResponse([
            'success' => true,
            'mfa_required' => true,
            'mfa_token' => $mfaToken,
            'message' => 'Please enter your authenticator code'
        ]);
    }

    // Enforce MFA for staff roles (admin, manager, rider)
    // Staff must enable MFA before they can fully access the system.
    $staffRoles = ['admin', 'manager', 'rider'];
    if (in_array($user['role'], $staffRoles, true) && empty($user['mfa_enabled'])) {
        // Set a limited session that only allows MFA setup
        $mfaToken = bin2hex(random_bytes(16));
        $_SESSION['mfa_setup_user_id'] = $user['id'];
        $_SESSION['mfa_setup_token'] = $mfaToken;
        $_SESSION['mfa_setup_timestamp'] = time();

        jsonResponse([
            'success' => true,
            'mfa_setup_required' => true,
            'mfa_token' => $mfaToken,
            'message' => 'Two-factor authentication is required for staff accounts. Please set it up now.'
        ]);
    }

    // No MFA — login directly
    setUserSession($user);
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// ===================================
// MFA VERIFICATION
// ===================================
function handleVerifyMFA()
{
    $code = trim($_POST['code'] ?? '');
    $token = trim($_POST['mfa_token'] ?? '');

    if (empty($code) || empty($token)) {
        jsonResponse(['success' => false, 'message' => 'Code and token are required'], 400);
    }

    // Verify the MFA token from the login step
    if (!isset($_SESSION['mfa_pending_user_id']) || !isset($_SESSION['mfa_token']) || $_SESSION['mfa_token'] !== $token) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired MFA session. Please login again.'], 401);
    }

    // Check timeout (5 minutes)
    if (time() - ($_SESSION['mfa_timestamp'] ?? 0) > 300) {
        unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_token'], $_SESSION['mfa_timestamp']);
        jsonResponse(['success' => false, 'message' => 'MFA session expired. Please login again.'], 401);
    }

    $userId = $_SESSION['mfa_pending_user_id'];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['totp_secret'])) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    // Verify TOTP code with replay protection
    $lastUsedSlice = $user['totp_last_used_slice'] !== null ? (int)$user['totp_last_used_slice'] : null;
    $matchedSlice = TOTP::verifyCode($user['totp_secret'], $code, $lastUsedSlice);
    if ($matchedSlice === false) {
        jsonResponse(['success' => false, 'message' => 'Invalid or already-used authenticator code. Please wait for a new code.'], 401);
    }

    // Save the used slice to prevent replay attacks
    $stmt = $db->prepare("UPDATE users SET totp_last_used_slice = ? WHERE id = ?");
    $stmt->execute([$matchedSlice, $user['id']]);

    // MFA verified — clean up and set session
    unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_token'], $_SESSION['mfa_timestamp']);
    setUserSession($user);

    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// ===================================
// MFA SETUP (generate secret + QR)
// ===================================
function handleSetupMFA()
{
    requireApiAuth();

    $user = getCurrentUser();
    $db = getDB();

    // Generate new secret
    $secret = TOTP::generateSecret();

    // Store it temporarily (not enabled yet until verified)
    $stmt = $db->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
    $stmt->execute([$secret, $user['id']]);

    $qrUrl = TOTP::getQRCodeUrl($user['email'], $secret);

    jsonResponse([
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qrUrl,
        'message' => 'Scan the QR code with Google Authenticator, then verify with a code'
    ]);
}

// ===================================
// ENABLE MFA (after verifying code)
// ===================================
function handleEnableMFA()
{
    requireApiAuth();

    $code = trim($_POST['code'] ?? '');
    if (empty($code)) {
        jsonResponse(['success' => false, 'message' => 'Please enter the code from your authenticator'], 400);
    }

    $user = getCurrentUser();
    $db = getDB();

    $stmt = $db->prepare("SELECT totp_secret FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (empty($row['totp_secret'])) {
        jsonResponse(['success' => false, 'message' => 'Please set up MFA first'], 400);
    }

    // Verify with replay protection (no last_used yet during initial setup)
    $matchedSlice = TOTP::verifyCode($row['totp_secret'], $code);
    if ($matchedSlice === false) {
        jsonResponse(['success' => false, 'message' => 'Invalid code. Please try again.'], 400);
    }

    // Enable MFA and record the used slice
    $stmt = $db->prepare("UPDATE users SET mfa_enabled = 1, totp_last_used_slice = ? WHERE id = ?");
    $stmt->execute([$matchedSlice, $user['id']]);

    jsonResponse(['success' => true, 'message' => 'MFA enabled successfully! You will need your authenticator for future logins.']);
}

// ===================================
// PENDING MFA SETUP (for staff first login)
// Works with mfa_setup_user_id session, not full session.
// ===================================
function handlePendingMFASetup()
{
    if (empty($_SESSION['mfa_setup_user_id']) || empty($_SESSION['mfa_setup_token'])) {
        jsonResponse(['success' => false, 'message' => 'No pending MFA setup. Please log in again.'], 401);
    }
    if (time() - ($_SESSION['mfa_setup_timestamp'] ?? 0) > 600) {
        unset($_SESSION['mfa_setup_user_id'], $_SESSION['mfa_setup_token'], $_SESSION['mfa_setup_timestamp']);
        jsonResponse(['success' => false, 'message' => 'Setup session expired. Please log in again.'], 401);
    }

    $userId = $_SESSION['mfa_setup_user_id'];
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    $secret = TOTP::generateSecret();
    $stmt = $db->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
    $stmt->execute([$secret, $userId]);

    $qrUrl = TOTP::getQRCodeUrl($user['email'], $secret);

    jsonResponse([
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qrUrl,
        'email' => $user['email']
    ]);
}

function handlePendingMFAEnable()
{
    if (empty($_SESSION['mfa_setup_user_id']) || empty($_SESSION['mfa_setup_token'])) {
        jsonResponse(['success' => false, 'message' => 'No pending MFA setup. Please log in again.'], 401);
    }

    $code = trim($_POST['code'] ?? '');
    $token = trim($_POST['mfa_token'] ?? '');
    if (empty($code) || empty($token) || $token !== $_SESSION['mfa_setup_token']) {
        jsonResponse(['success' => false, 'message' => 'Invalid setup token or missing code'], 400);
    }

    $userId = $_SESSION['mfa_setup_user_id'];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || empty($user['totp_secret'])) {
        jsonResponse(['success' => false, 'message' => 'Setup not initialized. Please scan the QR first.'], 400);
    }

    $matchedSlice = TOTP::verifyCode($user['totp_secret'], $code);
    if ($matchedSlice === false) {
        jsonResponse(['success' => false, 'message' => 'Invalid code. Please try again.'], 400);
    }

    // Enable MFA, save used slice, then create the full session
    $stmt = $db->prepare("UPDATE users SET mfa_enabled = 1, totp_last_used_slice = ? WHERE id = ?");
    $stmt->execute([$matchedSlice, $userId]);

    unset($_SESSION['mfa_setup_user_id'], $_SESSION['mfa_setup_token'], $_SESSION['mfa_setup_timestamp']);

    // Refresh user data and set session
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    setUserSession($user);

    logAudit('mfa_enabled', 'user', $userId, 'Staff account MFA enabled (mandatory)');

    jsonResponse([
        'success' => true,
        'message' => 'MFA enabled. Welcome!',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// ===================================
// DISABLE MFA
// ===================================
function handleDisableMFA()
{
    requireApiAuth();

    $code = trim($_POST['code'] ?? '');
    if (empty($code)) {
        jsonResponse(['success' => false, 'message' => 'Please enter the code from your authenticator to confirm'], 400);
    }

    $user = getCurrentUser();
    $db = getDB();

    $stmt = $db->prepare("SELECT totp_secret FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    // Disabling MFA also requires a fresh (unused) code
    $stmt2 = $db->prepare("SELECT totp_last_used_slice FROM users WHERE id = ?");
    $stmt2->execute([$user['id']]);
    $lastUsed = $stmt2->fetchColumn();
    $lastUsedSlice = $lastUsed !== null && $lastUsed !== false ? (int)$lastUsed : null;

    if (TOTP::verifyCode($row['totp_secret'], $code, $lastUsedSlice) === false) {
        jsonResponse(['success' => false, 'message' => 'Invalid or already-used code'], 400);
    }

    // Block staff from disabling MFA (it is mandatory for them)
    $u = getCurrentUser();
    $staffRoles = ['admin', 'manager', 'rider'];
    if (in_array($u['role'] ?? '', $staffRoles, true)) {
        jsonResponse(['success' => false, 'message' => 'MFA is required for staff accounts and cannot be disabled.'], 403);
    }

    $stmt = $db->prepare("UPDATE users SET mfa_enabled = 0, totp_secret = NULL, totp_last_used_slice = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    jsonResponse(['success' => true, 'message' => 'MFA has been disabled']);
}

// ===================================
// REGISTER
// ===================================
function handleRegister()
{
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Please fill in all required fields'], 400);
    }

    // Validate phone - exactly 11 digits
    $phoneDigits = preg_replace('/\D/', '', $phone);
    if (strlen($phoneDigits) !== 11) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be exactly 11 digits'], 400);
    }

    // Verify reCAPTCHA
    if (empty($captchaResponse)) {
        jsonResponse(['success' => false, 'message' => 'Please complete the CAPTCHA verification'], 400);
    }
    if (!verifyRecaptcha($captchaResponse)) {
        jsonResponse(['success' => false, 'message' => 'CAPTCHA verification failed. Please try again.'], 400);
    }

    // Validate password policy
    $policyErrors = validatePasswordPolicy($password);
    if (!empty($policyErrors)) {
        jsonResponse(['success' => false, 'message' => implode('. ', $policyErrors)], 400);
    }

    $db = getDB();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email already registered'], 409);
    }

    // Generate activation token
    $token = generateActivationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Create user (unverified) — encrypt phone number
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $encryptedPhone = encryptData($phone);
    $stmt = $db->prepare(
        "INSERT INTO users (name, email, phone, password, role, status, email_verified, email_token, email_token_expires, password_changed_at)
         VALUES (?, ?, ?, ?, 'customer', 'inactive', 0, ?, ?, NOW())"
    );
    $stmt->execute([$name, $email, $encryptedPhone, $hashedPassword, $token, $tokenExpires]);

    // Audit log: registration
    logAudit('register', 'user', $db->lastInsertId(), "New user registered: {$email}");

    // Build activation link
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $activationLink = "{$protocol}://{$host}/normss/activate.php?token={$token}";

    // Send activation email
    $emailSent = sendActivationEmail($email, $name, $activationLink);

    jsonResponse([
        'success' => true,
        'message' => $emailSent
            ? 'Registration successful! An activation link has been sent to your email.'
            : 'Registration successful! Please check your email to activate your account.',
        'email_sent' => $emailSent
    ]);
}

// ===================================
// EMAIL ACTIVATION
// ===================================
function handleActivate()
{
    $token = trim($_GET['token'] ?? $_POST['token'] ?? '');

    if (empty($token)) {
        jsonResponse(['success' => false, 'message' => 'Activation token is required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id, email_token_expires FROM users WHERE email_token = ? AND email_verified = 0"
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid or already used activation link'], 400);
    }

    // Check expiry
    if (strtotime($user['email_token_expires']) < time()) {
        jsonResponse(['success' => false, 'message' => 'Activation link has expired. Please register again.'], 400);
    }

    // Activate the account
    $stmt = $db->prepare(
        "UPDATE users SET email_verified = 1, status = 'active', email_token = NULL, email_token_expires = NULL WHERE id = ?"
    );
    $stmt->execute([$user['id']]);

    // Audit log: activation
    logAudit('activate_account', 'user', $user['id'], 'Email account activated');

    jsonResponse(['success' => true, 'message' => 'Account activated successfully! You can now login.']);
}

// ===================================
// SESSION INFO (for timeout JS)
// ===================================
function handleSessionInfo()
{
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'logged_in' => false]);
    }

    $settings = getSecuritySettings();
    $timeout = (int) $settings['session_timeout'];
    $lastActivity = $_SESSION['last_activity'] ?? time();
    $elapsed = time() - $lastActivity;
    $remaining = max(0, ($timeout * 60) - $elapsed);

    jsonResponse([
        'success' => true,
        'logged_in' => true,
        'timeout_minutes' => $timeout,
        'timeout_seconds' => $timeout * 60,
        'remaining_seconds' => $remaining
    ]);
}

// ===================================
// LOGOUT
// ===================================
function handleLogout()
{
    // Audit log: logout (before destroying session)
    logAudit('logout', 'user', $_SESSION['user_id'] ?? null, 'User logged out');
    destroySession();
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

// ===================================
// ME
// ===================================
function handleMe()
{
    $user = getCurrentUser();
    if ($user) {
        // Also fetch MFA status
        $db = getDB();
        $stmt = $db->prepare("SELECT mfa_enabled FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();
        $user['mfa_enabled'] = (bool)($row['mfa_enabled'] ?? false);

        jsonResponse(['success' => true, 'user' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
}

// ===================================
// PASSWORD RESET FLOW
// ===================================
function handleRequestReset()
{
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        jsonResponse(['success' => false, 'message' => 'Please enter your email'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email address not found'], 404);
    }

    // Generate 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store reset code
    $stmt = $db->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $code, $expiresAt]);

    // In production, send email. For demo, return the code.
    jsonResponse([
        'success' => true,
        'message' => 'Reset code sent to your email!',
        'code' => $code // Remove in production
    ]);
}

function handleVerifyCode()
{
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if (empty($email) || empty($code)) {
        jsonResponse(['success' => false, 'message' => 'Email and code are required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    $reset = $stmt->fetch();

    if (!$reset) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset code'], 400);
    }

    jsonResponse(['success' => true, 'message' => 'Code verified successfully']);
}

function handleResetPassword()
{
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($email) || empty($code) || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }

    // Validate password policy
    $policyErrors = validatePasswordPolicy($newPassword);
    if (!empty($policyErrors)) {
        jsonResponse(['success' => false, 'message' => implode('. ', $policyErrors)], 400);
    }

    $db = getDB();

    // Verify code again
    $stmt = $db->prepare("SELECT id FROM password_resets WHERE email = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    $reset = $stmt->fetch();

    if (!$reset) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset code'], 400);
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE email = ?");
    $stmt->execute([$hashedPassword, $email]);

    // Mark code as used
    $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->execute([$reset['id']]);

    // Audit log: password reset
    logAudit('password_reset', 'user', null, "Password reset for: {$email}");

    jsonResponse(['success' => true, 'message' => 'Password has been reset successfully!']);
}
