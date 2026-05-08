<?php
$pageTitle = "Audit Logs";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Audit Logs - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }

        .logs-toolbar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .logs-toolbar select, .logs-toolbar input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: inherit;
        }
        .logs-toolbar input[type="date"] { min-width: 140px; }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .logs-table th {
            text-align: left;
            padding: 0.6rem 0.75rem;
            background: #f8f8f8;
            border-bottom: 2px solid #e0e0e0;
            font-weight: 700;
            color: #555;
            white-space: nowrap;
        }
        .logs-table td {
            padding: 0.55rem 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            color: #444;
            vertical-align: top;
        }
        .logs-table tr:hover td { background: #fafafa; }

        .log-action {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .log-action.login { background: #E8F5E9; color: #2E7D32; }
        .log-action.logout { background: #FFF3E0; color: #E65100; }
        .log-action.create { background: #E3F2FD; color: #1565C0; }
        .log-action.update { background: #F3E5F5; color: #7B1FA2; }
        .log-action.delete { background: #FFEBEE; color: #C62828; }
        .log-action.cancel { background: #FFEBEE; color: #C62828; }
        .log-action.account_locked { background: #FFCDD2; color: #B71C1C; }
        .log-action.unlock_account { background: #C8E6C9; color: #1B5E20; }
        .log-action.register { background: #E0F7FA; color: #00695C; }
        .log-action.payment_initiated { background: #FFF9C4; color: #F57F17; }

        .log-details {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .log-details:hover {
            white-space: normal;
            word-break: break-word;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        .pagination button {
            padding: 0.4rem 0.9rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            font-size: 0.82rem;
            font-family: inherit;
        }
        .pagination button.active {
            background: var(--primary-color, #cc0000);
            color: #fff;
            border-color: var(--primary-color, #cc0000);
        }
        .pagination button:disabled { opacity: 0.5; cursor: default; }

        .log-count { color: #888; font-size: 0.82rem; }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="content-header">
                    <h1>Audit Logs</h1>
                    <span class="log-count" id="log-count"></span>
                </div>

                <div class="logs-toolbar">
                    <select id="filter-action">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="register">Register</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="cancel">Cancel</option>
                        <option value="account_locked">Account Locked</option>
                        <option value="unlock_account">Unlock Account</option>
                        <option value="change_password">Change Password</option>
                        <option value="password_reset">Password Reset</option>
                        <option value="admin_reset_password">Admin Reset Password</option>
                        <option value="payment_initiated">Payment Initiated</option>
                        <option value="upload">Upload</option>
                    </select>
                    <select id="filter-entity">
                        <option value="">All Entities</option>
                        <option value="user">User</option>
                        <option value="order">Order</option>
                        <option value="product">Product</option>
                        <option value="settings">Settings</option>
                        <option value="product_image">Product Image</option>
                    </select>
                    <input type="text" id="filter-search" placeholder="Search user or details...">
                    <input type="date" id="filter-from" title="From date">
                    <input type="date" id="filter-to" title="To date">
                    <button class="btn btn-primary" style="padding:0.5rem 1rem; font-size:0.85rem;" onclick="loadLogs(1)">Filter</button>
                    <button class="btn btn-secondary" style="padding:0.5rem 1rem; font-size:0.85rem;" onclick="clearFilters()">Clear</button>
                </div>

                <div style="overflow-x:auto;">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logs-body">
                            <tr><td colspan="6" style="text-align:center; padding:2rem;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>

    <script>
        const PER_PAGE = 50;
        let currentPage = 1;

        function esc(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str)));
            return div.innerHTML;
        }

        async function loadLogs(page = 1) {
            currentPage = page;
            const action = document.getElementById('filter-action').value;
            const entity = document.getElementById('filter-entity').value;
            const search = document.getElementById('filter-search').value.trim();
            const from = document.getElementById('filter-from').value;
            const to = document.getElementById('filter-to').value;

            const params = new URLSearchParams({ page, per_page: PER_PAGE });
            if (action) params.set('action', action);
            if (entity) params.set('entity_type', entity);
            if (search) params.set('search', search);
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            try {
                const res = await fetch(`/normss/api/audit-logs.php?${params}`);
                const data = await res.json();

                if (!data.success) {
                    document.getElementById('logs-body').innerHTML = `<tr><td colspan="6" style="text-align:center;color:red;">${esc(data.message)}</td></tr>`;
                    return;
                }

                const logs = data.logs || [];
                const total = data.total || 0;
                document.getElementById('log-count').textContent = `${total} total entries`;

                if (logs.length === 0) {
                    document.getElementById('logs-body').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#999;">No audit logs found</td></tr>';
                    document.getElementById('pagination').innerHTML = '';
                    return;
                }

                document.getElementById('logs-body').innerHTML = logs.map(log => `
                    <tr>
                        <td style="white-space:nowrap;">${esc(log.created_at)}</td>
                        <td>
                            <div style="font-weight:600;">${esc(log.user_name || 'System')}</div>
                            <div style="font-size:0.75rem;color:#888;">${esc(log.user_email || '')}</div>
                        </td>
                        <td><span class="log-action ${esc(log.action)}">${esc(log.action)}</span></td>
                        <td>
                            ${log.entity_type ? `<span style="font-weight:600;">${esc(log.entity_type)}</span>` : '-'}
                            ${log.entity_id ? ` #${esc(log.entity_id)}` : ''}
                        </td>
                        <td class="log-details" title="${esc(log.details)}">${esc(log.details || '-')}</td>
                        <td style="font-family:monospace;font-size:0.78rem;">${esc(log.ip_address)}</td>
                    </tr>
                `).join('');

                // Pagination
                const totalPages = Math.ceil(total / PER_PAGE);
                let pHtml = '';
                pHtml += `<button ${page <= 1 ? 'disabled' : ''} onclick="loadLogs(${page - 1})">Prev</button>`;
                for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
                    pHtml += `<button class="${i === page ? 'active' : ''}" onclick="loadLogs(${i})">${i}</button>`;
                }
                pHtml += `<button ${page >= totalPages ? 'disabled' : ''} onclick="loadLogs(${page + 1})">Next</button>`;
                document.getElementById('pagination').innerHTML = pHtml;

            } catch (e) {
                document.getElementById('logs-body').innerHTML = '<tr><td colspan="6" style="text-align:center;color:red;">Error loading logs</td></tr>';
            }
        }

        function clearFilters() {
            document.getElementById('filter-action').value = '';
            document.getElementById('filter-entity').value = '';
            document.getElementById('filter-search').value = '';
            document.getElementById('filter-from').value = '';
            document.getElementById('filter-to').value = '';
            loadLogs(1);
        }

        document.addEventListener('DOMContentLoaded', () => loadLogs(1));
    </script>
</body>

</html>
