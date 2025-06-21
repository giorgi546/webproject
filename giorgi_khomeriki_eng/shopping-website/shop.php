<?php
require_once 'includes/config.php';

// Smart image mapping for products
$image_map = [
    'Smartphone Pro' => 's24-ultra.webp',
    'Laptop Ultra' => 'asus-ROG.jpg', 
    'Cotton T-Shirt' => 't-shirt.jpeg',
    'Programming Book' => 'hacking-book.jpg'
];

// Simple product fetching without complex class methods
$filters = [];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for filters
$where_conditions = ['p.status = :status'];
$params = ['status' => 'active'];

if (!empty($_GET['category'])) {
    $where_conditions[] = 'c.name = :category';
    $params['category'] = sanitize_input($_GET['category']);
}

if (!empty($_GET['search'])) {
    $where_conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
    $params['search'] = '%' . sanitize_input($_GET['search']) . '%';
}

if (!empty($_GET['min_price'])) {
    $where_conditions[] = 'COALESCE(p.sale_price, p.price) >= :min_price';
    $params['min_price'] = floatval($_GET['min_price']);
}

if (!empty($_GET['max_price'])) {
    $where_conditions[] = 'COALESCE(p.sale_price, p.price) <= :max_price';
    $params['max_price'] = floatval($_GET['max_price']);
}

if (isset($_GET['featured'])) {
    $where_conditions[] = 'p.featured = 1';
}

$where_clause = implode(' AND ', $where_conditions);

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE {$where_clause}
        ORDER BY p.created_at DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE {$where_clause}";

$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue(':' . $key, $value);
}
$count_stmt->execute();
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Get categories
$cat_stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="css/shop.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Shop Hero Section -->
    <section class="shop-hero">
        <div class="container">
            <div class="shop-hero-content">
                <nav class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Shop</span>
                    <?php if (!empty($_GET['category'])): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo htmlspecialchars($_GET['category']); ?></span>
                    <?php endif; ?>
                </nav>
                
                <h1>
                    <?php if (!empty($_GET['search'])): ?>
                        Search: "<?php echo htmlspecialchars($_GET['search']); ?>"
                    <?php elseif (!empty($_GET['category'])): ?>
                        <?php echo htmlspecialchars($_GET['category']); ?>
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                <p>Discover amazing products at unbeatable prices</p>
            </div>
        </div>
    </section>

    <!-- Main Shop Content -->
    <div class="container">
        <div class="shop-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar" id="filtersSidebar">
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Filters</h3>
                    
                    <!-- Categories -->
                    <div class="filter-group">
                        <h4>Categories</h4>
                        <?php foreach ($categories as $cat): ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="cat_<?php echo $cat['id']; ?>" 
                                       class="category-filter" 
                                       value="<?php echo htmlspecialchars($cat['name']); ?>"
                                       <?php echo (!empty($_GET['category']) && $_GET['category'] === $cat['name']) ? 'checked' : ''; ?>>
                                <label for="cat_<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="filter-group">
                        <h4>Price Range</h4>
                        <div class="price-filter">
                            <div class="price-inputs">
                                <input type="number" 
                                       id="minPrice" 
                                       placeholder="Min ($)" 
                                       value="<?php echo $_GET['min_price'] ?? ''; ?>"
                                       min="0">
                                <input type="number" 
                                       id="maxPrice" 
                                       placeholder="Max ($)" 
                                       value="<?php echo $_GET['max_price'] ?? ''; ?>"
                                       min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Special Offers -->
                    <div class="filter-group">
                        <h4>Special Offers</h4>
                        <div class="filter-option">
                            <input type="checkbox" 
                                   id="featured" 
                                   class="featured-filter" 
                                   <?php echo isset($_GET['featured']) ? 'checked' : ''; ?>>
                            <label for="featured">Featured Products</label>
                        </div>
                    </div>
                    
                    <button class="clear-filters-btn" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear All Filters
                    </button>
                </div>
            </aside>

            <!-- Products Area -->
            <main class="products-area">
                <!-- Mobile Filter Toggle -->
                <button class="filter-mobile-toggle" onclick="toggleFilters()">
                    <i class="fas fa-filter"></i> Show Filters
                </button>
                
                <!-- Toolbar -->
                <div class="shop-toolbar">
                    <div class="product-count">
                        Showing <span class="results-highlight"><?php echo count($products); ?></span> of 
                        <span class="results-highlight"><?php echo $total_products; ?></span> products
                        <?php if ($page > 1): ?>
                            (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </div>
                    <select class="sort-dropdown" onchange="sortProducts(this.value)">
                        <option value="newest">Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="name">Name A-Z</option>
                        <option value="featured">Featured First</option>
                    </select>
                </div>

                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $item): ?>
                            <?php
                            // Get correct image path
                            $image_src = 'images/placeholder.jpg'; // Default fallback
                            
                            if ($item['main_image']) {
                                // If product has image in database, use uploads folder
                                $image_src = 'uploads/products/' . $item['main_image'];
                            } elseif (isset($image_map[$item['name']])) {
                                // Use mapped image from images folder
                                $image_src = 'images/' . $image_map[$item['name']];
                            }
                            ?>
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img src="<?php echo $image_src; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         onerror="this.src='images/asus-ROG.jpg'">
                                    
                                    <div class="product-overlay">
                                        <button class="overlay-btn quick-view" data-id="<?php echo $item['id']; ?>" title="Quick View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="overlay-btn add-to-cart" data-id="<?php echo $item['id']; ?>" title="Add to Cart">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="product-badges">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="badge sale">Sale</span>
                                        <?php endif; ?>
                                        <?php if ($item['featured']): ?>
                                            <span class="badge featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if (strtotime($item['created_at']) > strtotime('-7 days')): ?>
                                            <span class="badge new">New</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-details">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($item['category_name'] ?? 'General'); ?>
                                    </div>
                                    
                                    <h3 class="product-title">
                                        <a href="product.php?id=<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="product-description">
                                        <?php echo htmlspecialchars($item['short_description'] ?? substr($item['description'], 0, 100) . '...'); ?>
                                    </p>
                                    
                                    <div class="product-price">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="current-price">$<?php echo number_format($item['sale_price'], 2); ?></span>
                                            <span class="original-price">$<?php echo number_format($item['price'], 2); ?></span>
                                            <?php $discount = round((($item['price'] - $item['sale_price']) / $item['price']) * 100); ?>
                                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                                        <?php else: ?>
                                            <span class="current-price">$<?php echo number_format($item['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <button class="add-cart-btn" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                        <button class="wishlist-btn" data-id="<?php echo $item['id']; ?>" title="Add to Wishlist">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" 
                                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="page-btn">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-results">
                        <i class="fas fa-search"></i>
                        <h3>No Products Found</h3>
                        <p>We couldn't find any products matching your criteria.<br>Try adjusting your search or filters.</p>
                        <button class="btn btn-primary" onclick="clearFilters()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 1rem;">
                            <i class="fas fa-redo"></i> Clear Filters
                        </button>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/shop.js"></script>

</body>
</html>