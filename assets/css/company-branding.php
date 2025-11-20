<?php
/**
 * Dynamic CSS Generator for Company Branding
 * Generates custom CSS based on company branding settings
 */

/*
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to CSS
header('Content-Type: text/css');

// Include config and database - go up two directories from assets/css/
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Get company ID from session or parameter
$company_id = null;
if (isset($_SESSION['company_id']) && $_SESSION['company_id']) {
    $company_id = $_SESSION['company_id'];
} elseif (isset($_GET['company_id'])) {
    $company_id = (int)$_GET['company_id'];
}

// Default colors if no company found
$primary_color = '#0066cc';
$secondary_color = '#6c757d';
$custom_css = '';

if ($company_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT brand_primary_color, brand_secondary_color, custom_css 
            FROM companies 
            WHERE id = ?
        ");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company) {
            $primary_color = $company['brand_primary_color'] ?? $primary_color;
            $secondary_color = $company['brand_secondary_color'] ?? $secondary_color;
            $custom_css = $company['custom_css'] ?? '';
        }
    } catch (Exception $e) {
        // Use defaults if database error
    }
}

// Generate lighter/darker variations
function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    
    $color_parts = str_split($hex, 2);
    $return = '#';
    
    foreach ($color_parts as $color) {
        $color   = hexdec($color);
        $color   = max(0, min(255, $color + $steps));
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }
    
    return $return;
}

$primary_light = adjustBrightness($primary_color, 40);
$primary_dark = adjustBrightness($primary_color, -40);
$secondary_light = adjustBrightness($secondary_color, 40);
$secondary_dark = adjustBrightness($secondary_color, -40);

?>
/* Company Branding CSS - Auto-generated */
/*
:root {
    --company-primary: <?= $primary_color ?>;
    --company-primary-light: <?= $primary_light ?>;
    --company-primary-dark: <?= $primary_dark ?>;
    --company-secondary: <?= $secondary_color ?>;
    --company-secondary-light: <?= $secondary_light ?>;
    --company-secondary-dark: <?= $secondary_dark ?>;
}

/* Primary Brand Color Applications *//*
.btn-primary {
    background-color: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

.btn-primary:hover {
    background-color: var(--company-primary-dark) !important;
    border-color: var(--company-primary-dark) !important;
}

.btn-outline-primary {
    color: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

.btn-outline-primary:hover {
    background-color: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

/* Navigation and Sidebar *//*
.sidebar {
    background: linear-gradient(135deg, var(--company-primary) 0%, var(--company-primary-dark) 100%) !important;
}

.nav-link.active {
    background-color: var(--company-primary-light) !important;
}

.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

/* Header *//*
.header {
    border-bottom: 2px solid var(--company-primary) !important;
}

.page-title {
    color: var(--company-primary) !important;
}

/* Cards and Badges *//*
.badge.bg-primary {
    background-color: var(--company-primary) !important;
}

.card-header {
    background-color: var(--company-primary-light) !important;
    color: var(--company-primary-dark) !important;
}

/* Forms *//*
.form-control:focus {
    border-color: var(--company-primary) !important;
    box-shadow: 0 0 0 0.2rem rgba(<?= 
        hexdec(substr($primary_color, 1, 2)) . ', ' . 
        hexdec(substr($primary_color, 3, 2)) . ', ' . 
        hexdec(substr($primary_color, 5, 2)) 
    ?>, 0.25) !important;
}

.form-check-input:checked {
    background-color: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

/* Tables *//*
.table th {
    border-top: 2px solid var(--company-primary) !important;
}

.table-hover tbody tr:hover {
    background-color: var(--company-primary-light) !important;
    background-color: rgba(<?= 
        hexdec(substr($primary_color, 1, 2)) . ', ' . 
        hexdec(substr($primary_color, 3, 2)) . ', ' . 
        hexdec(substr($primary_color, 5, 2)) 
    ?>, 0.05) !important;
}

/* Links *//*
a {
    color: var(--company-primary) !important;
}

a:hover {
    color: var(--company-primary-dark) !important;
}

/* Progress bars and loading indicators *//*
.progress-bar {
    background-color: var(--company-primary) !important;
}

/* Pagination *//*
.page-link {
    color: var(--company-primary) !important;
}

.page-item.active .page-link {
    background-color: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

/* Alerts *//*
.alert-primary {
    color: var(--company-primary-dark) !important;
    background-color: var(--company-primary-light) !important;
    border-color: var(--company-primary) !important;
}

/* Secondary Color Applications *//*
.btn-secondary {
    background-color: var(--company-secondary) !important;
    border-color: var(--company-secondary) !important;
}

.btn-outline-secondary {
    color: var(--company-secondary) !important;
    border-color: var(--company-secondary) !important;
}

.text-muted {
    color: var(--company-secondary) !important;
}

/* Charts and DataTables *//*
.dataTables_wrapper .dataTables_info {
    color: var(--company-secondary) !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--company-primary) !important;
    border-color: var(--company-primary) !important;
}

/* Dashboard Cards *//*
.card {
    border-left: 3px solid var(--company-primary) !important;
}

.stat-card {
    border-left: 4px solid var(--company-primary) !important;
}

/* Custom Company CSS *//*
<?= $custom_css ?>

/* Responsive adjustments *//*
@media (max-width: 768px) {
    .sidebar {
        background: var(--company-primary) !important;
    }
}

/* Print styles *//*
@media print {
    .sidebar, .header {
        background: white !important;
        color: black !important;
    }
}

*/