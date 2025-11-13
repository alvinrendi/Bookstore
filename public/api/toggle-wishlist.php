<?php
// FILE: public/api/toggle-wishlist.php

session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil input JSON dari JavaScript
$input = json_decode(file_get_contents('php://input'), true);

// Validasi JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Format JSON tidak valid.']);
    exit;
}

$book_id = intval($input['book_id'] ?? 0);

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
    exit;
}

try {
    // Buat koneksi database
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($db->connect_error) {
        throw new Exception('Koneksi database gagal: ' . $db->connect_error);
    }

    // Set charset
    $db->set_charset("utf8mb4");

    // Cek apakah buku sudah ada di wishlist
    $check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    
    if (!$check) {
        throw new Exception('Prepare statement gagal: ' . $db->error);
    }
    
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Hapus dari wishlist
        $check->close();
        
        $delete = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
        
        if (!$delete) {
            throw new Exception('Prepare delete gagal: ' . $db->error);
        }
        
        $delete->bind_param("ii", $user_id, $book_id);
        
        if ($delete->execute()) {
            $delete->close();
            $db->close();
            echo json_encode([
                'success' => true, 
                'action' => 'removed',
                'message' => 'Berhasil menghapus dari wishlist!'
            ]);
        } else {
            throw new Exception('Gagal menghapus dari wishlist: ' . $delete->error);
        }
    } else {
        // Tambahkan ke wishlist
        $check->close();
        
        $insert = $db->prepare("INSERT INTO wishlist (user_id, book_id, created_at) VALUES (?, ?, NOW())");
        
        if (!$insert) {
            throw new Exception('Prepare insert gagal: ' . $db->error);
        }
        
        $insert->bind_param("ii", $user_id, $book_id);
        
        if ($insert->execute()) {
            $insert->close();
            $db->close();
            echo json_encode([
                'success' => true, 
                'action' => 'added',
                'message' => 'Berhasil menambahkan ke wishlist!'
            ]);
        } else {
            throw new Exception('Gagal menambahkan ke wishlist: ' . $insert->error);
        }
    }

} catch (Exception $e) {
    error_log('Toggle wishlist error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    
    if (isset($db) && $db->ping()) {
        $db->close();
    }
}
exit;
?>