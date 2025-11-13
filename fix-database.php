<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = new Database();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_admin') {
        $result = $db->query("SELECT id, email, role FROM users WHERE email = 'admin@bookstore.com'");
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $message = "✅ Admin sudah ada. ID: " . $admin['id'] . ", Role: " . $admin['role'];
        } else {
            $message = "❌ Admin tidak ditemukan. Silakan reset password untuk membuatnya.";
        }
    }
    elseif ($action === 'check_tables') {
        $tables = ['users', 'categories', 'books', 'cart', 'orders', 'order_items', 'reviews', 'wishlist'];
        $missing = [];
        
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                $missing[] = $table;
            }
        }
        
        if (empty($missing)) {
            $message = "✅ Semua tabel sudah ada!";
        } else {
            $message = "❌ Tabel yang hilang: " . implode(', ', $missing);
        }
    }
    elseif ($action === 'verify_users') {
        $result = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch_assoc()['count'];
        $message = "✅ Total users dalam database: " . $count;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database - BookHub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; margin-bottom: 20px; text-align: center; }
        button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin-bottom: 10px; }
        button:hover { background: #5568d3; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: monospace; }
        .message.success { background: #d1e7dd; color: #0f5132; }
        .message.error { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Diagnostics</h1>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="action" value="check_tables">Cek Semua Tabel</button>
            <button type="submit" name="action" value="check_admin">Cek Admin User</button>
            <button type="submit" name="action" value="verify_users">Verifikasi Semua Users</button>
        </form>
    </div>
</body>
</html>