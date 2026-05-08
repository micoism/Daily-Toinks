<?php
// ===================================
// REVIEWS API ENDPOINT
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireCsrf();
}

switch ($method) {
    case 'GET':
        handleGetReviews();
        break;
    case 'POST':
        requireApiAuth();
        handleCreateReview();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGetReviews()
{
    $db = getDB();
    $productId = $_GET['product_id'] ?? null;

    if (!$productId) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    $stmt = $db->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll();

    // Get summary
    $summaryStmt = $db->prepare("
        SELECT COUNT(*) as total, AVG(rating) as average,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star1
        FROM reviews WHERE product_id = ?
    ");
    $summaryStmt->execute([$productId]);
    $summary = $summaryStmt->fetch();

    // Check if current user can review (has delivered orders with this product and hasn't reviewed yet)
    $canReview = [];
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $eligibleStmt = $db->prepare("
            SELECT o.id as order_id, o.order_number
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'
            AND NOT EXISTS (
                SELECT 1 FROM reviews rv WHERE rv.user_id = ? AND rv.order_id = o.id AND rv.product_id = ?
            )
        ");
        $eligibleStmt->execute([$userId, $productId, $userId, $productId]);
        $canReview = $eligibleStmt->fetchAll();
    }

    jsonResponse([
        'success' => true,
        'reviews' => $reviews,
        'summary' => $summary,
        'can_review' => $canReview
    ]);
}

function handleCreateReview()
{
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    $productId = $input['product_id'] ?? null;
    $orderId = $input['order_id'] ?? null;
    $rating = intval($input['rating'] ?? 0);
    $comment = trim($input['comment'] ?? '');

    if (!$productId || !$orderId || $rating < 1 || $rating > 5) {
        jsonResponse(['success' => false, 'message' => 'Product, order, and rating (1-5) are required'], 400);
    }

    // Verify the user actually ordered this product and it's delivered
    $verifyStmt = $db->prepare("
        SELECT 1 FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'
    ");
    $verifyStmt->execute([$orderId, $userId, $productId]);
    if (!$verifyStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'You can only review delivered orders'], 403);
    }

    // Check for duplicate
    $dupStmt = $db->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND order_id = ? AND product_id = ?");
    $dupStmt->execute([$userId, $orderId, $productId]);
    if ($dupStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'You already reviewed this product for this order'], 400);
    }

    $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $productId, $orderId, $rating, $comment ?: null]);

    // Update product average rating
    $avgStmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
    $avgStmt->execute([$productId]);
    $avg = $avgStmt->fetch();
    $db->prepare("UPDATE products SET rating = ? WHERE id = ?")->execute([round($avg['avg_rating'], 1), $productId]);

    logAudit('create', 'review', $db->lastInsertId(), "Reviewed product #$productId with $rating stars");

    jsonResponse(['success' => true, 'message' => 'Review submitted successfully!']);
}
