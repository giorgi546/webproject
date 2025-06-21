<?php
require_once 'includes/config.php';

// Require login
require_login();

// Get order number from URL
$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    redirect('index.php');
}

// Get order details
$stmt = $db->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, get_current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    redirect('index.php');
}

// Check if this is a Bitcoin payment that needs to redirect to payment page
if ($order['payment_method'] === 'bitcoin' && $order['payment_status'] === 'pending') {
    redirect("bitcoin-payment.php?order=" . urlencode($order_number));
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-hero {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 4rem 0;
            margin-top: 80px;
            color: white;
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .order-details {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 3rem 0;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .order-header {
            text-align: center;
            padding-bottom: 2rem;
            border-bottom: 2px solid #E5E7EB;
            margin-bottom: 2rem;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #F8FAFC;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
        }
        
        .info-card h4 {
            color: #1F2937;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-card h4 i {
            color: #10B981;
        }
        
        .next-steps {
            background: linear-gradient(135d, #EBF8FF 0%, #DBEAFE 100%);
            border: 2px solid #3B82F6;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-action {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
        }
        
        .btn-secondary-action {
            background: white;
            color: #374151;
            border: 2px solid #E5E7EB;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Success Hero -->
    <section class="success-hero">
        <div class="container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order has been received and is being processed.</p>
            
            <div class="checkout-steps">
                <div class="step active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                </div>
                <div class="step active">
                    <i class="fas fa-credit-card"></i>
                    <span>Checkout</span>
                </div>
                <div class="step active">
                    <i class="fas fa-check"></i>
                    <span>Complete</span>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Order Details -->
        <div class="order-details">
            <div class="order-header">
                <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                <p style="color: #6B7280; margin-top: 0.5rem;">
                    Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                </p>
            </div>

            <!-- Order Information Grid -->
            <div class="order-info-grid">
                <!-- Order Status -->
                <div class="info-card">
                    <h4><i class="fas fa-info-circle"></i> Order Status</h4>
                    <p><strong>Order Status:</strong> 
                        <span style="color: #F59E0B; font-weight: 600;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Status:</strong> 
                        <span style="color: <?php echo $order['payment_status'] === 'paid' ? '#10B981' : '#F59E0B'; ?>; font-weight: 600;">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                </div>

                <!-- Shipping Information -->
                <div class="info-card">
                    <h4><i class="fas fa-shipping-fast"></i> Shipping Information</h4>
                    <?php 
                    $shipping = json_decode($order['shipping_address'], true);
                    if ($shipping): 
                    ?>
                        <p><strong><?php echo htmlspecialchars($shipping['first_name'] . ' ' . $shipping['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($shipping['address']); ?></p>
                        <p><?php echo htmlspecialchars($shipping['city'] . ', ' . $shipping['state'] . ' ' . $shipping['zip_code']); ?></p>
                        <p><?php echo htmlspecialchars($shipping['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="info-card">
                <h4><i class="fas fa-box"></i> Order Items</h4>
                <div class="order-items">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-details" style="flex: 1;">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <span class="item-quantity">Quantity: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                <?php echo format_price($item['total']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Totals -->
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span><?php echo format_price($order['total_amount'] - $order['shipping_cost'] - $order['tax_amount']); ?></span>
                    </div>
                    <?php if ($order['shipping_cost'] > 0): ?>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span><?php echo format_price($order['shipping_cost']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span><?php echo format_price($order['tax_amount']); ?></span>
                    </div>
                    <div class="total-row total">
                        <span>Total:</span>
                        <span><?php echo format_price($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps">
            <h3><i class="fas fa-lightbulb"></i> What's Next?</h3>
            <p>You will receive an email confirmation shortly with your order details and tracking information.</p>
            <p>If you have any questions about your order, please contact our customer support.</p>
            
            <div class="action-buttons">
                <a href="account.php" class="btn-action btn-primary-action">
                    <i class="fas fa-user"></i>
                    View Order in Account
                </a>
                <a href="shop.php" class="btn-action btn-secondary-action">
                    <i class="fas fa-shopping-bag"></i>
                    Continue Shopping
                </a>
                <a href="contact.php" class="btn-action btn-secondary-action">
                    <i class="fas fa-envelope"></i>
                    Contact Support
                </a>
            </div>
        </div>

        <!-- Estimated Delivery -->
        <?php if ($order['status'] !== 'cancelled'): ?>
            <div style="background: #F0FDF4; border: 2px solid #10B981; border-radius: 12px; padding: 2rem; margin: 2rem 0; text-align: center;">
                <h3 style="color: #065F46; margin-bottom: 1rem;">
                    <i class="fas fa-truck"></i> Estimated Delivery
                </h3>
                <p style="color: #047857; font-size: 1.1rem; font-weight: 600;">
                    <?php echo date('F j, Y', strtotime('+5 days', strtotime($order['created_at']))); ?> - 
                    <?php echo date('F j, Y', strtotime('+7 days', strtotime($order['created_at']))); ?>
                </p>
                <p style="color: #065F46; margin-top: 0.5rem;">
                    We'll send you tracking information once your order ships.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        // Auto-hide any flash messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>