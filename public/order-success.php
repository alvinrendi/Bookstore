<?php
// public/order-success.php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order'])) {
    header('Location: index.php');
    exit;
}

$orderNumber = $_GET['order'];
$order = new Order();
$db = new Database();

$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNumber, $_SESSION['user_id']);
$stmt->execute();
$orderData = $stmt->get_result()->fetch_assoc();

if (!$orderData) {
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            position: relative;
            overflow-x: hidden;
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

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: -1px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
        }

        .header-actions a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .header-actions a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }

        .success-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px 60px;
            position: relative;
            z-index: 1;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 60px 50px;
            border-radius: 30px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            position: relative;
            overflow: hidden;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #4facfe, #00f2fe, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }

        .success-icon-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 35px;
        }

        .success-icon {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 70px;
            box-shadow: 
                0 10px 40px rgba(46, 204, 113, 0.4),
                0 0 0 10px rgba(46, 204, 113, 0.1),
                0 0 0 20px rgba(46, 204, 113, 0.05);
            animation: successPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            z-index: 2;
        }

        @keyframes successPop {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(10deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .success-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px dashed #2ecc71;
            animation: rotate 20s linear infinite;
            top: -15px;
            left: -15px;
            width: calc(100% + 30px);
            height: calc(100% + 30px);
        }

        @keyframes rotate {
            100% { transform: rotate(360deg); }
        }

        .success-title {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 800;
            letter-spacing: -1px;
            animation: slideDown 0.6s ease 0.3s both;
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

        .success-message {
            font-size: 17px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.7;
            animation: fadeIn 0.6s ease 0.5s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .order-info-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 20px;
            margin: 40px 0;
            border: 2px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease 0.7s both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-info-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, #667eea, #764ba2);
        }

        .order-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 2px dashed rgba(0, 0, 0, 0.05);
        }

        .order-info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .order-info-item:first-child {
            padding-top: 0;
        }

        .info-label {
            color: #666;
            font-weight: 500;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label::before {
            content: '●';
            color: #667eea;
            font-size: 12px;
        }

        .info-value {
            color: #333;
            font-weight: 700;
            font-size: 16px;
        }

        .order-number-big {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideUp 0.6s ease 1.2s both;
        }

        .btn {
            padding: 16px 36px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            position: relative;
            overflow: hidden;
            border: none;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 3px solid #667eea;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .payment-instruction {
            background: linear-gradient(135deg, #fff9e6 0%, #ffe8cc 100%);
            padding: 30px;
            border-radius: 20px;
            margin-top: 35px;
            border: 2px solid rgba(255, 193, 7, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease 0.9s both;
        }

        .payment-instruction::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, #ffc107, #ff9800);
        }

        .payment-instruction h4 {
            color: #e65100;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-instruction ol {
            margin: 0;
            padding-left: 25px;
            color: #6d4c00;
        }

        .payment-instruction li {
            margin-bottom: 12px;
            line-height: 1.7;
            font-weight: 500;
        }

        .payment-instruction li strong {
            color: #e65100;
            font-weight: 700;
            background: rgba(255, 193, 7, 0.2);
            padding: 2px 8px;
            border-radius: 6px;
        }

        /* Floating particles */
        .particle {
            position: fixed;
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .success-card {
                padding: 40px 30px;
                border-radius: 25px;
            }

            .success-icon-wrapper {
                width: 120px;
                height: 120px;
            }

            .success-icon {
                width: 120px;
                height: 120px;
                font-size: 60px;
            }

            .success-title {
                font-size: 28px;
            }

            .success-message {
                font-size: 15px;
            }

            .order-number-big {
                font-size: 22px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .order-info-box {
                padding: 25px 20px;
            }

            .payment-instruction {
                padding: 25px 20px;
            }
        }

        @media (max-width: 480px) {
            .success-card {
                padding: 30px 20px;
            }

            .logo {
                font-size: 24px;
            }

            .header-actions {
                gap: 10px;
            }

            .header-actions a {
                font-size: 14px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">📚 BookHub</a>
                <div class="header-actions">
                    <a href="orders.php">📦 Pesanan</a>
                    <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
                </div>
            </div>
        </div>
    </header>

    <div class="success-container">
        <div class="success-card">
            <div class="success-icon-wrapper">
                <div class="success-icon">✓</div>
            </div>
            <h1 class="success-title">Pesanan Berhasil Dibuat!</h1>
            <p class="success-message">
                Terima kasih telah berbelanja di BookHub. Pesanan Anda telah kami terima dan akan segera diproses dengan penuh perhatian.
            </p>

            <div class="order-info-box">
                <div class="order-info-item">
                    <span class="info-label">Nomor Pesanan</span>
                    <span class="order-number-big">#<?= htmlspecialchars($orderData['order_number']) ?></span>
                </div>
                <div class="order-info-item">
                    <span class="info-label">Total Pembayaran</span>
                    <span class="info-value">Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></span>
                </div>
                <div class="order-info-item">
                    <span class="info-label">Metode Pembayaran</span>
                    <span class="info-value"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $orderData['payment_method']))) ?></span>
                </div>
                <div class="order-info-item">
                    <span class="info-label">Status Pesanan</span>
                    <span class="status-badge">⏳ Menunggu Pembayaran</span>
                </div>
            </div>

            <?php if ($orderData['payment_method'] === 'bank_transfer'): ?>
                <div class="payment-instruction">
                    <h4>💳 Instruksi Pembayaran Transfer Bank</h4>
                    <ol>
                        <li>Transfer ke <strong>Bank BCA 1234567890</strong> a/n <strong>PT BookHub Indonesia</strong></li>
                        <li>Nominal transfer: <strong>Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></strong></li>
                        <li>Masukkan kode unik: <strong><?= substr($orderData['order_number'], -6) ?></strong></li>
                        <li>Konfirmasi pembayaran melalui WhatsApp: <strong>0896-3089-2307</strong></li>
                        <li>Pesanan akan diproses setelah pembayaran dikonfirmasi (maksimal 1x24 jam)</li>
                    </ol>
                </div>
            <?php elseif ($orderData['payment_method'] === 'cod'): ?>
                <div class="payment-instruction">
                    <h4>💵 Instruksi Pembayaran Cash on Delivery</h4>
                    <ol>
                        <li>Pembayaran dilakukan saat barang sampai di lokasi Anda</li>
                        <li>Siapkan uang pas sejumlah <strong>Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></strong></li>
                        <li>Periksa kondisi paket dengan teliti sebelum melakukan pembayaran</li>
                        <li>Kurir akan memberikan bukti pengiriman resmi setelah pembayaran</li>
                        <li>Simpan bukti pengiriman sebagai bukti transaksi Anda</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="payment-instruction">
                    <h4>💚 Instruksi Pembayaran <?= htmlspecialchars(strtoupper($orderData['payment_method'])) ?></h4>
                    <ol>
                        <li>Buka aplikasi <strong><?= htmlspecialchars(ucwords($orderData['payment_method'])) ?></strong> di smartphone Anda</li>
                        <li>Pilih menu <strong>Transfer</strong> atau <strong>Bayar</strong></li>
                        <li>Transfer ke nomor <strong>0896-3089-2307</strong> a/n <strong>BookHub</strong></li>
                        <li>Nominal transfer: <strong>Rp <?= number_format($orderData['final_amount'], 0, ',', '.') ?></strong></li>
                        <li>Screenshot bukti transfer dan kirim ke WhatsApp kami untuk konfirmasi</li>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="btn-group">
                <a href="order-detail.php?id=<?= $orderData['id'] ?>" class="btn btn-primary">
                    <span>📋 Lihat Detail Pesanan</span>
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <span>🏠 Kembali Berbelanja</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Enhanced confetti animation
        window.addEventListener('load', function() {
            createConfettiExplosion();
            createFloatingParticles();
        });

        function createConfettiExplosion() {
            const colors = ['#667eea', '#764ba2', '#2ecc71', '#ffc107', '#ff4757', '#f093fb', '#4facfe'];
            
            for (let i = 0; i < 100; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    const size = Math.random() * 8 + 4;
                    const startX = Math.random() * window.innerWidth;
                    const rotation = Math.random() * 360;
                    const delay = Math.random() * 0.5;
                    
                    confetti.style.cssText = `
                        position: fixed;
                        width: ${size}px;
                        height: ${size}px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        top: -20px;
                        left: ${startX}px;
                        opacity: 1;
                        pointer-events: none;
                        z-index: 9999;
                        border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                        animation: confettiFall ${2 + Math.random() * 2}s ease-in-out ${delay}s forwards;
                        transform: rotate(${rotation}deg);
                    `;
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 20);
            }
        }

        function createFloatingParticles() {
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.cssText = `
                    top: ${Math.random() * 100}%;
                    left: ${Math.random() * 100}%;
                    animation-delay: ${Math.random() * 3}s;
                    animation-duration: ${3 + Math.random() * 2}s;
                `;
                document.body.appendChild(particle);
            }
        }

        // Add confetti animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confettiFall {
                0% {
                    transform: translateY(0) rotate(0deg) translateX(0);
                    opacity: 1;
                }
                100% {
                    transform: translateY(${window.innerHeight + 100}px) rotate(${720 + Math.random() * 360}deg) translateX(${Math.random() * 200 - 100}px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>