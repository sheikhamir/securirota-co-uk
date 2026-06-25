<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Site Rota Management';
require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM sites LIKE 'company_id'");
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
    
    // Get filters
    $selected_site_id = $_GET['site_id'] ?? '';
    $selected_client_id = $_GET['client_id'] ?? '';
    $view_mode = $_GET['view_mode'] ?? 'week'; // week, month
    
    // Date handling based on view mode
    if ($view_mode === 'month') {
        $current_month = $_GET['month'] ?? date('Y-m');
        $month_start = $current_month . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        
        // Navigation dates for month view
        $prev_month = date('Y-m', strtotime($month_start . ' -1 month'));
        $next_month = date('Y-m', strtotime($month_start . ' +1 month'));
        
        // For monthly view, we'll show the entire month
        $period_start = $month_start;
        $period_end = $month_end;
    } else {
        // Week view - always normalize to Monday
        $requested_week = $_GET['week'] ?? date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week', strtotime($requested_week)));
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        
        // Navigation dates for week view
        $prev_week = date('Y-m-d', strtotime($week_start . ' -7 days'));
        $next_week = date('Y-m-d', strtotime($week_start . ' +7 days'));
        
        $period_start = $week_start;
        $period_end = $week_end;
    }
    
    // Get all active sites (using same query as rota.php)
    $sites_query = "
        SELECT s.id, s.site_name, c.id as client_id, c.company_name as client_name 
        FROM sites s 
        JOIN clients c ON s.client_id = c.id 
        WHERE s.status = 'active'";
    
    // Add company filter
    if ($use_company_filter && $company_id) {
        $sites_query .= " AND s.company_id = " . intval($company_id);
    }
    
    // Add client filter if selected
    if ($selected_client_id) {
        $sites_query .= " AND c.id = " . intval($selected_client_id);
    }
    
    $sites_query .= " ORDER BY c.company_name, s.site_name";
    
    $stmt = $conn->query($sites_query);
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all clients for filter dropdown
    $clients_query = "
        SELECT DISTINCT c.id, c.company_name 
        FROM clients c 
        JOIN sites s ON c.id = s.client_id 
        WHERE s.status = 'active'";
    
    if ($use_company_filter && $company_id) {
        $clients_query .= " AND s.company_id = " . intval($company_id);
    }
    
    $clients_query .= " ORDER BY c.company_name";
    
    $stmt = $conn->query($clients_query);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add shift counts to sites and group by client
    $sites_by_client = [];
    foreach ($sites as $index => $site) {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_shifts,
                COUNT(CASE WHEN officer_id IS NULL THEN 1 END) as unallocated_shifts
            FROM shifts 
            WHERE site_id = ? AND shift_date BETWEEN ? AND ?
        ");
        $stmt->execute([$site['id'], $period_start, $period_end]);
        $shift_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add shift counts to the site
        $sites[$index]['total_shifts'] = $shift_counts['total_shifts'] ?? 0;
        $sites[$index]['unallocated_shifts'] = $shift_counts['unallocated_shifts'] ?? 0;
        
        // Add to grouped array with shift counts
        $sites_by_client[$site['client_name']][] = $sites[$index];
    }
    
    // Get shifts for selected site or client
    $site_shifts = [];
    $site_info = null;
    $shifts_by_date = [];
    $shifts_by_site = [];
    
    if ($selected_site_id) {
        // Single site view
        // Get site info (using same query structure as sites.php)
        $stmt = $conn->prepare("
            SELECT s.*, c.company_name as client_name 
            FROM sites s 
            JOIN clients c ON s.client_id = c.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$selected_site_id]);
        $site_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get shifts for this site
        $stmt = $conn->prepare("
            SELECT s.*, o.first_name, o.last_name, si.site_name,
                   r.id as role_id, r.name as role_name
            FROM shifts s
            LEFT JOIN officers o ON s.officer_id = o.id
            LEFT JOIN sites si ON s.site_id = si.id
            LEFT JOIN roles r ON s.role_id = r.id
            WHERE s.site_id = ? AND s.shift_date BETWEEN ? AND ?
            ORDER BY s.shift_date, s.start_time
        ");
        $stmt->execute([$selected_site_id, $period_start, $period_end]);
        $site_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize shifts by date
        $shifts_by_date = [];
        foreach ($site_shifts as $shift) {
            $shifts_by_date[$shift['shift_date']][] = $shift;
        }
    } elseif ($selected_client_id) {
        // Client view - all sites for this client
        $stmt = $conn->prepare("
            SELECT s.*, o.first_name, o.last_name, si.site_name, r.name as role_name
            FROM shifts s
            LEFT JOIN officers o ON s.officer_id = o.id
            LEFT JOIN sites si ON s.site_id = si.id
            LEFT JOIN roles r ON s.role_id = r.id
            JOIN clients c ON si.client_id = c.id
            WHERE c.id = ? AND s.shift_date BETWEEN ? AND ?
            ORDER BY si.site_name, s.shift_date, s.start_time
        ");
        $stmt->execute([$selected_client_id, $period_start, $period_end]);
        $site_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize shifts by site and date
        $shifts_by_site = [];
        foreach ($site_shifts as $shift) {
            $shifts_by_site[$shift['site_id']][$shift['shift_date']][] = $shift;
        }
        
        // Get client info
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$selected_client_id]);
        $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Generate dates based on view mode
    $period_dates = [];
    if ($view_mode === 'month') {
        // Generate all dates in the month
        $start = new DateTime($month_start);
        $end = new DateTime($month_end);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $period_dates[] = $date->format('Y-m-d');
        }
    } else {
        // Generate week dates
        for ($i = 0; $i < 7; $i++) {
            $period_dates[] = date('Y-m-d', strtotime($week_start . " +$i days"));
        }
    }
    
} catch (Exception $e) {
    $error = "Error loading site rota data: " . $e->getMessage();
    $sites = [];
    $clients = [];
    $sites_by_client = [];
    $site_shifts = [];
    $site_info = null;
    $client_info = null;
    $shifts_by_date = [];
    $shifts_by_site = [];
    $period_dates = [];
    $week_start = isset($week_start) ? $week_start : null;
    $week_end = isset($week_end) ? $week_end : null;
    $month_start = isset($month_start) ? $month_start : null;
    $month_end = isset($month_end) ? $month_end : null;
    $current_month = isset($current_month) ? $current_month : null;
}
?>

<style>
/* Modal fixes for compact display */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    overflow: hidden;
}

.modal-dialog {
    position: relative;
    margin: 2vh auto;
    padding: 0;
    width: 90%;
    max-width: 600px;
    max-height: 96vh;
    display: flex;
    flex-direction: column;
}

.modal-dialog > div {
    background-color: #fefefe;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 100%;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal-header .close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.modal-header .close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-content {
    padding: 1.5rem;
    overflow-y: auto;
    flex-grow: 1;
    max-height: calc(96vh - 80px);
}

