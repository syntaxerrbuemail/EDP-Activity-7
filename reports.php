<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reportType = isset($_GET['type']) ? $_GET['type'] : 'clients';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'last_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$data = [];

// Fetch current user occupation for the signature
$userOccupation = "Staff"; // Default
try {
    $stmtUser = $pdo->prepare("SELECT p.occupation FROM person p JOIN users u ON u.person_id = p.person_id WHERE u.user_id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $uRow = $stmtUser->fetch();
    if ($uRow) $userOccupation = $uRow['occupation'];
} catch (PDOException $e) { /* ignore */ }

try {
    if ($reportType === 'clients') {
        $sql = "SELECT p.*, c.philhealt_num, 
                a.region, a.province, a.municipality, a.district, a.barangay
                FROM person p 
                JOIN client c ON c.client_id = p.person_id
                JOIN address a ON a.address_id = p.address_id";
    } else {
        $sql = "SELECT p.*, b.category, b.subcategory, b.client_relationship, b.aid_description, b.aid_status, b.aid_amount, b.status_remarks,
                concat(cl_person.first_name, ' ', cl_person.last_name) AS client_name 
                FROM person p 
                JOIN beneficiary b ON b.beneficiary_id = p.person_id 
                JOIN client c ON c.client_id = b.client_id 
                JOIN person cl_person ON cl_person.person_id = c.client_id";
        
        if ($reportType === 'granted') {
            $sql .= " WHERE b.aid_status = 'Granted'";
        } elseif ($reportType === 'declined') {
            $sql .= " WHERE b.aid_status = 'Declined'";
        }
    }

    $params = [];
    if (!empty($search)) {
        $prefix = (strpos($sql, 'WHERE') === false) ? " WHERE " : " AND ";
        $sql .= $prefix . "(p.first_name LIKE ? OR p.last_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        
        if ($reportType !== 'clients') {
            $sql .= " OR concat(cl_person.first_name, ' ', cl_person.last_name) LIKE ?";
            $params[] = "%$search%";
        }
    }

    $allowedSort = ['last_name', 'first_name', 'category', 'philhealt_num'];
    if (!in_array($sort, $allowedSort)) $sort = 'last_name';
    if ($order !== 'DESC') $order = 'ASC';
    
    $sql .= " ORDER BY $sort $order";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load report data: " . $e->getMessage();
}

function getAge($birthdate) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    return $today->diff($birthDate)->y;
}

$analytics = [];
if ($reportType === 'clients') {
    foreach ($data as $row) {
        $age = getAge($row['birthdate']);
        $income = (float)$row['estimated_monthly_income'];
        $analytics[] = ['x' => $age, 'y' => $income];
    }
} else {
    foreach ($data as $row) {
        $cat = $row['category'] ?: 'Uncategorized';
        $sub = $row['subcategory'] ?: 'No Subcategory';
        $key = "$cat > $sub";
        $analytics[$key] = ($analytics[$key] ?? 0) + 1;
    }
}
?>

<!-- Move script up to ensure it's defined -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

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

