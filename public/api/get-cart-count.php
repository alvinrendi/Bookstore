<?php

session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Cart.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'User not logged in'
    ]);
    exit;
}

try {
    $cart = new Cart();
    $count = $cart->getCartCount($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$count,
        'message' => 'Cart count retrieved successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>