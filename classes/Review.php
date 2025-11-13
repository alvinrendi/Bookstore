<?php
// FILE: bookstore/classes/Review.php

class Review {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Tambahkan review baru
     */
    public function addReview($bookId, $userId, $rating, $comment) {
        if ($rating < 1) $rating = 1;
        if ($rating > 5) $rating = 5;

        $stmt = $this->db->prepare("
            INSERT INTO reviews (book_id, user_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiis", $bookId, $userId, $rating, $comment);

        if ($stmt->execute()) {
            $this->updateBookRating($bookId);
            return true;
        } else {
            error_log("Gagal menambahkan review: " . $stmt->error);
            return false;
        }
    }

    /**
     * Ambil semua review berdasarkan ID buku
     */
    public function getBookReviews($bookId) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.full_name, u.profile_image 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update rata-rata rating buku
     */
    public function updateBookRating($bookId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE book_id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $avgRating = round($result['avg_rating'] ?? 0, 1);

        $stmt2 = $this->db->prepare("UPDATE books SET rating = ? WHERE id = ?");
        $stmt2->bind_param("di", $avgRating, $bookId);
        $stmt2->execute();
    }
}
?>
