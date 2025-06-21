<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$product = new Product($db);
$results = $product->search($query, 5);

echo json_encode($results);
?>