.site-rota-header {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.site-selector {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.site-selector-inline {
    margin-left: 15px;
}

.site-selector-inline .form-select {
    max-width: 300px;
}

.filters-inline {
    margin-left: 15px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-group .form-select {
    min-width: 140px;
    padding: 6px 10px;
    font-size: 0.9em;
}

.filter-group .form-label {
    font-size: 0.9em;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .site-rota-header {
        padding: 10px;
        margin-bottom: 15px;
    }
    
    .site-rota-header .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px;
    }
    
    .filters-inline {
        margin-left: 0;
        width: 100%;
    }
    
    .filters-inline .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px;
    }
    
    .filter-group {
        width: 100%;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px;
    }
    
    .filter-group .form-select {
        width: 100%;
        min-width: auto;
    }
    
    .client-section {
        padding: 10px;
        margin-bottom: 15px;
    }
    
    .site-card .card-body {
        padding: 8px;
    }
}

.rota-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.rota-actions .btn {
    padding: 6px 12px;
    font-size: 0.9em;
}

.site-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    padding: 10px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 3px;
}

.stat-card div:last-child {
    font-size: 0.85em;
}

.site-shifts-grid {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
}

.shifts-table {
    width: 100%;
    border-collapse: collapse;
}

.shifts-table th,
.shifts-table td {
    border: 1px solid #e0e0e0;
    padding: 8px;
    text-align: center;
    vertical-align: top;
}

.shifts-table th {
    background: #f8f9fa;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 0.9em;
    padding: 10px 8px;
}

.day-header {
    background: #f8f9fa;
    font-weight: 600;
    text-align: left;
    min-width: 100px;
}

.shift-item {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 4px;
    padding: 6px;
    margin: 1px 0;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85em;
}

.shift-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

.shift-item.unallocated {
    background: #ffebee;
    border-color: #f44336;
    color: #c62828;
}

.shift-item.allocated {
    background: #fff3e0;
    border-color: #ff9800;
    color: #e65100;
}

.shift-item.confirmed {
    background: #e8f5e8;
    border-color: #4caf50;
    color: #2e7d32;
}

.shift-item.cancelled {
    background: #f5f5f5;
    border-color: #9e9e9e;
    color: #616161;
    opacity: 0.7;
    text-decoration: line-through;
}

.shift-item.declined {
    background: #dc3545;
    border-color: #c82333;
    color: #ffffff;
}

.shift-time {
    font-weight: bold;
    display: block;
    margin-bottom: 1px;
    font-size: 0.9em;
}

.shift-officer {
    font-size: 0.8em;
    display: block;
}

.shift-role {
    font-size: 0.75em;
    opacity: 0.8;
    display: block;
}

.empty-day {
    color: #999;
    font-style: italic;
    padding: 15px 8px;
    text-align: center;
    font-size: 0.85em;
}

.weekend {
    background: rgba(255, 152, 0, 0.03);
}

.add-shift-cell {
    border: 1px dashed #ddd;
    text-align: center;
    padding: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.8em;
}

.add-shift-cell:hover {
    border-color: #2196f3;
    background: rgba(33, 150, 243, 0.05);
}

