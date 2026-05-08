<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

$id = $_GET['id'] ?? null;
$source = $_GET['source'] ?? 'inquiry';

if ($id) {
    try {
        if ($source == 'visitor') {
            $stmt = $pdo->prepare("UPDATE visitors SET status = 'rejected' WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = 'deleted' WHERE id = ?");
        }
        $stmt->execute([$id]);
        header("Location: ../inquiries.php?msg=Inquiry soft-deleted");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../inquiries.php");
    exit;
}
?>
