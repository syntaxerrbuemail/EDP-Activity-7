<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE beneficiary ADD COLUMN aid_description TEXT");
    echo "Column added successfully.\n";
    $pdo->exec("UPDATE beneficiary SET aid_description = 'Emergency financial assistance for immediate needs.' WHERE aid_description IS NULL OR aid_description = ''");
    echo "Existing data updated successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
