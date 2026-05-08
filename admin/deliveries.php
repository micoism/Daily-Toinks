<?php
$pageTitle = "My Deliveries";
require_once __DIR__ . '/../config/auth.php';
requireRole(['rider']);
$user = getCurrentUser();
$tab = $_GET['tab'] ?? 'my';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Deliveries - DailyToinks Rider</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
        .tab-bar { display: flex; gap: 0; border-bottom: 2px solid #eee; margin-bottom: 1.5rem; }
        .tab-btn { padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.9rem; border: none; background: none; cursor: pointer; color: #666; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-btn:hover { color: var(--primary-color); }
        .delivery-card { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; transition: box-shadow 0.2s; }
        .delivery-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .delivery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .delivery-order-num { font-weight: 700; color: var(--primary-color); font-size: 1rem; }
        .delivery-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.85rem; color: #555; margin-bottom: 1rem; }
        .delivery-info strong { color: #222; }
        .delivery-items { font-size: 0.8rem; color: #888; margin-bottom: 1rem; }
        .delivery-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .delivery-actions .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.8rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.15s; }
        .btn-claim { background: var(--primary-color); color: #fff; }
        .btn-claim:hover { background: var(--primary-hover); }
        .btn-ofd { background: #E65100; color: #fff; }
        .btn-ofd:hover { background: #BF360C; }
        .btn-delivered { background: #2E7D32; color: #fff; }
        .btn-delivered:hover { background: #1B5E20; }
        .btn-returned { background: #616161; color: #fff; }
        .btn-returned:hover { background: #424242; }
        .btn-rate { background: #7B1FA2; color: #fff; }
        .btn-rate:hover { background: #6A1B9A; }
        .star-rating { display: inline-flex; gap: 2px; cursor: pointer; }
        .star-rating span { font-size: 1.5rem; color: #ddd; transition: color 0.1s; }
        .star-rating span.active { color: #FFC107; }
        .star-rating span:hover, .star-rating span:hover ~ span { color: #FFC107; }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <!-- Tabs -->
                <div class="tab-bar">
                    <button class="tab-btn <?= $tab === 'my' ? 'active' : '' ?>" onclick="switchTab('my')">🚚 My Deliveries</button>
                    <button class="tab-btn <?= $tab === 'available' ? 'active' : '' ?>" onclick="switchTab('available')">📋 Available Orders</button>
                </div>

                <!-- My Deliveries Tab -->
                <div id="tab-my" style="display: <?= $tab === 'my' ? 'block' : 'none' ?>;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <h3 style="margin:0;font-weight:700;">My Deliveries</h3>
                        <select id="my-status-filter" onchange="loadMyDeliveries()" style="padding:0.5rem;border:1px solid #ddd;border-radius:6px;font-size:0.85rem;">
                            <option value="">All</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Out for Delivery">Out for Delivery</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Returned">Returned</option>
                        </select>
                    </div>
                    <div id="my-deliveries-list">
                        <div class="empty-state" style="padding:3rem;text-align:center;color:#999;">Loading...</div>
                    </div>
                </div>

                <!-- Available Orders Tab -->
                <div id="tab-available" style="display: <?= $tab === 'available' ? 'block' : 'none' ?>;">
                    <h3 style="margin:0 0 1rem;font-weight:700;">Available Orders to Claim</h3>
                    <div id="available-orders-list">
                        <div class="empty-state" style="padding:3rem;text-align:center;color:#999;">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Customer Modal -->
    <div class="modal-overlay" id="rate-modal">
        <div class="modal" style="max-width:420px;">
            <div class="modal-header">
                <h3 class="modal-title">Rate Customer</h3>
                <button class="modal-close" onclick="closeRateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rate-order-id">
                <p style="margin:0 0 1rem;color:#555;font-size:0.9rem;">How was this customer? Rate your delivery experience.</p>
                <div style="text-align:center;margin-bottom:1rem;">
                    <div class="star-rating" id="star-rating">
                        <span data-val="1" onclick="setRating(1)">★</span>
                        <span data-val="2" onclick="setRating(2)">★</span>
                        <span data-val="3" onclick="setRating(3)">★</span>
                        <span data-val="4" onclick="setRating(4)">★</span>
                        <span data-val="5" onclick="setRating(5)">★</span>
                    </div>
                    <div id="rating-label" style="font-size:0.85rem;color:#999;margin-top:0.5rem;">Select a rating</div>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Comment (optional)</label>
                    <textarea id="rate-comment" class="admin-form-textarea" rows="3" placeholder="Any notes about this customer..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="topbar-btn topbar-btn-secondary" onclick="closeRateModal()">Cancel</button>
                <button class="topbar-btn topbar-btn-primary" onclick="submitRating()">Submit Rating</button>
            </div>
        </div>
    </div>

    <script>
        const formatCurrency = (amount) => `₱${parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        let currentRating = 0;

        function getStatusBadge(status) {
            const map = {
                'Order Placed': 'pending', 'Payment Confirmed': 'confirmed',
                'Packed': 'packed', 'Shipped': 'shipped',
                'Out for Delivery': 'delivery', 'Delivered': 'delivered',
                'Returned': 'returned', 'Cancelled': 'cancelled'
            };
            return `<span class="badge badge-${map[status] || 'pending'}">${status}</span>`;
        }

        function switchTab(tab) {
            document.getElementById('tab-my').style.display = tab === 'my' ? 'block' : 'none';
            document.getElementById('tab-available').style.display = tab === 'available' ? 'block' : 'none';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            if (tab === 'my') loadMyDeliveries();
            else loadAvailableOrders();
            history.replaceState({}, '', `/normss/admin/deliveries.php?tab=${tab}`);
        }

        // === AVAILABLE ORDERS ===
        async function loadAvailableOrders() {
            const container = document.getElementById('available-orders-list');
            container.innerHTML = '<div class="empty-state" style="padding:2rem;text-align:center;color:#999;">Loading...</div>';

            const res = await fetch('/normss/api/orders.php?available=1');
            const data = await res.json();

            if (!data.orders || data.orders.length === 0) {
                container.innerHTML = '<div class="admin-table-container" style="padding:3rem;text-align:center;"><p style="color:#999;font-size:1rem;">No orders available right now.</p><p style="color:#bbb;font-size:0.85rem;">Check back later — new packed orders will appear here.</p></div>';
                return;
            }

            container.innerHTML = data.orders.map(o => `
                <div class="delivery-card">
                    <div class="delivery-header">
                        <span class="delivery-order-num">${o.order_number}</span>
                        ${getStatusBadge(o.status)}
                    </div>
                    <div class="delivery-info">
                        <div><strong>Customer:</strong> ${o.user_name || 'Guest'}</div>
                        <div><strong>Total:</strong> ${formatCurrency(o.total)}</div>
                        <div><strong>Address:</strong> ${o.shipping_address}, ${o.shipping_city}</div>
                        <div><strong>Phone:</strong> ${o.shipping_phone}</div>
                    </div>
                    <div class="delivery-items">${o.items.length} item(s): ${o.items.map(i => i.product_name).join(', ')}</div>
                    <div class="delivery-actions">
                        <button class="btn-sm btn-claim" onclick="claimOrder(${o.id})">🚚 Take This Order</button>
                    </div>
                </div>
            `).join('');
        }

        async function claimOrder(id) {
            if (!confirm('Claim this order for delivery? It will be assigned to you.')) return;

            const res = await fetch('/normss/api/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'claim' })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                loadAvailableOrders();
            } else {
                alert(data.message);
            }
        }

        // === MY DELIVERIES ===
        async function loadMyDeliveries() {
            const container = document.getElementById('my-deliveries-list');
            const status = document.getElementById('my-status-filter').value;
            container.innerHTML = '<div class="empty-state" style="padding:2rem;text-align:center;color:#999;">Loading...</div>';

            let url = '/normss/api/orders.php?my_deliveries=1';
            if (status) url += `&status=${encodeURIComponent(status)}`;

            const res = await fetch(url);
            const data = await res.json();

            if (!data.orders || data.orders.length === 0) {
                container.innerHTML = '<div class="admin-table-container" style="padding:3rem;text-align:center;"><p style="color:#999;font-size:1rem;">No deliveries found.</p></div>';
                return;
            }

            container.innerHTML = data.orders.map(o => {
                let actionBtns = '';
                if (o.status === 'Shipped') {
                    actionBtns = `<button class="btn-sm btn-ofd" onclick="updateDelivery(${o.id})">📍 Mark Out for Delivery</button>`;
                } else if (o.status === 'Out for Delivery') {
                    actionBtns = `<span style="color:#888;font-size:0.85rem;">Waiting for customer confirmation</span>`;
                } else if (o.status === 'Delivered' && o.user_id) {
                    actionBtns = `<button class="btn-sm btn-rate" onclick="openRateModal(${o.id})">⭐ Rate Customer</button>`;
                }

                return `
                    <div class="delivery-card">
                        <div class="delivery-header">
                            <span class="delivery-order-num">${o.order_number}</span>
                            ${getStatusBadge(o.status)}
                        </div>
                        <div class="delivery-info">
                            <div><strong>Customer:</strong> ${o.user_name || 'Guest'}</div>
                            <div><strong>Total:</strong> ${formatCurrency(o.total)}</div>
                            <div><strong>Address:</strong> ${o.shipping_address}, ${o.shipping_city} ${o.shipping_postal}</div>
                            <div><strong>Phone:</strong> ${o.shipping_phone}</div>
                            <div><strong>Claimed:</strong> ${o.rider_claimed_at ? new Date(o.rider_claimed_at).toLocaleString() : '-'}</div>
                            <div><strong>Payment:</strong> ${o.payment_method}</div>
                        </div>
                        <div class="delivery-items">${o.items.length} item(s): ${o.items.map(i => `${i.product_name} x${i.quantity}`).join(', ')}</div>
                        <div class="delivery-actions">${actionBtns}</div>
                    </div>
                `;
            }).join('');
        }

        async function updateDelivery(id) {
            if (!confirm('Mark this order as Out for Delivery?')) return;

            const res = await fetch('/normss/api/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'rider_update' })
            });
            const data = await res.json();
            if (data.success) {
                loadMyDeliveries();
            } else {
                alert(data.message);
            }
        }

        // === RATING ===
        function setRating(val) {
            currentRating = val;
            const labels = ['', 'Poor', 'Below Average', 'Average', 'Good', 'Excellent'];
            document.getElementById('rating-label').textContent = labels[val];
            document.querySelectorAll('#star-rating span').forEach(s => {
                s.classList.toggle('active', parseInt(s.dataset.val) <= val);
            });
        }

        function openRateModal(orderId) {
            document.getElementById('rate-order-id').value = orderId;
            document.getElementById('rate-comment').value = '';
            currentRating = 0;
            document.querySelectorAll('#star-rating span').forEach(s => s.classList.remove('active'));
            document.getElementById('rating-label').textContent = 'Select a rating';
            document.getElementById('rate-modal').classList.add('active');
        }

        function closeRateModal() {
            document.getElementById('rate-modal').classList.remove('active');
        }

        async function submitRating() {
            if (currentRating === 0) return alert('Please select a rating');

            const orderId = parseInt(document.getElementById('rate-order-id').value);
            const comment = document.getElementById('rate-comment').value.trim();

            const res = await fetch('/normss/api/ratings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, rating: currentRating, comment })
            });

            const data = await res.json();
            if (data.success) {
                closeRateModal();
                alert('Customer rated successfully!');
                loadMyDeliveries();
            } else {
                alert(data.message);
            }
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            loadMyDeliveries();
            loadAvailableOrders();
        });
    </script>
</body>

</html>
