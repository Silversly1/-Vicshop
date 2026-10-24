<?php
// Fix admin login - run this once at http://localhost:8000/fix-admin.php
// Then delete this file

require_once 'config.php';

$db = getDB();

// Ensure admin user exists with correct password
$hash = hashPassword('admin1');

// Check if admin exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin1@gmail.com'");
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    // Update password to ensure it matches
    $stmt = $db->prepare("UPDATE users SET password = ?, is_admin = 1, username = 'admin' WHERE email = 'admin1@gmail.com'");
    $stmt->execute([$hash]);
    echo "Admin user updated successfully.\n";
} else {
    // Insert admin user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->execute(['admin', 'admin1@gmail.com', $hash]);
    echo "Admin user created successfully.\n";
}

// Verify
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin1@gmail.com'");
$stmt->execute();
$user = $stmt->fetch();
echo "Verified: id={$user['id']}, username={$user['username']}, email={$user['email']}, is_admin={$user['is_admin']}\n";
echo "Password hash: {$user['password']}\n";

// Test password verify
echo "Password verify test: " . (password_verify('admin1', $user['password']) ? 'PASS' : 'FAIL') . "\n";

// List all users
echo "\nAll users:\n";
$allUsers = $db->query("SELECT id, username, email, is_admin FROM users")->fetchAll();
foreach ($allUsers as $u) {
    echo "  id={$u['id']}, username={$u['username']}, email={$u['email']}, is_admin={$u['is_admin']}\n";
}
