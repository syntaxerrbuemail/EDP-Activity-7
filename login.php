<?php
session_start();
require_once 'includes/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT u.*, p.first_name, p.last_name FROM users u 
                                   LEFT JOIN person p ON u.person_id = p.person_id 
                                   WHERE BINARY u.username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $error = "Your account is currently inactive. Please contact the administrator.";
                } else {
                    // Secure session initialization
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = ($user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : $user['username']);
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again later.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>CAMS Login</h1>
            <p style="font-weight: 600; color: var(--primary-color); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 1rem;">Client Aid Management System</p>
            <p>Please enter your credentials to continue.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username<span class="required-star">*</span></label>
                <div class="input-wrapper">
                    <i data-lucide="user" size="18"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password<span class="required-star">*</span></label>
                <div class="input-wrapper" style="position: relative;">
                    <i data-lucide="lock" size="18"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required style="padding-right: 3rem;">
                    <button type="button" onclick="togglePassword('password', this)" title="Toggle password visibility" style="position:absolute;right:1rem;background:none;border:none;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;">
                        <span class="pw-eye-off" style="display:inline-flex;"><i data-lucide="eye-off" size="18"></i></span>
                        <span class="pw-eye" style="display:none;"><i data-lucide="eye" size="18"></i></span>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
                <a href="recovery.php" style="font-size: 0.875rem; color: var(--primary-color); text-decoration: none; font-weight: 500;">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary">
                Login to System <i data-lucide="arrow-right" size="18"></i>
            </button>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <p style="font-size: 0.875rem; color: var(--text-secondary);">Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Create one here</a></p>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/password-toggle.js"></script>
<?php include 'includes/footer.php'; ?>
