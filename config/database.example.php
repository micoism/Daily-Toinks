<?php
// ===================================
// DATABASE CONNECTION - EXAMPLE
// ===================================
// Copy this file to database.php and update with your actual credentials.
// DO NOT commit database.php to version control!

// Database server hostname (usually 'localhost' for XAMPP)
define('DB_HOST', 'localhost');

// Name of your database
define('DB_NAME', 'dailytoinks_db');

// Database username (default for XAMPP is 'root')
define('DB_USER', 'root');

// Database password (default for XAMPP is empty string '')
define('DB_PASS', '');

/**
 * Get PDO database connection
 * Uses singleton pattern to reuse connection
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
