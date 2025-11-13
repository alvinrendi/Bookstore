<?php
// public/api/add-review.php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Review.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Payload JSON tidak ditemukan.']);
    exit;
}

$bookId = isset($data['book_id']) ? (int)$data['book_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';

if ($bookId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    echo json_encode(['success' => false, 'message' => 'Data review tidak lengkap atau tidak valid.']);
    exit;
}

$review = new Review();
$userId = (int)$_SESSION['user_id'];

// Optional: jika class Review punya method checking duplicate, gunakan.
// Kita coba panggil addReview langsung dan handle false
try {
    $ok = $review->addReview($bookId, $userId, $rating, $comment);
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Review berhasil dikirim.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan review. Mungkin Anda sudah mereview buku ini.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
