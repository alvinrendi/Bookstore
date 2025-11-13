<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Wishlist.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    die('Silakan login terlebih dahulu');
}

$user_id = $_SESSION['user_id'];
$wishlist = new Wishlist();
$wishlistItems = $wishlist->getWishlist($user_id);

echo "<h1>Debug Path Gambar</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 12px; text-align: left; } th { background: #667eea; color: white; } .success { color: green; font-weight: bold; } .error { color: red; font-weight: bold; }</style>";

echo "<h2>Informasi Konfigurasi:</h2>";
echo "<table>";
echo "<tr><th>Constant</th><th>Value</th></tr>";
echo "<tr><td>UPLOAD_DIR</td><td>" . (defined('UPLOAD_DIR') ? UPLOAD_DIR : '<span class="error">NOT DEFINED</span>') . "</td></tr>";
echo "<tr><td>Current Directory</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>Parent Directory</td><td>" . dirname(__DIR__) . "</td></tr>";
echo "</table>";

echo "<h2>Data Wishlist Buku:</h2>";
if (empty($wishlistItems)) {
    echo "<p class='error'>Wishlist kosong. Tambahkan buku terlebih dahulu.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Judul</th><th>Image DB</th><th>Path Tests</th><th>Preview</th></tr>";
    
    foreach ($wishlistItems as $book) {
        echo "<tr>";
        echo "<td>" . $book['id'] . "</td>";
        echo "<td>" . htmlspecialchars($book['title']) . "</td>";
        echo "<td>" . htmlspecialchars($book['image']) . "</td>";
        echo "<td>";
        
        // Test berbagai path
        $paths_to_test = [
            'uploads/' . $book['image'],
            '../uploads/' . $book['image'],
            'assets/images/' . $book['image'],
            '../assets/images/' . $book['image'],
            $book['image'], // As is
            '../' . $book['image'],
            UPLOAD_DIR . $book['image'] ?? 'N/A'
        ];
        
        foreach ($paths_to_test as $path) {
            $fullPath = __DIR__ . '/' . $path;
            $exists = file_exists($fullPath);
            $color = $exists ? 'success' : 'error';
            $status = $exists ? '✓ EXISTS' : '✗ NOT FOUND';
            echo "<div class='$color'>$path → $status</div>";
        }
        
        echo "</td>";
        echo "<td>";
        
        // Coba tampilkan gambar dengan berbagai path
        foreach ($paths_to_test as $idx => $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                echo "<div><strong>Path yang berhasil:</strong></div>";
                echo "<img src='$path' style='max-width: 100px; border: 2px solid green;' alt='Test'>";
                echo "<div style='color: green; font-size: 11px;'>$path</div>";
                break;
            }
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Cek isi folder uploads
echo "<h2>Isi Folder Uploads:</h2>";
$upload_dirs = [
    __DIR__ . '/uploads/',
    __DIR__ . '/../uploads/',
    dirname(__DIR__) . '/uploads/',
    dirname(__DIR__) . '/public/uploads/'
];

foreach ($upload_dirs as $dir) {
    echo "<h3>Checking: $dir</h3>";
    if (is_dir($dir)) {
        echo "<p class='success'>✓ Directory EXISTS</p>";
        $files = scandir($dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $dir . $file;
                $size = filesize($filePath);
                echo "<li>$file (" . number_format($size) . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Directory NOT FOUND</p>";
    }
}

// Cek config.php
echo "<h2>Config.php Contents:</h2>";
$configFile = dirname(__DIR__) . '/config/config.php';
if (file_exists($configFile)) {
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars(file_get_contents($configFile));
    echo "</pre>";
} else {
    echo "<p class='error'>Config file not found at: $configFile</p>";
}
?>

<hr>
<h2>Quick Test Upload:</h2>
<p>Coba akses salah satu gambar ini di browser:</p>
<ul>
    <?php
    $test_paths = [
        'uploads/test.jpg',
        '../uploads/test.jpg',
        'assets/images/default.jpg'
    ];
    foreach ($test_paths as $path) {
        echo "<li><a href='$path' target='_blank'>$path</a></li>";
    }
    ?>
</ul>