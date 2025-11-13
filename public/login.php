<?php
// public/login.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Category.php';
require_once '../classes/Wishlist.php';

$categories = (new Category())->getAllCategories();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan Password harus diisi';
    } else {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['email'] = $result['email'];
            $_SESSION['role'] = $result['role'];
            
            if ($result['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email atau Password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BookHub</title>
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
        
        .login-wrapper {
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
        
        .promo-benefits {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            color: white;
        }
        
        .benefit-icon {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .benefit-text {
            font-size: 14px;
            font-weight: 500;
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
        
        /* RIGHT SIDE - LOGIN FORM */
        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 50px 45px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideInRight 0.6s ease;
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
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .auth-container h2 {
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 36px;
            font-weight: 800;
        }
        
        .auth-subtitle {
            text-align: center;
            color: #666;
            font-size: 15px;
            margin-bottom: 35px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
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
            transition: all 0.3s;
            z-index: 1;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 18px 16px 52px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 6px 24px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .form-group input:focus + .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.15);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .btn-login {
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
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.45);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .auth-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }
        
        .auth-links p {
            color: #666;
            font-size: 15px;
            font-weight: 500;
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
            padding: 15px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        
        .alert::before {
            font-size: 22px;
        }
        
        .alert-error::before {
            content: '⚠️';
        }
        
        .alert-success::before {
            content: '✓';
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 28px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, transparent, #e0e0e0, transparent);
        }
        
        .divider span {
            padding: 0 18px;
            color: #999;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            body {
                padding: 20px 15px;
                align-items: flex-start;
            }
            
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin-top: 20px;
            }
            
            .promo-section {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 40px 30px;
            }
            
            .auth-container h2 {
                font-size: 30px;
            }
            
            .logo-icon {
                width: 75px;
                height: 75px;
                font-size: 38px;
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
    <div class="login-wrapper">
        <!-- LEFT SIDE - PROMO SECTION -->
        <div class="promo-section">
            <div class="promo-header">
                <div class="promo-logo">📚</div>
                <h1>BookHub</h1>
                <p>Platform terpercaya untuk menemukan dan membeli buku favorit Anda dengan mudah dan cepat</p>
            </div>
            
            <div class="promo-features">
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <div class="feature-content">
                        <h3>Akses Instant</h3>
                        <p>Login sekali dan nikmati pengalaman belanja yang seamless</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <div class="feature-content">
                        <h3>Aman & Terpercaya</h3>
                        <p>Data Anda dilindungi dengan enkripsi tingkat tinggi</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-content">
                        <h3>Checkout Cepat</h3>
                        <p>Proses pembelian yang mudah dengan satu klik</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💝</div>
                    <div class="feature-content">
                        <h3>Wishlist Personal</h3>
                        <p>Simpan dan kelola daftar buku favorit Anda</p>
                    </div>
                </div>
            </div>
            
            <div class="promo-benefits">
                <div class="benefit-item">
                    <div class="benefit-icon">✨</div>
                    <div class="benefit-text">Dapatkan rekomendasi buku personal</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🎁</div>
                    <div class="benefit-text">Akses eksklusif ke promo & diskon member</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📦</div>
                    <div class="benefit-text">Track pesanan Anda secara real-time</div>
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
                    <div class="stat-label">Rating</div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE - LOGIN FORM -->
        <div class="auth-container">
            <div class="logo-section">
                <div class="logo-icon">📖</div>
                <h2>Selamat Datang Kembali!</h2>
                <p class="auth-subtitle">Masuk untuk melanjutkan petualangan membaca Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
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
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Masukkan password Anda" 
                               required>
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="reset-password.php">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <span>🚀</span>
                    <span>Masuk Sekarang</span>
                </button>
            </form>
            
            <div class="divider">
                <span>atau</span>
            </div>
            
            <div class="auth-links">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang gratis!</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Form submission with loading state
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('.btn-login');
        
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>⏳</span><span>Memproses...</span>';
        });
        
        // Auto-focus email input
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('email').focus();
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
        
        // Password visibility toggle (optional enhancement)
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                form.submit();
            }
        });
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-15px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>