async function viewDetails(personId) {
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
                    <div style="display: grid; grid-template-columns: 0.8fr 0.6fr 0.6fr; gap: 1.5rem; margin-top: 1.25rem;">
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Date of Birth</div>
                            <div style="font-weight: 600; font-size: 1rem; color: #1e293b;">${person.birthdate}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Sex</div>
                            <div style="font-weight: 600; font-size: 1rem; color: #1e293b;">${person.sex}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.65rem; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem;">Civil Status</div>
                            <div style="font-weight: 600; font-size: 1rem; color: #1e293b;">${person.civil_status}</div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 2rem; padding: 0 0.5rem;">
                    <h4 style="color: var(--primary-color); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1.5px solid #dcfce7; padding-bottom: 0.75rem;">
                        <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i> RESIDENTIAL ADDRESS
                    </h4>
                    <div style="font-weight: 600; font-size: 1rem; color: #1e293b;">${person.barangay}, ${person.addr_municipality}, ${person.addr_province}</div>
                    <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">${person.district || 'District N/A'}, ${person.region}</div>
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
            html += `
                <div style="background: ${style.bg}; padding: 1.75rem; border-radius: 16px; border: 2px solid ${style.border}; margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <h4 style="color: ${style.text}; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; display: flex; align-items: center; gap: 0.75rem;">
                            <i data-lucide="handshake" style="width: 18px; height: 18px;"></i> BENEFICIARY INFORMATION
                        </h4>
                        <div style="text-align: right;">
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.25rem;">Beneficiary Contact</div>
                            <div style="font-weight: 800; font-size: 1.1rem; color: ${style.text};">${beneficiary.client_contact_num || '---'}</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Aid Classification</div>
                            <div style="font-weight: 700; font-size: 1rem; color: ${style.text};">${beneficiary.category} / ${beneficiary.subcategory}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Linked Primary Client</div>
                            <div style="font-weight: 700; font-size: 1rem; color: ${style.text};">${beneficiary.client_name} (${beneficiary.client_relationship})</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.25rem; background: rgba(255,255,255,0.7); border-radius: 12px; border: 1px dashed ${style.border}; margin-bottom: 1.5rem;">
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Aid Processing Status</div>
                            <span class="datagrid-badge ${style.badge}" style="padding: 0.35rem 1rem;">${beneficiary.aid_status}</span>
                        </div>
                        ${beneficiary.aid_amount > 0 ? `
                        <div>
                            <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Total Granted Amount</div>
                            <div style="font-weight: 900; font-size: 1.35rem; color: ${style.text};">PHP ${parseFloat(beneficiary.aid_amount).toLocaleString()}</div>
                        </div>` : ''}
                    </div>

                    <div>
                        <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.35rem;">Reason for Assistance</div>
                        <div style="font-size: 0.95rem; color: ${style.text}; line-height: 1.5; font-style: italic; opacity: 0.9;">"${beneficiary.aid_description}"</div>
                    </div>

                    ${beneficiary.status_remarks ? `
                    <div style="padding-top: 1.5rem; border-top: 1.2px solid ${style.border}; margin-top: 1.5rem;">
                        <div style="font-size: 0.6rem; color: ${style.text}; font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem; opacity: 0.8;">Administrator Audit Remarks</div>
                        <div style="font-size: 0.95rem; color: ${style.text}; line-height: 1.5; font-weight: 700;">"${beneficiary.status_remarks}"</div>
                    </div>` : ''}
                </div>
            `;
        }

        html += `
            <div style="margin-top: 2.5rem; display: flex; gap: 1rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9;">
                <button onclick="confirmEditRecord(${personId}, '${beneficiary ? 'beneficiary' : 'client'}')" 
                   class="btn-edit-popup" style="flex: 1; background: var(--secondary-color); color: white; border: none; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-radius: 12px; height: 48px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s ease;">
                    <i data-lucide="edit-3" style="width: 18px; height: 18px;"></i> EDIT RECORD
                </button>
                <button onclick="confirmDeleteRecord(${personId}, '${beneficiary ? 'beneficiary' : 'client'}')" 
                   class="btn-delete-popup" style="flex: 1; background: #fee2e2; color: #dc2626; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-radius: 12px; height: 48px; border: 1.5px solid #fecaca; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease;">
                    <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i> DELETE RECORD
                </button>
            </div>
        `;

        html += `</div>`;

        Swal.fire({
            title: '<div style="font-size: 0.65rem; color: #64748b; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; margin-bottom: 0.65rem; opacity: 0.8;">Administrative Reference</div>' + 
                   '<div style="color: var(--primary-color); font-size: 1.75rem; font-weight: 900; letter-spacing: -0.01em;">MASTER DATABASE FILE</div>',
            html: html,
            width: '740px',
            confirmButtonText: 'CLOSE MASTER RECORD',
            confirmButtonColor: 'var(--primary-color)',
            padding: '3rem',
            customClass: { popup: 'premium-modal' },
            didRender: () => { lucide.createIcons(); }
        });

    } catch (error) {
        Swal.fire('Data Access Error', 'The master record could not be securely retrieved.', 'error');
    }
}

