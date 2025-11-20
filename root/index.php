<?php
/**
 * Super Admin Root Directory - Main Entry Point
 * Secure super admin interface with clean navigation
 */
session_start();
require_once '../config/config.php';

// Check if user is a root user
if (!isRootUser()) {
    // If they're a super_admin but not root, redirect to dashboard
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        header('Location: ../dashboard.php');
        exit();
    }
    // Otherwise, unauthorized
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// Root user - redirect to root dashboard
header('Location: root_dashboard.php');
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Control Panel - SecuriRota</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .stats-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .container-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 15px;
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Header -->
    <div class="admin-header text-center">
        <h1 class="mb-3">
            <i class="fas fa-crown text-warning me-3"></i>
            Super Admin Control Panel
        </h1>
        <p class="lead mb-4">Manage your multi-tenant SecuriRota platform with ease</p>
        
        <!-- Quick Stats -->
        <div class="row justify-content-center">
            <div class="col-auto">
                <span class="stats-badge me-3">
                    <i class="fas fa-building me-1"></i> Companies: 
                    <span id="company-count">-</span>
                </span>
            </div>
            <div class="col-auto">
                <span class="stats-badge me-3">
                    <i class="fas fa-users me-1"></i> Users: 
                    <span id="user-count">-</span>
                </span>
            </div>
            <div class="col-auto">
                <span class="stats-badge">
                    <i class="fas fa-shield-alt me-1"></i> Security: 
                    <span class="text-success">Active</span>
                </span>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-4">
            <a href="../pages/activity_logs.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-list me-1"></i> View Activity Logs
            </a>
            <a href="../logout.php" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Features Grid -->
    <div class="row">
        <!-- Dashboard -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h4 class="mb-3">System Dashboard</h4>
                <p class="text-muted mb-4">
                    Comprehensive overview of your entire platform with real-time analytics and monitoring.
                </p>
                <a href="dashboard.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>Access Dashboard
                </a>
            </div>
        </div>

        <!-- Security Management -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 class="mb-3">Security Center</h4>
                <p class="text-muted mb-4">
                    Monitor security events, manage access controls, and configure system-wide security settings.
                </p>
                <a href="security.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>Manage Security
                </a>
            </div>
        </div>

        <!-- Company Onboarding -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-magic"></i>
                </div>
                <h4 class="mb-3">Company Onboarding</h4>
                <p class="text-muted mb-4">
                    Streamlined wizard to add new companies with proper setup and configuration.
                </p>
                <a href="onboarding.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>Start Onboarding
                </a>
            </div>
        </div>

        <!-- Data Migration -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-database"></i>
                </div>
                <h4 class="mb-3">Migration Tools</h4>
                <p class="text-muted mb-4">
                    Migrate existing single-tenant installations to the multi-tenant platform.
                </p>
                <a href="migration.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>Migration Wizard
                </a>
            </div>
        </div>

        <!-- Company Branding -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-palette"></i>
                </div>
                <h4 class="mb-3">Branding Manager</h4>
                <p class="text-muted mb-4">
                    Customize company-specific themes, colors, logos, and branding elements.
                </p>
                <a href="branding.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>Manage Branding
                </a>
            </div>
        </div>

        <!-- System Settings -->
        <div class="col-lg-4 col-md-6">
            <div class="admin-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-cogs"></i>
                </div>
                <h4 class="mb-3">System Settings</h4>
                <p class="text-muted mb-4">
                    Configure global system settings, maintenance mode, and platform configurations.
                </p>
                <a href="settings.php" class="btn btn-admin">
                    <i class="fas fa-arrow-right me-2"></i>System Settings
                </a>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="admin-card">
                <h5 class="mb-4">
                    <i class="fas fa-heartbeat text-success me-2"></i>
                    System Status
                </h5>
                
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle d-inline-block me-3" style="width: 12px; height: 12px;"></div>
                            </div>
                            <div>
                                <div class="fw-bold">Database</div>
                                <small class="text-muted">Connected & Optimized</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle d-inline-block me-3" style="width: 12px; height: 12px;"></div>
                            </div>
                            <div>
                                <div class="fw-bold">Security</div>
                                <small class="text-muted">All Systems Active</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning rounded-circle d-inline-block me-3" style="width: 12px; height: 12px;"></div>
                            </div>
                            <div>
                                <div class="fw-bold">Email Service</div>
                                <small class="text-muted">Degraded Performance</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle d-inline-block me-3" style="width: 12px; height: 12px;"></div>
                            </div>
                            <div>
                                <div class="fw-bold">API Gateway</div>
                                <small class="text-muted">Operational</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-5">
        <p class="text-white-50">
            <i class="fas fa-shield-alt me-1"></i>
            SecuriRota Super Admin Panel - 
            Version 2.0 | 
            Last Updated: <?= date('Y-m-d H:i') ?>
        </p>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Load system stats
document.addEventListener('DOMContentLoaded', function() {
    loadSystemStats();
    setInterval(loadSystemStats, 30000); // Refresh every 30 seconds
});

function loadSystemStats() {
    fetch('../api/system_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('company-count').textContent = data.stats.companies || '-';
                document.getElementById('user-count').textContent = data.stats.users || '-';
            }
        })
        .catch(error => {
            console.error('Failed to load system stats:', error);
        });
}
</script>

</body>
</html>
