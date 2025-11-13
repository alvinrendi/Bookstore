<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Wishlist.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';

// 🔒 Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$wishlist = new Wishlist();
$cart = new Cart();
$categoryObj = new Category();

$wishlistItems = $wishlist->getWishlist($user_id);
$cartCount = $cart->getCartCount($user_id);
$categories = $categoryObj->getAllCategories();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Saya - BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* HEADER SECTION */
        .wishlist-header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: 30px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wishlist-header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .wishlist-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 25px;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .badge-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* EMPTY STATE */
        .empty-wishlist {
            background: white;
            border-radius: 20px;
            padding: 80px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-icon {
            font-size: 100px;
            margin-bottom: 25px;
            opacity: 0.7;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .empty-wishlist h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .empty-wishlist p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-explore {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-explore:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }

        /* WISHLIST GRID */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .book-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
            animation: fadeIn 0.5s ease backwards;
        }

        .book-card:nth-child(1) { animation-delay: 0.1s; }
        .book-card:nth-child(2) { animation-delay: 0.2s; }
        .book-card:nth-child(3) { animation-delay: 0.3s; }
        .book-card:nth-child(4) { animation-delay: 0.4s; }
        .book-card:nth-child(5) { animation-delay: 0.5s; }
        .book-card:nth-child(6) { animation-delay: 0.6s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }

        .book-image {
            position: relative;
            height: 320px;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-image::before {
            content: '📚';
            position: absolute;
            font-size: 60px;
            opacity: 0.2;
            z-index: 0;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
            display: block;
            position: relative;
            z-index: 1;
            background: white;
        }

        .book-card:hover .book-image img {
            transform: scale(1.1);
        }

        .discount-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.4);
            z-index: 10;
        }

        .wishlist-badge-card {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .book-info {
            padding: 20px;
        }

        .book-info h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #333;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 50px;
        }

        .author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .category-tag {
            background: #e9ecef;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            display: inline-block;
            margin-bottom: 12px;
        }

        .price {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .original {
            text-decoration: line-through;
            color: #aaa;
            font-size: 14px;
        }

        .final {
            color: #2ecc71;
            font-weight: 700;
            font-size: 20px;
        }

        .book-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 12px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-detail {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-cart {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-remove {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
        }

        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* STOCK INFO */
        .stock-info {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            margin-bottom: 12px;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .stock-available {
            background: #d1e7dd;
            color: #0f5132;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background: #f8d7da;
            color: #842029;
        }

        /* NOTIFICATION */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .notification.success {
            border-left: 4px solid #2ecc71;
        }

        .notification.error {
            border-left: 4px solid #ff4757;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-icon {
            font-size: 24px;
        }

        .notification-text {
            font-weight: 600;
            color: #333;
        }

        /* LOADING STATE */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .wishlist-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .wishlist-header h1 {
                font-size: 28px;
            }

            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .book-image {
                height: 250px;
            }

            .empty-wishlist {
                padding: 60px 30px;
            }

            .empty-icon {
                font-size: 80px;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: scale(0.8);
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="wishlist-header">
            <h1>
                <span>❤️</span>
                Wishlist Saya
            </h1>
            <div class="wishlist-badge">
                <div class="badge-icon">📚</div>
                <span><?= count($wishlistItems) ?> Buku</span>
            </div>
        </div>

        <?php if (empty($wishlistItems)): ?>
            <div class="empty-wishlist">
                <div class="empty-icon">💔</div>
                <h2>Wishlist Anda Masih Kosong</h2>
                <p>Tambahkan buku favorit Anda ke wishlist untuk menyimpannya dan membeli nanti.<br>Jelajahi koleksi kami dan temukan buku yang sempurna untuk Anda!</p>
                <a href="index.php" class="btn-explore">
                    <span>🔍</span>
                    Jelajahi Buku Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlistItems as $b): 
                    $finalPrice = $b['price'] * (100 - ($b['discount_percent'] ?? 0)) / 100;
                    $stock = $b['stock'] ?? 0;
                    
                    // 🔧 FIX: Perbaikan path gambar dengan pengecekan bertingkat
                    $imagePath = 'assets/images/default.jpg';
                    if (!empty($b['image'])) {
                        $possiblePaths = [
                            "uploads/books/" . basename($b['image']),
                            "../uploads/books/" . basename($b['image']),
                            $b['image'],
                            "uploads/" . basename($b['image']),
                            "../uploads/" . basename($b['image'])
                        ];
                        
                        foreach ($possiblePaths as $path) {
                            if (file_exists(__DIR__ . "/" . $path)) {
                                $imagePath = $path;
                                break;
                            }
                        }
                    }
                ?>
                    <div class="book-card" id="book-<?= $b['id'] ?>">
                        <div class="book-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" 
                                 alt="<?= htmlspecialchars($b['title']) ?>"
                                 onerror="handleImageError(this)"
                                 loading="lazy">
                            
                            <?php if (!empty($b['discount_percent']) && $b['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?= $b['discount_percent'] ?>%</span>
                            <?php endif; ?>
                            
                            <span class="wishlist-badge-card">
                                <span>❤️</span> Favorit
                            </span>
                        </div>
                        
                        <div class="book-info">
                            <h3><?= htmlspecialchars($b['title']) ?></h3>
                            
                            <p class="author">
                                <span>✍️</span>
                                <?= htmlspecialchars($b['author']) ?>
                            </p>

                            <?php if (!empty($b['category_name'])): ?>
                                <span class="category-tag">
                                    <?= htmlspecialchars($b['category_name']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($stock > 10): ?>
                                <div class="stock-info stock-available">
                                    <span>✓</span> Tersedia (<?= $stock ?> unit)
                                </div>
                            <?php elseif ($stock > 0): ?>
                                <div class="stock-info stock-low">
                                    <span>⚠</span> Stok Terbatas (<?= $stock ?> unit)
                                </div>
                            <?php else: ?>
                                <div class="stock-info stock-out">
                                    <span>✗</span> Stok Habis
                                </div>
                            <?php endif; ?>
                            
                            <div class="price">
                                <?php if (!empty($b['discount_percent']) && $b['discount_percent'] > 0): ?>
                                    <span class="original">Rp <?= number_format($b['price'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                                <span class="final">Rp <?= number_format($finalPrice, 0, ',', '.') ?></span>
                            </div>
                            
                            <div class="book-actions">
                                <button class="btn btn-detail" onclick="window.location.href='detail.php?id=<?= $b['id'] ?>'">
                                    <span>👁️</span> Detail
                                </button>
                                <button class="btn btn-cart" 
                                        onclick="addToCart(<?= $b['id'] ?>)"
                                        <?= $stock <= 0 ? 'disabled' : '' ?>>
                                    <span>🛒</span> Keranjang
                                </button>
                                <button class="btn btn-remove" onclick="removeWishlist(<?= $b['id'] ?>)">
                                    <span>🗑️</span> Hapus dari Wishlist
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification">
        <div class="notification-content">
            <span class="notification-icon" id="notifIcon"></span>
            <span class="notification-text" id="notifText"></span>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
    // Handle image loading errors dengan fallback bertingkat
    function handleImageError(img) {
        const fallbacks = [
            'uploads/books/default.jpg',
            '../uploads/books/default.jpg',
            'assets/images/default.jpg',
            '../assets/images/default.jpg',
            'assets/images/no-image.jpg',
            'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="280" height="320" viewBox="0 0 280 320"%3E%3Crect fill="%23f5f7fa" width="280" height="320"/%3E%3Ctext x="50%25" y="45%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="60" fill="%23ccc"%3E📚%3C/text%3E%3Ctext x="50%25" y="60%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="14" fill="%23999"%3EGambar Tidak Tersedia%3C/text%3E%3C/svg%3E'
        ];
        
        if (!img.dataset.fallbackIndex) {
            img.dataset.fallbackIndex = '0';
        }
        
        let currentIndex = parseInt(img.dataset.fallbackIndex);
        
        if (currentIndex < fallbacks.length) {
            img.dataset.fallbackIndex = (currentIndex + 1).toString();
            img.src = fallbacks[currentIndex];
        } else {
            img.style.display = 'none';
        }
    }

    // Show notification
    function showNotification(message, type = 'success') {
        const notif = document.getElementById('notification');
        const icon = document.getElementById('notifIcon');
        const text = document.getElementById('notifText');
        
        notif.className = 'notification ' + type;
        icon.textContent = type === 'success' ? '✓' : '✗';
        text.textContent = message;
        
        notif.style.display = 'block';
        
        setTimeout(() => {
            notif.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                notif.style.display = 'none';
                notif.style.animation = 'slideInRight 0.3s ease';
            }, 300);
        }, 3000);
    }

    // Show loading
    function showLoading(show) {
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }

    // 🛒 Tambahkan ke Keranjang - FIXED VERSION
    function addToCart(bookId) {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        
        showLoading(true);
        btn.disabled = true;
        
        fetch('api/add-to-cart.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                book_id: bookId, 
                quantity: 1 
            }),
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showNotification(data.message || 'Buku berhasil ditambahkan ke keranjang!', 'success');
                
                // Update cart count di header
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge && data.cart_count) {
                    cartBadge.textContent = data.cart_count;
                } else if (cartBadge) {
                    const currentCount = parseInt(cartBadge.textContent) || 0;
                    cartBadge.textContent = currentCount + 1;
                }
                
                // Update button
                btn.innerHTML = '<span>✓</span> Ditambahkan';
                btn.style.background = 'linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%)';
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            } else {
                showNotification(data.message || 'Gagal menambahkan ke keranjang', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            showLoading(false);
            console.error('Error:', err);
            showNotification('Terjadi kesalahan koneksi. Pastikan Anda sudah login.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }

    // ❌ Hapus dari Wishlist
    function removeWishlist(bookId) {
        if (!confirm('Apakah Anda yakin ingin menghapus buku ini dari wishlist?')) return;

        showLoading(true);

        fetch('api/toggle-wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: bookId })
        })
        .then(r => r.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showNotification('Buku berhasil dihapus dari wishlist.', 'success');
                
                const card = document.getElementById('book-' + bookId);
                if (card) {
                    card.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => {
                        card.remove();
                        
                        const remainingCards = document.querySelectorAll('.book-card');
                        if (remainingCards.length === 0) {
                            setTimeout(() => location.reload(), 500);
                        } else {
                            const badge = document.querySelector('.wishlist-badge span:last-child');
                            if (badge) {
                                badge.textContent = remainingCards.length + ' Buku';
                            }
                        }
                    }, 300);
                }
            } else {
                showNotification(data.message || 'Gagal menghapus dari wishlist.', 'error');
            }
        })
        .catch(err => {
            showLoading(false);
            showNotification('Terjadi kesalahan koneksi.', 'error');
            console.error(err);
        });
    }
    </script>
</body>
</html>