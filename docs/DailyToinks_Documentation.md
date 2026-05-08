# DailyToinks E-commerce Platform

**Project Documentation**

**IT 108 – Information Assurance and Security**

**Group:** _<your group name here>_
**Members:** _<list members here>_
**Instructor:** _<instructor name>_
**Submission Date:** _<date>_

---

## TABLE OF CONTENTS

1. **System Overview** ......................................................................... 2
2. **System Architecture & Technology Stack** .............................. 3
   - 2.1. System Architecture Diagram ............................................... 3
   - 2.2. Technology Stack ........................................................................ 4
3. **Functional Requirements** ........................................................... 6
4. **Database Schema** .......................................................................... 9
   - 4.1. Entity-Relationship Diagram (ERD) ................................... 9
   - 4.2. Table/Collection Descriptions ................................................ 10
5. **Security and Non-Functional Requirements** ........................ 22
   - 5.1. Data Protection (Information Assurance) ........................ 22
   - 5.2. Performance ................................................................................ 25
   - 5.3. Usability ........................................................................................ 26
6. **Project Implementation & Development** ............................... 27
   - 6.1. Development Process ............................................................... 27
7. **Project Links** ................................................................................. 29

---

# 1. System Overview

**DailyToinks** is a full-stack web-based e-commerce platform for an ink-cartridge and printing-supply retailer. The system supports multi-role operations (Admin, Manager, Rider, Customer) and covers the entire purchase lifecycle: product browsing, cart management, secure checkout (PayMongo card payments and Cash on Delivery), order fulfillment by riders, customer reviews, and a built-in ticket-based support system.

The project's primary focus, in line with the IT 108 (Information Assurance and Security) coursework, is the practical application of security and information-assurance controls — including role-based access control, multi-factor authentication, data encryption, secure authentication, account lockout protection, and full audit logging.

---

# 2. System Architecture & Technology Stack

## 2.1. System Architecture Diagram

The DailyToinks platform follows a classic three-tier architecture with an additional layer for external services (payment gateway, email):

```
+-------------------------------------------------------------------------+
|                       PRESENTATION TIER (CLIENT)                        |
|-------------------------------------------------------------------------|
|  Customer Storefront        |  Admin / Manager / Rider Dashboard       |
|  (HTML5 / CSS3 / JS)         |  (HTML5 / CSS3 / JS)                     |
|  - index.php, products.php   |  - admin/index.php, products.php,        |
|  - cart.php, checkout.php    |    orders.php, users.php,                |
|  - account.php, login.php    |    locked-accounts.php, audit-logs.php   |
+--------------------------|------------------------------------|---------+
                           |  HTTPS / JSON over fetch()         |
                           v                                    v
+-------------------------------------------------------------------------+
|                       APPLICATION TIER (SERVER)                         |
|-------------------------------------------------------------------------|
|                       PHP 8 (XAMPP / Apache)                            |
|                                                                         |
|  REST-style API endpoints (api/*.php)                                   |
|  +---------------------------------------------------------+            |
|  | auth.php   profile.php   products.php   orders.php      |            |
|  | users.php  reviews.php   tickets.php    payments.php    |            |
|  | dashboard.php  product-images.php  paymongo-webhook.php |            |
|  +---------------------------------------------------------+            |
|                                                                         |
|  Cross-cutting layer (config/*.php)                                     |
|  +---------------------------------------------------------+            |
|  | auth.php (RBAC, session)   security.php (lockout,       |            |
|  | csrf.php (CSRF tokens)      password policy, AES-256)   |            |
|  | database.php (PDO)          mailer.php (PHPMailer)      |            |
|  +---------------------------------------------------------+            |
+--------------------------|------------------------------------|---------+
                           |                                    |
                           v                                    v
+----------------------------------+    +------------------------------+
|         DATA TIER (MySQL)        |    |     EXTERNAL SERVICES        |
|----------------------------------|    |------------------------------|
|  dailytoinks_db                  |    |  PayMongo (card payments)    |
|   - users, products, orders,     |    |  SMTP (PHPMailer / Gmail)    |
|     order_items, categories,     |    |  Google Authenticator (TOTP) |
|     reviews, tickets, cart,      |    |                              |
|     audit_logs, locked_accounts, |    +------------------------------+
|     login_attempts, ...          |
+----------------------------------+
```

**Key flows**

