<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Shopping Cart";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin: 2rem 0;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .cart-item {
            display: flex;
            gap: 1.5rem;
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .cart-item:hover {
            box-shadow: var(--shadow-sm);
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .cart-item-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .qty-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.25rem;
            font-weight: 600;
        }

        .remove-btn {
            color: var(--primary-color);
            cursor: pointer;
            border: none;
            background: none;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .cart-summary {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .cart-summary h3 {
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 2px solid var(--text-dark);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--radius-md);
            grid-column: 1/-1;
        }

        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <h1 class="page-title">Shopping Cart</h1>
            <div class="cart-container" id="cart-container">
                <div style="grid-column: 1/-1; text-align:center; padding:2rem; color:#999;">Loading cart...</div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            await renderCart();
        });

        async function renderCart() {
            const { getCart, formatCurrency, removeFromCart, updateCartQuantity, clearCart } = window.DailyToinks;
            const container = document.getElementById('cart-container');
            const cart = await getCart();

            if (!cart || cart.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <h2 style="margin-bottom: 1rem;">🛒 Your cart is empty</h2>
                        <p>Looks like you haven't added anything yet!</p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 1.5rem; display:inline-block;">Continue Shopping</a>
                    </div>`;
                return;
            }

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal >= 2500 ? 0 : 150;
            const total = subtotal + shipping;

            container.innerHTML = `
                <div class="cart-items">
                    ${cart.map(item => `
                        <div class="cart-item">
                            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                            <div class="cart-item-info">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-price">${formatCurrency(item.price)}</div>
                                <div class="cart-item-quantity">
                                    <button class="qty-btn" onclick="changeQty(${item.id}, ${item.quantity - 1})">−</button>
                                    <input type="number" class="qty-input" value="${item.quantity}" min="1" onchange="changeQty(${item.id}, this.value)">
                                    <button class="qty-btn" onclick="changeQty(${item.id}, ${item.quantity + 1})">+</button>
                                </div>
                                <div style="color:#666;font-size:0.85rem;margin-top:0.5rem;">Subtotal: ${formatCurrency(item.price * item.quantity)}</div>
                                <button class="remove-btn" onclick="removeItem(${item.id})">Remove</button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal (${cart.length} items)</span><span>${formatCurrency(subtotal)}</span></div>
                    <div class="summary-row"><span>Shipping</span><span>${shipping === 0 ? '<span style="color:var(--success);font-weight:600;">FREE</span>' : formatCurrency(shipping)}</span></div>
                    ${shipping > 0 ? `<div style="font-size:0.8rem;color:var(--success);margin-bottom:1rem;">Free shipping on orders ₱2,500+</div>` : ''}
                    <div class="summary-total"><span>Total</span><span style="color:var(--primary-color);">${formatCurrency(total)}</span></div>
                    <a href="checkout.php" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; text-align:center; display:block;">Proceed to Checkout</a>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;" onclick="emptyCart()">Clear Cart</button>
                    <a href="products.php" style="display: block; text-align: center; margin-top: 1rem; font-size: 0.9rem; color: var(--primary-color);">Continue Shopping</a>
                </div>`;
        }

        async function changeQty(id, qty) {
            qty = parseInt(qty);
            if (qty <= 0) return removeItem(id);
            await window.DailyToinks.updateCartQuantity(id, qty);
            await renderCart();
        }

        async function removeItem(id) {
            await window.DailyToinks.removeFromCart(id);
            await renderCart();
        }

        async function emptyCart() {
            if (!confirm('Are you sure you want to clear your cart?')) return;
            await window.DailyToinks.clearCart();
            await renderCart();
        }
    </script>
</body>

</html>