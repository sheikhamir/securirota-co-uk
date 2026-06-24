<?php
/**
 * SecuriRota System - Main Header File
 * This file contains the common HTML head, navigation, and layout structure
 * for all pages in the SecuriRota system.
 */

// =============================================================================
// CORE SYSTEM INITIALIZATION
// =============================================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include core system files
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Ensure user is authenticated
requireLogin();

// =============================================================================
// PAGE CONFIGURATION & DEFAULTS
// =============================================================================

// Set default page title if not provided
if (!isset($page_title)) {
    $page_title = 'SecuriRota Management System';
}

// Set page-specific configurations
$current_page = getCurrentPageName();
$user_role = getCurrentUserRole();
$username = getCurrentUsername();

// =============================================================================
// NAVIGATION MENU STRUCTURE
// =============================================================================

// Super Admin specific menu items - using comprehensive root directory implementation
$super_admin_menu = [];
if ($user_role === 'super_admin') {
    // Special menu for root user
    if (isRootUser()) {
        $super_admin_menu = [
            'root_dashboard' => [
                'title' => 'ROOT DASHBOARD',
                'url' => 'root/root_dashboard.php',
                'icon' => 'fas fa-crown',
                'roles' => ['super_admin']
            ],
            'super_admin_root' => [
                'title' => 'Control Panel',
                'url' => 'root/index.php',
                'icon' => 'fas fa-tachometer-alt',
                'roles' => ['super_admin']
            ],
            'companies_onboarding' => [
                'title' => 'Company Onboarding',
                'url' => 'root/onboarding.php',
                'icon' => 'fas fa-building',
                'roles' => ['super_admin']
            ],
            'security_center' => [
                'title' => 'Security Center',
                'url' => 'root/security.php',
                'icon' => 'fas fa-shield-alt',
                'roles' => ['super_admin']
            ],
            'migration_tools' => [
                'title' => 'Migration Tools',
                'url' => 'root/migration.php',
                'icon' => 'fas fa-database',
                'roles' => ['super_admin']
            ],
            'branding_manager' => [
                'title' => 'Branding',
                'url' => 'root/branding.php',
                'icon' => 'fas fa-paint-brush',
                'roles' => ['super_admin']
            ],
            'system_settings' => [
                'title' => 'System Settings',
                'url' => 'root/settings.php',
                'icon' => 'fas fa-cogs',
                'roles' => ['super_admin']
            ]
        ];
    } else {
        // Regular super admin menu
        $super_admin_menu = [
            'super_admin_root' => [
                'title' => 'Control Panel',
                'url' => 'root/index.php',
                'icon' => 'fas fa-crown',
                'roles' => ['super_admin']
            ],
            'super_admin_dashboard' => [
                'title' => 'Dashboard',
                'url' => 'root/dashboard.php',
                'icon' => 'fas fa-chart-line',
                'roles' => ['super_admin']
            ],
            'companies_onboarding' => [
                'title' => 'Company Onboarding',
                'url' => 'root/onboarding.php',
                'icon' => 'fas fa-building',
                'roles' => ['super_admin']
            ],
            'security_center' => [
                'title' => 'Security Center',
                'url' => 'root/security.php',
                'icon' => 'fas fa-shield-alt',
                'roles' => ['super_admin']
            ],
            'migration_tools' => [
                'title' => 'Migration Tools',
                'url' => 'root/migration.php',
                'icon' => 'fas fa-database',
                'roles' => ['super_admin']
            ],
            'branding_manager' => [
                'title' => 'Branding',
                'url' => 'root/branding.php',
                'icon' => 'fas fa-paint-brush',
                'roles' => ['super_admin']
            ],
            'system_settings' => [
                'title' => 'System Settings',
                'url' => 'root/settings.php',
                'icon' => 'fas fa-cogs',
                'roles' => ['super_admin']
            ]
        ];
    }
}

