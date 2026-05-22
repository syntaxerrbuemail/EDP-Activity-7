<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'All';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle Grant/Decline/Reset Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $bene_id = $_GET['id'];
    $amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
    $remarks = isset($_GET['remarks']) ? trim($_GET['remarks']) : '';
    $status = ($action === 'grant') ? 'Granted' : (($action === 'decline') ? 'Declined' : 'Pending');
    
    try {
        if ($action === 'grant') {
            $stmt = $pdo->prepare("UPDATE beneficiary SET aid_status = ?, aid_amount = ?, status_remarks = ? WHERE beneficiary_id = ?");
            $stmt->execute([$status, $amount, $remarks, $bene_id]);
        } elseif ($action === 'decline') {
            $stmt = $pdo->prepare("UPDATE beneficiary SET aid_status = ?, aid_amount = 0, status_remarks = ? WHERE beneficiary_id = ?");
            $stmt->execute([$status, $remarks, $bene_id]);
        } else {
            // Reset
            $stmt = $pdo->prepare("UPDATE beneficiary SET aid_status = 'Pending', aid_amount = 0, status_remarks = NULL WHERE beneficiary_id = ?");
            $stmt->execute([$bene_id]);
        }
        $success = "Aid request successfully " . strtolower($status) . "!";
    } catch (PDOException $e) {
        $error = "Failed to update status: " . $e->getMessage();
    }
}

