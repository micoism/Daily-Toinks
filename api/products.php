<?php
// ===================================
// PRODUCTS API ENDPOINT
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
        requireApiRole(['manager']);
        handleCreate();
        break;
    case 'PUT':
        requireApiRole(['manager']);
        handleUpdate();
        break;
    case 'DELETE':
        requireApiRole(['manager']);
        handleDelete();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleGet()
{
    $db = getDB();
    $id = $_GET['id'] ?? null;
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;
    $limit = intval($_GET['limit'] ?? 0);
    $offset = intval($_GET['offset'] ?? 0);

    // Single product
    if ($id) {
        $stmt = $db->prepare("
            SELECT p.*, c.name AS category_name, c.icon AS category_icon
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }
        // Attach images
        $imgStmt = $db->prepare("SELECT id, image_path, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute([$id]);
        $product['images'] = $imgStmt->fetchAll();
        jsonResponse(['success' => true, 'product' => $product]);
    }

    // Build query
    $where = ["p.status = 'active'"];
    $params = [];

    if ($category && $category !== 'all') {
        $where[] = "c.name = ?";
        $params[] = $category;
    }

    if ($search) {
        $where[] = "(p.name LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(' AND ', $where);

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Fetch products
    $sql = "SELECT p.*, c.name AS category_name, c.icon AS category_icon
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
            ORDER BY p.id ASC";

    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Attach images for each product
    if (!empty($products)) {
        $ids = array_column($products, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $imgStmt = $db->prepare("SELECT id, product_id, image_path, sort_order FROM product_images WHERE product_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute($ids);
        $allImages = $imgStmt->fetchAll();
        $imageMap = [];
        foreach ($allImages as $img) {
            $imageMap[$img['product_id']][] = $img;
        }
        foreach ($products as &$p) {
            $p['images'] = $imageMap[$p['id']] ?? [];
        }
        unset($p);
    }

    jsonResponse(['success' => true, 'products' => $products, 'total' => $total]);
}

function handleCreate()
{
    $db = getDB();

    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rating = floatval($_POST['rating'] ?? 0);

    if (empty($name) || $price <= 0 || $categoryId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Name, price, and category are required'], 400);
    }

    // Handle single image_file for backward compat (sets main product image)
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $errors = validateFileUpload($_FILES['image_file'], 5);
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
        }
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $filename = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = __DIR__ . '/../uploads/products/' . $filename;
        move_uploaded_file($_FILES['image_file']['tmp_name'], $dest);
        $image = '/normss/uploads/products/' . $filename;
    }

    $stmt = $db->prepare("INSERT INTO products (name, price, category_id, rating, stock, image, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $categoryId, $rating, $stock, $image, $description]);
    $newId = $db->lastInsertId();

    // Save main image to product_images too
    if (!empty($image)) {
        $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, 0)");
        $imgStmt->execute([$newId, $image]);
    }

    // Handle multiple image files (image_files[])
    if (isset($_FILES['image_files'])) {
        $files = $_FILES['image_files'];
        $totalFiles = count($files['name']);

        // Cap: max 8 images per submission and 10 total per product
        if ($totalFiles > 8) {
            jsonResponse(['success' => false, 'message' => 'You can upload a maximum of 8 images at once'], 400);
        }

        $sortOrder = 1;
        $rejected = [];
        for ($i = 0; $i < $totalFiles; $i++) {
            $origName = $files['name'][$i];
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $rejected[] = "{$origName}: upload error";
                continue;
            }
            $file = [
                'name' => $origName,
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $errors = validateFileUpload($file, 5);
            if (!empty($errors)) {
                $rejected[] = $origName . ': ' . implode(', ', $errors);
                continue;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/products/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $rejected[] = "{$origName}: failed to save file";
                continue;
            }
            $path = '/normss/uploads/products/' . $filename;
            $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
            $imgStmt->execute([$newId, $path, $sortOrder++]);
            // Set first uploaded as main image if none set
            if (empty($image) && $sortOrder === 2) {
                $db->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$path, $newId]);
            }
        }

        // If user attempted uploads and ALL failed, surface that as an error response
        if ($totalFiles > 0 && $sortOrder === 1 && !empty($rejected)) {
            // Note: product was already created; we report partial success with details
            jsonResponse([
                'success' => true,
                'message' => 'Product created, but no images were uploaded. Issues: ' . implode(' | ', $rejected),
                'id' => $newId,
                'rejected' => $rejected
            ], 201);
        }
    }

    logAudit('create', 'product', $newId, "Product created: {$name}, Price: {$price}");
    jsonResponse(['success' => true, 'message' => 'Product created', 'id' => $newId], 201);
}

function handleUpdate()
{
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    $fields = [];
    $params = [];

    $allowed = ['name', 'price', 'category_id', 'rating', 'stock', 'image', 'description', 'status'];
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
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    logAudit('update', 'product', $id, 'Product updated: ' . implode(', ', array_keys(array_intersect_key($input, array_flip($allowed)))));

    jsonResponse(['success' => true, 'message' => 'Product updated']);
}

function handleDelete()
{
    $db = getDB();
    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    // Soft delete
    $stmt = $db->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);

    logAudit('delete', 'product', $id, 'Product soft-deleted');

    jsonResponse(['success' => true, 'message' => 'Product deleted']);
}
