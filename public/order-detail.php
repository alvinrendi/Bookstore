<?php
// public/order-detail.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Cart.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order = new Order();
$cart = new Cart();

$orderId = (int)$_GET['id'];
$orderData = $order->getOrderById($orderId, $_SESSION['user_id']);
$orderItems = $order->getOrderItems($orderId);
$cartCount = $cart->getCartCount($_SESSION['user_id']);

if (!$orderData) {
    header('Location: orders.php');
    exit;
}

// Proses cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $result = $order->cancelOrder($orderId, $_SESSION['user_id']);
    if ($result['success']) {
        header('Location: order-detail.php?id=' . $orderId . '&cancelled=1');
        exit;
    } else {
        $error = $result['message'];
    }
}

$statusConfig = [
    'pending' => ['color' => '#ff9800', 'icon' => '⏳', 'text' => 'Menunggu Pembayaran', 'bg' => 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)'],
    'processing' => ['color' => '#2196f3', 'icon' => '⚙️', 'text' => 'Diproses', 'bg' => 'linear-gradient(135deg, #2196f3 0%, #1976d2 100%)'],
    'shipped' => ['color' => '#9c27b0', 'icon' => '🚚', 'text' => 'Dikirim', 'bg' => 'linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%)'],
    'delivered' => ['color' => '#4caf50', 'icon' => '📦', 'text' => 'Terkirim', 'bg' => 'linear-gradient(135deg, #4caf50 0%, #388e3c 100%)'],
    'completed' => ['color' => '#00c853', 'icon' => '✅', 'text' => 'Selesai', 'bg' => 'linear-gradient(135deg, #00c853 0%, #00b248 100%)'],
    'cancelled' => ['color' => '#f44336', 'icon' => '❌', 'text' => 'Dibatalkan', 'bg' => 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)']
];

