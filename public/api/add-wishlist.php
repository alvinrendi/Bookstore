<?php
// public/api/add-wishlist.php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Wishlist.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Terima JSON atau FormData (compat)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data && !empty($_POST)) $data = $_POST;

$bookId = isset($data['book_id']) ? (int)$data['book_id'] : 0;
if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Book ID tidak valid.']);
    exit;
}

$wishlist = new Wishlist();

// Toggle action: jika sudah ada -> hapus, jika belum -> tambah
try {
    if ($wishlist->isInWishlist($userId, $bookId)) {
        $ok = $wishlist->removeFromWishlist($userId, $bookId);
        if ($ok) echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Dihapus dari wishlist.']);
        else echo json_encode(['success' => false, 'message' => 'Gagal menghapus dari wishlist.']);
    } else {
        $ok = $wishlist->addToWishlist($userId, $bookId);
        if ($ok) echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ditambahkan ke wishlist.']);
        else echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke wishlist.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
