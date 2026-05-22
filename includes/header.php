<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAMS - Client Aid Management System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
// Function to generate the sidebar/nav for dashboard pages
function render_sidebar($active_page = 'dashboard') {
    global $pdo;
    // Safety check: verify session user still exists in DB (crucial after database resets)
    if (isset($_SESSION['user_id'])) {
        $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmtCheck->execute([$_SESSION['user_id']]);
        if (!$stmtCheck->fetch()) {
            session_destroy();
            header("Location: login.php?error=session_expired");
            exit();
        }
    }
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <i data-lucide="shield-check" size="32"></i>
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 1.5rem; font-weight: 700; line-height: 1.2;">CAMS</span>
            <span style="font-size: 0.65rem; font-weight: 400; opacity: 0.8; letter-spacing: 0.02em;">Client Aid Management System</span>
        </div>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $active_page == 'reports' ? 'active' : ''; ?>">
                <i data-lucide="file-text"></i> Report Generator
            </a>
        </li>
        <li class="nav-item">
            <a href="add_client.php" class="nav-link <?php echo $active_page == 'add_client' ? 'active' : ''; ?>">
                <i data-lucide="user-plus"></i> Add Client
            </a>
        </li>
        <li class="nav-item">
            <a href="add_beneficiary.php" class="nav-link <?php echo $active_page == 'add_beneficiary' ? 'active' : ''; ?>">
                <i data-lucide="users"></i> Add Beneficiary
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_aid.php" class="nav-link <?php echo $active_page == 'manage_aid' ? 'active' : ''; ?>">
                <i data-lucide="heart-handshake"></i> Manage Aid
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo ($active_page == 'profile') ? 'active' : ''; ?>">
                <i data-lucide="user-cog"></i> <span>My Account</span>
            </a>
        </li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="user_list.php" class="nav-link <?php echo $active_page == 'users' ? 'active' : ''; ?>">
                <i data-lucide="shield-alert"></i> User Management
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="about.php" class="nav-link <?php echo $active_page == 'about' ? 'active' : ''; ?>">
                <i data-lucide="info"></i> About Program
            </a>
        </li>
        <li class="nav-item" style="margin-top: 2rem;">
            <a href="logout.php" class="nav-link" style="color: #feb2b2;" onclick="event.preventDefault(); Swal.fire({title: 'Logout?', text: 'Are you sure you want to log out?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, logout!'}).then((result) => { if (result.isConfirmed) { window.location.href = 'logout.php'; } });">
                <i data-lucide="log-out"></i> Logout
            </a>
        </li>
    </ul>
</div>
<?php
}
?>
