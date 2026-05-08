<?php
// ===================================
// PAYMENT GATEWAY API ENDPOINT
// PayMongo integration with GCash via Checkout Session
// Uses ngrok for webhook callbacks in development
// ===================================

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Webhook endpoint does NOT require CSRF (called by PayMongo servers)
$isWebhook = isset($_GET['webhook']);

if (!$isWebhook && in_array($method, ['POST', 'PUT'])) {
    requireCsrf();
}

// Load PayMongo keys from system_settings
$db = getDB();
$pmSecret = getSetting('paymongo_secret_key') ?: '';
$pmPublic = getSetting('paymongo_public_key') ?: '';

switch ($method) {
    case 'POST':
        if ($isWebhook) {
            handleWebhook();
        } else {
            handleCreatePayment();
        }
        break;
    case 'GET':
        handlePaymentStatus();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// ===================================
// CREATE CHECKOUT SESSION (PayMongo)
// ===================================
function handleCreatePayment()
{
    global $pmSecret, $pmPublic;
    requireApiAuth();

    $input = json_decode(file_get_contents('php://input'), true);
    $orderNumber = $input['order_number'] ?? '';
    $amount = floatval($input['amount'] ?? 0);

    if (empty($orderNumber) || $amount <= 0) {
        jsonResponse(['success' => false, 'message' => 'Order number and amount are required'], 400);
    }

    $db = getDB();

    // Verify order exists and belongs to user
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    if (empty($pmSecret)) {
        jsonResponse(['success' => false, 'message' => 'PayMongo is not configured. Please set API keys in admin settings.'], 500);
    }

    try {
        $paymentRef = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
        $amountCentavos = intval($amount * 100);
        $baseUrl = getBaseUrl();

        // Create PayMongo Checkout Session
        $checkoutData = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => "Order #{$orderNumber}",
                    'line_items' => [[
                        'currency' => 'PHP',
                        'amount' => $amountCentavos,
                        'name' => "Order #{$orderNumber}",
                        'quantity' => 1
                    ]],
                    'payment_method_types' => ['gcash'],
                    'success_url' => "{$baseUrl}/normss/payment-result.php?status=success&ref={$paymentRef}",
                    'cancel_url' => "{$baseUrl}/normss/payment-result.php?status=failed&ref={$paymentRef}",
                    'reference_number' => $paymentRef
                ]
            ]
        ];

        $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($pmSecret . ':')
            ],
            CURLOPT_POSTFIELDS => json_encode($checkoutData),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            logAudit('payment_error', 'order', $order['id'], "cURL error: {$curlError}");
            jsonResponse(['success' => false, 'message' => 'Cannot reach payment gateway. Check internet connection.'], 500);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errMsg = $result['errors'][0]['detail'] ?? "HTTP {$httpCode}";
            logAudit('payment_failed', 'order', $order['id'], "PayMongo error: {$errMsg}");
            jsonResponse(['success' => false, 'message' => "Payment error: {$errMsg}"], 500);
        }

        $checkoutUrl = $result['data']['attributes']['checkout_url'] ?? '';
        $checkoutId = $result['data']['id'] ?? '';

        // Store payment reference
        $stmt = $db->prepare(
            "UPDATE orders SET payment_reference = ?, payment_source_id = ?, payment_status = 'pending' WHERE id = ?"
        );
        $stmt->execute([$paymentRef, $checkoutId, $order['id']]);

        logAudit('payment_initiated', 'order', $order['id'],
            "GCash payment via PayMongo. Ref: {$paymentRef}, Amount: PHP {$amount}");

        jsonResponse([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'payment_ref' => $paymentRef,
            'checkout_id' => $checkoutId
        ]);

    } catch (Exception $e) {
        logAudit('payment_error', 'order', $order['id'], 'Payment error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Payment processing error. Please try again.'], 500);
    }
}

// ===================================
// PAYMONGO WEBHOOK HANDLER
// URL: /normss/api/payment.php?webhook=1
// ===================================
function handleWebhook()
{
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    // Log the raw webhook for debugging
    file_put_contents(__DIR__ . '/../logs/webhook_' . date('Y-m-d_His') . '.json', $payload);

    if (!$data || !isset($data['data']['attributes']['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook payload']);
        exit;
    }

    $eventType = $data['data']['attributes']['type'];
    $paymentData = $data['data']['attributes']['data'] ?? [];

    $db = getDB();

    if ($eventType === 'checkout_session.payment.paid') {
        $checkoutId = $paymentData['id'] ?? '';
        $refNumber = $paymentData['attributes']['reference_number'] ?? '';

        // Find order by checkout ID or reference
        $stmt = $db->prepare("SELECT * FROM orders WHERE payment_source_id = ? OR payment_reference = ?");
        $stmt->execute([$checkoutId, $refNumber]);
        $order = $stmt->fetch();

        if ($order) {
            $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$order['id']]);
            logAudit('payment_confirmed', 'order', $order['id'], "Payment confirmed via webhook. Event: {$eventType}");
        }
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// ===================================
// CHECK PAYMENT STATUS
// ===================================
function handlePaymentStatus()
{
    $ref = $_GET['ref'] ?? '';
    $checkPaymongo = isset($_GET['verify']);

    if (empty($ref)) {
        jsonResponse(['success' => false, 'message' => 'Payment reference required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, order_number, total, payment_method, payment_status, payment_reference, payment_source_id FROM orders WHERE payment_reference = ?");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Payment not found'], 404);
    }

    // If payment still pending, check PayMongo directly
    if ($checkPaymongo && $order['payment_status'] === 'pending' && $order['payment_source_id']) {
        global $pmSecret;
        if ($pmSecret) {
            $ch = curl_init("https://api.paymongo.com/v1/checkout_sessions/{$order['payment_source_id']}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Authorization: Basic ' . base64_encode($pmSecret . ':')
                ],
                CURLOPT_TIMEOUT => 15
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            $pmStatus = $result['data']['attributes']['payment_intent']['attributes']['status'] ?? '';
            $pmPayments = $result['data']['attributes']['payments'] ?? [];

            if (!empty($pmPayments) || $pmStatus === 'succeeded') {
                $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$order['id']]);
                $order['payment_status'] = 'paid';
                logAudit('payment_confirmed', 'order', $order['id'], "Payment confirmed via status check");
            }
        }
    }

    jsonResponse([
        'success' => true,
        'order_number' => $order['order_number'],
        'payment_status' => $order['payment_status'] ?? 'pending',
        'amount' => $order['total'],
        'payment_method' => $order['payment_method']
    ]);
}

// ===================================
// HELPER: Get base URL (uses ngrok if configured)
// ===================================
function getBaseUrl()
{
    // Check if ngrok URL is configured in settings
    $ngrokUrl = getSetting('ngrok_url');
    if (!empty($ngrokUrl)) {
        return rtrim($ngrokUrl, '/');
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return "{$protocol}://{$host}";
}