.template-actions {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Calendar View Styles */
.calendar-table .calendar-cell {
    height: 120px;
    width: 14.28%;
    vertical-align: top;
    position: relative;
    padding: 5px;
}

.calendar-date {
    font-weight: bold;
    margin-bottom: 5px;
}

.calendar-shifts {
    font-size: 0.75em;
}

.calendar-shift-item {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 3px;
    padding: 2px 4px;
    margin: 1px 0;
    font-size: 0.7em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-shift-item.unallocated {
    background: #ffebee;
    border-color: #f44336;
}

.calendar-shift-item.confirmed {
    background: #e8f5e8;
    border-color: #4caf50;
}

.calendar-shift-item.cancelled {
    background: #f5f5f5;
    border-color: #9e9e9e;
    color: #616161;
    opacity: 0.7;
    text-decoration: line-through;
}

.calendar-shift-item.declined {
    background: #dc3545;
    border-color: #c82333;
    color: #ffffff;
}

.calendar-shift-more {
    font-size: 0.6em;
    color: #666;
    font-style: italic;
}

/* Client View Styles */
.clients-grid {
    max-width: 100%;
}

.client-section {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.client-header {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.client-header h5 {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.site-card {
    transition: transform 0.2s ease;
    margin-bottom: 15px;
}

.site-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.site-card .card-body {
    padding: 12px;
}

.site-card .card-title {
    font-size: 0.95rem;
    margin-bottom: 8px;
}

.site-card .badge {
    font-size: 0.7em;
}

.site-card .btn {
    padding: 4px 8px;
    font-size: 0.8em;
}

/* Multi-Site Grid Styles */
.multi-site-grid {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.site-section {
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.site-section:last-child {
    border-bottom: none;
}

.site-section-title {
    color: #333;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.day-header-small {
    background: #f8f9fa;
    font-weight: 600;
    text-align: center;
    min-width: 80px;
    font-size: 0.85em;
    padding: 8px;
}

.shift-item-small {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 4px;
    padding: 4px 6px;
    margin: 1px 0;
    cursor: pointer;
    font-size: 0.75em;
    transition: all 0.2s ease;
}

.shift-item-small:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.shift-item-small.unallocated {
    background: #ffebee;
    border-color: #f44336;
    color: #c62828;
}

.shift-item-small.allocated {
    background: #fff3e0;
    border-color: #ff9800;
    color: #e65100;
}

.shift-item-small.confirmed {
    background: #e8f5e8;
    border-color: #4caf50;
    color: #2e7d32;
}

.shift-item-small.cancelled {
    background: #f5f5f5;
    border-color: #9e9e9e;
    color: #616161;
    opacity: 0.7;
    text-decoration: line-through;
}

.shift-item-small.declined {
    background: #dc3545;
    border-color: #c82333;
    color: #ffffff;
}

.shift-time-small {
    font-weight: bold;
    display: block;
}

.shift-officer-small {
    font-size: 0.9em;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty-day-small {
    color: #999;
    font-style: italic;
    padding: 15px 5px;
    text-align: center;
    font-size: 0.8em;
}

/* Modal styles are now handled by main.css */

/* Form styles for modal */
.modal .form-group {
    margin-bottom: 1rem;
}

.modal .form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.modal .form-control,
.modal .form-select {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    width: 100%;
    background-color: #fff;
}

.modal .form-control:focus,
.modal .form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.modal .form-control[readonly] {
    background-color: #e9ecef;
    opacity: 1;
}

.modal .btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.modal .btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.modal .btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
    color: #fff;
}

.modal .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

.modal .btn-secondary:hover {
    background-color: #5c636a;
    border-color: #565e64;
    color: #fff;
}

.modal .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.75rem;
    margin-left: -0.75rem;
}

.modal .col-md-6 {
    flex: 0 0 auto;
    width: 50%;
    padding-right: 0.75rem;
    padding-left: 0.75rem;
}

@media (max-width: 767.98px) {
    .modal .col-md-6 {
        width: 100%;
    }
    
    .modal-dialog {
        margin: 1vh auto;
        max-width: calc(100% - 1rem);
        width: calc(100% - 1rem);
        max-height: 98vh;
    }
    
    .modal-content {
        max-height: calc(98vh - 80px);
    }
}

/* Additional utility classes */
.fas {
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
}

.fa-plus:before {
    content: "\f067";
}

.fa-calendar-alt:before {
    content: "\f073";
}

.fa-building:before {
    content: "\f1ad";
}

.fa-eye:before {
    content: "\f06e";
}

.fa-clock:before {
    content: "\f017";
}

.fa-user:before {
    content: "\f007";
}

.fa-chevron-left:before {
    content: "\f053";
}

.fa-chevron-right:before {
    content: "\f054";
}

.fa-map-marker-alt:before {
    content: "\f3c5";
}

.fa-spinner:before {
    content: "\f110";
}

.fa-spin {
    animation: fa-spin 2s infinite linear;
}

@keyframes fa-spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Shift Popup Styles */
.shift-popup {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 12px;
    min-width: 200px;
    max-width: 300px;
    z-index: 1100;
    font-size: 0.85em;
    opacity: 0;
    transform: translateY(-5px);
    transition: all 0.2s ease;
    pointer-events: none;
}

.shift-popup.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}

.shift-popup-header {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 6px;
}

.shift-popup-time {
    color: #2196f3;
    font-weight: 600;
    margin-bottom: 4px;
}

.shift-popup-officer {
    color: #666;
    margin-bottom: 4px;
}

.shift-popup-status {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: 500;
    margin-bottom: 8px;
}

.shift-popup-status.unallocated {
    background: #ffebee;
    color: #c62828;
}

.shift-popup-status.allocated {
    background: #fff3e0;
    color: #e65100;
}

.shift-popup-status.confirmed {
    background: #e8f5e8;
    color: #2e7d32;
}

.shift-popup-status.in_progress {
    background: #e3f2fd;
    color: #1976d2;
}

.shift-popup-status.completed {
    background: #e1f5fe;
    color: #0277bd;
}

.shift-popup-actions {
    border-top: 1px solid #eee;
    padding-top: 8px;
    margin-top: 8px;
}

.shift-popup-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.8em;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #495057;
    text-decoration: none;
    display: inline-block;
    margin-right: 4px;
}

.shift-popup-btn:hover {
    background: #e9ecef;
    color: #495057;
    text-decoration: none;
}

.shift-popup-btn.primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.shift-popup-btn.primary:hover {
    background: #0056b3;
    color: white;
    border-color: #0056b3;
}

/* Calendar shift item enhancements */
.calendar-shift-item {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 3px;
    padding: 2px 4px;
    margin: 1px 0;
    font-size: 0.7em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.calendar-shift-item:hover {
    background: #bbdefb;
    border-color: #1976d2;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Site Rota Header -->
<div class="site-rota-header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h3 class="mb-0"><i class="fas fa-building me-2"></i>Site Rota Management</h3>
            
            <!-- Filters -->
            <div class="filters-inline d-flex gap-2 align-items-center flex-wrap">
                <form method="GET" class="d-flex align-items-center gap-2 flex-wrap" id="filtersForm">
                    <!-- Client Filter -->
                    <div class="filter-group">
                        <label class="form-label mb-0 text-nowrap">Client:</label>
                        <select name="client_id" class="form-select" onchange="handleClientChange()" style="min-width: 200px;">
                            <option value="">All Clients</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Site Filter -->
                    <div class="filter-group">
                        <label class="form-label mb-0 text-nowrap">Site:</label>
                        <select name="site_id" class="form-select" onchange="handleSiteChange()" style="min-width: 250px;">
                            <option value="">
                                <?php echo $selected_client_id ? 'All Sites for Client' : 'Select a site...'; ?>
                            </option>
                            <?php foreach ($sites as $site): ?>
                                <option value="<?php echo $site['id']; ?>" <?php echo $selected_site_id == $site['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($site['site_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- View Mode Filter (only show when site is selected) -->
                    <?php if ($selected_site_id): ?>
                    <div class="filter-group">
                        <label class="form-label mb-0 text-nowrap">View:</label>
                        <select name="view_mode" class="form-select" onchange="handleViewModeChange()" style="min-width: 120px;">
                            <option value="week" <?php echo $view_mode === 'week' ? 'selected' : ''; ?>>Week</option>
                            <option value="month" <?php echo $view_mode === 'month' ? 'selected' : ''; ?>>Month</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hidden fields to preserve state -->
                    <?php if ($view_mode === 'month' && isset($current_month)): ?>
                        <input type="hidden" name="month" value="<?php echo $current_month; ?>">
                    <?php elseif (isset($week_start)): ?>
                        <input type="hidden" name="week" value="<?php echo $week_start; ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="d-flex gap-1">
            <a href="rota.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-calendar-alt"></i> Back to Main Rota
            </a>
        </div>
    </div>
</div>

<?php if ($site_info): ?>
<!-- Site Info & Navigation -->
<div class="site-rota-header">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3><?php echo htmlspecialchars($site_info['site_name']); ?></h3>
            <p class="text-muted mb-0">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($site_info['client_name']); ?>
                <?php if ($site_info['address']): ?>
                    <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($site_info['address']); ?>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Period Navigation -->
        <div class="d-flex align-items-center gap-3">
            <?php if ($view_mode === 'month'): ?>
                <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=month&month=<?php echo $prev_month; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i> Previous Month
                </a>
                <div class="text-center">
                    <strong><?php echo date('F Y', strtotime($month_start)); ?></strong>
                    <br>
                    <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=month&month=<?php echo date('Y-m'); ?>" class="btn btn-sm btn-link">This Month</a>
                </div>
                <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=month&month=<?php echo $next_month; ?>" class="btn btn-outline-secondary">
                    Next Month <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=week&week=<?php echo $prev_week; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i> Previous Week
                </a>
                <div class="text-center">
                    <strong>Week of <?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?></strong>
                    <br>
                    <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=week&week=<?php echo date('Y-m-d', strtotime('monday this week')); ?>" class="btn btn-sm btn-link">This Week</a>
                </div>
                <a href="?site_id=<?php echo $selected_site_id; ?>&view_mode=week&week=<?php echo $next_week; ?>" class="btn btn-outline-secondary">
                    Next Week <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Site Statistics -->
    <div class="site-stats">
        <div class="stat-card">
            <div class="stat-number text-primary"><?php echo count($site_shifts); ?></div>
            <div>Total Shifts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-warning"><?php echo count(array_filter($site_shifts, function($s) { return !$s['officer_id']; })); ?></div>
            <div>Unallocated</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-success"><?php echo count(array_filter($site_shifts, function($s) { return $s['status'] === 'confirmed'; })); ?></div>
            <div>Confirmed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-info"><?php echo array_sum(array_map(function($s) { 
                $start = new DateTime($s['start_time']);
                $end = new DateTime($s['end_time']);
                if ($end < $start) $end->add(new DateInterval('P1D'));
                return $end->diff($start)->h + ($end->diff($start)->i / 60);
            }, $site_shifts)); ?></div>
            <div>Total Hours</div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="rota-actions">
        <button onclick="showCreateShiftModal('<?php echo $selected_site_id; ?>')" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Shift
        </button>
        <button onclick="showBulkScheduleModal('<?php echo $selected_site_id; ?>')" class="btn btn-success">
            <i class="fas fa-calendar-plus"></i> Bulk Schedule
        </button>
        <button onclick="showTemplateModal('<?php echo $selected_site_id; ?>')" class="btn btn-info">
            <i class="fas fa-copy"></i> Create Template
        </button>
        <button onclick="generateFromTemplate('<?php echo $selected_site_id; ?>')" class="btn btn-warning">
            <i class="fas fa-magic"></i> Generate from Template
        </button>
    </div>
</div>

<!-- Weekly Schedule Grid -->
<?php if ($view_mode === 'month'): ?>
    <!-- Monthly Calendar View -->
    <div class="site-shifts-grid">
        <div class="calendar-header mb-3">
            <h5>Monthly Calendar View</h5>
        </div>
        <?php
        // Create calendar layout (Monday-first)
        $weeks = [];
        $current_week = [];
        $month_start_day = date('w', strtotime($month_start)); // 0 = Sunday, 1 = Monday, etc.
        // Convert to Monday-first (0 = Monday, 1 = Tuesday, etc.)
        $month_start_day = ($month_start_day == 0) ? 6 : $month_start_day - 1;
        
        // Add empty cells for the first week
        for ($i = 0; $i < $month_start_day; $i++) {
            $current_week[] = null;
        }
        
        // Add all days of the month
        foreach ($period_dates as $date) {
            $current_week[] = $date;
            if (count($current_week) == 7) {
                $weeks[] = $current_week;
                $current_week = [];
            }
        }
        
        // Fill the last week if needed
        while (count($current_week) < 7 && !empty($current_week)) {
            $current_week[] = null;
        }
        if (!empty($current_week)) {
            $weeks[] = $current_week;
        }
        ?>
        
        <table class="shifts-table calendar-table">
            <thead>
                <tr>
                    <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weeks as $week): ?>
                <tr>
                    <?php foreach ($week as $date): ?>
                    <td class="calendar-cell <?php echo $date && in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                        <?php if ($date): ?>
                            <div class="calendar-date"><?php echo date('j', strtotime($date)); ?></div>
                            <?php 
                            $day_shifts = $shifts_by_date[$date] ?? [];
                            if (!empty($day_shifts)): 
                            ?>
                                <div class="calendar-shifts">
                                    <?php foreach (array_slice($day_shifts, 0, 3) as $shift): ?>
                                        <div class="calendar-shift-item <?php echo $shift['status']; ?>" 
                                             onmouseenter="showShiftPopup(event, <?php echo htmlspecialchars(json_encode($shift)); ?>)"
                                             onmouseleave="hideShiftPopup()"
                                             onclick="editShiftWithStatusCheck(<?php echo $shift['id']; ?>, '<?php echo $shift['status']; ?>')">
                                            <?php echo formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($day_shifts) > 3): ?>
                                        <div class="calendar-shift-more">+<?php echo count($day_shifts) - 3; ?> more</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <!-- Weekly Grid View -->
    <div class="site-shifts-grid">
        <table class="shifts-table">
            <thead>
                <tr>
                    <?php foreach ($period_dates as $date): ?>
                        <th class="day-header <?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                            <?php echo date('D', strtotime($date)); ?><br>
                            <small><?php echo date('M j', strtotime($date)); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($period_dates as $date): ?>
                        <td class="<?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                            <?php 
                            $day_shifts = $shifts_by_date[$date] ?? [];
                            if (empty($day_shifts)): 
                            ?>
                                <div class="empty-day">
                                    No shifts scheduled
                                    <div class="add-shift-cell" onclick="createShiftForDate('<?php echo $date; ?>', '<?php echo $selected_site_id; ?>')">
                                        <i class="fas fa-plus"></i><br>Add Shift
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($day_shifts as $shift): ?>
                                    <div class="shift-item <?php echo $shift['status']; ?>" onclick="editShiftWithStatusCheck(<?php echo $shift['id']; ?>, '<?php echo $shift['status']; ?>')">
                                        <span class="shift-time"><?php echo formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']); ?></span>
                                        <span class="shift-officer">
                                            <?php echo $shift['officer_id'] ? htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']) : 'Unallocated'; ?>
                                        </span>
                                        <span class="shift-role"><?php echo htmlspecialchars($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="add-shift-cell" onclick="createShiftForDate('<?php echo $date; ?>', '<?php echo $selected_site_id; ?>')">
                                    <i class="fas fa-plus"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php elseif ($selected_client_id && isset($client_info)): ?>
<!-- Client View -->
<div class="site-rota-header">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3><i class="fas fa-building"></i> <?php echo htmlspecialchars($client_info['company_name']); ?></h3>
            <p class="text-muted mb-0">All Sites - Week View</p>
        </div>
        
        <!-- Week Navigation for Client View -->
        <div class="d-flex align-items-center gap-3">
            <a href="?client_id=<?php echo $selected_client_id; ?>&week=<?php echo $prev_week; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chevron-left"></i> Previous Week
            </a>
            <div class="text-center">
                <strong>Week of <?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?></strong>
                <br>
                <a href="?client_id=<?php echo $selected_client_id; ?>&week=<?php echo date('Y-m-d', strtotime('monday this week')); ?>" class="btn btn-sm btn-link">This Week</a>
            </div>
            <a href="?client_id=<?php echo $selected_client_id; ?>&week=<?php echo $next_week; ?>" class="btn btn-outline-secondary">
                Next Week <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Client Statistics -->
    <div class="site-stats">
        <div class="stat-card">
            <div class="stat-number text-primary"><?php echo count($site_shifts); ?></div>
            <div>Total Shifts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-warning"><?php echo count(array_filter($site_shifts, function($s) { return !$s['officer_id']; })); ?></div>
            <div>Unallocated</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-info"><?php echo count(array_unique(array_column($site_shifts, 'site_id'))); ?></div>
            <div>Active Sites</div>
        </div>
    </div>
</div>

<!-- Multi-Site Schedule Grid -->
<div class="multi-site-grid">
    <?php foreach ($sites as $site): ?>
        <?php if ($site['client_id'] == $selected_client_id): ?>
        <div class="site-section mb-4">
            <h5 class="site-section-title">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($site['site_name']); ?>
                <a href="?site_id=<?php echo $site['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">View Details</a>
            </h5>
            
            <div class="site-shifts-grid">
                <table class="shifts-table">
                    <thead>
                        <tr>
                            <?php foreach ($period_dates as $date): ?>
                                <th class="day-header-small <?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                                    <?php echo date('D j', strtotime($date)); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($period_dates as $date): ?>
                                <td class="<?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                                    <?php 
                                    $day_shifts = $shifts_by_site[$site['id']][$date] ?? [];
                                    if (empty($day_shifts)): 
                                    ?>
                                        <div class="empty-day-small">No shifts</div>
                                    <?php else: ?>
                                        <?php foreach ($day_shifts as $shift): ?>
                                            <div class="shift-item-small <?php echo $shift['status']; ?>" onclick="editShiftWithStatusCheck(<?php echo $shift['id']; ?>, '<?php echo $shift['status']; ?>')">
                                                <div class="shift-time-small"><?php echo formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']); ?></div>
                                                <div class="shift-officer-small">
                                                    <?php echo $shift['officer_id'] ? htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']) : 'Unallocated'; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php else: ?>
<!-- No Site/Client Selected - Show Sites Grouped by Client -->
<div class="text-center py-2 mb-3">
    <div class="mb-2">
        <i class="fas fa-building fa-2x text-muted"></i>
    </div>
    <h4 class="mb-2">Site Rota Management</h4>
    <p class="text-muted mb-0">Select a client to view all their sites, or choose a specific site to manage its rota.</p>
</div>

<?php if (!empty($sites_by_client)): ?>
    <div class="clients-grid">
        <?php foreach ($sites_by_client as $client_name => $client_sites): ?>
        <div class="client-section mb-4">
            <div class="client-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($client_name); ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($client_sites); ?> sites</span>
                </h5>
                <a href="?client_id=<?php echo $client_sites[0]['client_id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View All Sites
                </a>
            </div>
            
            <div class="row">
                <?php foreach ($client_sites as $site): ?>
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card site-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title"><?php echo htmlspecialchars($site['site_name']); ?></h6>
                                <p class="mb-2">
                                    <span class="badge bg-primary"><?php echo $site['total_shifts']; ?> shifts</span>
                                    <?php if ($site['unallocated_shifts'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $site['unallocated_shifts']; ?> unallocated</span>
                                    <?php endif; ?>
                                </p>
                                <div class="d-grid gap-1">
                                    <a href="?site_id=<?php echo $site['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-calendar-alt"></i> View Rota
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <p class="text-muted">No active sites found.</p>
    </div>
<?php endif; ?>
<?php endif; ?>

<!-- Custom Modal (improved compact version) -->
<div id="genericModal" class="modal">
    <div class="modal-dialog">
        <div>
            <div class="modal-header">
                <h3 id="modalTitle">Modal Title</h3>
                <button type="button" class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content" id="modalBody">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>
</div>

<!-- Shift Popup -->
<div id="shiftPopup" class="shift-popup">
    <div class="shift-popup-header" id="popupHeader">Shift Details</div>
    <div class="shift-popup-time" id="popupTime"></div>
    <div class="shift-popup-officer" id="popupOfficer"></div>
    <div class="shift-popup-status" id="popupStatus"></div>
    <div class="shift-popup-actions">
        <button class="shift-popup-btn primary" id="popupShowMore" onclick="showFullShiftDetails()">Show More</button>
        <button class="shift-popup-btn" id="popupEdit" onclick="editShiftFromPopup()">Edit</button>
    </div>
</div>

<script>
function renderSiteOfficerPicker(id) {
    return `
        <div class="officer-search-wrap">
            <input type="hidden" name="officer_id" id="${id}" value="" data-officer-name="">
            <input type="text"
                   id="${id}_search"
                   class="form-control"
                   value="Leave Unallocated"
                   placeholder="Search officer by name, staff ID, or phone"
                   autocomplete="off">
            <div id="${id}_results" class="officer-search-results"></div>
        </div>
    `;
}

function initSiteOfficerPicker(id, linkContainerId) {
    initOfficerAjaxPicker({
        hiddenInputId: id,
        searchInputId: `${id}_search`,
        resultsId: `${id}_results`,
        linkContainerId,
        onChange: toggleCustomRateFieldSite
    });
}

// Global roles variable for dynamic loading
let ALL_ROLES = [];

// Load roles from API
async function loadRoles() {
    try {
        const response = await fetch(BASE_URL + 'api/get_roles.php');
        const data = await response.json();
        if (data.success) {
            ALL_ROLES = data.roles;
        } else {
            console.error('Failed to load roles:', data.error);
            // Empty fallback to prevent showing wrong company roles
            ALL_ROLES = [];
        }
    } catch (error) {
        console.error('Error loading roles:', error);
        // Empty fallback to prevent showing wrong company roles
        ALL_ROLES = [];
    }
    return ALL_ROLES;
}

// Generate role options HTML
function getRoleOptions(selectedValue = '') {
    return ALL_ROLES.map(role => {
        const selected = role.id == selectedValue ? 'selected' : '';
        return `<option value="${role.id}" ${selected}>${role.name}</option>`;
    }).join('');
}

// Populate role select elements
function populateRoleSelects() {
    const roleSelects = document.querySelectorAll('select[name*="[role_id]"], select[name="role_id"]');
    roleSelects.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = getRoleOptions(currentValue);
    });
}

// Function to toggle custom rate field visibility for site forms
function toggleCustomRateFieldSite(selectElement) {
    const customRateGroup = document.getElementById('customRateGroupSite');
    if (customRateGroup) {
        if (selectElement.value && selectElement.value !== '') {
            customRateGroup.style.display = 'block';
        } else {
            customRateGroup.style.display = 'none';
            // Clear the custom rate input when hiding
            const customRateInput = customRateGroup.querySelector('input[name="custom_officer_rate"]');
            if (customRateInput) {
                customRateInput.value = '';
            }
        }
    }
}

// Initialize roles when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadRoles().then(() => {
        populateRoleSelects();
    });
});

