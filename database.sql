-- ===================================
-- DAILYTOINKS DATABASE SCHEMA
-- MySQL Database Setup
-- ===================================

CREATE DATABASE IF NOT EXISTS dailytoinks_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dailytoinks_db;

-- ===================================
-- USERS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'rider', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    -- Address fields (encrypted)
    address VARCHAR(500) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    zip_code VARCHAR(20) DEFAULT NULL,
    -- Email verification
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    email_token VARCHAR(100) DEFAULT NULL,
    email_token_expires TIMESTAMP NULL DEFAULT NULL,
    -- MFA / Two-Factor Authentication
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    mfa_secret VARCHAR(100) DEFAULT NULL,
    -- Account lockout
    failed_logins INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    -- Password tracking
    password_changed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================================
-- CATEGORIES TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================================
-- PRODUCTS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0.0,
    stock INT DEFAULT 0,
    image VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===================================
-- ORDERS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    rider_id INT DEFAULT NULL,
    rider_claimed_at TIMESTAMP NULL DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('Order Placed','Payment Confirmed','Packed','Shipped','Out for Delivery','Delivered','Not Delivered','Returned','Cancelled') NOT NULL DEFAULT 'Order Placed',
    shipping_fullname VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_postal VARCHAR(10) NOT NULL,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- ORDER ITEMS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    image VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- ORDER STATUS HISTORY TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- CART TABLE (for logged-in users)
-- ===================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================
-- PASSWORD RESETS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================================
-- CUSTOMER RATINGS TABLE
-- Riders rate customers after delivery
-- ===================================
CREATE TABLE IF NOT EXISTS customer_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    rider_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;



-- ===================================
-- PRODUCT REVIEWS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_order_product (user_id, order_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================
-- SUPPORT TICKETS TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    product_id INT DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- TICKET REPLIES TABLE
-- ===================================
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================
-- PRODUCT IMAGES TABLE
-- Multiple images per product
-- ===================================
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id, sort_order)
) ENGINE=InnoDB;

-- ===================================
-- AUDIT LOGS TABLE
-- Track all security and admin actions
-- ===================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_email VARCHAR(150) DEFAULT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    page_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- LOCKED ACCOUNTS TABLE
-- History of all account lockouts
-- ===================================
CREATE TABLE IF NOT EXISTS locked_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    unlocked_at TIMESTAMP NULL DEFAULT NULL,
    unlocked_by INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_user (user_id),
    INDEX idx_unlocked (unlocked_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unlocked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================
-- SYSTEM SETTINGS TABLE
-- Configurable security and payment settings
-- ===================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================================
-- SEED DATA: Default Security Settings
-- ===================================
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_login_attempts', '5', 'Number of failed attempts before account lockout'),
('lockout_duration', '15', 'Lockout duration in minutes (legacy - now permanent)'),
('session_timeout', '15', 'Session timeout in minutes'),
('min_password_length', '8', 'Minimum password length'),
('require_uppercase', '1', 'Require at least one uppercase letter'),
('require_lowercase', '1', 'Require at least one lowercase letter'),
('require_number', '1', 'Require at least one number'),
('require_special_char', '1', 'Require at least one special character'),
('password_expiry_days', '90', 'Password expiration days (0 = never)'),
('paymongo_secret_key', '', 'PayMongo secret API key'),
('paymongo_public_key', '', 'PayMongo public API key'),
('ngrok_url', '', 'Ngrok URL for webhook testing')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ===================================
-- SEED DATA: Default Categories
-- ===================================
INSERT INTO categories (name, icon) VALUES
('Beverages', '🥤'),
('Snacks', '🍿'),
('Instant Food', '🍜'),
('Personal Care', '🧴'),
('Household', '🧹')
ON DUPLICATE KEY UPDATE name = name;

-- ===================================
-- SEED DATA: Default Role Accounts
-- Admin:   admin@dailytoinks.com   / admin123
-- Manager: manager@dailytoinks.com / manager123
-- Rider:   rider@dailytoinks.com   / rider123
-- ===================================
INSERT INTO users (name, email, phone, password, role, status, email_verified) VALUES
('System Admin', 'admin@dailytoinks.com', '09171234567', '$2y$10$uXtcSj.cvTfMIJIP8wz31eUPE9.8V6uP.3Nw99dAqao2jRoPLMyxG', 'admin', 'active', 1),
('Store Manager', 'manager@dailytoinks.com', '09181234567', '$2y$10$uXtcSj.cvTfMIJIP8wz31eUPE9.8V6uP.3Nw99dAqao2jRoPLMyxG', 'manager', 'active', 1),
('Delivery Rider', 'rider@dailytoinks.com', '09191234567', '$2y$10$uXtcSj.cvTfMIJIP8wz31eUPE9.8V6uP.3Nw99dAqao2jRoPLMyxG', 'rider', 'active', 1)
ON DUPLICATE KEY UPDATE email = email;

