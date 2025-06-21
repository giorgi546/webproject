<?php
require_once 'includes/config.php';

// Require login for checkout
require_login();

// Get cart items
$cart_items = get_cart_items();

if (empty($cart_items)) {
    $_SESSION['error'] = "Your cart is empty. Add some products before checkout.";
    redirect('cart.php');
}

// Calculate totals
$subtotal = calculate_cart_total($cart_items);
$shipping_cost = $subtotal > 100 ? 0 : 15.00; // Free shipping over $100
$tax_rate = 0.08; // 8% tax
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $shipping_cost + $tax_amount;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Check if Bitcoin payment - different validation rules
    $is_bitcoin_payment = $_POST['payment_method'] === 'bitcoin';
    
    if ($is_bitcoin_payment) {
        // For Bitcoin payments, require email + basic location info
        $required_fields = ['email', 'city', 'state', 'zip_code', 'payment_method'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Validate email
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        // Validate Bitcoin address
        if (empty($_POST['bitcoin_address'])) {
            $errors[] = "Please enter your Bitcoin wallet address for refunds";
        } elseif (!preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$|^bc1[a-z0-9]{39,59}$/', $_POST['bitcoin_address'])) {
            $errors[] = "Please enter a valid Bitcoin address";
        }
    } else {
        // For traditional payments, validate all shipping fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'payment_method'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Validate email
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        // Validate credit card (fake validation)
        if ($_POST['payment_method'] === 'credit_card') {
            if (empty($_POST['card_number']) || strlen($_POST['card_number']) < 16) {
                $errors[] = "Please enter a valid credit card number";
            }
            if (empty($_POST['expiry_month']) || empty($_POST['expiry_year'])) {
                $errors[] = "Please enter card expiry date";
            }
            if (empty($_POST['cvv']) || strlen($_POST['cvv']) < 3) {
                $errors[] = "Please enter a valid CVV";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create order
            $order_number = generate_order_number();
            
            $order_sql = "INSERT INTO orders (order_number, user_id, total_amount, shipping_cost, tax_amount, status, payment_status, payment_method, shipping_address, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $shipping_address = $is_bitcoin_payment ? json_encode([
                'type' => 'bitcoin_delivery',
                'email' => $_POST['email'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'zip_code' => $_POST['zip_code'],
                'bitcoin_address' => $_POST['bitcoin_address'],
                'note' => 'Bitcoin payment with delivery location'
            ]) : json_encode([
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'address' => $_POST['address'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'zip_code' => $_POST['zip_code'],
                'phone' => $_POST['phone']
            ]);
            
            $billing_address = $is_bitcoin_payment ? $shipping_address : (isset($_POST['same_as_shipping']) ? $shipping_address : json_encode([
                'first_name' => $_POST['billing_first_name'] ?? $_POST['first_name'],
                'last_name' => $_POST['billing_last_name'] ?? $_POST['last_name'],
                'address' => $_POST['billing_address'] ?? $_POST['address'],
                'city' => $_POST['billing_city'] ?? $_POST['city'],
                'state' => $_POST['billing_state'] ?? $_POST['state'],
                'zip_code' => $_POST['billing_zip_code'] ?? $_POST['zip_code']
            ]));
            
            $stmt = $db->prepare($order_sql);
            $stmt->execute([
                $order_number,
                get_current_user_id(),
                $total,
                $shipping_cost,
                $tax_amount,
                'pending',
                'pending',
                $_POST['payment_method'],
                $shipping_address,
                $billing_address
            ]);
            
            $order_id = $db->lastInsertId();
            
            // Add order items
            $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_sql);
            
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $item_total = $price * $item['quantity'];
                
                $item_stmt->execute([
                    $order_id,
                    $item['product_id'] ?? $item['id'],
                    $item['name'],
                    $item['quantity'],
                    $price,
                    $item_total
                ]);
                
                // Update product stock
                $product = new Product($db);
                $product->updateStock($item['product_id'] ?? $item['id'], $item['quantity']);
            }
            
            // Clear cart
            clear_cart();
            
            // Simulate payment processing
            if ($_POST['payment_method'] === 'credit_card') {
                // Fake payment processing - always succeeds
                $payment_status = 'paid';
                $order_status = 'processing';
            } elseif ($_POST['payment_method'] === 'bitcoin') {
                // Bitcoin payments start as pending
                $payment_status = 'pending';
                $order_status = 'pending';
                
                // Store Bitcoin payment details
                $bitcoin_details = json_encode([
                    'customer_address' => $_POST['bitcoin_address'],
                    'payment_address' => generate_bitcoin_address(),
                    'amount_btc' => convert_usd_to_btc($total),
                    'amount_usd' => $total,
                    'exchange_rate' => get_btc_exchange_rate()
                ]);
                
                // Update order with Bitcoin details
                $btc_update_sql = "UPDATE orders SET notes = ? WHERE id = ?";
                $btc_stmt = $db->prepare($btc_update_sql);
                $btc_stmt->execute([$bitcoin_details, $order_id]);
            } else {
                $payment_status = 'pending';
                $order_status = 'pending';
            }
            
            // Update order status
            $update_sql = "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$order_status, $payment_status, $order_id]);
            
            $db->commit();
            
            // Redirect to success page
            $_SESSION['success'] = "Order placed successfully! Order #: $order_number";
            redirect("order-success.php?order=$order_number");
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Error processing order. Please try again.";
            error_log("Checkout error: " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-hero">
        <div class="container">
            <h1>Checkout</h1>
            <div class="checkout-steps">
                <div class="step active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                </div>
                <div class="step active">
                    <i class="fas fa-credit-card"></i>
                    <span>Checkout</span>
                </div>
                <div class="step">
                    <i class="fas fa-check"></i>
                    <span>Complete</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form method="POST" id="checkoutForm">
                    <!-- Bitcoin Notice -->
                    <div id="bitcoinNotice" class="bitcoin-checkout-notice" style="display: none;">
                        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 2px solid #F59E0B; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                            <h3 style="color: #92400E; margin-bottom: 1rem;">
                                <i class="fab fa-bitcoin"></i> Bitcoin Payment Selected
                            </h3>
                            <p style="color: #78350F; margin-bottom: 0;">
                                For Bitcoin payments, we need your email and delivery location (city, state, ZIP code) for order processing.
                            </p>
                        </div>
                    </div>

                    <!-- Contact Information (Always Required) -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-envelope"></i> Contact Information</h2>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $_SESSION['user_email'] ?? ''); ?>" 
                                   placeholder="your@email.com" required>
                            <small style="color: #6B7280; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                We'll send order confirmations and updates to this email
                            </small>
                        </div>
                    </div>

                    <!-- Shipping Information (Modified for Bitcoin) -->
                    <div class="checkout-section" id="shippingSection">
                        <h2><i class="fas fa-shipping-fast"></i> <span id="shippingTitle">Shipping Information</span></h2>
                        
                        <!-- Full Name Fields (Hidden for Bitcoin) -->
                        <div id="nameFields" class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <!-- Phone Field (Hidden for Bitcoin) -->
                        <div id="phoneField" class="form-group">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                        </div>

                        <!-- Address Field (Hidden for Bitcoin) -->
                        <div id="addressField" class="form-group">
                            <label for="address" class="form-label">Address *</label>
                            <input type="text" id="address" name="address" class="form-input" 
                                   placeholder="Street address" 
                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                        </div>

                        <!-- Location Fields (Always Required) -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" id="city" name="city" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="state" class="form-label">City/Region *</label>
                                <select id="state" name="state" class="form-select" required>
                                    <option value="">Select City/Region</option>
                                    <option value="Tbilisi" <?php echo ($_POST['state'] ?? '') === 'Tbilisi' ? 'selected' : ''; ?>>Tbilisi</option>
                                    <option value="Kutaisi" <?php echo ($_POST['state'] ?? '') === 'Kutaisi' ? 'selected' : ''; ?>>Kutaisi</option>
                                    <option value="Batumi" <?php echo ($_POST['state'] ?? '') === 'Batumi' ? 'selected' : ''; ?>>Batumi</option>
                                    <option value="Rustavi" <?php echo ($_POST['state'] ?? '') === 'Rustavi' ? 'selected' : ''; ?>>Rustavi</option>
                                    <option value="Gori" <?php echo ($_POST['state'] ?? '') === 'Gori' ? 'selected' : ''; ?>>Gori</option>
                                    <option value="Zugdidi" <?php echo ($_POST['state'] ?? '') === 'Zugdidi' ? 'selected' : ''; ?>>Zugdidi</option>
                                    <option value="Poti" <?php echo ($_POST['state'] ?? '') === 'Poti' ? 'selected' : ''; ?>>Poti</option>
                                    <option value="Kobuleti" <?php echo ($_POST['state'] ?? '') === 'Kobuleti' ? 'selected' : ''; ?>>Kobuleti</option>
                                    <option value="Khashuri" <?php echo ($_POST['state'] ?? '') === 'Khashuri' ? 'selected' : ''; ?>>Khashuri</option>
                                    <option value="Samtredia" <?php echo ($_POST['state'] ?? '') === 'Samtredia' ? 'selected' : ''; ?>>Samtredia</option>
                                    <option value="Senaki" <?php echo ($_POST['state'] ?? '') === 'Senaki' ? 'selected' : ''; ?>>Senaki</option>
                                    <option value="Zestaponi" <?php echo ($_POST['state'] ?? '') === 'Zestaponi' ? 'selected' : ''; ?>>Zestaponi</option>
                                    <option value="Marneuli" <?php echo ($_POST['state'] ?? '') === 'Marneuli' ? 'selected' : ''; ?>>Marneuli</option>
                                    <option value="Telavi" <?php echo ($_POST['state'] ?? '') === 'Telavi' ? 'selected' : ''; ?>>Telavi</option>
                                    <option value="Akhaltsikhe" <?php echo ($_POST['state'] ?? '') === 'Akhaltsikhe' ? 'selected' : ''; ?>>Akhaltsikhe</option>
                                    <option value="Ozurgeti" <?php echo ($_POST['state'] ?? '') === 'Ozurgeti' ? 'selected' : ''; ?>>Ozurgeti</option>
                                    <option value="Kaspi" <?php echo ($_POST['state'] ?? '') === 'Kaspi' ? 'selected' : ''; ?>>Kaspi</option>
                                    <option value="Chiatura" <?php echo ($_POST['state'] ?? '') === 'Chiatura' ? 'selected' : ''; ?>>Chiatura</option>
                                    <option value="Tskaltubo" <?php echo ($_POST['state'] ?? '') === 'Tskaltubo' ? 'selected' : ''; ?>>Tskaltubo</option>
                                    <option value="Sagarejo" <?php echo ($_POST['state'] ?? '') === 'Sagarejo' ? 'selected' : ''; ?>>Sagarejo</option>
                                    <option value="Gardabani" <?php echo ($_POST['state'] ?? '') === 'Gardabani' ? 'selected' : ''; ?>>Gardabani</option>
                                    <option value="Borjomi" <?php echo ($_POST['state'] ?? '') === 'Borjomi' ? 'selected' : ''; ?>>Borjomi</option>
                                    <option value="Tkibuli" <?php echo ($_POST['state'] ?? '') === 'Tkibuli' ? 'selected' : ''; ?>>Tkibuli</option>
                                    <option value="Mtskheta" <?php echo ($_POST['state'] ?? '') === 'Mtskheta' ? 'selected' : ''; ?>>Mtskheta</option>
                                    <option value="Akhalkalaki" <?php echo ($_POST['state'] ?? '') === 'Akhalkalaki' ? 'selected' : ''; ?>>Akhalkalaki</option>
                                    <option value="Kareli" <?php echo ($_POST['state'] ?? '') === 'Kareli' ? 'selected' : ''; ?>>Kareli</option>
                                    <option value="Terjola" <?php echo ($_POST['state'] ?? '') === 'Terjola' ? 'selected' : ''; ?>>Terjola</option>
                                    <option value="Martvili" <?php echo ($_POST['state'] ?? '') === 'Martvili' ? 'selected' : ''; ?>>Martvili</option>
                                    <option value="Jvari" <?php echo ($_POST['state'] ?? '') === 'Jvari' ? 'selected' : ''; ?>>Jvari</option>
                                    <option value="Khoni" <?php echo ($_POST['state'] ?? '') === 'Khoni' ? 'selected' : ''; ?>>Khoni</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="zip_code" class="form-label">ZIP Code *</label>
                                <input type="text" id="zip_code" name="zip_code" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['zip_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information (Hidden for Bitcoin) -->
                    <div class="checkout-section" id="billingSection">
                        <h2><i class="fas fa-credit-card"></i> Billing Information</h2>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="same_as_shipping" id="same_as_shipping" checked>
                                Same as shipping address
                            </label>
                        </div>

                        <div id="billing_fields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billing_first_name" class="form-label">First Name</label>
                                    <input type="text" id="billing_first_name" name="billing_first_name" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="billing_last_name" class="form-label">Last Name</label>
                                    <input type="text" id="billing_last_name" name="billing_last_name" class="form-input">
                                </div>
                            </div>
                            <!-- Add more billing fields as needed -->
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-lock"></i> Payment Information</h2>
                        
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="credit_card" checked>
                                <div class="payment-option">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Credit Card</span>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal">
                                <div class="payment-option">
                                    <i class="fab fa-paypal"></i>
                                    <span>PayPal</span>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="bitcoin">
                                <div class="payment-option">
                                    <i class="fab fa-bitcoin"></i>
                                    <span>Bitcoin</span>
                                </div>
                            </label>
                            
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="bank_transfer">
                                <div class="payment-option">
                                    <i class="fas fa-university"></i>
                                    <span>Bank Transfer</span>
                                </div>
                            </label>
                        </div>

                        <!-- Credit Card Fields -->
                        <div id="credit_card_fields" class="payment-details">
                            <div class="form-group">
                                <label for="card_number" class="form-label">Card Number *</label>
                                <input type="text" id="card_number" name="card_number" class="form-input" 
                                       placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiry_month" class="form-label">Expiry Month *</label>
                                    <select id="expiry_month" name="expiry_month" class="form-select">
                                        <option value="">Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="expiry_year" class="form-label">Expiry Year *</label>
                                    <select id="expiry_year" name="expiry_year" class="form-select">
                                        <option value="">Year</option>
                                        <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cvv" class="form-label">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" class="form-input" 
                                           placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- Bitcoin Payment Fields -->
                        <div id="bitcoin_fields" class="payment-details" style="display: none;">
                            <div class="bitcoin-info">
                                <div class="crypto-rate">
                                    <i class="fab fa-bitcoin"></i>
                                    <span>Current Rate: 1 BTC = $<?php echo number_format(get_btc_exchange_rate(), 2); ?></span>
                                    <span class="crypto-amount">
                                        Total: <?php echo number_format(convert_usd_to_btc($total), 8); ?> BTC
                                    </span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bitcoin_address" class="form-label">Your Bitcoin Wallet Address (for refunds) *</label>
                                    <input type="text" 
                                           id="bitcoin_address" 
                                           name="bitcoin_address" 
                                           class="form-input" 
                                           placeholder="1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2">
                                    <small style="color: #6B7280; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                        <i class="fas fa-info-circle"></i>
                                        Enter your Bitcoin address to receive refunds if needed
                                    </small>
                                </div>
                                
                                <div class="bitcoin-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Bitcoin Payment Process:</strong>
                                        <ol style="margin: 0.5rem 0 0 1rem; color: #6B7280;">
                                            <li>Complete this form and click "Place Order"</li>
                                            <li>You'll receive a Bitcoin address and QR code</li>
                                            <li>Send exact Bitcoin amount within 15 minutes</li>
                                            <li>Order will be confirmed after 1 blockchain confirmation</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Payment Methods -->
                        <div id="paypal_fields" class="payment-details" style="display: none;">
                            <p class="payment-info">
                                <i class="fas fa-info-circle"></i>
                                You will be redirected to PayPal to complete your payment.
                            </p>
                        </div>

                        <div id="bank_transfer_fields" class="payment-details" style="display: none;">
                            <p class="payment-info">
                                <i class="fas fa-info-circle"></i>
                                Bank transfer details will be provided after order confirmation.
                            </p>
                        </div>
                    </div>

                    <button type="submit" class="place-order-btn">
                        <i class="fas fa-lock"></i>
                        Place Order - <?php echo format_price($total); ?>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo $item['main_image'] ? 'uploads/products/' . $item['main_image'] : 'images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                <?php echo format_price(($item['sale_price'] ?: $item['price']) * $item['quantity']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span><?php echo format_price($subtotal); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span><?php echo $shipping_cost > 0 ? format_price($shipping_cost) : 'FREE'; ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span><?php echo format_price($tax_amount); ?></span>
                    </div>
                    <div class="total-row total">
                        <span>Total:</span>
                        <span><?php echo format_price($total); ?></span>
                    </div>
                </div>

                <?php if ($shipping_cost == 0): ?>
                    <div class="free-shipping-notice">
                        <i class="fas fa-truck"></i>
                        You qualify for FREE shipping!
                    </div>
                <?php endif; ?>

                <div class="security-badges">
                    <div class="security-item">
                        <i class="fas fa-lock"></i>
                        <span>Secure Checkout</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Protected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/checkout.js"></script>
    <script src="js/main.js"></script>
    <script>
        function updateBitcoinAmount() {
            // This would fetch current BTC rate in a real implementation
            // For demo, we'll just update the display
            const cryptoRate = document.querySelector('.crypto-rate');
            if (cryptoRate) {
                // Simulate rate update
                const usdTotal = <?php echo $total; ?>;
                const btcRate = <?php echo get_btc_exchange_rate(); ?>;
                const btcAmount = (usdTotal / btcRate).toFixed(8);
            
                const cryptoAmount = cryptoRate.querySelector('.crypto-amount');
                if (cryptoAmount) {
                    cryptoAmount.textContent = `Total: ${btcAmount} BTC`;
                }
            }
        }
    </script>
</body>
</html>