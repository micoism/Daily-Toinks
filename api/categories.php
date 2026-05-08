<?php
// ===================================
// CATEGORIES API ENDPOINT
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$db = getDB();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    if (!$cat) {
        jsonResponse(['success' => false, 'message' => 'Category not found'], 404);
    }
    jsonResponse(['success' => true, 'category' => $cat]);
} else {
    $stmt = $db->query("SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' GROUP BY c.id ORDER BY c.name ASC");
    $categories = $stmt->fetchAll();
    jsonResponse(['success' => true, 'categories' => $categories]);
}
