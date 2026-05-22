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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Client Info
    $religion = trim($_POST['religion']);
    $philhealth = trim($_POST['philhealth']);
    $contact = trim($_POST['contact']);
    $nationality = trim($_POST['nationality']);
    
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

        // 1. Generate Birthplace ID (Target: 801+)
        $stmtMaxBP = $pdo->query("SELECT MAX(birthplace_id) FROM birthplace WHERE birthplace_id < 7500");
        $maxBpId = $stmtMaxBP->fetchColumn();
        $birthplace_id = ($maxBpId >= 801) ? $maxBpId + 1 : 801;

        // 2. Insert Birthplace
        $stmtBP = $pdo->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)");
        $stmtBP->execute([$birthplace_id, $bp_province, $bp_municipality, $bp_city]);

        // 3. Generate Address ID (Target: 701+)
        $stmtMaxAddr = $pdo->query("SELECT MAX(address_id) FROM address WHERE address_id < 8000");
        $maxAddrId = $stmtMaxAddr->fetchColumn();
        $address_id = ($maxAddrId >= 701) ? $maxAddrId + 1 : 701;

        // 4. Insert Address
        $stmtAddr = $pdo->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAddr->execute([$address_id, $addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay]);

        // 5. Generate Person ID (Target: 201+)
        $stmtMax = $pdo->query("SELECT MAX(person_id) FROM person WHERE person_id < 7000");
        $maxId = $stmtMax->fetchColumn();
        $person_id = ($maxId >= 201) ? $maxId + 1 : 201;

        // 6. Insert Person
        $stmtPerson = $pdo->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtPerson->execute([$person_id, $first_name, $last_name, $middle_name, $birthdate, $birthplace_id, $civil_status, $sex, $income, $address_id, $occupation]);

        // 7. Insert Client
        $stmtClient = $pdo->prepare("INSERT INTO client (client_id, religion, philhealt_num, contact_num, nationality) VALUES (?, ?, ?, ?, ?)");
        $stmtClient->execute([$person_id, $religion, $philhealth, $contact, $nationality]);

        $pdo->commit();
        $success = "Client successfully registered into the system!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to add client: " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <?php render_sidebar('add_client'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Register New Client</h1>
                <p style="color: var(--text-secondary);">Enroll a new beneficiary client into the Aid Management System.</p>
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

<form action="add_client.php" method="POST" id="addClientForm" class="card" style="padding: 2.5rem;">

            <!-- Client Specific -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;"><i data-lucide="file-badge"></i> Client Classification</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>PhilHealth Number<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="id-card" size="16"></i>
                        <input type="text" name="philhealth" class="form-control" placeholder="PH-000-0000" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contact Number<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="phone" size="16"></i>
                        <input type="text" name="contact" class="form-control" placeholder="09xxxxxxxxx" required>
                    </div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Religion</label>
                    <input type="text" name="religion" class="form-control" style="padding-left: 1rem;">
                </div>
                <div class="form-group">
                    <label>Nationality<span class="required-star">*</span></label>
                    <input type="text" name="nationality" class="form-control" style="padding-left: 1rem;" value="Filipino" required>
                </div>
            </div>

            <!-- Standard Person Info (Reuse fields) -->
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

            <!-- Address (Using cascading selector) -->
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
                    Register Client <i data-lucide="user-check" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

<script src="assets/js/address-selector.js"></script>
<script>
    document.getElementById('addClientForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Register New Client?',
            text: 'Are you sure you want to enroll this client into the system?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Register Client',
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
