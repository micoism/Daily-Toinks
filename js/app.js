// ===================================
// DAILYTOINKS - CORE APP
// Refactored to use PHP/MySQL Backend
// ===================================

// === PHILIPPINE CITIES DATA (kept client-side for checkout form) ===
const PHILIPPINE_CITIES = [
    { name: 'Manila', postal: '1000' }, { name: 'Quezon City', postal: '1100' },
    { name: 'Makati', postal: '1200' }, { name: 'Taguig', postal: '1630' },
    { name: 'Pasig', postal: '1600' }, { name: 'Mandaluyong', postal: '1550' },
    { name: 'San Juan', postal: '1500' }, { name: 'Pasay', postal: '1300' },
    { name: 'Parañaque', postal: '1700' }, { name: 'Las Piñas', postal: '1740' },
    { name: 'Muntinlupa', postal: '1770' }, { name: 'Marikina', postal: '1800' },
    { name: 'Malabon', postal: '1470' }, { name: 'Navotas', postal: '1485' },
    { name: 'Valenzuela', postal: '1440' }, { name: 'Caloocan', postal: '1400' },
    { name: 'Cebu City', postal: '6000' }, { name: 'Mandaue City', postal: '6014' },
    { name: 'Lapu-Lapu City', postal: '6015' }, { name: 'Davao City', postal: '8000' },
    { name: 'Bacolod City', postal: '6100' }, { name: 'Iloilo City', postal: '5000' },
    { name: 'Cagayan de Oro', postal: '9000' }, { name: 'Baguio City', postal: '2600' },
    { name: 'General Santos', postal: '9500' }, { name: 'Zamboanga City', postal: '7000' },
    { name: 'Antipolo', postal: '1870' }, { name: 'Imus', postal: '4103' },
    { name: 'Dasmariñas', postal: '4114' }, { name: 'Bacoor', postal: '4102' }
].sort((a, b) => a.name.localeCompare(b.name));

// === XSS PREVENTION ===

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

// === CSRF TOKEN ===

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// === UTILITY FUNCTIONS ===