async function confirmEditRecord(id, type) {
    const { isConfirmed } = await Swal.fire({
        title: 'Edit Master Record?',
        text: `You will be securely redirected to the information management module for this ${type}.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: 'var(--secondary-color)',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Open Editor',
        cancelButtonText: 'Cancel'
    });

    if (isConfirmed) {
        window.location.href = `reports_edit_${type}.php?id=${id}`;
    }
}

async function confirmDeleteRecord(id, type) {
    const { isConfirmed } = await Swal.fire({
        title: '<div style="color: #dc2626;">Confirm Permanent Deletion</div>',
        html: `<div style="text-align: center; color: #64748b; font-weight: 500;">
                ${type === 'client' 
                    ? '<strong>WARNING:</strong> Deleting this client will automatically purge all linked beneficiaries and related personal data. This action is <strong>irreversible</strong>.' 
                    : 'This will permanently remove the beneficiary and their related personal information from the master database.'}
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete Permanently',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'swal-confirm-custom'
        }
    });

    if (isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);
            
            const response = await fetch('delete_record.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'The record has been purged from the database.',
                    icon: 'success',
                    confirmButtonColor: 'var(--primary-color)'
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(result.error);
            }
        } catch (e) {
            Swal.fire('System Error', 'Deletion process failed: ' + e.message, 'error');
        }
    }
}

async function confirmExcelExport() {
    const { isConfirmed } = await Swal.fire({
        title: 'Export to Excel?',
        text: 'This will generate and download a comprehensive report based on the current data view.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--secondary-color)',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Export Now',
        cancelButtonText: 'Cancel'
    });

    if (isConfirmed) {
        generateExcelReport();
    }
}

