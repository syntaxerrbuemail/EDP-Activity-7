<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit();
}

$personId = $_GET['id'];

try {
    // Fetch Person, Address, and Birthplace info
    $sql = "SELECT p.*, 
                   a.region, a.province as addr_province, a.municipality as addr_municipality, a.barangay, a.district,
                   bp.province as birth_province, bp.municipality as birth_municipality, bp.city as birth_city
            FROM person p
            JOIN address a ON a.address_id = p.address_id
            JOIN birthplace bp ON bp.birthplace_id = p.birthplace_id
            WHERE p.person_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personId]);
    $person = $stmt->fetch();

    if (!$person) {
        echo json_encode(['error' => 'Person not found']);
        exit();
    }

    // Check if Client
    $stmt = $pdo->prepare("SELECT * FROM client WHERE client_id = ?");
    $stmt->execute([$personId]);
    $client = $stmt->fetch();

    // Check if Beneficiary - Fetch sponsor's contact number as well
    $stmt = $pdo->prepare("SELECT b.*, 
                                  concat(cl_p.first_name, ' ', cl_p.last_name) as client_name,
                                  cl.contact_num as client_contact_num
                           FROM beneficiary b 
                           JOIN person cl_p ON cl_p.person_id = b.client_id
                           JOIN client cl ON cl.client_id = b.client_id
                           WHERE b.beneficiary_id = ?");
    $stmt->execute([$personId]);
    $beneficiary = $stmt->fetch();

    echo json_encode([
        'person' => $person,
        'client' => $client,
        'beneficiary' => $beneficiary
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
