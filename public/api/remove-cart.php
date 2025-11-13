<?php
// FILE: public/api/remove-cart.php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Cart.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$cartId = isset($data['cart_id']) ? intval($data['cart_id']) : 0;

if ($cartId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID keranjang tidak valid.'
    ]);
    exit;
}

try {
    $db = new Database();

    // Pastikan item milik user yang login
    $stmt = $db->prepare("SELECT user_id FROM cart WHERE id = ?");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Item tidak ditemukan.'
        ]);
        exit;
    }

    if ($result['user_id'] != $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'Akses tidak diizinkan.'
        ]);
        exit;
    }

    $cart = new Cart();
    if ($cart->removeFromCart($cartId)) {
        echo json_encode([
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus item dari keranjang.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
