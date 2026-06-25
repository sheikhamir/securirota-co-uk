<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Rota Planner';
require_once '../includes/header.php';

// Debug: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("ROTA PAGE DEBUG: No user_id in session");
} else {
    error_log("ROTA PAGE DEBUG: user_id = " . $_SESSION['user_id']);
}

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
    
    // Initialize default variables
    $officers = [];
    $sites = [];
    $shifts = [];
    $week_dates = [];
    $shift_grid = [];
    
    // Get current week or specified week - always normalize to Monday
    $requested_week = $_GET['week'] ?? date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('monday this week', strtotime($requested_week)));
    $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
    
    // Get site filter
    $site_filter = $_GET['site_filter'] ?? '';
    
    // Navigation dates
    $prev_week = date('Y-m-d', strtotime($week_start . ' -7 days'));
    $next_week = date('Y-m-d', strtotime($week_start . ' +7 days'));
    
    // Get active officers - filter by site if specified
    if ($site_filter) {
        // Get officers who have shifts for the selected site in the current week
        // This will show only officers who actually have assigned shifts at this site
        $officer_sql = "
            SELECT DISTINCT o.id, o.staff_id, o.first_name, o.last_name 
            FROM officers o
            INNER JOIN shifts s ON o.id = s.officer_id
            WHERE o.employment_status != 'Inactive' 
            AND s.site_id = ? 
            AND s.shift_date BETWEEN ? AND ?";
        
        $params = [$site_filter, $week_start, $week_end];
        
        if ($use_company_filter && $company_id) {
            $officer_sql .= " AND o.company_id = ?";
            $params[] = $company_id;
        }
        
        $officer_sql .= " ORDER BY o.first_name";
        
        $stmt = $conn->prepare($officer_sql);
        $stmt->execute($params);
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if there are unallocated shifts for this site - if so, we need to show at least one row
        // This prevents the "No officers have shifts" message when there are unallocated shifts
        $unallocated_check_sql = "
            SELECT COUNT(*) as unallocated_count
            FROM shifts s
            WHERE s.site_id = ? 
            AND s.shift_date BETWEEN ? AND ?
            AND s.officer_id IS NULL";
        
        $unallocated_params = [$site_filter, $week_start, $week_end];
        
        if ($use_company_filter && $company_id) {
            $unallocated_check_sql .= " AND s.company_id = ?";
            $unallocated_params[] = $company_id;
        }
        
        $stmt = $conn->prepare($unallocated_check_sql);
        $stmt->execute($unallocated_params);
        $unallocated_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If there are unallocated shifts but no officers with assigned shifts,
        // we need to show an empty table structure so unallocated shifts are visible
        if ($unallocated_result['unallocated_count'] > 0 && empty($officers)) {
            // Don't show any officer rows, but the unallocated shifts section will still appear
            $officers = [];
        }
    } else {
        // Get all active officers when no site filter is applied
        if ($use_company_filter && $company_id) {
            $stmt = $conn->prepare("SELECT id, staff_id, first_name, last_name, phone FROM officers WHERE employment_status != 'Inactive' AND company_id = ? ORDER BY first_name");
            $stmt->execute([$company_id]);
        } else {
            $stmt = $conn->query("SELECT id, staff_id, first_name, last_name, phone FROM officers WHERE employment_status != 'Inactive' ORDER BY first_name");
        }
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Always get all active officers for modals/forms (not filtered by site)
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("SELECT id, staff_id, first_name, last_name, phone FROM officers WHERE employment_status != 'Inactive' AND company_id = ? ORDER BY first_name");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->query("SELECT id, staff_id, first_name, last_name, phone FROM officers WHERE employment_status != 'Inactive' ORDER BY first_name");
    }
    $all_officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active sites with shift statistics
    $sites_sql = "
        SELECT s.id, s.site_name, c.company_name as client_name,
               COUNT(sh.id) as total_shifts,
               COUNT(CASE WHEN sh.id IS NOT NULL AND sh.officer_id IS NULL THEN 1 END) as unallocated_shifts,
               COUNT(CASE WHEN sh.id IS NOT NULL AND sh.officer_id IS NOT NULL THEN 1 END) as allocated_shifts,
               COUNT(CASE WHEN sh.id IS NOT NULL AND sh.status = 'confirmed' THEN 1 END) as confirmed_shifts,
               COUNT(CASE WHEN sh.id IS NOT NULL AND sh.shift_date BETWEEN ? AND ? THEN 1 END) as week_shifts
        FROM sites s 
        JOIN clients c ON s.client_id = c.id 
        LEFT JOIN shifts sh ON s.id = sh.site_id AND sh.shift_date BETWEEN ? AND ?
        WHERE s.status = 'active'";
    
    $sites_params = [$week_start, $week_end, $week_start, $week_end];
    
    if ($use_company_filter && $company_id) {
        $sites_sql .= " AND s.company_id = ?";
        $sites_params[] = $company_id;
    }
    
    $sites_sql .= " GROUP BY s.id, s.site_name, c.company_name
                    ORDER BY c.company_name, s.site_name";
    
    $stmt = $conn->prepare($sites_sql);
    $stmt->execute($sites_params);
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current site information if filtering by site
    $current_site = null;
    if ($site_filter) {
        $current_site = array_filter($sites, function($s) use ($site_filter) { 
            return $s['id'] == $site_filter; 
        });
        $current_site = !empty($current_site) ? array_values($current_site)[0] : null;
    }
    
    // Get shifts for the week
    $shift_sql = "
        SELECT s.*, o.first_name, o.last_name, st.site_name as site_name, c.company_name as client_name,
               r.id as role_id, r.name as role_name
        FROM shifts s
        LEFT JOIN officers o ON s.officer_id = o.id
        LEFT JOIN sites st ON s.site_id = st.id
        LEFT JOIN clients c ON st.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        WHERE s.shift_date BETWEEN ? AND ?";
    
    $shift_params = [$week_start, $week_end];
    
    // Add company filter
    if ($use_company_filter && $company_id) {
        $shift_sql .= " AND s.company_id = ?";
        $shift_params[] = $company_id;
    }
    
    // Add site filter if specified
    if ($site_filter) {
        $shift_sql .= " AND s.site_id = ?";
        $shift_params[] = $site_filter;
    }
    
    $shift_sql .= " ORDER BY s.shift_date, s.start_time";
    
    $stmt = $conn->prepare($shift_sql);
    $stmt->execute($shift_params);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize shifts by date and officer
    $shift_grid = [];
    foreach ($shifts as $shift) {
        $date = $shift['shift_date'];
        $officer_id = $shift['officer_id'] ?: 'unallocated';
        $shift_grid[$date][$officer_id][] = $shift;
    }
    
    // Get week dates
    $week_dates = [];
    for ($i = 0; $i < 7; $i++) {
        $week_dates[] = date('Y-m-d', strtotime($week_start . " +$i days"));
    }
    
} catch (Exception $e) {
    $error = "Error loading rota data: " . $e->getMessage();
    // Ensure variables are initialized even on error
    $officers = $officers ?? [];
    $sites = $sites ?? [];
    $shifts = $shifts ?? [];
    $week_dates = $week_dates ?? [];
    $shift_grid = $shift_grid ?? [];
    
    // Initialize week dates if not already done
    if (empty($week_dates) && isset($week_start)) {
        for ($i = 0; $i < 7; $i++) {
            $week_dates[] = date('Y-m-d', strtotime($week_start . " +$i days"));
        }
    }
}
?>

<style>
/* Professional Rota Page Styling - Compact Version */
:root {
    --primary-blue: #4f46e5;
    --primary-blue-dark: #3730a3;
    --success-green: #10b981;
    --warning-yellow: #f59e0b;
    --danger-red: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.03);
    --shadow-md: 0 2px 4px -1px rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06);
    --shadow-lg: 0 4px 8px -2px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.06);
    --shadow-xl: 0 8px 16px -4px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.06);
}

.rota-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-size: 100% 100%;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: visible;
}

.rota-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
    border-radius: 8px;
}

.week-navigation {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.week-display {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    text-align: center;
    line-height: 1.2;
}

.week-display small {
    font-size: 0.75rem;
    opacity: 0.9;
    font-weight: 500;
    display: block;
    margin-top: 0.125rem;
}

.rota-header .d-flex {
    position: relative;
    z-index: 1;
}

.rota-grid {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
}

.rota-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.75rem;
}

.rota-table th,
.rota-table td {
    border-right: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
    padding: 0.5rem;
    text-align: center;
    vertical-align: top;
    min-width: 100px;
    position: relative;
}

.rota-table th:last-child,
.rota-table td:last-child {
    border-right: none;
}

.rota-table th {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    font-weight: 700;
    color: var(--gray-800);
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 1px solid var(--gray-300);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6875rem;
    padding: 0.625rem 0.5rem;
}

.rota-table .officer-cell {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    font-weight: 700;
    text-align: left;
    min-width: 140px;
    position: sticky;
    left: 0;
    z-index: 5;
    color: var(--gray-800);
    border-right: 1px solid var(--gray-300);
    font-size: 0.75rem;
    padding: 0.625rem 0.75rem;
}