// Filter handling functions
function handleClientChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    
    const clientSelect = form.querySelector('select[name="client_id"]');
    const siteSelect = form.querySelector('select[name="site_id"]');
    
    if (siteSelect) {
        // Reset site selection when client changes
        siteSelect.value = '';
    }
    
    // Submit form
    form.submit();
}

function handleSiteChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    form.submit();
}

function handleViewModeChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    
    const viewModeSelect = form.querySelector('select[name="view_mode"]');
    if (!viewModeSelect) {
        console.error('View mode select not found');
        return;
    }
    
    const viewMode = viewModeSelect.value;
    
    // Remove old period parameters
    const weekInput = form.querySelector('input[name="week"]');
    const monthInput = form.querySelector('input[name="month"]');
    
    if (weekInput) weekInput.remove();
    if (monthInput) monthInput.remove();
    
    // Add appropriate parameter for current period
    if (viewMode === 'month') {
        const monthValue = '<?php echo isset($current_month) ? $current_month : date("Y-m"); ?>';
        const monthHidden = document.createElement('input');
        monthHidden.type = 'hidden';
        monthHidden.name = 'month';
        monthHidden.value = monthValue;
        form.appendChild(monthHidden);
    } else {
        const weekValue = '<?php echo isset($week_start) ? $week_start : date("Y-m-d", strtotime("monday this week")); ?>';
        const weekHidden = document.createElement('input');
        weekHidden.type = 'hidden';
        weekHidden.name = 'week';
        weekHidden.value = weekValue;
        form.appendChild(weekHidden);
    }
    
    form.submit();
}
function handleClientChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    
    const clientSelect = form.querySelector('select[name="client_id"]');
    const siteSelect = form.querySelector('select[name="site_id"]');
    
    if (siteSelect) {
        // Reset site selection when client changes
        siteSelect.value = '';
    }
    
    // Submit form
    form.submit();
}

function handleSiteChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    form.submit();
}

function handleViewModeChange() {
    const form = document.getElementById('filtersForm');
    if (!form) {
        console.error('Filter form not found');
        return;
    }
    
    const viewModeSelect = form.querySelector('select[name="view_mode"]');
    if (!viewModeSelect) {
        console.error('View mode select not found');
        return;
    }
    
    const viewMode = viewModeSelect.value;
    
    // Remove old period parameters
    const weekInput = form.querySelector('input[name="week"]');
    const monthInput = form.querySelector('input[name="month"]');
    
    if (weekInput) weekInput.remove();
    if (monthInput) monthInput.remove();
    
    // Add appropriate parameter for current period
    if (viewMode === 'month') {
        const monthValue = '<?php echo isset($current_month) ? $current_month : date("Y-m"); ?>';
        const monthHidden = document.createElement('input');
        monthHidden.type = 'hidden';
        monthHidden.name = 'month';
        monthHidden.value = monthValue;
        form.appendChild(monthHidden);
    } else {
        const weekValue = '<?php echo isset($week_start) ? $week_start : date("Y-m-d", strtotime("monday this week")); ?>';
        const weekHidden = document.createElement('input');
        weekHidden.type = 'hidden';
        weekHidden.name = 'week';
        weekHidden.value = weekValue;
        form.appendChild(weekHidden);
    }
    
    form.submit();
}

