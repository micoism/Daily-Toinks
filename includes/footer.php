<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Shop</h3>
                <ul class="footer-links">
                    <li><a href="/normss/products.php">All Products</a></li>
                    <li><a href="/normss/order-history.php">My Orders</a></li>
                    <li><a href="/normss/cart.php">Cart</a></li>
                    <li><a href="/normss/account.php">My Account</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Payment Methods</h3>
                <p style="margin-bottom:0.75rem;">We accept the following:</p>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <span style="background:#fff;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.8rem;font-weight:600;border:1px solid #e0e0e0;">💙 GCash</span>
                    <span style="background:#fff;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.8rem;font-weight:600;border:1px solid #e0e0e0;">💵 COD</span>
                </div>
            </div>
            <div class="footer-section">
                <h3>Policies</h3>
                <ul class="footer-links">
                    <li><a href="/normss/privacy-policy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> DailyToinks. All rights reserved.</p>
            <p style="margin-top:.35rem;">Reliable electronics, clear service, and delivery updates you can trust.</p>
        </div>
    </div>
</footer>

<!-- Cookie Consent Banner -->
<div id="cookie-consent" style="display:none; position:fixed; bottom:0; left:0; right:0; background:#1a1a2e; color:#fff; padding:1rem 1.5rem; z-index:99999; box-shadow:0 -4px 20px rgba(0,0,0,0.2); font-family:'Manrope','Inter',sans-serif;">
    <div style="max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div style="flex:1; min-width:280px; font-size:0.88rem; line-height:1.5;">
            We use essential cookies to maintain your session and ensure security. By continuing to use DailyToinks, you consent to our use of cookies.
            <a href="/normss/privacy-policy.php" style="color:#ff6b6b; text-decoration:underline; font-weight:600;">Privacy Policy</a>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <button onclick="acceptCookies()" style="padding:0.5rem 1.5rem; background:var(--primary-color, #cc0000); color:#fff; border:none; border-radius:999px; font-weight:700; font-size:0.85rem; cursor:pointer;">Accept</button>
            <button onclick="declineCookies()" style="padding:0.5rem 1.5rem; background:transparent; color:#ccc; border:1px solid #555; border-radius:999px; font-weight:600; font-size:0.85rem; cursor:pointer;">Decline</button>
        </div>
    </div>
</div>
<script>
(function() {
    if (!localStorage.getItem('cookie_consent')) {
        document.getElementById('cookie-consent').style.display = 'block';
    }
})();
function acceptCookies() {
    localStorage.setItem('cookie_consent', 'accepted');
    document.getElementById('cookie-consent').style.display = 'none';
}
function declineCookies() {
    localStorage.setItem('cookie_consent', 'declined');
    document.getElementById('cookie-consent').style.display = 'none';
}
</script>

<!-- Scripts -->
<script src="js/app.js?v=<?php echo time(); ?>"></script>
