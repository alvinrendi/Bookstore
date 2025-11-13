<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User();
$cart = new Cart();
$userInfo = $user->getUserById($_SESSION['user_id']);
$cartCount = $cart->getCartCount($_SESSION['user_id']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($user->updateProfile($_SESSION['user_id'], $fullName, $phone, $address)) {
        $message = 'Profile berhasil diperbarui!';
        $userInfo = $user->getUserById($_SESSION['user_id']);
    } else {
        $message = 'Gagal memperbarui profile';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .profile-header { text-align: center; margin-bottom: 30px; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn-save { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-save:hover { background: #5568d3; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .profile-section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee; }
        .profile-section:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">📚 BookHub</a>
                <div class="header-actions">
                    <a href="cart.php" class="cart-btn">🛒 Keranjang (<?= $cartCount ?>)</a>
                    <a href="orders.php">Pesanan</a>
                    <a href="profile.php" style="background: rgba(255,255,255,0.3);"><?= htmlspecialchars($_SESSION['username']) ?></a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <h1><?= htmlspecialchars($userInfo['full_name'] ?? 'User') ?></h1>
            <p style="color: #666;"><?= htmlspecialchars($userInfo['email']) ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="profile-section">
                <h3>📋 Informasi Pribadi</h3>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($userInfo['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (tidak dapat diubah)</label>
                    <input type="email" value="<?= htmlspecialchars($userInfo['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($userInfo['phone'] ?? '') ?>" placeholder="+62...">
                </div>
            </div>

            <div class="profile-section">
                <h3>📮 Alamat</h3>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="address" placeholder="Jalan, No rumah, Kota, Provinsi, Kode Pos"><?= htmlspecialchars($userInfo['address'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
        </form>

        <div style="margin-top: 30px; text-align: center;">
            <a href="orders.php" style="color: #667eea; text-decoration: none; margin-right: 20px;">Lihat Pesanan</a>
            <a href="logout.php" style="color: #ff4757; text-decoration: none;">Logout</a>
        </div>
    </div>
</body>
</html>