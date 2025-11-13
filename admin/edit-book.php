<?php
// admin/edit-book.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Category.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$bookObj = new Book();
$categoryObj = new Category();
$categories = $categoryObj->getAllCategories();

$error = '';
$success = '';

// Get book ID from URL
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookId === 0) {
    $_SESSION['error'] = 'ID buku tidak valid';
    header('Location: books.php');
    exit;
}

// Get book data
$book = $bookObj->getBookById($bookId);

if (!$book) {
    $_SESSION['error'] = 'Buku tidak ditemukan';
    header('Location: books.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $discount = intval($_POST['discount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = !empty($_POST['year']) ? intval($_POST['year']) : null;
    $pages = !empty($_POST['pages']) ? intval($_POST['pages']) : null;
    $language = trim($_POST['language'] ?? 'Indonesia');
    
    // Keep existing image by default
    $imagePath = $book['image'];
    
    // Validasi
    $errors = [];
    
    if (empty($title)) $errors[] = 'Judul buku harus diisi';
    if (empty($author)) $errors[] = 'Nama penulis harus diisi';
    if ($categoryId === 0) $errors[] = 'Kategori harus dipilih';
    if ($price <= 0) $errors[] = 'Harga harus lebih dari 0';
    if ($stock < 0) $errors[] = 'Stok tidak boleh negatif';
    if ($discount < 0 || $discount > 100) $errors[] = 'Diskon harus antara 0-100%';
    if (empty($description)) $errors[] = 'Deskripsi buku harus diisi';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Format gambar harus JPG, PNG, GIF, atau WebP';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'Ukuran gambar maksimal 5MB';
        } else {
            $uploadDir = __DIR__ . '/../public/uploads/books/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = 'book_' . $bookId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                // Delete old image
                if (!empty($book['image'])) {
                    $oldImagePath = __DIR__ . '/../public/' . $book['image'];
                    if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imagePath = 'uploads/books/' . $fileName;
            } else {
                $errors[] = 'Gagal mengupload gambar';
            }
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        try {
            $db = new Database();
            
            $sql = "UPDATE books SET 
                    title = ?, 
                    author = ?, 
                    category_id = ?, 
                    price = ?, 
                    discount = ?,
                    stock = ?, 
                    description = ?,
                    image = ?,
                    isbn = ?,
                    publisher = ?,
                    publication_year = ?,
                    pages = ?,
                    language = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database error: " . $db->error);
            }
            
            $stmt->bind_param(
                "ssididssssiisi",
                $title,
                $author,
                $categoryId,
                $price,
                $discount,
                $stock,
                $description,
                $imagePath,
                $isbn,
                $publisher,
                $year,
                $pages,
                $language,
                $bookId
            );
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Buku berhasil diperbarui!';
                header('Location: books.php');
                exit;
            } else {
                throw new Exception("Gagal mengupdate: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get image path
$imagePath = '';
if (!empty($book['image'])) {
    $possiblePaths = [
        '../public/' . $book['image'],
        '../public/uploads/books/' . basename($book['image']),
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            $imagePath = $path;
            break;
        }
    }
    
    if (empty($imagePath)) {
        $imagePath = '../public/uploads/books/' . basename($book['image']);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - BookHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            padding: 30px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        /* TABLE FORM */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .form-table tr {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .form-table td {
            padding: 15px 10px;
            vertical-align: top;
        }
        
        .form-table td:first-child {
            width: 35%;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-table td:last-child {
            width: 65%;
        }
        
        .required {
            color: #ff6b6b;
            margin-left: 3px;
        }
        
        .form-table input[type="text"],
        .form-table input[type="number"],
        .form-table select,
        .form-table textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .form-table input:focus,
        .form-table select:focus,
        .form-table textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .form-table textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }
        
        .helper-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
            font-weight: 500;
        }
        
        /* IMAGE UPLOAD SECTION */
        .image-upload-section {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8edff 100%);
            border-radius: 15px;
            border: 2px dashed #667eea;
        }
        
        .image-preview-container {
            margin-bottom: 15px;
        }
        
        .current-image {
            max-width: 250px;
            max-height: 350px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            margin-bottom: 10px;
        }
        
        .no-image {
            width: 250px;
            height: 350px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 12px;
            color: #999;
            font-size: 80px;
            margin-bottom: 10px;
        }
        
        .upload-input {
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
        
        .image-info {
            margin-top: 10px;
            font-size: 13px;
            color: #667eea;
            font-weight: 600;
        }
        
        /* FORM ACTIONS */
        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding-top: 20px;
            border-top: 3px solid #f0f0f0;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            color: #666;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #e0e0e0 0%, #d0d0d0 100%);
            transform: translateY(-3px);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 25px 20px;
            }
            
            .form-table td {
                display: block;
                width: 100%;
                padding: 10px 0;
            }
            
            .form-table td:first-child {
                margin-bottom: 8px;
            }
            
            .form-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="books.php" class="back-btn">
            <span>←</span>
            <span>Kembali ke Daftar Buku</span>
        </a>
        
        <div class="header">
            <h1>✏️ Edit Buku</h1>
            <p>Perbarui informasi buku dengan mudah</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="editBookForm">
            <table class="form-table">
                <tr>
                    <td colspan="2">
                        <div class="image-upload-section">
                            <div class="image-preview-container">
                                <?php if (!empty($imagePath) && file_exists(__DIR__ . '/' . $imagePath)): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         alt="<?= htmlspecialchars($book['title']) ?>" 
                                         class="current-image"
                                         id="imagePreview">
                                <?php else: ?>
                                    <div class="no-image" id="imagePreview">📚</div>
                                <?php endif; ?>
                            </div>
                            
                            <input type="file" 
                                   id="imageInput" 
                                   name="image" 
                                   class="upload-input"
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                   onchange="previewImage(event)">
                            
                            <button type="button" class="btn-upload" onclick="document.getElementById('imageInput').click()">
                                <span>📤</span>
                                <span>Pilih Gambar Baru</span>
                            </button>
                            
                            <div class="image-info" id="imageInfo">
                                💡 Klik untuk mengubah cover buku (Max 5MB)
                            </div>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <td>Judul Buku <span class="required">*</span></td>
                    <td>
                        <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <td>Penulis <span class="required">*</span></td>
                    <td>
                        <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <td>Kategori <span class="required">*</span></td>
                    <td>
                        <select name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $book['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td>ISBN</td>
                    <td>
                        <input type="text" name="isbn" value="<?= htmlspecialchars($book['isbn'] ?? '') ?>">
                        <div class="helper-text">Format: 978-xxx-xxx-xxx-x</div>
                    </td>
                </tr>
                
                <tr>
                    <td>Harga (Rp) <span class="required">*</span></td>
                    <td>
                        <input type="number" name="price" value="<?= $book['price'] ?>" step="1000" min="1000" required>
                    </td>
                </tr>
                
                <tr>
                    <td>Diskon (%)</td>
                    <td>
                        <input type="number" name="discount" value="<?= $book['discount'] ?>" min="0" max="100">
                        <div class="helper-text">0-100%</div>
                    </td>
                </tr>
                
                <tr>
                    <td>Stok <span class="required">*</span></td>
                    <td>
                        <input type="number" name="stock" value="<?= $book['stock'] ?>" min="0" required>
                    </td>
                </tr>
                
                <tr>
                    <td>Penerbit</td>
                    <td>
                        <input type="text" name="publisher" value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
                    </td>
                </tr>
                
                <tr>
                    <td>Tahun Terbit</td>
                    <td>
                        <input type="number" name="year" value="<?= $book['publication_year'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
                    </td>
                </tr>
                
                <tr>
                    <td>Jumlah Halaman</td>
                    <td>
                        <input type="number" name="pages" value="<?= $book['pages'] ?? '' ?>" min="1">
                    </td>
                </tr>
                
                <tr>
                    <td>Bahasa</td>
                    <td>
                        <select name="language">
                            <option value="Indonesia" <?= ($book['language'] ?? 'Indonesia') === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                            <option value="English" <?= ($book['language'] ?? '') === 'English' ? 'selected' : '' ?>>English</option>
                            <option value="Mandarin" <?= ($book['language'] ?? '') === 'Mandarin' ? 'selected' : '' ?>>Mandarin</option>
                            <option value="Jepang" <?= ($book['language'] ?? '') === 'Jepang' ? 'selected' : '' ?>>Jepang</option>
                            <option value="Korea" <?= ($book['language'] ?? '') === 'Korea' ? 'selected' : '' ?>>Korea</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td>Deskripsi <span class="required">*</span></td>
                    <td>
                        <textarea name="description" required><?= htmlspecialchars($book['description']) ?></textarea>
                    </td>
                </tr>
            </table>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="if(confirm('Yakin ingin membatalkan?')) window.location.href='books.php'">
                    <span>✕</span>
                    <span>Batal</span>
                </button>
                <button type="submit" class="btn btn-primary">
                    <span>💾</span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            const imageInfo = document.getElementById('imageInfo');
            
            if (file) {
                // Validate size
                if (file.size > 5 * 1024 * 1024) {
                    alert('❌ Ukuran file terlalu besar! Maksimal 5MB');
                    event.target.value = '';
                    return;
                }
                
                // Validate type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('❌ Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update preview
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.outerHTML = '<img src="' + e.target.result + '" class="current-image" id="imagePreview" alt="Preview">';
                    }
                    
                    // Update info
                    const fileSize = (file.size / 1024).toFixed(2);
                    imageInfo.innerHTML = '✅ Gambar dipilih: <strong>' + file.name + '</strong> (' + fileSize + ' KB)';
                    imageInfo.style.color = '#2ecc71';
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Form validation
        document.getElementById('editBookForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const author = document.querySelector('input[name="author"]').value.trim();
            const category = document.querySelector('select[name="category_id"]').value;
            const price = parseFloat(document.querySelector('input[name="price"]').value);
            const stock = parseInt(document.querySelector('input[name="stock"]').value);
            
            if (!title || !author || !category) {
                e.preventDefault();
                alert('❌ Judul, Penulis, dan Kategori harus diisi!');
                return false;
            }
            
            if (price <= 0) {
                e.preventDefault();
                alert('❌ Harga harus lebih dari 0!');
                return false;
            }
            
            if (stock < 0) {
                e.preventDefault();
                alert('❌ Stok tidak boleh negatif!');
                return false;
            }
            
            return confirm('✅ Yakin ingin menyimpan perubahan?');
        });
    </script>
</body>
</html>