<?php
$pdo = new PDO('mysql:host=localhost;dbname=cams', 'root', '');
$stmt = $pdo->query('DESCRIBE beneficiary');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
