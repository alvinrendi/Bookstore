<?php
// public/checkout.php - DENGAN GAMBAR BUKU
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Cart.php';
require_once '../classes/Order.php';
require_once '../classes/User.php';
require_once '../classes/Payment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart = new Cart();
$order = new Order();
$user = new User();
$payment = new Payment();

$cartItems = $cart->getCartItems($_SESSION['user_id']);
$cartTotal = $cart->getCartTotal($_SESSION['user_id']);
$userData = $user->getUserById($_SESSION['user_id']);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = $_POST['shipping_address'] ?? '';
    $shippingCost = isset($_POST['shipping_cost']) ? (float)$_POST['shipping_cost'] : 0;
    
    if (empty($shippingAddress)) {
        $error = 'Mohon lengkapi alamat pengiriman';
    } else {
        $result = $order->createOrderFromCart($_SESSION['user_id'], 'pending', $shippingAddress, $shippingCost);
        
        if ($result['success']) {
            $orderData = $order->getOrderByNumber($result['order_number']);
            $paymentResult = $payment->createPayment(
                $orderData['id'],
                $result['order_number'],
                $_SESSION['user_id'],
                'pending',
                $orderData['final_amount']
            );
            
            if ($paymentResult['success']) {
                header('Location: payment.php?order=' . $result['order_number']);
                exit;
            } else {
                $error = 'Gagal membuat pembayaran: ' . $paymentResult['message'];
            }
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
    <title>🛒 Checkout - BookHub</title>
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
            padding-bottom: 50px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-actions a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .header-actions a:hover {
            background: #f0f4ff;
            color: #667eea;
            transform: translateY(-2px);
        }

        .checkout-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.95;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 30px;
            animation: fadeInUp 0.8s ease;
        }

        .checkout-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }

        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f4ff;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input:read-only {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .shipping-options {
            display: grid;
            gap: 15px;
        }

        .shipping-option {
            position: relative;
        }

        .shipping-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .shipping-option label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .shipping-option input:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8edff 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .shipping-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .shipping-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .shipping-details h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .shipping-details p {
            font-size: 13px;
            color: #666;
        }

        .shipping-price {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }

        .btn-checkout {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.5);
        }

        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .summary-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-image-small {
            width: 60px;
            height: 85px;
            border-radius: 8px;
            background: #f5f7fa;
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-image-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }

        .item-info {
            flex: 1;
        }

        .item-title-small {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-qty {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .item-price-small {
            font-size: 15px;
            font-weight: 700;
            color: #667eea;
        }

        .summary-calculations {
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 25px;
            margin-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .total-label {
            font-size: 20px;
            font-weight: 700;
        }

        .total-value {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 1024px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 36px;
            }
            .checkout-form {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">📚 BookHub</a>
            <div class="header-actions">
                <a href="index.php">🏠 Beranda</a>
                <a href="cart.php">🛒 Keranjang</a>
                <a href="orders.php">📦 Pesanan</a>
                <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></a>
            </div>
        </div>
    </header>

    <div class="checkout-container">
        <div class="page-header">
            <h1>🛒 Checkout</h1>
            <p>Lengkapi data pengiriman Anda</p>
        </div>

        <div class="checkout-layout">
            <div class="checkout-form">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="checkoutForm">
                    <!-- Informasi Pengiriman -->
                    <div class="form-section">
                        <h3 class="section-title">📍 Informasi Pengiriman</h3>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" value="<?= htmlspecialchars($userData['full_name'] ?? $userData['username']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nomor Telepon</label>
                            <input type="tel" value="<?= htmlspecialchars($userData['phone'] ?? '-') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Alamat Lengkap Pengiriman *</label>
                            <textarea name="shipping_address" required placeholder="Contoh: Jl. Sudirman No. 123, RT 05/RW 03, Kelurahan Menteng, Jakarta Pusat 10310"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Metode Pengiriman -->
                    <div class="form-section">
                        <h3 class="section-title">🚚 Pilih Metode Pengiriman</h3>
                        <div class="shipping-options">
                            <div class="shipping-option">
                                <input type="radio" name="shipping_cost" id="regular" value="10000" checked>
                                <label for="regular">
                                    <div class="shipping-info">
                                        <div class="shipping-icon">📦</div>
                                        <div class="shipping-details">
                                            <h4>Reguler (3-5 hari)</h4>
                                            <p>Pengiriman standar ke seluruh Indonesia</p>
                                        </div>
                                    </div>
                                    <div class="shipping-price">Rp 10.000</div>
                                </label>
                            </div>
                            <div class="shipping-option">
                                <input type="radio" name="shipping_cost" id="express" value="25000">
                                <label for="express">
                                    <div class="shipping-info">
                                        <div class="shipping-icon">⚡</div>
                                        <div class="shipping-details">
                                            <h4>Express (1-2 hari)</h4>
                                            <p>Pengiriman kilat untuk Jabodetabek</p>
                                        </div>
                                    </div>
                                    <div class="shipping-price">Rp 25.000</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout">
                        💳 Lanjut ke Pembayaran
                    </button>
                </form>
            </div>

            <div class="order-summary">
                <div class="summary-header">
                    <h3>📋 Ringkasan Pesanan</h3>
                </div>

                <div class="summary-items">
                    <?php if (is_array($cartItems) && !empty($cartItems)): 
                        foreach ($cartItems as $item): 
                            // 🔧 FIX: Perbaikan path gambar seperti di wishlist.php
                            $imagePath = null;
                            if (!empty($item['image'])) {
                                $possiblePaths = [
                                    "uploads/books/" . basename($item['image']),
                                    "../uploads/books/" . basename($item['image']),
                                    $item['image'],
                                    "uploads/" . basename($item['image']),
                                    "../uploads/" . basename($item['image'])
                                ];
                                
                                foreach ($possiblePaths as $path) {
                                    if (file_exists(__DIR__ . "/" . $path)) {
                                        $imagePath = $path;
                                        break;
                                    }
                                }
                            }
                    ?>
                        <div class="summary-item">
                            <div class="item-image-small">
                                <?php if ($imagePath): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         alt="<?= htmlspecialchars($item['title'] ?? 'Book') ?>"
                                         onerror="this.parentElement.innerHTML='<div class=\'item-image-placeholder\'>📚</div>'">
                                <?php else: ?>
                                    <div class="item-image-placeholder">📚</div>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <div class="item-title-small"><?= htmlspecialchars($item['title'] ?? 'Book') ?></div>
                                <div class="item-qty">Qty: <?= $item['quantity'] ?? 1 ?></div>
                                <div class="item-price-small">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="summary-calculations">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($cartTotal ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Pengiriman</span>
                        <span id="shipping-display">Rp 10.000</span>
                    </div>
                    <div class="summary-total">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="total-display">Rp <?= number_format(($cartTotal ?? 0) + 10000, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const shippingRadios = document.querySelectorAll('input[name="shipping_cost"]');
        const subtotal = <?= $cartTotal ?? 0 ?>;

        shippingRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const shippingCost = parseInt(this.value);
                document.getElementById('shipping-display').textContent = 'Rp ' + shippingCost.toLocaleString('id-ID');
                document.getElementById('total-display').textContent = 'Rp ' + (subtotal + shippingCost).toLocaleString('id-ID');
            });
        });

        document.getElementById('checkoutForm').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-checkout');
            btn.disabled = true;
            btn.textContent = '⏳ Memproses...';
        });
    </script>
</body>
</html>