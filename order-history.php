<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pageTitle = "Order History";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .orders-container {
            margin: 2rem 0;
        }

        .order-card {
            background: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .order-card:hover {
            box-shadow: var(--shadow-sm);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-number {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .order-date {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .status-placed {
            background: #FFF3E0;
            color: #E65100;
        }

        .status-confirmed {
            background: #E0F2F1;
            color: #00695C;
        }

        .status-packed {
            background: #E3F2FD;
            color: #1565C0;
        }

        .status-shipped {
            background: #F3E5F5;
            color: #7B1FA2;
        }

        .status-delivery {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-delivered {
            background: #E8F5E9;
            color: #1B5E20;
        }

        .status-cancelled {
            background: #FFEBEE;
            color: #B71C1C;
        }

        .order-items {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            overflow-x: auto;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 200px;
        }

        .order-item img {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
        }

        .order-item-name {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .order-item-qty {
            font-size: 0.75rem;
            color: #999;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .empty-orders {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: var(--radius-md);
        }
        .order-confirm-btns {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed var(--border-color);
        }
        .order-confirm-btns button {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-received {
            background: #E8F5E9;
            color: #2E7D32;
        }
        .btn-received:hover { background: #C8E6C9; }
        .btn-not-received {
            background: #FFEBEE;
            color: #C62828;
        }
        .btn-not-received:hover { background: #FFCDD2; }
        .status-not-delivered {
            background: #FFEBEE;
            color: #B71C1C;
        }
        .order-action-btns {
            display: flex; gap: 0.5rem; margin-top: 0.75rem;
            padding-top: 0.75rem; border-top: 1px dashed var(--border-color); flex-wrap: wrap;
        }
        .order-action-btns a, .order-action-btns button {
            padding: 0.45rem 1rem; border: none; border-radius: 999px;
            font-weight: 600; font-size: 0.8rem; cursor: pointer;
            font-family: inherit; text-decoration: none; display: inline-block;
        }
        .btn-review { background: #FFF8E1; color: #F57F17; }
        .btn-review:hover { background: #FFF3C4; }
        .btn-report { background: #E3F2FD; color: #1565C0; }
        .btn-report:hover { background: #BBDEFB; }
        .ticket-modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 9999;
            display: none; align-items: center; justify-content: center;
        }
        .ticket-modal-overlay.active { display: flex; }
        .ticket-modal {
            background: #fff; border-radius: 12px; padding: 2rem;
            max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;
        }
        .ticket-modal h3 { margin-bottom: 1rem; }
        .ticket-modal label { display: block; font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 0.3rem; margin-top: 0.75rem; }
        .ticket-modal input, .ticket-modal textarea, .ticket-modal select {
            width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #ddd;
            border-radius: 8px; font-family: inherit; font-size: 0.9rem;
        }
        .ticket-modal textarea { min-height: 100px; resize: vertical; }
        .ticket-modal-btns { display: flex; gap: 0.5rem; margin-top: 1.25rem; justify-content: flex-end; }
        .ticket-modal-btns button {
            padding: 0.6rem 1.5rem; border: none; border-radius: 999px;
            font-weight: 700; font-size: 0.85rem; cursor: pointer; font-family: inherit;
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <!-- Report Issue Modal -->
    <div class="ticket-modal-overlay" id="ticket-modal">
        <div class="ticket-modal">
            <h3>Report an Issue</h3>
            <input type="hidden" id="ticket-order-id">
            <input type="hidden" id="ticket-product-id">
            <div id="ticket-order-info" style="font-size:0.85rem;color:#666;margin-bottom:0.5rem;"></div>
            <label>Subject</label>
            <input type="text" id="ticket-subject" placeholder="e.g. Item arrived damaged">
            <label>Describe the issue</label>
            <textarea id="ticket-message" placeholder="Please describe the problem you encountered..."></textarea>
            <div id="ticket-msg" style="margin-top:0.5rem;font-size:0.85rem;"></div>
            <div class="ticket-modal-btns">
                <button onclick="closeTicketModal()" style="background:#f0f0f0;color:#333;">Cancel</button>
                <button onclick="submitTicket()" style="background:var(--primary-color);color:#fff;">Submit Ticket</button>
            </div>
        </div>
    </div>

    <main>
        <div class="container">
            <h1 class="page-title">My Orders</h1>
            <div class="orders-container" id="orders-container">
                <div style="text-align:center;padding:2rem;color:#999;">Loading orders...</div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const { getOrders, formatCurrency } = window.DailyToinks;
            const container = document.getElementById('orders-container');
            const orders = await getOrders();

            function getStatusClass(status) {
                const map = {
                    'Order Placed': 'placed', 'Payment Confirmed': 'confirmed',
                    'Packed': 'packed', 'Shipped': 'shipped',
                    'Out for Delivery': 'delivery', 'Delivered': 'delivered',
                    'Not Delivered': 'not-delivered', 'Cancelled': 'cancelled'
                };
                return map[status] || 'placed';
            }

            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-orders">
                        <h2 style="margin-bottom: 1rem;">📦 No orders yet</h2>
                        <p>You haven't placed any orders yet.</p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 1.5rem; display:inline-block;">Start Shopping</a>
                    </div>`;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-number">${order.order_number}</span>
                            <span class="order-date">&nbsp;• ${new Date(order.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                        <span class="order-status status-${getStatusClass(order.status)}">${order.status}</span>
                    </div>
                    <div class="order-items">
                        ${(order.items || []).map(item => `
                            <div class="order-item">
                                <img src="${item.image}" alt="${item.product_name}">
                                <div>
                                    <div class="order-item-name">${item.product_name}</div>
                                    <div class="order-item-qty">Qty: ${item.quantity} × ${formatCurrency(item.price)}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="order-footer">
                        <div>
                            <span style="color:#666;font-size:0.85rem;">Payment: ${order.payment_method}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <span class="order-total">Total: ${formatCurrency(order.total)}</span>
                            <a href="order-tracking.php?order=${order.order_number}" class="btn btn-primary" style="padding:0.5rem 1rem;font-size:0.85rem;">Track Order</a>
                        </div>
                    </div>
                    ${order.status === 'Out for Delivery' ? `
                    <div class="order-confirm-btns">
                        <span style="font-size:0.85rem;color:#555;align-self:center;margin-right:0.5rem;">Did you receive this order?</span>
                        <button class="btn-received" onclick="confirmDelivery(${order.id}, true)">Yes, Received</button>
                        <button class="btn-not-received" onclick="notReceived(${order.id})">Not Received</button>
                    </div>` : ''}
                    ${order.status === 'Delivered' || order.status === 'Not Delivered' ? `
                    <div class="order-action-btns">
                        ${order.status === 'Delivered' ? (order.items || []).map(item => `
                            <a href="product-details.php?id=${item.product_id}#reviews-section" class="btn-review">Review ${item.product_name.length > 20 ? item.product_name.substring(0,20)+'...' : item.product_name}</a>
                        `).join('') : ''}
                        <button class="btn-report" onclick="openTicketModal(${order.id})">Report Issue</button>
                    </div>` : ''}
                </div>
            `).join('');

            // Store orders for later lookup
            const ordersMap = {};
            orders.forEach(o => { ordersMap[o.id] = o; });

            window.openTicketModal = function(orderId) {
                const order = ordersMap[orderId];
                document.getElementById('ticket-order-id').value = orderId;
                document.getElementById('ticket-order-info').textContent = order ? 'Order: ' + order.order_number : '';
                document.getElementById('ticket-subject').value = '';
                document.getElementById('ticket-message').value = '';
                document.getElementById('ticket-msg').innerHTML = '';
                document.getElementById('ticket-modal').classList.add('active');
            };

            window.notReceived = async function(orderId) {
                if (!confirm('Report that you have NOT received this order? This will open a support ticket.')) return;
                try {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                    const res = await fetch('/normss/api/orders.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ id: orderId, action: 'customer_confirm', delivered: false })
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.DailyToinks.showNotification('Order marked as Not Received', 'success');
                        openTicketModal(orderId);
                        document.getElementById('ticket-subject').value = 'Order Not Received';
                    } else {
                        window.DailyToinks.showNotification(data.message, 'error');
                    }
                } catch (e) {
                    window.DailyToinks.showNotification('Error updating order', 'error');
                }
            };

            window.closeTicketModal = function() {
                document.getElementById('ticket-modal').classList.remove('active');
            };

            window.submitTicket = async function() {
                const orderId = document.getElementById('ticket-order-id').value;
                const subject = document.getElementById('ticket-subject').value.trim();
                const message = document.getElementById('ticket-message').value.trim();
                const msgEl = document.getElementById('ticket-msg');

                if (!subject || !message) {
                    msgEl.innerHTML = '<span style="color:red;">Subject and message are required</span>';
                    return;
                }

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.content : '';
                try {
                    const res = await fetch('/normss/api/tickets.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ order_id: parseInt(orderId), subject, message })
                    });
                    const data = await res.json();
                    if (data.success) {
                        msgEl.innerHTML = `<span style="color:green;">Ticket ${data.ticket_number} created! <a href="my-tickets.php" style="color:var(--primary-color);font-weight:600;">View Tickets</a></span>`;
                        document.getElementById('ticket-subject').value = '';
                        document.getElementById('ticket-message').value = '';
                    } else {
                        msgEl.innerHTML = `<span style="color:red;">${data.message}</span>`;
                    }
                } catch (e) {
                    msgEl.innerHTML = '<span style="color:red;">Network error</span>';
                }
            };

            window.confirmDelivery = async function(orderId, delivered) {
                if (!confirm('Confirm that you received this order?')) return;

                try {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                    const res = await fetch('/normss/api/orders.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ id: orderId, action: 'customer_confirm', delivered: true })
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.DailyToinks.showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        window.DailyToinks.showNotification(data.message, 'error');
                    }
                } catch (e) {
                    window.DailyToinks.showNotification('Error confirming delivery', 'error');
                }
            };
        });
    </script>
</body>

</html>