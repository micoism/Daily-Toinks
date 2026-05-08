<?php
// ===================================
// USERS API ENDPOINT
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
        handleGet();
        break;
    case 'PUT':
        requireApiRole(['admin', 'manager']);
        handleUpdate();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    requireApiRole(['admin', 'manager']);

    $db = getDB();

    // === LOCK HISTORY (admin only) ===
    if (isset($_GET['lock_history'])) {
        requireApiRole(['admin']);
        handleLockHistory($db);
        return;
    }

    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("SELECT id, name, email, phone, role, status, email_verified, mfa_enabled, failed_logins, locked_until, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        // Get user's order count
        $orderStmt = $db->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?");
        $orderStmt->execute([$id]);
        $stats = $orderStmt->fetch();
        $user['order_count'] = $stats['order_count'];
        $user['total_spent'] = $stats['total_spent'];

        if (!empty($user['phone'])) $user['phone'] = decryptData($user['phone']);

        jsonResponse(['success' => true, 'user' => $user]);
    }

    // List users
    $role = $_GET['role'] ?? null;
    $search = $_GET['search'] ?? null;

    $where = ["1=1"];
    $params = [];

    if ($role) {
        $where[] = "role = ?";
        $params[] = $role;
    }

    if ($search) {
        $where[] = "(name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(' AND ', $where);
    $stmt = $db->prepare("SELECT id, name, email, phone, role, status, email_verified, mfa_enabled, failed_logins, locked_until, created_at FROM users WHERE $whereClause ORDER BY created_at DESC");
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    foreach ($users as &$u) {
        if (!empty($u['phone'])) $u['phone'] = decryptData($u['phone']);
    }
    unset($u);

    jsonResponse(['success' => true, 'users' => $users]);
}

function handleUpdate()
{
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'User ID required'], 400);
    }

    // Handle unlock action
    if (isset($input['action']) && $input['action'] === 'unlock') {
        requireApiRole(['admin']);
        $adminId = $_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$id]);

        // Mark the most recent open lockout record as unlocked
        $stmt = $db->prepare("UPDATE locked_accounts SET unlocked_at = NOW(), unlocked_by = ? WHERE user_id = ? AND unlocked_at IS NULL ORDER BY locked_at DESC LIMIT 1");
        $stmt->execute([$adminId, $id]);

        logAudit('unlock_account', 'user', $id, 'Admin unlocked user account');
        jsonResponse(['success' => true, 'message' => 'Account unlocked successfully']);
    }

    // Password reset by admin is DISABLED — staff must change their own passwords
    // via the "Change My Password" option in the admin topbar.
    if (isset($input['action']) && $input['action'] === 'reset-password') {
        jsonResponse([
            'success' => false,
            'message' => 'Admins cannot reset other accounts\' passwords. Each staff member must change their own password via the topbar.'
        ], 403);
    }

    // Prevent admin from changing their own role
    if ($id == $_SESSION['user_id'] && isset($input['role'])) {
        jsonResponse(['success' => false, 'message' => "You cannot change your own role"], 400);
    }

    $fields = [];
    $params = [];

    $allowed = ['name', 'email', 'phone', 'role', 'status'];
    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($fields)) {
        jsonResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $params[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    logAudit('update', 'user', $id, 'User updated: ' . implode(', ', array_keys(array_intersect_key($input, array_flip($allowed)))));

    jsonResponse(['success' => true, 'message' => 'User updated successfully']);
}

function handleLockHistory($db)
{
    $search = $_GET['search'] ?? null;
    $statusFilter = $_GET['lock_status'] ?? null;

    $where = ["1=1"];
    $params = [];

    if ($search) {
        $where[] = "la.email LIKE ?";
        $params[] = "%$search%";
    }

    if ($statusFilter === 'active') {
        $where[] = "la.unlocked_at IS NULL";
    } elseif ($statusFilter === 'unlocked') {
        $where[] = "la.unlocked_at IS NOT NULL";
    }

    $whereClause = implode(' AND ', $where);

    // Lock history with user names and unlock attribution
    $sql = "
        SELECT la.id, la.user_id, la.email, la.failed_attempts,
               la.locked_at, la.unlocked_at, la.ip_address,
               u.name AS user_name,
               admin.name AS unlocked_by_name,
               (SELECT COUNT(*) FROM locked_accounts la2 WHERE la2.email = la.email AND la2.id <= la.id) AS lock_count
        FROM locked_accounts la
        LEFT JOIN users u ON la.user_id = u.id
        LEFT JOIN users admin ON la.unlocked_by = admin.id
        WHERE $whereClause
        ORDER BY la.locked_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $locks = $stmt->fetchAll();

    // Stats
    $statStmt = $db->query("
        SELECT
            (SELECT COUNT(DISTINCT user_id) FROM locked_accounts WHERE unlocked_at IS NULL AND user_id IS NOT NULL) AS active,
            (SELECT COUNT(*) FROM locked_accounts) AS total,
            (SELECT COUNT(DISTINCT email) FROM locked_accounts) AS unique_count
    ");
    $stats = $statStmt->fetch();

    jsonResponse([
        'success' => true,
        'locks' => $locks,
        'stats' => [
            'active' => (int)$stats['active'],
            'total' => (int)$stats['total'],
            'unique' => (int)$stats['unique_count']
        ]
    ]);
}
