<?php
require_once 'includes/config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'";

$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get related products from same category
$related_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
                ORDER BY RAND() 
                LIMIT 4";

$related_stmt = $db->prepare($related_sql);
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

// Parse gallery images
$gallery_images = [];
if ($product['gallery_images']) {
    $gallery_images = json_decode($product['gallery_images'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($product['short_description'] ?? substr($product['description'], 0, 160)); ?>">
    
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    

</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Product Hero -->
    <section class="product-hero">
        <div class="container">
            <nav class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="shop.php">Shop</a>
                <?php if ($product['category_name']): ?>
                    <i class="fas fa-chevron-right"></i>
                    <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                <?php endif; ?>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Details -->
    <section class="product-section">
        <div class="container">
            <div class="product-container">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image" onclick="openZoom('<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : ''; ?>')">
                        <img src="<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             id="mainImage"
                             onerror="this.src='https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'">
                    </div>
                    
                    <?php if (!empty($gallery_images) || $product['main_image']): ?>
                        <div class="image-thumbnails">
                            <!-- Main image thumbnail -->
                            <div class="thumbnail active" onclick="changeMainImage('<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>')">
                                <img src="<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>" 
                                     alt="Main image"
                                     onerror="this.src='https://via.placeholder.com/80x80/f3f4f6/9ca3af?text=No+Image'">
                            </div>
                            
                            <!-- Gallery thumbnails -->
                            <?php foreach ($gallery_images as $image): ?>
                                <div class="thumbnail" onclick="changeMainImage('uploads/products/<?php echo $image; ?>')">
                                    <img src="uploads/products/<?php echo $image; ?>" 
                                         alt="Gallery image"
                                         onerror="this.src='https://via.placeholder.com/80x80/f3f4f6/9ca3af?text=No+Image'">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-category">
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                    
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Product Rating -->
                    <div class="product-rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <span class="rating-text">(4.2 out of 5 - <?php echo rand(15, 89); ?> reviews)</span>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price">
                        <?php if ($product['sale_price']): ?>
                            <span class="price-sale">$<?php echo number_format($product['sale_price'], 2); ?></span>
                            <span class="price-original">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php 
                            $savings = $product['price'] - $product['sale_price'];
                            $savings_percent = round(($savings / $product['price']) * 100);
                            ?>
                            <div class="price-savings">
                                <i class="fas fa-tag"></i> Save $<?php echo number_format($savings, 2); ?> (<?php echo $savings_percent; ?>% off)
                            </div>
                        <?php else: ?>
                            <span class="price-current">$<?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="stock-info <?php echo $product['stock_quantity'] <= 0 ? 'out-of-stock' : ($product['stock_quantity'] <= 5 ? 'low-stock' : 'in-stock'); ?>">
                        <i class="fas fa-<?php echo $product['stock_quantity'] <= 0 ? 'times-circle' : ($product['stock_quantity'] <= 5 ? 'exclamation-triangle' : 'check-circle'); ?>"></i>
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            Out of Stock
                        <?php elseif ($product['stock_quantity'] <= 5): ?>
                            Only <?php echo $product['stock_quantity']; ?> left in stock - Order soon!
                        <?php else: ?>
                            In Stock (<?php echo $product['stock_quantity']; ?> available)
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <!-- Key Features -->
                    <div class="product-features">
                        <h3 class="features-title">Key Features:</h3>
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> High quality materials and construction</li>
                            <li><i class="fas fa-check"></i> Fast and reliable shipping</li>
                            <li><i class="fas fa-check"></i> 30-day money-back guarantee</li>
                            <li><i class="fas fa-check"></i> Expert customer support</li>
                            <?php if ($product['weight']): ?>
                                <li><i class="fas fa-check"></i> Lightweight design (<?php echo htmlspecialchars($product['weight']); ?> lbs)</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <!-- Quantity Selector -->
                        <div class="quantity-section">
                            <label class="quantity-label">
                                <i class="fas fa-shopping-cart"></i> Quantity:
                            </label>
                            <div class="quantity-controls">
                                <div class="qty-wrapper">
                                    <button type="button" class="qty-btn qty-decrease">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="qty-input" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $product['stock_quantity']; ?>"
                                           id="quantityInput">
                                    <button type="button" class="qty-btn qty-increase">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="stock-info in-stock">
                                    <i class="fas fa-info-circle"></i>
                                    Maximum <?php echo $product['stock_quantity']; ?> items
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Actions -->
                        <div class="product-actions">
                            <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="product-actions">
                            <button class="add-to-cart-btn" disabled>
                                <i class="fas fa-times"></i>
                                Out of Stock
                            </button>
                            <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Product Meta -->
                    <div class="product-meta">
                        <?php if ($product['sku']): ?>
                            <div class="meta-item">
                                <span class="meta-label">SKU:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['sku']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['weight']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Weight:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['weight']); ?> lbs</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['dimensions']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Dimensions:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['dimensions']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value">
                                <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>" style="color: #3B82F6; text-decoration: none;">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Tags:</span>
                            <span class="meta-value">
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem; font-size: 0.85rem;">Quality</span>
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem; font-size: 0.85rem;">Popular</span>
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">Trending</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section class="related-products">
            <div class="container">
                <h2 class="section-title">Related Products</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="related-card">
                            <div class="related-image">
                                <img src="<?php echo $related['main_image'] ? 'uploads/products/' . $related['main_image'] : 'https://via.placeholder.com/280x200/f3f4f6/9ca3af?text=No+Image'; ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/280x200/f3f4f6/9ca3af?text=No+Image'">
                            </div>
                            <div class="related-info">
                                <h3 class="related-name">
                                    <a href="product.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </a>
                                </h3>
                                <div class="related-price">
                                    <?php if ($related['sale_price']): ?>
                                        $<?php echo number_format($related['sale_price'], 2); ?>
                                        <span style="color: #9CA3AF; text-decoration: line-through; font-size: 0.9rem; margin-left: 0.5rem;">
                                            $<?php echo number_format($related['price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        $<?php echo number_format($related['price'], 2); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Image Zoom Overlay -->
    <div class="zoom-overlay" id="zoomOverlay" onclick="closeZoom()">
        <span class="zoom-close" onclick="closeZoom()">&times;</span>
        <img class="zoom-image" id="zoomImage" src="" alt="Zoomed image">
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/product.js"></script>
    
   
</body>
</html>