<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Invoices & Billing';
require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Default date range (current month)
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $type = $_GET['type'] ?? 'officer'; // officer or client
    $entity_id = $_GET['entity_id'] ?? '';
    
    if ($type === 'officer') {
        // Officer invoices
        $sql = "
            SELECT 
                o.id as officer_id,
                CONCAT(o.first_name, ' ', o.last_name) as officer_name,
                COUNT(s.id) as total_shifts,
                SUM(
                    CASE 
                        WHEN s.end_time < s.start_time 
                        THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600
                        ELSE TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600
                    END
                ) as total_hours,
                AVG(s.officer_rate) as avg_rate,
                SUM(
                    CASE 
                        WHEN s.end_time < s.start_time 
                        THEN (TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600) * s.officer_rate
                        ELSE (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.officer_rate
                    END
                ) as total_pay
            FROM officers o
            LEFT JOIN shifts s ON o.id = s.officer_id 
                AND s.shift_date BETWEEN ? AND ?
                AND s.status IN ('confirmed', 'completed')
            WHERE o.employment_status != 'Inactive'
        ";
        
        $params = [$start_date, $end_date];
        
        if ($entity_id) {
            $sql .= " AND o.id = ?";
            $params[] = $entity_id;
        }
        
        $sql .= " GROUP BY o.id ORDER BY officer_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $invoice_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $selected_officer_display = '';
        if ($entity_id) {
            $selected_officer_stmt = $conn->prepare("
                SELECT CONCAT(
                    first_name, ' ', last_name,
                    CASE WHEN staff_id IS NOT NULL AND staff_id != '' THEN CONCAT(' - ', staff_id) ELSE '' END,
                    CASE WHEN phone IS NOT NULL AND phone != '' THEN CONCAT(' - ', phone) ELSE '' END
                ) as display_name
                FROM officers
                WHERE id = ? AND employment_status != 'Inactive'
            ");
            $selected_officer_stmt->execute([$entity_id]);
            $selected_officer_display = $selected_officer_stmt->fetchColumn() ?: '';
        }
        $entities = [];
        
    } else {
        // Client invoices
        $sql = "
            SELECT 
                c.id as client_id,
                c.company_name as client_name,
                c.billing_rate,
                COUNT(s.id) as total_shifts,
                SUM(
                    CASE 
                        WHEN s.end_time < s.start_time 
                        THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600
                        ELSE TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600
                    END
                ) as total_hours,
                AVG(s.client_rate) as avg_rate,
                SUM(
                    CASE 
                        WHEN s.end_time < s.start_time 
                        THEN (TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600) * s.client_rate
                        ELSE (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.client_rate
                    END
                ) as total_charge
            FROM clients c
            LEFT JOIN sites st ON c.id = st.client_id
            LEFT JOIN shifts s ON st.id = s.site_id 
                AND s.shift_date BETWEEN ? AND ?
                AND s.status IN ('confirmed', 'completed')
        ";
        
        $params = [$start_date, $end_date];
        
        if ($entity_id) {
            $sql .= " WHERE c.id = ?";
            $params[] = $entity_id;
        }
        
        $sql .= " GROUP BY c.id ORDER BY client_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $invoice_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get clients for dropdown
        $clients_stmt = $conn->query("SELECT id, company_name as name FROM clients ORDER BY company_name");
        $entities = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = "Error loading invoice data: " . $e->getMessage();
}
?>

<style>
.invoice-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.invoice-type-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-button {
    padding: 10px 20px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #333;
}

.tab-button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.invoice-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.invoice-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.invoice-details {
    padding: 20px;
}

.invoice-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-item h4 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    color: #333;
}

.summary-item p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.invoice-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 20px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Invoice Type Tabs -->
<div class="invoice-type-tabs">
    <a href="?type=officer&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
       class="tab-button <?php echo $type === 'officer' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Officer Invoices
    </a>
    <a href="?type=client&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
       class="tab-button <?php echo $type === 'client' ? 'active' : ''; ?>">
        <i class="fas fa-building"></i> Client Billing
    </a>
</div>

<!-- Invoice Filters -->
<div class="invoice-filters">
    <h3><i class="fas fa-filter"></i> Invoice Filters</h3>
    
    <form method="GET" class="mt-20">
        <input type="hidden" name="type" value="<?php echo $type; ?>">
        
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
                <label><?php echo $type === 'officer' ? 'Officer:' : 'Client:'; ?></label>
                <?php if ($type === 'officer'): ?>
                    <div class="officer-search-wrap">
                        <input type="hidden"
                               name="entity_id"
                               id="invoice_officer_id"
                               value="<?php echo htmlspecialchars($entity_id); ?>"
                               data-officer-name="<?php echo htmlspecialchars($selected_officer_display ?? ''); ?>">
                        <input type="text"
                               id="invoice_officer_search"
                               class="form-control"
                               value="<?php echo htmlspecialchars(($selected_officer_display ?? '') ?: 'All Officers'); ?>"
                               placeholder="Search officer by name, staff ID, or phone"
                               autocomplete="off">
                        <div id="invoice_officer_results" class="officer-search-results"></div>
                    </div>
                <?php else: ?>
                    <select name="entity_id" class="form-control">
                        <option value="">All Clients</option>
                        <?php foreach ($entities as $entity): ?>
                            <option value="<?php echo $entity['id']; ?>" <?php echo $entity_id == $entity['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($entity['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-20">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search Invoices
            </button>
        </div>
    </form>
</div>

<?php if ($type === 'officer'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initOfficerAjaxPicker({
        hiddenInputId: 'invoice_officer_id',
        searchInputId: 'invoice_officer_search',
        resultsId: 'invoice_officer_results'
    });
});
</script>
<?php endif; ?>

<!-- Invoice List -->
<?php if (empty($invoice_data)): ?>
    <div class="text-center p-20">
        <p class="text-muted">No invoice data found for the selected criteria.</p>
    </div>
<?php else: ?>
    <?php foreach ($invoice_data as $invoice): ?>
        <div class="invoice-card">
            <div class="invoice-header">
                <div>
                    <h3>
                        <?php if ($type === 'officer'): ?>
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($invoice['officer_name']); ?>
                        <?php else: ?>
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($invoice['client_name']); ?>
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted">Period: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></p>
                </div>
                
                <div class="text-right">
                    <h3 class="success">
                        <?php 
                        $total = $type === 'officer' ? $invoice['total_pay'] : $invoice['total_charge'];
                        echo formatCurrency($total ?: 0); 
                        ?>
                    </h3>
                </div>
            </div>
            
            <div class="invoice-details">
                <div class="invoice-summary">
                    <div class="summary-item">
                        <h4><?php echo $invoice['total_shifts'] ?: 0; ?></h4>
                        <p>Total Shifts</p>
                    </div>
                    
                    <div class="summary-item">
                        <h4><?php echo number_format($invoice['total_hours'] ?: 0, 1); ?></h4>
                        <p>Total Hours</p>
                    </div>
                    
                    <div class="summary-item">
                        <h4><?php echo formatCurrency($invoice['avg_rate'] ?: 0); ?></h4>
                        <p>Average Rate</p>
                    </div>
                    
                    <div class="summary-item">
                        <h4><?php echo formatCurrency($total ?: 0); ?></h4>
                        <p>Total <?php echo $type === 'officer' ? 'Pay' : 'Charge'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="invoice-actions">
                <?php if ($type === 'officer'): ?>
                    <button onclick="showOfficerInvoiceDetails(<?php echo $invoice['officer_id']; ?>, '<?php echo htmlspecialchars($invoice['officer_name']); ?>', '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                            class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button onclick="generateOfficerInvoicePDF(<?php echo $invoice['officer_id']; ?>, '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                            class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                <?php else: ?>
                    <button onclick="showClientInvoiceDetails(<?php echo $invoice['client_id']; ?>, '<?php echo htmlspecialchars($invoice['client_name']); ?>', '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                            class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button onclick="generateClientInvoice(<?php echo $invoice['client_id']; ?>, '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                            class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button onclick="generateClientInvoicePDF(<?php echo $invoice['client_id']; ?>, '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                            class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Invoice Details Modal -->
<div id="invoiceDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1050;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 800px; max-height: 80vh; overflow-y: auto; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <h3 id="modalTitle" style="margin: 0; color: #333;">Invoice Details</h3>
            <button onclick="closeInvoiceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
        </div>
        <div id="modalContent">
            Loading...
        </div>
    </div>
</div>

<!-- Bulk Actions -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-download"></i> Bulk Export</h3>
        </div>
        
        <div style="padding: 20px;">
            <p class="text-muted">Export all <?php echo $type; ?> invoices for the selected period.</p>
            
            <div class="d-flex gap-10">
                <button onclick="exportAllInvoicesExcel('<?php echo $type; ?>', '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                        class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export All to Excel
                </button>
                <button onclick="exportAllInvoicesPDF('<?php echo $type; ?>', '<?php echo $start_date; ?>', '<?php echo $end_date; ?>')" 
                        class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Export All to PDF
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function showOfficerInvoiceDetails(officerId, officerName, startDate, endDate) {
    document.getElementById('modalTitle').textContent = `Officer Invoice Details - ${officerName}`;
    document.getElementById('modalContent').innerHTML = 'Loading...';
    document.getElementById('invoiceDetailsModal').style.display = 'block';
    
    // Fetch detailed shift data
    fetch(`../api/get_officer_invoice_details.php?officer_id=${officerId}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div style="margin-bottom: 20px;">
                        <strong>Period:</strong> ${startDate} to ${endDate}<br>
                        <strong>Total Hours:</strong> ${data.total_hours || 0}<br>
                        <strong>Total Shifts:</strong> ${data.total_shifts || 0}<br>
                        <strong>Total Pay:</strong> £${(data.total_pay || 0).toFixed(2)}
                    </div>
                    <h4>Shift Details:</h4>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Site</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Time</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Hours</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Rate</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Pay</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                if (data.shifts && data.shifts.length > 0) {
                    data.shifts.forEach(shift => {
                        html += `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">${shift.shift_date}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${shift.site_name}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${shift.start_time} - ${shift.end_time}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${shift.hours}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">£${shift.officer_rate}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">£${shift.pay}</td>
                            </tr>`;
                    });
                } else {
                    html += '<tr><td colspan="6" style="border: 1px solid #ddd; padding: 8px; text-align: center;">No shifts found for this period</td></tr>';
                }
                
                html += '</tbody></table></div>';
                document.getElementById('modalContent').innerHTML = html;
            } else {
                document.getElementById('modalContent').innerHTML = `<p style="color: red;">Error loading invoice details: ${data.message || 'Unknown error'}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('modalContent').innerHTML = `<p style="color: red;">Error loading invoice details: ${error.message}</p>`;
        });
}

function showClientInvoiceDetails(clientId, clientName, startDate, endDate) {
    document.getElementById('modalTitle').textContent = `Client Invoice Details - ${clientName}`;
    document.getElementById('modalContent').innerHTML = 'Loading...';
    document.getElementById('invoiceDetailsModal').style.display = 'block';
    
    // For now, show a simple message since we don't have the API endpoint
    document.getElementById('modalContent').innerHTML = `
        <p><strong>Client:</strong> ${clientName}</p>
        <p><strong>Period:</strong> ${startDate} to ${endDate}</p>
        <p style="color: #666; font-style: italic;">Client invoice details functionality is not yet implemented. Please use the export buttons to generate detailed reports.</p>
    `;
}

function closeInvoiceModal() {
    document.getElementById('invoiceDetailsModal').style.display = 'none';
}

function generateOfficerInvoice(officerId, startDate, endDate) {
    alert('Export functionality is not yet implemented. Please use the View Details button to see invoice information.');
}

function generateOfficerInvoicePDF(officerId, startDate, endDate) {
    alert('PDF export functionality is not yet implemented.');
}

function generateClientInvoice(clientId, startDate, endDate) {
    alert('Export functionality is not yet implemented. Please use the View Details button to see invoice information.');
}

function generateClientInvoicePDF(clientId, startDate, endDate) {
    alert('PDF export functionality is not yet implemented.');
}

function exportAllInvoicesExcel(type, startDate, endDate) {
    const url = `../api/export_all_invoices.php?type=${type}&start_date=${startDate}&end_date=${endDate}&format=excel`;
    window.open(url, '_blank');
}

function exportAllInvoicesPDF(type, startDate, endDate) {
    const url = `../api/export_all_invoices.php?type=${type}&start_date=${startDate}&end_date=${endDate}&format=pdf`;
    window.open(url, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
