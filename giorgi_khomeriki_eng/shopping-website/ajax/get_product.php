<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product_id = intval($_GET['id']);
$product = new Product($db);
$product_data = $product->getById($product_id);

if ($product_data) {
    echo json_encode($product_data);
} else {
    echo json_encode(['error' => 'Product not found']);
}
?>