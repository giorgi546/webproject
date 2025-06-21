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
    WHERE o.order_number = ? AND o.user_id = ? AND o.payment_method = 'bitcoin'
");
$stmt->execute([$order_number, get_current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Bitcoin payment order not found.";
    redirect('index.php');
}

// Get Bitcoin payment details
$bitcoin_details = json_decode($order['notes'], true);
if (!$bitcoin_details) {
    $_SESSION['error'] = "Bitcoin payment details not found.";
    redirect('order-success.php?order=' . $order_number);
}

// Calculate time remaining (15 minutes from order creation)
$order_time = strtotime($order['created_at']);
$expiry_time = $order_time + (15 * 60); // 15 minutes
$time_remaining = $expiry_time - time();
$expired = $time_remaining <= 0;

// Handle payment confirmation check
if ($_POST['action'] ?? '' === 'check_payment') {
    // Simulate checking blockchain (in real app, you'd check actual blockchain)
    $confirmed = simulate_bitcoin_confirmation($order['id']);
    
    if ($confirmed) {
        echo json_encode(['status' => 'confirmed']);
    } else {
        echo json_encode(['status' => 'pending']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin Payment - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="css/bitcoin-payment.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Bitcoin Hero -->
    <section class="bitcoin-hero">
        <div class="container">
            <h1><i class="fab fa-bitcoin"></i> Bitcoin Payment</h1>
            <p>Complete your payment using Bitcoin</p>
            <p><strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong></p>
        </div>
    </section>

    <div class="container">
        <div class="bitcoin-payment-container">
            <!-- Payment Header -->
            <div class="payment-header">
                <h2>Send Bitcoin Payment</h2>
                <?php if (!$expired): ?>
                    <div class="timer" id="timer">
                        <i class="fas fa-clock"></i>
                        <span>Time Remaining: </span>
                        <span id="countdown"><?php echo gmdate('i:s', $time_remaining); ?></span>
                    </div>
                <?php else: ?>
                    <div class="timer expired">
                        <i class="fas fa-exclamation-triangle"></i>
                        Payment window has expired
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$expired): ?>
                <!-- Payment Details -->
                <div class="payment-details">
                    <!-- Amount Display -->
                    <div class="amount-display">
                        <div class="btc-amount">
                            <?php echo number_format($bitcoin_details['amount_btc'], 8); ?> BTC
                        </div>
                        <div class="usd-amount">
                            ≈ $<?php echo number_format($bitcoin_details['amount_usd'], 2); ?> USD
                        </div>
                        <small style="color: #78350F; margin-top: 0.5rem; display: block;">
                            Rate: 1 BTC = $<?php echo number_format($bitcoin_details['exchange_rate'], 2); ?>
                        </small>
                    </div>

                    <!-- Payment Grid -->
                    <div class="payment-grid">
                        <!-- QR Code Section -->
                        <div class="qr-section">
                            <h4><i class="fas fa-qrcode"></i> Scan QR Code</h4>
                            <img src="<?php echo generate_bitcoin_qr(
                                $bitcoin_details['payment_address'], 
                                $bitcoin_details['amount_btc'], 
                                'Order ' . $order['order_number']
                            ); ?>" 
                                 alt="Bitcoin QR Code" 
                                 class="qr-code">
                            <p style="color: #6B7280; font-size: 0.9rem; margin-top: 1rem;">
                                Scan with your Bitcoin wallet app
                            </p>
                        </div>

                        <!-- Address Section -->
                        <div class="address-section">
                            <h4><i class="fas fa-wallet"></i> Send to Address</h4>
                            <p style="color: #6B7280; margin-bottom: 1rem;">
                                Copy the address below and send the exact amount:
                            </p>
                            
                            <div class="bitcoin-address" id="btcAddress">
                                <?php echo htmlspecialchars($bitcoin_details['payment_address']); ?>
                            </div>
                            
                            <button class="copy-btn" onclick="copyAddress()">
                                <i class="fas fa-copy"></i> Copy Address
                            </button>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="payment-instructions">
                        <h4><i class="fas fa-info-circle"></i> Payment Instructions</h4>
                        <ol>
                            <li><strong>Send exactly <?php echo number_format($bitcoin_details['amount_btc'], 8); ?> BTC</strong> to the address above</li>
                            <li>Include sufficient network fees to ensure fast confirmation</li>
                            <li>Do not send from an exchange - use your personal wallet</li>
                            <li>Payment must be completed within the time limit</li>
                            <li>Your order will be confirmed after 1 blockchain confirmation</li>
                        </ol>
                    </div>

                    <!-- Warning Box -->
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> Sending the wrong amount or to the wrong address will result in loss of funds. Double-check everything before sending.
                    </div>
                </div>

                <!-- Status Check -->
                <div class="status-check">
                    <p style="color: #6B7280; margin-bottom: 1rem;">
                        Sent your Bitcoin payment? Check if we've received it:
                    </p>
                    <button class="check-btn" onclick="checkPayment()">
                        <i class="fas fa-search"></i>
                        Check Payment Status
                    </button>
                    <div id="paymentStatus" style="margin-top: 1rem;"></div>
                </div>

            <?php else: ?>
                <!-- Expired Payment -->
                <div class="payment-details">
                    <div class="warning-box" style="text-align: center;">
                        <i class="fas fa-clock" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Payment Window Expired</h3>
                        <p>The 15-minute payment window for this order has expired.</p>
                        <p>Please create a new order to make a Bitcoin payment.</p>
                        
                        <div style="margin-top: 2rem;">
                            <a href="cart.php" class="btn-action btn-primary-action">
                                <i class="fas fa-shopping-cart"></i>
                                Return to Cart
                            </a>
                            <a href="shop.php" class="btn-action btn-secondary-action">
                                <i class="fas fa-shopping-bag"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        // Countdown timer
        <?php if (!$expired): ?>
        let timeRemaining = <?php echo $time_remaining; ?>;
        
        function updateTimer() {
            if (timeRemaining <= 0) {
                document.getElementById('timer').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Payment window expired';
                document.getElementById('timer').classList.add('expired');
                setTimeout(() => location.reload(), 2000);
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('countdown').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            timeRemaining--;
        }
        
        setInterval(updateTimer, 1000);
        <?php endif; ?>

        // Copy address function
        function copyAddress() {
            const address = document.getElementById('btcAddress').textContent;
            navigator.clipboard.writeText(address).then(() => {
                const btn = event.target.closest('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#10B981';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '#10B981';
                }, 2000);
            });
        }

        // Check payment status
        function checkPayment() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            btn.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_payment'
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('paymentStatus');
                
                if (data.status === 'confirmed') {
                    statusDiv.innerHTML = `
                        <div style="background: #D1FAE5; border: 2px solid #10B981; border-radius: 8px; padding: 1rem; color: #065F46;">
                            <i class="fas fa-check-circle"></i>
                            <strong>Payment Confirmed!</strong> Redirecting to order confirmation...
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = 'order-success.php?order=<?php echo urlencode($order['order_number']); ?>';
                    }, 2000);
                } else {
                    statusDiv.innerHTML = `
                        <div style="background: #FEF3C7; border: 2px solid #F59E0B; border-radius: 8px; padding: 1rem; color: #92400E;">
                            <i class="fas fa-clock"></i>
                            Payment not yet confirmed. Please wait a moment and try again.
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('paymentStatus').innerHTML = `
                    <div style="background: #FEE2E2; border: 2px solid #EF4444; border-radius: 8px; padding: 1rem; color: #991B1B;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error checking payment status. Please try again.
                    </div>
                `;
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        // Auto-check payment every 30 seconds
        <?php if (!$expired): ?>
        setInterval(() => {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_payment'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'confirmed') {
                    window.location.href = 'order-success.php?order=<?php echo urlencode($order['order_number']); ?>';
                }
            })
            .catch(() => {
                // Ignore auto-check errors
            });
        }, 30000); // Check every 30 seconds
        <?php endif; ?>
    </script>
</body>
</html>