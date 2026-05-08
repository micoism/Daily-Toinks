<?php
// ===================================
// AUDIT LOGS API ENDPOINT
// Admin-only access to view system audit logs
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

requireApiRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$db = getDB();

// Filters
$action = $_GET['action'] ?? '';
$entityType = $_GET['entity_type'] ?? '';
$search = $_GET['search'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = min(100, max(10, intval($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($action) {
    $where[] = 'action = ?';
    $params[] = $action;
}

if ($entityType) {
    $where[] = 'entity_type = ?';
    $params[] = $entityType;
}

if ($search) {
    $where[] = '(user_name LIKE ? OR user_email LIKE ? OR details LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($from) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $from;
}

if ($to) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $to;
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$whereClause}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Fetch logs
$stmt = $db->prepare(
    "SELECT * FROM audit_logs WHERE {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'logs' => $logs,
    'total' => (int)$total,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => ceil($total / $perPage)
]);
