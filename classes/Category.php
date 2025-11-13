<?php
class Category {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllCategories() {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCategoryById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function addCategory($name, $description, $image = null) {
        $stmt = $this->db->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $image);
        return $stmt->execute();
    }
    
    public function updateCategory($id, $name, $description, $image = null) {
        if ($image) {
            $stmt = $this->db->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $description, $image, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
        }
        return $stmt->execute();
    }
    
    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getCategoryBookCount($categoryId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
}
?>