.shift-item {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    color: white;
    padding: 0.375rem;
    border-radius: 6px;
    margin: 0.125rem 0;
    font-size: 0.6875rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.shift-item:hover {
    transform: translateY(-1px) scale(1.01);
    box-shadow: var(--shadow-md);
    z-index: 10;
}

.shift-item.confirmed {
    background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
    border-color: rgba(255, 255, 255, 0.2);
}

.shift-item.declined {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border-color: rgba(255, 255, 255, 0.2);
}

.shift-item.unallocated {
    background: linear-gradient(135deg, var(--gray-500) 0%, var(--gray-600) 100%);
    border-color: rgba(255, 255, 255, 0.1);
}

.shift-item.allocated {
    background: linear-gradient(135deg, var(--warning-yellow) 0%, #d97706 100%);
    color: var(--gray-800);
    border-color: rgba(0, 0, 0, 0.1);
}

.shift-item.cancelled {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    border-color: rgba(255, 255, 255, 0.1);
    opacity: 0.7;
    text-decoration: line-through;
}

.shift-actions {
    margin-top: 0.25rem;
    display: flex;
    gap: 0.25rem;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.shift-item:hover .shift-actions {
    opacity: 1;
}

.shift-action-btn {
    padding: 0.25rem 0.5rem;
    border: none;
    border-radius: 4px;
    font-size: 0.625rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-sm);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.125rem;
}

.shift-action-btn:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-md);
}

.shift-action-btn.confirm {
    background: var(--success-green);
    color: white;
}

.shift-action-btn.confirm:hover {
    background: #059669;
}

.shift-action-btn.reschedule {
    background: var(--gray-600);
    color: white;
}

.shift-action-btn.reschedule:hover {
    background: var(--gray-700);
}

.shift-action-btn.cancel {
    background: var(--danger-red);
    color: white;
}

.shift-action-btn.cancel:hover {
    background: #dc2626;
}

.shift-action-btn.confirmed {
    background: var(--success-green);
    color: white;
    opacity: 0.9;
    cursor: pointer;
    font-size: 0.625rem;
    transition: all 0.2s ease;
}

.shift-action-btn.confirmed .text-confirm {
    display: inline;
}
.shift-action-btn.confirmed .text-unconfirm {
    display: none;
}

.shift-action-btn.confirmed:hover {
    opacity: 1;
    background: var(--danger-red);
}

.shift-action-btn.confirmed:hover .text-confirm {
    display: none;
}
.shift-action-btn.confirmed:hover .text-unconfirm {
    display: inline;
}

.shift-actions {
    opacity: 1;
}

.shift-item.cancelled .shift-actions {
    opacity: 1;
}

.shift-status.cancelled {
    margin-top: 0.25rem;
    padding: 0.125rem 0.25rem;
    background: rgba(107, 114, 128, 0.8);
    color: white;
    border-radius: 4px;
    font-size: 0.625rem;
    text-align: center;
    font-weight: 600;
}

.shift-time {
    font-weight: 700;
    display: block;
    font-size: 0.75rem;
    margin-bottom: 0.125rem;
    line-height: 1.2;
}

.shift-site {
    font-size: 0.625rem;
    opacity: 0.9;
    display: block;
    margin-top: 0.125rem;
    font-weight: 500;
    line-height: 1.2;
}

.rate-indicator {
    font-size: 0.6rem;
    font-weight: 600;
    margin-top: 0.25rem;
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
    display: inline-block;
    line-height: 1;
}

.rate-indicator.custom {
    background: rgba(255, 215, 0, 0.9);
    color: #4b5563;
    border: 1px solid rgba(255, 215, 0, 0.6);
}

.rate-indicator.default {
    background: rgba(255, 255, 255, 0.2);
    color: inherit;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.rate-indicator i {
    font-size: 0.5rem;
    margin-right: 0.125rem;
}

.day-cell {
    min-height: 80px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.day-cell:hover {
    background: var(--gray-50);
}

.day-cell.weekend {
    background: linear-gradient(135deg, var(--gray-50) 0%, #fafafa 100%);
}

.day-cell.weekend:hover {
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-50) 100%);
}

.add-shift-btn {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: rgba(79, 70, 229, 0.1);
    border: 1px dashed var(--primary-blue);
    color: var(--primary-blue);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
    font-weight: 600;
}

.day-cell:hover .add-shift-btn {
    opacity: 1;
    transform: scale(1.05);
}

.add-shift-btn:hover {
    background: var(--primary-blue);
    color: white;
    transform: scale(1.1);
}

.unallocated-shifts {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid var(--warning-yellow);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-md);
}

.unallocated-shifts h4 {
    color: #92400e;
    margin-bottom: 0.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 1rem;
}

.unallocated-shifts h4 i {
    font-size: 1rem;
}

.drag-drop-zone {
    min-height: 40px;
    border: 1px dashed var(--gray-300);
    border-radius: 6px;
    padding: 0.5rem;
    text-align: center;
    color: var(--gray-500);
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.875rem;
}

.drag-drop-zone:hover,
.drag-drop-zone.drag-over {
    border-color: var(--primary-blue);
    background: rgba(79, 70, 229, 0.05);
    color: var(--primary-blue);
    transform: scale(1.01);
}

.cb-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    font-size: 0.75rem;
}

.cb-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
    color: white;
    text-decoration: none;
}

.cb-btn > .icon {
    font-size: 0.875rem;
}

.cb-btn > .text {
    display: flex;
    flex-direction: column;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1;
    letter-spacing: 0.05em;
}

.cb-btn > .text > span {
    display: block;
}

.cb-btn-left > .text > span {
    text-align: left;
}

.cb-btn-right > .text > span {
    text-align: right;
}

/* Enhanced buttons in header - Compact */
.rota-header .btn {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.rota-header .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
    color: white;
}

.rota-header .btn i {
    margin-right: 0.25rem;
    font-size: 0.75rem;
}

/* Form controls in header - Compact */
.rota-header .form-select {
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    backdrop-filter: blur(10px);
    font-weight: 500;
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
}

.rota-header .form-select:focus {
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 0.1rem rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.3);
}

.rota-header .form-select option {
    background: var(--gray-800);
    color: white;
}

.rota-header .form-label {
    color: white;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

/* Responsive adjustments - Compact */
@media (max-width: 768px) {
    .rota-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        padding: 0.75rem;
    }
    
    .week-navigation {
        justify-content: center;
        gap: 0.5rem;
    }
    
    .rota-header .d-flex {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .cb-btn {
        padding: 0.5rem;
        font-size: 0.6875rem;
    }
    
    .rota-table th,
    .rota-table td {
        padding: 0.375rem 0.25rem;
        min-width: 80px;
    }
    
    .rota-table .officer-cell {
        min-width: 100px;
    }

    .day-cell {
        min-height: 60px;
    }

    .shift-item {
        padding: 0.25rem;
        font-size: 0.625rem;
    }
    
    /* Mobile responsive for site search */
    .site-search-container {
        min-width: 200px !important;
    }
    
    #siteSearchResults {
        max-height: 200px !important;
        font-size: 0.875rem;
    }
    
    .search-result-item {
        padding: 8px 10px !important;
    }
    
    .search-result-item div:first-child {
        font-size: 0.875rem !important;
    }
    
    .search-result-item div:last-child {
        font-size: 0.75rem !important;
    }
    
    .search-no-results,
    .search-error,
    .search-loading {
        padding: 8px 10px !important;
        font-size: 0.75rem !important;
    }
}

/* Enhanced legend styling - Compact */
.card.mt-20 {
    border-radius: 8px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
    padding: 1rem;
    margin-bottom: 1rem;
    margin-top: 1rem;
}

.card.mt-20 .card-header {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 700;
    color: var(--gray-800);
    padding: 0.75rem 1rem;
    margin: -1rem -1rem 1rem -1rem;
}

.card.mt-20 .card-header h3 {
    font-size: 1rem;
    margin: 0;
}

/* Site Search Autocomplete Styles */
.search-result-item {
    transition: all 0.15s ease;
}

.search-result-item:hover {
    background-color: var(--gray-50) !important;
}

.search-result-item.selected {
    background-color: var(--primary-blue) !important;
    color: white;
}

.search-result-item.selected div {
    color: white !important;
}

.search-result-item:last-child {
    border-bottom: none;
}

#siteSearchInput {
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    backdrop-filter: blur(10px);
    font-weight: 500;
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
    transition: all 0.2s ease;
}

#siteSearchInput:focus {
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 0.1rem rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.3);
    outline: none;
}

#siteSearchInput::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

#siteSearchResults {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d1d5db;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 9999;
    display: none;
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.05);
    margin-top: 1px;
}

.search-result-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.15s ease;
    background: white;
}

.search-result-item:hover {
    background-color: #f8fafc !important;
}

.search-result-item.selected {
    background-color: var(--primary-blue) !important;
    color: white;
}

.search-result-item.selected div {
    color: white !important;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item div:first-child {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.search-result-item div:last-child {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 2px;
}

.search-no-results {
    padding: 12px;
    color: #6b7280;
    font-style: italic;
    text-align: center;
}

.search-error {
    padding: 12px;
    color: #ef4444;
    text-align: center;
}

.search-loading {
    padding: 12px;
    color: #6b7280;
    font-style: italic;
    text-align: center;
}

#clearSiteFilter {
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.7);
}

#clearSiteFilter:hover {
    color: #ef4444 !important;
    transform: scale(1.1);
}

/* Site Grid Styles */
.sites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.site-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.site-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.site-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-blue);
}

.site-card:hover::before {
    transform: scaleX(1);
}

.site-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.site-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    line-height: 1.3;
}

.client-name {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin: 0;
    margin-top: 0.25rem;
}

.site-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.site-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 8px;
    border: 1px solid var(--gray-100);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800);
    display: block;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-item.unallocated .stat-number {
    color: var(--warning-yellow);
}

.stat-item.confirmed .stat-number {
    color: var(--success-green);
}

.back-to-sites {
    margin-bottom: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 8px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
}

.back-to-sites .btn {
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.back-to-sites .btn:hover {
    background: linear-gradient(135deg, var(--gray-200) 0%, var(--gray-300) 100%);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
    color: var(--gray-800);
}

.site-header {
    display: flex;
    flex-direction: column;
}

.site-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800);
    line-height: 1.2;
}

