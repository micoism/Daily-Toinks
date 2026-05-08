<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pageTitle = "My Tickets";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .tickets-container { margin: 2rem 0; }
        .tickets-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .tickets-header h1 { font-size: 1.8rem; }
        .filter-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filter-tab {
            padding: 0.4rem 1rem; border-radius: 999px; border: 1px solid #ddd;
            background: #fff; font-size: 0.82rem; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: all 0.15s;
        }
        .filter-tab.active, .filter-tab:hover { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

        .ticket-card {
            background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem; margin-bottom: 0.75rem; cursor: pointer;
            transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        }
        .ticket-card:hover { box-shadow: var(--shadow-sm); border-color: var(--primary-color); }
        .ticket-main { flex: 1; min-width: 0; }
        .ticket-number { font-weight: 700; color: var(--primary-color); font-size: 0.85rem; }
        .ticket-subject { font-weight: 600; font-size: 1rem; margin: 0.25rem 0; }
        .ticket-meta { font-size: 0.8rem; color: #999; display: flex; gap: 1rem; flex-wrap: wrap; }
        .ticket-status {
            padding: 0.25rem 0.7rem; border-radius: 999px;
            font-weight: 600; font-size: 0.75rem; white-space: nowrap;
        }
        .status-open { background: #FFF3E0; color: #E65100; }
        .status-in_progress { background: #E3F2FD; color: #1565C0; }
        .status-resolved { background: #E8F5E9; color: #2E7D32; }
        .status-closed { background: #F5F5F5; color: #999; }

        /* Ticket Detail View */
        .ticket-detail { display: none; }
        .ticket-detail.active { display: block; }
        .ticket-detail-header {
            background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color);
            padding: 1.5rem; margin-bottom: 1rem;
        }
        .ticket-detail-back { background: none; border: none; color: var(--primary-color); font-weight: 600; cursor: pointer; font-family: inherit; font-size: 0.9rem; margin-bottom: 1rem; padding: 0; }
        .ticket-detail-title { font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; }
        .ticket-detail-info { font-size: 0.85rem; color: #666; display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem; }

        .ticket-messages {
            background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .ticket-msg {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid #f0f0f0;
        }
        .ticket-msg:last-child { border-bottom: none; }
        .ticket-msg-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .ticket-msg-author { font-weight: 600; font-size: 0.9rem; }
        .ticket-msg-staff { color: #1565C0; }
        .ticket-msg-badge {
            font-size: 0.65rem; padding: 0.1rem 0.4rem; border-radius: 4px;
            background: #E3F2FD; color: #1565C0; font-weight: 600; margin-left: 0.5rem;
        }
        .ticket-msg-date { font-size: 0.78rem; color: #999; }
        .ticket-msg-body { font-size: 0.9rem; color: #444; line-height: 1.6; white-space: pre-wrap; }

        .ticket-reply-form {
            background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem; margin-top: 1rem;
        }
        .ticket-reply-form textarea {
            width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;
            font-family: inherit; font-size: 0.9rem; resize: vertical; min-height: 80px;
        }
        .ticket-reply-form button {
            margin-top: 0.75rem; padding: 0.6rem 1.5rem;
            background: var(--primary-color); color: #fff; border: none;
            border-radius: 999px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
        }
        .ticket-close-btn {
            margin-top: 0.75rem; margin-left: 0.5rem; padding: 0.6rem 1.5rem;
            background: #f0f0f0; color: #666; border: none;
            border-radius: 999px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
        }
        .empty-tickets { text-align: center; padding: 4rem; background: #fff; border-radius: var(--radius-md); }
    </style>
</head>
<body class="store-page">
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="container">
            <!-- List View -->
            <div id="tickets-list-view">
                <div class="tickets-header">
                    <h1>My Support Tickets</h1>
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterTickets('')">All</button>
                        <button class="filter-tab" onclick="filterTickets('open')">Open</button>
                        <button class="filter-tab" onclick="filterTickets('in_progress')">In Progress</button>
                        <button class="filter-tab" onclick="filterTickets('resolved')">Resolved</button>
                        <button class="filter-tab" onclick="filterTickets('closed')">Closed</button>
                    </div>
                </div>
                <div class="tickets-container" id="tickets-container">
                    <div style="text-align:center;padding:2rem;color:#999;">Loading tickets...</div>
                </div>
            </div>

            <!-- Detail View -->
            <div id="ticket-detail-view" class="ticket-detail">
                <div class="ticket-detail-header">
                    <button class="ticket-detail-back" onclick="showListView()">&larr; Back to tickets</button>
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                        <div>
                            <span class="ticket-number" id="detail-number"></span>
                            <div class="ticket-detail-title" id="detail-subject"></div>
                        </div>
                        <span class="ticket-status" id="detail-status"></span>
                    </div>
                    <div class="ticket-detail-info" id="detail-info"></div>
                </div>
                <div class="ticket-messages" id="detail-messages"></div>
                <div id="detail-reply-area"></div>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let currentFilter = '';

        async function loadTickets() {
            let url = '/normss/api/tickets.php';
            if (currentFilter) url += `?status=${currentFilter}`;
            const res = await fetch(url);
            const data = await res.json();
            const container = document.getElementById('tickets-container');

            if (!data.tickets || data.tickets.length === 0) {
                container.innerHTML = '<div class="empty-tickets"><h2>No tickets found</h2><p style="color:#999;margin-top:0.5rem;">You haven\'t submitted any support tickets yet.</p></div>';
                return;
            }

            const statusLabels = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved', closed: 'Closed' };
            container.innerHTML = data.tickets.map(t => `
                <div class="ticket-card" onclick="viewTicket(${t.id})">
                    <div class="ticket-main">
                        <div class="ticket-number">${t.ticket_number}</div>
                        <div class="ticket-subject">${t.subject}</div>
                        <div class="ticket-meta">
                            ${t.order_number ? `<span>Order: ${t.order_number}</span>` : ''}
                            <span>${new Date(t.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                            <span>${t.reply_count} replies</span>
                        </div>
                    </div>
                    <span class="ticket-status status-${t.status}">${statusLabels[t.status] || t.status}</span>
                </div>
            `).join('');
        }

        function filterTickets(status) {
            currentFilter = status;
            document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            loadTickets();
        }

        async function viewTicket(id) {
            const res = await fetch(`/normss/api/tickets.php?id=${id}`);
            const data = await res.json();
            if (!data.success) return;

            const t = data.ticket;
            const statusLabels = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved', closed: 'Closed' };

            document.getElementById('detail-number').textContent = t.ticket_number;
            document.getElementById('detail-subject').textContent = t.subject;
            const statusEl = document.getElementById('detail-status');
            statusEl.textContent = statusLabels[t.status] || t.status;
            statusEl.className = 'ticket-status status-' + t.status;

            let info = `<span>Created: ${new Date(t.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>`;
            if (t.order_number) info += `<span>Order: ${t.order_number}</span>`;
            if (t.product_name) info += `<span>Product: ${t.product_name}</span>`;
            document.getElementById('detail-info').innerHTML = info;

            // Messages (original + replies)
            let msgs = `<div class="ticket-msg">
                <div class="ticket-msg-header">
                    <span class="ticket-msg-author">${t.user_name}</span>
                    <span class="ticket-msg-date">${new Date(t.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                <div class="ticket-msg-body">${t.message.replace(/</g, '&lt;')}</div>
            </div>`;

            (t.replies || []).forEach(r => {
                const isStaff = ['admin', 'manager'].includes(r.user_role);
                msgs += `<div class="ticket-msg" style="${isStaff ? 'background:#f8fbff;' : ''}">
                    <div class="ticket-msg-header">
                        <span class="ticket-msg-author ${isStaff ? 'ticket-msg-staff' : ''}">${r.user_name}${isStaff ? '<span class="ticket-msg-badge">Staff</span>' : ''}</span>
                        <span class="ticket-msg-date">${new Date(r.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                    <div class="ticket-msg-body">${r.message.replace(/</g, '&lt;')}</div>
                </div>`;
            });
            document.getElementById('detail-messages').innerHTML = msgs;

            // Reply form
            if (t.status !== 'closed') {
                document.getElementById('detail-reply-area').innerHTML = `
                    <div class="ticket-reply-form">
                        <textarea id="reply-text" placeholder="Write your reply..."></textarea>
                        <div style="display:flex;align-items:center;">
                            <button onclick="sendReply(${t.id})">Send Reply</button>
                            <button class="ticket-close-btn" onclick="closeTicket(${t.id})">Close Ticket</button>
                        </div>
                        <div id="reply-msg" style="margin-top:0.5rem;font-size:0.85rem;"></div>
                    </div>`;
            } else {
                document.getElementById('detail-reply-area').innerHTML = '<div style="text-align:center;padding:1.5rem;color:#999;font-size:0.9rem;">This ticket is closed.</div>';
            }

            document.getElementById('tickets-list-view').style.display = 'none';
            document.getElementById('ticket-detail-view').classList.add('active');
        }

        function showListView() {
            document.getElementById('ticket-detail-view').classList.remove('active');
            document.getElementById('tickets-list-view').style.display = 'block';
            loadTickets();
        }

        async function sendReply(ticketId) {
            const message = document.getElementById('reply-text').value.trim();
            const msgEl = document.getElementById('reply-msg');
            if (!message) { msgEl.innerHTML = '<span style="color:red;">Please enter a message</span>'; return; }

            const res = await fetch('/normss/api/tickets.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ action: 'reply', ticket_id: ticketId, message })
            });
            const data = await res.json();
            if (data.success) {
                viewTicket(ticketId);
            } else {
                msgEl.innerHTML = `<span style="color:red;">${data.message}</span>`;
            }
        }

        async function closeTicket(ticketId) {
            if (!confirm('Close this ticket? You can no longer reply after closing.')) return;
            const res = await fetch('/normss/api/tickets.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id: ticketId, status: 'closed' })
            });
            const data = await res.json();
            if (data.success) viewTicket(ticketId);
        }

        loadTickets();
    </script>
</body>
</html>
