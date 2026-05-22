<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id']) || !isset($_POST['type'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$id = $_POST['id'];
$type = $_POST['type']; // 'client' or 'beneficiary'

try {
    if ($type === 'client') {
        $stmt = $pdo->prepare("DELETE FROM client WHERE client_id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM beneficiary WHERE beneficiary_id = ?");
        $stmt->execute([$id]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
