# DailyToinks E-Commerce System

A comprehensive PHP-based e-commerce platform with advanced security features, multi-factor authentication, and role-based access control.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## Features

### Core Functionality
- **User Management**: Registration, login, profile management with email activation
- **Product Catalog**: Browse, search, and filter products with image galleries
- **Shopping Cart**: Add/remove items, quantity management, persistent cart
- **Checkout Process**: Secure order placement with PayMongo payment integration
- **Order Tracking**: Real-time order status updates with delivery tracking
- **Support Tickets**: Customer support system with ticket management

### Security Features
- **Account Lockout**: Permanent lockout after 5 failed login attempts (admin unlock required)
- **Data Encryption**: AES-256 encryption for sensitive user data
- **Password Security**: Bcrypt hashing with strong password requirements
- **CSRF Protection**: Token-based protection for all state-changing operations
- **Session Management**: 15-minute timeout with auto-logout and continuous validation
- **Audit Logging**: Comprehensive logging of security events
- **File Upload Security**: Type, size, and count restrictions on product images
- **Role-Based Access Control**: Admin, Manager, Rider, and Customer roles

### Admin Features
- **Dashboard**: Sales analytics, order statistics, and recent activity
- **User Management**: View, edit, lock/unlock user accounts
- **Product Management**: CRUD operations with image uploads
- **Order Management**: Update order status and assign riders
- **Audit Logs**: View system activity and security events
- **Locked Accounts**: History and management of locked accounts

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- OpenSSL extension enabled
- PDO MySQL extension
- PayMongo account (for payments)

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/dailytoinks.git
cd dailytoinks
```

### 2. Create Database
```sql
mysql -u root -p < database.sql
```

### 3. Configure Database
Copy `config/database.php` and update with your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dailytoinks_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 4. Configure Email (Optional)
For email activation features, configure `config/mail.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_NAME', 'DailyToinks');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
```
> Note: For Gmail, use an [App Password](https://myaccount.google.com/apppasswords)

### 5. Configure PayMongo (Optional)
For payment processing, add your PayMongo keys in `api/payment.php`:
```php
$paymongo_secret_key = 'sk_test_your_key_here';
$paymongo_public_key = 'pk_test_your_key_here';
```

### 6. Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/profiles/
```

### 7. Access the Application
- Frontend: `http://localhost/dailytoinks/`
- Admin Panel: `http://localhost/dailytoinks/admin/`

### Default Admin Account
- Email: `admin@dailytoinks.com`
- Password: `admin123` (change immediately after first login)

## Security Considerations

### Production Deployment
1. **Change all default passwords** (admin, database)
2. **Enable HTTPS** - Required for payment processing
3. **Set secure session cookies** in `config/security.php`
4. **Configure proper file permissions** (644 for PHP files, 755 for directories)
5. **Hide PHP version** in server configuration
6. **Set up regular backups** of database and uploads

### API Keys
Never commit API keys to version control. Use environment variables or a separate config file excluded from git.

## Project Structure

```
dailytoinks/
├── admin/              # Admin panel
│   ├── includes/       # Admin header, sidebar, topbar
│   ├── audit-logs.php
│   ├── locked-accounts.php
│   ├── users.php
│   └── ...
├── api/                # REST API endpoints
│   ├── auth.php        # Authentication
│   ├── cart.php        # Shopping cart
│   ├── orders.php      # Order management
│   ├── payment.php     # PayMongo integration
│   └── ...
├── config/             # Configuration files
│   ├── database.php    # Database connection
│   ├── security.php    # Security functions
│   └── mail.php        # Email configuration
├── includes/           # Shared components
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── uploads/            # User uploads (excluded from git)
├── docs/               # Documentation
├── css/                # Stylesheets
├── js/                 # JavaScript files
└── database.sql        # Database schema
```

## Architecture

The system follows a **layered architecture**:

- **Presentation Layer**: PHP templates with embedded HTML
- **API Layer**: RESTful endpoints for AJAX operations
- **Business Logic Layer**: PHP functions for core operations
- **Data Access Layer**: PDO with prepared statements

## Documentation

For detailed documentation, see:
- `docs/DailyToinks_Documentation.md` - Complete system documentation
- `docs/architecture-diagram.mmd` - Mermaid architecture diagram

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file.

## Acknowledgments

- PayMongo for payment processing
- Google Authenticator for TOTP implementation
- Feather Icons for UI icons

## Support

For issues or questions, please use the GitHub issue tracker or contact the development team.

---

**Disclaimer**: This system is provided as-is. Proper security auditing is recommended before production deployment.
