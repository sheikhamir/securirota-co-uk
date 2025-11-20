<?php
/**
 * Root User Navigation Component
 * Special navigation menu for root superuser only
 */

// Ensure this is only accessible by root user
if (!isRootUser()) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebar" class="bg-dark">
    <div class="sidebar-header">
        <h4 class="text-white">
            <i class="fas fa-crown"></i> ROOT CONTROL
        </h4>
        <p class="text-light small">Ultimate System Administrator</p>
    </div>

    <ul class="list-unstyled components">
        <!-- Dashboard -->
        <li class="<?php echo ($current_page === 'root_dashboard.php' || $current_page === 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php" class="text-white">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text">Root Dashboard</span>
            </a>
        </li>

        <!-- Company Management -->
        <li class="nav-separator">
            <hr class="sidebar-divider">
            <span class="nav-section-title">Company Management</span>
        </li>
        
        <li class="<?php echo ($current_page === 'companies.php') ? 'active' : ''; ?>">
            <a href="companies.php" class="text-white">
                <i class="fas fa-building"></i>
                <span class="nav-text">Manage Companies</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'subscription_tiers.php') ? 'active' : ''; ?>">
            <a href="subscription_tiers.php" class="text-white">
                <i class="fas fa-layer-group"></i>
                <span class="nav-text">Subscription Tiers</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'billing_management.php') ? 'active' : ''; ?>">
            <a href="billing_management.php" class="text-white">
                <i class="fas fa-credit-card"></i>
                <span class="nav-text">Billing Management</span>
            </a>
        </li>

        <!-- User Management -->
        <li class="nav-separator">
            <hr class="sidebar-divider">
            <span class="nav-section-title">User Management</span>
        </li>
        
        <li class="<?php echo ($current_page === 'all_users.php') ? 'active' : ''; ?>">
            <a href="all_users.php" class="text-white">
                <i class="fas fa-users"></i>
                <span class="nav-text">All Users</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'root_users.php') ? 'active' : ''; ?>">
            <a href="root_users.php" class="text-white">
                <i class="fas fa-user-crown"></i>
                <span class="nav-text">Root Users</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'billing_calculator_users.php') ? 'active' : ''; ?>">
            <a href="billing_calculator_users.php" class="text-white">
                <i class="fas fa-calculator"></i>
                <span class="nav-text">Billing Calculator Users</span>
            </a>
        </li>

        <!-- System Administration -->
        <li class="nav-separator">
            <hr class="sidebar-divider">
            <span class="nav-section-title">System Administration</span>
        </li>
        
        <li class="<?php echo ($current_page === 'system_resources.php') ? 'active' : ''; ?>">
            <a href="system_resources.php" class="text-white">
                <i class="fas fa-server"></i>
                <span class="nav-text">System Resources</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'cache_management.php') ? 'active' : ''; ?>">
            <a href="cache_management.php" class="text-white">
                <i class="fas fa-sync-alt"></i>
                <span class="nav-text">Cache Management</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'backup_management.php') ? 'active' : ''; ?>">
            <a href="backup_management.php" class="text-white">
                <i class="fas fa-database"></i>
                <span class="nav-text">Backup Management</span>
            </a>
        </li>
        
        <li class="<?php echo ($current_page === 'system_logs.php') ? 'active' : ''; ?>">
            <a href="system_logs.php" class="text-white">
                <i class="fas fa-file-alt"></i>
                <span class="nav-text">System Logs</span>
            </a>
        </li>

        <!-- Legacy Super Admin Access -->
        <li class="nav-separator">
            <hr class="sidebar-divider">
            <span class="nav-section-title">Legacy Access</span>
        </li>
        
        <li>
            <a href="index.php" class="text-white">
                <i class="fas fa-external-link-alt"></i>
                <span class="nav-text">Super Admin Panel</span>
            </a>
        </li>
        
        <li>
            <a href="../dashboard.php" class="text-white">
                <i class="fas fa-external-link-alt"></i>
                <span class="nav-text">Company Dashboard</span>
            </a>
        </li>

        <!-- Settings & Logout -->
        <li class="nav-separator">
            <hr class="sidebar-divider">
        </li>
        
        <li class="<?php echo ($current_page === 'root_settings.php') ? 'active' : ''; ?>">
            <a href="root_settings.php" class="text-white">
                <i class="fas fa-cogs"></i>
                <span class="nav-text">Root Settings</span>
            </a>
        </li>
        
        <li>
            <a href="../logout.php" class="text-white">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-text">Logout</span>
            </a>
        </li>
    </ul>
</nav>

<style>
/* Root-specific navigation styles */
#sidebar {
    width: 250px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 999;
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%) !important;
    transition: all 0.3s;
    overflow-y: auto;
}

.sidebar-header {
    padding: 20px;
    background: rgba(0,0,0,0.2);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h4 {
    color: #f39c12 !important;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.components {
    padding: 0;
}

.components li {
    margin: 0;
}

.components li a {
    padding: 12px 20px;
    display: block;
    text-decoration: none;
    color: #bdc3c7 !important;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.components li a:hover {
    color: #ffffff !important;
    background: rgba(241, 196, 15, 0.1);
    border-left-color: #f1c40f;
}

.components li.active a {
    color: #f1c40f !important;
    background: rgba(241, 196, 15, 0.2);
    border-left-color: #f1c40f;
    font-weight: bold;
}

.components li a i {
    width: 20px;
    margin-right: 10px;
}

.nav-separator {
    margin: 15px 0 5px 0;
}

.sidebar-divider {
    border-color: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

.nav-section-title {
    color: #95a5a6;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: bold;
    letter-spacing: 1px;
    padding: 0 20px;
    display: block;
}

/* Main content adjustment */
#content {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
    #sidebar {
        margin-left: -250px;
    }
    
    #content {
        margin-left: 0;
    }
}

/* Custom scrollbar for sidebar */
#sidebar::-webkit-scrollbar {
    width: 8px;
}

#sidebar::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.1);
}

#sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
}

#sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>