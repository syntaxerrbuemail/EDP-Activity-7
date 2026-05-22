// ==========================================
// EDP Activity 7 - Collaborative Update
// Contributed by: Collaborator (Email2 -> arvieDK)
// Index.php Purpose: Primary entry point for CAMS.
// ==========================================

<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
