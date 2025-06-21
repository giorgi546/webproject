<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $cart_count = get_cart_count();
    
    echo json_encode([
        'success' => true,
        'count' => $cart_count
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Error getting cart count'
    ]);
}
?>