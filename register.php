<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect all data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Server-side check for password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Role info
        $role = $_POST['role'];

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
            $stmtMaxBP = $pdo->query("SELECT MAX(birthplace_id) FROM birthplace WHERE birthplace_id >= $b_min AND birthplace_id <= $b_max");
            $maxBpId = $stmtMaxBP->fetchColumn();
            $birthplace_id = ($maxBpId) ? $maxBpId + 1 : $b_min;

            // 2. Insert Birthplace
            $stmtBP = $pdo->prepare("INSERT INTO birthplace (birthplace_id, province, municipality, city) VALUES (?, ?, ?, ?)");
            $stmtBP->execute([$birthplace_id, $bp_province, $bp_municipality, $bp_city]);

            // 3. Generate Address ID
            $stmtMaxAddr = $pdo->query("SELECT MAX(address_id) FROM address WHERE address_id >= $a_min AND address_id <= $a_max");
            $maxAddrId = $stmtMaxAddr->fetchColumn();
            $address_id = ($maxAddrId) ? $maxAddrId + 1 : $a_min;

            // 4. Insert Address
            $stmtAddr = $pdo->prepare("INSERT INTO address (address_id, region, province, municipality, district, barangay) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtAddr->execute([$address_id, $addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay]);

            // 5. Generate Person ID
            $stmtMax = $pdo->query("SELECT MAX(person_id) FROM person WHERE person_id >= $p_min AND person_id <= $p_max");
            $maxId = $stmtMax->fetchColumn();
            $person_id = ($maxId) ? $maxId + 1 : $p_min;

            // 6. Insert Person
            $stmtPerson = $pdo->prepare("INSERT INTO person (person_id, first_name, last_name, middle_name, birthdate, birthplace_id, civil_status, sex, estimated_monthly_income, address_id, occupation) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPerson->execute([$person_id, $first_name, $last_name, $middle_name, $birthdate, $birthplace_id, $civil_status, $sex, $income, $address_id, $occupation]);

            // 7. Generate User ID
            $stmtMaxU = $pdo->query("SELECT MAX(user_id) FROM users WHERE user_id >= $u_min AND user_id <= $u_max");
            $maxUId = $stmtMaxU->fetchColumn();
            $user_id = ($maxUId) ? $maxUId + 1 : $u_min;

            // 8. Insert User
            $stmtUser = $pdo->prepare("INSERT INTO users (user_id, person_id, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtUser->execute([$user_id, $person_id, $username, $email, $password_hashed, $role]);

            $pdo->commit();
            
            // Automatic Login after registration
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            $_SESSION['role'] = $role;
            
            $_SESSION['toast'] = ['message' => 'Registration successful! Welcome to the Admin Dashboard.', 'type' => 'success'];
            header("Location: dashboard.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<?php if ($error): ?>
    <script>window.onload = () => showToast("<?php echo $error; ?>", "error");</script>
<?php endif; ?>

<div class="auth-container" style="padding: 40px 20px; align-items: flex-start;">
    <div class="auth-card" style="max-width: 800px;">
        <div class="auth-header">
            <h1>Employee Registration</h1>
            <p style="font-weight: 600; color: var(--primary-color); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 1rem;">Client Aid Management System</p>
            <p>Create a new employee account to access CAMS.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert" style="background: #e9f7ef; color: #1e8449; border: 1px solid #d1e2d0;">
                <i data-lucide="check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="regForm" onsubmit="event.preventDefault(); Swal.fire({title: 'Create Account?', text: 'Are you sure you want to register this new account?', icon: 'question', showCancelButton: true, confirmButtonColor: '#3b82f6', confirmButtonText: 'Yes, register!'}).then((result) => { if (result.isConfirmed) { document.getElementById('regForm').submit(); } });">
            <!-- Section 1: Account -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1rem;"><i data-lucide="shield"></i> Account Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Username<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="user" size="16"></i>
                        <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="mail" size="16"></i>
                        <input type="email" name="email" class="form-control" placeholder="employee@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Role<span class="required-star">*</span></label>
                    <div class="input-wrapper">
                        <i data-lucide="briefcase" size="16"></i>
                        <select name="role" class="form-control" required style="padding-left: 2.5rem;">
                            <option value="admin">Administrator</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Password<span class="required-star">*</span></label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock" size="16"></i>
                        <input type="password" id="reg_password" name="password" class="form-control" placeholder="Create a password" required style="padding-right: 3rem;">
                        <button type="button" onclick="togglePassword('reg_password', this)" title="Toggle password visibility" style="position:absolute;right:1rem;background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;">
                            <span class="pw-eye-off" style="display:inline-flex;"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password<span class="required-star">*</span></label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock-keyhole" size="16"></i>
                        <input type="password" id="reg_confirm_password" name="confirm_password" class="form-control" placeholder="Verify password" required style="padding-right: 3rem;">
                        <button type="button" onclick="togglePassword('reg_confirm_password', this)" title="Toggle password visibility" style="position:absolute;right:1rem;background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;">
                            <span class="pw-eye-off" style="display:inline-flex;"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section 2: Personal Info -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1rem;"><i data-lucide="info"></i> Personal Information</h3>
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
                        <option value="Separated">Separated</option>
                    </select>
                </div>
            </div>

            <!-- Section 3: Professional -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label>Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" style="padding-left: 1rem;" required>
                </div>
            </div>

            <!-- Section 4: Birthplace -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1rem;"><i data-lucide="map-pin"></i> Birthplace Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Province (Birth)<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" style="padding-left: 1rem;" required>
                        <option value="">-- Select Province --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality/City (Birth)<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" style="padding-left: 1rem;" required>
                        <option value="">-- Select Municipality/City --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Specific City/Area<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" style="padding-left: 1rem;" placeholder="e.g. Naga City" required>
                </div>
            </div>

            <!-- Section 5: Address -->
            <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem; margin: 2rem 0 1rem;"><i data-lucide="home"></i> Residential Address</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" style="padding-left: 1rem;" required>
                        <option value="">-- Select Region --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" style="padding-left: 1rem;" required disabled>
                        <option value="">-- Select Province --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" style="padding-left: 1rem;" required disabled>
                        <option value="">-- Select Municipality --</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" style="padding-left: 1rem;" required disabled>
                        <option value="">-- Select Barangay --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" style="padding-left: 1rem;" placeholder="e.g. 1st District" required>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem;">
                    Complete Registration <i data-lucide="check" size="20"></i>
                </button>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.875rem;">Already have an account? Login here</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/address-selector.js"></script>
<script src="assets/js/password-toggle.js"></script>
<?php include 'includes/footer.php'; ?>