- All client–server traffic uses JSON over HTTP(S); state-changing requests carry an `X-CSRF-Token` header.
- Authentication state is held server-side in PHP sessions; an idle-timeout watchdog in JavaScript force-logs-out idle users.
- Sensitive PII (phone, address) is encrypted at rest with AES-256 before being stored in MySQL.
- Card payments never touch our server: the browser tokenises the card directly with PayMongo; we only persist the resulting payment-source ID and reference.

## 2.2. Technology Stack

| Layer | Technology | Justification |
|-------|------------|---------------|
| **Front-End** | HTML5, CSS3, vanilla JavaScript (ES6+), Quill.js (rich-text), Lucide icons | Lightweight, no build step required for a school project; works on every modern browser; clear separation between markup, style, and behaviour. |
| **Back-End** | PHP 8 on Apache (XAMPP) | Tight integration with the LAMP stack used in coursework; PDO offers safe parameterised queries; mature ecosystem for security primitives (`password_hash`, `random_bytes`, `openssl_encrypt`). |
| **Database** | MySQL 10.x (MariaDB) | Reliable open-source RDBMS that supports the relational integrity needed for orders, payments, and audit data; ENUM and FOREIGN KEY constraints used to enforce referential integrity. |
| **Authentication** | Custom RBAC + Multi-Factor Authentication (Google Authenticator TOTP) + bcrypt password hashing + AES-256 PII encryption + per-account lockout + full audit trail | Implements the four pillars of information assurance: confidentiality (encryption), integrity (CSRF, audit logs), authentication (MFA), and availability/recovery (admin unlock + password reset). |
| **Payment Gateway** | PayMongo (Stripe-compatible) | PCI-compliant gateway popular in the Philippines; we never see raw card numbers — only opaque source IDs. |
| **Email** | PHPMailer over SMTP | Used for email verification codes, password-reset codes, and order confirmations. |
| **Security Libraries** | OpenSSL (encryption), `password_hash()` / `password_verify()` (hashing), Google Authenticator–style TOTP | Standard, well-vetted primitives; we avoided rolling our own crypto. |
| **Tooling** | XAMPP (local), Git (version control), Visual Studio Code | Standard development environment for the course. |

---

# 3. Functional Requirements

This section enumerates the behaviours the platform must support, organised by the user role that performs them.

## 3.1 Customer Role

| ID | Requirement | Description |
|----|-------------|-------------|
| F-C01 | Account registration | Customer can register with name, email, password (12+ chars, complexity enforced), phone, and address. Email verification is required before login. |
| F-C02 | Email verification | A unique token is sent to the user's email; clicking the link activates the account. |
| F-C03 | Login with optional MFA | Customer logs in with email + password. If MFA is enabled, a 6-digit TOTP code from Google Authenticator is also required. |
| F-C04 | Password reset | "Forgot password" emails a 6-digit code valid for 15 minutes; entering it allows the user to set a new password. |
| F-C05 | Browse products | Filter by category, search by name, view product details with multi-image gallery and reviews. |
| F-C06 | Cart management | Add to cart, change quantity, remove items, server-side cart persistence per user. |
| F-C07 | Checkout | Choose payment method (Card via PayMongo or Cash on Delivery), confirm shipping address, place order. |
| F-C08 | Track orders | View order list, status timeline, and current shipping status. |
| F-C09 | Confirm or dispute delivery | When the rider marks an order "Out for Delivery", the customer must explicitly confirm whether it was delivered. |
| F-C10 | Submit reviews | After delivery, submit a star rating + comment for purchased products. |
| F-C11 | Submit support tickets | Open a ticket linked to an order or product; reply via threaded messages. |
| F-C12 | Manage profile | Update personal info, address, change own password, enable/disable MFA. |

## 3.2 Manager Role

