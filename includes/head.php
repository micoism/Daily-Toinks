<?php
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/security.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
<title>
    <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - DailyToinks' : 'DailyToinks - Your Trusted Electronics Store'; ?>
</title>
<meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Shop the best deals on smartphones, laptops, desktops, and tech accessories at DailyToinks. Free shipping on orders over ₱2,500!'; ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/normss/css/styles.css">