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
$person_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$person_id) {
    header("Location: reports.php");
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Fetch current data
    $stmt = $pdo->prepare("SELECT p.*, c.religion, c.philhealt_num, c.contact_num, c.nationality,
                                 a.region, a.province as addr_province, a.municipality as addr_municipality, a.district, a.barangay,
                                 bp.province as bp_province, bp.municipality as bp_municipality, bp.city as bp_city
                          FROM person p
                          JOIN client c ON c.client_id = p.person_id
                          JOIN address a ON a.address_id = p.address_id
                          JOIN birthplace bp ON bp.birthplace_id = p.birthplace_id
                          WHERE p.person_id = ?");
    $stmt->execute([$person_id]);
    $clientData = $stmt->fetch();

    if (!$clientData) {
        header("Location: reports.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching record: " . $e->getMessage();
}

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

        // 1. Update Birthplace
        $stmtBP = $pdo->prepare("UPDATE birthplace SET province = ?, municipality = ?, city = ? WHERE birthplace_id = ?");
        $stmtBP->execute([$bp_province, $bp_municipality, $bp_city, $clientData['birthplace_id']]);

        // 2. Update Address
        $stmtAddr = $pdo->prepare("UPDATE address SET region = ?, province = ?, municipality = ?, district = ?, barangay = ? WHERE address_id = ?");
        $stmtAddr->execute([$addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay, $clientData['address_id']]);

        // 3. Update Person
        $stmtPerson = $pdo->prepare("UPDATE person SET first_name = ?, last_name = ?, middle_name = ?, birthdate = ?, civil_status = ?, sex = ?, estimated_monthly_income = ?, occupation = ? WHERE person_id = ?");
        $stmtPerson->execute([$first_name, $last_name, $middle_name, $birthdate, $civil_status, $sex, $income, $occupation, $person_id]);

        // 4. Update Client
        $stmtClient = $pdo->prepare("UPDATE client SET religion = ?, philhealt_num = ?, contact_num = ?, nationality = ? WHERE client_id = ?");
        $stmtClient->execute([$religion, $philhealth, $contact, $nationality, $person_id]);

        $pdo->commit();
        $_SESSION['toast'] = ['message' => 'Client record successfully updated!', 'type' => 'success'];
        header("Location: reports.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to update client: " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <?php render_sidebar('reports'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Edit Client Record</h1>
                <p style="color: var(--text-secondary);">Modify existing client information in the system.</p>
            </div>
            <a href="reports.php" class="btn btn-secondary" style="width: auto; padding: 0.75rem 1.5rem;">
                <i data-lucide="arrow-left"></i> Back to Reports
            </a>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <i data-lucide="alert-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="reports_edit_client.php?id=<?php echo $person_id; ?>" method="POST" id="editClientForm" class="card" style="padding: 2.5rem;">
            <!-- Client Specific -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;"><i data-lucide="file-badge"></i> Client Classification</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>PhilHealth Number<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="id-card" size="16"></i>
                        <input type="text" name="philhealth" class="form-control" value="<?php echo htmlspecialchars($clientData['philhealt_num']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contact Number<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="phone" size="16"></i>
                        <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($clientData['contact_num']); ?>" required>
                    </div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Religion</label>
                    <input type="text" name="religion" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['religion']); ?>">
                </div>
                <div class="form-group">
                    <label>Nationality<span class="required-star">*</span></label>
                    <input type="text" name="nationality" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['nationality']); ?>" required>
                </div>
            </div>

            <!-- Personal Information -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="info"></i> Personal Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>First Name<span class="required-star">*</span></label>
                    <input type="text" name="first_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['middle_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Last Name<span class="required-star">*</span></label>
                    <input type="text" name="last_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['last_name']); ?>" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Date<span class="required-star">*</span></label>
                    <input type="date" name="birthdate" class="form-control" style="padding-left: 1rem;" value="<?php echo $clientData['birthdate']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-star">*</span></label>
                    <select name="sex" class="form-control" style="padding-left: 1rem;" required>
                        <option value="Male" <?php echo $clientData['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $clientData['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status<span class="required-star">*</span></label>
                    <select name="civil_status" class="form-control" style="padding-left: 1rem;" required>
                        <option value="Single" <?php echo $clientData['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo $clientData['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo $clientData['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['occupation']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Est. Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" style="padding-left: 1rem;" value="<?php echo $clientData['estimated_monthly_income']; ?>" required>
                </div>
            </div>

            <!-- Address -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="home"></i> Address Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['region']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['region']); ?>"><?php echo htmlspecialchars($clientData['region']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['addr_province']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['addr_province']); ?>"><?php echo htmlspecialchars($clientData['addr_province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['addr_municipality']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['addr_municipality']); ?>"><?php echo htmlspecialchars($clientData['addr_municipality']); ?></option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['barangay']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['barangay']); ?>"><?php echo htmlspecialchars($clientData['barangay']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['district']); ?>" required>
                </div>
            </div>

            <!-- Birthplace -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="map-pin"></i> Birthplace</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Province<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['bp_province']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['bp_province']); ?>"><?php echo htmlspecialchars($clientData['bp_province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birth Municipality<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" style="padding-left: 1rem;" data-saved-value="<?php echo htmlspecialchars($clientData['bp_municipality']); ?>" required>
                        <option value="<?php echo htmlspecialchars($clientData['bp_municipality']); ?>"><?php echo htmlspecialchars($clientData['bp_municipality']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Specific City<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($clientData['bp_city']); ?>" required>
                </div>
            </div>

            <div style="margin-top: 3rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">
                    Update Client Record <i data-lucide="save" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

<script src="assets/js/address-selector.js"></script>
<script>
    document.getElementById('editClientForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Save Changes?',
            text: 'Are you sure you want to update this client record?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update Now',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b',
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