// Shift popup functionality
let currentShiftData = null;
let popupTimeout = null;

function showShiftPopup(event, shiftData) {
    clearTimeout(popupTimeout);
    currentShiftData = shiftData;
    
    const popup = document.getElementById('shiftPopup');
    const popupHeader = document.getElementById('popupHeader');
    const popupTime = document.getElementById('popupTime');
    const popupOfficer = document.getElementById('popupOfficer');
    const popupStatus = document.getElementById('popupStatus');
    
    if (!popup || !shiftData) return;
    
    // Calculate shift duration
    const startTime = new Date('2000-01-01 ' + shiftData.start_time);
    const endTime = new Date('2000-01-01 ' + shiftData.end_time);
    if (endTime < startTime) {
        endTime.setDate(endTime.getDate() + 1); // Next day
    }
    const durationMs = endTime - startTime;
    const durationHours = Math.round((durationMs / (1000 * 60 * 60)) * 100) / 100;
    
    // Update popup content
    popupHeader.textContent = shiftData.role || 'Shift';
    popupTime.innerHTML = `<i class="fas fa-clock"></i> ${formatTime24(shiftData.start_time)} - ${formatTime24(shiftData.end_time)} (${durationHours}h)`;
    
    if (shiftData.officer_id && shiftData.first_name && shiftData.last_name) {
        popupOfficer.innerHTML = `<i class="fas fa-user"></i> ${shiftData.first_name} ${shiftData.last_name}`;
    } else {
        popupOfficer.innerHTML = `<i class="fas fa-user-times"></i> Unallocated`;
    }
    
    // Set status
    const statusText = shiftData.status || 'unallocated';
    popupStatus.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
    popupStatus.className = 'shift-popup-status ' + statusText;
    
    // Position popup near the mouse
    const rect = event.target.getBoundingClientRect();
    popup.style.left = (rect.left + window.scrollX + 10) + 'px';
    popup.style.top = (rect.top + window.scrollY - 10) + 'px';
    
    // Show popup with animation
    popup.classList.add('show');
}

function hideShiftPopup() {
    popupTimeout = setTimeout(() => {
        const popup = document.getElementById('shiftPopup');
        if (popup) {
            popup.classList.remove('show');
        }
    }, 300); // Small delay to allow moving to popup
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function getStoredUploadUrl(path) {
    if (!path) {
        return '';
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return BASE_URL + String(path).replace(/^\/+/, '');
}

function formatShiftTimestamp(value) {
    if (!value) {
        return 'Not recorded';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function renderAttendancePhotoCard(label, imagePath, timestamp) {
    const imageUrl = getStoredUploadUrl(imagePath);
    const safeLabel = escapeHtml(label);

    if (!imageUrl) {
        return `
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem; background: #f9fafb;">
                <div style="font-weight: 600; color: #374151; margin-bottom: 0.35rem;">${safeLabel}</div>
                <div style="color: #6b7280; font-size: 0.875rem;">No photo recorded</div>
                <div style="color: #6b7280; font-size: 0.8125rem; margin-top: 0.25rem;">${formatShiftTimestamp(timestamp)}</div>
            </div>
        `;
    }

    return `
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem; background: #fff;">
            <div style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">${safeLabel}</div>
            <a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener" style="display: block;">
                <img src="${escapeHtml(imageUrl)}" alt="${safeLabel}" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
            </a>
            <div style="color: #6b7280; font-size: 0.8125rem; margin-top: 0.5rem;">${formatShiftTimestamp(timestamp)}</div>
            <a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="margin-top: 0.5rem;">
                <i class="fas fa-external-link-alt"></i> Open full size
            </a>
        </div>
    `;
}

function getShiftLateReason(shift) {
    const notes = shift.notes || '';
    const match = notes.match(/Late check-in reason:\s*([^\r\n]+)/i);
    return match ? match[1].trim() : '';
}

function showShiftLateReason(reason) {
    alert(reason || 'No late check-in reason recorded.');
}

function renderAttendanceVerification(shift) {
    if (!shift.checkin_image && !shift.checkout_image && !shift.checkin_timestamp && !shift.checkout_timestamp) {
        return '';
    }

    const lateReason = getShiftLateReason(shift);

    return `
        <div class="row mb-3">
            <div class="col-12">
                <h6><i class="fas fa-camera"></i> Attendance Verification</h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                    ${renderAttendancePhotoCard('Check-in Photo', shift.checkin_image, shift.checkin_timestamp)}
                    ${renderAttendancePhotoCard('Check-out Photo', shift.checkout_image, shift.checkout_timestamp)}
                </div>
                ${lateReason ? `
                    <button type="button" class="btn btn-sm btn-outline-warning" style="margin-top: 0.75rem;" onclick='showShiftLateReason(${JSON.stringify(lateReason)})'>
                        <i class="fas fa-exclamation-triangle"></i> View late check-in reason
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

function showFullShiftDetails() {
    if (!currentShiftData) return;
    
    const shift = currentShiftData;
    
    // Calculate shift duration
    const startTime = new Date('2000-01-01 ' + shift.start_time);
    const endTime = new Date('2000-01-01 ' + shift.end_time);
    if (endTime < startTime) {
        endTime.setDate(endTime.getDate() + 1); // Next day
    }
    const durationMs = endTime - startTime;
    const durationHours = Math.round((durationMs / (1000 * 60 * 60)) * 100) / 100;
    const durationMinutes = Math.round((durationMs / (1000 * 60)) % 60);
    
    const content = `
        <div class="shift-details-modal">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6><i class="fas fa-calendar"></i> Date & Time</h6>
                    <p class="mb-1"><strong>Date:</strong> ${formatDate(shift.shift_date)}</p>
                    <p class="mb-1"><strong>Start:</strong> ${formatTime24(shift.start_time)}</p>
                    <p class="mb-1"><strong>End:</strong> ${formatTime24(shift.end_time)}</p>
                    <p class="mb-1"><strong>Duration:</strong> ${durationHours} hours ${durationMinutes > 0 ? durationMinutes + ' minutes' : ''}</p>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user"></i> Assignment</h6>
                    <p class="mb-1"><strong>Officer:</strong> ${shift.officer_id ? shift.first_name + ' ' + shift.last_name : 'Unallocated'}</p>
                    <p class="mb-1"><strong>Role:</strong> ${shift.role_name || shift.role || 'Not specified'}</p>
                    <p class="mb-1"><strong>Status:</strong> 
                        <span class="badge bg-${getStatusColor(shift.status)}">${(shift.status || 'unallocated').charAt(0).toUpperCase() + (shift.status || 'unallocated').slice(1)}</span>
                    </p>
                </div>
            </div>
            
            ${shift.notes ? `
                <div class="row mb-3">
                    <div class="col-12">
                        <h6><i class="fas fa-sticky-note"></i> Notes</h6>
                        <p class="bg-light p-2 rounded">${shift.notes}</p>
                    </div>
                </div>
            ` : ''}

            ${renderAttendanceVerification(shift)}
            
            <div class="row">
                <div class="col-12">
                    <h6><i class="fas fa-map-marker-alt"></i> Location</h6>
                    <p class="mb-1"><strong>Site:</strong> ${shift.site_name || 'Unknown Site'}</p>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary btn-sm" onclick="editShift(${shift.id})">
                    <i class="fas fa-edit"></i> Edit Shift
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="closeModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    `;
    
    hideShiftPopup();
    showModal('Shift Details - ' + formatDate(shift.shift_date), content);
}

function editShiftFromPopup() {
    if (!currentShiftData) return;
    hideShiftPopup();
    editShiftWithStatusCheck(currentShiftData.id, currentShiftData.status);
}

function showShiftDetails(shiftId) {
    // For when clicking on shift item - find the shift data and show full details
    if (currentShiftData && currentShiftData.id == shiftId) {
        showFullShiftDetails();
    } else {
        // Redirect to edit page as fallback
        editShift(shiftId);
    }
}

// Function to handle shift editing with status check for declined shifts
function editShiftWithStatusCheck(shiftId, status) {
    // Check if this is a declined shift and user is not admin
    if (status === 'declined' && !window.isAdmin) {
        alert('This shift has been declined and cannot be modified.');
        return; // Exit for non-admin users
    }
    
    // For admin users or non-declined shifts, proceed with normal edit
    // The confirmation for declined shifts will be handled within the editShift function
    editShift(shiftId);
}

// Helper functions
function formatTime24(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function getStatusColor(status) {
    switch(status) {
        case 'confirmed': return 'success';
        case 'in_progress': return 'info';
        case 'completed': return 'primary';
        case 'allocated': return 'warning';
        case 'declined': return 'secondary';
        case 'unallocated': return 'danger';
        default: return 'secondary';
    }
}

// Close popup when clicking outside
document.addEventListener('click', function(event) {
    const popup = document.getElementById('shiftPopup');
    if (popup && !popup.contains(event.target) && !event.target.closest('.calendar-shift-item')) {
        hideShiftPopup();
    }
});

// Keep popup open when hovering over it
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('shiftPopup');
    if (popup) {
        popup.addEventListener('mouseenter', function() {
            clearTimeout(popupTimeout);
        });
        
        popup.addEventListener('mouseleave', function() {
            hideShiftPopup();
        });
    }
    
    // Handle ESC key to close modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal();
            }
        }
    });
    
    // Handle click outside modal to close
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('genericModal');
        if (modal && modal.style.display === 'block' && event.target === modal) {
            closeModal();
        }
    });
});

