<?php
// ===================================
// SECURITY CONFIGURATION
// TOTP, Password Policy, Settings
// ===================================

require_once __DIR__ . '/database.php';

// === reCAPTCHA v2 KEYS ===
define('RECAPTCHA_SITE_KEY', '6LeABcssAAAAAB2jWom5xyALcoFgTtFnqj3WIAw1');
define('RECAPTCHA_SECRET_KEY', '6LeABcssAAAAABK2XNwyelCqC3Q4sxowkxYh29sy');

// === ENCRYPTION KEY (CHANGE IN PRODUCTION) ===
define('ENCRYPTION_KEY', 'dT0!nk$_s3cur3_k3y_2024_ch@ng3m3');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// === SYSTEM SETTINGS LOADER ===

function getSecuritySettings() {
    static $settings = null;
    if ($settings !== null) return $settings;
    
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Defaults if DB is empty
    $defaults = [
        'max_login_attempts' => '3',
        'lockout_duration' => '15',
        'session_timeout' => '2',
        'min_password_length' => '12',
        'require_uppercase' => '1',
        'require_lowercase' => '1',
        'require_number' => '1',
        'require_special_char' => '1',
        'password_expiry_days' => '90'
    ];
    
    foreach ($defaults as $k => $v) {
        if (!isset($settings[$k])) $settings[$k] = $v;
    }
    
    return $settings;
}

function getSetting($key) {
    $settings = getSecuritySettings();
    return $settings[$key] ?? null;
}

// === PASSWORD POLICY VALIDATION ===

