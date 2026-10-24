<?php
class Order {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create($customerData, $items, $total) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO orders (customer_name, customer_email, customer_address, total) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                sanitize($customerData['name']),
                sanitize($customerData['email']),
                sanitize($customerData['address']),
                $total
            ]);
            $orderId = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getAll() {
        $stmt = $this->db->query("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o 
            ORDER BY o.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o 
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if ($order) {
            $itemStmt = $this->db->prepare("
                SELECT oi.*, p.name as product_name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $itemStmt->execute([$id]);
            $order['items'] = $itemStmt->fetchAll();
        }
        return $order;
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([sanitize($status), $id]);
    }

    public function updateStripePaymentId($id, $paymentId) {
        $stmt = $this->db->prepare("UPDATE orders SET stripe_payment_id = ? WHERE id = ?");
        return $stmt->execute([$paymentId, $id]);
    }
}
