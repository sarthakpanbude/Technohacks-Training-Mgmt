<?php
require_once '../config/db.php';

echo "Checking admin user...\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin' AND role = 'admin'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    echo "Admin user found.\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Password Hash: " . $user['password'] . "\n";
    
    if (password_verify('admin123', $user['password'])) {
        echo "Password 'admin123' is CORRECT for this hash.\n";
    } else {
        echo "Password 'admin123' is INCORRECT for this hash.\n";
    }
} else {
    echo "Admin user NOT found.\n";
}

echo "\nAll users:\n";
$stmt = $pdo->query("SELECT id, username, role FROM users");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | Username: {$row['username']} | Role: {$row['role']}\n";
}
?>
