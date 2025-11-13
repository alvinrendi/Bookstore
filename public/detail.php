<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Review.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';
require_once '../classes/Wishlist.php';

$categories = (new Category())->getAllCategories();
$wishlist = new Wishlist();

$id = $_GET['id'] ?? 0;
$book = new Book();
$review = new Review();
$cart = new Cart();

$bookData = $book->getBookById($id);
$reviews = $review->getBookReviews($id);

if (!$bookData) {
    header('Location: index.php');
    exit;
}

$finalPrice = $bookData['price'] * (100 - $bookData['discount_percent']) / 100;
$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;

// Check if book is in wishlist
$isInWishlist = false;
if (isset($_SESSION['user_id'])) {
    $isInWishlist = $wishlist->isInWishlist($_SESSION['user_id'], $id);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bookData['title']) ?> - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HEADER */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .header-actions a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px !important;
            border-radius: 8px;
        }

        /* DETAIL CONTAINER */
        .detail-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
            margin: 30px 0;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .detail-image {
            width: 100%;
            height: 450px;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .discount-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.4);
        }

        .detail-info h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 700;
        }

        .detail-author {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rating-overview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            border-radius: 10px;
        }

        .rating-number {
            font-size: 32px;
            font-weight: 700;
            color: #f39c12;
        }

        .rating-stars {
            font-size: 24px;
            color: #f39c12;
        }

        .rating-count {
            color: #666;
            font-size: 14px;
        }

        .detail-price {
            margin-bottom: 25px;
        }

        .price-original {
            font-size: 18px;
            text-decoration: line-through;
            color: #999;
            margin-bottom: 5px;
        }

        .price-final {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .detail-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
        }

        .spec-item {
            font-size: 14px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .spec-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            font-weight: 700;
            color: #333;
            font-size: 15px;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
        }

        .stock-badge.available {
            background: linear-gradient(135deg, #d4edda 0%, #a8d8b5 100%);
            color: #155724;
        }

        .stock-badge.out {
            background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
            color: #721c24;
        }

        .detail-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-wishlist {
            background: white;
            color: #ff4757;
            border: 2px solid #ff4757;
            flex: 0 0 auto;
            padding: 15px 20px;
        }

        .btn-wishlist:hover {
            background: #ff4757;
            color: white;
            transform: translateY(-2px);
        }

        .btn-wishlist.active {
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
            border-color: transparent;
        }

        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        .description-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }

        .description-section h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #333;
        }

        .description-section p {
            line-height: 1.8;
            color: #555;
        }

        /* REVIEWS SECTION */
        .reviews-section {
            margin: 40px 0;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .reviews-section h2 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .review-form h4 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        .rating-input {
            margin: 20px 0;
        }

        .rating-input label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: #555;
        }

        .star-rating {
            display: flex;
            gap: 10px;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 32px;
            color: #ddd;
            transition: all 0.2s ease;
            margin: 0;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f39c12;
            transform: scale(1.1);
        }

        .review-form textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        .review-form textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .review-item {
            padding: 25px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .reviewer-name {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            color: #f39c12;
        }

        .review-date {
            font-size: 13px;
            color: #999;
            margin-bottom: 12px;
        }

        .review-comment {
            color: #555;
            line-height: 1.7;
            font-size: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state::before {
            content: '💬';
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
        }

        /* ALERTS */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
            color: #842029;
            border-left: 4px solid #842029;
        }

        .alert::before {
            font-size: 20px;
        }

        .alert-success::before {
            content: '✓';
        }

        .alert-error::before {
            content: '✗';
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .detail-container {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .detail-image {
                height: 350px;
            }

            .detail-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .detail-specs {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">📚 BookHub</a>
                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php" class="cart-btn">🛒 Keranjang (<?= $cartCount ?>)</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div id="alertContainer"></div>

        <div class="detail-container">
            <div class="detail-image">
                <?php
                $imagePath = "uploads/books/" . $bookData['image'];
                if (empty($bookData['image']) || !file_exists(__DIR__ . "/" . $imagePath)) {
                    $imagePath = "assets/images/default-book.jpg";
                }
                ?>
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($bookData['title']) ?>">
                <?php if ($bookData['discount_percent'] > 0): ?>
                    <div class="discount-badge">-<?= $bookData['discount_percent'] ?>%</div>
                <?php endif; ?>
            </div>

            <div class="detail-info">
                <h1><?= htmlspecialchars($bookData['title']) ?></h1>
                <div class="detail-author">
                    <span>✍️</span>
                    Oleh: <?= htmlspecialchars($bookData['author']) ?>
                </div>

                <div class="rating-overview">
                    <div class="rating-number"><?= number_format($bookData['rating'], 1) ?></div>
                    <div>
                        <div class="rating-stars">
                            <?php
                            $rating = round($bookData['rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <div class="rating-count"><?= count($reviews) ?> ulasan</div>
                    </div>
                </div>

                <div class="detail-price">
                    <?php if ($bookData['discount_percent'] > 0): ?>
                        <div class="price-original">Rp <?= number_format($bookData['price'], 0, ',', '.') ?></div>
                    <?php endif; ?>
                    <div class="price-final">Rp <?= number_format($finalPrice, 0, ',', '.') ?></div>
                </div>

                <div class="detail-specs">
                    <div class="spec-item">
                        <span class="spec-label">Penerbit</span>
                        <span class="spec-value"><?= htmlspecialchars($bookData['publisher'] ?? '-') ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Halaman</span>
                        <span class="spec-value"><?= $bookData['pages'] ?? '-' ?> halaman</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Bahasa</span>
                        <span class="spec-value"><?= htmlspecialchars($bookData['language'] ?? '-') ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Tahun Terbit</span>
                        <span class="spec-value"><?= $bookData['publication_year'] ?? '-' ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">ISBN</span>
                        <span class="spec-value"><?= htmlspecialchars($bookData['isbn'] ?? '-') ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Stok</span>
                        <span class="spec-value">
                            <?php if ($bookData['stock'] > 0): ?>
                                <span class="stock-badge available">
                                    <span>✓</span> <?= $bookData['stock'] ?> tersedia
                                </span>
                            <?php else: ?>
                                <span class="stock-badge out">
                                    <span>✗</span> Habis
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="detail-actions">
                    <?php if (isset($_SESSION['user_id']) && $bookData['stock'] > 0): ?>
                        <button class="btn btn-primary" onclick="addToCart(<?= $bookData['id'] ?>)">
                            <span>🛒</span> Tambah ke Keranjang
                        </button>
                    <?php elseif ($bookData['stock'] <= 0): ?>
                        <button class="btn btn-disabled" disabled>
                            <span>❌</span> Stok Habis
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <span>🔐</span> Login untuk Beli
                        </a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-wishlist <?= $isInWishlist ? 'active' : '' ?>" onclick="toggleWishlist(<?= $bookData['id'] ?>)" id="wishlistBtn">
                            <span><?= $isInWishlist ? '❤️' : '🤍' ?></span>
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-wishlist">
                            <span>🤍</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="description-section">
                    <h3>📝 Deskripsi</h3>
                    <p><?= nl2br(htmlspecialchars($bookData['description'])) ?></p>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h2>
                <span>💬</span>
                Review & Rating (<?= count($reviews) ?>)
            </h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="review-form">
                    <h4>✍️ Tulis Review Anda</h4>
                    <form id="reviewForm">
                        <div class="rating-input">
                            <label>Berikan Rating:</label>
                            <div class="star-rating">
                                <input type="radio" name="rating" value="5" id="star5" required>
                                <label for="star5">★</label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4">★</label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3">★</label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2">★</label>
                                <input type="radio" name="rating" value="1" id="star1">
                                <label for="star1">★</label>
                            </div>
                        </div>
                        <textarea name="comment" placeholder="Bagikan pengalaman Anda tentang buku ini..." rows="4" required></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                            <span>📨</span> Kirim Review
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); color: #0c5460; border-left-color: #0c5460;">
                    <span style="font-size: 20px;">ℹ️</span>
                    <a href="login.php" style="color: #0c5460; text-decoration: underline; font-weight: 600;">Login</a> untuk menulis review
                </div>
            <?php endif; ?>

            <div>
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <p>Belum ada review untuk buku ini. Jadilah yang pertama!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?= strtoupper(substr($r['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?= htmlspecialchars($r['full_name']) ?></div>
                                        <div class="review-date">
                                            📅 <?= date('d M Y', strtotime($r['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <span>⭐</span>
                                    <?= $r['rating'] ?>.0
                                </div>
                            </div>
                            <p class="review-comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
// ========== 🔹 TAMBAH KE KERANJANG ==========
function addToCart(bookId) {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid white;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;"></span> Menambahkan...';

    fetch('api/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id: bookId, quantity: 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'Berhasil menambahkan ke keranjang', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Gagal menambahkan ke keranjang', 'error');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('Terjadi kesalahan koneksi.', 'error');
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

// ========== 🔹 TOGGLE WISHLIST ==========
function toggleWishlist(bookId) {
    const btn = document.getElementById('wishlistBtn');
    btn.disabled = true;

    fetch('api/add-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id: bookId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                btn.classList.add('active');
                btn.querySelector('span').textContent = '❤️';
                showAlert('Ditambahkan ke wishlist!', 'success');
            } else {
                btn.classList.remove('active');
                btn.querySelector('span').textContent = '🤍';
                showAlert('Dihapus dari wishlist!', 'success');
            }
        } else {
            showAlert(data.message || 'Terjadi kesalahan.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('Gagal mengubah wishlist.', 'error');
    })
    .finally(() => btn.disabled = false);
}

// ========== 🔹 FORM REVIEW ==========
document.getElementById('reviewForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const rating = document.querySelector('input[name="rating"]:checked');
    const comment = document.querySelector('textarea[name="comment"]');
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;

    if (!rating) return showAlert('Silakan pilih rating terlebih dahulu!', 'error');
    if (!comment.value.trim()) return showAlert('Silakan tulis komentar!', 'error');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid white;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;"></span> Mengirim...';

    fetch('api/add-review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id: <?= (int)$id ?>,
            rating: rating.value,
            comment: comment.value.trim()
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Review berhasil dikirim!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Gagal mengirim review.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('Terjadi kesalahan koneksi.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
    });
});

// ========== 🔹 ALERT NOTIFIKASI ==========
function showAlert(message, type) {
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.background = type === 'success' ? '#28a745' : '#e74c3c';
    alert.style.color = '#fff';
    alert.style.padding = '10px 15px';
    alert.style.marginBottom = '10px';
    alert.style.borderRadius = '6px';
    alert.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
    alert.style.transition = 'all 0.3s ease';
    alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// ========== 🔹 ANIMASI SPINNER ==========
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);

// ========== 🔹 EFEK BINTANG RATING ==========
const stars = document.querySelectorAll('.star-rating label');
stars.forEach(label => {
    label.addEventListener('mouseenter', function () {
        const value = this.previousElementSibling.value;
        stars.forEach((l, i) => {
            if (5 - i <= value) l.style.color = '#f39c12';
            else l.style.color = '#ddd';
        });
    });
});

document.querySelector('.star-rating')?.addEventListener('mouseleave', function () {
    const checked = document.querySelector('.star-rating input:checked');
    stars.forEach((l, i) => {
        if (checked && 5 - i <= checked.value) l.style.color = '#f39c12';
        else l.style.color = '#ddd';
    });
});
</script>
