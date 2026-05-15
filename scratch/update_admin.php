<?php
require_once '../config/db.php';

$hash = '$2y$10$8OvyBlTl1Hag1oRHwHE.k.8.Zoj28RUErFbdVXTcSOhfW9q.3ONH2';
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
if ($stmt->execute([$hash])) {
    echo "Admin password updated successfully.\n";
} else {
    echo "Failed to update admin password.\n";
}
?>
