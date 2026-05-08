<?php
// ===================================
// SETTINGS API ENDPOINT
// Admin-configurable security settings
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// CSRF validation for state-changing requests
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireCsrf();
}

switch ($method) {
    case 'GET':
        requireApiRole(['admin']);
        handleGet();
        break;
    case 'PUT':
        requireApiRole(['admin']);
        handleUpdate();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value, description, updated_at FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll();

    jsonResponse(['success' => true, 'settings' => $settings]);
}

function handleUpdate()
{
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input) || !is_array($input)) {
        jsonResponse(['success' => false, 'message' => 'No settings provided'], 400);
    }

    // Allowed settings keys
    $allowed = [
        'max_login_attempts', 'lockout_duration', 'session_timeout',
        'min_password_length', 'require_uppercase', 'require_lowercase',
        'require_number', 'require_special_char', 'password_expiry_days',
        'paymongo_secret_key', 'paymongo_public_key', 'ngrok_url'
    ];

    $updated = 0;
    foreach ($input as $key => $value) {
        if (in_array($key, $allowed)) {
            $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([(string)$value, $key]);
            $updated++;
        }
    }

    if ($updated === 0) {
        jsonResponse(['success' => false, 'message' => 'No valid settings to update'], 400);
    }

    logAudit('update', 'settings', null, "Updated {$updated} security setting(s): " . implode(', ', array_keys(array_intersect_key($input, array_flip($allowed)))));

    jsonResponse(['success' => true, 'message' => "{$updated} setting(s) updated successfully"]);
}