function validatePasswordPolicy($password) {
    $settings = getSecuritySettings();
    $errors = [];
    
    $minLen = (int) $settings['min_password_length'];
    if (strlen($password) < $minLen) {
        $errors[] = "Password must be at least {$minLen} characters long";
    }
    if ($settings['require_uppercase'] === '1' && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if ($settings['require_lowercase'] === '1' && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if ($settings['require_number'] === '1' && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if ($settings['require_special_char'] === '1' && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// === ACCOUNT LOCKOUT ===

function checkAccountLock($user) {
    // Permanent lock: any non-null locked_until means the account is locked
    // until an admin manually unlocks it.
    if (!empty($user['locked_until'])) {
        return [
            'locked' => true,
            'locked_until' => $user['locked_until']
        ];
    }
    return ['locked' => false];
}

function recordFailedLogin($db, $userId, $email) {
    $settings = getSecuritySettings();
    $maxAttempts = (int) $settings['max_login_attempts'];

    // Log attempt FIRST so it's recorded regardless
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
    $stmt->execute([$email, $ip]);

    // Increment failed login count
    $stmt = $db->prepare("UPDATE users SET failed_logins = failed_logins + 1 WHERE id = ?");
    $stmt->execute([$userId]);

    // Check if we should lock
    $stmt = $db->prepare("SELECT failed_logins FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user['failed_logins'] >= $maxAttempts) {
        // Permanent lock — far-future timestamp so we know it's "locked indefinitely"
        // Admin must manually unlock by setting this to NULL.
        $lockUntil = '9999-12-31 23:59:59';
        $stmt = $db->prepare("UPDATE users SET locked_until = ? WHERE id = ?");
        $stmt->execute([$lockUntil, $userId]);

        // Log to locked_accounts table (permanent lock indicator)
        logAccountLockout($userId, $email, $user['failed_logins'], $lockUntil);

        // Audit log
        logAudit('account_locked', 'user', $userId, "Account locked permanently after {$user['failed_logins']} failed attempts. Admin unlock required.");

        return ['locked' => true];
    }

    return ['locked' => false];
}

function resetFailedLogins($db, $userId) {
    $stmt = $db->prepare("UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$userId]);
}

// === EMAIL ACTIVATION ===

function generateActivationToken() {
    return bin2hex(random_bytes(32));
}

// === reCAPTCHA VERIFICATION ===

function verifyRecaptcha($response) {
    if (RECAPTCHA_SECRET_KEY === 'YOUR_SECRET_KEY_HERE') {
        // Skip verification if secret key not configured (dev mode)
        return true;
    }
    
    if (empty($response)) return false;
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) return true; // Fail open for local dev
    
    $json = json_decode($result, true);
    
    // For reCAPTCHA v3: check success AND score (0.0 = bot, 1.0 = human)
    if (!($json['success'] ?? false)) return false;
    
    // If score exists (v3), require at least 0.5
    if (isset($json['score']) && $json['score'] < 0.5) return false;
    
    return true;
}

// === TOTP (Google Authenticator) ===

class TOTP {
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    /**
     * Generate a random secret key
     */
    public static function generateSecret($length = 16) {
        $secret = '';
        $chars = self::$base32Chars;
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Get the current TOTP code for a secret
     */
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify a TOTP code with strict replay protection.
     *
     * STRICT MODE: discrepancy=0 means only the CURRENT 30-second time slice is accepted.
     * The moment the code refreshes on the user's phone, the old code is rejected.
     * This prevents replay attacks where a user enters an old code after it has expired.
     *
     * @param string $secret  TOTP secret
     * @param string $code    6-digit code from authenticator
     * @param int|null $lastUsedSlice Last successfully used time slice (replay prevention)
     * @param int $discrepancy ±N time slices tolerance for clock drift (0 = strict, no drift)
     * @return int|false Returns the matched time slice on success, or false on failure
     */
    public static function verifyCode($secret, $code, $lastUsedSlice = null, $discrepancy = 0) {
        $currentSlice = floor(time() / 30);
        $padded = str_pad($code, 6, '0', STR_PAD_LEFT);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $slice = $currentSlice + $i;

            // Replay protection: reject any slice already used (or older)
            if ($lastUsedSlice !== null && $slice <= $lastUsedSlice) {
                continue;
            }

            if (self::getCode($secret, $slice) === $padded) {
                return $slice;
            }
        }
        return false;
    }
    
    /**
     * Generate a QR code URL for Google Authenticator
     */
    public static function getQRCodeUrl($label, $secret, $issuer = 'DailyToinks') {
        $otpauthUrl = "otpauth://totp/" . urlencode($issuer . ':' . $label) . "?" . http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30
        ]);
        // Use quickchart.io free QR code API
        return "https://quickchart.io/qr?text=" . urlencode($otpauthUrl) . "&size=200";
    }
    
    /**
     * Base32 decode helper
     */
    private static function base32Decode($input) {
        $map = array_flip(str_split(self::$base32Chars));
        $input = strtoupper($input);
        $input = str_replace('=', '', $input);
        
        $binary = '';
        foreach (str_split($input) as $char) {
            if (!isset($map[$char])) continue;
            $binary .= str_pad(decbin($map[$char]), 5, '0', STR_PAD_LEFT);
        }
        
        $output = '';
        for ($i = 0; $i + 8 <= strlen($binary); $i += 8) {
            $output .= chr(bindec(substr($binary, $i, 8)));
        }
        return $output;
    }
}

// ===================================
// CSRF PROTECTION
// ===================================

function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

function validateCsrfToken() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) return true;
    
    // Check header first, then POST body
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

function requireCsrf() {
    if (!validateCsrfToken()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token']);
        exit;
    }
}

// ===================================
// DATA ENCRYPTION (AES-256-CBC)
// ===================================

function encryptData($plaintext) {
    if (empty($plaintext)) return $plaintext;
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($plaintext, ENCRYPTION_METHOD, $key, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

function decryptData($ciphertext) {
    if (empty($ciphertext)) return $ciphertext;
    $data = base64_decode($ciphertext);
    if ($data === false || strpos($data, '::') === false) return $ciphertext;
    list($iv, $encrypted) = explode('::', $data, 2);
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $decrypted = openssl_decrypt($encrypted, ENCRYPTION_METHOD, $key, 0, $iv);
    return $decrypted !== false ? $decrypted : $ciphertext;
}

// ===================================
// AUDIT LOGGING
// ===================================

function logAudit($action, $entityType = null, $entityId = null, $details = null) {
    try {
        $db = getDB();
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? null;
        $userName = $_SESSION['user_name'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pageUrl = $_SERVER['HTTP_REFERER'] ?? ($_SERVER['REQUEST_URI'] ?? '');
        
        $stmt = $db->prepare(
            "INSERT INTO audit_logs (user_id, user_email, user_name, action, entity_type, entity_id, details, ip_address, user_agent, page_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId, $userEmail, $userName, $action,
            $entityType, $entityId, $details,
            $ip, substr($userAgent, 0, 500), substr($pageUrl, 0, 500)
        ]);
    } catch (Exception $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}

// ===================================
// LOCKED ACCOUNTS TRACKING
// ===================================

function logAccountLockout($userId, $email, $failedAttempts, $lockedUntil) {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare(
            "INSERT INTO locked_accounts (user_id, email, failed_attempts, locked_at, locked_until, ip_address)
             VALUES (?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->execute([$userId, $email, $failedAttempts, $lockedUntil, $ip]);
    } catch (Exception $e) {
        error_log('Lock log error: ' . $e->getMessage());
    }
}

// ===================================
// SECURE FILE UPLOAD VALIDATION
// ===================================

function validateFileUpload($file, $maxSizeMB = 2) {
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['File upload failed. Error code: ' . ($file['error'] ?? 'unknown')];
    }
    
    // Check file size
    $maxBytes = $maxSizeMB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $errors[] = "File size exceeds {$maxSizeMB}MB limit";
    }
    
    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions);
    }
    
    // Check MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file content type detected';
    }
    
    // Verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'File is not a valid image';
    }
    
    return $errors;
}

// ===================================
// SECURITY HEADERS
// ===================================

function setSecurityHeaders() {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ===================================
// HTTPS ENFORCEMENT (enable in production)
// ===================================
function enforceHttps() {
    if (headers_sent()) return;
    // Skip on localhost/development
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, 'localhost:') === 0) {
        return;
    }
    // Skip if behind a reverse proxy (ngrok, cloudflare, etc.) that already handles HTTPS
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($forwardedProto === 'https') {
        return;
    }
    // Redirect to HTTPS in production
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirectUrl = 'https://' . $host . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirectUrl, true, 301);
        exit;
    }
}