function formatCurrency(amount) {
    return `₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

function renderStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    let stars = '';
    for (let i = 0; i < fullStars; i++) stars += '★';
    if (hasHalfStar) stars += '☆';
    while (stars.length < 5) stars += '☆';
    return stars;
}

// === API HELPER ===

async function api(endpoint, options = {}) {
    try {
        // Inject CSRF token into all non-GET requests
        if (!options.headers) options.headers = {};
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') {
            options.headers['X-CSRF-Token'] = getCsrfToken();
        }
        // If body is FormData, also append CSRF token
        if (options.body instanceof FormData) {
            options.body.append('csrf_token', getCsrfToken());
        }
        const res = await fetch(`/normss/api/${endpoint}`, options);
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        return { success: false, message: 'Network error. Please try again.' };
    }
}

// === PRODUCT FUNCTIONS (from API) ===

async function getProducts(params = {}) {
    const query = new URLSearchParams(params).toString();
    return api(`products.php?${query}`);
}

async function getProductById(id) {
    const data = await api(`products.php?id=${id}`);
    return data.success ? data.product : null;
}

async function getCategories() {
    return api('categories.php');
}

function filterByCategory(category) {
    return getProducts({ category });
}

function searchProducts(query) {
    return getProducts({ search: query });
}

// === CART FUNCTIONS ===
// Uses server-side cart for logged-in users, localStorage for guests

function getLocalCart() {
    try {
        return JSON.parse(localStorage.getItem('cart')) || [];
    } catch { return []; }
}

function saveLocalCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartBadge();
}

async function addToCart(productId, quantity = 1) {
    // Try server-side first
    const userCheck = await api('auth.php?action=me');
    if (userCheck.success) {
        const data = await api('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity })
        });
        if (data.success) {
            showNotification('Added to cart!', 'success');
            updateCartBadge();
            return true;
        }
    }

    // Fallback to localStorage for guests
    const product = await getProductById(productId);
    if (!product) { showNotification('Product not found', 'error'); return false; }

    const cart = getLocalCart();
    const existing = cart.find(i => i.id == productId);
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart.push({ id: parseInt(productId), name: product.name, price: parseFloat(product.price), image: product.image, quantity });
    }
    saveLocalCart(cart);
    showNotification('Added to cart!', 'success');
    return true;
}

async function getCart() {
    const userCheck = await api('auth.php?action=me');
    if (userCheck.success) {
        const data = await api('cart.php');
        if (data.success) return data.items.map(i => ({
            id: i.product_id, name: i.name, price: parseFloat(i.price),
            image: i.image, quantity: i.quantity, stock: i.stock
        }));
    }
    return getLocalCart();
}

async function updateCartQuantity(productId, quantity) {
    const userCheck = await api('auth.php?action=me');
    if (userCheck.success) {
        await api('cart.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity })
        });
    } else {
        const cart = getLocalCart();
        const item = cart.find(i => i.id == productId);
        if (item) {
            if (quantity <= 0) {
                saveLocalCart(cart.filter(i => i.id != productId));
            } else {
                item.quantity = quantity;
                saveLocalCart(cart);
            }
        }
    }
    updateCartBadge();
}

async function removeFromCart(productId) {
    const userCheck = await api('auth.php?action=me');
    if (userCheck.success) {
        await api(`cart.php?product_id=${productId}`, { method: 'DELETE' });
    } else {
        saveLocalCart(getLocalCart().filter(i => i.id != productId));
    }
    showNotification('Item removed from cart', 'success');
    updateCartBadge();
}

async function clearCart() {
    const userCheck = await api('auth.php?action=me');
    if (userCheck.success) {
        await api('cart.php', { method: 'DELETE' });
    }
    localStorage.setItem('cart', '[]');
    updateCartBadge();
}

async function calculateCartTotal() {
    const cart = await getCart();
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

async function updateCartBadge() {
    let totalItems = 0;
    try {
        const userCheck = await api('auth.php?action=me');
        if (userCheck.success) {
            const data = await api('cart.php');
            if (data.success && data.items) {
                totalItems = data.items.reduce((sum, i) => sum + i.quantity, 0);
            }
        } else {
            const cart = getLocalCart();
            totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
        }
    } catch {
        const cart = getLocalCart();
        totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
    }

    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = totalItems;
        badge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

// === USER SESSION ===

async function isLoggedIn() {
    const data = await api('auth.php?action=me');
    return data.success;
}

async function getCurrentUser() {
    const data = await api('auth.php?action=me');
    return data.success ? data.user : null;
}

async function loginUser(email, password) {
    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('email', email);
    formData.append('password', password);
    return api('auth.php', { method: 'POST', body: formData });
}

async function registerUser(userData) {
    const formData = new FormData();
    formData.append('action', 'register');
    formData.append('name', userData.name);
    formData.append('email', userData.email);
    formData.append('phone', userData.phone);
    formData.append('password', userData.password);
    return api('auth.php', { method: 'POST', body: formData });
}

async function logoutUser() {
    await api('auth.php?action=logout');
    window.location.href = 'index.php';
}

// === PASSWORD RESET ===

async function requestPasswordReset(email) {
    const formData = new FormData();
    formData.append('action', 'request-reset');
    formData.append('email', email);
    return api('auth.php', { method: 'POST', body: formData });
}

async function verifyResetCode(email, code) {
    const formData = new FormData();
    formData.append('action', 'verify-code');
    formData.append('email', email);
    formData.append('code', code);
    return api('auth.php', { method: 'POST', body: formData });
}

async function resetUserPassword(email, code, newPassword) {
    const formData = new FormData();
    formData.append('action', 'reset-password');
    formData.append('email', email);
    formData.append('code', code);
    formData.append('new_password', newPassword);
    return api('auth.php', { method: 'POST', body: formData });
}

// === ORDERS ===

async function getOrders() {
    const data = await api('orders.php?user_only=1');
    return data.success ? data.orders : [];
}

async function createOrder(orderData) {
    const data = await api('orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    });
    return data;
}

async function getOrderByNumber(orderNumber) {
    const data = await api(`orders.php?order_number=${encodeURIComponent(orderNumber)}`);
    return data.success ? data.order : null;
}

async function cancelOrder(orderNumber) {
    // Get by order_number first to get the ID
    const order = await getOrderByNumber(orderNumber);
    if (!order) return { success: false, message: 'Order not found' };

    return api('orders.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: order.id, action: 'cancel' })
    });
}

// === NOTIFICATIONS ===

function showNotification(message, type = 'info') {
    const safeType = ['success', 'error', 'info'].includes(type) ? type : 'info';
    let stack = document.getElementById('toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toast-stack';
        stack.setAttribute('aria-live', 'polite');
        stack.style.cssText = 'position:fixed;top:18px;right:18px;z-index:10000;display:flex;flex-direction:column;gap:10px;max-width:min(360px,92vw);';
        document.body.appendChild(stack);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${safeType}`;
    notification.textContent = message;
    notification.style.cssText = `
        padding: 0.8rem 1rem; border-radius: 12px;
        background: ${safeType === 'success' ? '#0e7c3a' : safeType === 'error' ? '#b42318' : '#1d4ed8'};
        color: #fff; box-shadow: 0 12px 28px rgba(17,19,25,0.18);
        animation: slideIn 0.28s ease; font-family: 'Manrope','Inter',sans-serif;
        font-weight: 600; font-size: 0.88rem; line-height: 1.4;
    `;
    stack.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.25s ease';
        setTimeout(() => notification.remove(), 250);
    }, 3200);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn { from { transform: translateX(28px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(24px); opacity: 0; } }
`;
document.head.appendChild(style);

// === BANNER SLIDER ===

function initBannerSlider() {
    const slides = document.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.slider-dot');
    let currentSlide = 0;
    if (slides.length === 0) return;

    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
    }

    setInterval(() => { currentSlide = (currentSlide + 1) % slides.length; showSlide(currentSlide); }, 5000);
    dots.forEach((dot, i) => dot.addEventListener('click', () => { currentSlide = i; showSlide(i); }));
    showSlide(0);
}

// === FLASH DEAL COUNTDOWN ===

function initFlashCountdown() {
    const h = document.getElementById('countdown-hours');
    const m = document.getElementById('countdown-minutes');
    const s = document.getElementById('countdown-seconds');
    if (!h) return;

    const endTime = new Date().getTime() + (6 * 60 * 60 * 1000);

    function update() {
        const dist = endTime - new Date().getTime();
        if (dist < 0) { h.textContent = m.textContent = s.textContent = '00'; return; }
        h.textContent = String(Math.floor(dist / (1000 * 60 * 60))).padStart(2, '0');
        m.textContent = String(Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        s.textContent = String(Math.floor((dist % (1000 * 60)) / 1000)).padStart(2, '0');
    }
    update();
    setInterval(update, 1000);
}

// === SEARCH ===

function initSearch() {
    const input = document.getElementById('search-input');
    const btn = document.getElementById('search-button');
    if (!input || !btn) return;

    function perform() {
        const query = input.value.trim();
        if (query) window.location.href = `products.php?search=${encodeURIComponent(query)}`;
    }
    btn.addEventListener('click', perform);
    input.addEventListener('keypress', (e) => { if (e.key === 'Enter') perform(); });
}

// === INITIALIZE ===

document.addEventListener('DOMContentLoaded', async () => {
    updateCartBadge();
    initBannerSlider();
    initFlashCountdown();
    initSearch();

    // Update header for logged-in user
    const user = await getCurrentUser();
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');

    if (user && loginBtn) {
        loginBtn.textContent = user.name;
        loginBtn.href = '#';

        // Show admin link for all back-office roles
        if (['admin', 'manager', 'rider'].includes(user.role)) {
            const adminLink = document.createElement('a');
            adminLink.href = '/normss/admin/index.php';
            adminLink.className = 'btn btn-secondary';
            adminLink.textContent = 'Admin Panel';
            adminLink.style.marginRight = '0.5rem';
            loginBtn.parentNode.insertBefore(adminLink, loginBtn);
        }

        if (registerBtn) {
            registerBtn.textContent = 'Logout';
            registerBtn.href = '#';
            registerBtn.onclick = (e) => { e.preventDefault(); logoutUser(); };
        }
    }
});

// === EXPORT ===

window.DailyToinks = {
    PHILIPPINE_CITIES, formatCurrency, renderStars, escapeHtml, getCsrfToken,
    getProducts, getProductById, getCategories, filterByCategory, searchProducts,
    getCart, addToCart, updateCartQuantity, removeFromCart, clearCart, calculateCartTotal,
    isLoggedIn, getCurrentUser, loginUser, registerUser, logoutUser,
    getOrders, createOrder, getOrderByNumber, cancelOrder,
    requestPasswordReset, verifyResetCode, resetUserPassword,
    showNotification, api, updateCartBadge
};