.site-header small {
    color: var(--gray-600);
    font-size: 0.875rem;
    margin-top: 0.25rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .back-to-sites > div {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem !important;
    }
    
    .site-header h2 {
        font-size: 1.25rem;
    }
    
    .site-header small {
        font-size: 0.8rem;
    }
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0.5rem 0 0 0;
}

/* Mobile responsive modal improvements */
@media (max-width: 767.98px) {
    .modal-dialog {
        margin: 1vh auto !important;
        width: calc(100% - 1rem) !important;
        max-width: calc(100% - 1rem) !important;
        max-height: 98vh !important;
    }
    
    .modal-content {
        max-height: calc(98vh - 80px) !important;
        padding: 1rem !important;
    }
    
    .modal-header {
        padding: 0.75rem !important;
    }
    
    .modal-header h3 {
        font-size: 1.1rem !important;
    }
}

</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!$site_filter): ?>
    <!-- Site Overview Mode -->
    <div class="rota-header">
        <div class="week-navigation">
            <div class="week-display">
                <h1 class="page-title">Site Management</h1>
                <p class="page-subtitle">Select a site to view and manage its rota</p>
            </div>
        </div>
        
        <div class="d-flex gap-10 flex-wrap">
            <button onclick="showCreateShiftModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Shift
            </button>
            
            <button onclick="showBulkScheduleModal()" class="btn btn-success">
                <i class="fas fa-calendar-plus"></i> Bulk Schedule
            </button>
            
            <a href="site_rotas.php" class="btn btn-info">
                <i class="fas fa-building"></i> Site Templates
            </a>
        </div>
    </div>

    <!-- Sites Grid -->
    <div class="sites-grid">
        <?php if (empty($sites)): ?>
            <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <i class="fas fa-building" style="font-size: 48px; color: #6c757d; margin-bottom: 20px;"></i>
                <h3 style="color: #6c757d; margin-bottom: 15px;">No Sites Available</h3>
                <p style="color: #6c757d; margin-bottom: 25px; max-width: 400px; margin-left: auto; margin-right: auto;">
                    <?php if ($use_company_filter && $company_id): ?>
                        Your company doesn't have any sites set up yet. Contact your administrator to add sites, or if you're an admin, start by adding your first site.
                    <?php else: ?>
                        No active sites found in the system. Add some sites to get started with scheduling shifts.
                    <?php endif; ?>
                </p>
                <a href="../pages/sites.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Site
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($sites as $site): ?>
                <div class="site-card" onclick="window.location.href='?site_filter=<?php echo $site['id']; ?>&week=<?php echo $week_start; ?>'">
                    <div class="site-card-header">
                        <div>
                            <h3 class="site-name"><?php echo htmlspecialchars($site['site_name']); ?></h3>
                            <p class="client-name"><?php echo htmlspecialchars($site['client_name']); ?></p>
                        </div>
                        <div class="site-icon">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    
                    <div class="site-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $site['total_shifts'] ?? 0; ?></span>
                            <span class="stat-label">Total Shifts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $site['week_shifts'] ?? 0; ?></span>
                            <span class="stat-label">This Week</span>
                        </div>
                        <div class="stat-item unallocated">
                            <span class="stat-number"><?php echo $site['unallocated_shifts'] ?? 0; ?></span>
                            <span class="stat-label">Unallocated</span>
                        </div>
                        <div class="stat-item confirmed">
                            <span class="stat-number"><?php echo $site['confirmed_shifts'] ?? 0; ?></span>
                            <span class="stat-label">Confirmed</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Rota View Mode for Selected Site -->
    <div class="back-to-sites">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="?" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Sites Overview
                </a>
                <?php if ($current_site): ?>
                    <div class="site-header">
                        <h2><?php echo htmlspecialchars($current_site['site_name']); ?></h2>
                        <small><?php echo htmlspecialchars($current_site['client_name']); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Week Navigation -->
    <div class="rota-header">
        <div class="week-navigation">
            <a href="?week=<?php echo $prev_week; ?>&site_filter=<?php echo $site_filter; ?>" class="btn btn-secondary cb-btn cb-btn-left">
                <div class="icon"><i class="fas fa-chevron-left"></i></div>
                <div class="text"><span>Previous</span><span>Week</span></div>
            </a>
            
            <div class="week-display">
                Week of <?php echo date('jS M Y', strtotime($week_start)); ?> - <?php echo date('jS M Y', strtotime($week_end)); ?>
                <?php 
                $filtered_site = array_filter($sites, function($s) use ($site_filter) { return $s['id'] == $site_filter; });
                $filtered_site = reset($filtered_site);
                ?>
                <br><small class="text-muted">Site: <?php echo htmlspecialchars($filtered_site['site_name'] ?? 'Unknown Site'); ?></small>
            </div>
            
            <a href="?week=<?php echo $next_week; ?>&site_filter=<?php echo $site_filter; ?>" class="btn btn-secondary cb-btn cb-btn-right">
                <div class="text"><span>Next</span><span>Week</span></div>
                <div class="icon"><i class="fas fa-chevron-right"></i></div>
            </a>
        </div>
        
        <div class="d-flex gap-10 flex-wrap">
            <a href="?week=<?php echo date('Y-m-d', strtotime('monday this week')); ?>&site_filter=<?php echo $site_filter; ?>" class="btn btn-info">
                <i class="fas fa-calendar-day"></i> This Week
            </a>
            
            <button onclick="showCreateShiftModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Shift
            </button>
            
            <button onclick="showBulkScheduleModal()" class="btn btn-success">
                <i class="fas fa-calendar-plus"></i> Bulk Schedule
            </button>
        </div>
    </div>

    <!-- Unallocated Shifts -->
    <?php 
    $unallocated_shifts = [];
    foreach ($shifts as $shift) {
        if (!$shift['officer_id']) {
            $unallocated_shifts[] = $shift;
        }
    }
    if (!empty($unallocated_shifts)): 
    ?>
    <div class="unallocated-shifts">
        <h4><i class="fas fa-exclamation-triangle"></i> Unallocated Shifts (<?php echo count($unallocated_shifts); ?>)</h4>
        <?php if ($site_filter): ?>
            <p style="margin-bottom: 0.75rem; color: #92400e; font-size: 0.875rem;">
                <i class="fas fa-info-circle"></i> 
                These shifts need to be assigned to officers. Click on any shift to assign it to an officer.
            </p>
        <?php endif; ?>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($unallocated_shifts as $shift): ?>
                <div class="shift-item unallocated" data-shift-id="<?php echo $shift['id']; ?>" onclick="editShift(<?php echo $shift['id']; ?>)" draggable="true">
                    <span class="shift-time"><?php echo formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']); ?></span>
                    <small><?php echo formatDate($shift['shift_date']); ?></small>
                    <?php if ($shift['custom_officer_rate']): ?>
                        <div class="rate-indicator custom" title="Custom rate: £<?php echo number_format($shift['custom_officer_rate'], 2); ?>/hr">
                            <i class="fas fa-star"></i> £<?php echo number_format($shift['custom_officer_rate'], 2); ?>
                        </div>
                    <?php elseif ($shift['officer_rate']): ?>
                        <div class="rate-indicator default" title="Officer rate: £<?php echo number_format($shift['officer_rate'], 2); ?>/hr">
                            £<?php echo number_format($shift['officer_rate'], 2); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rota Grid -->
    <div class="rota-grid">
        <table class="rota-table">
            <thead>
                <tr>
                    <th class="officer-cell">Officer</th>
                    <?php foreach ($week_dates as $date): ?>
                        <th class="<?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>">
                            <?php echo date('D', strtotime($date)); ?><br>
                            <small><?php echo date('j M', strtotime($date)); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($officers)): ?>
                    <tr>
                        <td colspan="<?php echo count($week_dates) + 1; ?>" style="text-align: center; padding: 2rem; color: #6b7280; font-style: italic;">
                            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                            <?php if ($site_filter && !empty($unallocated_shifts)): ?>
                                No officers currently assigned to shifts for this site during this week.<br>
                                <small style="color: #f59e0b; font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    However, there are unallocated shifts shown above that need to be assigned.
                                </small>
                            <?php else: ?>
                                No officers have shifts for the selected site during this week.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($officers as $officer): ?>
                        <tr>
                            <td class="officer-cell">
                                <strong><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></strong>
                            </td>
                        <?php foreach ($week_dates as $date): ?>
                            <td class="day-cell <?php echo in_array(date('w', strtotime($date)), [0, 6]) ? 'weekend' : ''; ?>" 
                                data-date="<?php echo $date; ?>" 
                                data-officer-id="<?php echo $officer['id']; ?>"
                                ondrop="dropShift(event)" 
                                ondragover="allowDrop(event)">
                                
                                <?php 
                                $officer_shifts = $shift_grid[$date][$officer['id']] ?? [];
                                foreach ($officer_shifts as $shift): 
                                ?>
                                    <div class="shift-item <?php echo $shift['status']; ?>" 
                                         data-shift-id="<?php echo $shift['id']; ?>" 
                                         onclick="editShift(<?php echo $shift['id']; ?>)"
                                         draggable="true"
                                         ondragstart="dragShift(event, <?php echo $shift['id']; ?>)">
                                        <span class="shift-time"><?php echo formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']); ?></span>
                                    <small><?php echo ucfirst($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?></small>
                                    
                                    <?php if ($shift['custom_officer_rate']): ?>
                                        <div class="rate-indicator custom" title="Custom rate: £<?php echo number_format($shift['custom_officer_rate'], 2); ?>/hr (overrides default)">
                                            <i class="fas fa-star"></i> £<?php echo number_format($shift['custom_officer_rate'], 2); ?>
                                        </div>
                                    <?php elseif ($shift['officer_rate']): ?>
                                        <div class="rate-indicator default" title="Officer rate: £<?php echo number_format($shift['officer_rate'], 2); ?>/hr">
                                            £<?php echo number_format($shift['officer_rate'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($shift['status'] === 'allocated'): ?>
                                        <div class="shift-actions" onclick="event.stopPropagation();">
                                            <button class="shift-action-btn confirm" onclick="quickUpdateShift(<?php echo $shift['id']; ?>, 'confirm')" title="Confirm Shift: Officer accepts assignment">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="shift-action-btn reschedule" onclick="showRescheduleModal(<?php echo $shift['id']; ?>)" title="Reschedule: Open form to modify shift details">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                            <button class="shift-action-btn cancel" onclick="showCancelModal(<?php echo $shift['id']; ?>)" title="Cancel Shift: Mark this shift as cancelled">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php elseif ($shift['status'] === 'confirmed'): ?>
                                        <div class="shift-actions" onclick="event.stopPropagation();">
                                            <button class="shift-action-btn confirmed" onclick="quickUpdateShift(<?php echo $shift['id']; ?>, 'unconfirm')" title="Click to Unconfirm - Remove confirmation and set back to allocated status">
                                                <span class="text-confirm">
                                                    <i class="fas fa-check"></i>
                                                    Confirmed
                                                </span>
                                                <span class="text-unconfirm">
                                                    <i class="fas fa-times"></i>
                                                    Unconfirm
                                                </span>
                                            </button>
                                            <button class="shift-action-btn cancel" onclick="showCancelModal(<?php echo $shift['id']; ?>)" title="Cancel Shift: Mark this shift as cancelled">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php elseif ($shift['status'] === 'cancelled'): ?>
                                        <div class="shift-status cancelled">
                                            <i class="fas fa-ban"></i> Cancelled
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="add-shift-btn" onclick="createShiftForOfficer('<?php echo $date; ?>', <?php echo $officer['id']; ?>)">
                                <i class="fas fa-plus"></i>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Legend -->
<div style="margin: 1rem 0;">
    <h3 style="font-size: 1rem; margin: 0 0 0.5rem 0; color: var(--gray-800); font-weight: 700;"><i class="fas fa-info-circle"></i> Status Legend</h3>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div class="d-flex align-center gap-5">
            <div class="shift-item unallocated" style="margin: 0; padding: 5px 10px;">Unallocated</div>
        </div>
        <div class="d-flex align-center gap-5">
            <div class="shift-item allocated" style="margin: 0; padding: 5px 10px;">Allocated</div>
        </div>
        <div class="d-flex align-center gap-5">
            <div class="shift-item confirmed" style="margin: 0; padding: 5px 10px;">Confirmed</div>
        </div>
        <div class="d-flex align-center gap-5">
            <div class="shift-item declined" style="margin: 0; padding: 5px 10px;">Declined</div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Define JavaScript variables
const CURRENT_SITE_FILTER = <?php echo $site_filter ? $site_filter : 'null'; ?>;

// All sites data for autocomplete
const ALL_SITES = <?php echo json_encode($sites); ?>;

// All officers data for forms
const ALL_OFFICERS = <?php echo json_encode($all_officers); ?>;

// Week dates for bulk scheduling
const WEEK_START = '<?php echo $week_start ?? date('Y-m-d', strtotime('monday this week')); ?>';
const WEEK_END = '<?php echo $week_end ?? date('Y-m-d', strtotime('sunday this week')); ?>';

// Global roles variable for dynamic loading
let ALL_ROLES = [];

let draggedShiftId = null;

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

// Initialize roles when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadRoles();
});

function showCreateShiftModal() {
    // Build site options
    let siteOptions = '<option value="">Select Site</option>';
    ALL_SITES.forEach(site => {
        const selected = CURRENT_SITE_FILTER == site.id ? 'selected' : '';
        siteOptions += `<option value="${site.id}" ${selected}>${site.site_name} (${site.client_name})</option>`;
    });

    // Build officer options - get from existing PHP data
    let officerOptions = '<option value="">Unallocated</option>';
    // Sort officers by first name only
    const sortedOfficers = [...ALL_OFFICERS].sort((a, b) => {
        return a.first_name.toLowerCase().localeCompare(b.first_name.toLowerCase());
    });
    sortedOfficers.forEach(officer => {
        const displayName = `${officer.first_name} ${officer.last_name}${officer.staff_id ? ' - ' + officer.staff_id : ''}${officer.phone ? ' - ' + officer.phone : ''}`;
        officerOptions += `<option value="${officer.id}">${displayName}</option>`;
    });

    const content = `
        <form id="createShiftForm" onsubmit="createShift(event)">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Date:</label>
                <input type="date" name="shift_date" class="form-control" value="${new Date().toISOString().split('T')[0]}" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Site:</label>
                <select name="site_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${siteOptions}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Officer (Optional):</label>
                <select name="officer_id" id="create_officer_select" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" onchange="toggleCustomRateField(this)">
                    ${officerOptions}
                </select>
                <div id="create_officer_link_container"></div>
            </div>
            <div class="form-group" style="margin-bottom: 1rem; display: none;" id="customRateGroup">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                    Custom Officer Rate (£/hour):
                    <span style="font-size: 0.8em; color: #6b7280; font-weight: normal;">(Optional - overrides officer's default rate)</span>
                </label>
                <input type="number" 
                       name="custom_officer_rate" 
                       class="form-control" 
                       step="0.01" 
                       min="0" 
                       placeholder="Leave blank to use officer's default rate"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <div style="font-size: 0.75em; color: #6b7280; margin-top: 0.25rem;">
                    Only set this if you want to pay a different rate for this specific shift
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Start Time:</label>
                    <input type="time" name="start_time" class="form-control" value="08:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">End Time:</label>
                    <input type="time" name="end_time" class="form-control" value="20:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>
            <div id="overnight-indicator-main" style="display: none; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                <i class="fas fa-moon" style="color: #856404;"></i>
                <strong style="color: #856404;">Overnight Shift:</strong> 
                <span style="color: #856404;">This shift continues into the next day</span>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Role:</label>
                <select name="role_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${getRoleOptions()}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Notes:</label>
                <textarea name="notes" class="form-control" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal()" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.5rem 1rem; border: none; background: #4f46e5; color: white; border-radius: 6px; cursor: pointer;">Create Shift</button>
            </div>
        </form>
    `;
    showModal('Create New Shift', content);
    
    // Setup officer info link after modal is shown
    setTimeout(() => {
        const officerSelect = document.getElementById('create_officer_select');
        if (officerSelect) {
            setupOfficerInfoLink(officerSelect, 'create_officer_link_container');
        }
    }, 100);
}

function showBulkScheduleModal() {
    // Build site options
    let siteOptions = '<option value="">Select Site</option>';
    ALL_SITES.forEach(site => {
        const selected = CURRENT_SITE_FILTER == site.id ? 'selected' : '';
        siteOptions += `<option value="${site.id}" ${selected}>${site.site_name} (${site.client_name})</option>`;
    });

    // Build officer options
    let officerOptions = '<option value="">Leave Unallocated</option>';
    // Sort officers by first name only
    const sortedOfficers = [...ALL_OFFICERS].sort((a, b) => {
        return a.first_name.toLowerCase().localeCompare(b.first_name.toLowerCase());
    });
    sortedOfficers.forEach(officer => {
        const displayName = `${officer.first_name} ${officer.last_name}${officer.staff_id ? ' - ' + officer.staff_id : ''}${officer.phone ? ' - ' + officer.phone : ''}`;
        officerOptions += `<option value="${officer.id}">${displayName}</option>`;
    });

    const content = `
        <form id="bulkScheduleForm" onsubmit="createBulkSchedule(event)">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Start Date:</label>
                <input type="date" name="start_date" class="form-control" value="${WEEK_START}" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">End Date:</label>
                <input type="date" name="end_date" class="form-control" value="${WEEK_END}" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Site:</label>
                <select name="site_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${siteOptions}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Officer (Optional):</label>
                <select name="officer_id" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" onchange="toggleCustomRateFieldBulk(this)">
                    ${officerOptions}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem; display: none;" id="customRateGroupBulk">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                    Custom Officer Rate (£/hour):
                    <span style="font-size: 0.8em; color: #6b7280; font-weight: normal;">(Optional - overrides officer's default rate)</span>
                </label>
                <input type="number" 
                       name="custom_officer_rate" 
                       class="form-control" 
                       step="0.01" 
                       min="0" 
                       placeholder="Leave blank to use officer's default rate"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <div style="font-size: 0.75em; color: #6b7280; margin-top: 0.25rem;">
                    This rate will be applied to all shifts created in this bulk schedule
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Days of Week:</label>
                <div class="form-check-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="1" class="form-check-input" id="monday">
                        <label class="form-check-label" for="monday">Monday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="2" class="form-check-input" id="tuesday">
                        <label class="form-check-label" for="tuesday">Tuesday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="3" class="form-check-input" id="wednesday">
                        <label class="form-check-label" for="wednesday">Wednesday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="4" class="form-check-input" id="thursday">
                        <label class="form-check-label" for="thursday">Thursday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="5" class="form-check-input" id="friday">
                        <label class="form-check-label" for="friday">Friday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="6" class="form-check-input" id="saturday">
                        <label class="form-check-label" for="saturday">Saturday</label>
                    </div>
                    <div class="form-check" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="days[]" value="0" class="form-check-input" id="sunday">
                        <label class="form-check-label" for="sunday">Sunday</label>
                    </div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Start Time:</label>
                    <input type="time" name="start_time" class="form-control" value="09:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">End Time:</label>
                    <input type="time" name="end_time" class="form-control" value="17:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>
            <div id="overnight-indicator-bulk" style="display: none; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                <i class="fas fa-moon" style="color: #856404;"></i>
                <strong style="color: #856404;">Overnight Shifts:</strong> 
                <span style="color: #856404;">These shifts will continue into the next day</span>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Role:</label>
                <select name="role_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${getRoleOptions()}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Notes:</label>
                <textarea name="notes" class="form-control" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal()" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.5rem 1rem; border: none; background: #10b981; color: white; border-radius: 6px; cursor: pointer;">Create Bulk Schedule</button>
            </div>
        </form>
    `;
    showModal('Bulk Schedule Creator', content);
}

function createBulkSchedule(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const selectedDays = formData.getAll('days[]');
    
    if (selectedDays.length === 0) {
        alert('Please select at least one day of the week.');
        return;
    }
    
    // Validate required fields
    if (!formData.get('site_id')) {
        alert('Please select a site.');
        return;
    }
    
    if (!formData.get('start_time') || !formData.get('end_time')) {
        alert('Please enter both start and end times.');
        return;
    }
    
    // Convert form data to a regular object for easier processing
    const data = {
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        site_id: formData.get('site_id'),
        officer_id: formData.get('officer_id') || null,
        start_time: formData.get('start_time'),
        end_time: formData.get('end_time'),
        role_id: formData.get('role_id'),
        notes: formData.get('notes'),
        days: selectedDays
    };
    
    console.log('Bulk schedule data:', data);
    
    // Create shifts for each selected day within the date range
    const startDate = new Date(data.start_date);
    const endDate = new Date(data.end_date);
    let shiftsCreated = 0;
    let errors = [];
    
    // Function to create a single shift
    function createSingleShift(shiftDate, callback) {
        const shiftFormData = new FormData();
        shiftFormData.append('site_id', data.site_id);
        // Only append officer_id if it's not null or empty
        if (data.officer_id && data.officer_id !== 'null' && data.officer_id !== '') {
            shiftFormData.append('officer_id', data.officer_id);
        }
        shiftFormData.append('shift_date', shiftDate);
        shiftFormData.append('start_time', data.start_time);
        shiftFormData.append('end_time', data.end_time);
        shiftFormData.append('role_id', data.role_id);
        shiftFormData.append('notes', data.notes);
        
        fetch(BASE_URL + 'api/create_shift.php', {
            method: 'POST',
            body: shiftFormData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                shiftsCreated++;
            } else {
                console.error('Error creating shift for', shiftDate, ':', result);
                errors.push(shiftDate + ': ' + (result.message || 'Unknown error'));
            }
            callback();
        })
        .catch(error => {
            console.error('Network error creating shift for', shiftDate, ':', error);
            errors.push(shiftDate + ': Network error - ' + error.message);
            callback();
        });
    }
    
    // Generate all dates and create shifts
    const allPromises = [];
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        const dayOfWeek = d.getDay();
        if (selectedDays.includes(dayOfWeek.toString())) {
            const dateStr = d.toISOString().split('T')[0];
            allPromises.push(new Promise(resolve => {
                createSingleShift(dateStr, resolve);
            }));
        }
    }
    
    // Wait for all shifts to be processed
    Promise.all(allPromises).then(() => {
        document.getElementById('genericModal').style.display = 'none';
        
        if (errors.length > 0) {
            alert(`Bulk schedule completed with some errors:\n${shiftsCreated} shifts created successfully\n\nErrors:\n${errors.join('\n')}`);
        } else {
            alert(`Bulk schedule completed successfully!\n${shiftsCreated} shifts created.`);
        }
        
        // Reload the page to show new shifts
        location.reload();
    });
}

