<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Privacy Policy";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .policy-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            line-height: 1.8;
        }
        .policy-container h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .policy-container .last-updated {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }
        .policy-container h2 {
            font-size: 1.2rem;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.4rem;
        }
        .policy-container p, .policy-container li {
            color: #555;
            font-size: 0.92rem;
        }
        .policy-container ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .policy-container li {
            margin-bottom: 0.4rem;
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="policy-container">
                <h1>Privacy Policy</h1>
                <p class="last-updated">Last Updated: <?= date('F d, Y') ?></p>

                <p>At DailyToinks, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, store, and protect your data when you use our platform.</p>

                <h2>1. Information We Collect</h2>
                <p>We collect the following types of information:</p>
                <ul>
                    <li><strong>Personal Information:</strong> Name, email address, phone number, and shipping address when you register or place an order.</li>
                    <li><strong>Account Information:</strong> Login credentials (passwords are hashed and never stored in plain text).</li>
                    <li><strong>Transaction Data:</strong> Order history, payment method selections, and purchase details.</li>
                    <li><strong>Technical Data:</strong> IP address, browser type, device information, and cookies for session management.</li>
                    <li><strong>Activity Logs:</strong> Login/logout timestamps and actions performed on the platform for security auditing.</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <ul>
                    <li>To process and fulfill your orders</li>
                    <li>To manage your account and provide customer support</li>
                    <li>To send order confirmations and delivery updates</li>
                    <li>To improve our platform and user experience</li>
                    <li>To detect and prevent fraud, unauthorized access, and security threats</li>
                    <li>To comply with legal obligations</li>
                </ul>

                <h2>3. Data Protection & Security</h2>
                <p>We implement industry-standard security measures to protect your data:</p>
                <ul>
                    <li><strong>Encryption:</strong> Sensitive data (phone numbers, addresses) is encrypted using AES-256-CBC encryption at rest in our database.</li>
                    <li><strong>Password Hashing:</strong> All passwords are hashed using bcrypt and are never stored or transmitted in plain text.</li>
                    <li><strong>Multi-Factor Authentication:</strong> Optional TOTP-based MFA via Google Authenticator for enhanced account security.</li>
                    <li><strong>CSRF Protection:</strong> All state-changing requests are protected against Cross-Site Request Forgery attacks.</li>
                    <li><strong>Session Security:</strong> Automatic session timeout after periods of inactivity.</li>
                    <li><strong>Audit Logging:</strong> All significant user and system activities are logged for security monitoring.</li>
                </ul>

                <h2>4. Cookies</h2>
                <p>We use cookies for the following purposes:</p>
                <ul>
                    <li><strong>Session Cookies:</strong> Essential cookies to maintain your login session and shopping cart. These are temporary and expire when you close your browser or after the session timeout.</li>
                    <li><strong>Security Cookies:</strong> CSRF tokens to protect against cross-site request forgery attacks.</li>
                </ul>
                <p>We do not use third-party tracking cookies or advertising cookies. You can manage cookies through your browser settings.</p>

                <h2>5. Data Sharing</h2>
                <p>We do not sell, trade, or rent your personal information to third parties. We may share data only in the following cases:</p>
                <ul>
                    <li><strong>Delivery Partners:</strong> Shipping address and contact information with assigned delivery riders to fulfill your orders.</li>
                    <li><strong>Payment Processors:</strong> Payment details are shared with secure payment gateways (GCash, PayMaya, GrabPay) to process transactions.</li>
                    <li><strong>Legal Requirements:</strong> When required by law, regulation, or legal process.</li>
                </ul>

                <h2>6. Data Retention</h2>
                <p>We retain your personal data for as long as your account is active or as needed to provide services. Order records are kept for accounting and legal purposes. You may request deletion of your account and associated data by contacting our support team.</p>

                <h2>7. Your Rights</h2>
                <p>Under the Data Privacy Act of 2012 (Republic Act No. 10173), you have the right to:</p>
                <ul>
                    <li>Access your personal data</li>
                    <li>Correct inaccurate or incomplete data</li>
                    <li>Request erasure of your personal data</li>
                    <li>Object to the processing of your data</li>
                    <li>Data portability</li>
                </ul>

                <h2>8. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy or wish to exercise your data rights, please contact us at:</p>
                <ul>
                    <li>Email: <strong>privacy@dailytoinks.com</strong></li>
                    <li>Address: DailyToinks Inc., Metro Manila, Philippines</li>
                </ul>

                <h2>9. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date. Continued use of our platform after changes constitutes acceptance of the updated policy.</p>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
