<?php
$pageTitle = "User Management";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin']);
$user = getCurrentUser();
$canEdit = true;
$isAdmin = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Users - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
        .security-badges {
            display: flex;
            gap: 0.3rem;
            margin-top: 0.2rem;
        }
        .sec-badge {
            font-size: 0.65rem;
            padding: 0.1rem 0.4rem;
            border-radius: 999px;
            font-weight: 600;
        }
        .sec-badge.verified { background: #E8F5E9; color: #2E7D32; }
        .sec-badge.unverified { background: #FFF3E0; color: #E65100; }
        .sec-badge.mfa { background: #E3F2FD; color: #1565C0; }
        .sec-badge.locked { background: #FFEBEE; color: #B71C1C; }
        .unlock-btn {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .unlock-btn:hover {
            background: #C8E6C9;
        }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">All Users</h3>
                        <div class="filter-bar">
                            <input type="text" id="search-input" placeholder="Search users...">
                            <select id="role-filter">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="rider">Rider</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Security</th>
                                <th>Joined</th>
                                <?php if ($canEdit): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <tr>
                                <td colspan="8" style="text-align:center;padding:2rem;color:#999;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <!-- Edit User Modal -->
        <div class="modal-overlay" id="user-modal">
            <div class="modal" style="max-width:450px;">
                <div class="modal-header">
                    <h3 class="modal-title">Edit User</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-user-id">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Name</label>
                        <input type="text" id="edit-user-name" class="admin-form-input" readonly
                            style="background:#f9f9f9;">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Email</label>
                        <input type="text" id="edit-user-email" class="admin-form-input" readonly
                            style="background:#f9f9f9;">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Role</label>
                        <select id="edit-user-role" class="admin-form-select">
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="rider">Rider</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Status</label>
                        <select id="edit-user-status" class="admin-form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="topbar-btn topbar-btn-secondary" onclick="closeModal()">Cancel</button>
                    <button class="topbar-btn topbar-btn-primary" onclick="saveUser()">Save Changes</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const IS_ADMIN = <?= $canEdit ? 'true' : 'false' ?>;
        const CURRENT_USER_ID = <?= $user['id'] ?>;

        function getRoleBadge(role) {
            return `<span class="badge badge-${role}">${role}</span>`;
        }

        function getSecurityBadges(u) {
            let badges = '';

            // Email verification
            if (u.email_verified == 1) {
                badges += '<span class="sec-badge verified">✓ Verified</span>';
            } else {
                badges += '<span class="sec-badge unverified">⚠ Unverified</span>';
            }

            // MFA
            if (u.mfa_enabled == 1) {
                badges += '<span class="sec-badge mfa">🔐 MFA</span>';
            }

            // Locked (permanent lock — any non-null locked_until means admin must unlock)
            if (u.locked_until) {
                badges += '<span class="sec-badge locked">🔒 Locked</span>';
            } else if (u.failed_logins > 0) {
                badges += `<span class="sec-badge unverified">${u.failed_logins} failed</span>`;
            }

            return `<div class="security-badges">${badges}</div>`;
        }

        async function loadUsers() {
            const search = document.getElementById('search-input').value;
            const role = document.getElementById('role-filter').value;
            let url = '/normss/api/users.php?';
            if (search) url += `search=${encodeURIComponent(search)}&`;
            if (role) url += `role=${encodeURIComponent(role)}&`;

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById('users-tbody');

            if (!data.users || data.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No users found</td></tr>';
                return;
            }

            tbody.innerHTML = data.users.map(u => {
                const isLocked = !!u.locked_until;
                let actionBtns = '';
                if (IS_ADMIN && u.id !== CURRENT_USER_ID) {
                    actionBtns = `<button class="action-btn" onclick="editUser(${u.id})">Edit</button>`;
                    if (isLocked) {
                        actionBtns += ` <button class="unlock-btn" onclick="unlockUser(${u.id})">🔓 Unlock</button>`;
                    }
                } else {
                    actionBtns = '-';
                }

                return `
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div style="width:36px;height:36px;border-radius:50%;background:${u.role === 'admin' ? 'var(--primary-color)' : u.role === 'manager' ? '#E65100' : u.role === 'rider' ? '#1565C0' : '#2E7D32'};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;">
                                ${u.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div style="font-weight:600;">${u.name}</div>
                                ${u.id === CURRENT_USER_ID ? '<span style="font-size:0.7rem;color:var(--primary-color);">(You)</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.85rem;">${u.email}</td>
                    <td style="font-size:0.85rem;">${u.phone || '-'}</td>
                    <td>${getRoleBadge(u.role)}</td>
                    <td><span class="badge badge-${u.status}">${u.status}</span></td>
                    <td>${getSecurityBadges(u)}</td>
                    <td style="color:#999;font-size:0.85rem;">${new Date(u.created_at).toLocaleDateString()}</td>
                    ${IS_ADMIN ? `<td style="white-space:nowrap;">${actionBtns}</td>` : ''}
                </tr>
            `}).join('');
        }

        async function unlockUser(id) {
            if (!confirm('Unlock this account? The user will be able to login again.')) return;

            const res = await fetch('/normss/api/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'unlock' })
            });
            const data = await res.json();
            if (data.success) {
                loadUsers();
            } else {
                alert(data.message);
            }
        }

        function editUser(id) {
            fetch(`/normss/api/users.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return alert('User not found');
                    const u = data.user;
                    document.getElementById('edit-user-id').value = u.id;
                    document.getElementById('edit-user-name').value = u.name;
                    document.getElementById('edit-user-email').value = u.email;
                    document.getElementById('edit-user-role').value = u.role;
                    document.getElementById('edit-user-status').value = u.status;
                    document.getElementById('user-modal').classList.add('active');
                });
        }

        function closeModal() {
            document.getElementById('user-modal').classList.remove('active');
        }

        async function saveUser() {
            const id = parseInt(document.getElementById('edit-user-id').value);
            const role = document.getElementById('edit-user-role').value;
            const status = document.getElementById('edit-user-status').value;

            const res = await fetch('/normss/api/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, role, status })
            });

            const data = await res.json();
            if (data.success) {
                closeModal();
                loadUsers();
            } else {
                alert(data.message);
            }
        }

        document.getElementById('search-input').addEventListener('input', debounce(loadUsers, 300));
        document.getElementById('role-filter').addEventListener('change', loadUsers);

        function debounce(fn, ms) {
            let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
        }

        loadUsers();
    </script>
</body>

</html>