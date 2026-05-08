<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Track Order";
require_once __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .tracking-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .tracking-search {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            text-align: center;
        }

        .tracking-search h2 {
            margin-bottom: 1rem;
        }

        .tracking-search-form {
            display: flex;
            gap: 0.5rem;
            max-width: 500px;
            margin: 0 auto;
        }

        .tracking-search-form input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }

        .tracking-search-form input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .tracking-result {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .tracking-order-info {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .tracking-order-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .info-item {
            font-size: 0.9rem;
        }

        .info-label {
            color: #999;
            font-size: 0.8rem;
        }

        .info-value {
            font-weight: 600;
        }

        .status-timeline {
            position: relative;
            padding: 1rem 0;
        }

        .timeline-item {
            display: flex;
            gap: 1.5rem;
            padding-bottom: 2rem;
            position: relative;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid #ddd;
            background: white;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .timeline-dot.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .timeline-dot.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            animation: pulse 2s infinite;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 24px;
            bottom: 0;
            width: 2px;
            background: #ddd;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-item.completed::before {
            background: var(--success);
        }

        .timeline-content h4 {
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .timeline-content p {
            font-size: 0.8rem;
            color: #999;
        }

        .timeline-content .time {
            font-size: 0.75rem;
            color: #bbb;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(204, 0, 0, 0.4);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(204, 0, 0, 0);
            }
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="tracking-container">
                <div class="tracking-search">
                    <h2>Track Your Order</h2>
                    <p style="color: #666; margin-bottom: 1.5rem;">Enter your order number to check the status</p>
                    <div class="tracking-search-form">
                        <input type="text" id="order-input" placeholder="e.g. ORD-ABC12345">
                        <button class="btn btn-primary" onclick="trackOrder()">Track</button>
                    </div>
                </div>
                <div id="tracking-result" style="display: none;"></div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const allStatuses = ['Order Placed', 'Payment Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const orderNum = urlParams.get('order');
            if (orderNum) {
                document.getElementById('order-input').value = orderNum;
                trackOrder();
            }
        });

        async function trackOrder() {
            const { getOrderByNumber, formatCurrency } = window.DailyToinks;
            const orderNumber = document.getElementById('order-input').value.trim();
            const resultContainer = document.getElementById('tracking-result');

            if (!orderNumber) {
                window.DailyToinks.showNotification('Please enter an order number', 'error');
                return;
            }

            resultContainer.style.display = 'none';
            resultContainer.innerHTML = '<div style="text-align:center;padding:2rem;">Loading...</div>';
            resultContainer.style.display = 'block';

            const order = await getOrderByNumber(orderNumber);

            if (!order) {
                resultContainer.innerHTML = `
                    <div class="tracking-result" style="text-align:center;padding:2rem;">
                        <h3 style="color:var(--primary-color);margin-bottom:0.5rem;">Order Not Found</h3>
                        <p style="color:#666;">Please check your order number and try again.</p>
                    </div>`;
                return;
            }

            const currentStatusIndex = order.status === 'Cancelled' ? -1 : allStatuses.indexOf(order.status);
            const statusHistory = order.status_history || [];

            // Build history map
            const historyMap = {};
            statusHistory.forEach(h => { historyMap[h.status] = h.created_at; });

            const timelineHTML = allStatuses.map((status, index) => {
                let statusClass = '';
                let icon = '';
                if (order.status === 'Cancelled') {
                    statusClass = '';
                    icon = '';
                } else if (index < currentStatusIndex) {
                    statusClass = 'completed';
                    icon = '✓';
                } else if (index === currentStatusIndex) {
                    statusClass = 'active';
                    icon = '●';
                }

                const time = historyMap[status] ? new Date(historyMap[status]).toLocaleString() : '';

                return `
                    <div class="timeline-item ${statusClass}">
                        <div class="timeline-dot ${statusClass}">${icon}</div>
                        <div class="timeline-content">
                            <h4>${status}</h4>
                            ${time ? `<p class="time">${time}</p>` : '<p>Pending</p>'}
                        </div>
                    </div>`;
            }).join('');

            resultContainer.innerHTML = `
                <div class="tracking-result">
                    <div class="tracking-order-info">
                        <h3>Order: ${order.order_number}</h3>
                        <div class="info-grid">
                            <div class="info-item"><div class="info-label">Status</div><div class="info-value">${order.status}</div></div>
                            <div class="info-item"><div class="info-label">Payment</div><div class="info-value">${order.payment_method}</div></div>
                            <div class="info-item"><div class="info-label">Date Ordered</div><div class="info-value">${new Date(order.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
                            <div class="info-item"><div class="info-label">Total</div><div class="info-value" style="color:var(--primary-color);">${formatCurrency(order.total)}</div></div>
                        </div>
                        ${order.shipping_fullname ? `
                        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                            <div class="info-label">Ship To</div>
                            <div class="info-value">${order.shipping_fullname}, ${order.shipping_address}, ${order.shipping_city} ${order.shipping_postal}</div>
                        </div>` : ''}
                    </div>
                    ${order.status === 'Cancelled' ? `
                        <div style="text-align:center;padding:2rem;background:#FFEBEE;border-radius:var(--radius-sm);">
                            <h3 style="color:#B71C1C;"><span style="font-size:1.5rem;">✕</span> Order Cancelled</h3>
                            ${order.cancelled_at ? `<p style="color:#999;margin-top:0.5rem;">Cancelled on ${new Date(order.cancelled_at).toLocaleString()}</p>` : ''}
                        </div>
                    ` : `
                        <h3 style="margin-bottom:1rem;">Delivery Progress</h3>
                        <div class="status-timeline">${timelineHTML}</div>
                    `}
                    ${order.items && order.items.length > 0 ? `
                        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border-color);">
                            <h3 style="margin-bottom:1rem;">Items in this Order</h3>
                            ${order.items.map(item => `
                                <div style="display:flex;gap:1rem;padding:0.75rem 0;border-bottom:1px solid #f0f0f0;">
                                    <img src="${item.image}" style="width:50px;height:50px;border-radius:4px;object-fit:cover;">
                                    <div style="flex:1;">
                                        <div style="font-weight:500;">${item.product_name}</div>
                                        <div style="font-size:0.85rem;color:#999;">Qty: ${item.quantity} × ${formatCurrency(item.price)}</div>
                                    </div>
                                    <div style="font-weight:600;color:var(--primary-color);">${formatCurrency(item.price * item.quantity)}</div>
                                </div>
                            `).join('')}
                        </div>` : ''}
                </div>`;
        }

        document.getElementById('order-input').addEventListener('keypress', (e) => { if (e.key === 'Enter') trackOrder(); });
    </script>
</body>

</html>