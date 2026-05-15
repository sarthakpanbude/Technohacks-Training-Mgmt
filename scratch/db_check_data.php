<?php
$pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
$query = "SELECT (SELECT GROUP_CONCAT(CONCAT('Inst ', installment_no, ': ', amount, ' (', status, ' on ', due_date, ')') ORDER BY installment_no ASC SEPARATOR '; ') 
           FROM installments WHERE student_id = s.id) as installment_details
          FROM students s WHERE enrollment_no = 'E45410'";
$res = $pdo->query($query)->fetch();
echo "Installment Details: " . $res['installment_details'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM referral_bonuses");
echo "referral_bonuses count: " . $stmt->fetchColumn() . "\n";
?>
