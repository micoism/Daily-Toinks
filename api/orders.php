<?php
// ===================================
// ORDERS API ENDPOINT
// Updated: Staff → Rider, Manager gets order management
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

// Decrypt sensitive shipping fields for display
function decryptOrderShipping(&$order) {
    if (!empty($order['shipping_fullname'])) $order['shipping_fullname'] = decryptData($order['shipping_fullname']);
    if (!empty($order['shipping_phone']))    $order['shipping_phone']    = decryptData($order['shipping_phone']);
    if (!empty($order['shipping_address']))  $order['shipping_address']  = decryptData($order['shipping_address']);
}

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
    case 'PUT':
        handleUpdate();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    $db = getDB();
    $id = $_GET['id'] ?? null;
    $orderNumber = $_GET['order_number'] ?? null;

    // Single order by order_number (public tracking)
    if ($orderNumber) {
        $stmt = $db->prepare("SELECT o.*, r.name AS rider_name FROM orders o LEFT JOIN users r ON o.rider_id = r.id WHERE o.order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Check access: admin/manager can view any, rider can view assigned, customer only own
        if (isLoggedIn()) {
            $user = getCurrentUser();
            if ($user['role'] === 'customer' && $order['user_id'] != $user['id']) {
                jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
        }

        // Get order items
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();

        // Get status history
        $stmt = $db->prepare("SELECT h.*, u.name AS changed_by_name FROM order_status_history h LEFT JOIN users u ON h.changed_by = u.id WHERE h.order_id = ? ORDER BY h.created_at ASC");
        $stmt->execute([$order['id']]);
        $order['status_history'] = $stmt->fetchAll();

        decryptOrderShipping($order);
        jsonResponse(['success' => true, 'order' => $order]);
    }

    // Single order by ID (admin/manager/rider use)
    if ($id) {
        requireApiRole(['admin', 'manager', 'rider']);
        $stmt = $db->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email, r.name AS rider_name FROM orders o LEFT JOIN users u ON o.user_id = u.id LEFT JOIN users r ON o.rider_id = r.id WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Rider can only view their own claimed orders
        $user = getCurrentUser();
        if ($user['role'] === 'rider' && $order['rider_id'] != $user['id']) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT h.*, u.name AS changed_by_name FROM order_status_history h LEFT JOIN users u ON h.changed_by = u.id WHERE h.order_id = ? ORDER BY h.created_at ASC");
        $stmt->execute([$order['id']]);
        $order['status_history'] = $stmt->fetchAll();

        // Check if rider has rated the customer
        if ($order['status'] === 'Delivered' && $order['rider_id']) {
            $rStmt = $db->prepare("SELECT * FROM customer_ratings WHERE order_id = ?");
            $rStmt->execute([$order['id']]);
            $order['customer_rating'] = $rStmt->fetch() ?: null;
        }

        decryptOrderShipping($order);
        jsonResponse(['success' => true, 'order' => $order]);
    }

    // --- List modes ---

    // Rider: available orders to claim (status=Packed, no rider assigned)
    $available = $_GET['available'] ?? null;
    if ($available) {
        requireApiRole(['rider']);
        $stmt = $db->prepare("SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.status = 'Packed' AND o.rider_id IS NULL ORDER BY o.created_at ASC");
        $stmt->execute();
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $order['items'] = $itemStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'orders' => $orders]);
    }

    // Rider: my deliveries
    $myDeliveries = $_GET['my_deliveries'] ?? null;
    if ($myDeliveries) {
        requireApiRole(['rider']);
        $user = getCurrentUser();
        $status = $_GET['status'] ?? null;

        $sql = "SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.rider_id = ?";
        $params = [$user['id']];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $order['items'] = $itemStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'orders' => $orders]);
    }

    // Customer: own orders
    $userOnly = $_GET['user_only'] ?? null;
    if ($userOnly) {
        requireApiAuth();
        $user = getCurrentUser();
        $stmt = $db->prepare("SELECT o.*, r.name AS rider_name FROM orders o LEFT JOIN users r ON o.rider_id = r.id WHERE o.user_id = ? ORDER BY o.created_at DESC");
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $order['items'] = $itemStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'orders' => $orders]);
    }

    // Admin/Manager: all orders
    requireApiRole(['admin', 'manager']);
    $status = $_GET['status'] ?? null;

    $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email, r.name AS rider_name FROM orders o LEFT JOIN users u ON o.user_id = u.id LEFT JOIN users r ON o.rider_id = r.id";
    $params = [];

    if ($status) {
        $sql .= " WHERE o.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY o.created_at DESC";

    $limit = intval($_GET['limit'] ?? 0);
    if ($limit > 0) {
        $sql .= " LIMIT $limit";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }

    jsonResponse(['success' => true, 'orders' => $orders]);
}