function createShiftForOfficer(date, officerId) {
    // Build site options
    let siteOptions = '<option value="">Select Site</option>';
    ALL_SITES.forEach(site => {
        const selected = CURRENT_SITE_FILTER == site.id ? 'selected' : '';
        siteOptions += `<option value="${site.id}" ${selected}>${site.site_name} (${site.client_name})</option>`;
    });

    const content = `
        <form id="createShiftForm" onsubmit="createShift(event)">
            <input type="hidden" name="shift_date" value="${date}">
            <input type="hidden" name="officer_id" value="${officerId}">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Site:</label>
                <select name="site_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${siteOptions}
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Start Time:</label>
                    <input type="time" name="start_time" class="form-control" value="08:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">End Time:</label>
                    <input type="time" name="end_time" class="form-control" value="20:00" required onchange="checkOvernightShift(this)" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>
            <div id="overnight-indicator" style="display: none; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                <i class="fas fa-moon" style="color: #856404;"></i>
                <strong style="color: #856404;">Overnight Shift:</strong> 
                <span style="color: #856404;">This shift continues into the next day (${date} → <span id="next-day-date"></span>)</span>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Role:</label>
                <select name="role_id" class="form-control" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    ${getRoleOptions()}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Notes:</label>
                <textarea name="notes" class="form-control" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal()" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.5rem 1rem; border: none; background: #4f46e5; color: white; border-radius: 6px; cursor: pointer;">Create Shift</button>
            </div>
        </form>
    `;
    showModal('Create Shift for ' + date, content);
}