// Fetch beneficiaries
$beneficiaries = [];
try {
    $sql = "SELECT b.beneficiary_id, b.category, b.subcategory, b.aid_status, b.aid_description, b.aid_amount, b.status_remarks,
                   p.first_name, p.last_name,
                   concat(cl_p.first_name, ' ', cl_p.last_name) as client_name
            FROM beneficiary b
            JOIN person p ON p.person_id = b.beneficiary_id
            JOIN client c ON c.client_id = b.client_id
            JOIN person cl_p ON cl_p.person_id = c.client_id
            WHERE 1=1";
    
    $params = [];
    if ($filterStatus !== 'All') {
        $sql .= " AND b.aid_status = ?";
        $params[] = $filterStatus;
    }
    if (!empty($search)) {
        $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR concat(cl_p.first_name, ' ', cl_p.last_name) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY FIELD(b.aid_status, 'Pending', 'Granted', 'Declined'), p.last_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $beneficiaries = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load beneficiaries: " . $e->getMessage();
}
?>

<script>
function calculateAge(birthdate) {
    const birthDate = new Date(birthdate);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

async function viewDetails(personId, highlightBeneficiary = false) {
    Swal.fire({
        title: 'Loading Master Record...',
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch(`fetch_person_details.php?id=${personId}`);
        const data = await response.json();

        if (data.error) throw new Error(data.error);

        const { person, client, beneficiary } = data;
        const age = calculateAge(person.birthdate);
        
        let statusStyles = {
            'Granted': { bg: '#f0fdf4', border: '#bbf7d0', text: '#166534', sub: '#bbf7d0', badge: 'badge-success' },
            'Declined': { bg: '#fef2f2', border: '#fecaca', text: '#991b1b', sub: '#fecaca', badge: 'badge-danger' },
            'Pending': { bg: '#fffbeb', border: '#fef3c7', text: '#92400e', sub: '#fef3c7', badge: 'badge-warning' }
        };
        
        let status = beneficiary ? beneficiary.aid_status : 'Pending';
        let style = statusStyles[status] || statusStyles['Pending'];

        let html = `<div style="text-align: left;">`;

        if (client) {
            html += `
                <div style="background: var(--primary-color); color: white; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(45, 90, 39, 0.2);">
                    <div style="font-size: 0.7rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase; opacity: 0.85;">PHILHEALTH ID NUMBER</div>
                    <div style="font-size: 1.1rem; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 0.05em;">${client.philhealt_num}</div>
                </div>
            `;
        }

        html += `
                <div style="margin-bottom: 2rem; padding: 0 0.5rem;">
                    <h4 style="color: var(--primary-color); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1.5px solid #dcfce7; padding-bottom: 0.75rem;">
                        <i data-lucide="user" style="width: 16px; height: 16px;"></i> PERSONAL INFORMATION
                    </h4>
                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Full Legal Name</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: #1e293b;">${person.first_name} ${person.middle_name ? person.middle_name + ' ' : ''}${person.last_name}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Current Age</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: #1e293b;">${age} Years Old</div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 2rem; padding: 0 0.5rem;">
                    <h4 style="color: var(--primary-color); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1.5px solid #dcfce7; padding-bottom: 0.75rem;">
                        <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i> RESIDENTIAL ADDRESS
                    </h4>
                    <div style="font-weight: 600; font-size: 1rem; color: #1e293b;">${person.barangay}, ${person.addr_municipality}, ${person.addr_province}</div>
                </div>

                <div style="margin-bottom: 2rem; padding: 0 0.5rem;">
                    <h4 style="color: var(--primary-color); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1.5px solid #dcfce7; padding-bottom: 0.75rem;">
                        <i data-lucide="briefcase" style="width: 16px; height: 16px;"></i> ECONOMIC PROFILE
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Current Occupation</div>
                            <div style="font-weight: 700; font-size: 1rem; color: #1e293b;">${person.occupation}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Estimated Monthly Income</div>
                            <div style="font-weight: 800; font-size: 1.2rem; color: var(--secondary-color);">PHP ${parseFloat(person.estimated_monthly_income).toLocaleString()}</div>
                        </div>
                    </div>
                </div>
        `;

        if (beneficiary) {
            const highlightClass = highlightBeneficiary ? 'animate-pulse-focus' : '';
            html += `
                <div id="bene-section" class="${highlightClass}" style="background: ${style.bg}; padding: 1.75rem; border-radius: 16px; border: 2px solid ${highlightBeneficiary ? 'var(--primary-color)' : style.border}; margin-top: 1rem; transition: all 0.5s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <h4 style="color: ${style.text}; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; display: flex; align-items: center; gap: 0.75rem;">
                            <i data-lucide="handshake" style="width: 18px; height: 18px;"></i> BENEFICIARY ASSISTANCE RECORD
                        </h4>
                        <div style="text-align: right;">
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.25rem;">Beneficiary Contact</div>
                            <div style="font-weight: 800; font-size: 1.1rem; color: ${style.text};">${beneficiary.client_contact_num || '---'}</div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem;">
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Aid Classification</div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: ${style.text};">${beneficiary.category} / ${beneficiary.subcategory}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Linked Primary Client</div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: ${style.text};">${beneficiary.client_name} (${beneficiary.client_relationship})</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.25rem; background: rgba(255,255,255,0.7); border-radius: 12px; border: 1px dashed ${style.border}; margin-bottom: 1.25rem;">
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Processing Status</div>
                            <span class="datagrid-badge ${style.badge}" style="padding: 0.35rem 1rem;">${beneficiary.aid_status}</span>
                        </div>
                        ${beneficiary.aid_amount > 0 ? `
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Granted Amount</div>
                            <div style="font-weight: 900; font-size: 1.35rem; color: ${style.text};">PHP ${parseFloat(beneficiary.aid_amount).toLocaleString()}</div>
                        </div>` : ''}
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Detailed Reason</div>
                        <div style="font-size: 0.95rem; color: ${style.text}; line-height: 1.5; font-style: italic;">"${beneficiary.aid_description}"</div>
                    </div>

                    ${beneficiary.status_remarks ? `
                    <div style="padding-top: 1.5rem; border-top: 1.2px dashed ${style.border}; margin-top: 1rem;">
                        <div style="font-size: 0.65rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Audit Status Remarks</div>
                        <div style="font-size: 0.95rem; color: ${style.text}; line-height: 1.5; font-weight: 700; border-left: 3px solid ${style.text}; padding-left: 0.75rem;">
                            "${beneficiary.status_remarks}"
                        </div>
                    </div>` : ''}
                </div>
            `;
        }

        html += `</div>`;

        Swal.fire({
            title: '<div style="font-size: 0.65rem; color: #64748b; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; margin-bottom: 0.65rem; opacity: 0.8;">Administrative Reference</div>' + 
                   '<div style="color: var(--primary-color); font-size: 1.75rem; font-weight: 900; letter-spacing: -0.01em;">MASTER DATABASE FILE</div>',
            html: html,
            width: '740px',
            confirmButtonText: 'CLOSE RECORD',
            confirmButtonColor: 'var(--primary-color)',
            padding: '3rem',
            customClass: { popup: 'premium-modal' },
            didRender: () => { 
                lucide.createIcons();
                if (highlightBeneficiary) {
                    const el = document.getElementById('bene-section');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

    } catch (error) {
        Swal.fire('Data Access Error', 'Unable to retrieve master record.', 'error');
    }
}
</script>

<style>
@keyframes pulse-focus {
    0% { box-shadow: 0 0 0 0 rgba(45, 90, 39, 0.4); }
    70% { box-shadow: 0 0 0 15px rgba(45, 90, 39, 0); }
    100% { box-shadow: 0 0 0 0 rgba(45, 90, 39, 0); }
}
.animate-pulse-focus {
    animation: pulse-focus 2s infinite;
}
.manage-row:hover td {
    background-color: #f8fafc !important;
}
.btn-reset {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.6rem;
    border-radius: 6px;
    transition: var(--transition);
}
.btn-reset:hover {
    background-color: #f1f5f9;
    color: var(--primary-color);
}
.swal-confirm-custom {
    background-color: #ef4444 !important; /* Red */
    color: white !important;
    transition: background-color 0.3s ease !important;
}
.swal-confirm-custom:hover {
    background-color: #22c55e !important; /* Green */
}
</style>

<div class="dashboard-container">
    <?php render_sidebar('manage_aid'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1 style="font-weight: 800; font-size: 2rem; color: var(--primary-color); letter-spacing: -0.02em;">Manage Aid Requests</h1>
                <p style="color: var(--text-secondary); font-weight: 500;">Review and process aid applications from beneficiaries.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <script>window.onload = () => showToast("<?php echo $error; ?>", "error");</script>
        <?php endif; ?>
        <?php if ($success): ?>
            <script>window.onload = () => showToast("<?php echo $success; ?>", "success");</script>
        <?php endif; ?>

        <form action="manage_aid.php" method="GET" class="filter-bar" style="margin-bottom: 2rem;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary);">Search Beneficiary/Client</label>
                <div class="input-wrapper">
                    <i data-lucide="search" size="18"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 3rem;">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary);">Filter Status</label>
                <div class="input-wrapper">
                    <i data-lucide="filter" size="18"></i>
                    <select name="status" class="form-control" style="padding-left: 3rem;" onchange="this.form.submit()">
                        <option value="All" <?php echo $filterStatus == 'All' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo $filterStatus == 'Pending' ? 'selected' : ''; ?>>Pending Only</option>
                        <option value="Granted" <?php echo $filterStatus == 'Granted' ? 'selected' : ''; ?>>Granted Only</option>
                        <option value="Declined" <?php echo $filterStatus == 'Declined' ? 'selected' : ''; ?>>Declined Only</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 1.5rem; height: 48px;">Search</button>
                <a href="manage_aid.php" class="btn" style="width: 48px; height: 48px; background: #f1f5f9; color: #64748b;" title="Reset Filters"><i data-lucide="refresh-cw" size="20"></i></a>
            </div>
        </form>

        <div class="datagrid-container">
            <div class="datagrid-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary);">Beneficiary Aid Processing</h2>
                    <span style="background: #f0fdf4; color: #166534; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid #bbf7d0;">
                        <?php echo count($beneficiaries); ?> Active Requests
                    </span>
                </div>
            </div>
            <div class="datagrid-wrapper">
                <table class="datagrid">
                    <thead>
                        <tr>
                            <th>Beneficiary</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Status Remarks</th>
                            <th>Status</th>
                            <th style="text-align: center;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beneficiaries as $b): ?>
                        <tr class="manage-row" onclick="viewDetails(<?php echo $b['beneficiary_id']; ?>, true)" style="cursor: pointer;" title="Click to view full history and highlight assistance details">
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars($b['last_name'] . ', ' . $b['first_name']); ?>
                                <div style="font-size: 0.7rem; font-weight: 400; color: var(--text-secondary);"><?php echo htmlspecialchars($b['aid_description']); ?></div>
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($b['category'] . ' > ' . $b['subcategory']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: <?php echo $b['aid_amount'] > 0 ? 'var(--secondary-color)' : 'var(--text-secondary)'; ?>;">
                                <?php echo $b['aid_amount'] > 0 ? 'PHP ' . number_format($b['aid_amount'], 2) : '---'; ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary); font-style: italic; max-width: 200px;">
                                <?php echo $b['status_remarks'] ? htmlspecialchars($b['status_remarks']) : '<span style="opacity: 0.5;">No remarks</span>'; ?>
                            </td>
                            <td>
                                <?php 
                                $statusClass = 'badge-warning'; // Default Yellow for Pending
                                if ($b['aid_status'] === 'Granted') $statusClass = 'badge-success'; // Green
                                if ($b['aid_status'] === 'Declined') $statusClass = 'badge-danger'; // Red
                                ?>
                                <span class="datagrid-badge <?php echo $statusClass; ?>">
                                    <?php echo $b['aid_status']; ?>
                                </span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <?php if ($b['aid_status'] === 'Pending'): ?>
                                        <button onclick="processAid('grant', <?php echo $b['beneficiary_id']; ?>)" class="btn btn-grant">
                                            <i data-lucide="check-circle" size="14"></i> Grant
                                        </button>
                                        <button onclick="processAid('decline', <?php echo $b['beneficiary_id']; ?>)" class="btn btn-decline">
                                            <i data-lucide="x-circle" size="14"></i> Decline
                                        </button>
                                    <?php else: ?>
                                        <button onclick="confirmReset(<?php echo $b['beneficiary_id']; ?>)" class="btn-reset">
                                            <i data-lucide="rotate-ccw" size="14"></i> Reset
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
async function processAid(action, id) {
    if (action === 'grant') {
        const { value: amount } = await Swal.fire({
            title: 'Grant Assistance',
            text: 'Enter the amount to be granted.',
            input: 'number',
            inputLabel: 'Amount (PHP)',
            showCancelButton: true,
            confirmButtonColor: 'var(--secondary-color)',
            inputValidator: (value) => { if (!value || value <= 0) return 'Please enter a valid amount!' }
        });

        if (amount) {
            const { value: remarks } = await Swal.fire({
                title: 'Grant Remarks',
                text: 'Enter a reason or description for granting this aid.',
                input: 'textarea',
                inputPlaceholder: 'e.g. Approved based on financial assessment...',
                showCancelButton: true,
                confirmButtonColor: 'var(--secondary-color)',
                inputValidator: (value) => { if (!value) return 'Remarks are required!' }
            });

            if (remarks) {
                window.location.href = `manage_aid.php?status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>&action=grant&id=${id}&amount=${amount}&remarks=${encodeURIComponent(remarks)}`;
            }
        }
    } else if (action === 'decline') {
        const { value: remarks } = await Swal.fire({
            title: 'Decline Reason',
            text: 'Provide a reason for declining this request.',
            input: 'textarea',
            inputPlaceholder: 'e.g. Incomplete documentation or over income limit...',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            inputValidator: (value) => { if (!value) return 'A reason is required!' }
        });

        if (remarks) {
            window.location.href = `manage_aid.php?status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>&action=decline&id=${id}&remarks=${encodeURIComponent(remarks)}`;
        }
    }
}

async function confirmReset(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Reset Aid Status?',
        text: 'This will revert the request back to PENDING and clear all amount/remarks. Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reset Status',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'swal-confirm-custom'
        }
    });

    if (isConfirmed) {
        window.location.href = `manage_aid.php?action=reset&id=${id}&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>`;
    }
}
</script>

<style>
.btn-grant {
    padding: 0.4rem 0.8rem; 
    font-size: 0.75rem; 
    width: auto; 
    background: #f0fdf4; 
    color: #166534; 
    border: 1px solid #bbf7d0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-grant:hover {
    background: #22c55e !important;
    color: white !important;
    border-color: #22c55e !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-decline {
    padding: 0.4rem 0.8rem; 
    font-size: 0.75rem; 
    width: auto; 
    background: #fef2f2; 
    color: #991b1b; 
    border: 1px solid #fecaca;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-decline:hover {
    background: #ef4444 !important;
    color: white !important;
    border-color: #ef4444 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-reset {
    padding: 0.4rem 0.8rem; 
    font-size: 0.75rem; 
    width: auto; 
    background: #f1f5f9; 
    color: #475569; 
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-reset:hover {
    background: #475569 !important;
    color: white !important;
    border-color: #475569 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>

<?php include 'includes/footer.php'; ?>
