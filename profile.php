<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

// Determine which user to view/edit
$view_id = (isset($_GET['id']) && $_SESSION['role'] === 'admin') ? $_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($view_id == $_SESSION['user_id']);

$error = "";
$success = "";

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT u.*, p.*, a.*, b.province as bp_province, b.municipality as bp_municipality, b.city as bp_city 
                          FROM users u
                          JOIN person p ON u.person_id = p.person_id
                          JOIN address a ON p.address_id = a.address_id
                          JOIN birthplace b ON p.birthplace_id = b.birthplace_id
                          WHERE u.user_id = ?");
    $stmt->execute([$view_id]);
    $uData = $stmt->fetch();
    
    if (!$uData) {
        header("Location: user_list.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Failed to load profile data: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect updated data
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name']);
    $birthdate = $_POST['birthdate'];
    $sex = $_POST['sex'];
    $civil_status = $_POST['civil_status'];
    $occupation = trim($_POST['occupation']);
    $income = trim($_POST['income']);
    
    $bp_province = trim($_POST['bp_province']);
    $bp_municipality = trim($_POST['bp_municipality']);
    $bp_city = trim($_POST['bp_city']);
    
    $addr_region = trim($_POST['addr_region']);
    $addr_province = trim($_POST['addr_province']);
    $addr_municipality = trim($_POST['addr_municipality']);
    $addr_district = trim($_POST['addr_district']);
    $addr_barangay = trim($_POST['addr_barangay']);

    // Check if password update is requested
    $password = $_POST['password'];
    $update_password = !empty($password);

    try {
        $pdo->beginTransaction();

        // 1. Update Address
        $stmtAddr = $pdo->prepare("UPDATE address SET region=?, province=?, municipality=?, district=?, barangay=? WHERE address_id=?");
        $stmtAddr->execute([$addr_region, $addr_province, $addr_municipality, $addr_district, $addr_barangay, $uData['address_id']]);

        // 2. Update Birthplace
        $stmtBP = $pdo->prepare("UPDATE birthplace SET province=?, municipality=?, city=? WHERE birthplace_id=?");
        $stmtBP->execute([$bp_province, $bp_municipality, $bp_city, $uData['birthplace_id']]);

        // 3. Update Person
        $stmtPerson = $pdo->prepare("UPDATE person SET first_name=?, last_name=?, middle_name=?, birthdate=?, civil_status=?, sex=?, estimated_monthly_income=?, occupation=? WHERE person_id=?");
        $stmtPerson->execute([$first_name, $last_name, $middle_name, $birthdate, $civil_status, $sex, $income, $occupation, $uData['person_id']]);

        // 4. Update User
        if ($update_password) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("UPDATE users SET email=?, password_hash=? WHERE user_id=?");
            $stmtUser->execute([$email, $pass_hash, $view_id]);
        } else {
            $stmtUser = $pdo->prepare("UPDATE users SET email=? WHERE user_id=?");
            $stmtUser->execute([$email, $view_id]);
        }

        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh session vars if editing own profile
        if ($is_own_profile) {
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        }
        
        // Refresh local data for display
        $stmt->execute([$view_id]);
        $uData = $stmt->fetch();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Update failed: " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <?php render_sidebar('profile'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1><?php echo $is_own_profile ? 'My Account' : 'User Profile'; ?></h1>
                <p style="color: var(--text-secondary); font-weight: 500;"><?php echo $is_own_profile ? 'View and update your administrator profile.' : 'View and update details for this user account.'; ?></p>
            </div>
            <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 50px; box-shadow: var(--shadow); color: var(--primary-color); font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="<?php echo $uData['status'] == 'active' ? 'user-check' : 'user-x'; ?>" size="18"></i> <?php echo ucfirst($uData['status']); ?> Account
            </div>
        </header>

        <?php if ($error): ?>
            <script>window.onload = () => showToast("<?php echo $error; ?>", "error");</script>
        <?php endif; ?>
        <?php if ($success): ?>
            <script>window.onload = () => showToast("<?php echo $success; ?>", "success");</script>
        <?php endif; ?>

        <form action="profile.php<?php echo isset($_GET['id']) ? '?id=' . $_GET['id'] : ''; ?>" method="POST" id="profileForm" class="card" style="padding: 2.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.5rem; border-bottom: 1.5px solid var(--border-color); padding-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 80px; height: 80px; background: var(--bg-color); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <i data-lucide="user" size="40"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--primary-color); font-weight: 800;"><?php echo htmlspecialchars($uData['first_name'] . ' ' . $uData['last_name']); ?></h2>
                        <p style="color: var(--text-secondary); font-weight: 500;">Username: <span style="font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($uData['username']); ?></span></p>
                    </div>
                </div>
                <button type="button" id="editBtn" class="btn btn-secondary" style="width: auto; padding: 0.5rem 1.5rem; background: var(--bg-color); color: var(--primary-color); font-weight: 700; border: 1px solid var(--border-color);">
                    <i data-lucide="edit-3" size="18"></i> Edit Profile Details
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                        <i data-lucide="shield" size="18"></i> Account Security
                    </h3>
                    <div class="form-group">
                        <label>Email Address<span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i data-lucide="mail" size="16"></i>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($uData['email']); ?>" required disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password (Leave blank to keep current)</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock" size="16"></i>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" disabled>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                        <i data-lucide="info" size="18"></i> Personal Information
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>First Name<span class="required-star">*</span></label>
                            <input type="text" name="first_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['first_name']); ?>" required disabled>
                        </div>
                        <div class="form-group">
                            <label>Last Name<span class="required-star">*</span></label>
                            <input type="text" name="last_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['last_name']); ?>" required disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['middle_name']); ?>" disabled>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                <div class="form-group">
                    <label>Birth Date<span class="required-star">*</span></label>
                    <input type="date" name="birthdate" class="form-control" style="padding-left: 1rem;" value="<?php echo $uData['birthdate']; ?>" required disabled>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-star">*</span></label>
                    <select name="sex" class="form-control" style="padding-left: 1rem;" required disabled>
                        <option value="Male" <?php echo $uData['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $uData['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status<span class="required-star">*</span></label>
                    <select name="civil_status" class="form-control" style="padding-left: 1rem;" required disabled>
                        <option value="Single" <?php echo $uData['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo $uData['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo $uData['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <h3 style="color: var(--text-primary); margin: 2.5rem 0 1.5rem; display: flex; align-items: center; gap: 0.5rem; border-top: 1.5px solid var(--border-color); padding-top: 2rem; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                <i data-lucide="briefcase" size="18"></i> Professional Profile
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Occupation<span class="required-star">*</span></label>
                    <input type="text" name="occupation" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['occupation']); ?>" required disabled>
                </div>
                <div class="form-group">
                    <label>Monthly Income<span class="required-star">*</span></label>
                    <input type="number" name="income" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['estimated_monthly_income']); ?>" required disabled>
                </div>
            </div>

            <h3 style="color: var(--text-primary); margin: 2.5rem 0 1.5rem; display: flex; align-items: center; gap: 0.5rem; border-top: 1.5px solid var(--border-color); padding-top: 2rem; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                <i data-lucide="map-pin" size="18"></i> Birthplace Reference
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Province (Birth)<span class="required-star">*</span></label>
                    <select name="bp_province" id="bp_province" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['bp_province']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['bp_province']); ?>"><?php echo htmlspecialchars($uData['bp_province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality/City (Birth)<span class="required-star">*</span></label>
                    <select name="bp_municipality" id="bp_municipality" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['bp_municipality']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['bp_municipality']); ?>"><?php echo htmlspecialchars($uData['bp_municipality']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Specific City/Area<span class="required-star">*</span></label>
                    <input type="text" name="bp_city" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['bp_city']); ?>" required disabled>
                </div>
            </div>

            <h3 style="color: var(--text-primary); margin: 2.5rem 0 1.5rem; display: flex; align-items: center; gap: 0.5rem; border-top: 1.5px solid var(--border-color); padding-top: 2rem; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                <i data-lucide="home" size="18"></i> Current Residential Address
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Region<span class="required-star">*</span></label>
                    <select name="addr_region" id="addr_region" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['region']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['region']); ?>"><?php echo htmlspecialchars($uData['region']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Province<span class="required-star">*</span></label>
                    <select name="addr_province" id="addr_province" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['province']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['province']); ?>"><?php echo htmlspecialchars($uData['province']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Municipality<span class="required-star">*</span></label>
                    <select name="addr_municipality" id="addr_municipality" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['municipality']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['municipality']); ?>"><?php echo htmlspecialchars($uData['municipality']); ?></option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Barangay<span class="required-star">*</span></label>
                    <select name="addr_barangay" id="addr_barangay" class="form-control" style="padding-left: 1rem;" data-preset="true" data-saved-value="<?php echo htmlspecialchars($uData['barangay']); ?>" required disabled>
                        <option value="<?php echo htmlspecialchars($uData['barangay']); ?>"><?php echo htmlspecialchars($uData['barangay']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>District<span class="required-star">*</span></label>
                    <input type="text" name="addr_district" class="form-control" style="padding-left: 1rem;" value="<?php echo htmlspecialchars($uData['district']); ?>" required disabled>
                </div>
            </div>

            <div id="saveContainer" style="margin-top: 3rem; display: none; justify-content: flex-end; gap: 1rem;">
                <button type="button" id="cancelBtn" class="btn" style="width: auto; padding: 0.75rem 2rem; background: var(--bg-color); color: var(--text-secondary); font-weight: 700;">Cancel Changes</button>
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem; font-weight: 700;">
                    Update Profile <i data-lucide="save" size="20"></i>
                </button>
            </div>
        </form>
    </main>
</div>

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

<script>
    const editBtn = document.getElementById('editBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveContainer = document.getElementById('saveContainer');
    const profileForm = document.getElementById('profileForm');
    const inputs = document.querySelectorAll('#profileForm input:not([type="hidden"]), #profileForm select');

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const { isConfirmed } = await Swal.fire({
            title: 'Update Profile?',
            text: 'Are you sure you want to save these changes?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'swal-confirm-custom'
            }
        });

        if (isConfirmed) {
            profileForm.submit();
        }
    });

    editBtn.addEventListener('click', async () => {
        const { isConfirmed } = await Swal.fire({
            title: 'Edit Profile Details?',
            text: 'This will enable the form fields for editing. Are you sure you want to proceed?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, Enable Editing',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'swal-confirm-custom'
            }
        });

        if (isConfirmed) {
            inputs.forEach(input => input.disabled = false);
            editBtn.style.display = 'none';
            saveContainer.style.display = 'flex';
            
            // Trigger address selector re-init if needed
            if (typeof initAddressSelectors === 'function') initAddressSelectors();
        }
    });

    cancelBtn.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = true);
        editBtn.style.display = 'flex';
        saveContainer.style.display = 'none';
    });
</script>

<script src="assets/js/address-selector.js"></script>

<?php include 'includes/footer.php'; ?>