function createShift(event) {
    event.preventDefault();
    console.log('createShift called');
    const formData = new FormData(event.target);
    
    // Debug: log form data
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    fetch(BASE_URL + 'api/create_shift.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showNotification('Shift created successfully', 'success');
            location.reload();
        } else {
            showNotification('Error creating shift: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error creating shift', 'error');
    });
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

function renderAttendanceVerification(shift) {
    if (!shift.checkin_image && !shift.checkout_image && !shift.checkin_timestamp && !shift.checkout_timestamp) {
        return '';
    }

    return `
        <div class="form-group" style="margin-top: 1rem;">
            <label>Attendance Verification:</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                ${renderAttendancePhotoCard('Check-in Photo', shift.checkin_image, shift.checkin_timestamp)}
                ${renderAttendancePhotoCard('Check-out Photo', shift.checkout_image, shift.checkout_timestamp)}
            </div>
        </div>
    `;
}

function editShift(shiftId) {
    console.log('Editing shift:', shiftId);
    fetch(BASE_URL + 'api/get_shift.php?id=' + shiftId, {
        method: 'GET',
        credentials: 'same-origin', // Include cookies/session
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            if (response.redirected) {
                console.log('Request was redirected to:', response.url);
                showNotification('Session expired. Please log in again.', 'error');
                window.location.reload();
                return;
            }
            return response.text(); // Get raw text first
        })
        .then(rawResponse => {
            if (!rawResponse) return; // Handle redirect case
            console.log('Raw response:', rawResponse);
            try {
                const data = JSON.parse(rawResponse);
                if (data.success) {
                const shift = data.shift;
                const content = `
                    <form id="editShiftForm" onsubmit="updateShift(event, ${shiftId})">
                        <input type="hidden" name="site_id" value="${shift.site_id}">
                        <input type="hidden" name="shift_date" value="${shift.shift_date}">
                        <div class="form-group">
                            <label>Date:</label>
                            <input type="date" class="form-control" value="${shift.shift_date}" readonly style="background-color: #f8f9fa;">
                            <small class="text-muted">Date cannot be changed when editing</small>
                        </div>
                        <div class="form-group">
                            <label>Site:</label>
                            <input type="text" class="form-control" value="${data.sites.find(site => site.id == shift.site_id)?.site_name || 'Unknown'}" readonly style="background-color: #f8f9fa;">
                            <small class="text-muted">Site cannot be changed when editing</small>
                        </div>
                        <div class="form-group">
                            <label>Officer:</label>
                            <select name="officer_id" id="rota_edit_officer_select" class="form-control" onchange="toggleCustomRateFieldEdit(this)">
                                <option value="">Unallocated</option>
                                ${data.officers.sort((a, b) => {
                                    return a.first_name.toLowerCase().localeCompare(b.first_name.toLowerCase());
                                }).map(officer => {
                                    const displayName = `${officer.first_name} ${officer.last_name}${officer.staff_id ? ' - ' + officer.staff_id : ''}${officer.phone ? ' - ' + officer.phone : ''}`;
                                    return `<option value="${officer.id}" ${officer.id == shift.officer_id ? 'selected' : ''}>${displayName}</option>`;
                                }).join('')}
                            </select>
                            <div id="rota_edit_officer_link_container"></div>
                        </div>
                        <div class="form-group" style="display: none;" id="customRateGroupEdit">
                            <label for="custom_officer_rate_edit">
                                Custom Officer Rate (£/hour):
                                <span style="font-size: 0.8em; color: #6b7280; font-weight: normal;">(Optional - overrides officer's default rate)</span>
                            </label>
                            <input type="number" 
                                   name="custom_officer_rate" 
                                   id="custom_officer_rate_edit"
                                   class="form-control" 
                                   step="0.01" 
                                   min="0" 
                                   value="${shift.custom_officer_rate || ''}"
                                   placeholder="Leave blank to use officer's default rate">
                            <small class="form-text text-muted">
                                Only set this if you want to pay a different rate for this specific shift
                            </small>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Start Time:</label>
                                <input type="time" name="start_time" class="form-control" value="${shift.start_time}" required onchange="checkOvernightShift(this)">
                            </div>
                            <div class="form-group">
                                <label>End Time:</label>
                                <input type="time" name="end_time" class="form-control" value="${shift.end_time}" required onchange="checkOvernightShift(this)">
                            </div>
                        </div>
                        <div id="overnight-indicator-edit" style="display: none; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                            <i class="fas fa-moon" style="color: #856404;"></i>
                            <strong style="color: #856404;">Overnight Shift:</strong> 
                            <span style="color: #856404;">This shift continues into the next day (${shift.shift_date} → <span id="edit-next-day-date"></span>)</span>
                        </div>
                        <div class="form-group">
                            <label>Role:</label>
                            <select name="role_id" class="form-control" required>
                                ${getRoleOptions(shift.role_id)}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="unallocated" ${shift.status == 'unallocated' ? 'selected' : ''}>Unallocated</option>
                                <option value="allocated" ${shift.status == 'allocated' ? 'selected' : ''}>Allocated</option>
                                <option value="confirmed" ${shift.status == 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                <option value="declined" ${shift.status == 'declined' ? 'selected' : ''}>Declined</option>
                                <option value="completed" ${shift.status == 'completed' ? 'selected' : ''}>Completed</option>
                            </select>
                        </div>
                        ${renderAttendanceVerification(shift)}
                        <div class="form-group">
                            <label>Notes:</label>
                            <textarea name="notes" class="form-control" rows="3">${shift.notes || ''}</textarea>
                        </div>
                        <div class="d-flex gap-10">
                            <button type="submit" class="btn btn-primary">Update Shift</button>
                            <button type="button" class="btn btn-danger" onclick="deleteShift(${shiftId})">Delete Shift</button>
                        </div>
                    </form>
                `;
                showModal('Edit Shift', content);
                
                // Check for overnight shift after modal is displayed and setup officer info link
                setTimeout(() => {
                    const startTimeInput = document.querySelector('#editShiftForm input[name="start_time"]');
                    if (startTimeInput) {
                        checkOvernightShift(startTimeInput);
                    }
                    
                    // Setup officer info link
                    const officerSelect = document.getElementById('rota_edit_officer_select');
                    if (officerSelect) {
                        setupOfficerInfoLink(officerSelect, 'rota_edit_officer_link_container');
                        
                        // Show custom rate field if officer is selected or custom rate exists
                        const customRateInput = document.getElementById('custom_officer_rate_edit');
                        const hasCustomRate = customRateInput && customRateInput.value && customRateInput.value.trim() !== '';
                        const hasOfficer = officerSelect.value && officerSelect.value !== '';
                        
                        if (hasOfficer || hasCustomRate) {
                            const customRateGroup = document.getElementById('customRateGroupEdit');
                            if (customRateGroup) {
                                customRateGroup.style.display = 'block';
                            }
                        }
                    }
                }, 100);
            } else {
                console.error('API returned error:', data);
                if (data.redirect) {
                    showNotification('Session expired. Please log in again.', 'error');
                    setTimeout(() => {
                        window.location.href = '../login.php';
                    }, 2000);
                } else {
                    showNotification(data.message || 'Error loading shift data', 'error');
                }
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Raw response that failed to parse:', rawResponse);
            showNotification('Error: Received invalid response from server', 'error');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showNotification('Error loading shift data', 'error');
    });
}

function updateShift(event, shiftId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('id', shiftId);

    // Debug: Log all form data being sent
    console.log('Form data being sent:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    fetch(BASE_URL + 'api/update_shift.php', {
        method: 'POST',
        credentials: 'same-origin', // Include cookies/session
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            showNotification('Shift updated successfully', 'success');
            location.reload();
        } else {
            if (data.redirect) {
                showNotification('Session expired. Please log in again.', 'error');
                setTimeout(() => {
                    window.location.href = '../login.php';
                }, 2000);
            } else {
                showNotification('Error updating shift: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating shift', 'error');
    });
}

function deleteShift(shiftId) {
    if (confirm('Are you sure you want to delete this shift?')) {
        fetch(BASE_URL + 'api/delete_shift.php?id=' + shiftId, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Shift deleted successfully', 'success');
                location.reload();
            } else {
                showNotification('Error deleting shift: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting shift', 'error');
        });
    }
}

// Drag and Drop functionality
function dragShift(event, shiftId) {
    draggedShiftId = shiftId;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', event.target.outerHTML);
}

function allowDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.add('drag-over');
}

