<?php
// ===================================
// SUPPORT TICKETS API ENDPOINT
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireCsrf();
}

switch ($method) {
    case 'GET':
        requireApiAuth();
        handleGetTickets();
        break;
    case 'POST':
        requireApiAuth();
        handleCreateOrReply();
        break;
    case 'PUT':
        requireApiAuth();
        handleUpdateTicket();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function generateTicketNumber()
{
    return 'TKT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function handleGetTickets()
{
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $ticketId = $_GET['id'] ?? null;

    // Single ticket with replies
    if ($ticketId) {
        $stmt = $db->prepare("
            SELECT t.*, u.name as user_name, u.email as user_email,
                   o.order_number, p.name as product_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN orders o ON t.order_id = o.id
            LEFT JOIN products p ON t.product_id = p.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        // Customers can only see their own tickets
        if ($role === 'customer' && $ticket['user_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get replies
        $repliesStmt = $db->prepare("
            SELECT tr.*, u.name as user_name, u.role as user_role
            FROM ticket_replies tr
            JOIN users u ON tr.user_id = u.id
            WHERE tr.ticket_id = ?
            ORDER BY tr.created_at ASC
        ");
        $repliesStmt->execute([$ticketId]);
        $ticket['replies'] = $repliesStmt->fetchAll();

        jsonResponse(['success' => true, 'ticket' => $ticket]);
    }

    // List tickets
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    $where = [];
    $params = [];

    // Customers see only their own; managers/admins see all
    if ($role === 'customer') {
        $where[] = "t.user_id = ?";
        $params[] = $userId;
    }

    if ($status) {
        $where[] = "t.status = ?";
        $params[] = $status;
    }

    if ($search) {
        $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ? OR u.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT t.*, u.name as user_name, o.order_number, p.name as product_name
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN orders o ON t.order_id = o.id
        LEFT JOIN products p ON t.product_id = p.id
        $whereClause
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // Get reply counts
    foreach ($tickets as &$t) {
        $countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM ticket_replies WHERE ticket_id = ?");
        $countStmt->execute([$t['id']]);
        $t['reply_count'] = $countStmt->fetch()['cnt'];
    }
    unset($t);

    jsonResponse(['success' => true, 'tickets' => $tickets]);
}

function handleCreateOrReply()
{
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    if ($action === 'reply') {
        return handleReply($input);
    }

    $db = getDB();
    $userId = $_SESSION['user_id'];

    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message'] ?? '');
    $orderId = $input['order_id'] ?? null;
    $productId = $input['product_id'] ?? null;

    if (empty($subject) || empty($message)) {
        jsonResponse(['success' => false, 'message' => 'Subject and message are required'], 400);
    }

    // Generate unique ticket number
    $ticketNumber = generateTicketNumber();
    $checkStmt = $db->prepare("SELECT 1 FROM tickets WHERE ticket_number = ?");
    $checkStmt->execute([$ticketNumber]);
    while ($checkStmt->fetch()) {
        $ticketNumber = generateTicketNumber();
        $checkStmt->execute([$ticketNumber]);
    }

    $stmt = $db->prepare("
        INSERT INTO tickets (ticket_number, user_id, order_id, product_id, subject, message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$ticketNumber, $userId, $orderId ?: null, $productId ?: null, $subject, $message]);

    logAudit('create', 'ticket', $db->lastInsertId(), "Created support ticket $ticketNumber");

    jsonResponse([
        'success' => true,
        'message' => "Ticket $ticketNumber created successfully!",
        'ticket_number' => $ticketNumber
    ]);
}

function handleReply($input)
{
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];

    $ticketId = $input['ticket_id'] ?? null;
    $message = trim($input['message'] ?? '');

    if (!$ticketId || empty($message)) {
        jsonResponse(['success' => false, 'message' => 'Ticket ID and message are required'], 400);
    }

    // Verify access
    $ticketStmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
    $ticketStmt->execute([$ticketId]);
    $ticket = $ticketStmt->fetch();

    if (!$ticket) {
        jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    if ($role === 'customer' && $ticket['user_id'] != $userId) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    if ($ticket['status'] === 'closed') {
        jsonResponse(['success' => false, 'message' => 'Cannot reply to a closed ticket'], 400);
    }

    $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$ticketId, $userId, $message]);

    // If staff replies, set status to in_progress
    if (in_array($role, ['admin', 'manager'])) {
        $db->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ? AND status = 'open'")->execute([$ticketId]);
    }

    // Touch updated_at
    $db->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticketId]);

    logAudit('reply', 'ticket', $ticketId, "Replied to ticket #{$ticket['ticket_number']}");

    jsonResponse(['success' => true, 'message' => 'Reply sent successfully']);
}

function handleUpdateTicket()
{
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $input = json_decode(file_get_contents('php://input'), true);

    $ticketId = $input['id'] ?? null;
    $newStatus = $input['status'] ?? null;

    if (!$ticketId || !$newStatus) {
        jsonResponse(['success' => false, 'message' => 'Ticket ID and status required'], 400);
    }

    // Only staff can update ticket status (except customers closing their own)
    $ticketStmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
    $ticketStmt->execute([$ticketId]);
    $ticket = $ticketStmt->fetch();

    if (!$ticket) {
        jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    if ($role === 'customer') {
        if ($ticket['user_id'] != $userId || $newStatus !== 'closed') {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
    } elseif (!in_array($role, ['admin', 'manager'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $db->prepare("UPDATE tickets SET status = ? WHERE id = ?")->execute([$newStatus, $ticketId]);

    logAudit('update', 'ticket', $ticketId, "Updated ticket status to $newStatus");

    jsonResponse(['success' => true, 'message' => 'Ticket status updated']);
}
