<?php
// ===================================
// EMAIL CONFIGURATION - EXAMPLE
// ===================================
// Copy this file to mail.php and update with your actual credentials.
// DO NOT commit mail.php to version control!

// === SMTP SETTINGS ===
// Default configuration for Gmail SMTP
// To use Gmail: enable 2-Factor Auth on your Google Account, then create an App Password:
// https://myaccount.google.com/apppasswords

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);

// ===================================
// UPDATE THESE WITH YOUR CREDENTIALS
// ===================================
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_NAME', 'DailyToinks');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');

/**
 * Send an email using direct SMTP connection (no external library needed)
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML email body
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $htmlBody) {
    // If SMTP not configured, fall back to PHP mail()
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . (SMTP_FROM_EMAIL ?: 'noreply@dailytoinks.com') . ">\r\n";
        return @mail($to, $subject, $htmlBody, $headers);
    }

    try {
        $smtp = @fsockopen('tls://' . SMTP_HOST, 465, $errno, $errstr, 10);
        if (!$smtp) {
            $smtp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
            if (!$smtp) return false;

            fgets($smtp, 512);
            fwrite($smtp, "EHLO localhost\r\n");
            while ($line = fgets($smtp, 512)) {
                if (substr($line, 3, 1) == ' ') break;
            }

            fwrite($smtp, "STARTTLS\r\n");
            fgets($smtp, 512);
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);

            fwrite($smtp, "EHLO localhost\r\n");
            while ($line = fgets($smtp, 512)) {
                if (substr($line, 3, 1) == ' ') break;
            }
        } else {
            fgets($smtp, 512);
            fwrite($smtp, "EHLO localhost\r\n");
            while ($line = fgets($smtp, 512)) {
                if (substr($line, 3, 1) == ' ') break;
            }
        }

        fwrite($smtp, "AUTH LOGIN\r\n");
        fgets($smtp, 512);

        fwrite($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
        fgets($smtp, 512);

        fwrite($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
        $authResponse = fgets($smtp, 512);

        if (substr($authResponse, 0, 3) !== '235') {
            fclose($smtp);
            return false;
        }

        $from = SMTP_FROM_EMAIL ?: SMTP_USERNAME;
        fwrite($smtp, "MAIL FROM:<{$from}>\r\n");
        fgets($smtp, 512);

        fwrite($smtp, "RCPT TO:<{$to}>\r\n");
        fgets($smtp, 512);

        fwrite($smtp, "DATA\r\n");
        fgets($smtp, 512);

        $message = "From: " . SMTP_FROM_NAME . " <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;
        $message .= "\r\n.\r\n";

        fwrite($smtp, $message);
        fgets($smtp, 512);

        fwrite($smtp, "QUIT\r\n");
        fclose($smtp);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Send activation email to new user
 * @param string $email User email address
 * @param string $name User name
 * @param string $activationLink Activation URL
 * @return bool True on success
 */
function sendActivationEmail($email, $name, $activationLink) {
    $subject = "Activate Your DailyToinks Account";

    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 500px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: #C1001A; color: #fff; padding: 25px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .body { padding: 30px; }
            .body p { color: #555; line-height: 1.6; margin: 0 0 15px; }
            .btn { display: inline-block; background: #C1001A; color: #fff !important; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 16px; }
            .btn-container { text-align: center; margin: 25px 0; }
            .footer { background: #f9f9f9; padding: 15px; text-align: center; font-size: 12px; color: #999; }
            .link { word-break: break-all; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>DailyToinks</h1>
            </div>
            <div class="body">
                <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Thank you for registering at DailyToinks! Please click the button below to activate your account:</p>
                <div class="btn-container">
                    <a href="' . htmlspecialchars($activationLink) . '" class="btn">Activate My Account</a>
                </div>
                <p>This activation link will expire in <strong>24 hours</strong>.</p>
                <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                <p class="link">' . htmlspecialchars($activationLink) . '</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' DailyToinks. All rights reserved.</p>
                <p>If you did not create this account, please ignore this email.</p>
            </div>
        </div>
    </body>
    </html>';

    return sendEmail($email, $subject, $htmlBody);
}
