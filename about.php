<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<div class="dashboard-container">
    <?php render_sidebar('about'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>About the Program</h1>
                <p style="color: var(--text-secondary);">Learn more about the Client Aid Management System (CAMS).</p>
            </div>
        </header>

        <div class="card" style="padding: 3rem; max-width: 900px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 3rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background: rgba(45, 90, 39, 0.1); border-radius: 20px; color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i data-lucide="shield-check" size="48"></i>
                </div>
                <h2 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">CAMS v1.0</h2>
                <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                    The Client Aid Management System is a comprehensive platform designed to streamline the delivery of social services and financial assistance to citizens in need.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                <div style="padding: 1.5rem; background: var(--bg-color); border-radius: 1rem; border: 1px solid var(--border-color);">
                    <h3 style="display: flex; align-items: center; gap: 0.75rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i data-lucide="target" size="20"></i> Mission
                    </h3>
                    <p style="font-size: 0.9375rem; color: var(--text-primary);">
                        To provide an efficient, transparent, and data-driven approach to distributing government aid, ensuring that resources reach the right individuals at the right time.
                    </p>
                </div>
                <div style="padding: 1.5rem; background: var(--bg-color); border-radius: 1rem; border: 1px solid var(--border-color);">
                    <h3 style="display: flex; align-items: center; gap: 0.75rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i data-lucide="eye" size="20"></i> Vision
                    </h3>
                    <p style="font-size: 0.9375rem; color: var(--text-primary);">
                        A society where social assistance is accessible, well-managed, and impactful, fostering a more inclusive and supportive community for all residents.
                    </p>
                </div>
            </div>

            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem;">
                Core Features
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="color: var(--secondary-color);"><i data-lucide="users-2"></i></div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Client & Beneficiary Tracking</h4>
                        <p style="font-size: 0.875rem; color: var(--text-secondary);">Maintain high-fidelity records of all clients and their linked beneficiaries with full demographic data.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="color: var(--secondary-color);"><i data-lucide="database"></i></div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Relational Data Integrity</h4>
                        <p style="font-size: 0.875rem; color: var(--text-secondary);">Advanced transaction management ensures addresses, birthplaces, and personal records are perfectly synced.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="color: var(--secondary-color);"><i data-lucide="file-bar-chart"></i></div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Advanced Reporting</h4>
                        <p style="font-size: 0.875rem; color: var(--text-secondary);">Generate detailed PDF or CSV reports with dynamic filtering, sorting, and classification capabilities.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="color: var(--secondary-color);"><i data-lucide="map"></i></div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Geographic Analytics</h4>
                        <p style="font-size: 0.875rem; color: var(--text-secondary);">Powered by PSGC Cloud API for accurate regional, provincial, and barangay-level categorization.</p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-secondary); font-size: 0.875rem;">
                <p>&copy; <?php echo date('Y'); ?> Client Aid Management System. All rights reserved.</p>
                <p style="margin-top: 0.5rem; font-weight: 500;">Version 1.0.4 - Premium Edition</p>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>
