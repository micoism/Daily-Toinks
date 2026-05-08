<?php
// ===================================
// CART API ENDPOINT
// Server-side cart for logged-in users
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// CSRF validation for state-changing requests
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireCsrf();
}

// Staff cannot use cart
if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'manager', 'rider']) && $method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Staff accounts cannot add items to cart'], 403);
}

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handleAdd();
        break;
    case 'PUT':
        handleUpdate();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    requireApiAuth();
    if (isStaffUser()) {
        jsonResponse(['success' => false, 'message' => 'Staff accounts cannot use cart'], 403);
    }
    $db = getDB();
    $userId = $_SESSION['user_id'];

    $stmt = $db->prepare("
        SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND p.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $total = array_reduce($items, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);

    jsonResponse(['success' => true, 'items' => $items, 'total' => $total]);
}

function handleAdd()
{
    requireApiAuth();
    if (isStaffUser()) {
        jsonResponse(['success' => false, 'message' => 'Staff accounts cannot add to cart'], 403);
    }
    $db = getDB();
    $userId = $_SESSION['user_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input)
        $input = $_POST;

    $productId = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);

    if ($productId <= 0 || $quantity <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
    }

    // Check product exists
    $stmt = $db->prepare("SELECT id, stock FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
    }

    // Upsert cart item
    $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    $stmt->execute([$userId, $productId, $quantity]);

    jsonResponse(['success' => true, 'message' => 'Added to cart']);
}

function handleUpdate()
{
    requireApiAuth();
    if (isStaffUser()) {
        jsonResponse(['success' => false, 'message' => 'Staff accounts cannot update cart'], 403);
    }
    $db = getDB();
    $userId = $_SESSION['user_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    $productId = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 0);

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    if ($quantity <= 0) {
        // Remove item
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        jsonResponse(['success' => true, 'message' => 'Item removed from cart']);
    }

    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $userId, $productId]);

    jsonResponse(['success' => true, 'message' => 'Cart updated']);
}

function handleDelete()
{
    requireApiAuth();
    if (isStaffUser()) {
        jsonResponse(['success' => false, 'message' => 'Staff accounts cannot modify cart'], 403);
    }
    $db = getDB();
    $userId = $_SESSION['user_id'];

    $productId = $_GET['product_id'] ?? null;

    if ($productId) {
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, intval($productId)]);
        jsonResponse(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        // Clear all cart
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        jsonResponse(['success' => true, 'message' => 'Cart cleared']);
    }
}
