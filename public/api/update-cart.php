<?php
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Cart.php';

$data = json_decode(file_get_contents("php://input"), true);
$cartId = (int)($data['cart_id'] ?? 0);
$action = $data['action'] ?? '';

$cart = new Cart();
$db = new Database();

// Ambil jumlah saat ini
$stmt = $db->prepare("SELECT quantity FROM cart WHERE id = ?");
$stmt->bind_param("i", $cartId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "message" => "Item tidak ditemukan"]);
    exit;
}

$quantity = (int)$row['quantity'];
if ($action === 'increase') $quantity++;
if ($action === 'decrease') $quantity--;

if ($quantity <= 0) {
    $cart->removeFromCart($cartId);
} else {
    $cart->updateQuantity($cartId, $quantity);
}

echo json_encode(["success" => true]);
?>
