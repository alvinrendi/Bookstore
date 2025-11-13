<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Category.php';
require_once '../classes/Cart.php';

$categoryId = $_GET['id'] ?? 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$categoryObj = new Category();
$book = new Book();
$cart = new Cart();

$categoryData = $categoryObj->getCategoryById($categoryId);

if (!$categoryData) {
    header('Location: index.php');
    exit;
}

// 🔧 FIX: Gunakan koneksi database dengan benar
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$totalBooks = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalBooks / $limit);
$stmt->close();

$books = $book->getBooksByCategory($categoryId, $limit, $offset);
$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;
$categories = $categoryObj->getAllCategories();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoryData['name']) ?> - BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .category-header {
            background: white;
            padding: 40px 30px;
            margin: 30px 0;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .category-header p {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .category-header .book-count {
            color: #999;
            font-size: 14px;
            font-weight: 600;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .book-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
            animation: fadeIn 0.5s ease backwards;
        }

        .book-card:nth-child(1) { animation-delay: 0.1s; }
        .book-card:nth-child(2) { animation-delay: 0.2s; }
        .book-card:nth-child(3) { animation-delay: 0.3s; }
        .book-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }

        .book-image {
            position: relative;
            height: 320px;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-image::before {
            content: '📚';
            position: absolute;
            font-size: 60px;
            opacity: 0.2;
            z-index: 0;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
            position: relative;
            z-index: 1;
            background: white;
        }

        .book-card:hover .book-image img {
            transform: scale(1.1);
        }

        .discount-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.4);
            z-index: 10;
        }

        .book-info {
            padding: 20px;
        }

        .book-info h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #333;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 50px;
        }

        .author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 12px;
        }

        .star {
            color: #ddd;
            font-size: 16px;
        }

        .star.filled {
            color: #ffc107;
        }

        .rating-text {
            color: #666;
            font-size: 13px;
            margin-left: 6px;
            font-weight: 600;
        }

        .price {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .original {
            text-decoration: line-through;
            color: #aaa;
            font-size: 14px;
        }

        .final {
            color: #2ecc71;
            font-weight: 700;
            font-size: 20px;
        }

        .book-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 12px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
        }

        .btn-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        .btn:active {
            transform: translateY(0);
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .empty-state .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            font-size: 16px;
            color: #666;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 10px 16px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pagination a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .category-header {
                padding: 30px 20px;
            }

            .category-header h1 {
                font-size: 28px;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .book-image {
                height: 250px;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="category-header">
            <h1><?= htmlspecialchars($categoryData['name']) ?></h1>
            <p><?= htmlspecialchars($categoryData['description']) ?></p>
            <p class="book-count">📚 <?= $totalBooks ?> buku tersedia di kategori ini</p>
        </div>

        <div class="books-grid">
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h2>Tidak Ada Buku</h2>
                    <p>Belum ada buku yang tersedia di kategori ini</p>
                </div>
            <?php else: ?>
                <?php foreach ($books as $b):
                    $finalPrice = $b['price'] * (100 - $b['discount_percent']) / 100;
                    
                    // 🔧 FIX: Perbaikan path gambar
                    $imagePath = 'assets/images/default.jpg';
                    if (!empty($b['image'])) {
                        if (strpos($b['image'], 'uploads/') === 0 || strpos($b['image'], '../uploads/') === 0) {
                            $imagePath = $b['image'];
                        } else {
                            $imagePath = '../uploads/' . basename($b['image']);
                        }
                    }
                ?>
                    <div class="book-card">
                        <div class="book-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" 
                                 alt="<?= htmlspecialchars($b['title']) ?>"
                                 onerror="handleImageError(this)"
                                 loading="lazy">
                            <?php if ($b['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?= $b['discount_percent'] ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="book-info">
                            <h3><?= htmlspecialchars($b['title']) ?></h3>
                            <p class="author">✍️ <?= htmlspecialchars($b['author']) ?></p>
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
                            <div class="book-actions">
                                <a href="detail.php?id=<?= $b['id'] ?>" class="btn btn-info">👁️ Detail</a>
                                <?php if (isset($_SESSION['user_id']) && $b['stock'] > 0): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?= $b['id'] ?>)">🛒 Keranjang</button>
                                <?php elseif ($b['stock'] <= 0): ?>
                                    <button class="btn btn-disabled" disabled>❌ Habis</button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">🔐 Login</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="category.php?id=<?= $categoryId ?>&page=1">&laquo; Awal</a>
                    <a href="category.php?id=<?= $categoryId ?>&page=<?= $page - 1 ?>">← Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="category.php?id=<?= $categoryId ?>&page=<?= $i ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="category.php?id=<?= $categoryId ?>&page=<?= $page + 1 ?>">Berikutnya →</a>
                    <a href="category.php?id=<?= $categoryId ?>&page=<?= $totalPages ?>">Akhir &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Handle image loading errors
        function handleImageError(img) {
            const fallbacks = [
                '../uploads/default.jpg',
                'assets/images/default.jpg',
                'assets/images/no-image.jpg',
                '../assets/images/default.jpg',
                'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="280" height="320" viewBox="0 0 280 320"%3E%3Crect fill="%23f5f7fa" width="280" height="320"/%3E%3Ctext x="50%25" y="45%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="60" fill="%23ccc"%3E📚%3C/text%3E%3Ctext x="50%25" y="60%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="14" fill="%23999"%3EGambar Tidak Tersedia%3C/text%3E%3C/svg%3E'
            ];
            
            if (!img.dataset.fallbackIndex) {
                img.dataset.fallbackIndex = '0';
            }
            
            let currentIndex = parseInt(img.dataset.fallbackIndex);
            
            if (currentIndex < fallbacks.length) {
                img.dataset.fallbackIndex = (currentIndex + 1).toString();
                img.src = fallbacks[currentIndex];
            } else {
                img.style.display = 'none';
            }
        }

        function addToCart(bookId) {
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ book_id: bookId, quantity: 1 })
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(err => {
                alert('Terjadi kesalahan koneksi');
                console.error(err);
            });
        }
    </script>
</body>

</html>