async function generateExcelReport() {
    Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we prepare your Excel file.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const reportType = '<?php echo $reportType; ?>';
    const userName = '<?php echo $_SESSION['full_name']; ?>';
    const userOccupation = '<?php echo $userOccupation; ?>';
    const analyticsData = <?php echo json_encode($analytics); ?>;
    
    const workbook = new ExcelJS.Workbook();
    const sheet1 = workbook.addWorksheet('Report Data');
    const sheet2 = workbook.addWorksheet('Data Analytics');

    try {
        const response = await fetch('assets/images/logo.png');
        const buffer = await response.arrayBuffer();
        const logoId = workbook.addImage({ buffer: buffer, extension: 'png' });
        sheet1.addImage(logoId, { tl: { col: 0.1, row: 0.1 }, ext: { width: 120, height: 120 } });
    } catch(e) {}

    sheet1.mergeCells('B2:F2');
    const companyName = sheet1.getCell('B2');
    companyName.value = 'CLIENT AID MANAGEMENT SYSTEM (CAMS)';
    companyName.font = { name: 'Arial Black', size: 18, color: { argb: 'FF2D5A27' } };
    companyName.alignment = { vertical: 'middle', horizontal: 'left' };

    sheet1.mergeCells('B3:F3');
    const reportTitle = sheet1.getCell('B3');
    let titleText = 'CLIENT MASTERLIST REPORT';
    if (reportType === 'granted') titleText = 'GRANTED AID RECORDS REPORT';
    else if (reportType === 'declined') titleText = 'DECLINED AID RECORDS REPORT';
    else if (reportType === 'beneficiaries') titleText = 'BENEFICIARY MASTERLIST REPORT';
    reportTitle.value = titleText;
    reportTitle.font = { name: 'Arial', size: 14, bold: true };

    sheet1.mergeCells('B4:F4');
    sheet1.getCell('B4').value = 'Generated on: ' + new Date().toLocaleString();
    sheet1.getCell('B4').font = { size: 10, italic: true };

    const startRow = 8;
    let tableHeaders = [];
    if (reportType === 'beneficiaries') {
        tableHeaders = ['Full Name', 'Category', 'Subcategory', 'Aid Description', 'Relationship', 'Linked Client'];
    } else if (reportType === 'granted' || reportType === 'declined') {
        tableHeaders = ['Full Name', 'Status', 'Amount', 'Status Remarks', 'Category', 'Subcategory', 'Aid Description', 'Relationship', 'Linked Client'];
    } else {
        tableHeaders = ['Full Name', 'Age', 'Monthly Income', 'PhilHealth Number', 'Complete Address'];
    }

    const headerRow = sheet1.getRow(startRow);
    tableHeaders.forEach((h, i) => {
        const cell = headerRow.getCell(i + 1);
        cell.value = h;
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF2D5A27' } };
        cell.font = { color: { argb: 'FFFFFFFF' }, bold: true };
        cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
    });

    const tableRows = [];
    document.querySelectorAll('#reportTable tbody tr').forEach(row => {
        if (row.cells.length < tableHeaders.length) return;
        const rowData = [];
        row.querySelectorAll('td').forEach(td => rowData.push(td.innerText.trim()));
        tableRows.push(rowData);
    });

    tableRows.forEach((data, i) => {
        const row = sheet1.getRow(startRow + 1 + i);
        data.forEach((val, j) => {
            const cell = row.getCell(j + 1);
            cell.value = val;
            cell.border = { style: 'thin' };
        });
    });

    sheet1.columns = tableHeaders.map((h, i) => {
        let width = 25;
        if (reportType === 'clients' && i === 4) width = 45;
        if (reportType === 'beneficiaries' && i === 3) width = 50;
        if ((reportType === 'granted' || reportType === 'declined')) {
            if (i === 3) width = 40;
            if (i === 6) width = 50;
        }
        return { width };
    });

    const signRowIndex = startRow + tableRows.length + 4;
    sheet1.getCell('A' + signRowIndex).value = 'Prepared by:';
    sheet1.getCell('A' + (signRowIndex + 3)).value = userName;
    sheet1.getCell('A' + (signRowIndex + 3)).font = { bold: true };
    sheet1.getCell('A' + (signRowIndex + 4)).value = userOccupation;
    sheet1.getCell('A' + (signRowIndex + 4)).font = { italic: true };
    
    sheet1.getCell('D' + signRowIndex).value = 'Noted by:';
    sheet1.getCell('D' + (signRowIndex + 2)).value = '__________________________';
    sheet1.getCell('D' + (signRowIndex + 3)).value = 'OFFICE HEAD';
    sheet1.getCell('D' + (signRowIndex + 3)).font = { bold: true };

    sheet2.getColumn(1).width = 25;
    sheet2.getColumn(2).width = 35;
    
    sheet2.getCell('A1').value = 'DATA ANALYTICS REPORT';
    sheet2.getCell('A1').font = { size: 18, bold: true, color: { argb: 'FF2D5A27' } };
    
    sheet2.mergeCells('A2:H3');
    const analyticsDesc = sheet2.getCell('A2');
    if (reportType === 'clients') {
        analyticsDesc.value = 'This analytics sheet displays the correlation between the age of clients and their estimated monthly income, helping to identify economic trends across different demographics.';
    } else {
        analyticsDesc.value = 'This analytics sheet visualizes the distribution of assistance requests across various categories and subcategories within the system.';
    }
    analyticsDesc.alignment = { wrapText: true, vertical: 'top' };
    analyticsDesc.font = { italic: true, size: 11 };

    const ctx = document.getElementById('exportChart').getContext('2d');
    let chartConfig = {};

    if (reportType === 'clients') {
        // Group data by unique Age/Income to find occurrences
        const groupedData = {};
        analyticsData.forEach(p => {
            const key = `${p.x}_${p.y}`;
            if (!groupedData[key]) {
                groupedData[key] = { x: p.x, y: p.y, count: 0 };
            }
            groupedData[key].count++;
        });
        const plotData = Object.values(groupedData);

        chartConfig = {
            type: 'scatter',
            plugins: [ChartDataLabels],
            data: {
                datasets: [{ 
                    label: 'Income vs Age Distribution', 
                    data: plotData, 
                    backgroundColor: '#2d5a27', 
                    showLine: false, 
                    borderColor: '#52be80', 
                    borderWidth: 2, 
                    pointRadius: 7,
                    pointHoverRadius: 9,
                    datalabels: {
                        align: 'top',
                        offset: 10,
                        backgroundColor: 'rgba(30, 41, 59, 0.8)',
                        borderRadius: 4,
                        color: 'white',
                        font: { size: 10, weight: 'bold' },
                        padding: 4,
                        formatter: function(value) {
                            return `PHP ${value.y.toLocaleString()}\n${value.x} yrs (${value.count})`;
                        }
                    }
                }]
            },
            options: { 
                responsive: false, 
                animation: false, 
                layout: { padding: { top: 40, right: 60, bottom: 20, left: 20 } },
                plugins: { 
                    datalabels: { display: true },
                    title: { display: true, text: 'Correlation: Age vs Estimated Monthly Income (with Occurrences)' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let p = context.raw;
                                return `PHP ${p.y.toLocaleString()}, ${p.x} yrs (${p.count} people)`;
                            }
                        }
                    }
                }, 
                scales: { 
                    x: { title: { display: true, text: 'Age (Years)' } }, 
                    y: { title: { display: true, text: 'Monthly Income (PHP)' } } 
                } 
            }
        };
    } else {
        const labels = Object.keys(analyticsData);
        const counts = Object.values(analyticsData);
        let barColor = '#2ecc71'; 
        if (reportType === 'declined') barColor = '#e74c3c'; 
        chartConfig = {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: 'Count', data: counts, backgroundColor: barColor, borderWidth: 1 }] },
            options: { responsive: false, animation: false, plugins: { title: { display: true, text: 'Distribution: Category Hierarchy Frequency' } }, scales: { x: { title: { display: true, text: 'Category Hierarchy' } }, y: { title: { display: true, text: 'Count' }, beginAtZero: true } } }
        };
    }

    const myChart = new Chart(ctx, chartConfig);
    await new Promise(resolve => setTimeout(resolve, 1500));
    const chartImg = document.getElementById('exportChart').toDataURL('image/png');
    const chartId = workbook.addImage({ base64: chartImg, extension: 'png' });
    sheet2.addImage(chartId, { tl: { col: 0, row: 4 }, ext: { width: 850, height: 400 } });

    const summaryRowStart = 26;
    sheet2.getCell('A' + summaryRowStart).value = 'DATA SUMMARY TABLE';
    sheet2.getCell('A' + summaryRowStart).font = { bold: true, size: 14 };
    
    const summaryHeader = sheet2.getRow(summaryRowStart + 1);
    summaryHeader.getCell(1).value = 'METRIC';
    summaryHeader.getCell(2).value = 'VALUE';
    summaryHeader.eachCell(c => {
        c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF2D5A27' } };
        c.font = { color: { argb: 'FFFFFFFF' }, bold: true };
    });

    if (reportType === 'clients') {
        const incomes = analyticsData.map(d => d.y);
        const metrics = [
            ['Total Registered Clients', analyticsData.length],
            ['Maximum Monthly Salary', 'PHP ' + (Math.max(...incomes) || 0).toLocaleString(undefined, {minimumFractionDigits: 2})],
            ['Minimum Monthly Salary', 'PHP ' + (Math.min(...incomes) || 0).toLocaleString(undefined, {minimumFractionDigits: 2})],
            ['Average Monthly Salary', 'PHP ' + (incomes.reduce((a,b)=>a+b,0)/incomes.length || 0).toLocaleString(undefined, {minimumFractionDigits: 2})]
        ];
        metrics.forEach((m, i) => {
            const r = sheet2.getRow(summaryRowStart + 2 + i);
            r.getCell(1).value = m[0];
            r.getCell(2).value = m[1];
            r.eachCell(c => c.border = {style:'thin'});
        });
    } else {
        const counts = Object.values(analyticsData);
        
        const summaryData = [
            ['Total Processed Records', counts.reduce((a,b)=>a+b,0)]
        ];

        // Add each category frequency immediately after totals
        Object.keys(analyticsData).sort().forEach(cat => {
            summaryData.push([cat, analyticsData[cat]]);
        });

        summaryData.forEach((m, i) => {
            const r = sheet2.getRow(summaryRowStart + 2 + i);
            r.getCell(1).value = m[0];
            r.getCell(2).value = m[1];
            r.getCell(1).border = {style:'thin'};
            r.getCell(2).border = {style:'thin'};
            if (i > 0) { // All individual categories after the first row
                r.getCell(1).font = { italic: true };
            }
        });
    }

    const buffer = await workbook.xlsx.writeBuffer();
    saveAs(new Blob([buffer]), `CAMS_Report_${reportType}_${new Date().toISOString().slice(0,10)}.xlsx`);
    myChart.destroy();
    Swal.close();
    showToast("Report exported successfully!", "success");
}
</script>

