<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Reports & Analytics';
require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM shifts LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            // Multi-tenant mode active
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // Pre-migration mode, no company filtering
        $use_company_filter = false;
    }
    
    // Default date range (current month)
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $client_filter = $_GET['client_id'] ?? '';
    $site_filter = $_GET['site_id'] ?? '';
    $officer_filter = $_GET['officer_id'] ?? '';
    
    // Build query - WITH COMPANY FILTERING FOR SECURITY
    $sql = "
        SELECT 
            s.shift_date,
            CONCAT(o.first_name, ' ', o.last_name) as officer_name,
            o.id as officer_id,
            st.site_name as site_name,
            st.id as site_id,
            c.company_name as client_name,
            s.start_time,
            s.end_time,
            s.role,
            s.status,
            s.officer_rate,
            -- Use effective site rate (site rate overrides client rate)
            COALESCE(st.client_rate, c.billing_rate, 0.00) as site_rate,
            s.client_rate as historical_client_rate,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600
                ELSE TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600
            END as hours_worked,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN (TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600) * s.officer_rate
                ELSE (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.officer_rate
            END as officer_pay,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN (TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600) * COALESCE(st.client_rate, c.billing_rate, 0.00)
                ELSE (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * COALESCE(st.client_rate, c.billing_rate, 0.00)
            END as site_charge
        FROM shifts s
        LEFT JOIN officers o ON s.officer_id = o.id
        LEFT JOIN sites st ON s.site_id = st.id
        LEFT JOIN clients c ON st.client_id = c.id
        WHERE s.shift_date BETWEEN ? AND ?
        AND s.status IN ('confirmed', 'completed')";
    
    $params = [$start_date, $end_date];
    
    // SECURITY: Add company filtering to prevent cross-company data access
    if ($use_company_filter && $company_id) {
        $sql .= " AND s.company_id = ?";
        $params[] = $company_id;
    }
    
    if ($client_filter) {
        $sql .= " AND c.id = ?";
        $params[] = $client_filter;
    }
    
    if ($site_filter) {
        $sql .= " AND st.id = ?";
        $params[] = $site_filter;
    }
    
    if ($officer_filter) {
        $sql .= " AND o.id = ?";
        $params[] = $officer_filter;
    }
    
    $sql .= " ORDER BY s.shift_date DESC, s.start_time";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $deployment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading reports data: " . $e->getMessage();
        $deployment_data = [];
    }
    
    // Get filter options - WITH COMPANY FILTERING FOR SECURITY
    if ($use_company_filter && $company_id) {
        $clients_stmt = $conn->prepare("SELECT id, company_name as name FROM clients WHERE company_id = ? ORDER BY company_name");
        $clients_stmt->execute([$company_id]);
    } else {
        $clients_stmt = $conn->query("SELECT id, company_name as name FROM clients ORDER BY company_name");
    }
    $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($use_company_filter && $company_id) {
        $sites_stmt = $conn->prepare("
            SELECT s.id, s.site_name as name, c.company_name as client_name 
            FROM sites s 
            JOIN clients c ON s.client_id = c.id 
            WHERE s.company_id = ?
            ORDER BY c.company_name, s.site_name
        ");
        $sites_stmt->execute([$company_id]);
    } else {
        $sites_stmt = $conn->query("
            SELECT s.id, s.site_name as name, c.company_name as client_name 
            FROM sites s 
            JOIN clients c ON s.client_id = c.id 
            ORDER BY c.company_name, s.site_name
        ");
    }
    $sites = $sites_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($use_company_filter && $company_id) {
        $officers_stmt = $conn->prepare("SELECT id, staff_id, first_name, last_name, phone FROM officers WHERE company_id = ? ORDER BY first_name");
        $officers_stmt->execute([$company_id]);
    } else {
        $officers_stmt = $conn->query("SELECT id, staff_id, first_name, last_name, phone FROM officers ORDER BY first_name");
    }
    $officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals (with safe defaults)
    $deployment_data = $deployment_data ?? [];
    $total_hours = array_sum(array_column($deployment_data, 'hours_worked')) ?: 0;
    $total_officer_pay = array_sum(array_column($deployment_data, 'officer_pay')) ?: 0;
    $total_site_charge = array_sum(array_column($deployment_data, 'site_charge')) ?: 0;
    
    // Group by client for summary
    $client_summary = [];
    foreach ($deployment_data as $row) {
        $client = $row['client_name'];
        if (!isset($client_summary[$client])) {
            $client_summary[$client] = [
                'shifts' => 0,
                'hours' => 0,
                'officer_pay' => 0,
                'site_charge' => 0
            ];
        }
        $client_summary[$client]['shifts']++;
        $client_summary[$client]['hours'] += $row['hours_worked'];
        $client_summary[$client]['officer_pay'] += $row['officer_pay'];
        $client_summary[$client]['site_charge'] += $row['site_charge'];
    }
    
} catch (Exception $e) {
    $error = "Error loading reports: " . $e->getMessage();
}
?>

<style>
.report-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.summary-card h3 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: #333;
}

.summary-card p {
    color: #666;
    margin: 0;
}

.report-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.client-summary {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.client-summary-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
}

.client-item {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 20px;
    align-items: center;
}

.client-item:last-child {
    border-bottom: none;
}

.export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Report table link styles */
.report-link {
    color: #667eea !important;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.report-link:hover {
    color: #5a6fd8 !important;
    text-decoration: underline !important;
}

.report-link:visited {
    color: #667eea !important;
}
</style>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<!-- Report Filters -->
<div class="report-filters">
    <h3><i class="fas fa-filter"></i> Report Filters</h3>
    
    <form method="GET" class="mt-20">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Start Date:</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label>End Date:</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Client:</label>
                <select name="client_id" class="form-control">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Site:</label>
                <select name="site_id" class="form-control">
                    <option value="">All Sites</option>
                    <?php foreach ($sites as $site): ?>
                        <option value="<?php echo $site['id']; ?>" <?php echo $site_filter == $site['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($site['name'] . ' (' . $site['client_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Officer:</label>
                <select name="officer_id" class="form-control">
                    <option value="">All Officers</option>
                    <?php foreach ($officers as $officer): ?>
                        <option value="<?php echo $officer['id']; ?>" <?php echo $officer_filter == $officer['id'] ? 'selected' : ''; ?>>
                            <?php 
                                $displayName = $officer['first_name'] . ' ' . $officer['last_name'];
                                if (!empty($officer['staff_id'])) {
                                    $displayName .= ' - ' . $officer['staff_id'];
                                }
                                if (!empty($officer['phone'])) {
                                    $displayName .= ' - ' . $officer['phone'];
                                }
                                echo htmlspecialchars($displayName);
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="mt-20">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Generate Report
            </button>
            <a href="reports.php" class="btn btn-secondary">
                <i class="fas fa-refresh"></i> Reset Filters
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <h3><?php echo count($deployment_data); ?></h3>
        <p><i class="fas fa-calendar-check"></i> Total Shifts</p>
    </div>
    
    <div class="summary-card">
        <h3><?php echo number_format($total_hours, 1); ?></h3>
        <p><i class="fas fa-clock"></i> Total Hours</p>
    </div>
    
    <div class="summary-card">
        <h3><?php echo formatCurrency($total_officer_pay); ?></h3>
        <p><i class="fas fa-pound-sign"></i> Officer Costs</p>
    </div>
    
    <div class="summary-card">
        <h3><?php echo formatCurrency($total_site_charge); ?></h3>
        <p><i class="fas fa-money-bill-wave"></i> Site Revenue</p>
    </div>
    
    <div class="summary-card">
        <h3><?php echo formatCurrency($total_site_charge - $total_officer_pay); ?></h3>
        <p><i class="fas fa-chart-line"></i> Gross Profit</p>
    </div>
</div>

<!-- Client Summary -->
<?php if (!empty($client_summary)): ?>
<div class="client-summary">
    <div class="client-summary-header">
        <h3><i class="fas fa-building"></i> Client Summary</h3>
    </div>
    
    <div class="client-item" style="background: #f8f9fa; font-weight: 600;">
        <div>Client</div>
        <div>Shifts</div>
        <div>Hours</div>
        <div>Cost</div>
        <div>Revenue</div>
    </div>
    
    <?php foreach ($client_summary as $client_name => $summary): ?>
        <div class="client-item">
            <div>
                <strong><?php echo htmlspecialchars($client_name); ?></strong>
            </div>
            <div><?php echo $summary['shifts']; ?></div>
            <div><?php echo number_format($summary['hours'], 1); ?>h</div>
            <div><?php echo formatCurrency($summary['officer_pay']); ?></div>
            <div class="success"><?php echo formatCurrency($summary['site_charge']); ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Export Actions -->
<div class="report-actions">
    <div class="export-buttons">
        <button onclick="exportToExcel('deploymentTable', 'deployment_summary')" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
        <button onclick="exportToPDF('reportContent', 'deployment_summary')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </button>
        <button onclick="window.print()" class="btn btn-info">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Deployment Details -->
<div class="card" id="reportContent">
    <div class="card-header">
        <h3><i class="fas fa-clipboard-list"></i> Deployment Summary</h3>
        <p class="text-muted">Period: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></p>
    </div>
    
    <?php if (empty($deployment_data)): ?>
        <div class="text-center p-20">
            <p class="text-muted">No deployment data found for the selected criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table id="deploymentTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Shift Date</th>
                        <th>Staff Name</th>
                        <th>Site Name</th>
                        <th>Client</th>
                        <th>Role</th>
                        <th>Start Time</th>
                        <th>Finish Time</th>
                        <th>Total Hours</th>
                        <th>Pay Rate</th>
                        <th>Shift Pay</th>
                        <th>Site Rate</th>
                        <th>Site Charge</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deployment_data as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['shift_date']); ?></td>
                            <td>
                                <?php if ($row['officer_id']): ?>
                                    <a href="officer_detail.php?id=<?php echo $row['officer_id']; ?>" target="_blank" class="report-link">
                                        <?php echo htmlspecialchars($row['officer_name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Unallocated</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="site_detail.php?id=<?php echo $row['site_id']; ?>" target="_blank" class="report-link">
                                    <?php echo htmlspecialchars($row['site_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo formatTime($row['start_time']); ?></td>
                            <td><?php echo formatTime($row['end_time']); ?></td>
                            <td><?php echo number_format($row['hours_worked'], 2); ?>h</td>
                            <td><?php echo formatCurrency($row['officer_rate']); ?></td>
                            <td><?php echo formatCurrency($row['officer_pay']); ?></td>
                            <td><?php echo formatCurrency($row['site_rate']); ?></td>
                            <td><?php echo formatCurrency($row['site_charge']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: 600;">
                        <td colspan="7">TOTALS</td>
                        <td><?php echo number_format($total_hours, 2); ?>h</td>
                        <td>-</td>
                        <td><?php echo formatCurrency($total_officer_pay); ?></td>
                        <td>-</td>
                        <td><?php echo formatCurrency($total_site_charge); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Include XLSX library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
// Enhanced export functions
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table, {sheet: "Deployment Summary"});
    
    // Add metadata
    const ws = wb.Sheets["Deployment Summary"];
    XLSX.utils.sheet_add_aoa(ws, [
        ['Deployment Summary Report'],
        ['Generated: ' + new Date().toLocaleString()],
        ['Period: <?php echo formatDate($start_date) . " to " . formatDate($end_date); ?>'],
        ['Total Shifts: <?php echo count($deployment_data); ?>'],
        ['Total Hours: <?php echo number_format($total_hours, 2); ?>'],
        ['Total Cost: <?php echo formatCurrency($total_officer_pay); ?>'],
        ['Total Revenue: <?php echo formatCurrency($total_site_charge); ?>'],
        [''],
    ], {origin: 'A1'});
    
    XLSX.writeFile(wb, filename + '_' + new Date().toISOString().split('T')[0] + '.xlsx');
}

function exportToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    const opt = {
        margin: 1,
        filename: filename + '_' + new Date().toISOString().split('T')[0] + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().from(element).set(opt).save();
}

// Print styles
window.addEventListener('beforeprint', function() {
    document.body.style.fontSize = '12px';
});

window.addEventListener('afterprint', function() {
    document.body.style.fontSize = '';
});
</script>

<style media="print">
@page {
    size: A4 landscape;
    margin: 0.5in;
}

body * {
    visibility: hidden;
}

#reportContent, #reportContent * {
    visibility: visible;
}

#reportContent {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
}

.btn, .report-actions, .sidebar, .header {
    display: none !important;
}

table {
    font-size: 10px;
}

.main-content {
    margin-left: 0 !important;
}
</style>

<script>
// Initialize DataTables for Reports
$(document).ready(function() {
    $('#deploymentTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']], // Sort by shift date descending by default
        dom: '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4 text-center"B><"col-sm-12 col-md-4"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> Export CSV',
                className: 'btn btn-success btn-sm',
                filename: 'deployment-report-' + new Date().toISOString().slice(0,10)
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm',
                filename: 'deployment-report-' + new Date().toISOString().slice(0,10)
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-primary btn-sm',
                title: 'Deployment Report'
            }
        ],
        language: {
            search: "Search reports:",
            lengthMenu: "Show _MENU_ records per page",
            info: "Showing _START_ to _END_ of _TOTAL_ deployments",
            infoEmpty: "No deployments found",
            infoFiltered: "(filtered from _MAX_ total deployments)"
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
