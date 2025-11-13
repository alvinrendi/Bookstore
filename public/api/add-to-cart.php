<?php
// FILE: public/api/add-to-cart.php
// API untuk menambahkan buku ke keranjang

// Prevent any output before JSON
ob_start();

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them
ini_set('log_errors', 1);

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Silakan login terlebih dahulu'
        ]);
        exit;
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON: ' . json_last_error_msg()
        ]);
        exit;
    }

    // Validate required fields
    if (!isset($data['book_id']) || !isset($data['quantity'])) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap. Diperlukan: book_id dan quantity'
        ]);
        exit;
    }

    $book_id = (int)$data['book_id'];
    $quantity = (int)$data['quantity'];
    $user_id = (int)$_SESSION['user_id'];

    // Validate values
    if ($book_id <= 0 || $quantity <= 0) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'book_id dan quantity harus lebih dari 0'
        ]);
        exit;
    }

    // Load required files
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';
    require_once __DIR__ . '/../../classes/Cart.php';

    // Add to cart
    $cart = new Cart();
    $result = $cart->addToCart($user_id, $book_id, $quantity);

    // Clean any output buffer
    ob_clean();

    // Return response
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Berhasil menambahkan ke keranjang!',
            'cart_count' => $cart->getCartCount($user_id)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan ke keranjang. Coba lagi.'
        ]);
    }

} catch (Exception $e) {
    // Clean any output buffer
    ob_clean();
    
    // Log error
    error_log('Add to cart error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'error_details' => $e->getTrace()
    ]);
}

// Ensure no extra output
ob_end_flush();
exit;
?>