<div class="dashboard-container">
    <?php render_sidebar('reports'); ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1 style="font-weight: 800; font-size: 2rem; color: var(--primary-color); letter-spacing: -0.02em;">Report Generator</h1>
                <p style="color: var(--text-secondary); font-weight: 500;">Advanced data grid for system records and analytics.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button class="btn btn-excel" onclick="confirmExcelExport()">
                    <i data-lucide="file-spreadsheet" size="18"></i> Export to Excel
                </button>
            </div>
        </header>

        <div class="report-tabs-container">
            <a href="reports.php?type=clients" class="report-tab <?php echo $reportType == 'clients' ? 'active' : ''; ?>">
                <i data-lucide="user" size="16"></i> Clients
            </a>
            <a href="reports.php?type=beneficiaries" class="report-tab <?php echo $reportType == 'beneficiaries' ? 'active' : ''; ?>">
                <i data-lucide="users" size="16"></i> Beneficiaries
            </a>
            <a href="reports.php?type=granted" class="report-tab tab-granted <?php echo $reportType == 'granted' ? 'active' : ''; ?>">
                <i data-lucide="check-circle" size="16"></i> Granted Aid
            </a>
            <a href="reports.php?type=declined" class="report-tab tab-declined <?php echo $reportType == 'declined' ? 'active' : ''; ?>">
                <i data-lucide="x-circle" size="16"></i> Declined Aid
            </a>
        </div>

        <form action="reports.php" method="GET" class="filter-bar">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($reportType); ?>">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary);">Global Search</label>
                <div class="input-wrapper">
                    <i data-lucide="search" size="18"></i>
                    <input type="text" name="search" class="form-control" placeholder="Type to filter records..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 3rem;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 180px;">
                <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary);">Sort Column</label>
                <div class="input-wrapper">
                    <i data-lucide="columns" size="18"></i>
                    <select name="sort" class="form-control" style="padding-left: 3rem;">
                        <option value="last_name" <?php echo $sort == 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                        <option value="first_name" <?php echo $sort == 'first_name' ? 'selected' : ''; ?>>First Name</option>
                        <?php if ($reportType != 'clients'): ?>
                            <option value="category" <?php echo $sort == 'category' ? 'selected' : ''; ?>>Category</option>
                        <?php else: ?>
                            <option value="philhealt_num" <?php echo $sort == 'philhealt_num' ? 'selected' : ''; ?>>PhilHealth No.</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary);">Order</label>
                <div class="input-wrapper">
                    <i data-lucide="arrow-up-down" size="18"></i>
                    <select name="order" class="form-control" style="padding-left: 3rem;">
                        <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 1.5rem; height: 48px;">
                    Apply
                </button>
                <a href="reports.php?type=<?php echo $reportType; ?>" class="btn" style="width: 48px; height: 48px; background: #f1f5f9; color: #64748b;" title="Reset Filters">
                    <i data-lucide="refresh-cw" size="20"></i>
                </a>
            </div>
        </form>

        <div class="datagrid-container">
            <div class="datagrid-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary);">
                        <?php 
                        if ($reportType == 'clients') echo 'Client Masterlist';
                        elseif ($reportType == 'granted') echo 'Granted Aid Records';
                        elseif ($reportType == 'declined') echo 'Declined Aid Records';
                        else echo 'Beneficiary Masterlist';
                        ?>
                    </h2>
                    <span style="background: #f0fdf4; color: #166534; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid #bbf7d0;">
                        <?php echo count($data); ?> Entries Found
                    </span>
                </div>
            </div>
            
            <div class="datagrid-wrapper">
                <table class="datagrid" id="reportTable">
                    <thead>
                        <?php if ($reportType == 'beneficiaries'): ?>
                        <tr>
                            <th>Full Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Aid Description</th>
                            <th>Relationship</th>
                            <th>Linked Client</th>
                        </tr>
                        <?php elseif ($reportType == 'granted' || $reportType == 'declined'): ?>
                        <tr>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Status Remarks</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Aid Description</th>
                            <th>Relationship</th>
                            <th>Linked Client</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Income</th>
                            <th>PhilHealth Number</th>
                            <th>Address</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr onclick="viewDetails(<?php echo $row['person_id']; ?>)" style="cursor: pointer;" title="Click to view full record">
                            <?php if ($reportType == 'beneficiaries'): ?>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td>
                                    <span class="datagrid-badge badge-info">
                                        <i data-lucide="tag" size="12"></i> <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['subcategory']); ?></td>
                                <td style="font-size: 0.8rem; max-width: 250px;"><?php echo htmlspecialchars($row['aid_description']); ?></td>
                                <td>
                                    <span class="datagrid-badge badge-success">
                                        <?php echo htmlspecialchars($row['client_relationship']); ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <?php elseif ($reportType == 'granted' || $reportType == 'declined'): ?>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td>
                                    <?php $statusClass = $row['aid_status'] === 'Granted' ? 'badge-success' : 'badge-danger'; ?>
                                    <span class="datagrid-badge <?php echo $statusClass; ?>"><?php echo $row['aid_status']; ?></span>
                                </td>
                                <td style="font-weight: 700; color: <?php echo $row['aid_amount'] > 0 ? 'var(--secondary-color)' : 'var(--text-secondary)'; ?>;">
                                    <?php echo $row['aid_amount'] > 0 ? 'PHP ' . number_format($row['aid_amount'], 2) : '---'; ?>
                                </td>
                                <td style="font-size: 0.8rem; font-style: italic; color: var(--text-secondary); max-width: 200px;">
                                    <?php echo $row['status_remarks'] ? htmlspecialchars($row['status_remarks']) : '---'; ?>
                                </td>
                                <td>
                                    <span class="datagrid-badge badge-info">
                                        <i data-lucide="tag" size="12"></i> <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['subcategory']); ?></td>
                                <td style="font-size: 0.8rem; max-width: 250px;"><?php echo htmlspecialchars($row['aid_description']); ?></td>
                                <td>
                                    <span class="datagrid-badge badge-success"><?php echo htmlspecialchars($row['client_relationship']); ?></span>
                                </td>
                                <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <?php else: ?>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo getAge($row['birthdate']); ?></td>
                                <td>PHP <?php echo number_format((float)$row['estimated_monthly_income'], 2); ?></td>
                                <td><code><?php echo htmlspecialchars($row['philhealt_num']); ?></code></td>
                                <td style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($row['barangay'] . ', ' . $row['municipality'] . ', ' . $row['province']); ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div style="position: absolute; left: -9999px;">
    <canvas id="exportChart" width="1000" height="500"></canvas>
</div>

<?php include 'includes/footer.php'; ?>
