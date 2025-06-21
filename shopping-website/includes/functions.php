<?php
// includes/functions.php - Helper functions

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format price with currency
 */
function format_price($price) {
    return '$' . number_format($price, 2);
}

/**
 * Get time ago format
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in (static version)
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return is_logged_in() ? $_SESSION['user_id'] : null;
}

/**
 * Require login - redirect if not logged in
 */
function require_login($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        redirect($redirect_to . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Require admin - redirect if not admin
 */
function require_admin($redirect_to = 'index.php') {
    if (!is_admin()) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        redirect($redirect_to);
    }
}

/**
 * Generate pagination links
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    $pagination = '';
    
    if ($total_pages <= 1) return $pagination;
    
    $pagination .= '<div class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<a href="' . $base_url . '&page=' . $prev_page . '" class="page-link prev">
            <i class="fas fa-chevron-left"></i> Previous
        </a>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $pagination .= '<a href="' . $base_url . '&page=1" class="page-link">1</a>';
        if ($start > 2) {
            $pagination .= '<span class="page-dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? ' active' : '';
        $pagination .= '<a href="' . $base_url . '&page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $pagination .= '<span class="page-dots">...</span>';
        }
        $pagination .= '<a href="' . $base_url . '&page=' . $total_pages . '" class="page-link">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<a href="' . $base_url . '&page=' . $next_page . '" class="page-link next">
            Next <i class="fas fa-chevron-right"></i>
        </a>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

/**
 * Upload file with validation
 */
function upload_file($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size: ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $upload_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination,
            'url' => $destination
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Resize image
 */
function resize_image($source, $destination, $max_width, $max_height, $quality = 85) {
    list($orig_width, $orig_height, $image_type) = getimagesize($source);
    
    // Calculate new dimensions
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    $new_width = round($orig_width * $ratio);
    $new_height = round($orig_height * $ratio);
    
    // Create image resource
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $src_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    // Save
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $destination);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $destination);
            break;
    }
    
    // Clean up
    imagedestroy($src_image);
    imagedestroy($new_image);
    
    return true;
}

/**
 * Send email (basic version - replace with PHPMailer for production)
 */
function send_email($to, $subject, $message, $from = null) {
    $from = $from ?: ADMIN_EMAIL;
    
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    // In development, just log the email
    if (defined('DEBUG') && DEBUG) {
        error_log("EMAIL TO: $to\nSUBJECT: $subject\nMESSAGE: $message");
        return true;
    }
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate SEO-friendly slug
 */
function create_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Truncate text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Generate order number
 */
function generate_order_number() {
    return 'ORD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
}

/**
 * Calculate cart total
 */
function calculate_cart_total($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $price = $item['sale_price'] ?: $item['price'];
        $total += $price * $item['quantity'];
    }
    return $total;
}

/**
 * Get cart count from session or database
 */
function get_cart_count() {
    if (is_logged_in()) {
        global $db;
        $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->execute([get_current_user_id()]);
        $result = $stmt->fetch();
        return $result['count'] ?: 0;
    } else {
        return isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
    }
}

/**
 * Add to cart (session-based for guests, database for logged-in users)
 */
function add_to_cart($product_id, $quantity = 1) {
    if (is_logged_in()) {
        global $db;
        
        // Check if item already exists in cart
        $stmt = $db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([get_current_user_id(), $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $new_quantity = $existing['quantity'] + $quantity;
            $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$new_quantity, get_current_user_id(), $product_id]);
        } else {
            // Insert new item
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            return $stmt->execute([get_current_user_id(), $product_id, $quantity]);
        }
    } else {
        // Session-based cart for guests
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        return true;
    }
}

/**
 * Remove from cart
 */
function remove_from_cart($product_id) {
    if (is_logged_in()) {
        global $db;
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([get_current_user_id(), $product_id]);
    } else {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }
    }
    return false;
}

/**
 * Get cart items
 */