// Regular navigation menu for company users
$navigation_menu = [
    'dashboard' => [
        'title' => 'Dashboard',
        'url' => 'dashboard.php',
        'icon' => 'fas fa-tachometer-alt',
        'base_path' => true,
        'roles' => ['admin', 'manager']
    ],
    'officers' => [
        'title' => 'Staff Records',
        'url' => 'pages/officers.php',
        'icon' => 'fas fa-users',
        'roles' => ['admin', 'manager']
    ],
    'subcontractors' => [
        'title' => 'Subcontractors',
        'url' => 'pages/subcontractors.php',
        'icon' => 'fas fa-building-user',
        'roles' => ['admin', 'manager']
    ],
    'sites' => [
        'title' => 'Site Records',
        'url' => 'pages/sites.php',
        'icon' => 'fas fa-building',
        'roles' => ['admin', 'manager']
    ],
    'rota' => [
        'title' => 'Planner (Rota)',
        'url' => 'pages/rota.php',
        'icon' => 'fas fa-calendar-alt',
        'roles' => ['admin', 'manager']
    ],
    'site_rotas' => [
        'title' => 'Site Rotas',
        'url' => 'pages/site_rotas.php',
        'icon' => 'fas fa-calendar-week',
        'roles' => ['admin', 'manager']
    ],
    'screening' => [
        'title' => 'Screening/Vetting',
        'url' => 'pages/screening.php',
        'icon' => 'fas fa-user-check',
        'roles' => ['admin', 'manager']
    ],
    'deployment' => [
        'title' => 'Deployment & Operations',
        'url' => 'pages/deployment.php',
        'icon' => 'fas fa-clipboard-list',
        'roles' => ['admin', 'manager']
    ],
    'reports' => [
        'title' => 'Reports',
        'url' => 'pages/reports.php',
        'icon' => 'fas fa-chart-bar',
        'roles' => ['admin', 'manager']
    ],
    'invoices' => [
        'title' => 'Invoices',
        'url' => 'pages/invoices.php',
        'icon' => 'fas fa-file-invoice-dollar',
        'roles' => ['admin', 'manager']
    ],
    'clients' => [
        'title' => 'Clients',
        'url' => 'pages/clients.php',
        'icon' => 'fas fa-handshake',
        'roles' => ['admin', 'manager']
    ]
];

// Admin-only menu items
$admin_menu = [
    'users' => [
        'title' => 'User Management',
        'url' => 'pages/users.php',
        'icon' => 'fas fa-users-cog',
        'roles' => ['admin']
    ],
    'roles' => [
        'title' => 'Shift Roles',
        'url' => 'pages/roles.php',
        'icon' => 'fas fa-user-tag',
        'roles' => ['admin']
    ],
    'email_templates' => [
        'title' => 'Email Templates',
        'url' => 'pages/email_templates.php',
        'icon' => 'fas fa-envelope-open-text',
        'roles' => ['admin']
    ],
    'company_branding' => [
        'title' => 'Company Branding',
        'url' => 'pages/company_branding.php',
        'icon' => 'fas fa-palette',
        'roles' => ['admin']
    ],
    'settings' => [
        'title' => 'Settings',
        'url' => 'pages/settings.php',
        'icon' => 'fas fa-cog',
        'roles' => ['admin']
    ]
];

// General menu items (available to all roles)
$general_menu = [
    'support' => [
        'title' => 'Support Guides',
        'url' => 'pages/support.php',
        'icon' => 'fas fa-question-circle',
        'roles' => ['admin', 'manager', 'officer']
    ],
    'activity_log' => [
        'title' => 'Activity Log',
        'url' => 'pages/activity_log.php',
        'icon' => 'fas fa-history',
        'roles' => ['admin', 'manager', 'super_admin']
    ]
];

