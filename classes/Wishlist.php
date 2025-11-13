<?php
// FILE: classes/Wishlist.php

class Wishlist {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    // Tambah buku ke wishlist
    public function addToWishlist($userId, $bookId) {
        try {
            // Cek apakah sudah ada
            if ($this->isInWishlist($userId, $bookId)) {
                return true; // sudah ada, return success agar tidak error
            }

            $stmt = $this->db->prepare("
                INSERT INTO wishlist (user_id, book_id, added_at)
                VALUES (?, ?, NOW())
            ");
            
            if (!$stmt) {
                error_log("Wishlist addToWishlist - prepare failed: " . $this->db->error());
                return false;
            }
            
            $stmt->bind_param("ii", $userId, $bookId);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Wishlist addToWishlist exception: " . $e->getMessage());
            return false;
        }
    }

    // Hapus buku dari wishlist
    public function removeFromWishlist($userId, $bookId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM wishlist WHERE user_id = ? AND book_id = ?
            ");
            
            if (!$stmt) {
                error_log("Wishlist removeFromWishlist - prepare failed: " . $this->db->error());
                return false;
            }
            
            $stmt->bind_param("ii", $userId, $bookId);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Wishlist removeFromWishlist exception: " . $e->getMessage());
            return false;
        }
    }

    // Ambil semua wishlist milik user
    public function getWishlist($userId) {
        try {
            $sql = "
                SELECT b.*, c.name AS category_name, w.added_at
                FROM wishlist w
                JOIN books b ON w.book_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE w.user_id = ?
                ORDER BY w.added_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Wishlist getWishlist - prepare failed: " . $this->db->error());
                return [];
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Wishlist getWishlist exception: " . $e->getMessage());
            return [];
        }
    }

    // Cek apakah buku sudah ada di wishlist user
    public function isInWishlist($userId, $bookId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?
            ");
            
            if (!$stmt) {
                error_log("Wishlist isInWishlist - prepare failed: " . $this->db->error());
                return false;
            }
            
            $stmt->bind_param("ii", $userId, $bookId);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            return $exists;
        } catch (Exception $e) {
            error_log("Wishlist isInWishlist exception: " . $e->getMessage());
            return false;
        }
    }

    // Hitung total wishlist user
    public function getWishlistCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS count FROM wishlist WHERE user_id = ?
            ");
            
            if (!$stmt) {
                error_log("Wishlist getWishlistCount - prepare failed: " . $this->db->error());
                return 0;
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Wishlist getWishlistCount exception: " . $e->getMessage());
            return 0;
        }
    }

    
    
    // Method tambahan: Clear seluruh wishlist user
    public function clearWishlist($userId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM wishlist WHERE user_id = ?");
            
            if (!$stmt) {
                error_log("Wishlist clearWishlist - prepare failed: " . $this->db->error());
                return false;
            }
            
            $stmt->bind_param("i", $userId);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Wishlist clearWishlist exception: " . $e->getMessage());
            return false;
        }
    }
}
?>
    