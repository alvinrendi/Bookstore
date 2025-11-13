<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$user = new User();
$users = $user->getAllUsers();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        if ($userId === 1) {
            $message = 'Tidak dapat menghapus akun admin utama!';
            $messageType = 'error';
        } elseif ($userId === $_SESSION['user_id']) {
            $message = 'Tidak dapat menghapus akun Anda sendiri!';
            $messageType = 'error';
        } elseif ($user->deleteUser($userId)) {
            $message = 'User berhasil dihapus!';
            $messageType = 'success';
            $users = $user->getAllUsers();
        } else {
            $message = 'Gagal menghapus user';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin BookHub</title>
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

        .user-count {
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

        /* MESSAGE STYLES */
        .message {
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
        
        .message.success {
            background: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }
        
        .message.error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }

        .message::before {
            content: '';
            font-size: 20px;
        }

        .message.success::before { content: '✓'; }
        .message.error::before { content: '✗'; }

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

        /* ID BADGE */
        .id-badge {
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            display: inline-block;
        }

        /* USER INFO */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
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

        .user-name {
            font-weight: 600;
            color: #333;
        }

        /* BADGE STYLES */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-admin {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #8b6914;
        }

        .badge-customer {
            background: #e9ecef;
            color: #495057;
        }

        /* BUTTON STYLES */
        .btn {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }

        .btn-danger:active {
            transform: translateY(0);
        }

        .btn-disabled {
            background: #e9ecef;
            color: #adb5bd;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
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

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            animation: slideUp 0.5s ease backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.admins {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        }

        .stat-icon.customers {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .stat-info p {
            font-size: 12px;
            color: #999;
            font-weight: 500;
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

            .user-info {
                flex-direction: column;
                align-items: flex-start;
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
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Pesanan</span>
                </a>
                <a href="users.php" class="nav-item active">
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
                <h1>Kelola Pengguna</h1>
                <div class="user-count">
                    <div class="count-icon">👥</div>
                    <span>Total: <?= count($users) ?> Pengguna</span>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <?php
                $totalUsers = count($users);
                $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
                $customerCount = $totalUsers - $adminCount;
                ?>
                <div class="stat-card">
                    <div class="stat-icon total">👥</div>
                    <div class="stat-info">
                        <h3><?= $totalUsers ?></h3>
                        <p>Total Pengguna</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon admins">👑</div>
                    <div class="stat-info">
                        <h3><?= $adminCount ?></h3>
                        <p>Admin</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon customers">👤</div>
                    <div class="stat-info">
                        <h3><?= $customerCount ?></h3>
                        <p>Customer</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>
                        <span>📋</span>
                        Daftar Pengguna
                    </h2>
                </div>
                
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <span class="id-badge">#<?= $u['id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                                </div>
                                                <span class="user-name"><?= htmlspecialchars($u['username']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="badge badge-admin">
                                                    <span>👑</span> Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-customer">
                                                    <span>👤</span> Customer
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($u['id'] === 1 || $u['id'] === $_SESSION['user_id']): ?>
                                                <button class="btn btn-disabled" disabled title="Akun ini tidak dapat dihapus">
                                                    🔒 Terlindungi
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ Apakah Anda yakin ingin menghapus user ini?\n\nUsername: <?= htmlspecialchars($u['username']) ?>\nEmail: <?= htmlspecialchars($u['email']) ?>\n\nTindakan ini tidak dapat dibatalkan!');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">👥</div>
                                            <p>Belum ada pengguna terdaftar</p>
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

    <script>
    // Auto-hide success message after 3 seconds
    const successAlert = document.querySelector('.message.success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            setTimeout(() => successAlert.remove(), 300);
        }, 3000);
    }

    // Enhanced delete confirmation with user details
    const deleteForms = document.querySelectorAll('form[method="POST"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const deleteBtn = this.querySelector('.btn-danger');
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span style="display: inline-block; width: 14px; height: 14px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span> Menghapus...';
                
                const spinStyle = document.createElement('style');
                spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
                document.head.appendChild(spinStyle);
            }
        });
    });

    // Add tooltip for protected accounts
    const protectedBtns = document.querySelectorAll('.btn-disabled');
    protectedBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.cursor = 'not-allowed';
        });
    });
    </script>
</body>
</html>