function handleCreate()
{
    // Staff cannot place orders
    if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'manager', 'rider'])) {
        jsonResponse(['success' => false, 'message' => 'Staff accounts cannot place orders'], 403);
    }

    $db = getDB();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $items = $input['items'] ?? [];
    $total = floatval($input['total'] ?? 0);
    $paymentMethod = trim($input['payment_method'] ?? '');
    $shipping = $input['shipping'] ?? [];

    if (empty($items) || $total <= 0 || empty($paymentMethod) || empty($shipping)) {
        jsonResponse(['success' => false, 'message' => 'Missing required order data'], 400);
    }

    // Generate order number
    $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8)) . rand(10, 99);

    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

    try {
        $db->beginTransaction();

        // Create order
        // Encrypt sensitive shipping data
        $encFullname = encryptData($shipping['fullname'] ?? '');
        $encPhone = encryptData($shipping['phone'] ?? '');
        $encAddress = encryptData($shipping['address'] ?? '');

        $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, total, payment_method, status, shipping_fullname, shipping_phone, shipping_address, shipping_city, shipping_postal) VALUES (?, ?, ?, ?, 'Order Placed', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $orderNumber,
            $userId,
            $total,
            $paymentMethod,
            $encFullname,
            $encPhone,
            $encAddress,
            $shipping['city'] ?? '',
            $shipping['postal'] ?? ''
        ]);

        $orderId = $db->lastInsertId();

        // Create order items
        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $itemStmt->execute([
                $orderId,
                $item['id'] ?? null,
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item['image'] ?? ''
            ]);
        }

        // Add status history
        $histStmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'Order Placed', ?)");
        $histStmt->execute([$orderId, $userId]);

        // Clear server-side cart if logged in
        if ($userId) {
            $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        }

        $db->commit();

        // Audit log: order created
        logAudit('create', 'order', $orderId, "Order {$orderNumber} placed. Total: {$total}, Payment: {$paymentMethod}");

        jsonResponse(['success' => true, 'message' => 'Order placed successfully', 'order_number' => $orderNumber], 201);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
    }
}

function handleUpdate()
{
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? $_GET['id'] ?? null;
    $action = $input['action'] ?? '';

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Order ID required'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    // === CANCEL ===
    if ($action === 'cancel') {
        if (isLoggedIn()) {
            $user = getCurrentUser();
            if ($user['role'] === 'customer' && $order['user_id'] != $user['id']) {
                jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
        }

        $nonCancellable = ['Shipped', 'Out for Delivery', 'Delivered', 'Returned', 'Cancelled'];
        if (in_array($order['status'], $nonCancellable)) {
            jsonResponse(['success' => false, 'message' => 'Order cannot be cancelled at this stage'], 400);
        }

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE orders SET status = 'Cancelled', cancelled_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'Cancelled', ?)");
        $stmt->execute([$id, $userId]);
        $db->commit();

        // Audit log: order cancelled
        logAudit('cancel', 'order', $id, "Order #{$order['order_number']} cancelled");

        jsonResponse(['success' => true, 'message' => 'Order cancelled successfully']);

    // === RIDER CLAIMS ORDER ===
    } elseif ($action === 'claim') {
        requireApiRole(['rider']);
        $user = getCurrentUser();

        if ($order['status'] !== 'Packed') {
            jsonResponse(['success' => false, 'message' => 'Only packed orders can be claimed'], 400);
        }

        if ($order['rider_id']) {
            jsonResponse(['success' => false, 'message' => 'Order already claimed by another rider'], 400);
        }

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE orders SET rider_id = ?, rider_claimed_at = NOW(), status = 'Shipped' WHERE id = ?");
        $stmt->execute([$user['id'], $id]);

        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'Shipped', ?)");
        $stmt->execute([$id, $user['id']]);
        $db->commit();

        // Audit log: order claimed by rider
        logAudit('claim', 'order', $id, "Order #{$order['order_number']} claimed by rider");

        jsonResponse(['success' => true, 'message' => 'Order claimed! Status updated to Shipped.']);

    // === RIDER UPDATES DELIVERY STATUS ===
    } elseif ($action === 'rider_update') {
        requireApiRole(['rider']);
        $user = getCurrentUser();

        if ($order['rider_id'] != $user['id']) {
            jsonResponse(['success' => false, 'message' => 'You can only update your own deliveries'], 403);
        }

        // Rider can only move Shipped → Out for Delivery
        if ($order['status'] !== 'Shipped') {
            jsonResponse(['success' => false, 'message' => 'Can only update shipped orders'], 400);
        }

        $newStatus = 'Out for Delivery';

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
        $stmt->execute([$id, $newStatus, $user['id']]);
        $db->commit();

        logAudit('update', 'order', $id, "Order #{$order['order_number']} delivery status: {$newStatus}");

        jsonResponse(['success' => true, 'message' => "Delivery status updated to: $newStatus"]);

    // === CUSTOMER CONFIRMS DELIVERY ===
    } elseif ($action === 'customer_confirm') {
        requireApiAuth();
        $user = getCurrentUser();

        if ($user['role'] !== 'customer' || $order['user_id'] != $user['id']) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        if ($order['status'] !== 'Out for Delivery') {
            jsonResponse(['success' => false, 'message' => 'Can only confirm orders that are out for delivery'], 400);
        }

        $delivered = $input['delivered'] ?? true;
        $newStatus = $delivered ? 'Delivered' : 'Not Delivered';

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
        $stmt->execute([$id, $newStatus, $user['id']]);
        $db->commit();

        logAudit('update', 'order', $id, "Order #{$order['order_number']} customer confirmed: {$newStatus}");

        jsonResponse(['success' => true, 'message' => "Order marked as: $newStatus"]);

    // === MANAGER NEXT-STEP STATUS UPDATE ===
    } elseif ($action === 'update_status') {
        requireApiRole(['manager']);

        // Manager can only advance to the next step
        $nextStep = [
            'Order Placed' => 'Payment Confirmed',
            'Payment Confirmed' => 'Packed'
        ];

        if (!isset($nextStep[$order['status']])) {
            jsonResponse(['success' => false, 'message' => 'No further action available for this order'], 400);
        }

        $newStatus = $nextStep[$order['status']];

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $userId = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
        $stmt->execute([$id, $newStatus, $userId]);
        $db->commit();

        logAudit('update', 'order', $id, "Order #{$order['order_number']} status changed to: {$newStatus}");

        jsonResponse(['success' => true, 'message' => "Order status updated to: $newStatus"]);

    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
}
