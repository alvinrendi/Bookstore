<?php
// FILE: public/cart.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$cart = new Cart();
$cartItems = $cart->getCartItems($userId);
$cartTotal = $cart->getCartTotal($userId);
$cartCount = $cart->getCartCount($userId);
$categories = (new Category())->getAllCategories();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒 Keranjang Belanja - BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* HEADER */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-actions a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions a:hover {
            background: #f0f4ff;
            color: #667eea;
            transform: translateY(-2px);
        }

        .header-actions a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.95;
        }

        /* CART LAYOUT */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 30px;
            animation: fadeInUp 0.8s ease;
        }

        /* CART ITEMS */
        .cart-items-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: slideInLeft 0.6s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .items-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .cart-item {
            display: flex;
            gap: 25px;
            padding: 25px 0;
            border-bottom: 2px solid #f0f0f0;
            position: relative;
            transition: all 0.3s;
        }

        .cart-item:hover {
            background: #f8f9fa;
            padding-left: 15px;
            padding-right: 15px;
            margin-left: -15px;
            margin-right: -15px;
            border-radius: 15px;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .item-image {
            width: 120px;
            height: 170px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }

        .cart-item:hover .item-image {
            transform: scale(1.05) rotate(-2deg);
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(255, 71, 87, 0.4);
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .item-author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .item-price-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .item-price {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .item-subtotal {
            font-size: 14px;
            color: #666;
            padding: 5px 12px;
            background: #f0f4ff;
            border-radius: 8px;
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .qty-btn:active {
            transform: scale(0.95);
        }

        .qty-display {
            min-width: 40px;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
            color: #333;
        }

        .remove-btn {
            padding: 10px 20px;
            background: #fff5f5;
            color: #ff4757;
            border: 2px solid #ffebee;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remove-btn:hover {
            background: #ff4757;
            color: white;
            border-color: #ff4757;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
        }

        /* CART SUMMARY */
        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            height: fit-content;
            position: sticky;
            top: 100px;
            animation: slideInRight 0.6s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .summary-header {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .summary-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-of-type {
            border-bottom: 2px solid #e0e0e0;
        }

        .summary-label {
            color: #666;
            font-size: 15px;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 0 0 0;
            margin-top: 20px;
        }

        .total-label {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .total-value {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            margin-top: 25px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            border: none;
            cursor: pointer;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.5);
        }

        .checkout-btn:active {
            transform: translateY(-1px);
        }

        .continue-shopping {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .continue-shopping:hover {
            color: #764ba2;
        }

        .promo-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border-left: 4px solid #ffc107;
        }

        .promo-section h4 {
            font-size: 16px;
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .promo-section p {
            font-size: 13px;
            color: #856404;
            line-height: 1.6;
        }

        /* EMPTY CART */
        .empty-cart {
            background: white;
            border-radius: 20px;
            padding: 80px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: fadeInUp 0.6s ease;
        }

        .empty-icon {
            font-size: 120px;
            margin-bottom: 20px;
            opacity: 0.3;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .empty-cart h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
        }

        .empty-cart p {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }

        /* LOADING OVERLAY */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* NOTIFICATION */
        .notification {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 20px 30px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            animation: slideInNotif 0.3s ease;
        }

        @keyframes slideInNotif {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #ff4757 0%, #e74c3c 100%);
            color: white;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .page-header h1 {
                font-size: 36px;
            }

            .cart-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 250px;
            }

            .item-actions {
                flex-direction: column;
                width: 100%;
            }

            .quantity-control,
            .remove-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                📚 BookHub
            </a>
            <div class="header-actions">
                <a href="index.php">🏠 Beranda</a>
                <a href="cart.php" class="active">🛒 Keranjang (<?= $cartCount ?>)</a>
                <a href="orders.php">📦 Pesanan</a>
                <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🛒 Keranjang Belanja</h1>
            <p>Kelola belanjaan Anda sebelum checkout</p>
        </div>

        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <h2>Keranjang Belanja Kosong</h2>
                <p>Yuk mulai belanja dan temukan buku favorit Anda!</p>
                <a href="index.php" class="checkout-btn" style="max-width: 300px; margin: 0 auto;">
                    🏠 Mulai Belanja Sekarang
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Layout -->
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items-section">
                    <div class="section-title">
                        📚 Daftar Belanja
                        <span class="items-count"><?= count($cartItems) ?> Item</span>
                    </div>

                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $cart_id = (int) $item['cart_id'];
                        $title = htmlspecialchars($item['title']);
                        $author = htmlspecialchars($item['author']);
                        $quantity = (int) $item['quantity'];
                        $final_price = (float) $item['final_price'];
                        $subtotal = $final_price * $quantity;

                        // Path gambar yang benar
                        if (!empty($item['image']) && file_exists(__DIR__ . '/uploads/books/' . $item['image'])) {
                            $imageUrl = "uploads/books/" . htmlspecialchars($item['image']);
                        } else {
                            $imageUrl = "assets/images/default-book.jpg";
                        }
                        ?>
                        <div class="cart-item" data-cart-id="<?= $cart_id ?>">
                            <div class="item-image-wrapper">
                                <div class="item-image">
                                    <img src="<?= $imageUrl ?>" alt="<?= $title ?>" onerror="this.src='assets/images/default-book.jpg'">
                                </div>
                                <div class="item-badge"><?= $quantity ?></div>
                            </div>

                            <div class="item-details">
                                <div class="item-header">
                                    <h3><?= $title ?></h3>
                                    <div class="item-author">
                                        ✍️ <?= $author ?>
                                    </div>
                                </div>

                                <div class="item-price-section">
                                    <div class="item-price">
                                        Rp <?= number_format($final_price, 0, ',', '.') ?>
                                    </div>
                                    <div class="item-subtotal">
                                        Subtotal: Rp <?= number_format($subtotal, 0, ',', '.') ?>
                                    </div>
                                </div>

                                <div class="item-actions">
                                    <div class="quantity-control">
                                        <button class="qty-btn qty-decrease" data-id="<?= $cart_id ?>">−</button>
                                        <span class="qty-display"><?= $quantity ?></span>
                                        <button class="qty-btn qty-increase" data-id="<?= $cart_id ?>">+</button>
                                    </div>

                                    <button class="remove-btn" data-id="<?= $cart_id ?>">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-header">
                        <h3>💰 Ringkasan Belanja</h3>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">Subtotal (<?= count($cartItems) ?> item)</span>
                        <span class="summary-value" id="subtotalValue">Rp <?= number_format($cartTotal, 0, ',', '.') ?></span>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">Estimasi Ongkir</span>
                        <span class="summary-value">Mulai Rp 10.000</span>
                    </div>

                    <div class="summary-total">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="totalValue">Rp <?= number_format($cartTotal, 0, ',', '.') ?></span>
                    </div>

                    <a href="checkout.php" class="checkout-btn">
                        🛒 Lanjut ke Checkout
                    </a>

                    <a href="index.php" class="continue-shopping">
                        ← Lanjut Belanja
                    </a>

                    <div class="promo-section">
                        <h4>🎁 Info Promo</h4>
                        <p>Gratis ongkir untuk pembelian minimal Rp 100.000! Gunakan kode: <strong>FREESHIP</strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Show notification
        function showNotification(message, type = 'success') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.className = `notification ${type}`;
            notif.style.display = 'block';

            setTimeout(() => {
                notif.style.animation = 'slideInNotif 0.3s ease reverse';
                setTimeout(() => {
                    notif.style.display = 'none';
                }, 300);
            }, 3000);
        }

        // Show loading
        function showLoading(show = true) {
            document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
        }

        // Update quantity
        document.querySelectorAll('.qty-increase, .qty-decrease').forEach(btn => {
            btn.addEventListener('click', function () {
                const cartId = this.dataset.id;
                const action = this.classList.contains('qty-increase') ? 'increase' : 'decrease';
                const qtyDisplay = this.parentElement.querySelector('.qty-display');
                const currentQty = parseInt(qtyDisplay.textContent);

                if (action === 'decrease' && currentQty <= 1) {
                    showNotification('Minimal quantity adalah 1', 'error');
                    return;
                }

                showLoading();

                fetch('api/update-cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId, action: action })
                })
                    .then(r => r.json())
                    .then(res => {
                        showLoading(false);
                        if (res.success) {
                            showNotification('✓ Keranjang berhasil diperbarui');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showNotification('✗ ' + res.message, 'error');
                        }
                    })
                    .catch(err => {
                        showLoading(false);
                        showNotification('✗ Terjadi kesalahan', 'error');
                        console.error(err);
                    });
            });
        });

        // Remove item
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const cartId = this.dataset.id;
                const cartItem = this.closest('.cart-item');

                if (!confirm('🗑️ Hapus buku ini dari keranjang?')) return;

                showLoading();

                fetch('api/remove-cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId })
                })
                    .then(r => r.json())
                    .then(res => {
                        showLoading(false);
                        if (res.success) {
                            cartItem.style.animation = 'fadeOut 0.3s ease';
                            setTimeout(() => {
                                showNotification('✓ Item berhasil dihapus');
                                setTimeout(() => location.reload(), 800);
                            }, 300);
                        } else {
                            showNotification('✗ ' + res.message, 'error');
                        }
                    })
                    .catch(err => {
                        showLoading(false);
                        showNotification('✗ Terjadi kesalahan', 'error');
                        console.error(err);
                    });
            });
        });

        // Animation for fade out
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateX(-50px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>