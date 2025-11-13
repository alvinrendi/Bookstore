<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';
require_once '../classes/Wishlist.php';

$categories = (new Category())->getAllCategories();

$q = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$book = new Book();
$cart = new Cart();

if ($q) {
    $books = $book->searchBooks($q, $limit, $offset);
} else {
    $books = [];
}

$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian: <?= htmlspecialchars($q) ?> - BookHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">📚 BookHub</a>
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Cari buku..." value="<?= htmlspecialchars($q) ?>">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php" class="cart-btn">🛒 Keranjang (<?= $cartCount ?>)</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 style="margin: 30px 0;">Hasil Pencarian untuk "<?= htmlspecialchars($q) ?>"</h1>
        
        <?php if (empty($books)): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                <p style="font-size: 18px; color: #666;">Tidak ada buku yang cocok dengan pencarian Anda</p>
                <a href="index.php" class="btn btn-primary" style="display: inline-block; margin-top: 20px; padding: 10px 30px;">Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($books as $b):
                    $finalPrice = $b['price'] * (100 - $b['discount_percent']) / 100;
                ?>
                    <div class="book-card">
                        <div class="book-image">
                            <img src="<?= UPLOAD_DIR . $b['image'] ?>" alt="<?= htmlspecialchars($b['title']) ?>">
                            <?php if ($b['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?= $b['discount_percent'] ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="book-info">
                            <h3><?= htmlspecialchars(substr($b['title'], 0, 30)) ?></h3>
                            <p class="author"><?= htmlspecialchars($b['author']) ?></p>
                            <div class="price">
                                <?php if ($b['discount_percent'] > 0): ?>
                                    <span class="original">Rp <?= number_format($b['price'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                                <span class="final">Rp <?= number_format($finalPrice, 0, ',', '.') ?></span>
                            </div>
                            <div class="book-actions">
                                <a href="detail.php?id=<?= $b['id'] ?>" class="btn btn-info">Detail</a>
                                <?php if (isset($_SESSION['user_id']) && $b['stock'] > 0): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?= $b['id'] ?>)">Keranjang</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(bookId) {
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({book_id: bookId, quantity: 1})
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    </script>
</body>
</html>