function showModal(title, content) {
    const modal = document.getElementById('genericModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    if (!modal || !modalTitle || !modalBody) {
        console.error('Modal elements not found');
        return;
    }
    
    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Show modal with custom animation (matching rota.php style)
    modal.style.display = 'block';
    modal.style.opacity = '0';
    requestAnimationFrame(() => {
        modal.style.opacity = '1';
    });
}

function closeModal() {
    const modal = document.getElementById('genericModal');
    if (modal) {
        // Restore body scroll
        document.body.style.overflow = '';
        
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.opacity = '1';
        }, 200);
    }
}

function showCreateShiftModal(siteId) {
    const content = `
        <form id="createShiftForm" onsubmit="createShift(event)">
            <input type="hidden" name="site_id" value="${siteId}">
            <div class="form-group mb-3">
                <label for="shift_date" class="form-label">Date:</label>
                <input type="date" name="shift_date" id="shift_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="start_time" class="form-label">Start Time:</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" value="09:00" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="end_time" class="form-label">End Time:</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" value="17:00" required>
                    </div>
                </div>
            </div>
            <div class="form-group mb-3">
                <label for="officer_id" class="form-label">Officer (Optional):</label>
                ${renderSiteOfficerPicker('site_create_officer_id')}
                <div id="site_create_officer_link_container"></div>
            </div>
            <div class="form-group mb-3" style="display: none;" id="customRateGroupSite">
                <label for="custom_officer_rate" class="form-label">
                    Custom Officer Rate (£/hour):
                    <small class="text-muted">(Optional - overrides officer's default rate)</small>
                </label>
                <input type="number" 
                       name="custom_officer_rate" 
                       id="custom_officer_rate"
                       class="form-control" 
                       step="0.01" 
                       min="0" 
                       placeholder="Leave blank to use officer's default rate">
                <small class="form-text text-muted">
                    Only set this if you want to pay a different rate for this specific shift
                </small>
            </div>
            <div class="form-group mb-3">
                <label for="role" class="form-label">Role:</label>
                <select name="role_id" id="role" class="form-select" required>
                    ${getRoleOptions()}
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="notes" class="form-label">Notes:</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional notes about this shift..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Shift
                </button>
            </div>
        </form>
    `;
    showModal('Create New Shift', content);
    
    // Setup officer info link after modal is shown
    setTimeout(() => {
        initSiteOfficerPicker('site_create_officer_id', 'site_create_officer_link_container');
    }, 100);
}

function createShiftForDate(date, siteId) {
    const content = `
        <form id="createShiftForm" onsubmit="createShift(event)">
            <input type="hidden" name="site_id" value="${siteId}">
            <input type="hidden" name="shift_date" value="${date}">
            <div class="form-group mb-3">
                <label for="display_date" class="form-label">Date:</label>
                <input type="date" id="display_date" class="form-control" value="${date}" readonly style="background-color: #f8f9fa;">
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="start_time" class="form-label">Start Time:</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" value="09:00" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="end_time" class="form-label">End Time:</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" value="17:00" required>
                    </div>
                </div>
            </div>
            <div class="form-group mb-3">
                <label for="officer_id" class="form-label">Officer (Optional):</label>
                ${renderSiteOfficerPicker('site_date_officer_id')}
                <div id="site_date_officer_link_container"></div>
            </div>
            <div class="form-group mb-3" style="display: none;" id="customRateGroupSite">
                <label for="custom_officer_rate" class="form-label">
                    Custom Officer Rate (£/hour):
                    <small class="text-muted">(Optional - overrides officer's default rate)</small>
                </label>
                <input type="number" 
                       name="custom_officer_rate" 
                       id="custom_officer_rate"
                       class="form-control" 
                       step="0.01" 
                       min="0" 
                       placeholder="Leave blank to use officer's default rate">
                <small class="form-text text-muted">
                    Only set this if you want to pay a different rate for this specific shift
                </small>
            </div>
            <div class="form-group mb-3">
                <label for="role" class="form-label">Role:</label>
                <select name="role_id" id="role" class="form-select" required>
                    ${getRoleOptions()}
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="notes" class="form-label">Notes:</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional notes about this shift..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Shift
                </button>
            </div>
        </form>
    `;
    showModal('Create Shift for ' + date, content);
    
    // Setup officer info link after modal is shown
    setTimeout(() => {
        initSiteOfficerPicker('site_date_officer_id', 'site_date_officer_link_container');
    }, 100);
}