function get_cart_items() {
    global $db;
    
    if (is_logged_in()) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.sale_price, p.main_image, p.stock_quantity 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([get_current_user_id()]);
        return $stmt->fetchAll();
    } else {
        if (empty($_SESSION['cart'])) return [];
        
        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $db->prepare("
            SELECT id, name, price, sale_price, main_image, stock_quantity 
            FROM products 
            WHERE id IN ($placeholders) AND status = 'active'
        ");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add quantities from session
        foreach ($products as &$product) {
            $product['quantity'] = $_SESSION['cart'][$product['id']];
        }
        
        return $products;
    }
}

/**
 * Clear cart
 */
function clear_cart() {
    if (is_logged_in()) {
        global $db;
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([get_current_user_id()]);
    } else {
        $_SESSION['cart'] = [];
        return true;
    }
}

/**
 * Log user activity
 */
function log_activity($action, $details = null) {
    if (!is_logged_in()) return;
    
    global $db;
    
    // You'd need to create an activity_log table for this
    /*
    $stmt = $db->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([
        get_current_user_id(),
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    */
}

/**
 * Get product reviews average rating
 */
function get_product_rating($product_id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
        FROM reviews 
        WHERE product_id = ? AND status = 'approved'
    ");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();
    
    return [
        'rating' => round($result['avg_rating'], 1),
        'count' => $result['review_count']
    ];
}

/**
 * Format rating stars
 */
function format_rating_stars($rating, $max = 5) {
    $stars = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max - $full_stars - ($half_star ? 1 : 0);
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $stars .= '<i class="fas fa-star text-yellow-400"></i>';
    }
    
    // Half star
    if ($half_star) {
        $stars .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars .= '<i class="far fa-star text-gray-300"></i>';
    }
    
    return $stars;
}

/**
 * Check if product is in wishlist
 */
function is_in_wishlist($product_id) {
    if (!is_logged_in()) return false;
    
    global $db;
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([get_current_user_id(), $product_id]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Debug helper - remove in production
 */
function debug_dump($data, $die = false) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    
    if ($die) die();
}

/**
 * Format Bitcoin amount with proper decimals
 */
function format_btc($amount) {
    return number_format($amount, 8, '.', '') . ' BTC';
}

/**
 * Check if Bitcoin address is valid (basic validation)
 */
function is_valid_bitcoin_address($address) {
    // Basic Bitcoin address validation
    if (preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address)) {
        return true; // Legacy address
    }
    if (preg_match('/^bc1[a-z0-9]{39,59}$/', $address)) {
        return true; // Bech32 address
    }
    return false;
}

/**
 * Simulate Bitcoin payment confirmation (for demo)
 */
function simulate_bitcoin_confirmation($order_id) {
    global $db;
    
    // In real implementation, you would check blockchain for confirmation
    // For demo, we'll randomly "confirm" some payments
    $confirmed = rand(1, 100) > 30; // 70% chance of confirmation
    
    if ($confirmed) {
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);
        return true;
    }
    
    return false;
}


if (!function_exists('get_btc_exchange_rate')) {
    function get_btc_exchange_rate() {
        
        return 140000.00; // Example: 1 BTC = $140,000
    }
}

// Convert USD to BTC using the current exchange rate
function convert_usd_to_btc($usd) {
    $rate = get_btc_exchange_rate();
    if ($rate > 0) {
        return $usd / $rate;
    }
    return 0;
}

// Dummy Bitcoin address generator (replace with real implementation as needed)
function generate_bitcoin_address() {
    // Example: return a random valid-looking Bitcoin address (for demo purposes only)
    $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $address = '1';
    for ($i = 0; $i < 33; $i++) {
        $address .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $address;
}

/**
 * Generate a Bitcoin payment QR code image URL using Google Chart API.
 * @param string $address Bitcoin address
 * @param float $amount Amount in BTC
 * @param string $label Optional label or message
 * @return string URL to QR code image
 */
function generate_bitcoin_qr($address, $amount, $label = '') {
    $uri = 'bitcoin:' . urlencode($address) . '?amount=' . urlencode(number_format($amount, 8, '.', ''));
    if (!empty($label)) {
        $uri .= '&label=' . urlencode($label);
    }
    $qr_url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($uri);
    return $qr_url;
}














?>