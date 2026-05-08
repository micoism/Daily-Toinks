<?php
// ===================================
// PRODUCT IMAGES API ENDPOINT
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'DELETE'])) {
    requireCsrf();
    requireApiRole(['manager']);
}

switch ($method) {
    case 'POST':
        handleUpload();
        break;
    case 'DELETE':
        handleDeleteImage();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleUpload()
{
    $db = getDB();
    $productId = intval($_POST['product_id'] ?? 0);

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    // Verify product exists
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
    }

    // Get current max sort_order
    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);
    $sortOrder = (int)$stmt->fetchColumn();

    if (!isset($_FILES['images'])) {
        jsonResponse(['success' => false, 'message' => 'No images uploaded'], 400);
    }

    $files = $_FILES['images'];
    $totalFiles = count($files['name']);

    // Limit: max 8 images per upload batch
    if ($totalFiles > 8) {
        jsonResponse(['success' => false, 'message' => 'You can upload a maximum of 8 images at once'], 400);
    }

    // Limit: total images per product (existing + new) capped at 10
    $countStmt = $db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
    $countStmt->execute([$productId]);
    $existingCount = (int) $countStmt->fetchColumn();
    if ($existingCount + $totalFiles > 10) {
        $allowed = max(0, 10 - $existingCount);
        jsonResponse(['success' => false, 'message' => "This product already has {$existingCount} image(s). You can only add {$allowed} more (max 10 per product)."], 400);
    }

    $uploaded = [];
    $rejected = [];

    for ($i = 0; $i < $totalFiles; $i++) {
        $origName = $files['name'][$i];

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $rejected[] = "{$origName}: upload error code {$files['error'][$i]}";
            continue;
        }

        $file = [
            'name' => $origName,
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];

        // 5 MB max, JPG/PNG/GIF/WEBP only (enforced in validateFileUpload)
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
        $imgStmt->execute([$productId, $path, $sortOrder]);

        $uploaded[] = [
            'id' => $db->lastInsertId(),
            'image_path' => $path,
            'sort_order' => $sortOrder,
        ];

        // Set as main product image if it's the first image
        if ($sortOrder === 0) {
            $db->prepare("UPDATE products SET image = ? WHERE id = ? AND (image IS NULL OR image = '')")->execute([$path, $productId]);
        }

        $sortOrder++;
    }

    // If everything was rejected, fail the whole request so the user sees errors
    if (count($uploaded) === 0 && count($rejected) > 0) {
        jsonResponse([
            'success' => false,
            'message' => 'No valid images uploaded. ' . implode(' | ', $rejected)
        ], 400);
    }

    $msg = count($uploaded) . ' image(s) uploaded';
    if (count($rejected) > 0) {
        $msg .= '. Skipped: ' . implode(' | ', $rejected);
    }

    jsonResponse(['success' => true, 'message' => $msg, 'images' => $uploaded, 'rejected' => $rejected]);
}

function handleDeleteImage()
{
    $db = getDB();
    $imageId = intval($_GET['id'] ?? 0);

    if ($imageId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Image ID required'], 400);
    }

    // Get image info
    $stmt = $db->prepare("SELECT * FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();

    if (!$image) {
        jsonResponse(['success' => false, 'message' => 'Image not found'], 404);
    }

    // Delete file from disk
    $filePath = __DIR__ . '/..' . $image['image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from DB
    $db->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imageId]);

    // If this was the main product image, set next available as main
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$image['product_id']]);
    $product = $stmt->fetch();

    if ($product && $product['image'] === $image['image_path']) {
        $nextImg = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
        $nextImg->execute([$image['product_id']]);
        $next = $nextImg->fetchColumn();
        $db->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$next ?: '', $image['product_id']]);
    }

    jsonResponse(['success' => true, 'message' => 'Image deleted']);
}
