<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pageTitle = "Checkout";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin: 2rem 0;
        }

        .checkout-section {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .checkout-section h2 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 1rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background: #FFF5F5;
        }

        .payment-option input {
            display: none;
        }

        .order-summary {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .order-summary h3 {
            margin-bottom: 1.5rem;
        }

        .summary-item {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-item img {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
        }

        .summary-item-info {
            flex: 1;
        }

        .summary-item-name {
            font-weight: 500;
            font-size: 0.85rem;
        }

        .summary-item-price {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.85rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 2px solid var(--text-dark);
            font-weight: 700;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <h1 class="page-title">Checkout</h1>
            <div class="checkout-container">
                <div>
                    <!-- Shipping Address -->
                    <div class="checkout-section">
                        <h2>Shipping Address</h2>
                        <form id="checkout-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" id="ship-fullname" class="form-input" required
                                        value="<?= htmlspecialchars($_SESSION['user_name']) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" id="ship-phone" class="form-input" required
                                        minlength="11" maxlength="11" pattern="[0-9]{11}"
                                        placeholder="e.g. 09171234567"
                                        value="">
                                    <small style="color:#999;font-size:0.72rem;">Must be exactly 11 digits</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Complete Address *</label>
                                <input type="text" id="ship-address" class="form-input" required
                                    placeholder="House/Unit No., Street, Barangay">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">City *</label>
                                    <select id="ship-city" class="form-input" required>
                                        <option value="">Select city</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" id="ship-postal" class="form-input" readonly>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top:0.5rem;">
                                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;">
                                    <input type="checkbox" id="save-as-default" checked>
                                    Save as my default shipping info
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h2>Payment Method</h2>
                        <div class="payment-methods">
                            <label class="payment-option" onclick="selectPayment(this, 'GCash')">
                                <input type="radio" name="payment" value="GCash">
                                <div style="font-weight: 600;">💙 GCash</div>
                                <div style="font-size: 0.75rem; color: #666;">Pay online via GCash</div>
                            </label>
                            <label class="payment-option" onclick="selectPayment(this, 'COD')">
                                <input type="radio" name="payment" value="COD">
                                <div style="font-weight: 600;">💵 Cash on Delivery</div>
                                <div style="font-size: 0.75rem; color: #666;">Pay when delivered</div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div id="summary-items">Loading...</div>
                    <div style="margin-top: 1.5rem;">
                        <div class="summary-row"><span>Subtotal</span><span id="summary-subtotal"></span></div>
                        <div class="summary-row"><span>Shipping</span><span id="summary-shipping"></span></div>
                        <div class="summary-total"><span>Total</span><span id="summary-total"
                                style="color:var(--primary-color);"></span></div>
                    </div>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;" onclick="placeOrder()"
                        id="place-order-btn">Place Order</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        let cartItems = [];
        let selectedPayment = '';

        document.addEventListener('DOMContentLoaded', async () => {
            const { getCart, formatCurrency, PHILIPPINE_CITIES } = window.DailyToinks;

            // Populate cities
            const citySelect = document.getElementById('ship-city');
            PHILIPPINE_CITIES.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                opt.dataset.postal = c.postal;
                opt.textContent = c.name;
                citySelect.appendChild(opt);
            });

            citySelect.addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                document.getElementById('ship-postal').value = selected.dataset.postal || '';
            });

            // Auto-fill from saved profile defaults
            try {
                const profRes = await fetch('/normss/api/profile.php');
                const profData = await profRes.json();
                if (profData.success && profData.user) {
                    const u = profData.user;
                    if (u.name) document.getElementById('ship-fullname').value = u.name;
                    if (u.phone) document.getElementById('ship-phone').value = u.phone;
                    if (u.address) document.getElementById('ship-address').value = u.address;
                    if (u.city) {
                        // Try to match city in dropdown
                        const cityMatch = PHILIPPINE_CITIES.find(c => c.name === u.city);
                        if (cityMatch) {
                            citySelect.value = cityMatch.name;
                            document.getElementById('ship-postal').value = cityMatch.postal;
                        } else {
                            // City might be stored differently; still set it
                            for (let i = 0; i < citySelect.options.length; i++) {
                                if (citySelect.options[i].value === u.city) {
                                    citySelect.selectedIndex = i;
                                    document.getElementById('ship-postal').value = citySelect.options[i].dataset.postal || u.zip_code || '';
                                    break;
                                }
                            }
                        }
                    }
                    if (!citySelect.value && u.zip_code) {
                        document.getElementById('ship-postal').value = u.zip_code;
                    }
                }
            } catch (e) { console.log('Could not load defaults:', e); }

            // Load cart
            cartItems = await getCart();
            if (!cartItems || cartItems.length === 0) {
                window.location.href = 'cart.php';
                return;
            }

            const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal >= 2500 ? 0 : 150;
            const total = subtotal + shipping;

            const { escapeHtml } = window.DailyToinks;
            document.getElementById('summary-items').innerHTML = cartItems.map(item => `
                <div class="summary-item">
                    <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}">
                    <div class="summary-item-info">
                        <div class="summary-item-name">${escapeHtml(item.name)}</div>
                        <div class="summary-item-price">${formatCurrency(item.price)} x ${item.quantity}</div>
                    </div>
                </div>
            `).join('');

            document.getElementById('summary-subtotal').textContent = formatCurrency(subtotal);
            document.getElementById('summary-shipping').innerHTML = shipping === 0 ? '<span style="color:var(--success);font-weight:600;">FREE</span>' : formatCurrency(shipping);
            document.getElementById('summary-total').textContent = formatCurrency(total);
        });

        function selectPayment(el, method) {
            document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
            selectedPayment = method;
        }

        async function placeOrder() {
            const { createOrder, formatCurrency, showNotification, clearCart } = window.DailyToinks;

            const fullname = document.getElementById('ship-fullname').value.trim();
            const phone = document.getElementById('ship-phone').value.trim();
            const address = document.getElementById('ship-address').value.trim();
            const city = document.getElementById('ship-city').value;
            const postal = document.getElementById('ship-postal').value;

            if (!fullname || !phone || !address || !city) {
                showNotification('Please fill in all shipping fields', 'error');
                return;
            }

            // Phone validation
            const phoneDigits = phone.replace(/\D/g, '');
            if (phoneDigits.length !== 11) {
                showNotification('Phone number must be exactly 11 digits', 'error');
                return;
            }

            if (!selectedPayment) {
                showNotification('Please select a payment method', 'error');
                return;
            }

            const btn = document.getElementById('place-order-btn');
            btn.textContent = 'Placing order...';
            btn.disabled = true;

            const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal >= 2500 ? 0 : 150;
            const total = subtotal + shipping;

            const result = await createOrder({
                items: cartItems,
                total: total,
                payment_method: selectedPayment,
                shipping: { fullname, phone, address, city, postal }
            });

            if (result.success) {
                await clearCart();

                // Save shipping info as default if checked
                if (document.getElementById('save-as-default').checked) {
                    try {
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                        await fetch('/normss/api/profile.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                            body: JSON.stringify({ action: 'update-info', name: fullname, phone })
                        });
                        await fetch('/normss/api/profile.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                            body: JSON.stringify({ action: 'update-address', address, city, province: '', zip_code: postal })
                        });
                    } catch (e) { console.log('Could not save defaults:', e); }
                }

                // If GCash selected, redirect to PayMongo checkout
                if (selectedPayment === 'GCash') {
                    btn.textContent = 'Redirecting to GCash...';
                    try {
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                        const payRes = await fetch('/normss/api/payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                            body: JSON.stringify({
                                order_number: result.order_number,
                                amount: total
                            })
                        });
                        const payText = await payRes.text();
                        console.log('PayMongo raw response:', payText);
                        let payData;
                        try { payData = JSON.parse(payText); } catch(pe) {
                            console.error('PayMongo response not JSON:', payText);
                            showNotification('Payment gateway returned invalid response. Check console.', 'error');
                            btn.textContent = 'Place Order';
                            btn.disabled = false;
                            return;
                        }
                        if (payData.success && payData.checkout_url) {
                            window.location.href = payData.checkout_url;
                            return;
                        } else {
                            console.error('PayMongo error:', payData);
                            showNotification(payData.message || 'GCash payment setup failed.', 'error');
                            btn.textContent = 'Place Order';
                            btn.disabled = false;
                            return;
                        }
                    } catch (e) {
                        console.error('Payment fetch error:', e);
                        showNotification('Could not connect to payment gateway: ' + e.message, 'error');
                        btn.textContent = 'Place Order';
                        btn.disabled = false;
                        return;
                    }
                }

                // COD — show success
                showNotification('Order placed successfully!', 'success');
                document.querySelector('.checkout-container').innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem; background: white; border-radius: var(--radius-md);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <h2 style="margin-bottom: 1rem;">Order Placed Successfully!</h2>
                        <p style="color: #666; margin-bottom: 0.5rem;">Your order number: <strong style="color: var(--primary-color);">${result.order_number}</strong></p>
                        <p style="color: #666; margin-bottom: 2rem;">Thank you for shopping with us!</p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <a href="order-history.php" class="btn btn-primary">My Orders</a>
                            <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                        </div>
                    </div>`;
            } else {
                showNotification(result.message || 'Failed to place order', 'error');
                btn.textContent = 'Place Order';
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>