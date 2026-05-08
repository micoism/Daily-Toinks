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
            <div class="banner-slider">
                <div class="banner-slide active">
                    <img src="images/flash_sale_banner.png" alt="Flash Sale - Up to 70% OFF on Electronics">
                </div>
                <div class="banner-slide">
                    <img src="images/free_shipping_banner.png" alt="Free Shipping on tech orders over ₱2,500">
                </div>
                <div class="banner-slide">
                    <img src="images/new_arrivals_banner.png"
                        alt="New Arrivals - Latest smartphones, laptops & gadgets">
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