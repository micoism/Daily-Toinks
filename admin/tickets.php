<?php
$pageTitle = "Support Tickets";
require_once __DIR__ . '/../config/auth.php';
requireRole(['admin', 'manager']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Support Tickets - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', 'Inter', sans-serif; margin: 0; }
        .tickets-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .ticket-stat {
            background: #fff; border-radius: 10px; border: 1px solid #e8e8e8;
            padding: 1.25rem; text-align: center;
        }
        .ticket-stat-num { font-size: 2rem; font-weight: 800; }
        .ticket-stat-label { font-size: 0.8rem; color: #999; margin-top: 0.25rem; }
        .stat-open .ticket-stat-num { color: #E65100; }
        .stat-progress .ticket-stat-num { color: #1565C0; }
        .stat-resolved .ticket-stat-num { color: #2E7D32; }
        .stat-closed .ticket-stat-num { color: #999; }

        .filter-bar { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        .filter-bar select, .filter-bar input {
            padding: 0.5rem 0.75rem; border: 1px solid #ddd; border-radius: 8px;
            font-family: inherit; font-size: 0.85rem;
        }

        .ticket-row {
            display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.15s;
        }
        .ticket-row:hover { background: #fafafa; }
        .ticket-row-main { flex: 1; min-width: 0; }
        .ticket-row-number { font-size: 0.78rem; font-weight: 700; color: var(--primary-color); }
        .ticket-row-subject { font-weight: 600; font-size: 0.9rem; margin: 0.15rem 0; }
        .ticket-row-meta { font-size: 0.78rem; color: #999; display: flex; gap: 1rem; }
        .ticket-row-status {
            padding: 0.2rem 0.6rem; border-radius: 999px;
            font-weight: 600; font-size: 0.72rem; white-space: nowrap;
        }
        .status-open { background: #FFF3E0; color: #E65100; }
        .status-in_progress { background: #E3F2FD; color: #1565C0; }
        .status-resolved { background: #E8F5E9; color: #2E7D32; }
        .status-closed { background: #F5F5F5; color: #999; }
        .priority-high { color: #B71C1C; }
        .priority-medium { color: #E65100; }
        .priority-low { color: #666; }

        /* Detail Panel */
        .detail-panel { display: none; }
        .detail-panel.active { display: block; }
        .detail-card {
            background: #fff; border-radius: 12px; border: 1px solid #e8e8e8;
            overflow: hidden; margin-bottom: 1rem;
        }
        .detail-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f0f0f0; }
        .detail-back {
            background: none; border: none; color: var(--primary-color);
            font-weight: 600; cursor: pointer; font-family: inherit; font-size: 0.85rem; padding: 0;
        }
        .detail-title { font-size: 1.2rem; font-weight: 700; margin: 0.5rem 0; }
        .detail-info { font-size: 0.82rem; color: #666; display: flex; gap: 1.5rem; flex-wrap: wrap; }
        .detail-actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap; }
        .detail-actions select {
            padding: 0.4rem 0.75rem; border: 1px solid #ddd; border-radius: 8px;
            font-family: inherit; font-size: 0.82rem;
        }
        .detail-actions button {
            padding: 0.4rem 1rem; border: none; border-radius: 8px;
            font-weight: 600; font-size: 0.82rem; cursor: pointer; font-family: inherit;
        }

        .msg-item { padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0; }
        .msg-item:last-child { border-bottom: none; }
        .msg-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
        .msg-author { font-weight: 600; font-size: 0.88rem; }
        .msg-staff { color: #1565C0; }
        .msg-badge { font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 4px; background: #E3F2FD; color: #1565C0; font-weight: 600; margin-left: 0.4rem; }
        .msg-customer-badge { background: #E8F5E9; color: #2E7D32; }
        .msg-date { font-size: 0.75rem; color: #999; }
        .msg-body { font-size: 0.88rem; color: #444; line-height: 1.6; white-space: pre-wrap; }

        .reply-box { padding: 1.25rem 1.5rem; border-top: 1px solid #e8e8e8; }
        .reply-box textarea {
            width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;
            font-family: inherit; font-size: 0.88rem; resize: vertical; min-height: 80px;
        }
        .reply-box button {
            margin-top: 0.5rem; padding: 0.5rem 1.5rem;
            background: var(--primary-color); color: #fff; border: none;
            border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
        }

        @media (max-width: 768px) {
            .tickets-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>
            <div class="admin-content">
                <!-- List View -->
                <div id="list-view">
                    <div class="tickets-stats" id="ticket-stats"></div>
                    <div class="admin-table-container">
                        <div class="admin-table-header">
                            <h3 class="admin-table-title">Support Tickets</h3>
                            <div class="filter-bar">
                                <input type="text" id="search-input" placeholder="Search tickets...">
                                <select id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div id="tickets-list"></div>
                    </div>
                </div>

                <!-- Detail View -->
                <div id="detail-view" class="detail-panel">
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <button class="detail-back" onclick="showList()">&larr; Back to tickets</button>
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.5rem;margin-top:0.5rem;">
                                <div>
                                    <span class="ticket-row-number" id="d-number"></span>
                                    <div class="detail-title" id="d-subject"></div>
                                    <div class="detail-info" id="d-info"></div>
                                </div>
                                <span class="ticket-row-status" id="d-status"></span>
                            </div>
                            <div class="detail-actions" id="d-actions"></div>
                        </div>
                        <div id="d-messages"></div>
                        <div class="reply-box" id="d-reply"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const statusLabels = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved', closed: 'Closed' };

        async function loadTickets() {
            const search = document.getElementById('search-input').value;
            const status = document.getElementById('status-filter').value;
            let url = '/normss/api/tickets.php?';
            if (search) url += `search=${encodeURIComponent(search)}&`;
            if (status) url += `status=${encodeURIComponent(status)}&`;

            const res = await fetch(url);
            const data = await res.json();
            const tickets = data.tickets || [];

            // Stats
            const counts = { open: 0, in_progress: 0, resolved: 0, closed: 0 };
            // Fetch all for stats (ignore current filter)
            const allRes = await fetch('/normss/api/tickets.php');
            const allData = await allRes.json();
            (allData.tickets || []).forEach(t => { if (counts.hasOwnProperty(t.status)) counts[t.status]++; });

            document.getElementById('ticket-stats').innerHTML = `
                <div class="ticket-stat stat-open"><div class="ticket-stat-num">${counts.open}</div><div class="ticket-stat-label">Open</div></div>
                <div class="ticket-stat stat-progress"><div class="ticket-stat-num">${counts.in_progress}</div><div class="ticket-stat-label">In Progress</div></div>
                <div class="ticket-stat stat-resolved"><div class="ticket-stat-num">${counts.resolved}</div><div class="ticket-stat-label">Resolved</div></div>
                <div class="ticket-stat stat-closed"><div class="ticket-stat-num">${counts.closed}</div><div class="ticket-stat-label">Closed</div></div>
            `;

            if (tickets.length === 0) {
                document.getElementById('tickets-list').innerHTML = '<div style="text-align:center;padding:2rem;color:#999;">No tickets found</div>';
                return;
            }

            document.getElementById('tickets-list').innerHTML = tickets.map(t => `
                <div class="ticket-row" onclick="viewTicket(${t.id})">
                    <div class="ticket-row-main">
                        <div class="ticket-row-number">${t.ticket_number}</div>
                        <div class="ticket-row-subject">${t.subject}</div>
                        <div class="ticket-row-meta">
                            <span>${t.user_name}</span>
                            ${t.order_number ? `<span>Order: ${t.order_number}</span>` : ''}
                            <span>${new Date(t.updated_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                            <span>${t.reply_count} replies</span>
                        </div>
                    </div>
                    <span class="ticket-row-status status-${t.status}">${statusLabels[t.status] || t.status}</span>
                </div>
            `).join('');
        }

        async function viewTicket(id) {
            const res = await fetch(`/normss/api/tickets.php?id=${id}`);
            const data = await res.json();
            if (!data.success) return;
            const t = data.ticket;

            document.getElementById('d-number').textContent = t.ticket_number;
            document.getElementById('d-subject').textContent = t.subject;
            const statusEl = document.getElementById('d-status');
            statusEl.textContent = statusLabels[t.status] || t.status;
            statusEl.className = 'ticket-row-status status-' + t.status;

            let info = `<span>By: ${t.user_name} (${t.user_email})</span>`;
            info += `<span>Created: ${new Date(t.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>`;
            if (t.order_number) info += `<span>Order: ${t.order_number}</span>`;
            if (t.product_name) info += `<span>Product: ${t.product_name}</span>`;
            document.getElementById('d-info').innerHTML = info;

            // Status update actions
            document.getElementById('d-actions').innerHTML = `
                <select id="status-select">
                    <option value="open" ${t.status === 'open' ? 'selected' : ''}>Open</option>
                    <option value="in_progress" ${t.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="resolved" ${t.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                    <option value="closed" ${t.status === 'closed' ? 'selected' : ''}>Closed</option>
                </select>
                <button onclick="updateStatus(${t.id})" style="background:var(--primary-color);color:#fff;">Update Status</button>
            `;

            // Messages
            let msgs = `<div class="msg-item">
                <div class="msg-header">
                    <span class="msg-author">${t.user_name}<span class="msg-badge msg-customer-badge">Customer</span></span>
                    <span class="msg-date">${new Date(t.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                <div class="msg-body">${t.message.replace(/</g, '&lt;')}</div>
            </div>`;

            (t.replies || []).forEach(r => {
                const isStaff = ['admin', 'manager'].includes(r.user_role);
                msgs += `<div class="msg-item" style="${isStaff ? 'background:#f8fbff;' : ''}">
                    <div class="msg-header">
                        <span class="msg-author ${isStaff ? 'msg-staff' : ''}">${r.user_name}<span class="msg-badge ${isStaff ? '' : 'msg-customer-badge'}">${isStaff ? 'Staff' : 'Customer'}</span></span>
                        <span class="msg-date">${new Date(r.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                    <div class="msg-body">${r.message.replace(/</g, '&lt;')}</div>
                </div>`;
            });
            document.getElementById('d-messages').innerHTML = msgs;

            // Reply
            if (t.status !== 'closed') {
                document.getElementById('d-reply').innerHTML = `
                    <textarea id="staff-reply" placeholder="Write a reply to the customer..."></textarea>
                    <button onclick="sendReply(${t.id})">Send Reply</button>
                    <span id="reply-msg" style="margin-left:0.75rem;font-size:0.85rem;"></span>
                `;
            } else {
                document.getElementById('d-reply').innerHTML = '<div style="text-align:center;padding:0.75rem;color:#999;font-size:0.85rem;">Ticket closed</div>';
            }

            document.getElementById('list-view').style.display = 'none';
            document.getElementById('detail-view').classList.add('active');
        }

        function showList() {
            document.getElementById('detail-view').classList.remove('active');
            document.getElementById('list-view').style.display = 'block';
            loadTickets();
        }

        async function updateStatus(id) {
            const status = document.getElementById('status-select').value;
            const res = await fetch('/normss/api/tickets.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id, status })
            });
            const data = await res.json();
            if (data.success) viewTicket(id);
            else alert(data.message);
        }

        async function sendReply(ticketId) {
            const message = document.getElementById('staff-reply').value.trim();
            const msgEl = document.getElementById('reply-msg');
            if (!message) { msgEl.innerHTML = '<span style="color:red;">Enter a message</span>'; return; }

            const res = await fetch('/normss/api/tickets.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ action: 'reply', ticket_id: ticketId, message })
            });
            const data = await res.json();
            if (data.success) viewTicket(ticketId);
            else msgEl.innerHTML = `<span style="color:red;">${data.message}</span>`;
        }

        function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
        document.getElementById('search-input').addEventListener('input', debounce(loadTickets, 300));
        document.getElementById('status-filter').addEventListener('change', loadTickets);

        loadTickets();
    </script>
</body>
</html>
