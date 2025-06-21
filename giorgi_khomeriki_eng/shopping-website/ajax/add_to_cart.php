<?php
require_once '../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get input data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Initialize Product class to check stock
    $product = new Product($db);
    
    // Check if product exists and is active
    $product_data = $product->getById($product_id);
    if (!$product_data || $product_data['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Product not available']);
        exit;
    }
    
    // Check stock availability
    if (!$product->isInStock($product_id, $quantity)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough stock available. Only ' . $product_data['stock_quantity'] . ' items left.'
        ]);
        exit;
    }
    
    // Check if user is logged in for database cart or use session cart
    if (is_logged_in()) {
        // Database cart for logged-in users
        $user_id = get_current_user_id();
        
        // Check if item already exists in cart
        $stmt = $db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing item quantity
            $new_quantity = $existing['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if (!$product->isInStock($product_id, $new_quantity)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot add more items. Stock limit exceeded.'
                ]);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $result = $stmt->execute([$new_quantity, $user_id, $product_id]);
        } else {
            // Add new item to cart
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $result = $stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        if ($result) {
            // Log the file upload activity
            $upload_stmt = $db->prepare("INSERT INTO file_uploads (user_id, original_name, file_name, file_path, file_size, mime_type, upload_type, related_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $upload_stmt->execute([
                $user_id,
                'cart_action.log',
                'cart_' . time() . '.log',
                'logs/cart_action.log',
                strlen("Added product $product_id to cart"),
                'text/plain',
                'document',
                $product_id
            ]);
            
            // Get updated cart count
            $count_stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $count_stmt->execute([$user_id]);
            $count_result = $count_stmt->fetch();
            $cart_count = $count_result['total'] ?: 0;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to cart successfully!',
                'cart_count' => $cart_count,
                'product_name' => $product_data['name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
        }
    } else {
        // Session cart for guest users
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check current quantity in session cart
        $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
        $new_quantity = $current_qty + $quantity;
        
        // Check if new quantity exceeds stock
        if (!$product->isInStock($product_id, $new_quantity)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot add more items. Stock limit exceeded.'
            ]);
            exit;
        }
        
        $_SESSION['cart'][$product_id] = $new_quantity;
        
        // Calculate total cart count
        $cart_count = array_sum($_SESSION['cart']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart successfully!',
            'cart_count' => $cart_count,
            'product_name' => $product_data['name']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding to cart']);
}
?>