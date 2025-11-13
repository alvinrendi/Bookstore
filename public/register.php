<?php
// public/register.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        $error = 'Semua field wajib harus diisi';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password tidak sesuai';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10,13}$/', $phone)) {
        $error = 'Nomor telepon harus 10-13 digit';
    } else {
        $user = new User();
        $result = $user->register($username, $email, $password, $fullName, $phone);
        
        if ($result['success']) {
            // Set session
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = 'customer';
            
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
            pointer-events: none;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .register-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 500px 1fr;
            gap: 40px;
            align-items: center;
            margin: auto;
        }
        
        /* LEFT SIDE - PROMO SECTION */
        .promo-section {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: slideInLeft 0.6s ease;
            border: 2px solid rgba(255, 255, 255, 0.2);
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
        
        .promo-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .promo-logo {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin-bottom: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .promo-header h1 {
            color: white;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .promo-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 16px;
            line-height: 1.6;
        }
        
        .promo-features {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .feature-icon {
            width: 55px;
            height: 55px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-content h3 {
            color: white;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 3px;
        }
        
        .feature-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            line-height: 1.4;
        }
        
        .promo-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            color: white;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 12px;
            font-weight: 500;
        }
        
        /* RIGHT SIDE - REGISTER FORM */
        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 40px 35px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideInRight 0.6s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Custom scrollbar */
        .auth-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .auth-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        .auth-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
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
        
        .form-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-header h2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .form-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 16px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .required {
            color: #ff4757;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            transition: all 0.3s;
            z-index: 1;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
        }
        
        .form-group input:focus + .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }
        
        .password-strength {
            margin-top: 6px;
            font-size: 11px;
            color: #666;
        }
        
        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 3px;
        }
        
        .password-match {
            margin-top: 5px;
            font-size: 12px;
            display: none;
        }
        
        .password-match.match {
            color: #2ecc71;
            display: block;
        }
        
        .password-match.no-match {
            color: #ff4757;
            display: block;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(-1px);
        }
        
        .auth-links {
            text-align: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #e0e0e0;
        }
        
        .auth-links p {
            color: #666;
            font-size: 14px;
        }
        
        .auth-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        
        .auth-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .alert::before {
            font-size: 18px;
        }
        
        .alert-error::before {
            content: '⚠️';
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            body {
                padding: 20px 15px;
                align-items: flex-start;
            }
            
            .register-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin-top: 20px;
            }
            
            .promo-section {
                display: none;
            }
            
            .auth-container {
                max-height: none;
            }
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 25px;
            }
            
            .form-header h2 {
                font-size: 26px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                margin-bottom: 14px;
            }
        }
        
        @media (min-height: 900px) {
            body {
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <!-- LEFT SIDE - PROMO SECTION -->
        <div class="promo-section">
            <div class="promo-header">
                <div class="promo-logo">📚</div>
                <h1>BookHub</h1>
                <p>Temukan ribuan buku terbaik dan mulai petualangan membaca Anda bersama kami</p>
            </div>
            
            <div class="promo-features">
                <div class="feature-card">
                    <div class="feature-icon">📖</div>
                    <div class="feature-content">
                        <h3>Koleksi Lengkap</h3>
                        <p>Ribuan buku dari berbagai genre dan penulis terbaik</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💝</div>
                    <div class="feature-content">
                        <h3>Wishlist Pribadi</h3>
                        <p>Simpan buku favorit untuk dibeli nanti</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-content">
                        <h3>Pengiriman Cepat</h3>
                        <p>Proses order cepat dengan pengiriman ke seluruh Indonesia</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎁</div>
                    <div class="feature-content">
                        <h3>Promo Menarik</h3>
                        <p>Dapatkan diskon dan penawaran spesial untuk member</p>
                    </div>
                </div>
            </div>
            
            <div class="promo-stats">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Buku Tersedia</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5K+</div>
                    <div class="stat-label">Member Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.8★</div>
                    <div class="stat-label">Rating Pengguna</div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE - REGISTER FORM -->
        <div class="auth-container">
            <div class="form-header">
                <h2>Daftar Sekarang</h2>
                <p class="form-subtitle">Buat akun dan mulai belanja buku favorit</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="Pilih username unik" 
                               required 
                               minlength="3"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               placeholder="Nama lengkap Anda" 
                               required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        <span class="input-icon">✍️</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   placeholder="nama@email.com" 
                                   required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <span class="input-icon">📧</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">No. Telepon</label>
                        <div class="input-wrapper">
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="08xxxxxxxxxx" 
                                   pattern="[0-9]{10,13}"
                                   title="Nomor telepon harus 10-13 digit"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            <span class="input-icon">📱</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Minimal 6 karakter" 
                               required 
                               minlength="6">
                        <span class="input-icon">🔒</span>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Ketik ulang password" 
                               required>
                        <span class="input-icon">🔐</span>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <button type="submit" class="btn-register">✨ Daftar Sekarang</button>
            </form>
            
            <div class="auth-links">
                <p>Sudah punya akun? <a href="login.php">Login sekarang</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthFill = document.querySelector('.strength-fill');
        
        passwordInput.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            if (value.length >= 6) strength += 33;
            if (value.length >= 10) strength += 33;
            if (/[A-Z]/.test(value) && /[0-9]/.test(value)) strength += 34;
            
            strengthFill.style.width = strength + '%';
            
            if (strength <= 33) {
                strengthFill.style.background = 'linear-gradient(90deg, #ff6b6b, #ee5a6f)';
            } else if (strength <= 66) {
                strengthFill.style.background = 'linear-gradient(90deg, #ffd93d, #ffb800)';
            } else {
                strengthFill.style.background = 'linear-gradient(90deg, #51cf66, #37b24d)';
            }
        });
        
        // Password match checker
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchDiv = document.getElementById('passwordMatch');
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatchDiv.style.display = 'none';
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatchDiv.textContent = '✓ Password cocok';
                passwordMatchDiv.className = 'password-match match';
            } else {
                passwordMatchDiv.textContent = '✗ Password tidak cocok';
                passwordMatchDiv.className = 'password-match no-match';
            }
        }
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', checkPasswordMatch);
        
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Form validation
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const phone = phoneInput.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ Password dan konfirmasi password tidak cocok!');
                confirmPasswordInput.focus();
                return false;
            }
            
            if (phone && (phone.length < 10 || phone.length > 13)) {
                e.preventDefault();
                alert('❌ Nomor telepon harus 10-13 digit!');
                phoneInput.focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('.btn-register');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Mendaftarkan...';
        });
        
        // Auto-focus on first input
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('username').focus();
        });
        
        // Add input animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>