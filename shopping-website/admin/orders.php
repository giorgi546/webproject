<?php
require_once '../includes/config.php';
require_admin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $order_id = intval($_POST['order_id']);
                $new_status = $_POST['status'];
                
                $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$new_status, $order_id])) {
                    $_SESSION['success'] = "Order status updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update order status";
                }
                break;
                
            case 'update_payment_status':
                $order_id = intval($_POST['order_id']);
                $payment_status = $_POST['payment_status'];
                
                $stmt = $db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$payment_status, $order_id])) {
                    $_SESSION['success'] = "Payment status updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update payment status";
                }
                break;
                
            case 'delete_order':
                $order_id = intval($_POST['order_id']);
                
                $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    $_SESSION['success'] = "Order deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete order";
                }
                break;
        }
        header('Location: orders.php');
        exit;
    }
}

// Get orders with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where_clause 
        ORDER BY o.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetch()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get order statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue
FROM orders";
$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute();
$order_stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - <?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin_orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>Orders Management</h1>
        </div>
    </div>
    
    <nav class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="orders.php" class="active">Orders</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <!-- Order Statistics -->
        <div class="order-stats">
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($order_stats['total_orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock" style="color: #D97706;"></i>
                </div>
                <div class="stat-number"><?php echo number_format($order_stats['pending_orders']); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cog" style="color: #2563EB;"></i>
                </div>
                <div class="stat-number"><?php echo number_format($order_stats['processing_orders']); ?></div>
                <div class="stat-label">Processing</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-truck" style="color: #059669;"></i>
                </div>
                <div class="stat-number"><?php echo number_format($order_stats['shipped_orders']); ?></div>
                <div class="stat-label">Shipped</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle" style="color: #065F46;"></i>
                </div>
                <div class="stat-number"><?php echo number_format($order_stats['delivered_orders']); ?></div>
                <div class="stat-label">Delivered</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($order_stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search orders, customers..." 
                   value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <select name="status" class="form-select" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <select name="payment" class="form-select" style="width: auto;">
                <option value="">All Payments</option>
                <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Payment Pending</option>
                <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="orders.php" class="btn">Clear</a>
        </form>

        <!-- Orders Table -->
        <div class="admin-card">
            <div class="card-header">
                <h3>Orders (<?php echo number_format($total_orders); ?> total)</h3>
            </div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['id']); ?></strong>
                                <div class="order-details">
                                    ID: <?php echo $order['id']; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                    <div class="order-details"><?php echo htmlspecialchars($order['email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                <div class="order-details"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    <div class="order-details">+ $<?php echo number_format($order['shipping_cost'], 2); ?> shipping</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" class="btn btn-primary">
                                    Status
                                </button>
                                <button onclick="updatePaymentStatus(<?php echo $order['id']; ?>, '<?php echo $order['payment_status']; ?>')" class="btn btn-warning">
                                    Payment
                                </button>
                                <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" class="btn btn-success">
                                    View
                                </button>
                                <button onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['id']); ?>')" class="btn btn-danger">
                                    Delete
                                </button>
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
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>" 
                       class="btn <?php echo $i === $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Update Order Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h2>Update Order Status</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="status_order_id">
                
                <div class="form-group">
                    <label class="form-label">Order Status</label>
                    <select name="status" id="status_select" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">Update Status</button>
                    <button type="button" onclick="closeModal('statusModal')" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Payment Status Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h2>Update Payment Status</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_payment_status">
                <input type="hidden" name="order_id" id="payment_order_id">
                
                <div class="form-group">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" id="payment_select" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">Update Payment</button>
                    <button type="button" onclick="closeModal('paymentModal')" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <h2>Order Details</h2>
            <div id="orderDetailsContent">
                Loading...
            </div>
            <div style="margin-top: 2rem;">
                <button type="button" onclick="closeModal('detailsModal')" class="btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            openModal('statusModal');
        }

        function updatePaymentStatus(orderId, currentPaymentStatus) {
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('payment_select').value = currentPaymentStatus;
            openModal('paymentModal');
        }

        function viewOrderDetails(orderId) {
            document.getElementById('orderDetailsContent').innerHTML = 'Loading order details...';
            openModal('detailsModal');
            
            // In a real implementation, you'd fetch order details via AJAX
            setTimeout(() => {
                document.getElementById('orderDetailsContent').innerHTML = `
                    <p><strong>Order ID:</strong> ${orderId}</p>
                    <p><strong>Note:</strong> Detailed order information would be loaded here via AJAX in a complete implementation.</p>
                    <p>This would include:</p>
                    <ul>
                        <li>Order items and quantities</li>
                        <li>Shipping address</li>
                        <li>Payment method</li>
                        <li>Order timeline</li>
                    </ul>
                `;
            }, 1000);
        }

        function deleteOrder(orderId, orderNumber) {
            if (confirm(`Are you sure you want to delete order "${orderNumber}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" value="${orderId}">
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

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; background: #D1FAE5; border: 1px solid #10B981; color: #065F46;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.style.display = 'none');
        }, 5000);
    </script>
</body>
</html>