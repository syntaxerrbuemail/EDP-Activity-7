<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Fetch list of clients for the dropdown - Querying tables directly as per schema rules
$clients = [];
try {
    $stmtClients = $pdo->query("SELECT c.client_id, p.first_name, p.last_name 
                               FROM client c 
                               JOIN person p ON c.client_id = p.person_id 
                               ORDER BY p.last_name ASC");
    $clients = $stmtClients->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load clients: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Beneficiary Specific
    $client_id = $_POST['client_id'];
    $relationship = trim($_POST['relationship']);
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    $aid_description = trim($_POST['aid_description']);
    
    // Personal Info
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name']);
    $birthdate = $_POST['birthdate'];
    $sex = $_POST['sex'];
    $civil_status = $_POST['civil_status'];
    $occupation = trim($_POST['occupation']);
    $income = trim($_POST['income']);
    
    // Birthplace Info
    $bp_province = trim($_POST['bp_province']);
    $bp_municipality = trim($_POST['bp_municipality']);
    $bp_city = trim($_POST['bp_city']);
    
    // Address Info
    $addr_region = trim($_POST['addr_region']);
    $addr_province = trim($_POST['addr_province']);
    $addr_municipality = trim($_POST['addr_municipality']);
    $addr_district = trim($_POST['addr_district']);
    $addr_barangay = trim($_POST['addr_barangay']);

    try {
        $pdo->beginTransaction();

        // 1. Generate Birthplace ID (Target: 4101+)
        $stmtMaxBP = $pdo->query("SELECT MAX(birthplace_id) FROM birthplace WHERE birthplace_id < 7500 AND birthplace_id >= 4101");
        $maxBpId = $stmtMaxBP->fetchColumn();
        $birthplace_id = ($maxBpId) ? $maxBpId + 1 : 4101;

        // 2. Insert Birthplace
        $stmtBP = $pdo->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)");
        $stmtBP->execute([$birthplace_id, $bp_province, $bp_municipality, $bp_city]);

        // 3. Generate Address ID (Target: 3101+)
        $stmtMaxAddr = $pdo->query("SELECT MAX(address_id) FROM address WHERE address_id < 8000 AND address_id >= 3101");
        $maxAddrId = $stmtMaxAddr->fetchColumn();
        $address_id = ($maxAddrId) ? $maxAddrId + 1 : 3101;

        // 4. Insert Address
        $stmtAddr = $pdo->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAddr->execute([$address_id, $addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay]);

        // 5. Generate Person ID (Target: 101-200)
        $stmtMax = $pdo->query("SELECT MAX(person_id) FROM person WHERE person_id < 201 AND person_id >= 101");
        $maxId = $stmtMax->fetchColumn();
        $person_id = ($maxId) ? $maxId + 1 : 101;

        // 6. Insert Person
        $stmtPerson = $pdo->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtPerson->execute([$person_id, $first_name, $last_name, $middle_name, $birthdate, $birthplace_id, $civil_status, $sex, $income, $address_id, $occupation]);

        // 7. Insert Beneficiary
        $stmtBen = $pdo->prepare("INSERT INTO beneficiary (beneficiary_id, client_id, client_relationship, category, subcategory, aid_description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtBen->execute([$person_id, $client_id, $relationship, $category, $subcategory, $aid_description]);

        $pdo->commit();
        $success = "Beneficiary successfully registered and linked to client!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to add beneficiary: " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <?php render_sidebar('add_beneficiary'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Register New Beneficiary</h1>
                <p style="color: var(--text-secondary);">Add a person as a beneficiary of an existing client.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <script>window.onload = () => showToast("<?php echo $error; ?>", "error");</script>
        <?php endif; ?>

        <?php if ($success): ?>
            <script>window.onload = () => showToast("<?php echo $success; ?>", "success");</script>
        <?php endif; ?>

        <style>
    .swal-confirm-custom {
        background-color: #ef4444 !important; /* Red */
        color: white !important;
        transition: background-color 0.3s ease !important;
    }
    .swal-confirm-custom:hover {
        background-color: #22c55e !important; /* Green */
    }
</style>

<form action="add_beneficiary.php" method="POST" id="addBeneficiaryForm" class="card" style="padding: 2.5rem;">

            <!-- Beneficiary Selection & Link -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;"><i data-lucide="link"></i> Client Linkage</h3>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Select Associated Client<span class="required-star">*</span></label>
                    <select name="client_id" class="form-control" style="padding-left: 1rem;" required>
                        <option value="">-- Choose Client --</option>
                        <?php foreach($clients as $c): ?>
                            <option value="<?php echo $c['client_id']; ?>">
                                <?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relationship<span class="required-star">*</span></label>
                    <input type="text" name="relationship" class="form-control" placeholder="e.g. Spouse" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Category<span class="required-star">*</span></label>
                    <select name="category" class="form-control" style="padding-left: 1rem;" required>
                        <option value="Health">Health</option>
                        <option value="Education">Education</option>
                        <option value="Financial">Financial</option>
                        <option value="Livelihood">Livelihood</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory<span class="required-star">*</span></label>
                    <input type="text" name="subcategory" class="form-control" style="padding-left: 1rem;" required>
                </div>
            </div>

            <!-- Aid Description -->
            <div class="form-group" style="margin-top: 1rem;">
                <label>Reason for Asking Aid (Aid Description)<span class="required-star">*</span></label>
                <textarea name="aid_description" class="form-control" style="padding: 1rem; min-height: 100px; resize: vertical;" placeholder="Provide a detailed reason or description for the assistance request..." required></textarea>
            </div>

            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="info"></i> Personal Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>First Name<span class="required-star">*</span></label>
                    <input type="text" name="first_name" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" style="padding-left: 1rem;">
                </div>
                <div class="form-group">
                    <label>Last Name<span class="required-star">*</span></label>
                    <input type="text" name="last_name" class="form-control" style="padding-left: 1rem;" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Date<span class="required-star">*</span></label>
                    <input type="date" name="birthdate" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-star">*</span></label>
                    <select name="sex" class="form-control" style="padding-left: 1rem;" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status<span class="required-star">*</span></label>
                    <select name="civil_status" class="form-control" style="padding-left: 1rem;" required>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label>Est. Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" style="padding-left: 1rem;" required>
                </div>
            </div>

            <!-- Address Cascading -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="home"></i> Address Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" style="padding-left: 1rem;" required></select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" style="padding-left: 1rem;" required disabled></select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" style="padding-left: 1rem;" required disabled></select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" style="padding-left: 1rem;" required disabled></select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" style="padding-left: 1rem;" required placeholder="District">
                </div>
            </div>

            <!-- Birthplace -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="map-pin"></i> Birthplace</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Province<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" style="padding-left: 1rem;" required></select>
                </div>
                <div class="form-group">
                    <label>Birth Municipality<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" style="padding-left: 1rem;" required disabled></select>
                </div>
                <div class="form-group">
                    <label>Specific City<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" style="padding-left: 1rem;" required>
                </div>
            </div>

            <div style="margin-top: 3rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">
                    Register Beneficiary <i data-lucide="users-2" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

<script src="assets/js/address-selector.js"></script>
<script>
    document.getElementById('addBeneficiaryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Register New Beneficiary?',
            text: 'Are you sure you want to enroll this beneficiary and link them to the selected client?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Register Beneficiary',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'swal-confirm-custom'
            }
        });

        if (isConfirmed) {
            this.submit();
        }
    });
</script>
<?php include 'includes/footer.php'; ?>
