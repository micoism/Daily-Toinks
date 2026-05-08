<?php
$pageTitle = "Product Management";
require_once __DIR__ . '/../config/auth.php';
requireRole(['manager']);
// Admin cannot access products page
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>Products - DailyToinks Admin</title>
    <link rel="stylesheet" href="/normss/css/styles.css">
    <link rel="stylesheet" href="/normss/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', 'Inter', sans-serif;
            margin: 0;
        }
    </style>
</head>

<body class="admin-page">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h3 class="admin-table-title">All Products</h3>
                        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                            <div class="filter-bar">
                                <input type="text" id="search-input" placeholder="Search products...">
                                <select id="category-filter">
                                    <option value="">All Categories</option>
                                </select>
                            </div>
                            <button class="topbar-btn topbar-btn-primary" onclick="openAddModal()">+ Add
                                Product</button>
                        </div>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:2rem;color:#999;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal-overlay" id="product-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Add Product</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="product-form">
                    <input type="hidden" id="product-id">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Product Name *</label>
                        <input type="text" id="product-name" class="admin-form-input" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="admin-form-group">
                            <label class="admin-form-label">Price (₱) *</label>
                            <input type="number" id="product-price" class="admin-form-input" step="0.01" min="0"
                                required>
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label">Category *</label>
                            <select id="product-category" class="admin-form-select" required>
                                <option value="">Select category</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="admin-form-group">
                            <label class="admin-form-label">Stock</label>
                            <input type="number" id="product-stock" class="admin-form-input" min="0" value="0">
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label">Rating</label>
                            <input type="number" id="product-rating" class="admin-form-input" step="0.1" min="0" max="5"
                                value="0">
                        </div>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Product Images</label>
                        <input type="file" id="product-image-files" class="admin-form-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="padding:0.4rem;">
                        <div style="margin-top:0.4rem;font-size:0.72rem;color:#666;line-height:1.4;">
                            ✓ Allowed types: <strong>JPG, PNG, GIF, WEBP</strong><br>
                            ✓ Max size: <strong>5 MB per image</strong> &nbsp;•&nbsp;
                            Max <strong>8 images per upload</strong> &nbsp;•&nbsp;
                            Max <strong>10 total per product</strong>
                        </div>
                        <div id="image-upload-error" style="margin-top:0.4rem;font-size:0.78rem;color:#B71C1C;display:none;"></div>
                        <div id="image-preview" style="margin-top:0.5rem;display:flex;flex-wrap:wrap;gap:0.5rem;"></div>
                        <div style="margin-top:0.5rem;font-size:0.75rem;color:#999;">Or paste an image URL:</div>
                        <input type="text" id="product-image" class="admin-form-input" placeholder="https://..." style="margin-top:0.25rem;">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Description</label>
                        <div id="product-description-editor" style="height:150px;background:#fff;border-radius:0 0 6px 6px;"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="topbar-btn topbar-btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="topbar-btn topbar-btn-primary" onclick="saveProduct()">Save Product</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const formatCurrency = (amount) => `₱${parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        let allCategories = [];

        const quill = new Quill('#product-description-editor', {
            theme: 'snow',
            placeholder: 'Write product description...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['blockquote'],
                    ['clean']
                ]
            }
        });

        async function loadCategories() {
            const res = await fetch('/normss/api/categories.php');
            const data = await res.json();
            allCategories = data.categories || [];

            const filter = document.getElementById('category-filter');
            const select = document.getElementById('product-category');
            allCategories.forEach(c => {
                filter.innerHTML += `<option value="${c.name}">${c.icon} ${c.name}</option>`;
                select.innerHTML += `<option value="${c.id}">${c.icon} ${c.name}</option>`;
            });
        }

        async function loadProducts() {
            const search = document.getElementById('search-input').value;
            const category = document.getElementById('category-filter').value;
            let url = '/normss/api/products.php?';
            if (search) url += `search=${encodeURIComponent(search)}&`;
            if (category) url += `category=${encodeURIComponent(category)}&`;

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById('products-tbody');

            if (!data.products || data.products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No products found</td></tr>';
                return;
            }

            tbody.innerHTML = data.products.map(p => `
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <img src="${p.images && p.images.length ? p.images[0].image_path : p.image}" alt="" style="width:42px;height:42px;border-radius:6px;object-fit:cover;background:#f0f0f0;">
                            <div>
                                <div style="font-weight:600;">${p.name}</div>
                                <div style="font-size:0.75rem;color:#999;">ID: ${p.id} · ${p.images ? p.images.length : 0} img</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge" style="background:#f0f0f0;color:#333;">${p.category_icon || ''} ${p.category_name}</span></td>
                    <td style="font-weight:700;color:var(--primary-color);">${formatCurrency(p.price)}</td>
                    <td>${p.stock <= 10 ? '<span style="color:var(--primary-color);font-weight:600;">' + p.stock + '</span>' : p.stock}</td>
                    <td>⭐ ${p.rating}</td>
                    <td><span class="badge badge-${p.status === 'active' ? 'active' : 'inactive'}">${p.status}</span></td>
                    <td>
                        <button class="action-btn" onclick="editProduct(${p.id})">Edit</button>
                        <button class="action-btn danger" onclick="deleteProduct(${p.id}, '${p.name.replace(/'/g, "\\'")}')">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        let editingProductId = null;

        function openAddModal() {
            editingProductId = null;
            document.getElementById('modal-title').textContent = 'Add Product';
            document.getElementById('product-form').reset();
            document.getElementById('product-id').value = '';
            document.getElementById('image-preview').innerHTML = '';
            quill.root.innerHTML = '';
            document.getElementById('product-modal').classList.add('active');
        }

        async function editProduct(id) {
            const res = await fetch(`/normss/api/products.php?id=${id}`);
            const data = await res.json();
            if (!data.success) return alert('Product not found');

            editingProductId = id;
            const p = data.product;
            document.getElementById('modal-title').textContent = 'Edit Product';
            document.getElementById('product-id').value = p.id;
            document.getElementById('product-name').value = p.name;
            document.getElementById('product-price').value = p.price;
            document.getElementById('product-category').value = p.category_id;
            document.getElementById('product-stock').value = p.stock;
            document.getElementById('product-rating').value = p.rating;
            document.getElementById('product-image').value = '';
            quill.root.innerHTML = p.description || '';
            document.getElementById('product-image-files').value = '';

            // Show existing images
            const preview = document.getElementById('image-preview');
            if (p.images && p.images.length > 0) {
                preview.innerHTML = p.images.map(img => `
                    <div style="position:relative;display:inline-block;" data-img-id="${img.id}">
                        <img src="${img.image_path}" style="width:80px;height:80px;border-radius:8px;object-fit:cover;border:1px solid #ddd;">
                        <button type="button" onclick="deleteImage(${img.id}, this)" style="position:absolute;top:-6px;right:-6px;background:#cc0000;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:20px;text-align:center;">×</button>
                    </div>
                `).join('');
            } else if (p.image) {
                preview.innerHTML = `<div style="position:relative;display:inline-block;">
                    <img src="${p.image}" style="width:80px;height:80px;border-radius:8px;object-fit:cover;border:1px solid #ddd;">
                </div>`;
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('product-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('product-modal').classList.remove('active');
        }

        // === IMAGE UPLOAD CLIENT-SIDE VALIDATION ===
        const ALLOWED_IMG_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const ALLOWED_IMG_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const MAX_IMG_SIZE_MB = 5;
        const MAX_IMG_BATCH = 8;

        function validateImageFiles(files) {
            const errors = [];
            if (files.length === 0) return { valid: true, errors: [] };
            if (files.length > MAX_IMG_BATCH) {
                errors.push(`Too many files selected (${files.length}). Maximum is ${MAX_IMG_BATCH} per upload.`);
            }
            for (let i = 0; i < files.length; i++) {
                const f = files[i];
                const ext = f.name.split('.').pop().toLowerCase();
                if (!ALLOWED_IMG_TYPES.includes(f.type) || !ALLOWED_IMG_EXTS.includes(ext)) {
                    errors.push(`"${f.name}": invalid type. Only JPG, PNG, GIF, WEBP allowed.`);
                }
                if (f.size > MAX_IMG_SIZE_MB * 1024 * 1024) {
                    const mb = (f.size / 1024 / 1024).toFixed(1);
                    errors.push(`"${f.name}": file is ${mb}MB. Maximum is ${MAX_IMG_SIZE_MB}MB.`);
                }
                if (f.size === 0) {
                    errors.push(`"${f.name}": file is empty.`);
                }
            }
            return { valid: errors.length === 0, errors };
        }

        function showImageError(msg) {
            const el = document.getElementById('image-upload-error');
            if (msg) {
                el.innerHTML = msg;
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
                el.innerHTML = '';
            }
        }

        // Multi-image preview for new files (with validation)
        document.getElementById('product-image-files').addEventListener('change', function() {
            const files = this.files;
            const preview = document.getElementById('image-preview');
            showImageError('');

            // Validate before previewing
            const result = validateImageFiles(files);
            if (!result.valid) {
                showImageError(result.errors.map(e => '⚠ ' + e).join('<br>'));
                this.value = ''; // clear selection
                // Restore preview to existing-only when editing
                if (editingProductId) return;
                preview.innerHTML = '';
                return;
            }

            // Don't clear existing images when editing — append new previews
            const existingHtml = editingProductId ? preview.innerHTML.replace(/<div[^>]*data-new="1"[^>]*>[\s\S]*?<\/div>/g, '') : '';
            let newHtml = '';
            for (let i = 0; i < files.length; i++) {
                const url = URL.createObjectURL(files[i]);
                newHtml += `<div data-new="1" style="position:relative;display:inline-block;">
                    <img src="${url}" style="width:80px;height:80px;border-radius:8px;object-fit:cover;border:2px solid #4CAF50;">
                    <div style="position:absolute;bottom:2px;right:2px;background:#4CAF50;color:#fff;border-radius:4px;font-size:9px;padding:1px 4px;">NEW</div>
                </div>`;
            }
            preview.innerHTML = existingHtml + newHtml;
            if (!editingProductId) document.getElementById('product-image').value = '';
        });

        async function deleteImage(imageId, btn) {
            if (!confirm('Delete this image?')) return;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(`/normss/api/product-images.php?id=${imageId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-Token': token }
            });
            const data = await res.json();
            if (data.success) {
                btn.closest('[data-img-id]').remove();
            } else {
                alert(data.message || 'Failed to delete image');
            }
        }

        async function saveProduct() {
            const id = document.getElementById('product-id').value;
            const name = document.getElementById('product-name').value;
            const price = document.getElementById('product-price').value;
            const category_id = document.getElementById('product-category').value;
            const stock = document.getElementById('product-stock').value;
            const rating = document.getElementById('product-rating').value;
            const image = document.getElementById('product-image').value;
            const description = quill.root.innerHTML.trim() === '<p><br></p>' ? '' : quill.root.innerHTML;
            const imageFiles = document.getElementById('product-image-files').files;

            if (!name || !price || !category_id) {
                return alert('Please fill in required fields');
            }

            // Final validation of selected images before sending
            if (imageFiles.length > 0) {
                const result = validateImageFiles(imageFiles);
                if (!result.valid) {
                    showImageError(result.errors.map(e => '⚠ ' + e).join('<br>'));
                    return;
                }
            }

            const token = document.querySelector('meta[name="csrf-token"]').content;

            let res;
            if (id) {
                // Edit: update text fields via JSON
                const payload = { id: parseInt(id), name, price: parseFloat(price), category_id: parseInt(category_id), stock: parseInt(stock), rating: parseFloat(rating), description };
                if (image) payload.image = image;
                res = await fetch('/normss/api/products.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify(payload)
                });

                // Upload new images if any selected
                if (imageFiles.length > 0) {
                    const imgForm = new FormData();
                    imgForm.append('product_id', id);
                    for (let i = 0; i < imageFiles.length; i++) {
                        imgForm.append('images[]', imageFiles[i]);
                    }
                    await fetch('/normss/api/product-images.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': token },
                        body: imgForm
                    });
                }
            } else {
                // Create: use FormData
                const formData = new FormData();
                formData.append('name', name);
                formData.append('price', price);
                formData.append('category_id', category_id);
                formData.append('stock', stock);
                formData.append('rating', rating);
                formData.append('description', description);
                if (image) formData.append('image', image);
                // Attach all selected files
                for (let i = 0; i < imageFiles.length; i++) {
                    formData.append('image_files[]', imageFiles[i]);
                }
                res = await fetch('/normss/api/products.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': token },
                    body: formData
                });
            }

            const data = await res.json();
            if (data.success) {
                closeModal();
                loadProducts();
            } else {
                alert(data.message);
            }
        }

        async function deleteProduct(id, name) {
            if (!confirm(`Delete "${name}"? This action cannot be undone.`)) return;
            const res = await fetch(`/normss/api/products.php?id=${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) loadProducts();
            else alert(data.message);
        }

        // Event listeners
        document.getElementById('search-input').addEventListener('input', debounce(loadProducts, 300));
        document.getElementById('category-filter').addEventListener('change', loadProducts);

        function debounce(fn, ms) {
            let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
        }

        // Init
        loadCategories().then(loadProducts);
    </script>
</body>

</html>