<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "DailyToinks - Your Trusted Electronics Store";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
</head>

<body class="store-page">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Banner Slider -->
            <style>
                .banner-css {
                    width: 100%;
                    aspect-ratio: 16/5;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    padding: 2rem 3rem;
                    color: #fff;
                    overflow: hidden;
                    position: relative;
                }
                .banner-css h2 { font-size: clamp(1.4rem, 3vw, 2.4rem); margin: 0 0 0.4rem; font-weight: 800; line-height: 1.1; }
                .banner-css p { font-size: clamp(0.85rem, 1.4vw, 1.1rem); margin: 0; opacity: 0.95; }
                .banner-css .badge {
                    display: inline-block; padding: 0.25rem 0.7rem; background: rgba(255,255,255,0.25);
                    border-radius: 999px; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.6rem;
                    backdrop-filter: blur(4px); letter-spacing: 0.5px;
                }
                .banner-flash    { background: linear-gradient(135deg, #C1001A 0%, #FF5722 50%, #FFC107 100%); }
                .banner-shipping { background: linear-gradient(135deg, #1976D2 0%, #00BCD4 100%); }
                .banner-arrivals { background: linear-gradient(135deg, #6A1B9A 0%, #E91E63 100%); }
            </style>
            <div class="banner-slider">
                <div class="banner-slide active">
                    <div class="banner-css banner-flash">
                        <div>
                            <span class="badge">⚡ LIMITED TIME</span>
                            <h2>Flash Sale</h2>
                            <p>Up to 70% OFF on Electronics</p>
                        </div>
                    </div>
                </div>
                <div class="banner-slide">
                    <div class="banner-css banner-shipping">
                        <div>
                            <span class="badge">🚚 FREE DELIVERY</span>
                            <h2>Free Shipping</h2>
                            <p>On tech orders over ₱2,500</p>
                        </div>
                    </div>
                </div>
                <div class="banner-slide">
                    <div class="banner-css banner-arrivals">
                        <div>
                            <span class="badge">✨ JUST IN</span>
                            <h2>New Arrivals</h2>
                            <p>Latest smartphones, laptops &amp; gadgets</p>
                        </div>
                    </div>
                </div>
                <div class="slider-controls">
                    <span class="slider-dot active"></span>
                    <span class="slider-dot"></span>
                    <span class="slider-dot"></span>
                </div>
            </div>

            <!-- Categories Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">Shop by Category</h2>
                </div>
                <div class="categories-grid" id="categories-grid">
                    <!-- Categories will be loaded here -->
                </div>
            </section>

            <!-- All Products Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">Our Products</h2>
                    <a href="products.php" class="view-all">View All →</a>
                </div>
                <div class="products-grid" id="all-products-grid">
                    <div style="grid-column:1/-1;text-align:center;padding:2rem;color:#999;">Loading products...</div>
                </div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const { formatCurrency, renderStars, getCategories, getProducts } = window.DailyToinks;

            // Load categories
            const catData = await getCategories();
            const categoriesGrid = document.getElementById('categories-grid');
            if (catData.success && catData.categories) {
                categoriesGrid.innerHTML = catData.categories.map(cat => `
                    <a href="products.php?category=${cat.name}" class="category-card">
                        <div class="category-icon">${cat.icon}</div>
                        <div class="category-name">${cat.name}</div>
                    </a>
                `).join('');
            }

            // Load products
            const prodData = await getProducts();
            const grid = document.getElementById('all-products-grid');
            if (prodData.success && prodData.products && prodData.products.length > 0) {
                grid.innerHTML = prodData.products.map(product => `
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <a href="product-details.php?id=${product.id}">
                                <img src="${(product.images && product.images.length) ? product.images[0].image_path : product.image}" alt="${product.name}" class="product-image">
                            </a>
                        </div>
                        <div class="product-info">
                            <a href="product-details.php?id=${product.id}">
                                <h3 class="product-name">${product.name}</h3>
                            </a>
                            <div class="product-price">${formatCurrency(product.price)}</div>
                            <div class="product-rating">
                                <span class="stars">${renderStars(product.rating)}</span>
                                <span class="rating-count">(${product.rating})</span>
                            </div>
                            ${!window.__IS_STAFF__ ? `<div class="product-actions">
                                <button class="add-to-cart-btn" onclick="window.DailyToinks.addToCart(${product.id})">
                                    Add to Cart
                                </button>
                            </div>` : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#999;">No products yet. Check back soon!</div>';
            }
        });
    </script>
</body>

</html>