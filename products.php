<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "All Products";
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
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title" id="page-title">All Products</h2>
                    <div id="product-count"></div>
                </div>

                <!-- Category Filter -->
                <div style="margin-bottom: 2rem;">
                    <select id="category-filter"
                        style="padding: 0.75rem; border: 2px solid var(--primary-color); border-radius: 4px; font-size: 1rem;">
                        <option value="all">All Categories</option>
                    </select>
                </div>

                <!-- Products Grid -->
                <div class="products-grid" id="products-grid">
                    <div style="grid-column: 1/-1; text-align:center; padding:2rem; color:#999;">Loading products...
                    </div>
                </div>

                <!-- No Results Message -->
                <div id="no-results" class="text-center" style="display: none; padding: 3rem 0;">
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-primary mt-2">View All Products</a>
                </div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const { formatCurrency, renderStars, getProducts, getCategories } = window.DailyToinks;
            const productsGrid = document.getElementById('products-grid');
            const categoryFilter = document.getElementById('category-filter');
            const pageTitle = document.getElementById('page-title');
            const productCount = document.getElementById('product-count');
            const noResults = document.getElementById('no-results');

            // Populate category filter
            const catData = await getCategories();
            if (catData.success) {
                catData.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.name;
                    option.textContent = `${cat.icon} ${cat.name}`;
                    categoryFilter.appendChild(option);
                });
            }

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const categoryParam = urlParams.get('category');
            const searchParam = urlParams.get('search');

            if (categoryParam) {
                categoryFilter.value = categoryParam;
                pageTitle.textContent = categoryParam;
            } else if (searchParam) {
                pageTitle.textContent = `Search results for "${searchParam}"`;
            }

            async function displayProducts(params = {}) {
                productsGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:#999;">Loading...</div>';
                const data = await getProducts(params);

                if (!data.success || !data.products || data.products.length === 0) {
                    productsGrid.style.display = 'none';
                    noResults.style.display = 'block';
                    productCount.textContent = '';
                    return;
                }

                productsGrid.style.display = 'grid';
                noResults.style.display = 'none';
                productCount.textContent = `${data.products.length} products found`;

                productsGrid.innerHTML = data.products.map(product => `
                    <div class="product-card">
                        <a href="product-details.php?id=${product.id}">
                            <img src="${(product.images && product.images.length) ? product.images[0].image_path : product.image}" alt="${product.name}" class="product-image">
                        </a>
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
            }

            // Initial load
            let params = {};
            if (searchParam) params.search = searchParam;
            else if (categoryParam) params.category = categoryParam;
            await displayProducts(params);

            // Category filter change
            categoryFilter.addEventListener('change', (e) => {
                const category = e.target.value;
                if (category === 'all') {
                    pageTitle.textContent = 'All Products';
                    displayProducts();
                } else {
                    pageTitle.textContent = category;
                    displayProducts({ category });
                }
            });
        });
    </script>
</body>

</html>