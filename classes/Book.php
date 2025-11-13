<?php
// FILE: bookstore/classes/Book.php

class Book
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ✅ Ambil semua buku (bisa untuk halaman utama)
    public function getAllBooks($limit = 12, $offset = 0)
    {
        $sql = "SELECT b.*, c.name AS category_name 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ✅ Ambil buku berdasarkan kategori
    public function getBooksByCategory($categoryId, $limit = 12, $offset = 0)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, c.name AS category_name 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE b.category_id = ? 
            ORDER BY b.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $categoryId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ✅ Hitung total buku (semua)
    public function getTotalBooks()
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM books");
        return $result->fetch_assoc()['total'] ?? 0;
    }

    // ✅ Hitung total buku berdasarkan kategori
    public function getTotalBooksByCategory($categoryId)
    {
        $sql = "SELECT COUNT(*) AS total FROM books WHERE category_id = ?";
        $result = $this->db->fetch($sql, [$categoryId], "i");
        return $result['total'] ?? 0;
    }

    // ✅ Ambil buku berdasarkan ID
    public function getBookById($id)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, c.name AS category_name 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ✅ Pencarian buku
    public function searchBooks($keyword, $limit = 12, $offset = 0)
    {
        $search = "%$keyword%";
        $sql = "SELECT b.*, c.name AS category_name 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ? 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssii", $search, $search, $search, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ✅ Tambah buku baru
    public function addBook($title, $author, $publisher, $categoryId, $isbn, $description, $price, $stock, $pages, $language, $year, $image)
    {
        $sql = "INSERT INTO books 
                (title, author, publisher, category_id, isbn, description, price, stock, pages, language, publication_year, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssissdiisis",
            $title,
            $author,
            $publisher,
            $categoryId,
            $isbn,
            $description,
            $price,
            $stock,
            $pages,
            $language,
            $year,
            $image
        );
        return $stmt->execute();
    }

    // ✅ Update buku
    public function updateBook($id, $title, $author, $publisher, $categoryId, $isbn, $description, $price, $stock, $pages, $language, $year, $image = null)
    {
        if ($image) {
            $sql = "UPDATE books 
                    SET title=?, author=?, publisher=?, category_id=?, isbn=?, description=?, price=?, stock=?, pages=?, language=?, publication_year=?, image=? 
                    WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "sssissdiisssi",
                $title,
                $author,
                $publisher,
                $categoryId,
                $isbn,
                $description,
                $price,
                $stock,
                $pages,
                $language,
                $year,
                $image,
                $id
            );
        } else {
            $sql = "UPDATE books 
                    SET title=?, author=?, publisher=?, category_id=?, isbn=?, description=?, price=?, stock=?, pages=?, language=?, publication_year=? 
                    WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "sssissdiissi",
                $title,
                $author,
                $publisher,
                $categoryId,
                $isbn,
                $description,
                $price,
                $stock,
                $pages,
                $language,
                $year,
                $id
            );
        }
        return $stmt->execute();
    }

    // ✅ Hapus buku
    public function deleteBook($id)
    {
        $stmt = $this->db->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>