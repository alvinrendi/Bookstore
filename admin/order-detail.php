<?php
// admin/order-detail.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order = new Order();
$orderId = (int)$_GET['id'];
$orderData = $order->getOrderById($orderId);
$orderItems = $order->getOrderItems($orderId);

if (!$orderData) {
    header('Location: orders.php');
    exit;
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = trim($_POST['status']);
    if ($order->updateOrderStatus($orderId, $status)) {
        header('Location: order-detail.php?id=' . $orderId . '&updated=1');
        exit;
    } else {
        $error = 'Gagal memperbarui status';
    }
}

$statusConfig = [
    'pending' => ['color' => '#ffc107', 'icon' => '⏳', 'text' => 'Menunggu Pembayaran'],
    'processing' => ['color' => '#17a2b8', 'icon' => '⚙️', 'text' => 'Diproses'],
    'shipped' => ['color' => '#007bff', 'icon' => '🚚', 'text' => 'Dikirim'],
    'delivered' => ['color' => '#28a745', 'icon' => '📦', 'text' => 'Terkirim'],
    'completed' => ['color' => '#28a745', 'icon' => '✅', 'text' => 'Selesai'],
    'cancelled' => ['color' => '#dc3545', 'icon' => '❌', 'text' => 'Dibatalkan']
];

