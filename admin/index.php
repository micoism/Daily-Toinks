<?php
$pageTitle = "Dashboard";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin', 'manager', 'rider']);
$user = getCurrentUser();
$isRider = $user['role'] === 'rider';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title><?= $isRider ? 'Rider Dashboard' : 'Dashboard' ?> - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">

                <?php if ($isRider): ?>
                <!-- ============================== -->
                <!-- RIDER DASHBOARD -->
                <!-- ============================== -->
                <div class="stats-grid" id="stats-grid">
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon" style="background:#E3F2FD;">📋</div></div>
                        <div class="stat-card-value" id="stat-available">--</div>
                        <div class="stat-card-label">Available Orders</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon" style="background:#FFF3E0;">🚚</div></div>
                        <div class="stat-card-value" id="stat-active">--</div>
                        <div class="stat-card-label">Active Deliveries</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon" style="background:#E8F5E9;">✅</div></div>
                        <div class="stat-card-value" id="stat-completed">--</div>
                        <div class="stat-card-label">Completed</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon" style="background:#F3E5F5;">⭐</div></div>
                        <div class="stat-card-value" id="stat-ratings">--</div>
                        <div class="stat-card-label">Ratings Given</div>
                    </div>
                </div>

                <div style="display:flex;gap:1rem;margin-bottom:2rem;">
                    <a href="/normss/admin/deliveries.php" class="topbar-btn topbar-btn-primary" style="text-decoration:none;">🚚 Go to My Deliveries</a>
                    <a href="/normss/admin/deliveries.php?tab=available" class="topbar-btn topbar-btn-secondary" style="text-decoration:none;">📋 Browse Available Orders</a>
                </div>

                <!-- Rider's Recent Deliveries -->
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">My Recent Deliveries</h3>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Claimed</th></tr>
                        </thead>
                        <tbody id="recent-deliveries">
                            <tr><td colspan="5" style="text-align:center;padding:2rem;color:#999;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <?php else: ?>
                <!-- ============================== -->
                <!-- ADMIN / MANAGER DASHBOARD -->
                <!-- ============================== -->
                <div class="stats-grid" id="stats-grid">
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon orders">📦</div></div>
                        <div class="stat-card-value" id="stat-orders">--</div>
                        <div class="stat-card-label">Total Orders</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon revenue">💰</div></div>
                        <div class="stat-card-value" id="stat-revenue">--</div>
                        <div class="stat-card-label">Total Revenue</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon users">👥</div></div>
                        <div class="stat-card-value" id="stat-users">--</div>
                        <div class="stat-card-label">Total Users</div>
                    </div>
                    <div class="stat-card animate-in">
                        <div class="stat-card-header"><div class="stat-card-icon products">🏷️</div></div>
                        <div class="stat-card-value" id="stat-products">--</div>
                        <div class="stat-card-label">Active Products</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="admin-table-container">
                        <div class="admin-table-header"><h3 class="admin-table-title">Orders by Status</h3></div>
                        <div style="padding: 1.5rem;" id="status-chart"><div class="empty-state"><p>Loading...</p></div></div>
                    </div>
                    <div class="admin-table-container">
                        <div class="admin-table-header"><h3 class="admin-table-title">Top Selling Products</h3></div>
                        <div style="padding: 0;" id="top-products"><div class="empty-state"><p>Loading...</p></div></div>
                    </div>
                </div>

                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">Recent Orders</h3>
                        <a href="/normss/admin/orders.php" class="topbar-btn topbar-btn-secondary">View All</a>
                    </div>
                    <table class="admin-table">
                        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Rider</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody id="recent-orders">
                            <tr><td colspan="6" style="text-align:center;padding:2rem;color:#999;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const formatCurrency = (amount) => `₱${parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        const IS_RIDER = <?= $isRider ? 'true' : 'false' ?>;

        function getStatusBadge(status) {
            const map = {
                'Order Placed': 'pending', 'Payment Confirmed': 'confirmed',
                'Packed': 'packed', 'Shipped': 'shipped',
                'Out for Delivery': 'delivery', 'Delivered': 'delivered',
                'Not Delivered': 'returned',
                'Returned': 'returned', 'Cancelled': 'cancelled'
            };
            const label = (status && String(status).trim()) ? status : 'Unknown';
            const cls = map[label] || 'pending';
            return `<span class="badge badge-${cls}">${label}</span>`;
        }

        async function loadDashboard() {
            try {
                const res = await fetch('/normss/api/dashboard.php');
                const data = await res.json();
                if (!data.success) throw new Error(data.message);

                if (IS_RIDER) {
                    // Rider dashboard
                    const r = data.rider_stats;
                    document.getElementById('stat-available').textContent = r.available_orders;
                    document.getElementById('stat-active').textContent = r.active_deliveries;
                    document.getElementById('stat-completed').textContent = r.completed_deliveries;
                    document.getElementById('stat-ratings').textContent = r.ratings_given;

                    // Load recent deliveries
                    const delRes = await fetch('/normss/api/orders.php?my_deliveries=1');
                    const delData = await delRes.json();
                    const tbody = document.getElementById('recent-deliveries');
                    if (delData.orders && delData.orders.length > 0) {
                        tbody.innerHTML = delData.orders.slice(0, 10).map(o => `
                            <tr>
                                <td><span style="font-weight:600;color:var(--primary-color);">${o.order_number}</span></td>
                                <td>${o.user_name || 'Guest'}</td>
                                <td style="font-weight:700;">${formatCurrency(o.total)}</td>
                                <td>${getStatusBadge(o.status)}</td>
                                <td style="color:#999;font-size:0.85rem;">${o.rider_claimed_at ? new Date(o.rider_claimed_at).toLocaleString() : '-'}</td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No deliveries yet. Browse available orders!</td></tr>';
                    }

                } else {
                    // Admin/Manager dashboard
                    const s = data.stats;
                    document.getElementById('stat-orders').textContent = s.total_orders.toLocaleString();
                    document.getElementById('stat-revenue').textContent = formatCurrency(s.total_revenue);
                    document.getElementById('stat-users').textContent = s.total_users.toLocaleString();
                    document.getElementById('stat-products').textContent = s.total_products.toLocaleString();

                    // Orders by Status
                    const statusContainer = document.getElementById('status-chart');
                    if (data.orders_by_status.length > 0) {
                        statusContainer.innerHTML = data.orders_by_status.map(s => `
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid #f0f0f0;">
                                <div>${getStatusBadge(s.status)}</div>
                                <div style="font-weight:700;font-size:1.1rem;">${s.count}</div>
                            </div>
                        `).join('');
                    } else {
                        statusContainer.innerHTML = '<div class="empty-state"><p>No orders yet</p></div>';
                    }

                    // Top Products
                    const topContainer = document.getElementById('top-products');
                    if (data.top_products.length > 0) {
                        topContainer.innerHTML = '<table class="admin-table"><tbody>' + data.top_products.map((p, i) => `
                            <tr>
                                <td style="width:40px;font-weight:700;color:#999;">#${i + 1}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <img src="${p.image}" alt="" style="width:36px;height:36px;border-radius:4px;object-fit:cover;">
                                        <span style="font-weight:500;">${p.product_name}</span>
                                    </div>
                                </td>
                                <td style="text-align:right;font-weight:600;">${p.total_sold} sold</td>
                            </tr>
                        `).join('') + '</tbody></table>';
                    } else {
                        topContainer.innerHTML = '<div class="empty-state"><p>No sales data yet</p></div>';
                    }

                    // Recent Orders
                    const tbody = document.getElementById('recent-orders');
                    if (data.recent_orders.length > 0) {
                        tbody.innerHTML = data.recent_orders.map(o => `
                            <tr>
                                <td><a href="/normss/admin/orders.php?id=${o.id}" style="color:var(--primary-color);font-weight:600;">${o.order_number}</a></td>
                                <td>${o.user_name || 'Guest'}</td>
                                <td style="font-weight:600;">${formatCurrency(o.total)}</td>
                                <td>${o.rider_name || '<span style="color:#999;">-</span>'}</td>
                                <td>${getStatusBadge(o.status)}</td>
                                <td style="color:#999;">${new Date(o.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No orders yet</td></tr>';
                    }
                }
            } catch (err) {
                console.error('Dashboard load error:', err);
            }
        }

        loadDashboard();
    </script>
</body>

</html>