function createShift(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    const formData = new FormData(form);

    fetch(BASE_URL + 'api/create_shift.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Shift created successfully', 'success');
            closeModal();
            // Reload the page to show the new shift
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error creating shift: ' + (data.message || 'Unknown error'), 'error');
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showNotification('Error creating shift: Network error', 'error');
        console.error('Error:', error);
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function showNotification(message, type) {
    // Simple notification system
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Template functionality
function showTemplateModal(siteId) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h5>Create New Template</h5>
                <form id="createTemplateForm" onsubmit="createTemplate(event, '${siteId}')">
                    <div class="form-group mb-3">
                        <label>Template Name:</label>
                        <input type="text" name="template_name" class="form-control" required placeholder="e.g. Weekday Coverage, 24/7 Security">
                    </div>
                    <div class="form-group mb-3">
                        <label>Copy from Week:</label>
                        <input type="date" name="source_week" class="form-control" value="<?php echo isset($week_start) ? $week_start : date('Y-m-d', strtotime('monday this week')); ?>" required>
                        <small class="text-muted">Select a week that has the shifts you want to use as a template</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Template</button>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Available Templates</h5>
                <div id="templatesList">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p>Loading templates...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    showModal('Site Rota Templates', content);
    loadSiteTemplates(siteId);
}

function createTemplate(event, siteId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'create_template');
    formData.append('site_id', siteId);

    fetch(BASE_URL + 'api/site_rota.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Template created successfully', 'success');
            loadSiteTemplates(siteId);
            document.getElementById('createTemplateForm').reset();
        } else {
            showNotification('Error creating template: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error creating template', 'error');
        console.error('Error:', error);
    });
}

function loadSiteTemplates(siteId) {
    fetch(`${BASE_URL}api/get_site_templates.php?site_id=${siteId}`)
    .then(response => response.json())
    .then(data => {
        const templatesList = document.getElementById('templatesList');
        if (data.success && data.templates.length > 0) {
            let html = '';
            data.templates.forEach(template => {
                html += `
                    <div class="card mb-2">
                        <div class="card-body p-3">
                            <h6 class="card-title mb-1">${template.name}</h6>
                            <small class="text-muted">${template.shifts_count} shifts • Created ${template.created_at}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-success" onclick="applyTemplate('${siteId}', '${template.name}')">
                                    Apply Template
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate('${siteId}', '${template.name}')">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            templatesList.innerHTML = html;
        } else {
            templatesList.innerHTML = '<p class="text-muted">No templates found for this site.</p>';
        }
    })
    .catch(error => {
        document.getElementById('templatesList').innerHTML = '<p class="text-danger">Error loading templates</p>';
    });
}

function applyTemplate(siteId, templateName) {
    const targetWeek = prompt('Enter the start date of the week to apply template to (YYYY-MM-DD):', '<?php echo isset($week_start) ? $week_start : date('Y-m-d', strtotime('monday this week')); ?>');
    if (!targetWeek) return;

    const formData = new FormData();
    formData.append('action', 'apply_template');
    formData.append('site_id', siteId);
    formData.append('template_name', templateName);
    formData.append('target_week_start', targetWeek);

    fetch(BASE_URL + 'api/site_rota.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal();
            location.reload();
        } else {
            showNotification('Error applying template: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error applying template', 'error');
        console.error('Error:', error);
    });
}

function generateFromTemplate(siteId) {
    const content = `
        <h5>Generate Recurring Shifts</h5>
        <form id="generateRecurringForm" onsubmit="generateRecurring(event, '${siteId}')">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Start Date:</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo isset($week_start) ? $week_start : date('Y-m-d', strtotime('monday this week')); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>End Date:</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime((isset($week_start) ? $week_start : date('Y-m-d', strtotime('monday this week'))) . ' +4 weeks')); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label>Pattern:</label>
                <select name="pattern" class="form-control" required onchange="toggleCustomDays(this.value)">
                    <option value="">Select Pattern</option>
                    <option value="daily">Every Day</option>
                    <option value="weekdays">Weekdays Only (Mon-Fri)</option>
                    <option value="weekends">Weekends Only (Sat-Sun)</option>
                    <option value="custom">Custom Days</option>
                </select>
            </div>
            
            <div id="customDaysSection" style="display: none;">
                <div class="form-group mb-3">
                    <label>Select Days:</label>
                    <div class="form-check-group">
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="1" class="form-check-input" id="mon">
                            <label class="form-check-label" for="mon">Mon</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="2" class="form-check-input" id="tue">
                            <label class="form-check-label" for="tue">Tue</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="3" class="form-check-input" id="wed">
                            <label class="form-check-label" for="wed">Wed</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="4" class="form-check-input" id="thu">
                            <label class="form-check-label" for="thu">Thu</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="5" class="form-check-input" id="fri">
                            <label class="form-check-label" for="fri">Fri</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="6" class="form-check-input" id="sat">
                            <label class="form-check-label" for="sat">Sat</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="selected_days[]" value="0" class="form-check-input" id="sun">
                            <label class="form-check-label" for="sun">Sun</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="shiftDetailsSection">
                <h6>Shift Details</h6>
                <div id="shiftDetails">
                    <div class="shift-detail-row border rounded p-3 mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Start Time:</label>
                                <input type="time" name="shift_details[0][start_time]" class="form-control" value="09:00" required>
                            </div>
                            <div class="col-md-3">
                                <label>End Time:</label>
                                <input type="time" name="shift_details[0][end_time]" class="form-control" value="17:00" required>
                            </div>
                            <div class="col-md-3">
                                <label>Role:</label>
                                <select name="shift_details[0][role_id]" class="form-control role-select" required>
                                    <!-- Roles will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Notes:</label>
                                <input type="text" name="shift_details[0][notes]" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addShiftDetail()">
                    <i class="fas fa-plus"></i> Add Another Shift
                </button>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-success">Generate Recurring Shifts</button>
            </div>
        </form>
    `;
    showModal('Generate Recurring Shifts', content);
}

function toggleCustomDays(pattern) {
    const customSection = document.getElementById('customDaysSection');
    customSection.style.display = pattern === 'custom' ? 'block' : 'none';
}

let shiftDetailCount = 1;
function addShiftDetail() {
    const shiftDetails = document.getElementById('shiftDetails');
    const newShiftHtml = `
        <div class="shift-detail-row border rounded p-3 mb-2">
            <div class="row">
                <div class="col-md-3">
                    <label>Start Time:</label>
                    <input type="time" name="shift_details[${shiftDetailCount}][start_time]" class="form-control" value="18:00" required>
                </div>
                <div class="col-md-3">
                    <label>End Time:</label>
                    <input type="time" name="shift_details[${shiftDetailCount}][end_time]" class="form-control" value="06:00" required>
                </div>
                <div class="col-md-3">
                    <label>Role:</label>
                    <select name="shift_details[${shiftDetailCount}][role_id]" class="form-control role-select" required>
                        <!-- Roles will be populated by JavaScript -->
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Notes:</label>
                    <input type="text" name="shift_details[${shiftDetailCount}][notes]" class="form-control" placeholder="Optional">
                </div>
                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    shiftDetails.insertAdjacentHTML('beforeend', newShiftHtml);
    
    // Populate roles in the newly added select element
    const newRoleSelect = shiftDetails.querySelector(`select[name="shift_details[${shiftDetailCount}][role_id]"]`);
    if (newRoleSelect && ALL_ROLES.length > 0) {
        newRoleSelect.innerHTML = getRoleOptions(ALL_ROLES[0].id);
    }
    
    shiftDetailCount++;
}

function generateRecurring(event, siteId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'generate_recurring');
    formData.append('site_id', siteId);

    fetch(BASE_URL + 'api/site_rota.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal();
            location.reload();
        } else {
            showNotification('Error generating shifts: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error generating shifts', 'error');
        console.error('Error:', error);
    });
}

function showBulkScheduleModal(siteId) {
    // Redirect to main rota page with site filter
    window.location.href = `rota.php?site_filter=${siteId}`;
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
