<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Book.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$db = new Database();
$order = new Order();
$book = new Book();

$totalBooks = $book->getTotalBooks();

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$totalUsers = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'");
$totalRevenue = $stmt->fetch_assoc()['total'] ?? 0;

$recentOrders = $order->getAllOrders(5, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 30px 25px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .logo h2 {
            font-size: 22px;
            font-weight: 700;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            padding: 14px 25px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
            font-size: 15px;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 30px;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
            color: white;
        }
        
        .nav-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .nav-item.logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            color: rgba(255,255,255,0.8);
        }
        
        /* MAIN CONTENT */
        .main {
            grid-column: 2;
            margin-left: 280px;
            padding: 30px;
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        .topbar {
            background: white;
            padding: 25px 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .topbar h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        /* STATS CARDS */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            animation: slideUp 0.5s ease backwards;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
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
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        
        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            position: relative;
            z-index: 1;
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .stat-content h3 {
            font-size: 14px;
            color: #999;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        /* SECTION */
        .section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            animation: slideUp 0.5s ease 0.5s backwards;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section h2 {
            font-size: 22px;
            color: #333;
            font-weight: 600;
        }
        
        /* TABLE */
        .table-wrapper {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table thead {
            background: #f8f9fa;
        }
        
        .table th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table td {
            padding: 16px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }
        
        .table tbody tr {
            transition: all 0.3s;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-shipped {
            background: #cff4fc;
            color: #055160;
        }
        
        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }
        
        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .btn {
            padding: 8px 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main {
                margin-left: 0;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .table-wrapper {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">📚</div>
                    <h2>BookHub Admin</h2>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
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
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Pesanan</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Users</span>
                </a>
                <a href="../public/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <div class="topbar">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">👤</div>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon">📖</div>
                    <div class="stat-content">
                        <h3>Total Buku</h3>
                        <p class="stat-number"><?= number_format($totalBooks) ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3>Pelanggan</h3>
                        <p class="stat-number"><?= number_format($totalUsers) ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3>Pesanan</h3>
                        <p class="stat-number"><?= number_format($totalOrders) ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>Pendapatan</h3>
                        <p class="stat-number">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="section">
                <div class="section-header">
                    <h2>Pesanan Terbaru</h2>
                    <a href="orders.php" class="btn">Lihat Semua</a>
                </div>
                
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                                        <td><?= htmlspecialchars($o['email']) ?></td>
                                        <td><strong>Rp <?= number_format($o['final_amount'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="status status-<?= $o['status'] ?>">
                                                <?= ucfirst($o['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📦</div>
                                            <p>Belum ada pesanan</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>