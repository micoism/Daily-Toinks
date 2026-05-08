<?php
$pageTitle = "Order Management";
require_once __DIR__ . '/../config/auth.php';
requireRole(['manager']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Orders - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', 'Inter', sans-serif;
            margin: 0;
        }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <!-- Order Detail View (hidden by default) -->
                <div id="order-detail" style="display:none;">
                    <div style="margin-bottom:1rem;">
                        <button class="topbar-btn topbar-btn-secondary" onclick="showList()">← Back to Orders</button>
                    </div>
                    <div id="order-detail-content"></div>
                </div>

                <!-- Orders List -->
                <div id="orders-list">
                    <div class="admin-table-container">
                        <div class="admin-table-header">
                            <h3 class="admin-table-title">All Orders</h3>
                            <div class="filter-bar">
                                <select id="status-filter">
                                    <option value="">All Statuses</option>
                                    <option>Order Placed</option>
                                    <option>Payment Confirmed</option>
                                    <option>Packed</option>
                                    <option>Shipped</option>
                                    <option>Out for Delivery</option>
                                    <option>Delivered</option>
                                    <option>Returned</option>
                                    <option>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Rider</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody">
                                <tr>
                                    <td colspan="9" style="text-align:center;padding:2rem;color:#999;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        const formatCurrency = (amount) => `₱${parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;

        function getStatusBadge(status) {
            const map = {
                'Order Placed': 'pending', 'Payment Confirmed': 'confirmed',
                'Packed': 'packed', 'Shipped': 'shipped',
                'Out for Delivery': 'delivery', 'Delivered': 'delivered',
                'Returned': 'returned', 'Cancelled': 'cancelled'
            };
            return `<span class="badge badge-${map[status] || 'pending'}">${status}</span>`;
        }

        async function loadOrders() {
            const status = document.getElementById('status-filter').value;
            let url = '/normss/api/orders.php?';
            if (status) url += `status=${encodeURIComponent(status)}`;

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById('orders-tbody');

            if (!data.orders || data.orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No orders found</td></tr>';
                return;
            }

            tbody.innerHTML = data.orders.map(o => `
                <tr>
                    <td><span style="font-weight:600;color:var(--primary-color);cursor:pointer;" onclick="viewOrder(${o.id})">${o.order_number}</span></td>
                    <td>${o.user_name || 'Guest'}</td>
                    <td>${o.items ? o.items.length : 0} item(s)</td>
                    <td style="font-weight:700;">${formatCurrency(o.total)}</td>
                    <td>${o.payment_method}</td>
                    <td>${getStatusBadge(o.status)}</td>
                    <td>${o.rider_name || '<span style="color:#ccc;">—</span>'}</td>
                    <td style="color:#999;font-size:0.85rem;">${new Date(o.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn" onclick="viewOrder(${o.id})">View</button>
                        ${getNextStepBtn(o)}
                    </td>
                </tr>
            `).join('');
        }

        async function viewOrder(id) {
            const res = await fetch(`/normss/api/orders.php?id=${id}`);
            const data = await res.json();
            if (!data.success) return alert('Order not found');

            const o = data.order;
            document.getElementById('orders-list').style.display = 'none';
            document.getElementById('order-detail').style.display = 'block';

            document.getElementById('order-detail-content').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <div class="admin-table-container" style="padding:1.5rem;">
                        <h3 style="margin-bottom:1rem;font-weight:700;">Order Information</h3>
                        <div style="display:grid;gap:0.5rem;font-size:0.9rem;">
                            <div><strong>Order #:</strong> ${o.order_number}</div>
                            <div><strong>Status:</strong> ${getStatusBadge(o.status)}</div>
                            <div><strong>Payment:</strong> ${o.payment_method}</div>
                            <div><strong>Total:</strong> <span style="font-weight:700;color:var(--primary-color);">${formatCurrency(o.total)}</span></div>
                            <div><strong>Date:</strong> ${new Date(o.created_at).toLocaleString()}</div>
                            ${o.user_name ? `<div><strong>Customer:</strong> ${o.user_name} (${o.user_email})</div>` : ''}
                        </div>
                    </div>
                    <div class="admin-table-container" style="padding:1.5rem;">
                        <h3 style="margin-bottom:1rem;font-weight:700;">Shipping Address</h3>
                        <div style="font-size:0.9rem;line-height:1.8;">
                            <div><strong>${o.shipping_fullname}</strong></div>
                            <div>${o.shipping_phone}</div>
                            <div>${o.shipping_address}</div>
                            <div>${o.shipping_city}, ${o.shipping_postal}</div>
                        </div>
                    </div>
                </div>
                <div class="admin-table-container" style="margin-top:1.5rem;">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">Order Items</h3>
                    </div>
                    <table class="admin-table">
                        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            ${o.items.map(i => `
                                <tr>
                                    <td><div style="display:flex;align-items:center;gap:0.75rem;">
                                        <img src="${i.image}" alt="" style="width:40px;height:40px;border-radius:4px;object-fit:cover;">
                                        <span>${i.product_name}</span>
                                    </div></td>
                                    <td>${formatCurrency(i.price)}</td>
                                    <td>${i.quantity}</td>
                                    <td style="font-weight:600;">${formatCurrency(i.price * i.quantity)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ${o.status_history && o.status_history.length > 0 ? `
                <div class="admin-table-container" style="margin-top:1.5rem;padding:1.5rem;">
                    <h3 style="margin-bottom:1rem;font-weight:700;">Status History</h3>
                    ${o.status_history.map(h => `
                        <div style="display:flex;align-items:center;gap:1rem;padding:0.5rem 0;border-bottom:1px solid #f0f0f0;">
                            ${getStatusBadge(h.status)}
                            <span style="color:#999;font-size:0.85rem;">${new Date(h.created_at).toLocaleString()}</span>
                        </div>
                    `).join('')}
                </div>` : ''}
            `;

            // Check URL param
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('id')) {
                // Already viewing from URL
            }
        }

        function showList() {
            document.getElementById('orders-list').style.display = 'block';
            document.getElementById('order-detail').style.display = 'none';
            window.history.replaceState({}, '', '/normss/admin/orders.php');
        }

        function getNextStepBtn(o) {
            if (o.status === 'Order Placed') return `<button class="action-btn success" onclick="advanceOrder(${o.id}, 'Confirm Payment')">Confirm Payment</button>`;
            if (o.status === 'Payment Confirmed') return `<button class="action-btn success" onclick="advanceOrder(${o.id}, 'Mark as Packed')">Mark as Packed</button>`;
            if (o.status === 'Packed') return `<span style="color:#999;font-size:0.8rem;">Waiting for rider</span>`;
            return '';
        }

        async function advanceOrder(id, label) {
            if (!confirm(`${label} for this order?`)) return;

            const res = await fetch('/normss/api/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id), action: 'update_status' })
            });

            const data = await res.json();
            if (data.success) {
                loadOrders();
            } else {
                alert(data.message);
            }
        }

        document.getElementById('status-filter').addEventListener('change', loadOrders);

        // Init — check for URL param
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('id');
            if (orderId) {
                viewOrder(parseInt(orderId));
            }
            loadOrders();
        });
    </script>
</body>

</html>