function dropShift(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('drag-over');
    
    if (draggedShiftId) {
        const cell = event.currentTarget;
        const newDate = cell.dataset.date;
        const newOfficerId = cell.dataset.officerId;
        
        // First, fetch the existing shift data to preserve all fields
        fetch(BASE_URL + 'api/get_shift.php?id=' + draggedShiftId)
        .then(response => response.json())
        .then(data => {
            console.log('Fetched shift data for drag:', data);
            if (data.success) {
                const shift = data.shift;
                
                console.log('Original shift data:', shift);
                console.log('New date:', newDate);
                console.log('New officer ID:', newOfficerId);
                
                // Update shift allocation with all required fields
                const formData = new FormData();
                formData.append('id', draggedShiftId);
                formData.append('site_id', shift.site_id);
                formData.append('shift_date', newDate);
                formData.append('start_time', shift.start_time);
                formData.append('end_time', shift.end_time);
                formData.append('role_id', shift.role_id);
                formData.append('officer_id', newOfficerId);
                formData.append('status', newOfficerId ? 'allocated' : 'unallocated');
                formData.append('notes', shift.notes || '');
                
                // Debug: Log all form data being sent for drag operation
                console.log('Drag & Drop - Form data being sent:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                fetch(BASE_URL + 'api/update_shift.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Shift moved successfully', 'success');
                        
                        // Instead of full page reload, just update the shift visually
                        // Find and remove the shift from its old position
                        const oldShiftElement = document.querySelector(`[data-shift-id="${draggedShiftId}"]`);
                        if (oldShiftElement) {
                            oldShiftElement.remove();
                        }
                        
                        // Reload after showing success
                        // setTimeout(() => {
                        //     location.reload();
                        // }, 1500);
                        location.reload();
                    } else {
                        showNotification('Error moving shift: ' + data.message, 'error');
                    }
                    // Reset draggedShiftId after API call completes
                    draggedShiftId = null;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error moving shift', 'error');
                    // Reset draggedShiftId after error
                    draggedShiftId = null;
                });
            } else {
                showNotification('Error loading shift data for move', 'error');
                // Reset draggedShiftId after error
                draggedShiftId = null;
            }
        })
        .catch(error => {
            console.error('Error fetching shift data:', error);
            showNotification('Error moving shift', 'error');
            // Reset draggedShiftId after error
            draggedShiftId = null;
        });
    }
}

// Remove drag-over class when leaving
document.querySelectorAll('.day-cell').forEach(cell => {
    cell.addEventListener('dragleave', function(event) {
        if (!this.contains(event.relatedTarget)) {
            this.classList.remove('drag-over');
        }
    });
});

