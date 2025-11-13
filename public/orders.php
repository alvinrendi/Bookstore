<?php
// public/orders.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order = new Order();
$cart = new Cart();

$userOrders = $order->getUserOrders($_SESSION['user_id']);
$cartCount = $cart->getCartCount($_SESSION['user_id']);

// Hitung statistik
$totalOrders = count($userOrders);
$pendingOrders = count(array_filter($userOrders, fn($o) => $o['status'] === 'pending'));
$completedOrders = count(array_filter($userOrders, fn($o) => in_array($o['status'], ['completed', 'delivered'])));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Pesanan Saya - BookHub</title>
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
            position: relative;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* HEADER */
        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
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
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -1px;
            transition: all 0.3s;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-actions a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .header-actions a:hover {
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            color: #667eea;
            transform: translateY(-2px);
        }

        .header-actions a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
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
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            letter-spacing: -1px;
        }

        .page-header p {
            font-size: 19px;
            opacity: 0.95;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
            animation: fadeInUp 0.8s ease;
        }

        .stat-card {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            animation: slideInUp 0.6s ease backwards;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            position: relative;
            z-index: 1;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #ffd93d 0%, #ffb800 100%);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .stat-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .stat-content h3 {
            font-size: 14px;
            color: #666;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 38px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        /* ORDERS SECTION */
        .orders-section {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            padding: 45px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            animation: slideInUp 1s ease;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 3px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-tabs {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 25px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            color: #666;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .tab-btn:hover {
            background: #e9ecef;
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }

        /* ORDER CARD */
        .orders-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .order-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 35px;
            border-radius: 20px;
            border-left: 6px solid #667eea;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.03;
            border-radius: 50%;
            transform: translate(50%, -50%);
            transition: all 0.4s;
        }

        .order-card:hover {
            transform: translateX(10px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            background: white;
            border-left-width: 8px;
        }

        .order-card:hover::before {
            opacity: 0.08;
            transform: translate(30%, -30%) scale(1.2);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-number {
            font-size: 22px;
            font-weight: 800;
            color: #333;
            margin-bottom: 8px;
        }

        .order-number span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.5px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .status-badge {
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            animation: pulse 2s infinite;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .status-processing {
            background: linear-gradient(135deg, #cfe2ff 0%, #a8d4ff 100%);
            color: #084298;
        }

        .status-shipped {
            background: linear-gradient(135deg, #cff4fc 0%, #9fe2f5 100%);
            color: #055160;
        }

        .status-delivered, .status-completed {
            background: linear-gradient(135deg, #d1e7dd 0%, #a3d9bb 100%);
            color: #0f5132;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #f8d7da 0%, #ffb3ba 100%);
            color: #842029;
        }

        .order-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .order-info {
            background: white;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .order-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.2);
        }

        .order-label {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-value {
            font-weight: 800;
            color: #333;
            font-size: 17px;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 2px solid #e9ecef;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-total {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn span {
            position: relative;
            z-index: 1;
        }

        .btn-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
        }

        /* EMPTY STATE */
        .empty-orders {
            text-align: center;
            padding: 100px 40px;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease;
        }

        .empty-icon {
            font-size: 140px;
            margin-bottom: 35px;
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

        .empty-orders h2 {
            font-size: 36px;
            color: #333;
            margin-bottom: 18px;
            font-weight: 800;
        }

        .empty-orders p {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.7;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 45px;
            font-size: 17px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .page-header h1 {
                font-size: 40px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .orders-section {
                padding: 30px 25px;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .page-header p {
                font-size: 16px;
            }

            .section-title {
                font-size: 24px;
            }

            .order-body {
                grid-template-columns: 1fr;
            }

            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .stat-card {
                padding: 25px;
            }

            .stat-icon {
                width: 65px;
                height: 65px;
                font-size: 30px;
            }

            .stat-value {
                font-size: 32px;
            }

            .order-card {
                padding: 25px;
            }

            .filter-tabs {
                width: 100%;
            }

            .tab-btn {
                flex: 1;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .header-actions {
                gap: 8px;
            }

            .header-actions a {
                padding: 8px 12px;
                font-size: 13px;
            }

            .logo {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                📚 BookHub
            </a>
            <div class="header-actions">
                <a href="index.php">🏠 Beranda</a>
                <a href="cart.php">🛒 Keranjang<?= $cartCount > 0 ? " ({$cartCount})" : '' ?></a>
                <a href="orders.php" class="active">📦 Pesanan</a>
                <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="logout.php" style="color: #ff4757;">🚪 Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📦 Pesanan Saya</h1>
            <p>Pantau status pesanan dan riwayat pembelian Anda</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3>Total Pesanan</h3>
                    <div class="stat-value"><?= $totalOrders ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3>Menunggu</h3>
                    <div class="stat-value"><?= $pendingOrders ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>Selesai</h3>
                    <div class="stat-value"><?= $completedOrders ?></div>
                </div>
            </div>
        </div>

        <?php if (empty($userOrders)): ?>
            <!-- Empty State -->
            <div class="empty-orders">
                <div class="empty-icon">📦</div>
                <h2>Belum Ada Pesanan</h2>
                <p>Anda belum melakukan pemesanan. Mulai belanja sekarang dan<br>temukan buku-buku favorit Anda!</p>
                <a href="index.php" class="btn btn-primary">
                    <span>🛍️ Mulai Belanja Sekarang</span>
                </a>
            </div>
        <?php else: ?>
            <!-- Orders Section -->
            <div class="orders-section">
                <div class="section-header">
                    <h2 class="section-title">
                        📋 Riwayat Pesanan
                    </h2>
                    <div class="filter-tabs">
                        <button class="tab-btn active" onclick="filterOrders('all')">Semua</button>
                        <button class="tab-btn" onclick="filterOrders('pending')">Pending</button>
                        <button class="tab-btn" onclick="filterOrders('completed')">Selesai</button>
                    </div>
                </div>

                <div class="orders-container" id="ordersContainer">
                    <?php foreach ($userOrders as $o): ?>
                        <div class="order-card" data-status="<?= $o['status'] ?>">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">
                                        Order <span>#<?= htmlspecialchars($o['order_number']) ?></span>
                                    </div>
                                    <div class="order-date">
                                        🗓️ <?= date('d F Y, H:i', strtotime($o['created_at'])) ?> WIB
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $o['status'] ?>">
                                    <?php
                                    $statusIcons = [
                                        'pending' => '⏳',
                                        'processing' => '⚙️',
                                        'shipped' => '🚚',
                                        'delivered' => '📦',
                                        'completed' => '✅',
                                        'cancelled' => '❌'
                                    ];
                                    $statusTexts = [
                                        'pending' => 'Pending',
                                        'processing' => 'Diproses',
                                        'shipped' => 'Dikirim',
                                        'delivered' => 'Terkirim',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    echo $statusIcons[$o['status']] ?? '❓';
                                    ?>
                                    <?= $statusTexts[$o['status']] ?? 'Unknown' ?>
                                </span>
                            </div>

                            <div class="order-body">
                                <div class="order-info">
                                    <div class="order-label">💳 Pembayaran</div>
                                    <div class="order-value"><?= ucwords(str_replace('_', ' ', $o['payment_method'])) ?></div>
                                </div>
                                <div class="order-info">
                                    <div class="order-label">📦 Subtotal</div>
                                    <div class="order-value">Rp <?= number_format($o['total_amount'], 0, ',', '.') ?></div>
                                </div>
                                <div class="order-info">
                                    <div class="order-label">🚚 Ongkir</div>
                                    <div class="order-value">Rp <?= number_format($o['shipping_cost'], 0, ',', '.') ?></div>
                                </div>
                                <div class="order-info">
                                    <div class="order-label">📅 Tanggal</div>
                                    <div class="order-value"><?= date('d/m/Y', strtotime($o['created_at'])) ?></div>
                                </div>
                            </div>

                            <div class="order-footer">
                                <div class="order-total">
                                    Total: Rp <?= number_format($o['final_amount'], 0, ',', '.') ?>
                                </div>
                                <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn btn-info">
                                    <span>👁️ Lihat Detail</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Filter orders by status
        function filterOrders(status) {
            const cards = document.querySelectorAll('.order-card');
            const tabs = document.querySelectorAll('.tab-btn');

            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Filter cards with animation
            cards.forEach((card, index) => {
                card.style.animation = 'none';
                
                setTimeout(() => {
                    if (status === 'all') {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.animation = 'fadeIn 0.5s ease forwards';
                        }, index * 50);
                    } else if (status === 'completed') {
                        const cardStatus = card.dataset.status;
                        if (cardStatus === 'completed' || cardStatus === 'delivered') {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.animation = 'fadeIn 0.5s ease forwards';
                            }, index * 50);
                        } else {
                            card.style.display = 'none';
                        }
                    } else {
                        if (card.dataset.status === status) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.animation = 'fadeIn 0.5s ease forwards';
                            }, index * 50);
                        } else {
                            card.style.display = 'none';
                        }
                    }
                }, 50);
            });
        }

        // Add fade in animation keyframe
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

        // Smooth scroll to top on page load
        window.addEventListener('load', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Add counter animation for stats
        const animateCounter = (element, target, duration = 1000) => {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start);
                }
            }, 16);
        };

        // Trigger counter animation on page load
        window.addEventListener('DOMContentLoaded', () => {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach((stat, index) => {
                const target = parseInt(stat.textContent);
                if (target > 0) {
                    stat.textContent = '0';
                    setTimeout(() => {
                        animateCounter(stat, target, 1000);
                    }, 300 + (index * 150));
                }
            });
        });

        // Add hover effect sound (optional, silent by default)
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderLeftColor = '#764ba2';
            });
            card.addEventListener('mouseleave', function() {
                this.style.borderLeftColor = '#667eea';
            });
        });

        // Lazy load animation for order cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.order-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Add notification badge animation
        const cartBadge = document.querySelector('.header-actions a[href="cart.php"]');
        if (cartBadge && cartBadge.textContent.includes('(')) {
            cartBadge.style.position = 'relative';
            setInterval(() => {
                cartBadge.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    cartBadge.style.transform = 'scale(1)';
                }, 200);
            }, 3000);
        }

        // Show empty state with animation if no orders
        const emptyState = document.querySelector('.empty-orders');
        if (emptyState) {
            setTimeout(() => {
                emptyState.style.transform = 'scale(1)';
                emptyState.style.opacity = '1';
            }, 300);
        }

        // Add tooltip for status badges
        document.querySelectorAll('.status-badge').forEach(badge => {
            const status = badge.classList[1].replace('status-', '');
            const tooltips = {
                'pending': 'Menunggu konfirmasi pembayaran',
                'processing': 'Pesanan sedang diproses',
                'shipped': 'Pesanan dalam pengiriman',
                'delivered': 'Pesanan telah sampai',
                'completed': 'Pesanan selesai',
                'cancelled': 'Pesanan dibatalkan'
            };
            badge.title = tooltips[status] || 'Status pesanan';
        });

        // Add real-time clock for page header
        function updateClock() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleDateString('id-ID', options);
            
            // Optional: Add clock to page if desired
            // document.querySelector('.page-header p').textContent = timeString;
        }
        
        // Update every second (optional)
        // setInterval(updateClock, 1000);

        // Prevent multiple rapid clicks on filter buttons
        let isFiltering = false;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (isFiltering) return;
                isFiltering = true;
                setTimeout(() => {
                    isFiltering = false;
                }, 500);
            });
        });

        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Optional: Add modal close or other functionality
            }
        });

        // Performance optimization: Debounce scroll events
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Add scroll-based animations here if needed
            }, 100);
        });

        // Add print styles optimization
        window.addEventListener('beforeprint', () => {
            document.body.style.background = 'white';
        });

        window.addEventListener('afterprint', () => {
            document.body.style.background = '';
        });

        // Initialize all animations on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Stagger animation for stats cards
            document.querySelectorAll('.stat-card').forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });

            // Animate page header
            const pageHeader = document.querySelector('.page-header');
            if (pageHeader) {
                setTimeout(() => {
                    pageHeader.style.opacity = '1';
                    pageHeader.style.transform = 'translateY(0)';
                }, 50);
            }
        });
    </script>
</body>
</html>