<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$order = new Order();
$db = new Database();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalOrders / $limit);

$orders = $order->getAllOrders($limit, $offset);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = trim($_POST['status']);
    if ($order->updateOrderStatus($orderId, $status)) {
        header('Location: orders.php?updated=1');
        exit;
    } else {
        $error = 'Gagal memperbarui status pesanan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin BookHub</title>
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

        .stats-badge {
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

        .count-icon {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* ALERTS */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            background: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }

        .alert::before {
            content: '';
            font-size: 20px;
        }

        .alert-success::before { content: '✓'; }
        .alert-error::before { content: '✗'; }

        /* SECTION */
        .section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            animation: slideUp 0.5s ease backwards;
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
            display: flex;
            align-items: center;
            gap: 10px;
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
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* ORDER NUMBER */
        .order-number {
            font-weight: 700;
            color: #667eea;
            font-size: 15px;
        }

        /* CUSTOMER INFO */
        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
        }

        /* AMOUNT */
        .amount {
            font-weight: 700;
            color: #2d3748;
            font-size: 15px;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        /* STATUS DROPDOWN */
        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 35px;
        }

        .status-select:hover {
            border-color: #667eea;
            background-color: #fafafa;
        }

        .status-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }

        .status-select option {
            padding: 10px;
        }

        /* DATE */
        .date {
            color: #6c757d;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* BUTTONS */
        .btn-detail {
            padding: 8px 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-detail:active {
            transform: translateY(0);
        }

        /* PAGINATION */
        .pagination {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        .pagination a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
        }
        
        .pagination a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .pagination a:hover:not(.active) {
            background: #f8f9fa;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
        }

        /* RESPONSIVE */
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

            .topbar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .main {
                padding: 20px;
            }

            .customer-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .pagination {
                flex-wrap: wrap;
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
            <a href="../logout.php" class="nav-item logout">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <div class="topbar">
            <h1>Kelola Pesanan</h1>
            <div class="stats-badge">
                <div class="count-icon">📦</div>
                <span>Total: <?= number_format($totalOrders) ?> Pesanan</span>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                Status pesanan berhasil diperbarui
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-header">
                <h2>
                    <span>📋</span>
                    Daftar Pesanan
                </h2>
            </div>
            
            <?php if (!empty($orders)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <span class="order-number">#<?= htmlspecialchars($o['order_number']) ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?= strtoupper(substr($o['full_name'], 0, 1)) ?>
                                        </div>
                                        <span class="customer-name"><?= htmlspecialchars($o['full_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="amount">Rp <?= number_format($o['final_amount'], 0, ',', '.') ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" class="status-select" onchange="if(confirm('Ubah status pesanan ini?')) this.form.submit(); else this.selectedIndex = Array.from(this.options).findIndex(opt => opt.value === '<?= $o['status'] ?>');">
                                            <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                            <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>✅ Selesai</option>
                                            <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Dibatalkan</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="date">
                                        📅 <?= date('d M Y', strtotime($o['created_at'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn-detail">
                                        👁️ Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1" title="Halaman Pertama">«</a>
                            <a href="?page=<?= $page - 1 ?>" title="Sebelumnya">‹</a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" title="Selanjutnya">›</a>
                            <a href="?page=<?= $totalPages ?>" title="Halaman Terakhir">»</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <p>Belum ada pesanan yang masuk</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Auto-hide success message after 3 seconds
const successAlert = document.querySelector('.alert-success');
if (successAlert) {
    setTimeout(() => {
        successAlert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        successAlert.style.opacity = '0';
        successAlert.style.transform = 'translateY(-20px)';
        setTimeout(() => successAlert.remove(), 300);
    }, 3000);
}

// Smooth scroll to top when page changes
if (window.location.search.includes('page=')) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Add loading state when submitting status change
const statusForms = document.querySelectorAll('.status-form');
statusForms.forEach(form => {
    form.addEventListener('submit', function() {
        const select = this.querySelector('.status-select');
        select.disabled = true;
        select.style.opacity = '0.6';
        select.style.cursor = 'not-allowed';
    });
});
</script>
</body>
</html>