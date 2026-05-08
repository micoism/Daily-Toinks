<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Product Details";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .product-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            margin: 2rem 0;
            border: 1px solid var(--border-color);
        }

        .product-image-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .product-main-image {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: #f5f5f5;
        }

        .product-thumbnails {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .product-thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color 0.2s;
            background: #f5f5f5;
        }

        .product-thumb:hover,
        .product-thumb.active {
            border-color: var(--primary-color);
        }

        .product-details-info h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .product-details-price {
            font-size: 2.5rem;
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .stock-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .stock-status.in-stock {
            background: #E8F5E9;
            color: var(--success);
        }

        .stock-status.low-stock {
            background: #FFF3E0;
            color: var(--warning);
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-buttons button {
            flex: 1;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 2rem;
        }

        .product-description {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .product-description-content {
            line-height: 1.7;
            color: #444;
        }

        .product-description-content h1,
        .product-description-content h2,
        .product-description-content h3 {
            margin: 1rem 0 0.5rem;
            color: var(--text-dark);
        }

        .product-description-content p {
            margin: 0.5rem 0;
        }

        .product-description-content ul,
        .product-description-content ol {
            padding-left: 1.5rem;
            margin: 0.5rem 0;
        }

        .product-description-content li {
            margin-bottom: 0.3rem;
        }

        .product-description-content blockquote {
            border-left: 3px solid var(--primary-color);
            padding-left: 1rem;
            margin: 0.75rem 0;
            color: #666;
            font-style: italic;
        }

        .product-description-content strong {
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .product-details-container {
                grid-template-columns: 1fr;
            }
        }

        /* Reviews Section */
        .reviews-section {
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 2rem;
            margin: 2rem 0;
        }
        .reviews-section h2 { font-size: 1.4rem; margin-bottom: 1.5rem; }
        .review-summary {
            display: flex; gap: 2rem; align-items: center;
            padding-bottom: 1.5rem; margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
        }
        .review-avg { text-align: center; min-width: 120px; }
        .review-avg-number { font-size: 3rem; font-weight: 800; color: var(--text-dark); line-height: 1; }
        .review-avg-stars { margin: 0.25rem 0; font-size: 1.2rem; }
        .review-avg-count { font-size: 0.85rem; color: #999; }
        .review-bars { flex: 1; min-width: 200px; }
        .review-bar-row {
            display: flex; align-items: center; gap: 0.5rem;
            margin-bottom: 0.3rem; font-size: 0.82rem;
        }
        .review-bar-row span:first-child { width: 45px; text-align: right; color: #666; }
        .review-bar-track { flex: 1; height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden; }
        .review-bar-fill { height: 100%; background: #FFAB00; border-radius: 4px; }
        .review-bar-row span:last-child { width: 25px; color: #999; font-size: 0.78rem; }

        .review-form { background: #fafafa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .review-form h3 { margin-bottom: 1rem; font-size: 1rem; }
        .star-picker { display: flex; gap: 0.25rem; margin-bottom: 1rem; }
        .star-picker span {
            font-size: 1.8rem; cursor: pointer; color: #ddd; transition: color 0.15s;
        }
        .star-picker span.active, .star-picker span:hover { color: #FFAB00; }
        .review-form textarea {
            width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;
            font-family: inherit; font-size: 0.9rem; resize: vertical; min-height: 80px;
        }
        .review-form select {
            padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;
            font-family: inherit; font-size: 0.85rem; margin-bottom: 0.75rem;
        }
        .review-form button {
            margin-top: 0.75rem; padding: 0.6rem 1.5rem;
            background: var(--primary-color); color: #fff; border: none;
            border-radius: 999px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
        }
        .review-card {
            padding: 1.25rem 0; border-bottom: 1px solid #f0f0f0;
        }
        .review-card:last-child { border-bottom: none; }
        .review-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .review-user { font-weight: 600; font-size: 0.9rem; }
        .review-date { font-size: 0.78rem; color: #999; }
        .review-stars { color: #FFAB00; font-size: 0.95rem; margin-bottom: 0.4rem; }
        .review-comment { font-size: 0.9rem; color: #444; line-height: 1.5; }
        .no-reviews { text-align: center; padding: 2rem; color: #999; }
    </style>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="product-details-container" id="product-details">
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #999;">Loading product...
                </div>
            </div>
            <!-- Reviews Section -->
            <div class="reviews-section" id="reviews-section">
                <h2>Customer Reviews</h2>
                <div id="reviews-content"><div style="text-align:center;padding:1rem;color:#999;">Loading reviews...</div></div>
            </div>

            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">You May Also Like</h2>
                </div>
                <div class="products-grid" id="related-products"></div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const { getProductById, formatCurrency, renderStars, getProducts, addToCart } = window.DailyToinks;
            const urlParams = new URLSearchParams(window.location.search);
            const productId = parseInt(urlParams.get('id'));

            const product = await getProductById(productId);

            if (!product) {
                document.getElementById('product-details').innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <h2>Product not found</h2>
                        <a href="products.php" class="btn btn-primary mt-2">Back to Products</a>
                    </div>`;
                return;
            }

            document.title = `${product.name} - DailyToinks`;
            document.getElementById('product-details').innerHTML = `
                <div class="product-image-section">
                    <img src="${(product.images && product.images.length) ? product.images[0].image_path : product.image}" alt="${product.name}" class="product-main-image" id="main-product-image">
                    ${(product.images && product.images.length > 1) ? `
                    <div class="product-thumbnails">
                        ${product.images.map((img, i) => `
                            <img src="${img.image_path}" alt="${product.name}" class="product-thumb ${i === 0 ? 'active' : ''}" onclick="switchImage(this, '${img.image_path}')">
                        `).join('')}
                    </div>` : ''}
                </div>
                <div class="product-details-info">
                    <h1>${product.name}</h1>
                    <div class="product-rating">
                        <span class="stars" style="font-size: 1.2rem;">${renderStars(product.rating)}</span>
                        <span class="rating-count">(${product.rating} rating)</span>
                    </div>
                    <div class="product-details-price">${formatCurrency(product.price)}</div>
                    <div class="stock-status ${product.stock > 20 ? 'in-stock' : 'low-stock'}">
                        ${product.stock > 20 ? '✓ In Stock' : '⚠ Only ' + product.stock + ' left!'}
                    </div>
                    ${!window.__IS_STAFF__ ? `<div class="quantity-selector">
                        <label>Quantity:</label>
                        <button class="qty-btn" onclick="decreaseQty()">-</button>
                        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="${product.stock}">
                        <button class="qty-btn" onclick="increaseQty()">+</button>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="addToCartWithQty()">🛒 Add to Cart</button>
                        <button class="btn btn-success" onclick="buyNow()">⚡ Buy Now</button>
                    </div>` : '<div style="padding:0.75rem 0;color:#888;font-style:italic;">Staff accounts cannot purchase items.</div>'}
                    <div class="product-description">
                        <h3>Product Description</h3>
                        <div class="product-description-content">
                            ${product.description || `<p>High-quality ${(product.category_name || '').toLowerCase()} product.</p>`}
                        </div>
                        <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #eee;">
                            <ul style="padding-left:1.5rem;color:#666;font-size:0.9rem;">
                                <li>Category: ${product.category_name || 'N/A'}</li>
                                <li>Free shipping on orders over ₱2,500</li>
                                <li>7-day return policy</li>
                            </ul>
                        </div>
                    </div>
                </div>`;

            // Load related products
            const relatedData = await getProducts({ category: product.category_name });
            if (relatedData.success) {
                const related = relatedData.products.filter(p => p.id != product.id).slice(0, 4);
                document.getElementById('related-products').innerHTML = related.map(p => `
                    <div class="product-card">
                        <a href="product-details.php?id=${p.id}"><img src="${p.image}" alt="${p.name}" class="product-image"></a>
                        <div class="product-info">
                            <a href="product-details.php?id=${p.id}"><h3 class="product-name">${p.name}</h3></a>
                            <div class="product-price">${formatCurrency(p.price)}</div>
                            <div class="product-rating"><span class="stars">${renderStars(p.rating)}</span></div>
                            ${!window.__IS_STAFF__ ? `<div class="product-actions">
                                <button class="add-to-cart-btn" onclick="window.DailyToinks.addToCart(${p.id})">Add to Cart</button>
                            </div>` : ''}
                        </div>
                    </div>
                `).join('');
            }
        });

        function switchImage(thumb, src) {
            document.getElementById('main-product-image').src = src;
            document.querySelectorAll('.product-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }
        function increaseQty() { const i = document.getElementById('quantity'); if (parseInt(i.value) < parseInt(i.max)) i.value = parseInt(i.value) + 1; }
        function decreaseQty() { const i = document.getElementById('quantity'); if (parseInt(i.value) > 1) i.value = parseInt(i.value) - 1; }
        function addToCartWithQty() {
            const id = parseInt(new URLSearchParams(window.location.search).get('id'));
            const qty = parseInt(document.getElementById('quantity').value);
            window.DailyToinks.addToCart(id, qty);
        }
        function buyNow() { addToCartWithQty(); setTimeout(() => { window.location.href = 'cart.php'; }, 500); }

        // === REVIEWS ===
        async function loadReviews() {
            const productId = parseInt(new URLSearchParams(window.location.search).get('id'));
            const res = await fetch(`/normss/api/reviews.php?product_id=${productId}`);
            const data = await res.json();
            if (!data.success) return;

            const { reviews, summary, can_review } = data;
            const { renderStars } = window.DailyToinks;
            const total = parseInt(summary.total) || 0;
            const avg = parseFloat(summary.average) || 0;

            let html = '';

            // Summary bar
            if (total > 0) {
                html += `<div class="review-summary">
                    <div class="review-avg">
                        <div class="review-avg-number">${avg.toFixed(1)}</div>
                        <div class="review-avg-stars">${renderStars(avg)}</div>
                        <div class="review-avg-count">${total} review${total !== 1 ? 's' : ''}</div>
                    </div>
                    <div class="review-bars">
                        ${[5,4,3,2,1].map(s => {
                            const cnt = parseInt(summary['star' + s]) || 0;
                            const pct = total > 0 ? (cnt / total * 100) : 0;
                            return `<div class="review-bar-row">
                                <span>${s} star</span>
                                <div class="review-bar-track"><div class="review-bar-fill" style="width:${pct}%"></div></div>
                                <span>${cnt}</span>
                            </div>`;
                        }).join('')}
                    </div>
                </div>`;
            }

            // Review form (if eligible)
            if (can_review && can_review.length > 0 && !window.__IS_STAFF__) {
                html += `<div class="review-form">
                    <h3>Write a Review</h3>
                    <label style="font-size:0.85rem;color:#666;">Order:</label>
                    <select id="review-order">
                        ${can_review.map(o => `<option value="${o.order_id}">${o.order_number}</option>`).join('')}
                    </select><br>
                    <label style="font-size:0.85rem;color:#666;">Rating:</label>
                    <div class="star-picker" id="star-picker">
                        ${[1,2,3,4,5].map(i => `<span data-val="${i}" onclick="pickStar(${i})">★</span>`).join('')}
                    </div>
                    <textarea id="review-comment" placeholder="Share your experience with this product..."></textarea>
                    <button onclick="submitReview()">Submit Review</button>
                    <div id="review-msg" style="margin-top:0.5rem;font-size:0.85rem;"></div>
                </div>`;
            }

            // Review list
            if (reviews.length > 0) {
                html += reviews.map(r => `
                    <div class="review-card">
                        <div class="review-card-header">
                            <span class="review-user">${r.user_name}</span>
                            <span class="review-date">${new Date(r.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                        </div>
                        <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
                        ${r.comment ? `<div class="review-comment">${r.comment.replace(/</g,'&lt;')}</div>` : ''}
                    </div>
                `).join('');
            } else {
                html += '<div class="no-reviews">No reviews yet. Be the first to review this product!</div>';
            }

            document.getElementById('reviews-content').innerHTML = html;
        }

        let selectedRating = 0;
        function pickStar(val) {
            selectedRating = val;
            document.querySelectorAll('#star-picker span').forEach(s => {
                s.classList.toggle('active', parseInt(s.dataset.val) <= val);
            });
        }

        async function submitReview() {
            const productId = parseInt(new URLSearchParams(window.location.search).get('id'));
            const orderId = parseInt(document.getElementById('review-order').value);
            const comment = document.getElementById('review-comment').value.trim();
            const msgEl = document.getElementById('review-msg');

            if (!selectedRating) { msgEl.innerHTML = '<span style="color:red;">Please select a rating</span>'; return; }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch('/normss/api/reviews.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ product_id: productId, order_id: orderId, rating: selectedRating, comment })
            });
            const data = await res.json();
            if (data.success) {
                msgEl.innerHTML = '<span style="color:green;">Review submitted!</span>';
                selectedRating = 0;
                setTimeout(loadReviews, 500);
            } else {
                msgEl.innerHTML = `<span style="color:red;">${data.message}</span>`;
            }
        }

        loadReviews();
    </script>
</body>

</html>