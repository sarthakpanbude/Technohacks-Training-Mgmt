<?php
$pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
echo "Checking referral_bonus (singular)...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'referral_bonus'");
if ($stmt->fetch()) {
    echo "referral_bonus EXISTS\n";
} else {
    echo "referral_bonus DOES NOT EXIST\n";
}

echo "Checking referral_bonuses (plural)...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'referral_bonuses'");
if ($stmt->fetch()) {
    echo "referral_bonuses EXISTS\n";
} else {
    echo "referral_bonuses DOES NOT EXIST\n";
}
?>
