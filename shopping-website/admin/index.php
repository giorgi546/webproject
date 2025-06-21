<?php
require_once '../includes/config.php';

// Require admin access
require_admin();

// Get dashboard statistics
$stats = [];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$stmt->execute();
$stats['products'] = $stmt->fetch()['total'];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$stats['users'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$stats['orders'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stmt->execute();
$stats['revenue'] = $stmt->fetch()['total'] ?: 0;

// Recent orders
$stmt = $db->prepare("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $db->prepare("SELECT * FROM products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
    </div>
    
    <nav class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-label">Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="admin-content">
            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Orders</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <div class="order-customer"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                </div>
                                <div class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #6B7280; text-align: center;">No orders yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Low Stock -->
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">Low Stock Alert</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($low_stock)): ?>
                        <?php foreach ($low_stock as $product): ?>
                            <div class="stock-item">
                                <div class="stock-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <span class="stock-count <?php echo $product['stock_quantity'] <= 2 ? 'stock-critical' : 'stock-low'; ?>">
                                    <?php echo $product['stock_quantity']; ?> left
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #6B7280; text-align: center;">All products are well stocked!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>