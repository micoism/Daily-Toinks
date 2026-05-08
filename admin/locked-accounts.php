<?php
$pageTitle = "Locked Accounts History";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin']);
$user = getCurrentUser();
$currentPage = 'locked-accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Locked Accounts - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
        .lock-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .lock-stat {
            background: #fff; border-radius: 10px; border: 1px solid #e8e8e8;
            padding: 1.25rem; text-align: center;
        }
        .lock-stat-num { font-size: 2rem; font-weight: 800; }
        .lock-stat-label { font-size: 0.8rem; color: #999; margin-top: 0.25rem; }
        .stat-currently-locked .lock-stat-num { color: #B71C1C; }
        .stat-total-locks .lock-stat-num { color: #E65100; }
        .stat-unique-users .lock-stat-num { color: #1565C0; }

        .lock-status {
            display: inline-block; padding: 0.25rem 0.7rem; border-radius: 999px;
            font-weight: 600; font-size: 0.72rem; white-space: nowrap;
        }
        .status-active-lock { background: #FFEBEE; color: #B71C1C; }
        .status-unlocked { background: #E8F5E9; color: #2E7D32; }
        .unlock-btn-tbl {
            background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9;
            padding: 0.3rem 0.7rem; border-radius: 6px; font-size: 0.75rem;
            font-weight: 600; cursor: pointer; font-family: inherit;
        }
        .unlock-btn-tbl:hover { background: #C8E6C9; }
        @media (max-width: 768px) {
            .lock-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>
            <div class="admin-content">
                <div class="lock-stats" id="lock-stats">
                    <div class="lock-stat stat-currently-locked"><div class="lock-stat-num" id="stat-active">0</div><div class="lock-stat-label">Currently Locked</div></div>
                    <div class="lock-stat stat-total-locks"><div class="lock-stat-num" id="stat-total">0</div><div class="lock-stat-label">Total Lock Events</div></div>
                    <div class="lock-stat stat-unique-users"><div class="lock-stat-num" id="stat-unique">0</div><div class="lock-stat-label">Unique Accounts</div></div>
                </div>

                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">Lock History</h3>
                        <div class="filter-bar">
                            <input type="text" id="search-input" placeholder="Search by email...">
                            <select id="status-filter">
                                <option value="">All</option>
                                <option value="active">Currently Locked</option>
                                <option value="unlocked">Unlocked</option>
                            </select>
                        </div>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>User</th>
                                <th>Lock Count</th>
                                <th>Failed Attempts</th>
                                <th>Locked At</th>
                                <th>Unlocked At</th>
                                <th>Unlocked By</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="locks-tbody">
                            <tr><td colspan="10" style="text-align:center;padding:2rem;color:#999;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function escapeHtml(s) {
            if (s === null || s === undefined) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        }

        function fmtDate(d) {
            if (!d) return '-';
            return new Date(d).toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        async function loadLocks() {
            const search = document.getElementById('search-input').value;
            const status = document.getElementById('status-filter').value;
            let url = '/normss/api/users.php?lock_history=1';
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&lock_status=${encodeURIComponent(status)}`;

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById('locks-tbody');

            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:2rem;color:#B71C1C;">${escapeHtml(data.message || 'Failed to load')}</td></tr>`;
                return;
            }

            // Stats
            document.getElementById('stat-active').textContent = data.stats.active;
            document.getElementById('stat-total').textContent = data.stats.total;
            document.getElementById('stat-unique').textContent = data.stats.unique;

            const rows = data.locks || [];
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:#999;">No lock history found</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(r => {
                const isActive = !r.unlocked_at;
                return `
                    <tr>
                        <td style="font-size:0.85rem;">${escapeHtml(r.email)}</td>
                        <td style="font-size:0.85rem;">${escapeHtml(r.user_name || '(deleted user)')}</td>
                        <td style="text-align:center;font-weight:600;">${r.lock_count || 1}</td>
                        <td style="text-align:center;">${r.failed_attempts}</td>
                        <td style="font-size:0.82rem;color:#666;">${fmtDate(r.locked_at)}</td>
                        <td style="font-size:0.82rem;color:#666;">${fmtDate(r.unlocked_at)}</td>
                        <td style="font-size:0.82rem;color:#666;">${escapeHtml(r.unlocked_by_name || '-')}</td>
                        <td style="font-size:0.82rem;color:#999;">${escapeHtml(r.ip_address || '-')}</td>
                        <td><span class="lock-status ${isActive ? 'status-active-lock' : 'status-unlocked'}">${isActive ? '🔒 Locked' : '✓ Unlocked'}</span></td>
                        <td>${isActive && r.user_id ? `<button class="unlock-btn-tbl" onclick="unlockUser(${r.user_id})">🔓 Unlock</button>` : '-'}</td>
                    </tr>
                `;
            }).join('');
        }

        async function unlockUser(userId) {
            if (!confirm('Unlock this account? The user will be able to log in again.')) return;
            const res = await fetch('/normss/api/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id: userId, action: 'unlock' })
            });
            const data = await res.json();
            if (data.success) {
                loadLocks();
            } else {
                alert(data.message || 'Failed to unlock');
            }
        }

        function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
        document.getElementById('search-input').addEventListener('input', debounce(loadLocks, 300));
        document.getElementById('status-filter').addEventListener('change', loadLocks);

        loadLocks();
    </script>
</body>
</html>