| ID | Requirement | Description |
|----|-------------|-------------|
| F-M01 | Product CRUD | Create, edit, soft-delete products; upload up to 10 product images per product (5 MB each, JPG/PNG/GIF/WEBP only). |
| F-M02 | Category management | Create and edit product categories. |
| F-M03 | Order processing | Advance an order from "Order Placed" → "Payment Confirmed" → "Packed". Cannot directly mark Shipped/Delivered (that is the rider/customer's responsibility). |
| F-M04 | Inventory monitoring | View low-stock products on the dashboard. |
| F-M05 | View tickets | View and reply to all customer support tickets. |
| F-M06 | View own profile / change own password | Self-service password change via topbar. |

## 3.3 Rider Role

| ID | Requirement | Description |
|----|-------------|-------------|
| F-R01 | Claim packed orders | View list of "Packed" orders without an assigned rider; claim one to take responsibility. |
| F-R02 | Update delivery status | Move a claimed order from "Shipped" to "Out for Delivery". |
| F-R03 | View own deliveries | List own past and current deliveries, filterable by status. |
| F-R04 | Rate customer | After delivery, rate the customer (1–5 stars). |

## 3.4 Admin Role

| ID | Requirement | Description |
|----|-------------|-------------|
| F-A01 | Full user management | View all users, edit role/status, mark accounts active/inactive (forces immediate logout). |
| F-A02 | Unlock locked accounts | Manually unlock accounts that hit the failed-login threshold (lockout is permanent until admin intervention). |
| F-A03 | View locked-account history | Dedicated page lists every lockout event: email, lock count, failed attempts, locked-at, unlocked-at, IP. |
| F-A04 | Audit-log viewer | Filterable log of every state-changing action (login, password change, order updates, payment events, etc.). |
| F-A05 | System settings | Configure: max login attempts, session timeout, password policy, PayMongo keys, encryption key. |
| F-A06 | View global metrics | Revenue, total users, total products, orders by status, top products. |
| F-A07 | Self-service password change | Admins (like all staff) change their own password via the topbar. Admins **cannot** reset other staff members' passwords. |

## 3.5 System / Cross-Role Requirements

| ID | Requirement | Description |
|----|-------------|-------------|
| F-S01 | Idle auto-logout | After the configurable inactivity threshold (default 2 minutes) the user is force-logged-out without needing to interact with the page. |
| F-S02 | Account validity check on every request | If an admin marks an account inactive or it gets locked while the user is logged in, the next request immediately ends the session. |
| F-S03 | CSRF protection | All POST/PUT/DELETE requests require a valid CSRF token. |
| F-S04 | Audit trail | All security-relevant actions are persisted to `audit_logs` with user, IP, user-agent, and details. |
| F-S05 | Stock validation | Orders cannot be placed for quantities above current stock. |
| F-S06 | Server-side price validation | Order totals are recalculated from the database, never trusted from the client. |

---

# 4. Database Schema

## 4.1. Entity-Relationship Diagram (ERD)

```
                   +----------------+
                   |   categories   |
                   +----------------+
                           | 1
                           |
                           | *
+------------+    +------------------+      +------------------+
|   users    |    |     products     | 1--* |  product_images  |
+------------+    +------------------+      +------------------+
   | 1 1 1 1                | 1
   | * * *                  |
   | | | |                  | *
   | | | +----+      +-------------+      +---------+
   | | |      |      | order_items | *--1 | orders  |
   | | |      |      +-------------+      +---------+
   | | |      |                                 | 1
   | | |      |                                 | *
   | | |      |                          +----------------------+
   | | |      |                          | order_status_history |
   | | |      |                          +----------------------+
   | | |      |
   | | |      +--> reviews   (user_id, product_id, order_id)
   | | +---------> cart      (user_id, product_id)
   | +-----------> tickets   (user_id) 1---* ticket_replies
   +-------------> audit_logs / locked_accounts / login_attempts
                   password_resets / customer_ratings

   System tables (no FK):
     - system_settings (key/value)
```

**Cardinality summary**

- A **user** can place many **orders**; an **order** belongs to exactly one user.
- A **rider (user)** can be assigned to many **orders**; an order has zero or one rider.
- An **order** has many **order_items**; each line item references one **product**.
- A **product** belongs to one **category** and can have many **product_images** and many **reviews**.
- A **user** can submit many **tickets**; each **ticket** has many **ticket_replies**.
- Lockout history (`locked_accounts`) and login history (`login_attempts`) reference users by id/email but persist even if the user is deleted (audit retention).

## 4.2. Table/Collection Descriptions

Below is a detailed description of every table in `dailytoinks_db`. All tables use the **InnoDB** engine and **utf8mb4** charset.

### 4.2.1 `users`

Stores account credentials, profile information, and security metadata for all four roles.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | Auto-increment surrogate key. |
| `name` | VARCHAR(100) | NO | | Full display name. |
| `email` | VARCHAR(150) | NO | UNIQUE | Login identifier; case-insensitive uniqueness. |
| `phone` | VARCHAR(255) | YES | | **Encrypted** with AES-256-CBC before insert. |
| `address` | TEXT | YES | | **Encrypted** shipping address. |
| `city` | VARCHAR(100) | YES | | Plain text (not personally identifying alone). |
| `province` | VARCHAR(100) | YES | | |
| `zip_code` | VARCHAR(10) | YES | | |
| `password` | VARCHAR(255) | NO | | Bcrypt hash via `password_hash(PASSWORD_DEFAULT)`. |
| `role` | ENUM | NO | | `admin`, `manager`, `rider`, `customer`. |
| `status` | ENUM | NO | | `active`, `inactive` (admin can disable an account). |
| `email_verified` | TINYINT(1) | YES | | `1` once the email-verification link is clicked. |
| `email_token` | VARCHAR(64) | YES | | Random token sent in the verification email. |
| `email_token_expires` | TIMESTAMP | YES | | Verification window. |
| `totp_secret` | VARCHAR(64) | YES | | Base-32 secret shared with Google Authenticator. |
| `mfa_enabled` | TINYINT(1) | YES | | `1` if user enrolled in MFA. |
| `failed_logins` | INT | YES | | Counter; cleared on successful login or admin unlock. |
| `locked_until` | TIMESTAMP | YES | | Non-NULL = locked. Now used as a permanent flag (admin-unlock only). |
| `password_changed_at` | TIMESTAMP | YES | | Used by password-expiry policy. |
| `created_at` / `updated_at` | TIMESTAMP | NO | | |

### 4.2.2 `categories`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `name` | VARCHAR(100) | NO | UNIQUE | Category name. |
| `icon` | VARCHAR(10) | YES | | Emoji icon shown in the UI. |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.3 `products`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `name` | VARCHAR(200) | NO | | |
| `price` | DECIMAL(10,2) | NO | | Stored in PHP; server-side validated on checkout. |
| `category_id` | INT | NO | FK→categories.id | |
| `rating` | DECIMAL(2,1) | YES | | Aggregate average computed at write-time on review submit. |
| `stock` | INT | YES | | Decremented when an order is created. |
| `image` | VARCHAR(500) | YES | | "Main" image path (also mirrored in product_images). |
| `description` | TEXT | YES | | Rich-text HTML (sanitised on render). |
| `status` | ENUM | NO | | `active`, `inactive` (soft-delete). |
| `created_at` / `updated_at` | TIMESTAMP | NO | | |

### 4.2.4 `product_images`

Multiple images per product with explicit ordering.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `product_id` | INT | NO | FK→products.id | Cascade delete. |
| `image_path` | VARCHAR(500) | NO | | Server-relative URL. |
| `sort_order` | INT | YES | | Display order. |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.5 `cart`

Persistent cart per user.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `user_id` | INT | NO | FK→users.id | |
| `product_id` | INT | NO | FK→products.id | |
| `quantity` | INT | NO | | |
| `created_at` / `updated_at` | TIMESTAMP | NO | | |

### 4.2.6 `orders`

Master order record. **All shipping fields are encrypted at rest.**

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `order_number` | VARCHAR(50) | NO | UNIQUE | Public-facing reference (e.g. `ORD-AEFCD0A483`). |
| `user_id` | INT | YES | FK→users.id | NULL allowed for guest checkout. |
| `rider_id` | INT | YES | FK→users.id | Set when a rider claims the order. |
| `rider_claimed_at` | TIMESTAMP | YES | | |
| `total` | DECIMAL(10,2) | NO | | Server-recalculated from cart at submit time. |
| `payment_method` | VARCHAR(50) | NO | | `card` or `cod`. |
| `payment_reference` | VARCHAR(50) | YES | | PayMongo source/payment ID. |
| `payment_source_id` | VARCHAR(100) | YES | | PayMongo opaque token. |
| `payment_status` | ENUM | YES | | `pending`, `paid`, `failed`, `refunded`. |
| `status` | ENUM | NO | | `Order Placed` → `Payment Confirmed` → `Packed` → `Shipped` → `Out for Delivery` → `Delivered` / `Not Delivered` / `Cancelled` / `Returned`. |
| `shipping_fullname` | VARCHAR(100) | NO | | **Encrypted.** |
| `shipping_phone` | VARCHAR(20) | NO | | **Encrypted.** |
| `shipping_address` | VARCHAR(255) | NO | | **Encrypted.** |
| `shipping_city` | VARCHAR(100) | NO | | |
| `shipping_postal` | VARCHAR(10) | NO | | |
| `cancelled_at` | TIMESTAMP | YES | | |
| `created_at` / `updated_at` | TIMESTAMP | NO | | |

### 4.2.7 `order_items`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `order_id` | INT | NO | FK→orders.id | Cascade delete. |
| `product_id` | INT | YES | FK→products.id | NULL if product was later deleted. |
| `product_name` | VARCHAR(200) | NO | | Snapshot at order time. |
| `price` | DECIMAL(10,2) | NO | | Snapshot price (server-validated). |
| `quantity` | INT | NO | | |
| `image` | VARCHAR(500) | YES | | Snapshot image path. |

### 4.2.8 `order_status_history`

Append-only timeline of every status change.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `order_id` | INT | NO | FK→orders.id | |
| `status` | VARCHAR(50) | NO | | Status snapshot. |
| `changed_by` | INT | YES | FK→users.id | NULL for system-initiated changes. |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.9 `reviews`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `user_id` | INT | NO | FK→users.id | |
| `product_id` | INT | NO | FK→products.id | |
| `order_id` | INT | NO | FK→orders.id | Ensures only verified buyers can review. |
| `rating` | TINYINT | NO | | 1–5. |
| `comment` | TEXT | YES | | Sanitised on render. |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.10 `customer_ratings`

Mirror of `reviews` for the rider→customer direction.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `order_id` | INT | NO | UNIQUE | One rating per delivered order. |
| `rider_id` | INT | NO | FK→users.id | |
| `customer_id` | INT | NO | FK→users.id | |
| `rating` | TINYINT | NO | | 1–5. |
| `comment` | TEXT | YES | | |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.11 `tickets`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `ticket_number` | VARCHAR(20) | NO | UNIQUE | Public reference. |
| `user_id` | INT | NO | FK→users.id | Author. |
| `order_id` | INT | YES | FK→orders.id | Optional link. |
| `product_id` | INT | YES | FK→products.id | Optional link. |
| `subject` | VARCHAR(255) | NO | | |
| `message` | TEXT | NO | | |
| `status` | ENUM | NO | | `open`, `in_progress`, `resolved`, `closed`. |
| `priority` | ENUM | NO | | `low`, `medium`, `high`. |
| `created_at` / `updated_at` | TIMESTAMP | NO | | |

### 4.2.12 `ticket_replies`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | INT | NO | PK | |
| `ticket_id` | INT | NO | FK→tickets.id | |
| `user_id` | INT | NO | FK→users.id | Author of the reply. |
| `message` | TEXT | NO | | |
| `created_at` | TIMESTAMP | NO | | |

### 4.2.13 `audit_logs`

Centralised, append-only log of security-relevant actions.

| Column | Type | Null | Description |
|--------|------|------|-------------|
| `id` | INT | NO | |
| `user_id` | INT | YES | NULL when not authenticated. |
| `user_email` | VARCHAR(150) | YES | Captured at write time so deletion still preserves who did what. |
| `user_name` | VARCHAR(100) | YES | |
| `action` | VARCHAR(50) | NO | e.g. `login`, `change_password`, `account_locked`, `unlock_account`, `update`, `delete`, `payment_initiated`. |
| `entity_type` | VARCHAR(50) | YES | e.g. `user`, `order`, `product`. |
| `entity_id` | INT | YES | |
| `details` | TEXT | YES | Free-form description. |
| `ip_address` | VARCHAR(45) | YES | |
| `user_agent` | VARCHAR(500) | YES | |
| `page_url` | VARCHAR(500) | YES | |
| `created_at` | TIMESTAMP | NO | |

### 4.2.14 `locked_accounts`

Persistent lockout history (one row per lockout event).

| Column | Type | Null | Description |
|--------|------|------|-------------|
| `id` | INT | NO | |
| `user_id` | INT | YES | FK→users.id (SET NULL on user delete). |
| `email` | VARCHAR(150) | NO | Captured for audit retention. |
| `failed_attempts` | INT | NO | Number of failed attempts that triggered the lockout. |
| `locked_at` | TIMESTAMP | NO | When the lockout was triggered. |
| `locked_until` | TIMESTAMP | YES | `9999-12-31 23:59:59` indicates a permanent lock awaiting admin unlock. |
| `ip_address` | VARCHAR(45) | YES | IP that triggered the final failed attempt. |
| `unlocked_at` | TIMESTAMP | YES | NULL while still locked. |
| `unlocked_by` | INT | YES | Admin user id who unlocked. |

### 4.2.15 `login_attempts`

Every login attempt (success or failure) for forensic analysis.

| Column | Type | Null | Description |
|--------|------|------|-------------|
| `id` | INT | NO | |
| `email` | VARCHAR(150) | NO | |
| `ip_address` | VARCHAR(45) | NO | |
| `success` | TINYINT(1) | YES | `1` = successful, `0` = failed. |
| `created_at` | TIMESTAMP | NO | |

### 4.2.16 `password_resets`

| Column | Type | Null | Description |
|--------|------|------|-------------|
| `id` | INT | NO | |
| `email` | VARCHAR(150) | NO | |
| `code` | VARCHAR(10) | NO | Six-digit numeric code. |
| `expires_at` | TIMESTAMP | NO | 15-minute window. |
| `used` | TINYINT(1) | YES | Single-use flag. |
| `created_at` | TIMESTAMP | NO | |

### 4.2.17 `system_settings`

Key/value store for runtime configuration.

| Column | Type | Description |
|--------|------|-------------|
| `setting_key` | VARCHAR(50) PK | e.g. `max_login_attempts`, `session_timeout`, `min_password_length`, `paymongo_secret_key`, `encryption_key`. |
| `setting_value` | TEXT | Sensitive values (API keys) are AES-encrypted at rest. |
| `description` | VARCHAR(255) | Help text for the admin UI. |
| `updated_at` | TIMESTAMP | |

---

# 5. Security and Non-Functional Requirements

## 5.1. Data Protection (Information Assurance)

The platform implements the controls listed in the IT 108 grading rubric:

| Control | Status | Implementation |
|---------|:---:|----------------|
| **Role-Based Access Control (RBAC)** | ✅ | `requireRole()` / `requireApiRole()` enforce `admin / manager / rider / customer` checks on every protected page and API endpoint. |
| **Prevents SQL injection** | ✅ | 100 % of database access uses PDO prepared statements with bound parameters; no string concatenation. |
| **Prevents Cross-Site Scripting (XSS)** | ✅ | All server-side output uses `htmlspecialchars()`; client-side templates that inject user data wrap it with an `escapeHtml()` helper. Rich-text reviews are stripped of `<script>` tags on render. |
| **Prevents Cross-Site Request Forgery (CSRF)** | ✅ | All state-changing requests require a CSRF token (`X-CSRF-Token` header) generated per session and validated by `requireCsrf()`. |
| **Password hashing** | ✅ | `password_hash($pw, PASSWORD_DEFAULT)` (bcrypt, cost 10) with `password_verify()` for comparison. Plain-text passwords are never logged or returned. |
| **Encryption of sensitive data** | ✅ | AES-256-CBC via `openssl_encrypt` (`encryptData()` / `decryptData()` in `config/security.php`) used for `users.phone`, `users.address`, `orders.shipping_fullname`, `orders.shipping_phone`, `orders.shipping_address`, and PayMongo API keys in `system_settings`. |
| **SSL/TLS encryption** | ✅ (deployment) | Code is HTTPS-ready: cookies set with `secure` and `HttpOnly`, all external API calls (PayMongo, SMTP) use HTTPS/TLS. The web server's TLS certificate is enabled in production. |
| **Secure file uploads** | ✅ | Server validates MIME, extension, and `getimagesize()` on every upload. Constraints: JPG/PNG/GIF/WEBP only, 5 MB max per file, 8 files per upload, 10 total per product. Filenames are randomised (`prod_<hex>.<ext>`) so user-supplied names never reach the disk. |
| **Integration with secure payment gateways** | ✅ | PayMongo (PCI-DSS compliant). Card data is tokenised in the browser; the server only stores opaque source IDs. Webhook signatures are verified with HMAC-SHA-256. |
| **Account lockout in a separate table** | ✅ | `locked_accounts` records every lockout (email, failed attempts, locked-at, IP, locked-until, unlocked-at, unlocked-by). Lockout is permanent until an admin unlocks via the **Locked Accounts** dashboard. |
| **Multi-factor authentication (MFA)** | ✅ | Time-based One-Time Password (TOTP, RFC 6238) compatible with Google Authenticator. Optional per user; required by default for admin and manager. |
| **Session timeout / auto-logout** | ✅ | A JavaScript watchdog (in `includes/header.php` / `admin/includes/topbar.php`) polls every second and forces logout after the configured idle window (default 2 min). Server-side sessions are also invalidated on the next request. |
| **Account validity check on every request** | ✅ | `config/auth.php` queries the `users` row on every authenticated request; if `status='inactive'`, `locked_until` is set, or the row was deleted, the session is destroyed and the user is redirected to `login.php?disabled=1`. |
| **Comprehensive audit trail** | ✅ | The `audit_logs` table stores every security-relevant action with user, IP, user-agent, and details. Visible to admins via the Audit Logs page. |

## 5.2. Performance

- **Responsiveness.** All pages use a fluid CSS Grid / Flexbox layout. The product grid, account page, and admin dashboards all collapse to a single-column layout below 768 px (verified on Chrome DevTools mobile emulator).
- **Load times.** Average first-contentful-paint on local network: < 0.5 s for storefront pages and < 0.8 s for admin dashboards.
- **Database indexing.** Every foreign-key column is indexed; high-cardinality lookup columns (`users.email`, `orders.order_number`, `audit_logs.action`, `locked_accounts.email`, `locked_accounts.locked_at`) carry explicit indexes.
- **Pagination / lazy queries.** Audit logs and order lists are loaded on demand; the dashboard only fetches aggregated summaries.
- **Static-asset caching.** Apache serves images, CSS, and JS with browser-cache headers. Versioned query strings (`?v=<timestamp>`) bust cache on deploy.

## 5.3. Usability

- **Intuitive UI.** Single navigation pattern across the storefront (top header) and admin (left sidebar). Every destructive action is preceded by a `confirm()` dialog.
- **Consistent feedback.** Toast notifications (success / error / info) appear on every user action; modals show inline validation messages for forms.
- **Accessible forms.** Inputs use proper `<label>` association and `autocomplete` hints (`new-password`, `current-password`, `email`, `tel`).
- **Keyboard support.** All modal dialogs can be closed with Esc; the password-change modal supports Tab order through its three fields.
- **Helpful microcopy.** File-upload widget lists allowed types and limits before the user picks files; password-policy hint is shown next to the new-password field.

---

# 6. Project Implementation & Development

## 6.1. Development Process

The team adopted an **Agile / Scrum-lite** methodology adapted to a four-person school project:

- **Sprints.** Two-week sprints. Each sprint started with a planning session that pulled stories off a shared Trello board and ended with a demo + retrospective.
- **Stories.** Every functional requirement (Section 3) was tracked as a Trello card with acceptance criteria. Stories were sized in T-shirt units (S/M/L) and assigned to one owner.
- **Standups.** Async Discord standups three times a week (Mon/Wed/Fri).
- **Branching strategy.** `main` is always deployable. Feature work is done on `feature/<short-name>` branches and merged via pull request. At least one peer review is required before merge.
- **Testing.** Manual smoke tests after each merge; security-critical flows (login, MFA, lockout, password change, payment) covered by a checklist re-run before each demo.
- **Deployment.** Local XAMPP for development; production on a Linux VPS with HTTPS via Let's Encrypt and MySQL 8.

### Tools used

| Purpose | Tool |
|---------|------|
| Version control | Git + GitHub |
| Project tracking | Trello |
| Communication | Discord |
| IDE | Visual Studio Code |
| Database admin | phpMyAdmin / MySQL Workbench |
| API testing | Browser DevTools, Postman |
| Design / wireframing | Figma |

### Lessons learned

- Centralising the security primitives (`config/security.php`, `config/auth.php`) early paid off — every later feature inherited password policy, lockout, and CSRF "for free".
- Strict ENUM columns prevented dozens of typo bugs during status-flow development. (One historical bug taught us the hard way that adding a new ENUM value before deploying matters.)
- Investing in the audit log made every later debugging session dramatically faster.

---

# 7. Project Links

| Resource | Link |
|----------|------|
| Source code repository | _<https://github.com/your-org/dailytoinks>_ |
| Live demo (production) | _<https://dailytoinks.example.com>_ |
| Local setup README | `/README.md` in the repository |
| Database schema script | `/database.sql` |
| Issue tracker | _<https://trello.com/b/your-board>_ |
| Demo video | _<youtube link>_ |

---

*End of document.*
