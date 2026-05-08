<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Payment Result";
$status = $_GET['status'] ?? 'unknown';
$ref = $_GET['ref'] ?? '';
$sandbox = isset($_GET['sandbox']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .payment-result {
            max-width: 500px;
            margin: 3rem auto;
            text-align: center;
            background: #fff;
            padding: 3rem 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .payment-icon { font-size: 4rem; margin-bottom: 1rem; }
        .payment-ref { font-family: monospace; background: #f5f5f5; padding: 0.3rem 0.8rem; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="payment-result" id="payment-result">
                <div class="payment-icon" id="payment-icon">⏳</div>
                <h2 id="payment-title">Processing Payment...</h2>
                <p id="payment-message" style="color:#666; margin:1rem 0;"></p>
                <div id="payment-actions" style="margin-top:2rem;"></div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        (async function() {
            const status = '<?= htmlspecialchars($status, ENT_QUOTES) ?>';
            const ref = '<?= htmlspecialchars($ref, ENT_QUOTES) ?>';
            const sandbox = <?= $sandbox ? 'true' : 'false' ?>;

            const icon = document.getElementById('payment-icon');
            const title = document.getElementById('payment-title');
            const message = document.getElementById('payment-message');
            const actions = document.getElementById('payment-actions');

            const { escapeHtml } = window.DailyToinks;

            if (status === 'success') {
                // Confirm payment on server
                if (ref) {
                    try {
                        const res = await fetch(`/normss/api/payment.php?ref=${encodeURIComponent(ref)}`);
                        const data = await res.json();

                        if (data.success) {
                            icon.textContent = '✅';
                            icon.style.color = '#2E7D32';
                            title.textContent = 'Payment Successful!';
                            message.innerHTML = `
                                <p>Your payment has been processed${sandbox ? ' (Sandbox Mode)' : ''}.</p>
                                <p style="margin-top:0.5rem;">Order: <strong>${escapeHtml(data.order_number)}</strong></p>
                                <p>Amount: <strong>₱${parseFloat(data.amount).toLocaleString('en-PH', {minimumFractionDigits:2})}</strong></p>
                                <p>Method: <strong>${escapeHtml(data.payment_method)}</strong></p>
                                ${ref ? `<p style="margin-top:0.75rem;">Reference: <span class="payment-ref">${escapeHtml(ref)}</span></p>` : ''}
                            `;
                            actions.innerHTML = `
                                <a href="order-tracking.php?order=${escapeHtml(data.order_number)}" class="btn btn-primary" style="margin-right:0.5rem;">Track Order</a>
                                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                            `;
                        } else {
                            showError('Could not verify payment. Please contact support.');
                        }
                    } catch (e) {
                        showError('Network error while verifying payment.');
                    }
                } else {
                    showError('No payment reference provided.');
                }
            } else if (status === 'failed') {
                icon.textContent = '❌';
                icon.style.color = '#B71C1C';
                title.textContent = 'Payment Failed';
                message.textContent = 'Your payment could not be processed. Please try again.';
                actions.innerHTML = `
                    <a href="order-history.php" class="btn btn-primary" style="margin-right:0.5rem;">My Orders</a>
                    <a href="index.php" class="btn btn-secondary">Go Home</a>
                `;
            } else {
                showError('Unknown payment status.');
            }

            function showError(msg) {
                icon.textContent = '⚠️';
                title.textContent = 'Payment Error';
                message.textContent = msg;
                actions.innerHTML = `<a href="index.php" class="btn btn-secondary">Go Home</a>`;
            }
        })();
    </script>
</body>

</html>
