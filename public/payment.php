<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💳 Pembayaran - BookHub</title>
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

        .payment-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
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
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .timer-box {
            background: rgba(255, 77, 77, 0.95);
            color: white;
            padding: 15px 30px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 8px 25px rgba(255, 77, 77, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .payment-card {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            animation: slideInUp 0.6s ease;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-summary-mini {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .summary-label {
            color: #666;
            font-weight: 500;
        }

        .summary-value {
            color: #333;
            font-weight: 700;
        }

        .order-number {
            font-size: 24px;
            color: #667eea;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .total-amount {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
        }

        .payment-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 3px solid #f0f0f0;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 15px 30px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            margin-bottom: -3px;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bank-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .bank-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            cursor: pointer;
        }

        .bank-item:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
        }

        .bank-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .account-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .account-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .account-value {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: #764ba2;
            transform: scale(1.05);
        }

        .qris-section {
            text-align: center;
        }

        .qris-image {
            max-width: 350px;
            width: 100%;
            margin: 20px auto;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .ewallet-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .ewallet-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #e0e0e0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .ewallet-item:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
        }

        .ewallet-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .ewallet-name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .ewallet-number {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            padding: 10px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
        }

        .upload-section {
            background: linear-gradient(135deg, #fff9e6 0%, #ffe8cc 100%);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
            border: 2px dashed #ffc107;
        }

        .upload-title {
            font-size: 20px;
            font-weight: 700;
            color: #e65100;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .file-name {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(46, 204, 113, 0.5);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .instructions {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #2196f3;
        }

        .instructions h4 {
            color: #1565c0;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions ol {
            margin: 0;
            padding-left: 25px;
            color: #0d47a1;
        }

        .instructions li {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1f2eb;
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }

        @media (max-width: 768px) {
            .payment-card {
                padding: 25px;
            }

            .bank-list, .ewallet-list {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .timer-box {
                font-size: 16px;
                padding: 12px 20px;
            }

            .payment-tabs {
                gap: 8px;
            }

            .tab-btn {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">📚 BookHub</a>
        </div>
    </header>

    <div class="payment-container">
        <div class="page-header">
            <h1>💳 Halaman Pembayaran</h1>
            <div style="margin-top: 20px;">
                <div class="timer-box">
                    <span>⏰</span>
                    <span>Selesaikan dalam: <span id="countdown">23:59:59</span></span>
                </div>
            </div>
        </div>

        <div class="payment-card">
            <div id="alertContainer"></div>

            <div class="order-summary-mini">
                <div class="summary-row">
                    <span class="summary-label">Nomor Pesanan</span>
                    <span class="order-number">#ORD20241112001</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Metode Pembayaran</span>
                    <span class="summary-value">Transfer Bank</span>
                </div>
                <div class="summary-row" style="border-top: 2px dashed #ddd; margin-top: 15px; padding-top: 15px;">
                    <span class="summary-label" style="font-size: 18px;">Total Pembayaran</span>
                    <span class="total-amount">Rp 285.000</span>
                </div>
            </div>

            <div class="payment-tabs">
                <button class="tab-btn active" data-tab="bank">🏦 Transfer Bank</button>
                <button class="tab-btn" data-tab="qris">📱 QRIS</button>
                <button class="tab-btn" data-tab="ewallet">💳 E-Wallet</button>
            </div>

            <!-- Tab Transfer Bank -->
            <div class="tab-content active" id="bank-tab">
                <div class="instructions">
                    <h4>📋 Cara Transfer Bank</h4>
                    <ol>
                        <li>Pilih salah satu rekening bank di bawah ini</li>
                        <li>Transfer <strong>SESUAI NOMINAL TERTERA</strong></li>
                        <li>Simpan bukti transfer</li>
                        <li>Upload bukti transfer di form bagian bawah</li>
                        <li>Tunggu konfirmasi dari admin (maksimal 1x24 jam)</li>
                    </ol>
                </div>

                <div class="bank-list">
                    <div class="bank-item">
                        <div class="bank-name">🏦 Bank BCA</div>
                        <div class="account-info">
                            <div class="account-label">Nomor Rekening</div>
                            <div class="account-value">
                                <span id="bca-number">1234567890</span>
                                <button class="copy-btn" onclick="copyText('bca-number')">📋 Copy</button>
                            </div>
                        </div>
                        <div class="account-info">
                            <div class="account-label">Nama Pemilik</div>
                            <div class="account-value">
                                <span>PT BookHub Indonesia</span>
                            </div>
                        </div>
                    </div>

                    <div class="bank-item">
                        <div class="bank-name">🏦 Bank Mandiri</div>
                        <div class="account-info">
                            <div class="account-label">Nomor Rekening</div>
                            <div class="account-value">
                                <span id="mandiri-number">9876543210</span>
                                <button class="copy-btn" onclick="copyText('mandiri-number')">📋 Copy</button>
                            </div>
                        </div>
                        <div class="account-info">
                            <div class="account-label">Nama Pemilik</div>
                            <div class="account-value">
                                <span>PT BookHub Indonesia</span>
                            </div>
                        </div>
                    </div>

                    <div class="bank-item">
                        <div class="bank-name">🏦 Bank BNI</div>
                        <div class="account-info">
                            <div class="account-label">Nomor Rekening</div>
                            <div class="account-value">
                                <span id="bni-number">5555666677</span>
                                <button class="copy-btn" onclick="copyText('bni-number')">📋 Copy</button>
                            </div>
                        </div>
                        <div class="account-info">
                            <div class="account-label">Nama Pemilik</div>
                            <div class="account-value">
                                <span>PT BookHub Indonesia</span>
                            </div>
                        </div>
                    </div>

                    <div class="bank-item">
                        <div class="bank-name">🏦 Bank BRI</div>
                        <div class="account-info">
                            <div class="account-label">Nomor Rekening</div>
                            <div class="account-value">
                                <span id="bri-number">3333444455</span>
                                <button class="copy-btn" onclick="copyText('bri-number')">📋 Copy</button>
                            </div>
                        </div>
                        <div class="account-info">
                            <div class="account-label">Nama Pemilik</div>
                            <div class="account-value">
                                <span>PT BookHub Indonesia</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab QRIS -->
            <div class="tab-content" id="qris-tab">
                <div class="instructions">
                    <h4>📋 Cara Bayar dengan QRIS</h4>
                    <ol>
                        <li>Buka aplikasi e-wallet/mobile banking Anda</li>
                        <li>Pilih menu Scan QR atau QRIS</li>
                        <li>Scan kode QR di bawah ini</li>
                        <li>Pastikan nominal <strong>Rp 285.000</strong></li>
                        <li>Konfirmasi pembayaran</li>
                        <li>Screenshot bukti pembayaran dan upload di bawah</li>
                    </ol>
                </div>

                <div class="qris-section">
                    <img src="../qris/QRIS.png" alt="QRIS Code" class="qris-image">
                    <p style="color: #666; margin-top: 15px; font-size: 14px;">
                        QRIS dapat dibayar melalui semua aplikasi: GoPay, OVO, DANA, ShopeePay, LinkAja, dll
                    </p>
                </div>
            </div>

            <!-- Tab E-Wallet -->
            <div class="tab-content" id="ewallet-tab">
                <div class="instructions">
                    <h4>📋 Cara Transfer E-Wallet</h4>
                    <ol>
                        <li>Pilih e-wallet yang Anda gunakan</li>
                        <li>Buka aplikasi e-wallet Anda</li>
                        <li>Transfer ke nomor yang tertera</li>
                        <li>Nominal: <strong>Rp 285.000</strong></li>
                        <li>Screenshot bukti transfer dan upload di bawah</li>
                    </ol>
                </div>

                <div class="ewallet-list">
                    <div class="ewallet-item">
                        <div class="ewallet-icon">💚</div>
                        <div class="ewallet-name">GoPay</div>
                        <div class="ewallet-number" id="gopay-number">089630892307</div>
                        <button class="copy-btn" onclick="copyText('gopay-number')" style="margin-top: 15px; width: 100%;">📋 Copy Nomor</button>
                    </div>

                    <div class="ewallet-item">
                        <div class="ewallet-icon">💜</div>
                        <div class="ewallet-name">OVO</div>
                        <div class="ewallet-number" id="ovo-number">089630892307</div>
                        <button class="copy-btn" onclick="copyText('ovo-number')" style="margin-top: 15px; width: 100%;">📋 Copy Nomor</button>
                    </div>

                    <div class="ewallet-item">
                        <div class="ewallet-icon">💙</div>
                        <div class="ewallet-name">DANA</div>
                        <div class="ewallet-number" id="dana-number">089630892307</div>
                        <button class="copy-btn" onclick="copyText('dana-number')" style="margin-top: 15px; width: 100%;">📋 Copy Nomor</button>
                    </div>

                    <div class="ewallet-item">
                        <div class="ewallet-icon">🧡</div>
                        <div class="ewallet-name">ShopeePay</div>
                        <div class="ewallet-number" id="shopeepay-number">089630892307</div>
                        <button class="copy-btn" onclick="copyText('shopeepay-number')" style="margin-top: 15px; width: 100%;">📋 Copy Nomor</button>
                    </div>
                </div>
            </div>

            <!-- Upload Bukti Pembayaran -->
            <form id="paymentForm" enctype="multipart/form-data">
                <div class="upload-section">
                    <h4 class="upload-title">📤 Upload Bukti Pembayaran</h4>
                    <div class="file-input-wrapper">
                        <input type="file" id="paymentProof" name="payment_proof" accept="image/*" required>
                        <label for="paymentProof" class="file-input-label">
                            <span>📁</span>
                            <span>Pilih File Bukti Transfer</span>
                        </label>
                    </div>
                    <div id="fileName" class="file-name" style="display: none;"></div>
                    <p style="margin-top: 15px; color: #666; font-size: 13px;">
                        Format: JPG, PNG, atau GIF (Maksimal 5MB)
                    </p>
                </div>

                <button type="submit" class="btn-submit">
                    ✅ Konfirmasi & Kirim Bukti Pembayaran
                </button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // Copy to clipboard
        function copyText(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                showAlert('✅ Nomor berhasil disalin!', 'success');
            });
        }

        // File input handler
        document.getElementById('paymentProof').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const fileNameEl = document.getElementById('fileName');
                fileNameEl.textContent = '📎 ' + fileName;
                fileNameEl.style.display = 'block';
            }
        });

        // Form submit
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-submit');
            
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Mengirim bukti pembayaran...';
            
            // Simulate upload (replace with actual API call)
            setTimeout(() => {
                showAlert('✅ Bukti pembayaran berhasil dikirim! Mengarahkan ke halaman sukses...', 'success');
                setTimeout(() => {
                    window.location.href = 'order-success.php?order=ORD20241112001';
                }, 2000);
            }, 2000);
        });

        // Countdown timer
        function startCountdown(duration) {
            let timer = duration;
            const countdownEl = document.getElementById('countdown');
            
            const interval = setInterval(() => {
                const hours = Math.floor(timer / 3600);
                const minutes = Math.floor((timer % 3600) / 60);
                const seconds = timer % 60;
                
                countdownEl.textContent = 
                    String(hours).padStart(2, '0') + ':' + 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
                
                if (--timer < 0) {
                    clearInterval(interval);
                    showAlert('⚠️ Waktu pembayaran habis! Pesanan dibatalkan.', 'error');
                    setTimeout(() => {
                        window.location.href = 'orders.php';
                    }, 3000);
                }
            }, 1000);
        }

        // Start 24 hour countdown
        startCountdown(24 * 60 * 60);

        // Alert helper
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
            container.appendChild(alertDiv);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }
    </script>
</body>
</html>