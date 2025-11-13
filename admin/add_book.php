<?php
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';

$book = new Book();

if (isset($_POST['save'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $imageName = null;

    // ✅ Upload gambar jika ada
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/books/";
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        // Pastikan folder ada
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Cek apakah file valid
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowed)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imageName = $fileName;
            } else {
                echo "<p style='color:red;'>Gagal upload gambar!</p>";
            }
        } else {
            echo "<p style='color:red;'>Tipe file tidak didukung!</p>";
        }
    }

    // ✅ Simpan ke database
    $book->addBook($title, $author, $price, $stock, $imageName);
    echo "<script>alert('Buku berhasil ditambahkan!'); window.location='books.php';</script>";
}
?>

<form action="add_book.php" method="POST" enctype="multipart/form-data">
    <label>Judul Buku</label>
    <input type="text" name="title" required>

    <label>Penulis</label>
    <input type="text" name="author" required>

    <label>Harga</label>
    <input type="number" name="price" required>

    <label>Stok</label>
    <input type="number" name="stock" required>

    <label>Gambar Buku</label>
    <input type="file" name="image" accept="image/*">

    <button type="submit" name="save">Simpan</button>
</form>