// Function to check if a shift spans midnight and show indicator
function checkOvernightShift(timeInput) {
    const form = timeInput.closest('form');
    if (!form) return;
    
    const startTimeInput = form.querySelector('input[name="start_time"]');
    const endTimeInput = form.querySelector('input[name="end_time"]');
    
    if (!startTimeInput || !endTimeInput) return;
    
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    
    if (!startTime || !endTime) return;
    
    // Convert times to minutes for easier comparison
    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);
    
    // Check if this is an overnight shift (end time is before start time)
    const isOvernightShift = endMinutes < startMinutes;
    
    // Find the appropriate indicator based on the form
    let indicator;
    if (form.id === 'createShiftForm') {
        // Check if this is the officer-specific form (has hidden officer_id)
        if (form.querySelector('input[name="officer_id"]')) {
            indicator = document.getElementById('overnight-indicator');
        } else {
            indicator = document.getElementById('overnight-indicator-main');
        }
    } else if (form.id === 'bulkScheduleForm') {
        indicator = document.getElementById('overnight-indicator-bulk');
    } else if (form.id && form.id.startsWith('editShiftForm')) {
        indicator = document.getElementById('overnight-indicator-edit');
    }
    
    if (indicator) {
        if (isOvernightShift) {
            indicator.style.display = 'block';
            
            // Update the next day date if this is the officer-specific form
            if (indicator.id === 'overnight-indicator') {
                const dateInput = form.querySelector('input[name="shift_date"]');
                if (dateInput) {
                    const shiftDate = new Date(dateInput.value);
                    const nextDay = new Date(shiftDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    
                    const nextDayElement = indicator.querySelector('#next-day-date');
                    if (nextDayElement) {
                        nextDayElement.textContent = nextDay.toISOString().split('T')[0];
                    }
                }
            }
            
            // Update the next day date for edit form
            if (indicator.id === 'overnight-indicator-edit') {
                const dateInput = form.querySelector('input[name="shift_date"]');
                if (dateInput) {
                    const shiftDate = new Date(dateInput.value);
                    const nextDay = new Date(shiftDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    
                    const nextDayElement = indicator.querySelector('#edit-next-day-date');
                    if (nextDayElement) {
                        nextDayElement.textContent = nextDay.toISOString().split('T')[0];
                    }
                }
            }
        } else {
            indicator.style.display = 'none';
        }
    }
}

// Helper function to convert time string to minutes
function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

// Function to toggle custom rate field visibility based on officer selection
function toggleCustomRateField(selectElement) {
    const customRateGroup = document.getElementById('customRateGroup');
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

// Function to toggle custom rate field visibility for bulk form
function toggleCustomRateFieldBulk(selectElement) {
    const customRateGroup = document.getElementById('customRateGroupBulk');
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

// Function to toggle custom rate field visibility for edit form
function toggleCustomRateFieldEdit(selectElement) {
    const customRateGroup = document.getElementById('customRateGroupEdit');
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

// Helper function to format time duration
function formatShiftDuration(startTime, endTime) {
    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);
    
    let durationMinutes;
    if (endMinutes < startMinutes) {
        // Overnight shift: add 24 hours to end time
        durationMinutes = (endMinutes + 24 * 60) - startMinutes;
    } else {
        durationMinutes = endMinutes - startMinutes;
    }
    
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    
    return `${hours}h ${minutes}m`;
}

// Quick update function for confirm/reschedule buttons
function quickUpdateShift(shiftId, action) {
    let actionText = '';
    let confirmMessage = '';
    
    switch(action) {
        case 'confirm':
            actionText = 'confirm';
            confirmMessage = 'Confirm this shift? This will mark it as confirmed and the officer accepts the assignment.';
            break;
        case 'unconfirm':
            actionText = 'unconfirm';
            confirmMessage = 'Unconfirm this shift? This will set it back to allocated status.';
            break;
        default:
            showNotification('Unknown action', 'error');
            return;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    fetch(BASE_URL + 'api/quick_update_shift.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            shift_id: shiftId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let successMessage = '';
            switch(action) {
                case 'confirm':
                    successMessage = 'Shift confirmed successfully';
                    break;
                case 'unconfirm':
                    successMessage = 'Shift unconfirmed successfully';
                    break;
            }
            showNotification(successMessage, 'success');
            
            // Update the shift element's class and buttons
            const shiftElement = document.querySelector(`[data-shift-id="${shiftId}"]`);
            if (shiftElement) {
                // Remove old status class and add new one
                shiftElement.className = shiftElement.className.replace(/\b(unallocated|allocated|confirmed|declined|completed)\b/g, '');
                shiftElement.classList.add(data.new_status);
                
                // Update action buttons based on new status
                const actionsDiv = shiftElement.querySelector('.shift-actions');
                if (actionsDiv) {
                    if (data.new_status === 'confirmed') {
                        // Replace with confirmed button
                        actionsDiv.innerHTML = `
                            <button class="shift-action-btn confirmed" onclick="quickUpdateShift(${shiftId}, 'unconfirm')" title="Click to Unconfirm - Remove confirmation and set back to allocated status">
                                <span class="text-confirm">
                                    <i class="fas fa-check"></i>
                                    Confirmed
                                </span>
                                <span class="text-unconfirm">
                                    <i class="fas fa-times"></i>
                                    Unconfirm
                                </span>
                            </button>
                            <button class="shift-action-btn cancel" onclick="showCancelModal(${shiftId})" title="Cancel Shift: Mark this shift as cancelled">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    } else if (data.new_status === 'allocated') {
                        // Replace with confirm/reschedule buttons
                        actionsDiv.innerHTML = `
                            <button class="shift-action-btn confirm" onclick="quickUpdateShift(${shiftId}, 'confirm')" title="Confirm Shift: Officer accepts assignment">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="shift-action-btn reschedule" onclick="showRescheduleModal(${shiftId})" title="Reschedule: Open form to modify shift details">
                                <i class="fas fa-clock"></i>
                            </button>
                            <button class="shift-action-btn cancel" onclick="showCancelModal(${shiftId})" title="Cancel Shift: Mark this shift as cancelled">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    }
                }
            }
        } else {
            showNotification(`Error ${actionText}ing shift: ` + data.message, 'error');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = '../login.php';
                }, 2000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(`Error ${actionText}ing shift`, 'error');
    });
}

// Show reschedule modal with form
function showRescheduleModal(shiftId) {
    console.log('Opening reschedule modal for shift:', shiftId);
    fetch(BASE_URL + 'api/get_shift.php?id=' + shiftId, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            showNotification('Session expired. Please log in again.', 'error');
            window.location.reload();
            return;
        }
        return response.text();
    })
    .then(rawResponse => {
        if (!rawResponse) return;
        try {
            const data = JSON.parse(rawResponse);
            if (data.success) {
                const shift = data.shift;
                const content = `
                    <form id="rescheduleShiftForm" onsubmit="rescheduleShift(event, ${shiftId})">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Reschedule Shift:</strong> Modify the shift details below. All changes will be logged with the reason provided.
                        </div>
                        
                        <div class="form-group">
                            <label>Date:</label>
                            <input type="date" name="shift_date" class="form-control" value="${shift.shift_date}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Site:</label>
                            <select name="site_id" class="form-control" required>
                                ${data.sites.map(site => 
                                    `<option value="${site.id}" ${site.id == shift.site_id ? 'selected' : ''}>
                                        ${site.site_name} (${site.client_name})
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Officer:</label>
                            <select name="officer_id" id="reschedule_officer_select" class="form-control">
                                <option value="">Unallocated</option>
                                ${data.officers.sort((a, b) => {
                                    return a.first_name.toLowerCase().localeCompare(b.first_name.toLowerCase());
                                }).map(officer => {
                                    const displayName = `${officer.first_name} ${officer.last_name}${officer.staff_id ? ' - ' + officer.staff_id : ''}${officer.phone ? ' - ' + officer.phone : ''}`;
                                    return `<option value="${officer.id}" ${officer.id == shift.officer_id ? 'selected' : ''}>${displayName}</option>`;
                                }).join('')}
                            </select>
                            <div id="reschedule_officer_link_container"></div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Start Time:</label>
                                <input type="time" name="start_time" class="form-control" value="${shift.start_time}" required onchange="checkOvernightShift(this)">
                            </div>
                            <div class="form-group">
                                <label>End Time:</label>
                                <input type="time" name="end_time" class="form-control" value="${shift.end_time}" required onchange="checkOvernightShift(this)">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Role:</label>
                            <select name="role_id" class="form-control" required>
                                ${getRoleOptions(shift.role_id)}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><span style="color: red;">*</span> Reschedule Reason:</label>
                            <textarea name="reschedule_reason" class="form-control" rows="3" required placeholder="Please provide a reason for rescheduling this shift (e.g., client request, officer unavailability, timing conflict, etc.)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Additional Notes:</label>
                            <textarea name="notes" class="form-control" rows="2">${shift.notes || ''}</textarea>
                        </div>
                        
                        <div class="d-flex gap-10">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-clock"></i> Reschedule Shift
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        </div>
                    </form>
                `;
                showModal('Reschedule Shift', content);
                
                // Check for overnight shift after modal is displayed and setup officer info link
                setTimeout(() => {
                    const startTimeInput = document.querySelector('#rescheduleShiftForm input[name="start_time"]');
                    if (startTimeInput) {
                        checkOvernightShift(startTimeInput);
                    }
                    
                    // Setup officer info link
                    const officerSelect = document.getElementById('reschedule_officer_select');
                    if (officerSelect) {
                        setupOfficerInfoLink(officerSelect, 'reschedule_officer_link_container');
                    }
                }, 100);
            } else {
                showNotification(data.message || 'Error loading shift data', 'error');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            showNotification('Error: Received invalid response from server', 'error');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showNotification('Error loading shift data', 'error');
    });
}

// Handle reschedule form submission
function rescheduleShift(event, shiftId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('id', shiftId);

    // Validate reschedule reason
    const rescheduleReason = formData.get('reschedule_reason');
    if (!rescheduleReason || rescheduleReason.trim().length < 10) {
        showNotification('Please provide a detailed reschedule reason (minimum 10 characters)', 'error');
        return;
    }

    fetch(BASE_URL + 'api/reschedule_shift.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Shift rescheduled successfully', 'success');
            closeModal();
            location.reload(); // Reload to show updated shift
        } else {
            showNotification('Error rescheduling shift: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error rescheduling shift', 'error');
    });
}

// Show cancel modal with form
function showCancelModal(shiftId) {
    console.log('Opening cancel modal for shift:', shiftId);
    fetch(BASE_URL + 'api/get_shift.php?id=' + shiftId, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = '../login.php';
            return;
        }
        return response.text();
    })
    .then(text => {
        console.log('Cancel modal - API Response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                const shift = data.shift;
                const content = `
                    <form id="cancelShiftForm" onsubmit="cancelShift(event, ${shiftId})">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Cancel Shift:</strong> This will mark the shift as cancelled but it will remain visible in the calendar. This action cannot be undone.
                        </div>
                        
                        <div class="shift-info mb-3">
                            <h5>Shift Details:</h5>
                            <p><strong>Site:</strong> ${shift.site_name}</p>
                            <p><strong>Date:</strong> ${shift.shift_date}</p>
                            <p><strong>Time:</strong> ${shift.start_time} - ${shift.end_time}</p>
                            <p><strong>Officer:</strong> ${shift.officer_name || 'Unallocated'}</p>
                            <p><strong>Role:</strong> ${shift.role_name || shift.role || 'Unknown Role'}</p>
                        </div>
                        
                        <div class="form-group">
                            <label><span style="color: red;">*</span> Cancellation Reason:</label>
                            <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Please provide a reason for cancelling this shift (e.g., client request, officer unavailability, emergency, etc.)"></textarea>
                        </div>
                        
                        <div class="d-flex gap-10">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban"></i> Cancel Shift
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                        </div>
                    </form>
                `;
                showModal('Cancel Shift', content);
            } else {
                showNotification(data.message || 'Error loading shift data', 'error');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            showNotification('Error: Received invalid response from server', 'error');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showNotification('Error loading shift data', 'error');
    });
}

// Handle cancel form submission
function cancelShift(event, shiftId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('id', shiftId);

    // Validate cancellation reason
    const cancellationReason = formData.get('cancellation_reason');
    if (!cancellationReason || cancellationReason.trim().length < 10) {
        showNotification('Please provide a detailed cancellation reason (minimum 10 characters)', 'error');
        return;
    }

    fetch(BASE_URL + 'api/cancel_shift.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = '../login.php';
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('Shift cancelled successfully', 'success');
            closeModal();
            // Refresh page to show updated shift status
            location.reload();
        } else {
            showNotification(data.message || 'Error cancelling shift', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error cancelling shift', 'error');
    });
}

// Site filter functionality
function filterBySite(siteId) {
    const urlParams = new URLSearchParams(window.location.search);
    if (siteId) {
        urlParams.set('site_filter', siteId);
    } else {
        urlParams.delete('site_filter');
    }
    window.location.search = urlParams.toString();
}

// Clear site filter
function clearSiteFilter() {
    filterBySite('');
}

// Site search autocomplete functionality
let searchTimeout;
const searchInput = document.getElementById('siteSearchInput');
const searchResults = document.getElementById('siteSearchResults');
const clearButton = document.getElementById('clearSiteFilter');

if (searchInput) {
    // Handle input events
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Show/hide clear button
        if (query.length > 0 && !CURRENT_SITE_FILTER) {
            clearButton.style.display = 'block';
        } else if (!CURRENT_SITE_FILTER) {
            clearButton.style.display = 'none';
        }
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            if (query.length >= 2) {
                showSearchResults(query);
            } else {
                hideSearchResults();
            }
        }, 300);
    });
    
    // Handle focus events
    searchInput.addEventListener('focus', function(e) {
        const query = e.target.value.trim();
        if (query.length >= 2) {
            showSearchResults(query);
        }
    });
    
    // Handle blur events (with delay to allow for clicks)
    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            hideSearchResults();
        }, 200);
    });
    
    // Handle key navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = searchResults.querySelectorAll('.search-result-item');
        let selectedIndex = Array.from(items).findIndex(item => item.classList.contains('selected'));
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
                selectSearchItem(items, selectedIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
                selectSearchItem(items, selectedIndex);
                break;
            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                }
                break;
            case 'Escape':
                hideSearchResults();
                searchInput.blur();
                break;
        }
    });
}

function showSearchResults(query) {
    // If we have too many sites (> 500), use API search for better performance
    if (ALL_SITES && ALL_SITES.length > 500) {
        showSearchResultsFromAPI(query);
        return;
    }
    
    if (!ALL_SITES || ALL_SITES.length === 0) {
        hideSearchResults();
        return;
    }
    
    const queryLower = query.toLowerCase();
    const filteredSites = ALL_SITES.filter(site => {
        const siteName = site.site_name.toLowerCase();
        const clientName = site.client_name.toLowerCase();
        return siteName.includes(queryLower) || clientName.includes(queryLower);
    });
    
    // Sort results with better relevance
    filteredSites.sort((a, b) => {
        const aScore = getSearchScore(a, queryLower);
        const bScore = getSearchScore(b, queryLower);
        return bScore - aScore; // Higher scores first
    });
    
    displaySearchResults(filteredSites, query);
    
    // Ensure dropdown is positioned correctly
    positionSearchResults();
}

function showSearchResultsFromAPI(query) {
    // Show loading indicator
    searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    searchResults.style.display = 'block';
    positionSearchResults();
    
    fetch(BASE_URL + 'api/search_sites.php?q=' + encodeURIComponent(query) + '&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.sites) {
                displaySearchResults(data.sites, query);
                positionSearchResults();
            } else {
                searchResults.innerHTML = '<div class="search-error">Error loading search results</div>';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="search-error">Error loading search results</div>';
        });
}

function displaySearchResults(filteredSites, query) {
    if (filteredSites.length === 0) {
        searchResults.innerHTML = '<div class="search-no-results">No sites found</div>';
        searchResults.style.display = 'block';
        return;
    }
    
    // Limit results to prevent UI issues
    const limitedResults = filteredSites.slice(0, 20);
    
    searchResults.innerHTML = limitedResults.map(site => `
        <div class="search-result-item" data-site-id="${site.id}" onclick="selectSite(${site.id}, '${escapeHtml(site.site_name)} (${escapeHtml(site.client_name)})')">
            <div>${highlightMatch(site.site_name, query)}</div>
            <div>${highlightMatch(site.client_name, query)}</div>
        </div>
    `).join('');
    
    searchResults.style.display = 'block';
}

function positionSearchResults() {
    if (!searchInput || !searchResults) return;
    
    const inputRect = searchInput.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const dropdownHeight = 300; // max-height from CSS
    
    // Check if dropdown is clipped by checking if it's visible when it should be
    const testElement = searchResults.querySelector('.search-result-item, .search-no-results, .search-loading, .search-error');
    if (testElement) {
        const testRect = testElement.getBoundingClientRect();
        const isClipped = testRect.height === 0 || testRect.width === 0;
        
        if (isClipped) {
            // If clipped, use fixed positioning relative to viewport
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            searchResults.style.position = 'fixed';
            searchResults.style.top = (inputRect.bottom + 1) + 'px';
            searchResults.style.left = inputRect.left + 'px';
            searchResults.style.width = inputRect.width + 'px';
            searchResults.style.right = 'auto';
        } else {
            // Use default absolute positioning
            searchResults.style.position = 'absolute';
            searchResults.style.width = 'auto';
        }
    }
    
    // Check if there's enough space below the input
    const spaceBelow = viewportHeight - inputRect.bottom;
    
    if (spaceBelow < dropdownHeight && inputRect.top > dropdownHeight) {
        // Position above the input if there's more space there
        if (searchResults.style.position === 'fixed') {
            searchResults.style.top = (inputRect.top - 1) + 'px';
            searchResults.style.transform = 'translateY(-100%)';
        } else {
            searchResults.style.top = 'auto';
            searchResults.style.bottom = '100%';
            searchResults.style.transform = 'none';
        }
        searchResults.style.borderRadius = '6px 6px 0 0';
        searchResults.style.borderTop = '1px solid #d1d5db';
        searchResults.style.borderBottom = 'none';
        searchResults.style.marginTop = '0';
        searchResults.style.marginBottom = '1px';
    } else {
        // Default position below the input
        if (searchResults.style.position === 'fixed') {
            searchResults.style.top = (inputRect.bottom + 1) + 'px';
            searchResults.style.transform = 'none';
        } else {
            searchResults.style.top = '100%';
            searchResults.style.bottom = 'auto';
            searchResults.style.transform = 'none';
        }
        searchResults.style.borderRadius = '0 0 6px 6px';
        searchResults.style.borderTop = 'none';
        searchResults.style.borderBottom = '1px solid #d1d5db';
        searchResults.style.marginTop = '1px';
        searchResults.style.marginBottom = '0';
    }
}

function hideSearchResults() {
    searchResults.style.display = 'none';
    // Reset positioning styles
    searchResults.style.position = 'absolute';
    searchResults.style.top = '100%';
    searchResults.style.left = '0';
    searchResults.style.right = '0';
    searchResults.style.width = 'auto';
    searchResults.style.transform = 'none';
}

function getSearchScore(site, queryLower) {
    const siteName = site.site_name.toLowerCase();
    const clientName = site.client_name.toLowerCase();
    let score = 0;
    
    // Exact matches get highest priority
    if (siteName === queryLower) score += 1000;
    if (clientName === queryLower) score += 900;
    
    // Starting with query gets high priority
    if (siteName.startsWith(queryLower)) score += 500;
    if (clientName.startsWith(queryLower)) score += 400;
    
    // Word boundary matches (after space, dash, etc.)
    const wordBoundaryRegex = new RegExp('(^|\\s|-)' + queryLower.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    if (wordBoundaryRegex.test(siteName)) score += 300;
    if (wordBoundaryRegex.test(clientName)) score += 200;
    
    // Contains query anywhere
    if (siteName.includes(queryLower)) score += 100;
    if (clientName.includes(queryLower)) score += 50;
    
    // Shorter names get slight preference (more specific)
    score += Math.max(0, 100 - siteName.length);
    score += Math.max(0, 50 - clientName.length);
    
    return score;
}

function selectSearchItem(items, index) {
    items.forEach(item => item.classList.remove('selected'));
    if (items[index]) {
        items[index].classList.add('selected');
    }
}

function selectSite(siteId, siteName) {
    searchInput.value = siteName;
    hideSearchResults();
    clearButton.style.display = 'block';
    
    // Apply filter
    setTimeout(() => {
        filterBySite(siteId);
    }, 100);
}

function highlightMatch(text, query) {
    if (!query || query.length < 2) return escapeHtml(text);
    
    // Escape special regex characters in the query
    const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    
    // Create regex for case-insensitive global matching
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    
    // Apply highlighting with a more visible background
    return escapeHtml(text).replace(regex, '<strong style="background-color: #fbbf24; color: #92400e; padding: 1px 2px; border-radius: 2px;">$1</strong>');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Initialize clear button visibility
document.addEventListener('DOMContentLoaded', function() {
    if (CURRENT_SITE_FILTER && clearButton) {
        clearButton.style.display = 'block';
    }
    
    // Reposition dropdown on window resize
    window.addEventListener('resize', function() {
        if (searchResults && searchResults.style.display === 'block') {
            positionSearchResults();
        }
    });
    
    // Reposition dropdown on scroll (for fixed positioning)
    window.addEventListener('scroll', function() {
        if (searchResults && searchResults.style.display === 'block' && searchResults.style.position === 'fixed') {
            positionSearchResults();
        }
    });
    
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

// Modal functions with body scroll prevention
function showModal(title, content, modalId = 'genericModal') {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const modalTitle = modal.querySelector('#modalTitle');
    const modalBody = modal.querySelector('#modalBody');
    
    if (modalTitle) modalTitle.textContent = title;
    if (modalBody) modalBody.innerHTML = content;
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Show modal with animation
    modal.style.display = 'block';
    modal.style.opacity = '0';
    requestAnimationFrame(() => {
        modal.style.opacity = '1';
    });
}

function closeModal(modalId = 'genericModal') {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Hide modal with animation
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.opacity = '1';
    }, 200);
}
</script>

<!-- Modal HTML -->
<div id="genericModal" class="modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); overflow: hidden;">
    <div class="modal-dialog" style="position: relative; margin: 2vh auto; padding: 0; width: 90%; max-width: 600px; max-height: 96vh; display: flex; flex-direction: column;">
        <div style="background-color: #fefefe; border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 600;">Modal Title</h3>
                <button type="button" class="close" onclick="closeModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content" id="modalBody" style="padding: 1.5rem; overflow-y: auto; flex-grow: 1; max-height: calc(96vh - 80px);">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
