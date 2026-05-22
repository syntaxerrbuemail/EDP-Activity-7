<?php
session_start();
require_once 'includes/db.php';

// Auth check - only admin can manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'toggle_status') {
            $current_status = $_POST['current_status'];
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';
            
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            $_SESSION['toast'] = ['message' => "Account status updated to " . ucfirst($new_status), 'type' => 'success'];
        } 
        elseif ($action === 'delete') {
            // Check if not deleting self
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['toast'] = ['message' => "You cannot delete your own account.", 'type' => 'error'];
            } else {
                // The users table has a cascading foreign key to person, and we have triggers in the database
                // However, based on the SQL provided, the trigger `deleteClient` is for the `client` table.
                // The `users` table has: CONSTRAINT `fk_users_person` FOREIGN KEY (`person_id`) REFERENCES `person` (`person_id`) ON DELETE CASCADE
                // So deleting from `person` will delete from `users`. 
                // But if we delete from `users`, we should probably also clean up the person record if it's not needed.
                
                // Fetch person_id first
                $stmt = $conn->prepare("SELECT person_id FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $person_id = $user['person_id'];
                    
                    // Simply delete the user account. 
                    // We DO NOT delete the person record because they might be a client or beneficiary.
                    // Deleting the user account revokes their system access.
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $_SESSION['toast'] = ['message' => "Account deleted successfully.", 'type' => 'success'];
                }
            }
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['toast'] = ['message' => "Action failed: " . $e->getMessage(), 'type' => 'error'];
    }
    
    header("Location: user_list.php");
    exit();
}

header("Location: user_list.php");
exit();
?>
