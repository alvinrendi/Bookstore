<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Category.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$book = new Book();
$category = new Category();
$books = $book->getAllBooks(50, 0);
$categories = $category->getAllCategories();
$message = '';
$messageType = '';

// Fungsi helper untuk mendapatkan URL gambar
if (!function_exists('getBookImageUrl')) {
    function getBookImageUrl($imagePath) {
        if (empty($imagePath)) {
            return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22%3E%3Crect fill=%22%23e9ecef%22 width=%22200%22 height=%22280%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2240%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📚%3C/text%3E%3C/svg%3E';
        }
        
        $cleanPath = $imagePath;
        
        if (strpos($cleanPath, 'uploads/') === false && $cleanPath !== 'default.jpg') {
            $cleanPath = 'uploads/books/' . $cleanPath;
        }
        
        $fullPath = __DIR__ . '/../public/' . $cleanPath;
        if (file_exists($fullPath)) {
            return '../public/' . $cleanPath;
        }
        
        return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22%3E%3Crect fill=%22%23e9ecef%22 width=%22200%22 height=%22280%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2240%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📚%3C/text%3E%3C/svg%3E';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $isbn = trim($_POST['isbn'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $pages = (int)($_POST['pages'] ?? 0);
        $language = trim($_POST['language'] ?? 'Indonesia');
        $year = (int)($_POST['year'] ?? date('Y'));
        $image = 'default.jpg';
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($fileType, $allowedTypes)) {
                $message = 'Format gambar harus JPG, PNG, GIF, atau WebP';
                $messageType = 'error';
            } elseif ($fileSize > $maxSize) {
                $message = 'Ukuran gambar maksimal 5MB';
                $messageType = 'error';
            } else {
                $uploadDir = __DIR__ . '/../public/uploads/books/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    $htaccessContent = "<IfModule mod_rewrite.c>\n    RewriteEngine Off\n</IfModule>\n\n<FilesMatch \"\.(jpg|jpeg|png|gif|webp)$\">\n    Order allow,deny\n    Allow from all\n</FilesMatch>";
                    file_put_contents($uploadDir . '.htaccess', $htaccessContent);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = 'book_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $image = 'uploads/books/' . $fileName;
                    chmod($uploadPath, 0644);
                } else {
                    $message = 'Gagal mengupload gambar';
                    $messageType = 'error';
                }
            }
        }
        
        if (empty($message) && !empty($title) && !empty($author) && $price > 0) {
            if ($book->addBook($title, $author, $publisher, $categoryId, $isbn, $description, $price, $stock, $pages, $language, $year, $image)) {
                $message = 'Buku berhasil ditambahkan!';
                $messageType = 'success';
                $books = $book->getAllBooks(50, 0);
            } else {
                $message = 'Gagal menambahkan buku';
                $messageType = 'error';
            }
        } elseif (empty($message)) {
            $message = 'Judul, Penulis, dan Harga harus diisi';
            $messageType = 'error';
        }
    }
    elseif ($_POST['action'] === 'delete') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId > 0) {
            $bookInfo = $book->getBookById($bookId);
            if ($bookInfo && $bookInfo['image'] !== 'default.jpg' && strpos($bookInfo['image'], 'uploads/') !== false) {
                $imagePath = __DIR__ . '/../public/' . $bookInfo['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            if ($book->deleteBook($bookId)) {
                $message = 'Buku berhasil dihapus!';
                $messageType = 'success';
                $books = $book->getAllBooks(50, 0);
            } else {
                $message = 'Gagal menghapus buku';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Admin BookHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 30px 25px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .logo h2 {
            font-size: 22px;
            font-weight: 700;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            padding: 14px 25px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
            font-size: 15px;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 30px;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
            color: white;
        }
        
        .nav-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .nav-item.logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            color: rgba(255,255,255,0.8);
        }
        
        .main {
            grid-column: 2;
            margin-left: 280px;
            padding: 30px;
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        .topbar {
            background: white;
            padding: 25px 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease;
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
        
        .topbar h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .book-count {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .count-icon {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .message.success {
            background: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }
        
        .message.error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }

        .message::before {
            content: '';
            font-size: 20px;
        }

        .message.success::before { content: '✓'; }
        .message.error::before { content: '✗'; }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            animation: slideUp 0.5s ease backwards;
        }

        .section:nth-child(2) { animation-delay: 0.1s; }
        .section:nth-child(3) { animation-delay: 0.2s; }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section h2 {
            font-size: 22px;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .form-group label .required {
            color: #ff4757;
            margin-left: 3px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input:hover,
        .form-group textarea:hover,
        .form-group select:hover {
            border-color: #c0c0c0;
            background: white;
        }
        
        .form-group textarea {
            grid-column: 1 / -1;
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        .image-upload-group {
            grid-column: 1 / -1;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8edff 100%);
            border-radius: 15px;
            border: 2px dashed #667eea;
            text-align: center;
            transition: all 0.3s ease;
        }

        .image-upload-group:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, #f0f3ff 0%, #dde5ff 100%);
        }
        
        .image-upload-label {
            display: block;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .upload-area {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            transform: scale(1.02);
        }
        
        .preview-container {
            margin-bottom: 15px;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 280px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            display: none;
            margin: 0 auto;
            object-fit: cover;
        }

        .preview-image.show {
            display: block;
        }
        
        .upload-placeholder {
            width: 200px;
            height: 280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 12px;
            color: #999;
            font-size: 70px;
            transition: all 0.3s ease;
        }

        .upload-placeholder.hide {
            display: none;
        }

        .upload-area:hover .upload-placeholder {
            background: linear-gradient(135deg, #dee2e6 0%, #e9ecef 100%);
            transform: scale(1.05);
        }
        
        .upload-text {
            margin-top: 15px;
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
        }
        
        .upload-hint {
            margin-top: 8px;
            color: #999;
            font-size: 12px;
        }
        
        .file-input {
            display: none;
        }
        
        .btn-upload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-upload:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-upload:active {
            transform: translateY(-1px);
        }
        
        .btn-submit {
            grid-column: 1 / -1;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table thead {
            background: #f8f9fa;
        }
        
        .table th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table td {
            padding: 16px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .book-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            display: block;
        }

        .book-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .book-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .book-meta {
            font-size: 12px;
            color: #999;
        }

        .author-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            color: #555;
        }

        .price-tag {
            font-weight: 600;
            color: #2d3748;
            font-size: 15px;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .stock-badge.in-stock {
            background: #d1e7dd;
            color: #0f5132;
        }

        .stock-badge.low-stock {
            background: #fff3cd;
            color: #856404;
        }

        .stock-badge.out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .category-tag {
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            color: #555;
            display: inline-block;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }

            .topbar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .main {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">📚</div>
                    <h2>BookHub Admin</h2>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="books.php" class="nav-item active">
                    <span class="nav-icon">📖</span>
                    <span>Kelola Buku</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <span class="nav-icon">🏷️</span>
                    <span>Kategori</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Pesanan</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Users</span>
                </a>
                <a href="../public/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main">
            <div class="topbar">
                <h1>Kelola Buku</h1>
                <div class="book-count">
                    <div class="count-icon">📚</div>
                    <span>Total: <?= count($books) ?> Buku</span>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-header">
                    <h2>
                        <span>➕</span>
                        Tambah Buku Baru
                    </h2>
                </div>
                <form method="POST" enctype="multipart/form-data" class="form-grid" id="addBookForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="image-upload-group">
                        <label class="image-upload-label">📸 Upload Cover Buku</label>
                        <div class="upload-area" onclick="document.getElementById('bookImage').click()">
                            <div class="preview-container">
                                <img id="imagePreview" class="preview-image" alt="Preview">
                                <div id="uploadPlaceholder" class="upload-placeholder">📚</div>
                            </div>
                            <button type="button" class="btn-upload" onclick="event.stopPropagation(); document.getElementById('bookImage').click()">
                                <span>📤</span>
                                <span>Pilih Gambar</span>
                            </button>
                            <div class="upload-text" id="uploadText">Klik untuk upload cover buku</div>
                            <div class="upload-hint">Format: JPG, PNG, GIF, WebP (Max 5MB)</div>
                        </div>
                        <input type="file" 
                               id="bookImage" 
                               name="image" 
                               class="file-input"
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                               onchange="previewImage(event)">
                    </div>
                    
                    <div class="form-group">
                        <label>Judul Buku <span class="required">*</span></label>
                        <input type="text" name="title" placeholder="Masukkan judul buku" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Penulis <span class="required">*</span></label>
                        <input type="text" name="author" placeholder="Nama penulis" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Penerbit</label>
                        <input type="text" name="publisher" placeholder="Nama penerbit">
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori <span class="required">*</span></label>
                        <select name="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>ISBN</label>
                        <input type="text" name="isbn" placeholder="978-xxx-xxx-xxx-x">
                    </div>
                    
                    <div class="form-group">
                        <label>Harga <span class="required">*</span></label>
                        <input type="number" name="price" step="1000" placeholder="50000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stok <span class="required">*</span></label>
                        <input type="number" name="stock" placeholder="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Halaman</label>
                        <input type="number" name="pages" placeholder="200">
                    </div>
                    
                    <div class="form-group">
                        <label>Bahasa</label>
                        <input type="text" name="language" value="Indonesia" placeholder="Indonesia">
                    </div>
                    
                    <div class="form-group">
                        <label>Tahun Terbit</label>
                        <input type="number" name="year" value="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" placeholder="Tuliskan deskripsi singkat tentang buku ini..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <span>💾</span>
                        <span>Simpan Buku</span>
                    </button>
                </form>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>
                        <span>📚</span>
                        Daftar Buku
                    </h2>
                </div>
                
                <?php if (!empty($books)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Buku</th>
                                    <th>Penulis</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Kategori</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $b): ?>
                                    <tr>
                                        <td>
                                            <?php $imageUrl = getBookImageUrl($b['image']); ?>
                                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                 alt="<?= htmlspecialchars($b['title']) ?>"
                                                 class="book-image"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22%3E%3Crect fill=%22%23e9ecef%22 width=%22200%22 height=%22280%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2240%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📚%3C/text%3E%3C/svg%3E';">
                                        </td>
                                        <td>
                                            <div class="book-info">
                                                <div class="book-title">
                                                    <?= htmlspecialchars(substr($b['title'], 0, 40)) ?><?= strlen($b['title']) > 40 ? '...' : '' ?>
                                                </div>
                                                <div class="book-meta">
                                                    <?= $b['pages'] ?? 0 ?> hal • <?= $b['year'] ?? 'N/A' ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="author-badge">
                                                <span>✍️</span>
                                                <?= htmlspecialchars($b['author']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="price-tag">
                                                Rp <?= number_format($b['price'], 0, ',', '.') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $stock = $b['stock'];
                                            if ($stock > 10): 
                                            ?>
                                                <span class="stock-badge in-stock">
                                                    <span>✓</span> <?= $stock ?> unit
                                                </span>
                                            <?php elseif ($stock > 0): ?>
                                                <span class="stock-badge low-stock">
                                                    <span>⚠</span> <?= $stock ?> unit
                                                </span>
                                            <?php else: ?>
                                                <span class="stock-badge out-of-stock">
                                                    <span>✗</span> Habis
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="category-tag">
                                                <?= htmlspecialchars($b['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit-book.php?id=<?= $b['id'] ?>" class="btn btn-edit">
                                                    ✏️ Edit
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ Yakin ingin menghapus buku ini?\n\nJudul: <?= htmlspecialchars($b['title']) ?>')">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <p>Belum ada buku dalam sistem</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const uploadText = document.getElementById('uploadText');
        
        if (file) {
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar! Maksimal 5MB');
                event.target.value = '';
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP');
                event.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.add('show');
                placeholder.classList.add('hide');
                uploadText.textContent = '✓ Gambar berhasil dipilih: ' + file.name;
                uploadText.style.color = '#0f5132';
            };
            reader.readAsDataURL(file);
        }
    }

    const successAlert = document.querySelector('.message.success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            setTimeout(() => successAlert.remove(), 300);
        }, 3000);
    }

    const addBookForm = document.getElementById('addBookForm');
    if (addBookForm) {
        addBookForm.addEventListener('submit', function(e) {
            const title = this.querySelector('input[name="title"]').value.trim();
            const author = this.querySelector('input[name="author"]').value.trim();
            const price = parseFloat(this.querySelector('input[name="price"]').value);
            
            if (!title || !author) {
                e.preventDefault();
                alert('Judul dan Penulis harus diisi!');
                return false;
            }
            
            if (price <= 0) {
                e.preventDefault();
                alert('Harga harus lebih dari 0!');
                return false;
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span> Menyimpan...';
            
            const spinStyle = document.createElement('style');
            spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
            document.head.appendChild(spinStyle);
        });
    }
    </script>
</body>
</html>