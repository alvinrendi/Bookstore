<?php
// public/reset-password.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

$error = '';
$success = '';
$step = 'email'; // email, verify, reset

// Cek apakah ada token di URL
if (isset($_GET['token'])) {
    $step = 'verify';
    $token = $_GET['token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Step 1: Request Reset Password
    if ($action === 'request_reset') {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = 'Email harus diisi';
        } else {
            try {
                $db = new Database();
                
                // Cek apakah email terdaftar
                $stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $db->error);
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Generate token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Buat koneksi baru untuk update
                    $db2 = new Database();
                    
                    // Cek apakah kolom reset_token sudah ada
                    $checkColumn = $db2->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
                    if ($checkColumn->num_rows == 0) {
                        // Tambahkan kolom jika belum ada
                        $db2->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
                        $db2->query("ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL");
                    }
                    
                    // Update token
                    $stmt2 = $db2->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                    if (!$stmt2) {
                        throw new Exception("Prepare update failed: " . $db2->error);
                    }
                    
                    $stmt2->bind_param("ssi", $token, $expires, $user['id']);
                    
                    if ($stmt2->execute()) {
                        // Generate link reset
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        $path = dirname($_SERVER['PHP_SELF']);
                        $resetLink = $protocol . "://" . $host . $path . "/reset-password.php?token=" . $token;
                        
                        $success = "Link reset password telah dibuat!<br><br>
                                   <strong>Untuk development, klik link ini:</strong><br>
                                   <a href='$resetLink' style='color: #667eea; font-weight: bold; word-break: break-all;'>$resetLink</a><br><br>
                                   <small>Link berlaku selama 1 jam.</small>";
                        $step = 'email';
                    } else {
                        throw new Exception("Execute failed: " . $stmt2->error);
                    }
                    
                    $stmt2->close();
                    $db2->close();
                } else {
                    // Untuk keamanan, tampilkan pesan yang sama meskipun email tidak ditemukan
                    $success = "Jika email terdaftar, link reset password akan dikirim.";
                }
                
                $stmt->close();
                $db->close();
                
            } catch (Exception $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
    
    // Step 2: Verify Token & Reset Password
    elseif ($action === 'reset_password') {
        $token = trim($_POST['token']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Semua field harus diisi';
            $step = 'verify';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password minimal 6 karakter';
            $step = 'verify';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password tidak cocok';
            $step = 'verify';
        } else {
            try {
                $db = new Database();
                
                // Cek token
                $stmt = $db->prepare("SELECT id, email, reset_token_expires FROM users WHERE reset_token = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $db->error);
                }
                
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'Token tidak valid atau sudah digunakan';
                    $step = 'email';
                } else {
                    $user = $result->fetch_assoc();
                    
                    // Cek apakah token expired
                    if (strtotime($user['reset_token_expires']) < time()) {
                        $error = 'Token sudah kadaluarsa. Silakan request ulang.';
                        $step = 'email';
                    } else {
                        // Update password
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        
                        $db2 = new Database();
                        $stmt2 = $db2->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                        if (!$stmt2) {
                            throw new Exception("Prepare update failed: " . $db2->error);
                        }
                        
                        $stmt2->bind_param("si", $hashedPassword, $user['id']);
                        
                        if ($stmt2->execute()) {
                            $success = 'Password berhasil diubah! Silakan login dengan password baru Anda.';
                            $step = 'success';
                        } else {
                            throw new Exception("Execute failed: " . $stmt2->error);
                        }
                        
                        $stmt2->close();
                        $db2->close();
                    }
                }
                
                $stmt->close();
                $db->close();
                
            } catch (Exception $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
                $step = 'verify';
            }
        }
    }
}

// Jika ada token di URL, verifikasi
if ($step === 'verify' && isset($token)) {
    try {
        $db = new Database();
        $stmt = $db->prepare("SELECT id, email, reset_token_expires FROM users WHERE reset_token = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Token tidak valid atau sudah digunakan';
            $step = 'email';
        } else {
            $user = $result->fetch_assoc();
            if (strtotime($user['reset_token_expires']) < time()) {
                $error = 'Token sudah kadaluarsa. Silakan request ulang.';
                $step = 'email';
            }
        }
        
        $stmt->close();
        $db->close();
        
    } catch (Exception $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
        $step = 'email';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BookHub</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            max-width: 500px;
            width: 100%;
            padding: 50px 45px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo-icon {
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin-bottom: 20px;
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 15px;
            margin-bottom: 35px;
            line-height: 1.5;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
            line-height: 1.6;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
            color: white;
        }
        
        .alert a {
            color: white;
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            z-index: 1;
        }
        
        input {
            width: 100%;
            padding: 16px 18px 16px 52px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 6px 24px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 17px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 17px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.35);
            font-family: 'Poppins', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.45);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .back-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            font-size: 13px;
            color: #666;
            margin-top: 10px;
        }
        
        .password-requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        .password-requirements li {
            margin: 5px 0;
        }
        
        .success-icon {
            text-align: center;
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <div class="logo-icon">🔐</div>
        </div>
        
        <?php if ($step === 'email'): ?>
            <!-- Step 1: Request Reset -->
            <h1>Lupa Password?</h1>
            <p class="subtitle">Masukkan email Anda dan kami akan mengirimkan link untuk reset password</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <input type="hidden" name="action" value="request_reset">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="nama@email.com" 
                               required
                               autofocus>
                        <span class="input-icon">📧</span>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">Kirim Link Reset</button>
            </form>
            
        <?php elseif ($step === 'verify'): ?>
            <!-- Step 2: Reset Password -->
            <h1>Buat Password Baru</h1>
            <p class="subtitle">Masukkan password baru Anda</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="passwordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               placeholder="Masukkan password baru" 
                               required
                               autofocus>
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Masukkan ulang password baru" 
                               required>
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                
                <div class="password-requirements">
                    <strong>⚠️ Persyaratan Password:</strong>
                    <ul>
                        <li>Minimal 6 karakter</li>
                        <li>Kedua password harus sama</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
            
        <?php elseif ($step === 'success'): ?>
            <!-- Step 3: Success -->
            <div class="success-icon">✅</div>
            <h1>Berhasil!</h1>
            <p class="subtitle">Password Anda telah berhasil diubah</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <a href="login.php" class="btn">Login Sekarang</a>
            
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Kembali ke Login</a>
        </div>
    </div>
    
    <script>
        // Form submission with loading state
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            });
        }
        
        // Password match validation
        const confirmPassword = document.getElementById('confirm_password');
        const newPassword = document.getElementById('new_password');
        
        if (confirmPassword && newPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.style.borderColor = '#ff6b6b';
                } else {
                    this.style.borderColor = '#51cf66';
                }
            });
        }
    </script>
</body>
</html>