<?php
require_once '../includes/config.php';
require_admin();

$product = new Product($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => floatval($_POST['price']),
                    'sale_price' => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'status' => $_POST['status']
                ];
                
                if ($product->create($data)) {
                    $_SESSION['success'] = "Product created successfully";
                } else {
                    $_SESSION['error'] = "Failed to create product";
                }
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => floatval($_POST['price']),
                    'sale_price' => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'status' => $_POST['status']
                ];
                
                if ($product->update($id, $data)) {
                    $_SESSION['success'] = "Product updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update product";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                if ($product->delete($id)) {
                    $_SESSION['success'] = "Product deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete product";
                }
                break;
        }
        header('Location: products.php');
        exit;
    }
}

// Get products
$filters = [];
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$products = $product->getAll($filters, $per_page, $offset);
$total_products = $product->getTotalCount($filters);
$total_pages = ceil($total_products / $per_page);

// Get categories
$stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin_products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>Products Management</h1>
        </div>
    </div>
    
    <nav class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <!-- Search and Add Button -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="search-input">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <button onclick="openModal('createModal')" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>

        <!-- Products Table -->
        <div class="admin-card">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td>
                                <img src="../<?php echo $prod['main_image'] ? 'uploads/products/' . $prod['main_image'] : 'images/placeholder.jpg'; ?>" 
                                     alt="Product" class="product-image">
                            </td>
                            <td><?php echo htmlspecialchars($prod['name']); ?></td>
                            <td>
                                <?php if ($prod['sale_price']): ?>
                                    <span style="text-decoration: line-through; color: #9CA3AF;">$<?php echo number_format($prod['price'], 2); ?></span>
                                    <strong>$<?php echo number_format($prod['sale_price'], 2); ?></strong>
                                <?php else: ?>
                                    $<?php echo number_format($prod['price'], 2); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $prod['stock_quantity']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $prod['status']; ?>">
                                    <?php echo ucfirst($prod['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $prod['featured'] ? '⭐' : ''; ?></td>
                            <td>
                                <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($prod)); ?>)" class="btn btn-primary">Edit</button>
                                <button onclick="deleteProduct(<?php echo $prod['id']; ?>)" class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                       class="btn <?php echo $i === $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h2>Add New Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" required></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Sale Price</label>
                        <input type="number" step="0.01" name="sale_price" class="form-input">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="featured">
                            Featured Product
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">Create Product</button>
                    <button type="button" onclick="closeModal('createModal')" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" id="edit_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-textarea" required></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Sale Price</label>
                        <input type="number" step="0.01" name="sale_price" id="edit_sale_price" class="form-input">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="edit_stock" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="edit_category" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="featured" id="edit_featured">
                            Featured Product
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">Update Product</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_sale_price').value = product.sale_price || '';
            document.getElementById('edit_stock').value = product.stock_quantity;
            document.getElementById('edit_category').value = product.category_id || '';
            document.getElementById('edit_status').value = product.status;
            document.getElementById('edit_featured').checked = product.featured == 1;
            openModal('editModal');
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>