<?php
require_once __DIR__ . '/../../config/auth.php';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userInitials = strtoupper(substr($currentUser['name'], 0, 1));
$role = $currentUser['role'];
?>
<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-header">
        <a href="/normss/admin/index.php" class="sidebar-logo">
            <span>D</span><span class="sidebar-logo-text">ailyToinks</span>
        </a>
        <button class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">☰</button>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Main</div>
            <a href="/normss/admin/index.php" class="sidebar-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span class="link-text">Dashboard</span>
            </a>
        </div>

        <?php if ($role === 'manager'): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Management</div>
            <a href="/normss/admin/products.php" class="sidebar-link <?= $currentPage === 'products' ? 'active' : '' ?>">
                <span class="icon">📦</span>
                <span class="link-text">Products</span>
            </a>
            <a href="/normss/admin/orders.php" class="sidebar-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <span class="icon">🛒</span>
                <span class="link-text">Orders</span>
            </a>
            <a href="/normss/admin/tickets.php" class="sidebar-link <?= $currentPage === 'tickets' ? 'active' : '' ?>">
                <span class="icon">🎫</span>
                <span class="link-text">Tickets</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Management</div>
            <a href="/normss/admin/users.php" class="sidebar-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span class="link-text">Users</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">System</div>
            <a href="/normss/admin/settings.php" class="sidebar-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span class="link-text">Settings</span>
            </a>
            <a href="/normss/admin/audit-logs.php" class="sidebar-link <?= $currentPage === 'audit-logs' ? 'active' : '' ?>">
                <span class="icon">📋</span>
                <span class="link-text">Audit Logs</span>
            </a>
            <a href="/normss/admin/locked-accounts.php" class="sidebar-link <?= $currentPage === 'locked-accounts' ? 'active' : '' ?>">
                <span class="icon">🔒</span>
                <span class="link-text">Locked Accounts</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($role === 'rider'): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Deliveries</div>
            <a href="/normss/admin/deliveries.php" class="sidebar-link <?= $currentPage === 'deliveries' ? 'active' : '' ?>">
                <span class="icon">🚚</span>
                <span class="link-text">My Deliveries</span>
            </a>
        </div>
        <?php endif; ?>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Store</div>
            <a href="/normss/index.php" class="sidebar-link" target="_blank">
                <span class="icon">🌐</span>
                <span class="link-text">View Store</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= $userInitials ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">
                    <?= htmlspecialchars($currentUser['name']) ?>
                </div>
                <div class="sidebar-user-role">
                    <?= ucfirst(htmlspecialchars($currentUser['role'])) ?>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const wrapper = document.querySelector('.admin-wrapper');
        sidebar.classList.toggle('collapsed');
        if (wrapper) wrapper.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    }

    // Restore sidebar state
    document.addEventListener('DOMContentLoaded', () => {
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('admin-sidebar')?.classList.add('collapsed');
            document.querySelector('.admin-wrapper')?.classList.add('sidebar-collapsed');
        }
    });
</script>
<script src="/normss/js/admin.js?v=<?php echo time(); ?>"></script>