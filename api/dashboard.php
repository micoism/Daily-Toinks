<?php
// ===================================
// DASHBOARD API ENDPOINT
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

requireApiRole(['admin', 'manager', 'rider']);

$db = getDB();

// Total users
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// Total orders and revenue
$orderStats = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue FROM orders WHERE status != 'Cancelled'")->fetch();

// Total products
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();

// Orders by status
$statusStmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status ORDER BY FIELD(status, 'Order Placed', 'Payment Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled')");
$ordersByStatus = $statusStmt->fetchAll();

// Recent orders (last 10)
$recentOrders = $db->query("SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

// Top selling products
$topProducts = $db->query("SELECT oi.product_name, oi.image, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as total_revenue FROM order_items oi GROUP BY oi.product_name, oi.image ORDER BY total_sold DESC LIMIT 5")->fetchAll();

// Revenue by day (last 7 days)
$revenueByDay = $db->query("SELECT DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders FROM orders WHERE status != 'Cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC")->fetchAll();

// New users this month
$newUsersMonth = $db->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

// Pending orders count
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Order Placed'")->fetchColumn();

// Rider-specific stats
$riderStats = null;
$user = getCurrentUser();
if ($user['role'] === 'rider') {
    $riderId = $user['id'];
    $activeDeliveries = $db->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status IN ('Shipped','Out for Delivery')");
    $activeDeliveries->execute([$riderId]);
    $completedDeliveries = $db->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status = 'Delivered'");
    $completedDeliveries->execute([$riderId]);
    $returnedDeliveries = $db->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status = 'Returned'");
    $returnedDeliveries->execute([$riderId]);
    $ratingsGiven = $db->prepare("SELECT COUNT(*) FROM customer_ratings WHERE rider_id = ?");
    $ratingsGiven->execute([$riderId]);
    $availableOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Packed' AND rider_id IS NULL")->fetchColumn();

    $riderStats = [
        'active_deliveries' => intval($activeDeliveries->fetchColumn()),
        'completed_deliveries' => intval($completedDeliveries->fetchColumn()),
        'returned_deliveries' => intval($returnedDeliveries->fetchColumn()),
        'ratings_given' => intval($ratingsGiven->fetchColumn()),
        'available_orders' => intval($availableOrders)
    ];
}

$response = [
    'success' => true,
    'stats' => [
        'total_users' => intval($totalUsers),
        'total_customers' => intval($totalCustomers),
        'total_orders' => intval($orderStats['total_orders']),
        'total_revenue' => floatval($orderStats['total_revenue']),
        'total_products' => intval($totalProducts),
        'new_users_month' => intval($newUsersMonth),
        'pending_orders' => intval($pendingOrders)
    ],
    'orders_by_status' => $ordersByStatus,
    'recent_orders' => $recentOrders,
    'top_products' => $topProducts,
    'revenue_by_day' => $revenueByDay
];

if ($riderStats) {
    $response['rider_stats'] = $riderStats;
}

jsonResponse($response);

