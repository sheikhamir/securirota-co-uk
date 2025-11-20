<?php
/**
 * Root Management Header Template
 * Basic header template for root management pages
 */

// Security check
if (!defined('ROOT_ACCESS') || !isset($page_title)) {
    die('Direct access not allowed');
}

// Set defaults
$page_title = $page_title ?? 'Root Management';
$page_description = $page_description ?? 'Root administration panel';
$active_page = $active_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Root Control</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Root Theme CSS -->
    <link href="assets/css/root-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-crown me-2"></i>Root Control
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="companyDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-building me-1"></i>Companies
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="companies.php">
                                <i class="fas fa-building me-2"></i>Manage Companies
                            </a></li>
                            <li><a class="dropdown-item" href="subscription_tiers.php">
                                <i class="fas fa-layer-group me-2"></i>Subscription Tiers
                            </a></li>
                            <li><a class="dropdown-item" href="billing_management.php">
                                <i class="fas fa-credit-card me-2"></i>Billing Management
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="all_users.php">
                                <i class="fas fa-users me-2"></i>All Users
                            </a></li>
                            <li><a class="dropdown-item" href="root_users.php">
                                <i class="fas fa-user-crown me-2"></i>Root Users
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="systemDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-server me-1"></i>System
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="system_resources.php">
                                <i class="fas fa-server me-2"></i>System Resources
                            </a></li>
                            <li><a class="dropdown-item" href="cache_management.php">
                                <i class="fas fa-memory me-2"></i>Cache Management
                            </a></li>
                            <li><a class="dropdown-item" href="backup_management.php">
                                <i class="fas fa-archive me-2"></i>Backup Management
                            </a></li>
                            <li><a class="dropdown-item" href="system_logs.php">
                                <i class="fas fa-file-alt me-2"></i>System Logs
                            </a></li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenuDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-crown me-1"></i><?= htmlspecialchars($_SESSION['username'] ?? 'Root User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../dashboard.php">
                                <i class="fas fa-home me-2"></i>Main Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid mt-4">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['warning']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['info']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

        <!-- Page Content Starts Here -->