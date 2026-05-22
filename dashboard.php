<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats using existing tables
try {
    $clientCount = $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();
    $beneCount = $pdo->query("SELECT COUNT(*) FROM beneficiary")->fetchColumn();
    $totalPeople = $pdo->query("SELECT COUNT(*) FROM person")->fetchColumn();
    
    // Fetch recent clients using the existing view
    $recentClients = $pdo->query("SELECT * FROM client_records LIMIT 5")->fetchAll();
    
} catch (PDOException $e) {
    $error = "Failed to load dashboard data.";
}

?>

<div class="dashboard-container">
    <?php render_sidebar('dashboard'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                <p style="color: var(--text-secondary);">Here's what's happening in CAMS today.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; background: white; padding: 0.5rem 1rem; border-radius: 50px; box-shadow: var(--shadow);">
                <div style="background: var(--secondary-color); width: 10px; height: 10px; border-radius: 50%;"></div>
                <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);">System Online</span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Clients</h3>
                    <p><?php echo number_format($clientCount); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(39, 174, 96, 0.1); color: var(--accent-color);">
                    <i data-lucide="heart"></i>
                </div>
                <div class="stat-info">
                    <h3>Beneficiaries</h3>
                    <p><?php echo number_format($beneCount); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(45, 90, 39, 0.1); color: var(--primary-color);">
                    <i data-lucide="user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>System Access</h3>
                    <p><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 style="font-size: 1.25rem; color: var(--primary-color);">Recent Client Records</h2>
                <a href="reports.php" style="font-size: 0.875rem; color: var(--secondary-color); text-decoration: none; font-weight: 600;">View All</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>PhilHealth Num</th>
                            <th>Contact</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentClients as $client): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($client['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($client['last_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($client['philhealt_num']); ?></code></td>
                            <td><?php echo htmlspecialchars($client['contact_num']); ?></td>
                            <td>
                                <span style="background: #e9f7ef; color: #1e8449; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Active</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentClients)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No client records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
