<?php
// FILE: bookstore/classes/Cart.php

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tambahkan buku ke keranjang
    public function addToCart($userId, $bookId, $quantity = 1) {
        // Pastikan buku masih tersedia
        $check = $this->db->prepare("SELECT stock FROM books WHERE id = ?");
        $check->bind_param("i", $bookId);
        $check->execute();
        $book = $check->get_result()->fetch_assoc();

        if (!$book || $book['stock'] < $quantity) {
            return false; // stok habis
        }

        // Cek apakah buku sudah ada di keranjang
        $stmt = $this->db->prepare("SELECT id FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $userId, $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt = $this->db->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param("iii", $quantity, $userId, $bookId);
        } else {
            $stmt = $this->db->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $userId, $bookId, $quantity);
        }

        return $stmt->execute();
    }

    // Ambil semua item keranjang user
    public function getCartItems($userId) {
        $sql = "SELECT c.id as cart_id, c.quantity, 
                       b.title, b.price, b.image, b.author, b.stock,
                       (b.price * (100 - b.discount_percent) / 100) as final_price
                FROM cart c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Update jumlah item dalam keranjang
    public function updateQuantity($cartId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cartId);
        }
        $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $cartId);
        return $stmt->execute();
    }

    // Hapus item dari keranjang
    public function removeFromCart($cartId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartId);
        return $stmt->execute();
    }
    
    // Hapus semua item milik user
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    // Total harga keranjang
    public function getCartTotal($userId) {
        $sql = "SELECT SUM(c.quantity * b.price * (100 - b.discount_percent) / 100) as total 
                FROM cart c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }
    
    // Jumlah total item dalam keranjang
    public function getCartCount($userId) {
        $stmt = $this->db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }
}
?>
