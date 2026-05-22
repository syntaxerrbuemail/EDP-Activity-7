<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Auth check - only admin can manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    // Collect all data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
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
            $conn->beginTransaction();

            if ($role === 'admin') {
                $u_min = 7000; $u_max = 7999;
                $a_min = 8000; $a_max = 8999;
                $p_min = 9000; $p_max = 9999;
                $b_min = 7500; $b_max = 7999;
            } else {
                $u_min = 1100; $u_max = 1199;
                $a_min = 1200; $a_max = 1299;
                $p_min = 1300; $p_max = 1399;
                $b_min = 1400; $b_max = 1499;
            }

            // 1. Generate Birthplace ID
            $stmtMaxBP = $conn->query("SELECT MAX(birthplace_id) FROM birthplace WHERE birthplace_id >= $b_min AND birthplace_id <= $b_max");
            $maxBpId = $stmtMaxBP->fetchColumn();
            $birthplace_id = ($maxBpId) ? $maxBpId + 1 : $b_min;

            // 2. Insert Birthplace
            $stmtBP = $conn->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)");
            $stmtBP->execute([$birthplace_id, $bp_province, $bp_municipality, $bp_city]);

            // 3. Generate Address ID
            $stmtMaxAddr = $conn->query("SELECT MAX(address_id) FROM address WHERE address_id >= $a_min AND address_id <= $a_max");
            $maxAddrId = $stmtMaxAddr->fetchColumn();
            $address_id = ($maxAddrId) ? $maxAddrId + 1 : $a_min;

            // 4. Insert Address
            $stmtAddr = $conn->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtAddr->execute([$address_id, $addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay]);

            // 5. Generate Person ID
            $stmtMax = $conn->query("SELECT MAX(person_id) FROM person WHERE person_id >= $p_min AND person_id <= $p_max");
            $maxId = $stmtMax->fetchColumn();
            $person_id = ($maxId) ? $maxId + 1 : $p_min;

            // 6. Insert Person
            $stmtPerson = $conn->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPerson->execute([$person_id, $first_name, $last_name, $middle_name, $birthdate, $birthplace_id, $civil_status, $sex, $income, $address_id, $occupation]);

            // 7. Generate User ID
            $stmtMaxU = $conn->query("SELECT MAX(user_id) FROM users WHERE user_id >= $u_min AND user_id <= $u_max");
            $maxUId = $stmtMaxU->fetchColumn();
            $user_id = ($maxUId) ? $maxUId + 1 : $u_min;

            // 8. Insert User
            $stmtUser = $conn->prepare("INSERT INTO users (user_id, person_id, username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmtUser->execute([$user_id, $person_id, $username, $email, $password_hashed, $role]);

            $conn->commit();
            
            $_SESSION['toast'] = ['message' => 'New account created successfully!', 'type' => 'success'];
            header("Location: user_list.php");
            exit();
            
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $error = "Account creation failed: " . $e->getMessage();
        }
    } // End password match check
}
?>

<div class="dashboard-container">
    <?php render_sidebar('users'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Add New Account</h1>
                <p style="color: var(--text-secondary);">Create a new administrative or staff account.</p>
            </div>
            <a href="user_list.php" class="btn btn-secondary" style="width: auto; padding: 0.75rem 1.5rem;">
                <i data-lucide="arrow-left"></i> Back to List
            </a>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <i data-lucide="alert-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <style>
            .swal-create-custom {
                background-color: #2d5a27 !important; /* System Green */
                color: white !important;
                transition: background-color 0.3s ease !important;
            }
            .swal-create-custom:hover {
                background-color: #1e3d1a !important; /* Darker Green */
            }
        </style>

        <form action="user_add.php" method="POST" id="addForm" class="card" style="padding: 2rem;">
            <!-- Account Section -->
            <h3 style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                <i data-lucide="shield"></i> Account Credentials
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Username<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="user" size="16"></i>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="mail" size="16"></i>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Initial Password<span class="required-star">*</span></label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock" size="16"></i>
                        <input type="password" id="password" name="password" class="form-control" required style="padding-right: 3rem;">
                        <button type="button" onclick="togglePassword('password', this)" title="Toggle password visibility" style="position:absolute;right:1rem;background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;height:100%;top:0;">
                            <span class="pw-eye-off"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password<span class="required-star">*</span></label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock-keyhole" size="16"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required style="padding-right: 3rem;">
                        <button type="button" onclick="togglePassword('confirm_password', this)" title="Toggle password visibility" style="position:absolute;right:1rem;background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;height:100%;top:0;">
                            <span class="pw-eye-off"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Role<span class="required-star">*</span></label>
                <select name="role" class="form-control" required>
                    <option value="admin">Administrator</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <!-- Personal Info Section -->
            <h3 style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;">
                <i data-lucide="info"></i> Personal Information
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>First Name<span class="required-star">*</span></label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Last Name<span class="required-star">*</span></label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Birth Date<span class="required-star">*</span></label>
                    <input type="date" name="birthdate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-star">*</span></label>
                    <select name="sex" class="form-control" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status<span class="required-star">*</span></label>
                    <select name="civil_status" class="form-control" required>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" required>
                </div>
            </div>

            <!-- Birthplace & Address (Simplified for this form to keep it short, using dropdowns if possible) -->
            <h3 style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;">
                <i data-lucide="map-pin"></i> Location Details
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" required disabled></select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" required disabled></select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" required disabled></select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" required>
                </div>
            </div>

            <!-- Birthplace Section -->
            <h3 style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin: 2rem 0 1.5rem;">
                <i data-lucide="baby"></i> Birthplace Details
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Province (Birth)<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label>Municipality/City (Birth)<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" required disabled></select>
                </div>
                <div class="form-group">
                    <label>Specific City/Area<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" placeholder="e.g. Naga City" required>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">
                    Create Account <i data-lucide="check" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

<script src="assets/js/address-selector.js"></script>
<script src="assets/js/password-toggle.js"></script>
<script>
    document.getElementById('addForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Create Account?',
            text: 'Are you sure you want to add this new account?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, create it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'swal-create-custom'
            }
        });

        if (isConfirmed) {
            this.submit();
        }
    });
</script>
<?php include 'includes/footer.php'; ?>