$currentStatus = $statusConfig[$orderData['status']] ?? ['color' => '#6c757d', 'icon' => '❓', 'text' => 'Unknown'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= htmlspecialchars($orderData['order_number']) ?> - Admin BookHub</title>
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
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            color: #333;
            padding: 0;
            position: fixed;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
            box-shadow: 4px 0 30px rgba(0,0,0,0.15);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 30px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .logo h2 {
            font-size: 22px;
            font-weight: 800;
            color: white;
            line-height: 1.2;
        }
        
        .logo small {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            padding: 16px 25px;
            color: #666;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
            font-size: 15px;
            margin: 5px 0;
        }
        
        .nav-item:hover {
            background: linear-gradient(90deg, rgba(102,126,234,0.1) 0%, transparent 100%);
            color: #667eea;
            padding-left: 30px;
            border-left-color: #667eea;
        }
        
        .nav-item.active {
            background: linear-gradient(90deg, rgba(102,126,234,0.15) 0%, transparent 100%);
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 700;
        }
        
        .nav-icon {
            font-size: 22px;
            width: 28px;
            text-align: center;
        }
        
        /* MAIN CONTENT */
        .main {
            grid-column: 2;
            margin-left: 280px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 12px 20px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
            background: rgba(255,255,255,0.3);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 18px 24px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideIn 0.3s ease;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        @keyframes slideIn {
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
            background: linear-gradient(135deg, #d1e7dd 0%, #a3d9bb 100%);
            color: #0f5132;
            border-left: 5px solid #0f5132;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #ffb3ba 100%);
            color: #842029;
            border-left: 5px solid #842029;
        }
        
        .order-header {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .order-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .order-title {
            font-size: 32px;
            color: #333;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .order-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .order-date {
            color: #666;
            font-size: 15px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge {
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .status-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .status-form h4 {
            margin-bottom: 18px;
            color: #333;
            font-weight: 700;
            font-size: 18px;
        }
        
        .status-select-wrapper {
            display: flex;
            gap: 12px;
        }
        
        .status-select {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }
        
        .btn-update {
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102,126,234,0.5);
        }
        
        .order-content {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 30px;
        }
        
        .section {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 30px;
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
            padding: 25px 0;
            border-bottom: 2px solid #f5f5f5;
        }
        
        .order-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .order-item:first-child {
            padding-top: 0;
        }
        
        .item-image {
            width: 90px;
            height: 130px;
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-weight: 700;
            font-size: 17px;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .item-author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            font-style: italic;
        }
        
        .item-quantity {
            color: #666;
            font-size: 14px;
            margin: 8px 0;
            font-weight: 500;
        }
        
        .item-price {
            font-weight: 700;
            color: #667eea;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .price-strike {
            font-size: 13px;
            color: #999;
            text-decoration: line-through;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
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
            margin-top: 20px;
            padding-top: 20px;
            border-top: 3px solid #e0e0e0;
        }
        
        .total-label {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .total-value {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .customer-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .customer-info-item {
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .customer-info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            color: #667eea;
            font-size: 20px;
            margin-top: 2px;
        }
        
        .customer-info-content {
            flex: 1;
        }
        
        .customer-info-label {
            font-weight: 700;
            color: #333;
            font-size: 13px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .customer-info-text {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
            font-weight: 500;
        }
        
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main {
                margin-left: 0;
            }
            
            .order-content {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main {
                padding: 20px;
            }

            .order-header {
                padding: 25px;
            }

            .section {
                padding: 25px;
            }
            
            .order-header-top {
                flex-direction: column;
                gap: 20px;
            }
            
            .status-select-wrapper {
                flex-direction: column;
            }

            .order-title {
                font-size: 24px;
            }

            .order-number {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">📚</div>
                <h2>
                    BookHub
                    <small>Admin Panel</small>
                </h2>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="books.php" class="nav-item">
                <span class="nav-icon">📖</span>
                <span>Kelola Buku</span>
            </a>
            <a href="categories.php" class="nav-item">
                <span class="nav-icon">🏷️</span>
                <span>Kategori</span>
            </a>
            <a href="orders.php" class="nav-item active">
                <span class="nav-icon">📦</span>
                <span>Pesanan</span>
            </a>
            <a href="users.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span>Users</span>
            </a>
            <a href="../public/logout.php" class="nav-item">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <a href="orders.php" class="back-btn">
            ← Kembali ke Daftar Pesanan
        </a>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                ✓ Status pesanan berhasil diperbarui!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="order-header">
            <div class="order-header-top">
                <div>
                    <h1 class="order-title">Detail Pesanan</h1>
                    <p class="order-number">#<?= htmlspecialchars($orderData['order_number']) ?></p>
                    <p class="order-date">
                        📅 <?= date('d F Y, H:i', strtotime($orderData['created_at'])) ?> WIB
                    </p>
                </div>
                <span class="status-badge" style="background: <?= $currentStatus['color'] ?>20; color: <?= $currentStatus['color'] ?>;">
                    <?= $currentStatus['icon'] ?> <?= $currentStatus['text'] ?>
                </span>
            </div>

            <!-- Update Status Form -->
            <div class="status-form">
                <h4>🔄 Ubah Status Pesanan</h4>
                <form method="POST" class="status-select-wrapper">
                    <select name="status" class="status-select" required>
                        <option value="pending" <?= $orderData['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                        <option value="processing" <?= $orderData['status'] === 'processing' ? 'selected' : '' ?>>⚙️ Diproses</option>
                        <option value="shipped" <?= $orderData['status'] === 'shipped' ? 'selected' : '' ?>>🚚 Dikirim</option>
                        <option value="delivered" <?= $orderData['status'] === 'delivered' ? 'selected' : '' ?>>📦 Terkirim</option>
                        <option value="completed" <?= $orderData['status'] === 'completed' ? 'selected' : '' ?>>✅ Selesai</option>
                        <option value="cancelled" <?= $orderData['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Dibatalkan</option>
                    </select>
                    <button type="submit" class="btn-update" onclick="return confirm('Ubah status pesanan ini?')">
                        💾 Update Status
                    </button>
                </form>
            </div>
        </div>

        <div class="order-content">
            <!-- Order Items -->
            <div>
                <div class="section">
                    <h2 class="section-title">📚 Buku yang Dipesan</h2>
                    <?php foreach ($orderItems as $item): 
                        $imagePath = !empty($item['image']) && file_exists(__DIR__ . '/../public/uploads/books/' . $item['image']) 
                            ? '../public/uploads/books/' . $item['image'] 
                            : '../public/assets/images/default-book.jpg';
                        $finalPrice = $item['price'] * (100 - $item['discount_percent']) / 100;
                    ?>
                        <div class="order-item">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="item-author">oleh <?= htmlspecialchars($item['author']) ?></div>
                                <div class="item-quantity">
                                    📦 Jumlah: <strong><?= $item['quantity'] ?> buku</strong>
                                </div>
                                <?php if ($item['discount_percent'] > 0): ?>
                                    <span class="price-strike">
                                        Rp <?= number_format($item['price'], 0, ',', '.') ?>
                                    </span>
                                <?php endif; ?>
                                <div class="item-price">
                                    Rp <?= number_format($finalPrice, 0, ',', '.') ?> × <?= $item['quantity'] ?> = 
                                    <strong>Rp <?= number_format($finalPrice * $item['quantity'], 0, ',', '.') ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Info -->
            <div>
                <!-- Customer Info -->
                <div class="section" style="margin-bottom: 20px;">
                    <h3 class="section-title">👤 Informasi Pelanggan</h3>
                    <div class="customer-info">
                        <div class="customer-info-item">
                            <span class="info-icon">👤</span>
                            <div class="customer-info-content">
                                <div class="customer-info-label">Nama Lengkap</div>
                                <div class="customer-info-text"><?= htmlspecialchars($orderData['full_name']) ?></div>
                            </div>
                        </div>
                        <div class="customer-info-item">
                            <span class="info-icon">📧</span>
                            <div class="customer-info-content">
                                <div class="customer-info-label">Email</div>
                                <div class="customer-info-text"><?= htmlspecialchars($orderData['email']) ?></div>
                            </div>
                        </div>
                        <div class="customer-info-item">
                            <span class="info-icon">📱</span>
                            <div class="customer-info-content">
                                <div class="customer-info-label">Nomor Telepon</div>
                                <div class="customer-info-text"><?= htmlspecialchars($orderData['phone']) ?></div>
                            </div>
                        </div>
                        <div class="customer-info-item">
                            <span class="info-icon">📍</span>
                            <div class="customer-info-content">
                                <div class="customer-info-label">Alamat Pengiriman</div>
                                <div class="customer-info-text"><?= nl2br(htmlspecialchars($orderData['shipping_address'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="section">
                    <h3 class="section-title">💰 Ringkasan Pembayaran</h3>
                    <div class="info-row">
                        <span class="info-label">💳 Metode Pembayaran</span>
                        <span class="info-value" style="color: #667eea;">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $orderData['payment_method']))) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📦 Subtotal</span>
                        <span class="info-value">Rp <?= number_format($orderData['total_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🚚 Biaya Pengiriman</span>
                        <span class="info-value">Rp <?= number_format($orderData['shipping_cost'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-row total-row">
                        <span class="total-label">Total Pembayaran</span>
                        <span class="total-value">Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Auto-hide success message after 4 seconds
const successAlert = document.querySelector('.alert-success');
if (successAlert) {
    setTimeout(() => {
        successAlert.style.transition = 'all 0.5s ease';
        successAlert.style.opacity = '0';
        successAlert.style.transform = 'translateY(-20px)';
        setTimeout(() => successAlert.remove(), 500);
    }, 4000);
}

// Animate elements on load
window.addEventListener('load', () => {
    document.querySelectorAll('.order-item, .customer-info-item, .info-row').forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        setTimeout(() => {
            el.style.transition = 'all 0.5s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>
</body>
</html>