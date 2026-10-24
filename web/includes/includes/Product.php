<?php
class Product {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($category = null) {
        if ($category && $category !== 'All') {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
            $stmt->execute([$category]);
        } else {
            $stmt = $this->db->query("SELECT * FROM products ORDER BY created_at DESC");
        }
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getCategories() {
        $stmt = $this->db->query("SELECT DISTINCT category FROM products ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price, image, stock, category) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            sanitize($data['name']),
            sanitize($data['description']),
            floatval($data['price']),
            sanitize($data['image'] ?? '📦'),
            intval($data['stock'] ?? 0),
            sanitize($data['category'] ?? 'General')
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE products SET name=?, description=?, price=?, image=?, stock=?, category=? WHERE id=?");
        return $stmt->execute([
            sanitize($data['name']),
            sanitize($data['description']),
            floatval($data['price']),
            sanitize($data['image'] ?? '📦'),
            intval($data['stock'] ?? 0),
            sanitize($data['category'] ?? 'General'),
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