$currentStatus = $statusConfig[$orderData['status']] ?? ['color' => '#6c757d', 'icon' => '❓', 'text' => 'Unknown', 'bg' => 'linear-gradient(135deg, #6c757d 0%, #5a6268 100%)'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= htmlspecialchars($orderData['order_number']) ?> - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(138, 43, 226, 0.2), transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(75, 0, 130, 0.15), transparent 40%);
            pointer-events: none;
            z-index: 0;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: -0.5px;
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
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .header-actions a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .order-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .order-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .order-title {
            font-size: 32px;
            color: #333;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .order-number-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .status-badge {
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: <?= $currentStatus['bg'] ?>;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .order-content {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 30px;
        }
        
        .order-items-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .order-item {
            display: flex;
            gap: 20px;
            padding: 25px;
            border-bottom: 2px solid #f5f5f5;
            transition: all 0.3s ease;
            border-radius: 15px;
            margin-bottom: 15px;
        }
        
        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .order-item:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .item-image {
            width: 90px;
            height: 135px;
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .order-item:hover .item-image {
            transform: scale(1.05) rotate(-2deg);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-weight: 700;
            font-size: 17px;
            color: #333;
            margin-bottom: 6px;
            line-height: 1.4;
        }
        
        .item-author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .item-price {
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 16px;
            margin-top: 8px;
        }
        
        .item-qty {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            background: rgba(102, 126, 234, 0.1);
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 8px;
        }
        
        .order-info-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 2px solid #f5f5f5;
            align-items: center;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 15px;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 700;
            color: #333;
            text-align: right;
            font-size: 15px;
        }
        
        .total-row {
            margin-top: 15px;
            padding-top: 20px;
            border-top: 3px solid #e0e0e0 !important;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            margin: 15px -10px -10px;
            padding: 20px 10px 10px;
            border-radius: 12px;
        }
        
        .total-label {
            font-size: 18px;
            font-weight: 800;
            color: #333;
        }
        
        .total-value {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .address-text {
            color: #666;
            line-height: 1.8;
            font-size: 14px;
            font-weight: 400;
        }
        
        .btn-cancel {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(244, 67, 54, 0.3);
            letter-spacing: 0.5px;
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(244, 67, 54, 0.4);
        }
        
        .btn-cancel:active {
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(209, 231, 221, 0.95);
            color: #0f5132;
            border-left: 5px solid #0f5132;
        }
        
        .alert-error {
            background: rgba(248, 215, 218, 0.95);
            color: #842029;
            border-left: 5px solid #842029;
        }
        
        .payment-method-highlight {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
            color: #667eea;
            border: 2px dashed #667eea;
        }
        
        .shipping-info-box {
            padding: 15px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        
        .shipping-info-box > div {
            margin-bottom: 10px;
        }
        
        .shipping-info-box > div:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 1024px) {
            .order-content {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-badge {
                align-self: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .detail-container {
                padding: 20px 15px;
            }
            
            .order-header {
                padding: 25px 20px;
            }
            
            .order-title {
                font-size: 24px;
            }
            
            .order-items-section,
            .info-card {
                padding: 20px;
            }
            
            .order-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .item-details {
                width: 100%;
            }
            
            .header-actions {
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .header-actions a {
                font-size: 12px;
                padding: 8px 15px;
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
                    <a href="cart.php">🛒 Keranjang (<?= $cartCount ?>)</a>
                    <a href="orders.php">📦 Pesanan</a>
                    <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
                </div>
            </div>
        </div>
    </header>

    <div class="detail-container">
        <a href="orders.php" class="back-btn">← Kembali ke Daftar Pesanan</a>

        <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-success">✓ Pesanan berhasil dibatalkan</div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="order-header">
            <div>
                <h1 class="order-title">Detail Pesanan</h1>
                <p class="order-number-badge">#<?= htmlspecialchars($orderData['order_number']) ?></p>
                <p style="color: #666; font-size: 14px; margin-top: 8px; font-weight: 500;">
                    📅 <?= date('d F Y, H:i', strtotime($orderData['created_at'])) ?>
                </p>
            </div>
            <span class="status-badge">
                <?= $currentStatus['icon'] ?> <?= $currentStatus['text'] ?>
            </span>
        </div>

        <div class="order-content">
            <!-- Order Items -->
            <div class="order-items-section">
                <h2 class="section-title">📚 Buku yang Dipesan</h2>
                <?php foreach ($orderItems as $item): 
                    $imagePath = !empty($item['image']) && file_exists(__DIR__ . '/uploads/books/' . $item['image']) 
                        ? 'uploads/books/' . $item['image'] 
                        : 'assets/images/default-book.jpg';
                    $finalPrice = $item['price'] * (100 - $item['discount_percent']) / 100;
                ?>
                    <div class="order-item">
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-title"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="item-author">✍️ <?= htmlspecialchars($item['author']) ?></div>
                            <div class="item-qty">📦 Jumlah: <?= $item['quantity'] ?> buku</div>
                            <?php if ($item['discount_percent'] > 0): ?>
                                <div style="font-size: 13px; color: #999; text-decoration: line-through; margin-top: 5px;">
                                    Rp <?= number_format($item['price'], 0, ',', '.') ?>
                                </div>
                            <?php endif; ?>
                            <div class="item-price">
                                Rp <?= number_format($finalPrice, 0, ',', '.') ?> × <?= $item['quantity'] ?> = 
                                Rp <?= number_format($finalPrice * $item['quantity'], 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Info -->
            <div class="order-info-section">
                <!-- Ringkasan Pembayaran -->
                <div class="info-card">
                    <h3 class="section-title">💰 Ringkasan Pembayaran</h3>
                    <div class="info-row">
                        <span class="info-label">Subtotal</span>
                        <span class="info-value">Rp <?= number_format($orderData['total_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Biaya Pengiriman</span>
                        <span class="info-value">Rp <?= number_format($orderData['shipping_cost'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-row total-row">
                        <span class="total-label">Total Pembayaran</span>
                        <span class="total-value">Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Info Pembayaran -->
                <div class="info-card">
                    <h3 class="section-title">💳 Metode Pembayaran</h3>
                    <div class="payment-method-highlight">
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $orderData['payment_method']))) ?>
                    </div>
                </div>

                <!-- Info Pengiriman -->
                <div class="info-card">
                    <h3 class="section-title">📍 Alamat Pengiriman</h3>
                    <div class="shipping-info-box">
                        <div style="font-weight: 700; font-size: 16px; color: #333; margin-bottom: 10px;">
                            👤 <?= htmlspecialchars($orderData['full_name']) ?>
                        </div>
                        <div style="font-size: 14px; color: #666; margin-bottom: 10px; font-weight: 500;">
                            📱 <?= htmlspecialchars($orderData['phone']) ?>
                        </div>
                        <div class="address-text">
                            📮 <?= nl2br(htmlspecialchars($orderData['shipping_address'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Tombol Cancel (hanya untuk pending) -->
                <?php if ($orderData['status'] === 'pending'): ?>
                    <div class="info-card">
                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                            <button type="submit" name="cancel_order" class="btn-cancel">
                                ❌ Batalkan Pesanan
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>