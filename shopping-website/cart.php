<?php
require_once 'includes/config.php';

// Get cart items
$cart_items = get_cart_items();
$cart_total = 0;
$cart_count = 0;

foreach ($cart_items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $cart_total += $price * $item['quantity'];
    $cart_count += $item['quantity'];
}

// Handle cart updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $product_id = intval($_POST['product_id']);
                $quantity = max(1, intval($_POST['quantity']));
                
                if (is_logged_in()) {
                    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, get_current_user_id(), $product_id]);
                } else {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id] = $quantity;
                    }
                }
                
                $_SESSION['success'] = "Cart updated successfully";
                header('Location: cart.php');
                exit;
                
            case 'remove_item':
                $product_id = intval($_POST['product_id']);
                
                if (remove_from_cart($product_id)) {
                    $_SESSION['success'] = "Item removed from cart";
                } else {
                    $_SESSION['error'] = "Failed to remove item";
                }
                
                header('Location: cart.php');
                exit;
                
            case 'clear_cart':
                if (clear_cart()) {
                    $_SESSION['success'] = "Cart cleared successfully";
                } else {
                    $_SESSION['error'] = "Failed to clear cart";
                }
                
                header('Location: cart.php');
                exit;
        }
    }
}

// Calculate shipping
$shipping_cost = 0;
$free_shipping_threshold = 50;

if ($cart_total > 0 && $cart_total < $free_shipping_threshold) {
    $shipping_cost = 9.99;
}

$final_total = $cart_total + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>


    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Cart Hero Section -->
    <section class="cart-hero">
        <div class="container">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
            <p>Review your items and proceed to checkout</p>
        </div>
    </section>

    <!-- Main Cart Content -->
    <div class="container">
        <?php if (!empty($cart_items)): ?>
            <div class="cart-layout">
                <!-- Cart Items Section -->
                <div class="cart-items-section">
                    <div class="cart-header">
                        <h2>Your Items</h2>
                        <p class="items-count"><?php echo $cart_count; ?> item<?php echo $cart_count !== 1 ? 's' : ''; ?> in your cart</p>
                    </div>
                    
                    <div class="cart-items-list">
                        <?php foreach ($cart_items as $item): ?>
                            <?php 
                            $item_price = $item['sale_price'] ?: $item['price'];
                            $item_total = $item_price * $item['quantity'];
                            ?>
                            <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                                <div class="item-image">
                                    <img src="<?php echo $item['main_image'] ? 'uploads/products/' . $item['main_image'] : 'images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                
                                <div class="item-details">
                                    <h3>
                                        <a href="product.php?id=<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="item-price-unit">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="original-price">$<?php echo number_format($item['price'], 2); ?></span>
                                            $<?php echo number_format($item['sale_price'], 2); ?> each
                                        <?php else: ?>
                                            $<?php echo number_format($item['price'], 2); ?> each
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-stock <?php echo $item['stock_quantity'] <= 0 ? 'stock-out' : ($item['stock_quantity'] <= 5 ? 'stock-low' : 'stock-ok'); ?>">
                                        <i class="fas fa-<?php echo $item['stock_quantity'] <= 0 ? 'times-circle' : ($item['stock_quantity'] <= 5 ? 'exclamation-triangle' : 'check-circle'); ?>"></i>
                                        <?php if ($item['stock_quantity'] <= 0): ?>
                                            Out of stock
                                        <?php elseif ($item['stock_quantity'] <= 5): ?>
                                            Only <?php echo $item['stock_quantity']; ?> left
                                        <?php else: ?>
                                            In stock
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="quantity-wrapper">
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn qty-decrease" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               class="qty-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock_quantity']; ?>"
                                               data-id="<?php echo $item['id']; ?>"
                                               readonly>
                                        <button type="button" class="qty-btn qty-increase" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="item-total">
                                    $<?php echo number_format($item_total, 2); ?>
                                </div>
                                
                                <button type="button" class="remove-btn" data-id="<?php echo $item['id']; ?>" title="Remove item">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-footer">
                        <a href="shop.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <button type="button" class="clear-cart-btn" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-header">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                        <p>Review your order details</p>
                    </div>
                    
                    <div class="summary-row subtotal">
                        <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                        <span>$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Calculated at checkout</span>
                    </div>
                    
                    <!-- Shipping Progress -->
                    <?php if ($cart_total < $free_shipping_threshold && $cart_total > 0): ?>
                        <div class="shipping-progress">
                            <?php 
                            $remaining = $free_shipping_threshold - $cart_total;
                            $progress = ($cart_total / $free_shipping_threshold) * 100;
                            ?>
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-shipping-fast"></i> 
                                Add $<?php echo number_format($remaining, 2); ?> more for FREE shipping!
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%"></div>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 0.5rem;">
                                <?php echo round($progress); ?>% towards free shipping
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="shipping-info">
                            <i class="fas fa-check-circle"></i> 
                            <strong>You qualify for FREE shipping!</strong>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Coupon Section -->
                    <div class="coupon-section">
                        <div class="coupon-header">
                            <i class="fas fa-tag"></i>
                            <span>Have a promo code?</span>
                        </div>
                        <div class="coupon-input">
                            <input type="text" placeholder="Enter code" id="couponCode">
                            <button type="button" class="apply-coupon-btn" onclick="applyCoupon()">Apply</button>
                        </div>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($final_total, 2); ?></span>
                    </div>
                    
                    <?php if (is_logged_in()): ?>
                        <button type="button" class="checkout-btn" onclick="proceedToCheckout()">
                            <i class="fas fa-lock"></i> Secure Checkout
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=cart.php" class="checkout-btn" style="text-decoration: none; text-align: center; display: block;">
                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                        </a>
                    <?php endif; ?>
                    
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>SSL Secure</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-lock"></i>
                            <span>256-bit Encryption</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-credit-card"></i>
                            <span>Safe Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added anything to your cart yet.<br>Start shopping to fill it up!</p>
                <a href="shop.php" class="shop-now-btn">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    <Script src="js/cart.js"></Script>
   
</body>
</html>