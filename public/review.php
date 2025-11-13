<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Review.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';
require_once '../classes/Wishlist.php';

// ====== CEK KONEKSI DATABASE ======
try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    die('❌ Koneksi database gagal: ' . $e->getMessage());
}

// ====== INISIALISASI KELAS ======
$book = new Book();
$review = new Review();
$cart = new Cart();
$wishlist = new Wishlist();
$category = new Category();

$categories = $category->getAllCategories();

$id = $_GET['id'] ?? 0;
$bookData = $book->getBookById($id);
$reviews = $review->getBookReviews($id);

if (!$bookData) {
    header('Location: index.php');
    exit;
}

// ====== HITUNG HARGA SETELAH DISKON ======
$finalPrice = $bookData['price'] * (100 - $bookData['discount_percent']) / 100;
$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;

// ====== CEK APAKAH ADA DI WISHLIST ======
$isInWishlist = false;
if (isset($_SESSION['user_id'])) {
    $isInWishlist = $wishlist->isInWishlist($_SESSION['user_id'], $id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($bookData['title']) ?> - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6fa; margin: 0; }
        .container { max-width: 1100px; margin: 30px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .price { color: #764ba2; font-weight: bold; font-size: 28px; margin-bottom: 20px; }
        .review-form textarea { width: 100%; border: 1px solid #ccc; border-radius: 8px; padding: 12px; font-size: 15px; }
        .btn { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; }
        .btn:hover { opacity: 0.9; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 10px; color: #fff; }
        .alert-success { background: #28a745; }
        .alert-error { background: #e74c3c; }
        .review-item { border-bottom: 1px solid #eee; padding: 12px 0; }
    </style>
</head>
<body>

<div class="container">
    <h1><?= htmlspecialchars($bookData['title']) ?></h1>
    <div>✍️ <strong><?= htmlspecialchars($bookData['author']) ?></strong></div>
    <div class="price">Rp <?= number_format($finalPrice, 0, ',', '.') ?></div>
    <p><?= nl2br(htmlspecialchars($bookData['description'])) ?></p>

    <hr style="margin: 30px 0;">

    <h2>💬 Ulasan (<?= count($reviews) ?>)</h2>

    <?php if (isset($_SESSION['user_id'])): ?>
    <form id="reviewForm" class="review-form">
        <label>Rating:</label><br>
        <select name="rating" required>
            <option value="">-- Pilih Rating --</option>
            <option value="5">⭐⭐⭐⭐⭐ (5)</option>
            <option value="4">⭐⭐⭐⭐ (4)</option>
            <option value="3">⭐⭐⭐ (3)</option>
            <option value="2">⭐⭐ (2)</option>
            <option value="1">⭐ (1)</option>
        </select><br><br>

        <textarea name="comment" placeholder="Tulis pengalamanmu..." required></textarea><br>
        <button type="submit" class="btn">Kirim Review</button>
    </form>
    <?php else: ?>
        <div class="alert alert-error">🔒 Silakan <a href="login.php" style="color:white; text-decoration:underline;">login</a> untuk menulis review.</div>
    <?php endif; ?>

    <div id="reviewList">
        <?php if (empty($reviews)): ?>
            <p style="color:#666;">Belum ada ulasan untuk buku ini.</p>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-item">
                    <strong><?= htmlspecialchars($r['full_name']) ?></strong> — <?= $r['rating'] ?>⭐<br>
                    <small><?= date('d M Y', strtotime($r['created_at'])) ?></small>
                    <p><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const rating = this.rating.value;
    const comment = this.comment.value.trim();

    fetch('api/add-review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id: <?= $id ?>,
            rating,
            comment
        })
    })
    .then(r => r.json())
    .then(data => {
        const msg = document.createElement('div');
        msg.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');
        msg.textContent = data.message || (data.success ? 'Review berhasil dikirim!' : 'Gagal mengirim review.');
        document.querySelector('.container').prepend(msg);
        if (data.success) setTimeout(() => location.reload(), 1200);
    })
    .catch(() => alert('Terjadi kesalahan koneksi.'));
});
</script>
</body>
</html>
