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
    $stmt = $pdo->prepare("SELECT p.*, b.client_id, b.client_relationship, b.category, b.subcategory, b.aid_description,
                                 a.region, a.province as addr_province, a.municipality as addr_municipality, a.district, a.barangay,
                                 bp.province as bp_province, bp.municipality as bp_municipality, bp.city as bp_city
                          FROM person p
                          JOIN beneficiary b ON b.beneficiary_id = p.person_id
                          JOIN address a ON a.address_id = p.address_id
                          JOIN birthplace bp ON bp.birthplace_id = p.birthplace_id
                          WHERE p.person_id = ?");
    $stmt->execute([$person_id]);
    $beneData = $stmt->fetch();

    if (!$beneData) {
        header("Location: reports.php");
        exit();
    }

    // Fetch clients for linkage
    $stmtClients = $pdo->query("SELECT c.client_id, p.first_name, p.last_name 
                                FROM client c JOIN person p ON p.person_id = c.client_id 
                                ORDER BY p.last_name ASC");
    $clients = $stmtClients->fetchAll();

} catch (PDOException $e) {
    $error = "Error fetching record: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Linkage Info
    $client_id = $_POST['client_id'];
    $relationship = trim($_POST['relationship']);
    $category = $_POST['category'];
    $subcategory = trim($_POST['subcategory']);
    $description = trim($_POST['description']);
    
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
        $stmtBP->execute([$bp_province, $bp_municipality, $bp_city, $beneData['birthplace_id']]);

        // 2. Update Address
        $stmtAddr = $pdo->prepare("UPDATE address SET region = ?, province = ?, municipality = ?, district = ?, barangay = ? WHERE address_id = ?");
        $stmtAddr->execute([$addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay, $beneData['address_id']]);

        // 3. Update Person
        $stmtPerson = $pdo->prepare("UPDATE person SET first_name = ?, last_name = ?, middle_name = ?, birthdate = ?, civil_status = ?, sex = ?, estimated_monthly_income = ?, occupation = ? WHERE person_id = ?");
        $stmtPerson->execute([$first_name, $last_name, $middle_name, $birthdate, $civil_status, $sex, $income, $occupation, $person_id]);

        // 4. Update Beneficiary
        $stmtBene = $pdo->prepare("UPDATE beneficiary SET client_id = ?, client_relationship = ?, category = ?, subcategory = ?, aid_description = ? WHERE beneficiary_id = ?");
        $stmtBene->execute([$client_id, $relationship, $category, $subcategory, $description, $person_id]);

        $pdo->commit();
        $_SESSION['toast'] = ['message' => 'Beneficiary record successfully updated!', 'type' => 'success'];
        header("Location: reports.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to update beneficiary: " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <?php render_sidebar('reports'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Edit Beneficiary Record</h1>
                <p style="color: var(--text-secondary);">Modify existing beneficiary and linkage information.</p>
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

        <form action="reports_edit_beneficiary.php?id=<?php echo $person_id; ?>" method="POST" id="editBeneForm" class="card" style="padding: 2.5rem;">
            <!-- Client Linkage -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;"><i data-lucide="link"></i> Client Linkage</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Associated Client<span class="required-star">*</span></label>
                    <select name="client_id" class="form-control" required>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['client_id']; ?>" <?php echo $c['client_id'] == $beneData['client_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relationship<span class="required-star">*</span></label>
                    <input type="text" name="relationship" class="form-control" value="<?php echo htmlspecialchars($beneData['client_relationship']); ?>" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Category<span class="required-star">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="Health" <?php echo $beneData['category'] == 'Health' ? 'selected' : ''; ?>>Health</option>
                        <option value="Education" <?php echo $beneData['category'] == 'Education' ? 'selected' : ''; ?>>Education</option>
                        <option value="Financial" <?php echo $beneData['category'] == 'Financial' ? 'selected' : ''; ?>>Financial</option>
                        <option value="Livelihood" <?php echo $beneData['category'] == 'Livelihood' ? 'selected' : ''; ?>>Livelihood</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory<span class="required-star">*</span></label>
                    <input type="text" name="subcategory" class="form-control" value="<?php echo htmlspecialchars($beneData['subcategory']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Reason for Aid (Description)<span class="required-star">*</span></label>
                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($beneData['aid_description']); ?></textarea>
            </div>

            <!-- Personal Information -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="info"></i> Personal Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>First Name<span class="required-star">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($beneData['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($beneData['middle_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Last Name<span class="required-star">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($beneData['last_name']); ?>" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Date<span class="required-star">*</span></label>
                    <input type="date" name="birthdate" class="form-control" value="<?php echo $beneData['birthdate']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-star">*</span></label>
                    <select name="sex" class="form-control" required>
                        <option value="Male" <?php echo $beneData['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $beneData['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status<span class="required-star">*</span></label>
                    <select name="civil_status" class="form-control" required>
                        <option value="Single" <?php echo $beneData['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo $beneData['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo $beneData['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($beneData['occupation']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Est. Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" value="<?php echo $beneData['estimated_monthly_income']; ?>" required>
                </div>
            </div>

            <!-- Address Details -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="home"></i> Address Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['region']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['region']); ?>"><?php echo htmlspecialchars($beneData['region']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['addr_province']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['addr_province']); ?>"><?php echo htmlspecialchars($beneData['addr_province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['addr_municipality']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['addr_municipality']); ?>"><?php echo htmlspecialchars($beneData['addr_municipality']); ?></option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['barangay']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['barangay']); ?>"><?php echo htmlspecialchars($beneData['barangay']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" value="<?php echo htmlspecialchars($beneData['district']); ?>" required>
                </div>
            </div>

            <!-- Birthplace -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;"><i data-lucide="map-pin"></i> Birthplace</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Province<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['bp_province']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['bp_province']); ?>"><?php echo htmlspecialchars($beneData['bp_province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birth Municipality<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" data-saved-value="<?php echo htmlspecialchars($beneData['bp_municipality']); ?>" required>
                        <option value="<?php echo htmlspecialchars($beneData['bp_municipality']); ?>"><?php echo htmlspecialchars($beneData['bp_municipality']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Specific City<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" value="<?php echo htmlspecialchars($beneData['bp_city']); ?>" required>
                </div>
            </div>

            <div style="margin-top: 3rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">
                    Update Beneficiary Record <i data-lucide="save" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

<script src="assets/js/address-selector.js"></script>
<script>
    document.getElementById('editBeneForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Save Changes?',
            text: 'Are you sure you want to update this beneficiary record?',
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
