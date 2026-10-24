<?php
class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function register($data) {
        $username = sanitize($data['username']);
        $email = sanitize($data['email']);
        $password = hashPassword($data['password']);

        $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $email, $password]);
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && verifyPassword($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
