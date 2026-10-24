<?php
require_once 'config.php';

$db = getDB();

// Check if admin already exists
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin1@gmail.com'");
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    // Update password to ensure it matches
    $hash = hashPassword('admin1');
    $db->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE email = 'admin1@gmail.com'")
       ->execute([$hash]);
    echo "Admin user updated: admin1@gmail.com / admin1\n";
} else {
    // Insert admin user
    $hash = hashPassword('admin1');
    $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)")
       ->execute(['admin', 'admin1@gmail.com', $hash]);
    echo "Admin user created: admin1@gmail.com / admin1\n";
}

// Verify
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin1@gmail.com'");
$stmt->execute();
$user = $stmt->fetch();
echo "Verified: id={$user['id']}, username={$user['username']}, is_admin={$user['is_admin']}\n";

// List all users
echo "\nAll users:\n";
$allUsers = $db->query("SELECT id, username, email, is_admin FROM users")->fetchAll();
foreach ($allUsers as $u) {
    echo "  id={$u['id']}, username={$u['username']}, email={$u['email']}, is_admin={$u['is_admin']}\n";
}
