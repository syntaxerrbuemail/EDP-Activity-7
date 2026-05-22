<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

$message = "";
$type = "";
$step = 1; 
$email_found = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    // STEP 1: VERIFY EMAIL
    if (isset($_POST['check_email'])) {
        $email = trim($_POST['email']);
        try {
            // Case-insensitive search for email
            $stmt = $conn->prepare("SELECT email FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $step = 2;
                $email_found = $user['email'];
            } else {
                $message = "No account found with the email address: " . htmlspecialchars($email);
                $type = "error";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $type = "error";
        }
    } 
    // STEP 2: RESET PASSWORD
    elseif (isset($_POST['reset_password'])) {
        $email = trim($_POST['email_confirmed']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if (empty($password)) {
            $message = "Password cannot be empty.";
            $type = "error";
            $step = 2;
            $email_found = $email;
        } elseif ($password !== $confirm) {
            $message = "Passwords do not match. Please try again.";
            $type = "error";
            $step = 2;
            $email_found = $email;
        } else {
            try {
                // Hash the new password
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Perform the update
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $stmt->execute([$new_hash, $email]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "SUCCESS! Your password has been updated. You can now login with your new password.";
                    $type = "success";
                    $step = 1;
                } else {
                    $message = "Update Failed: No changes were made. Ensure the account is valid.";
                    $type = "error";
                    $step = 2;
                    $email_found = $email;
                }
            } catch (PDOException $e) {
                $message = "Technical Error: " . $e->getMessage();
                $type = "error";
                $step = 2;
                $email_found = $email;
            }
        }
    }
}
?>

<div class="auth-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-color); padding: 2rem;">
    <div class="auth-card" style="background: white; padding: 2.5rem; border-radius: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 450px;">
        <div class="auth-header" style="text-align: center; margin-bottom: 2rem;">
            <div style="background: var(--primary-color); width: 64px; height: 64px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white;">
                <i data-lucide="<?php echo $step == 1 ? 'key-round' : 'shield-check'; ?>" size="32"></i>
            </div>
            <h1 style="font-size: 1.75rem; color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $step == 1 ? 'Password Recovery' : 'Create New Password'; ?></h1>
            <p style="color: var(--text-secondary);"><?php echo $step == 1 ? 'Enter your registered email to reset your account.' : 'Please enter and confirm your new password.'; ?></p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; background: <?php echo $type == 'success' ? '#f0fff4' : '#fff5f5'; ?>; color: <?php echo $type == 'success' ? '#2f855a' : '#c53030'; ?>; border: 1px solid <?php echo $type == 'success' ? '#c6f6d5' : '#fed7d7'; ?>;">
                <i data-lucide="<?php echo $type == 'success' ? 'check-circle' : 'alert-circle'; ?>" size="20"></i>
                <span style="font-size: 0.875rem; font-weight: 500;"><?php echo $message; ?></span>
            </div>
            <?php if ($type == 'success'): ?>
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <a href="login.php" class="btn btn-primary">Proceed to Login <i data-lucide="arrow-right"></i></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($step == 1 && $type != 'success'): ?>
            <form action="recovery.php" method="POST">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Email Address</label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="mail" size="18" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                        <input type="email" name="email" class="form-control" required style="padding-left: 3rem;" placeholder="name@example.com">
                    </div>
                </div>
                <button type="submit" name="check_email" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
                    Find My Account <i data-lucide="search" size="18"></i>
                </button>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="login.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.875rem;"><i data-lucide="chevron-left" size="14" style="vertical-align: middle;"></i> Back to Login</a>
                </div>
            </form>
        <?php elseif ($step == 2): ?>
            <form action="recovery.php" method="POST" id="resetForm">
                <input type="hidden" name="email_confirmed" value="<?php echo htmlspecialchars($email_found); ?>">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">New Password</label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock" size="18" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                        <input type="password" id="password" name="password" class="form-control" required style="padding: 0.875rem 3rem 0.875rem 3rem;">
                        <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 0.5rem; top: 0; height: 100%; background: none; border: none; padding: 0 0.5rem; cursor: pointer; color: var(--text-secondary);">
                            <span class="pw-eye-off"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Confirm Password</label>
                    <div class="input-wrapper" style="position: relative;">
                        <i data-lucide="lock-keyhole" size="18" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required style="padding: 0.875rem 3rem 0.875rem 3rem;">
                        <button type="button" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 0.5rem; top: 0; height: 100%; background: none; border: none; padding: 0 0.5rem; cursor: pointer; color: var(--text-secondary);">
                            <span class="pw-eye-off"><i data-lucide="eye-off" size="18"></i></span>
                            <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                        </button>
                    </div>
                </div>

                <button type="button" onclick="confirmReset()" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
                    Change Password <i data-lucide="save" size="18"></i>
                </button>
            </form>
            
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
            function confirmReset() {
                const p1 = document.getElementById('password').value;
                const p2 = document.getElementById('confirm_password').value;
                
                if(!p1 || !p2) {
                    Swal.fire('Error', 'Please fill in both password fields.', 'error');
                    return;
                }
                
                if(p1 !== p2) {
                    Swal.fire('Mismatch', 'Passwords do not match.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Update Password?',
                    text: 'This will replace your old password with the new one.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'swal-confirm-custom'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('resetForm').submit();
                    }
                });
            }
            </script>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/password-toggle.js"></script>
<?php include 'includes/footer.php'; ?>
