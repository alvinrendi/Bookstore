<?php
if (!isset($cart)) {
    require_once '../classes/Cart.php';
    $cart = new Cart();
}
if (!isset($categories)) {
    require_once '../classes/Category.php';
    $categoryObj = new Category();
    $categories = $categoryObj->getAllCategories();
}

$cartCount = isset($_SESSION['user_id']) ? $cart->getCartCount($_SESSION['user_id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF']);
?>

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
                    <a href="wishlist.php" class="cart-btn">❤️ Wishlist</a>
                    <a href="orders.php">Pesanan</a>
                    <div class="dropdown">
                        <button class="dropbtn">👤 <?= htmlspecialchars(substr($_SESSION['username'], 0, 15)) ?></button>
                        <div class="dropdown-content">
                            <a href="profile.php">Profile</a>
                            <a href="orders.php">Pesanan Saya</a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <hr>
                                <a href="../admin/dashboard.php">🔧 Admin Panel</a>
                            <?php endif; ?>
                            <hr>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-register">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="navbar">
            <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">📚 Semua Buku</a>
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?id=<?= $cat['id'] ?>" class="nav-item" title="<?= htmlspecialchars($cat['name']) ?>">
                    <?php
                    $icons = [
                        'Fiksi' => '📖',
                        'Non-Fiksi' => '📚',
                        'Teknologi' => '💻',
                        'Self Help' => '🌟',
                        'Anak-anak' => '👶',
                        'Biografi' => '👤'
                    ];
                    $icon = $icons[$cat['name']] ?? '📕';
                    ?>
                    <?= $icon ?> <?= htmlspecialchars(substr($cat['name'], 0, 12)) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<style>
    .dropdown {
        position: relative;
        display: inline-block;
    }
    .dropbtn {
        background-color: transparent;
        color: white;
        padding: 8px 12px;
        font-size: 14px;
        border: none;
        cursor: pointer;
        border-radius: 5px;
        transition: background 0.3s;
    }
    .dropbtn:hover {
        background-color: rgba(255,255,255,0.2);
    }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 5px;
        top: 100%;
        margin-top: 5px;
    }
    .dropdown-content a {
        color: #333;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 14px;
        transition: background 0.2s;
    }
    .dropdown-content a:first-child {
        border-radius: 5px 5px 0 0;
    }
    .dropdown-content a:hover {
        background-color: #f0f0f0;
    }
    .dropdown-content hr {
        margin: 5px 0;
        border: none;
        border-top: 1px solid #eee;
    }
    .dropdown:hover .dropdown-content {
        display: block;
    }
    .btn-register {
        background: white !important;
        color: #667eea !important;
        font-weight: bold;
        padding: 8px 16px !important;
        border-radius: 5px;
    }
</style>

