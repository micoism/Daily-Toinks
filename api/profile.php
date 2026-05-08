<?php
// ===================================
// PROFILE API ENDPOINT
// Handles: get profile, update info, change password, manage address
// ===================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// CSRF validation for state-changing requests
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireCsrf();
}

switch ($method) {
    case 'GET':
        requireApiAuth();
        handleGetProfile();
        break;
    case 'PUT':
        requireApiAuth();
        handleUpdateProfile();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// ===================================
// GET PROFILE
// ===================================
function handleGetProfile()
{
    $db = getDB();
    $userId = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT id, name, email, phone, address, city, province, zip_code, role, status, email_verified, mfa_enabled, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    // Get order stats
    $orderStmt = $db->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?");
    $orderStmt->execute([$userId]);
    $stats = $orderStmt->fetch();

    $user['order_count'] = $stats['order_count'];
    $user['total_spent'] = $stats['total_spent'];

    // Decrypt sensitive fields for display
    if (!empty($user['phone']))   $user['phone']   = decryptData($user['phone']);
    if (!empty($user['address'])) $user['address']  = decryptData($user['address']);

    jsonResponse(['success' => true, 'user' => $user]);
}

// ===================================
// UPDATE PROFILE
// ===================================
function handleUpdateProfile()
{
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? 'update-info';

    switch ($action) {
        case 'update-info':
            return updatePersonalInfo($db, $userId, $input);
        case 'update-address':
            return updateAddress($db, $userId, $input);
        case 'change-password':
            return changePassword($db, $userId, $input);
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function updatePersonalInfo($db, $userId, $input)
{
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
    }

    // Validate phone
    if (!empty($phone)) {
        $phoneDigits = preg_replace('/\D/', '', $phone);
        if (strlen($phoneDigits) !== 11) {
            jsonResponse(['success' => false, 'message' => 'Phone number must be exactly 11 digits'], 400);
        }
    }

    $encryptedPhone = !empty($phone) ? encryptData($phone) : '';
    $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $encryptedPhone, $userId]);

    // Update session name
    $_SESSION['user_name'] = $name;

    logAudit('update', 'user', $userId, 'Updated personal information');

    jsonResponse(['success' => true, 'message' => 'Personal information updated successfully']);
}

function updateAddress($db, $userId, $input)
{
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $province = trim($input['province'] ?? '');
    $zipCode = trim($input['zip_code'] ?? '');

    $encAddress = !empty($address) ? encryptData($address) : '';
    $stmt = $db->prepare("UPDATE users SET address = ?, city = ?, province = ?, zip_code = ? WHERE id = ?");
    $stmt->execute([$encAddress, $city, $province, $zipCode, $userId]);

    logAudit('update', 'user', $userId, 'Updated address information');

    jsonResponse(['success' => true, 'message' => 'Address updated successfully']);
}

function changePassword($db, $userId, $input)
{
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        jsonResponse(['success' => false, 'message' => 'All password fields are required'], 400);
    }

    if ($newPassword !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => 'New passwords do not match'], 400);
    }

    // Verify current password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 400);
    }

    // Validate new password policy
    $policyErrors = validatePasswordPolicy($newPassword);
    if (!empty($policyErrors)) {
        jsonResponse(['success' => false, 'message' => implode('. ', $policyErrors)], 400);
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    logAudit('change_password', 'user', $userId, 'User changed their password');

    jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
}
