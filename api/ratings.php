<?php
// ===================================
// CUSTOMER RATINGS API ENDPOINT
// Riders rate customers after delivery
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
        handleGet();
        break;
    case 'POST':
        handleCreate();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    requireApiAuth();
    $db = getDB();
    $user = getCurrentUser();

    // Get ratings for a specific customer
    $customerId = $_GET['customer_id'] ?? null;
    $orderId = $_GET['order_id'] ?? null;

    if ($orderId) {
        // Check rating for a specific order
        $stmt = $db->prepare("SELECT cr.*, u.name AS customer_name, r.name AS rider_name FROM customer_ratings cr LEFT JOIN users u ON cr.customer_id = u.id LEFT JOIN users r ON cr.rider_id = r.id WHERE cr.order_id = ?");
        $stmt->execute([$orderId]);
        $rating = $stmt->fetch();
        jsonResponse(['success' => true, 'rating' => $rating ?: null]);
    }

    if ($customerId) {
        // Get all ratings for a customer (admin/manager/rider can view)
        if (!in_array($user['role'], ['admin', 'manager', 'rider'])) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        $stmt = $db->prepare("SELECT cr.*, o.order_number, r.name AS rider_name FROM customer_ratings cr LEFT JOIN orders o ON cr.order_id = o.id LEFT JOIN users r ON cr.rider_id = r.id WHERE cr.customer_id = ? ORDER BY cr.created_at DESC");
        $stmt->execute([$customerId]);
        $ratings = $stmt->fetchAll();

        // Calculate average
        $avgStmt = $db->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_ratings FROM customer_ratings WHERE customer_id = ?");
        $avgStmt->execute([$customerId]);
        $stats = $avgStmt->fetch();

        jsonResponse([
            'success' => true,
            'ratings' => $ratings,
            'avg_rating' => round(floatval($stats['avg_rating']), 1),
            'total_ratings' => intval($stats['total_ratings'])
        ]);
    }

    // Rider: my submitted ratings
    if ($user['role'] === 'rider') {
        $stmt = $db->prepare("SELECT cr.*, o.order_number, u.name AS customer_name FROM customer_ratings cr LEFT JOIN orders o ON cr.order_id = o.id LEFT JOIN users u ON cr.customer_id = u.id WHERE cr.rider_id = ? ORDER BY cr.created_at DESC");
        $stmt->execute([$user['id']]);
        $ratings = $stmt->fetchAll();
        jsonResponse(['success' => true, 'ratings' => $ratings]);
    }

    // Admin/Manager: all ratings
    if (in_array($user['role'], ['admin', 'manager'])) {
        $stmt = $db->query("SELECT cr.*, o.order_number, u.name AS customer_name, r.name AS rider_name FROM customer_ratings cr LEFT JOIN orders o ON cr.order_id = o.id LEFT JOIN users u ON cr.customer_id = u.id LEFT JOIN users r ON cr.rider_id = r.id ORDER BY cr.created_at DESC LIMIT 100");
        $ratings = $stmt->fetchAll();
        jsonResponse(['success' => true, 'ratings' => $ratings]);
    }

    jsonResponse(['success' => false, 'message' => 'No query parameters provided'], 400);
}

function handleCreate()
{
    requireApiRole(['rider']);
    $db = getDB();
    $user = getCurrentUser();

    $input = json_decode(file_get_contents('php://input'), true);

    $orderId = intval($input['order_id'] ?? 0);
    $rating = intval($input['rating'] ?? 0);
    $comment = trim($input['comment'] ?? '');

    if (!$orderId || $rating < 1 || $rating > 5) {
        jsonResponse(['success' => false, 'message' => 'Valid order_id and rating (1-5) required'], 400);
    }

    // Verify the order exists, belongs to this rider, and is delivered
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    if ($order['rider_id'] != $user['id']) {
        jsonResponse(['success' => false, 'message' => 'You can only rate customers for your own deliveries'], 403);
    }

    if ($order['status'] !== 'Delivered') {
        jsonResponse(['success' => false, 'message' => 'Can only rate customers on delivered orders'], 400);
    }

    if (!$order['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Cannot rate guest customers'], 400);
    }

    // Check if already rated
    $check = $db->prepare("SELECT id FROM customer_ratings WHERE order_id = ?");
    $check->execute([$orderId]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'You have already rated this customer for this order'], 400);
    }

    // Insert rating
    $stmt = $db->prepare("INSERT INTO customer_ratings (order_id, rider_id, customer_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$orderId, $user['id'], $order['user_id'], $rating, $comment]);

    jsonResponse(['success' => true, 'message' => 'Customer rated successfully']);
}
