<?php
require_once dirname(__DIR__) . '/includes/db.php';
$db = new Database();
$pdo = $db->getConnection();

// 1. CLEAR DATABASE
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("DELETE FROM beneficiary;");
$pdo->exec("DELETE FROM client;");
$pdo->exec("DELETE FROM users;");
$pdo->exec("DELETE FROM person;");
$pdo->exec("DELETE FROM address;");
$pdo->exec("DELETE FROM birthplace;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Re-add default admin
$pdo->exec("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (8000, 'Region V', 'Albay', 'Legazpi', 'District 1', 'Barangay 1')");
$pdo->exec("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (7500, 'Albay', 'Legazpi', 'Legazpi')");
$pdo->exec("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) VALUES (9000, 'System', 'Admin', NULL, '1990-01-01', 7500, 'Single', 'Male', '0', 8000, 'Administrator')");
$pdo->exec("INSERT INTO users (user_id, person_id, username, email, password_hash, role, status) VALUES (7000, 9000, 'admin', 'admin@cams.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin', 'active')");

// 2. GENERATE 15 CLIENTS
$first_names = ['Juan', 'Maria', 'Pedro', 'Ana', 'Jose', 'Rosa', 'Carlo', 'Liza', 'Mark', 'Grace', 'Ramon', 'Elena', 'Diego', 'Sofia', 'Luis'];
$last_names = ['Dela Cruz', 'Reyes', 'Santos', 'Cruz', 'Ramos', 'Flores', 'Villanueva', 'Morales', 'Aquino', 'Pascual', 'Castillo', 'Bautista', 'Fernandez', 'Gomez', 'Hernandez'];
$occupations = ['Teacher', 'Driver', 'Farmer', 'Vendor', 'Nurse', 'Mechanic', 'Accountant', 'Engineer', 'Clerk', 'Artist', 'Tailor', 'Fisherman', 'Guard', 'Sales', 'Cook'];
$provinces = ['Albay', 'Camarines Sur', 'Sorsogon', 'Masbate', 'Catanduanes'];

for ($i = 0; $i < 15; $i++) {
    $p_id = 201 + $i;
    $b_id = 801 + $i;
    $a_id = 701 + $i;
    
    $fname = $first_names[$i];
    $lname = $last_names[$i];
    $occ = $occupations[$i];
    $prov = $provinces[$i % 5];
    $income = rand(5000, 25000);
    $bdate = rand(1970, 2000) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    
    $pdo->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)")->execute([$b_id, $prov, 'Municipality '.$i, 'City '.$i]);
    $pdo->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)")->execute([$a_id, 'Region V', $prov, 'Town '.$i, 'District '.rand(1,3), 'Barangay '.$i]);
    $pdo->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([$p_id, $fname, $lname, 'M', $bdate, $b_id, 'Married', ($i % 2 == 0 ? 'Male' : 'Female'), $income, $a_id, $occ]);
    $pdo->prepare("INSERT INTO client (client_id, religion, philhealt_num, contact_num, nationality) VALUES (?, ?, ?, ?, ?)")->execute([$p_id, 'Catholic', 'PH-'.rand(100,999).'-'.rand(1000,9999), '09'.rand(100000000, 999999999), 'Filipino']);
}

// 3. GENERATE 15 BENEFICIARIES
$b_first = ['Ken', 'Lea', 'Ben', 'Mia', 'Roy', 'Joy', 'Leo', 'Eva', 'Dan', 'Zoe', 'Tom', 'Ann', 'Jim', 'Sue', 'Sam'];
$categories = ['Health', 'Education', 'Financial', 'Livelihood'];
$statuses = ['Pending', 'Granted', 'Declined'];

for ($i = 0; $i < 15; $i++) {
    $p_id = 101 + $i;
    $b_id = 4101 + $i;
    $a_id = 3101 + $i;
    $client_id = 201 + $i; // Linking each beneficiary to a unique client for simplicity
    
    $fname = $b_first[$i];
    $lname = $last_names[($i + 5) % 15]; // Different last name than their linked client sometimes
    $prov = $provinces[rand(0, 4)];
    $income = rand(0, 5000);
    $bdate = rand(2005, 2020) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    
    $status = $statuses[$i % 3];
    $cat = $categories[$i % 4];
    $amount = ($status == 'Granted') ? rand(1000, 5000) : 0;
    
    $pdo->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)")->execute([$b_id, $prov, 'B-Municipality '.$i, 'B-City '.$i]);
    $pdo->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)")->execute([$a_id, 'Region V', $prov, 'B-Town '.$i, 'District '.rand(1,3), 'B-Barangay '.$i]);
    $pdo->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([$p_id, $fname, $lname, 'B', $bdate, $b_id, 'Single', ($i % 2 == 0 ? 'Female' : 'Male'), $income, $a_id, 'None/Student']);
    $pdo->prepare("INSERT INTO beneficiary (beneficiary_id, client_id, client_relationship, category, subcategory, aid_description, aid_status, aid_amount, status_remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([$p_id, $client_id, 'Relative', $cat, 'General Assistance', 'Needs assistance for ' . strtolower($cat), $status, $amount, ($status == 'Declined' ? 'Insufficient documents' : '')]);
}

echo "Success: 15 Clients and 15 Beneficiaries generated with accurate ID formats.";
?>
