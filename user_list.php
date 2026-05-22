<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Auth check - only admin can manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    $query = "SELECT u.user_id, u.username, u.email, u.role, u.status, p.first_name, p.last_name, p.person_id 
              FROM users u
              JOIN person p ON u.person_id = p.person_id
              WHERE 1=1";
    
    $params = [];
    
    if ($search) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status_filter) {
        $query .= " AND u.status = ?";
        $params[] = $status_filter;
    }
    
    $query .= " ORDER BY u.user_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Failed to load users: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <?php render_sidebar('users'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>User Management</h1>
                <p style="color: var(--text-secondary);">Manage administrative accounts and system access.</p>
            </div>
            <a href="user_add.php" class="btn btn-primary" style="width: auto; padding: 0.75rem 1.5rem;">
                <i data-lucide="user-plus"></i> Add Account
            </a>
        </header>

        <?php if (isset($_SESSION['toast'])): ?>
            <div class="alert alert-<?php echo $_SESSION['toast']['type'] === 'success' ? 'success' : 'error'; ?>" style="margin-bottom: 2rem; <?php echo $_SESSION['toast']['type'] === 'success' ? 'background: #e9f7ef; color: #1e8449; border: 1px solid #d1e2d0;' : ''; ?>">
                <i data-lucide="<?php echo $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'alert-circle'; ?>"></i> 
                <?php echo $_SESSION['toast']['message']; ?>
            </div>
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
            <form action="user_list.php" method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Search Users</label>
                    <div class="input-wrapper">
                        <i data-lucide="search" size="18"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, username or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="form-group" style="width: 200px; margin-bottom: 0;">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: auto; height: 46px;">Filter</button>
                <?php if ($search || $status_filter): ?>
                    <a href="user_list.php" class="btn btn-secondary" style="width: auto; height: 46px; display: flex; align-items: center; background: #f3f4f6;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                <i data-lucide="users" size="48" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>No users found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">ID: #<?php echo $user['user_id']; ?></div>
                                </td>
                                <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span style="text-transform: capitalize; font-size: 0.875rem;"><?php echo htmlspecialchars($user['role']); ?></span>
                                </td>
                                <td>
                                    <?php if ($user['status'] == 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-error" style="background: #fee2e2; color: #991b1b;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <form action="user_action.php" method="POST" class="status-form" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                            <button type="submit" class="btn" style="width: auto; padding: 0.4rem; background: var(--bg-color); color: var(--primary-color);" title="<?php echo $user['status'] == 'active' ? 'Deactivate' : 'Activate'; ?> Account">
                                                <i data-lucide="<?php echo $user['status'] == 'active' ? 'user-minus' : 'user-check'; ?>" size="18"></i>
                                            </button>
                                        </form>
                                        
                                        <a href="profile.php?id=<?php echo $user['user_id']; ?>" class="btn" style="width: auto; padding: 0.4rem; background: var(--bg-color); color: #3b82f6;" title="Edit Profile">
                                            <i data-lucide="edit-3" size="18"></i>
                                        </a>

                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form action="user_action.php" method="POST" class="delete-form" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn" style="width: auto; padding: 0.4rem; background: #fee2e2; color: #991b1b;" title="Delete Account">
                                                <i data-lucide="trash-2" size="18"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
    document.querySelectorAll('.status-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const { isConfirmed } = await Swal.fire({
                title: 'Change Status?',
                text: 'Are you sure you want to change this account\'s status?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'swal-confirm-custom'
                }
            });
            if (isConfirmed) this.submit();
        });
    });

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const { isConfirmed } = await Swal.fire({
                title: 'Delete Account?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'swal-confirm-custom'
                }
            });
            if (isConfirmed) this.submit();
        });
    });
</script>
<?php include 'includes/footer.php'; ?>