// Officer-specific menu items
if ($user_role === 'officer') {
    // Officers get a completely different, restricted menu
    $navigation_menu = [
        'officer_portal' => [
            'title' => 'My Dashboard',
            'url' => 'pages/officer_portal.php',
            'icon' => 'fas fa-tachometer-alt',
            'roles' => ['officer']
        ],
        'profile' => [
            'title' => 'My Profile',
            'url' => 'pages/profile.php',
            'icon' => 'fas fa-user-circle',
            'roles' => ['officer']
        ],
        'support' => [
            'title' => 'Support Guides',
            'url' => 'pages/support.php',
            'icon' => 'fas fa-question-circle',
            'roles' => ['officer']
        ]
    ];
    // Clear admin and general menus for officers
    $admin_menu = [];
    $general_menu = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- =================================================================== -->
    <!-- META INFORMATION -->
    <!-- =================================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SecuriRota - Professional Security Staff Management System">
    <meta name="author" content="SecuriRota Development Team">
    <title><?php echo strip_tags($page_title); ?></title>
    
    <!-- =================================================================== -->
    <!-- FAVICON -->
    <!-- =================================================================== -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('favicon.ico'); ?>">
    
    <!-- =================================================================== -->
    <!-- EXTERNAL CSS LIBRARIES -->
    <!-- =================================================================== -->
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
    
    <!-- =================================================================== -->
    <!-- EXTERNAL JAVASCRIPT LIBRARIES -->
    <!-- =================================================================== -->
    
    <!-- jQuery (loaded early for DataTables and other dependencies) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Chart.js for dashboards and reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- =================================================================== -->
    <!-- CUSTOM CSS -->
    <!-- =================================================================== -->
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo assetUrl('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetUrl('assets/css/enhanced-ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetUrl('assets/css/datatables-custom.css'); ?>">
    
    <!-- Company Branding CSS -->
    <?php if (isset($_SESSION['company_id']) && $_SESSION['company_id']): ?>
    <link rel="stylesheet" href="<?php echo assetUrl('assets/css/company-branding.php?company_id=' . $_SESSION['company_id']); ?>">
    <?php endif; ?>
    
    <!-- Custom JavaScript -->
    <script>
        // User role information for JavaScript
        window.userRole = '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>';
        window.isAdmin = <?php echo (hasRole('admin')) ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo assetUrl('assets/js/main.js'); ?>"></script>    <!-- =================================================================== -->
    <!-- PAGE SPECIFIC STYLES -->
    <!-- =================================================================== -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo assetUrl($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- =================================================================== -->
    <!-- INLINE STYLES FOR DYNAMIC CONTENT -->
    <!-- =================================================================== -->
    <?php if (isset($inline_styles)): ?>
        <style><?php echo $inline_styles; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo $current_page; ?>-page">

    <!-- =================================================================== -->
    <!-- SIDEBAR NAVIGATION -->
    <!-- =================================================================== -->
    <div class="sidebar" id="sidebar">
        <!-- Logo Section -->
        <div class="logo">
            <h2>
                <i class="fas fa-shield-alt"></i> 
                <span class="logo-text">SecuriRota</span>
            </h2>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <?php 
                // Function to render navigation items
                function renderNavItem($key, $item, $current_page, $user_role) {
                    // Check if user has permission for this menu item
                    if (!in_array($user_role, $item['roles'])) {
                        return;
                    }
                    
                    $url = isset($item['base_path']) && $item['base_path'] 
                        ? baseUrl($item['url']) 
                        : baseUrl($item['url']);
                    
                    $is_active = isCurrentPage(basename($item['url']));
                    $active_class = $is_active ? 'active' : '';
                    
                    echo '<li class="nav-item">';
                    echo '<a href="' . $url . '" class="nav-link ' . $active_class . '" title="' . safe_html($item['title']) . '">';
                    echo '<i class="' . $item['icon'] . '"></i>';
                    echo '<span class="nav-text">' . safe_html($item['title']) . '</span>';
                    echo '</a>';
                    echo '</li>';
                }
                
                // Render navigation based on user role
                if ($user_role === 'super_admin') {
                    // Render super admin navigation
                    foreach ($super_admin_menu as $key => $item) {
                        renderNavItem($key, $item, $current_page, $user_role);
                    }
                } else {
                    // Render regular company navigation
                    foreach ($navigation_menu as $key => $item) {
                        renderNavItem($key, $item, $current_page, $user_role);
                    }
                    
                    // Add separator for admin section
                    if (isAdmin()): ?>
                        <li class="nav-separator">
                            <hr class="sidebar-divider">
                            <span class="nav-section-title">Administration</span>
                        </li>
                        <?php foreach ($admin_menu as $key => $item) {
                            renderNavItem($key, $item, $current_page, $user_role);
                        }
                    endif;
                    
                    // Add separator for general section
                    ?>
                    <li class="nav-separator">
                        <hr class="sidebar-divider">
                        <span class="nav-section-title">General</span>
                    </li>
                    <?php 
                    foreach ($general_menu as $key => $item) {
                        renderNavItem($key, $item, $current_page, $user_role);
                    }
                }
                ?>
                
                <!-- Logout -->
                <li class="nav-item logout-item">
                    <a href="<?php echo baseUrl('logout.php'); ?>" class="nav-link" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- =================================================================== -->
    <!-- MAIN CONTENT AREA -->
    <!-- =================================================================== -->
    <div class="main-content" id="main-content">
        
        <!-- Top Header Bar -->
        <div class="header">
            <!-- Sidebar Toggle Button -->
            <button class="btn btn-outline-secondary sidebar-toggle" id="sidebar-toggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Page Title -->
            <h1 class="page-title">
                <?php echo safe_html($page_title); ?>
            </h1>
            
            <!-- User Information & Actions -->
            <div class="user-info">
                <div class="user-details">
                    <span class="welcome-text">Welcome, <strong><?php echo safe_html($username); ?></strong></span>
                    <span class="badge bg-info text-dark ms-2 user-role-badge">
                        <?php echo safe_html(ucfirst($user_role)); ?>
                    </span>
                </div>
                
                <div class="user-actions">
                    <!-- Profile Link -->
                    <a href="<?php echo baseUrl('pages/profile.php'); ?>" 
                       class="btn btn-outline-info btn-sm" title="View Profile">
                        <i class="fas fa-user-circle me-1"></i>
                        <span class="d-none d-md-inline">Profile</span>
                    </a>
                    
                    <!-- Logout Link -->
                    <a href="<?php echo baseUrl('logout.php'); ?>" 
                       class="btn btn-outline-danger btn-sm ms-2" title="Logout">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        <span class="d-none d-md-inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content" id="page-content">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo safe_html($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo safe_html($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo safe_html($_SESSION['warning']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo safe_html($_SESSION['info']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>

            <!-- Page Content Starts Here -->
            <!-- Individual pages will continue from this point -->
