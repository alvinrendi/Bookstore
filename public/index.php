<?php
// public/index.php - Halaman Utama Enhanced dengan Wishlist
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';
require_once '../classes/User.php';
require_once '../classes/Wishlist.php'; // Tambahkan ini

$book = new Book();
$cart = new Cart();
$wishlist = new Wishlist(); // Tambahkan ini
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;

if ($categoryId) {
    $books = $book->getBooksByCategory($categoryId, $limit, $offset);
    $db = new Database();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM books WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $totalBooks = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $books = $book->getAllBooks($limit, $offset);
    $totalBooks = $book->getTotalBooks();
}

$totalPages = ceil($totalBooks / $limit);
$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;
$wishlistCount = isset($_SESSION['user_id']) ? $wishlist->getWishlistCount($_SESSION['user_id']) : 0; // Tambahkan ini
$categories = (new Database())->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub - Toko Buku Online Terpercaya #1 di Indonesia</title>
    <meta name="description" content="BookHub adalah toko buku online terpercaya dengan koleksi lengkap, harga terbaik, dan pengiriman cepat ke seluruh Indonesia">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Enhanced Book Card dengan Efek Hover */
        .book-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
        }
        
        /* Efek mengembung dan memuncul saat hover */
        .book-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            z-index: 10;
        }
        
        .book-image {
            position: relative;
            overflow: hidden;
        }
        
        /* Gambar buku membesar saat hover */
        .book-card:hover .book-image img {
            transform: scale(1.15);
        }
        
        .book-image img {
            transition: transform 0.5s ease;
        }
        
        /* Overlay gelap saat hover */
        .book-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .book-card:hover .book-overlay {
            opacity: 1;
        }
        
        .quick-view {
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 12px 20px;
            background: rgba(102, 126, 234, 0.9);
            border-radius: 5px;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .book-card:hover .quick-view {
            transform: translateY(0);
        }
        
        /* Badge NEW dengan animasi pulse */
        .book-card::before {
            content: 'NEW';
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            z-index: 1;
            opacity: 0;
            animation: pulse 2s infinite;
        }
        
        .book-card:nth-child(-n+3)::before {
            opacity: 1;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        /* Efek glow pada button saat card di-hover */
        .book-card:hover .btn-primary {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
            transform: scale(1.05);
        }
        
        .book-card .btn {
            transition: all 0.3s ease;
        }
        
        /* Info card naik sedikit saat hover */
        .book-card:hover .book-info {
            transform: translateY(-5px);
        }
        
        .book-info {
            transition: transform 0.3s ease;
        }
        
        /* Discount badge berputar saat hover */
        .discount-badge {
            transition: transform 0.5s ease;
        }
        
        .book-card:hover .discount-badge {
            transform: rotate(10deg) scale(1.1);
        }
        
        /* Rating stars berkilau saat hover */
        .book-card:hover .star.filled {
            animation: sparkle 1s ease-in-out infinite;
        }
        
        @keyframes sparkle {
            0%, 100% {
                filter: brightness(1);
            }
            50% {
                filter: brightness(1.5);
            }
        }
        
        /* Efek shine/cahaya bergerak */
        .book-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .book-card:hover::after {
            left: 100%;
        }

        /* Styling untuk Wishlist Button */
        .wishlist-btn {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            color: #ff4757;
            transform: scale(1.05);
        }

        /* About Section */
        .about-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            margin-bottom: 60px;
        }
        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        .about-content h2 {
            font-size: 36px;
            margin-bottom: 20px;
            animation: fadeInDown 1s;
        }
        .about-content p {
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        .about-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        .feature-card {
            background: rgba(255,255,255,0.15);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.2);
        }
        .feature-icon {
            font-size: 50px;
            margin-bottom: 15px;
            display: block;
        }
        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .feature-card p {
            font-size: 15px;
            opacity: 0.9;
        }

        /* Why Choose Us Section */
        .why-choose-section {
            background: #f8f9fa;
            padding: 60px 20px;
            margin-bottom: 60px;
        }
        .why-choose-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .why-choose-content h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 50px;
            color: #333;
        }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        .benefit-item {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .benefit-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }
        .benefit-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .benefit-item h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #667eea;
        }
        .benefit-item p {
            color: #666;
            line-height: 1.6;
        }

        /* Testimonials Section */
        .testimonials-section {
            padding: 60px 20px;
            background: white;
            margin-bottom: 60px;
        }
        .testimonials-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .testimonials-content h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 50px;
            color: #333;
        }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .testimonial-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            position: relative;
            border-left: 4px solid #667eea;
        }
        .testimonial-quote {
            font-size: 50px;
            color: #667eea;
            opacity: 0.3;
            position: absolute;
            top: 10px;
            left: 20px;
        }
        .testimonial-text {
            font-style: italic;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }
        .author-info h4 {
            margin: 0;
            color: #333;
        }
        .author-info p {
            margin: 0;
            font-size: 13px;
            color: #999;
        }
        .rating-stars {
            color: #ffc107;
            font-size: 14px;
            margin-top: 5px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            padding: 60px 20px;
            text-align: center;
            color: white;
            margin-bottom: 60px;
        }
        .cta-content h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        .cta-content p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        /* Animation */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .about-content h2 { font-size: 28px; }
            .about-content p { font-size: 16px; }
            .about-features { grid-template-columns: 1fr; }
            .benefits-grid { grid-template-columns: 1fr; }
            .testimonials-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <!-- Header & Navigation -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">📚 BookHub</a>
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Cari buku, penulis...">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php" class="cart-btn">🛒 Keranjang (<?= $cartCount ?>)</a>
                        <a href="wishlist.php" class="wishlist-btn">❤️ Wishlist</a>
                        <a href="orders.php">📦 Pesanan</a>
                        <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
                        <a href="logout.php">🚪 Logout</a>
                    <?php else: ?>
                        <a href="login.php">🔐 Login</a>
                        <a href="register.php">📝 Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="navbar">
                <a href="index.php" class="nav-item <?= !isset($_GET['category']) ? 'active' : '' ?>">📚 Semua Buku</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?= $cat['id'] ?>" 
                       class="nav-item <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Temukan Buku Favorit Anda di BookHub</h1>
            <p>Koleksi terlengkap buku dengan harga terbaik dan pengiriman cepat ke seluruh Indonesia</p>
            <div class="hero-stats">
                <div class="stat">
                    <strong><?= number_format($totalBooks) ?>+</strong>
                    <span>Buku Tersedia</span>
                </div>
                <div class="stat">
                    <strong>10K+</strong>
                    <span>Pembaca Puas</span>
                </div>
                <div class="stat">
                    <strong>24 Jam</strong>
                    <span>Pengiriman Cepat</span>
                </div>
                <div class="stat">
                    <strong>100%</strong>
                    <span>Original & Berkualitas</span>
                </div>
            </div>
        </div>
    </section>

    
    <!-- Why Choose Us Section -->
    <section class="why-choose-section">
        <div class="why-choose-content">
            <h2>🌟 Mengapa Memilih BookHub?</h2>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">🔒</div>
                    <h3>Transaksi Aman</h3>
                    <p>Sistem pembayaran terenkripsi dan terjamin keamanannya. Kami menjaga privasi dan keamanan data Anda.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">⚡</div>
                    <h3>Proses Cepat</h3>
                    <p>Checkout mudah dan cepat. Pesanan Anda akan diproses dalam hitungan menit setelah pembayaran dikonfirmasi.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📦</div>
                    <h3>Packaging Rapi</h3>
                    <p>Setiap buku dikemas dengan rapi dan aman menggunakan bubble wrap dan kardus berkualitas tinggi.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <h3>Rekomendasi Personal</h3>
                    <p>Dapatkan rekomendasi buku sesuai minat dan riwayat bacaan Anda dengan sistem AI kami.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">⭐</div>
                    <h3>Review & Rating</h3>
                    <p>Baca review jujur dari pembaca lain sebelum membeli untuk membantu Anda memilih buku yang tepat.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🔄</div>
                    <h3>Garansi Retur</h3>
                    <p>Tidak puas? Kami menerima retur dalam 7 hari jika buku rusak atau tidak sesuai pesanan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content - Book Catalog (DIPINDAHKAN KE ATAS) -->
    <main class="container" style="margin-top: 60px;">
        <section class="books-section">
            <h2 style="text-align: center; font-size: 32px; margin-bottom: 40px; color: #333;">
                <?= $categoryId ? '📚 Buku ' . $categories[array_search($categoryId, array_column($categories, 'id'))]['name'] : '📚 Buku Terbaru & Terpopuler' ?>
            </h2>
            <div class="books-grid">
                <?php foreach ($books as $b):
                    $finalPrice = $b['price'] * (100 - $b['discount_percent']) / 100;
                    $imagePath = !empty($b['image']) && file_exists(__DIR__ . '/uploads/books/' . $b['image']) 
                        ? 'uploads/books/' . $b['image'] 
                        : 'assets/images/default-book.jpg';
                ?>
                    <div class="book-card">
                        <div class="book-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($b['title']) ?>" class="book-cover">
                            <?php if ($b['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?= $b['discount_percent'] ?>%</span>
                            <?php endif; ?>
                            <div class="book-overlay">
                                <span class="quick-view">👁️ Lihat Detail</span>
                            </div>
                        </div>
                        <div class="book-info">
                            <h3><?= htmlspecialchars(substr($b['title'], 0, 30)) ?></h3>
                            <p class="author"><?= htmlspecialchars($b['author']) ?></p>
                            <div class="rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="star <?= $i < floor($b['rating']) ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                                <span class="rating-text"><?= number_format($b['rating'], 1) ?></span>
                            </div>
                            <div class="price">
                                <?php if ($b['discount_percent'] > 0): ?>
                                    <span class="original">Rp <?= number_format($b['price'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                                <span class="final">Rp <?= number_format($finalPrice, 0, ',', '.') ?></span>
                            </div>
                            <div class="stock">
                                <?php if ($b['stock'] > 0): ?>
                                    <span class="in-stock">Stok: <?= $b['stock'] ?></span>
                                <?php else: ?>
                                    <span class="out-stock">Stok Habis</span>
                                <?php endif; ?>
                            </div>
                            <div class="book-actions">
                                <a href="detail.php?id=<?= $b['id'] ?>" class="btn btn-info">Detail</a>
                                <?php if (isset($_SESSION['user_id']) && $b['stock'] > 0): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?= $b['id'] ?>)">Keranjang</button>
                                <?php elseif ($b['stock'] <= 0): ?>
                                    <button class="btn btn-disabled" disabled>Habis</button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php<?= $categoryId ? '?category='.$categoryId.'&' : '?' ?>page=1">&laquo; Awal</a>
                        <a href="index.php<?= $categoryId ? '?category='.$categoryId.'&' : '?' ?>page=<?= $page - 1 ?>">← Sebelumnya</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="index.php<?= $categoryId ? '?category='.$categoryId.'&' : '?' ?>page=<?= $i ?>" 
                           class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="index.php<?= $categoryId ? '?category='.$categoryId.'&' : '?' ?>page=<?= $page + 1 ?>">Berikutnya →</a>
                        <a href="index.php<?= $categoryId ? '?category='.$categoryId.'&' : '?' ?>page=<?= $totalPages ?>">Akhir &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-content">
            <h2>🎯 Tentang BookHub</h2>
            <p>
                BookHub adalah toko buku online terpercaya #1 di Indonesia yang menyediakan ribuan koleksi buku berkualitas
                dari berbagai kategori. Kami berkomitmen untuk menjadi mitra terbaik Anda dalam perjalanan membaca dan
                pembelajaran, dengan menyediakan buku-buku pilihan, harga kompetitif, dan layanan pelanggan yang excellence.
            </p>
            <p>
                Sejak didirikan, BookHub telah melayani lebih dari 10.000 pelanggan di seluruh Indonesia dengan kepuasan
                pelanggan sebagai prioritas utama. Kami percaya bahwa membaca adalah jendela dunia, dan kami hadir untuk
                memudahkan Anda menemukan buku impian dengan pengalaman belanja yang menyenangkan.
            </p>

            <div class="about-features">
                <div class="feature-card">
                    <span class="feature-icon">📚</span>
                    <h3>Koleksi Lengkap</h3>
                    <p>Ribuan judul buku dari berbagai genre: fiksi, non-fiksi, teknologi, self-help, anak-anak, dan biografi</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">💰</span>
                    <h3>Harga Terjangkau</h3>
                    <p>Dapatkan diskon hingga 50% dan promo menarik setiap minggu untuk buku favorit Anda</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">🚚</span>
                    <h3>Pengiriman Cepat</h3>
                    <p>Pengiriman ke seluruh Indonesia dalam 1-3 hari kerja dengan packaging yang aman</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">✅</span>
                    <h3>100% Original</h3>
                    <p>Semua buku dijamin asli dan berkualitas dari penerbit resmi terpercaya</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">🎁</span>
                    <h3>Program Loyalitas</h3>
                    <p>Kumpulkan poin dan dapatkan hadiah menarik setiap pembelian buku</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">💬</span>
                    <h3>Customer Support 24/7</h3>
                    <p>Tim customer service kami siap membantu Anda kapan saja via WhatsApp, Email & Live Chat</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="testimonials-content">
            <h2>💬 Apa Kata Mereka Tentang BookHub?</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-quote">"</div>
                    <p class="testimonial-text">
                        Pengalaman belanja yang luar biasa! Buku sampai tepat waktu dan dalam kondisi sempurna. 
                        Harganya juga lebih murah dibanding toko lain. Sangat puas!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">B</div>
                        <div class="author-info">
                            <h4>Budi Santoso</h4>
                            <p>Surabaya</p>
                            <div class="rating-stars">★★★★★</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-quote">"</div>
                    <p class="testimonial-text">
                        Packaging-nya rapih banget, buku ga ada yang rusak sedikitpun. Pengiriman juga cepat! 
                        Ini toko buku online terbaik yang pernah saya coba. Thank you BookHub!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">D</div>
                        <div class="author-info">
                            <h4>Dewi Lestari</h4>
                            <p>Bandung</p>
                            <div class="rating-stars">★★★★★</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>🎉 Siap Mulai Petualangan Membaca Anda?</h2>
            <p>Bergabunglah dengan ribuan pembaca lain dan dapatkan akses ke koleksi buku terlengkap!</p>
            <a href="<?= isset($_SESSION['user_id']) ? 'index.php#books' : 'register.php' ?>" class="cta-button">
                <?= isset($_SESSION['user_id']) ? 'Belanja Sekarang' : 'Daftar Gratis Sekarang' ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Tentang BookHub</h4>
                    <p>Toko buku online terpercaya #1 di Indonesia dengan koleksi lengkap, harga terbaik, dan pengiriman cepat ke seluruh nusantara.</p>
                    <br>
                    <p><strong>📍 Alamat:</strong> Jl. Pendidikan No. 123, Jakarta Selatan</p>
                    <p><strong>⏰ Jam Operasional:</strong> Senin - Sabtu, 09:00 - 21:00 WIB</p>
                </div>
                <div class="footer-col">
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="index.php">🏠 Beranda</a></li>
                        <li><a href="#about">ℹ️ Tentang Kami</a></li>
                        <li><a href="index.php#books">📚 Katalog Buku</a></li>
                        <li><a href="#contact">📞 Hubungi Kami</a></li>
                        <li><a href="#">❓ FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontak</h4>
                    <p>📧 Email: info@bookhub.com</p>
                    <p>📱 Telepon: +62 896-3089-2307</p>
                    <p>💬 WhatsApp: <a href="https://wa.me/6289630892307" target="_blank" style="color: #25D366;">+62 896-3089-2307</a></p>
                    <br>
                    <p><strong>Metode Pembayaran:</strong></p>
                    <p>💳 Transfer Bank • 💰 E-Wallet • 📦 COD</p>
                </div>
                <div class="footer-col">
                    <h4>Sosial Media</h4>
                    <div class="social-links">
                        <a href="https://wa.me/6289630892307" target="_blank" title="Chat via WhatsApp">💬 WhatsApp</a>
                        <a href="https://www.instagram.com/alvin_renn" target="_blank" title="Lihat Instagram">📸 Instagram</a>
                        <a href="https://github.com/alvinrendi" target="_blank" title="Lihat GitHub">💻 GitHub</a>
                    </div>
                    <br>
                    <p><strong>Newsletter:</strong></p>
                    <form style="display: flex; gap: 5px; margin-top: 10px;">
                        <input type="email" placeholder="Email Anda" style="flex: 1; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                        <button type="submit" style="padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Kirim</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 BookHub - Toko Buku Online Terpercaya #1 di Indonesia. Semua Hak Dilindungi.</p>
                <p style="margin-top: 10px; font-size: 13px;">
                    <a href="#" style="color: #999; margin: 0 10px;">Syarat & Ketentuan</a> | 
                    <a href="#" style="color: #999; margin: 0 10px;">Kebijakan Privasi</a> | 
                    <a href="#" style="color: #999; margin: 0 10px;">Kebijakan Pengembalian</a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Fungsi Add to Cart dengan notifikasi yang lebih baik
        function addToCart(bookId) {
            // Tampilkan loading
            const loadingMsg = document.createElement('div');
            loadingMsg.style.cssText = 'position:fixed;top:20px;right:20px;background:#667eea;color:white;padding:15px 25px;border-radius:10px;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,0.2);';
            loadingMsg.innerHTML = '⏳ Menambahkan ke keranjang...';
            document.body.appendChild(loadingMsg);

            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ book_id: bookId, quantity: 1 })
            })
            .then(r => r.json())
            .then(data => {
                // Hapus loading message
                document.body.removeChild(loadingMsg);

                if (data.success) {
                    // Tampilkan notifikasi sukses
                    showNotification('✅ Buku berhasil ditambahkan ke keranjang!', 'success');
                    
                    // Update cart count di header
                    updateCartCount();
                    
                    // Animasi pada tombol yang diklik
                    animateButton(bookId);
                } else {
                    showNotification('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                document.body.removeChild(loadingMsg);
                showNotification('❌ Terjadi kesalahan. Silakan coba lagi.', 'error');
                console.error('Error:', error);
            });
        }

        // Fungsi untuk menampilkan notifikasi custom
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 10px;
                z-index: 10000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                animation: slideIn 0.3s ease-out;
                font-weight: bold;
                max-width: 350px;
            `;
            
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #2ecc71, #27ae60)';
                notification.style.color = 'white';
            } else {
                notification.style.background = 'linear-gradient(135deg, #ff4757, #e74c3c)';
                notification.style.color = 'white';
            }
            
            notification.innerHTML = message;
            document.body.appendChild(notification);

            // Hapus setelah 3 detik
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Update cart count tanpa reload
        function updateCartCount() {
            fetch('api/get-cart-count.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const cartBtn = document.querySelector('.cart-btn');
                        if (cartBtn) {
                            cartBtn.innerHTML = `🛒 Keranjang (${data.count})`;
                            // Animasi bounce pada cart icon
                            cartBtn.style.animation = 'bounce 0.5s ease';
                            setTimeout(() => {
                                cartBtn.style.animation = '';
                            }, 500);
                        }
                    }
                });
        }

        // Update wishlist count tanpa reload
        function updateWishlistCount() {
            fetch('api/get-wishlist-count.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const wishlistBtn = document.querySelector('.wishlist-btn');
                        if (wishlistBtn) {
                            wishlistBtn.innerHTML = `❤️ Wishlist (${data.count})`;
                            // Animasi bounce pada wishlist icon
                            wishlistBtn.style.animation = 'bounce 0.5s ease';
                            setTimeout(() => {
                                wishlistBtn.style.animation = '';
                            }, 500);
                        }
                    }
                });
        }

        // Animasi pada button setelah add to cart
        function animateButton(bookId) {
            const buttons = document.querySelectorAll(`button[onclick="addToCart(${bookId})"]`);
            buttons.forEach(btn => {
                btn.style.transform = 'scale(0.9)';
                btn.innerHTML = '✓ Ditambahkan';
                btn.style.background = '#2ecc71';
                
                setTimeout(() => {
                    btn.style.transform = 'scale(1)';
                    setTimeout(() => {
                        btn.innerHTML = 'Keranjang';
                        btn.style.background = '';
                    }, 1000);
                }, 300);
            });
        }

        // Tambahkan CSS untuk animasi
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                25% { transform: scale(1.2); }
                50% { transform: scale(0.95); }
                75% { transform: scale(1.1); }
            }
        `;
        document.head.appendChild(style);

        // Smooth scroll untuk link anchor
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.book-card, .feature-card, .benefit-item, .testimonial-card').forEach(el => {
            observer.observe(el);
        });

        // Counter animation untuk stats
        const animateCounter = (element, target, duration = 2000) => {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target.toLocaleString('id-ID');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start).toLocaleString('id-ID');
                }
            }, 16);
        };

        // Trigger counter animation when hero is visible
        const heroObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const stats = document.querySelectorAll('.stat strong');
                    stats.forEach(stat => {
                        const text = stat.textContent;
                        const number = parseInt(text.replace(/\D/g, ''));
                        if (number) {
                            animateCounter(stat, number, 1500);
                        }
                    });
                    heroObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const heroSection = document.querySelector('.hero');
        if (heroSection) {
            heroObserver.observe(heroSection);
        }

        // Newsletter form
        const newsletterForm = document.querySelector('footer form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const email = newsletterForm.querySelector('input[type="email"]').value;
                if (email) {
                    showNotification('✅ Terima kasih! Anda akan mendapatkan update terbaru dari BookHub ke email: ' + email, 'success');
                    newsletterForm.reset();
                }
            });
        }

        // Quick view saat hover (opsional enhancement)
        document.querySelectorAll('.book-card').forEach(card => {
            const overlay = card.querySelector('.book-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target.classList.contains('quick-view')) {
                        const detailLink = card.querySelector('a[href^="detail.php"]');
                        if (detailLink) {
                            window.location.href = detailLink.href;
                        }
                    }
                });
            }
        });

        // Loading indicator global
        let isLoading = false;
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            if (args[0].includes('add-to-cart')) {
                isLoading = true;
            }
            return originalFetch.apply(this, arguments)
                .finally(() => {
                    isLoading = false;
                });
        };

        // Prevent double click
        document.querySelectorAll('button[onclick^="addToCart"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (isLoading) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
        });
    </script>
</body>
</html>
