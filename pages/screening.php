<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Screening & Vetting';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-check"></i> Screening & Vetting Management</h3>
    </div>
    
    <div style="padding: 40px; text-align: center;">
        <div style="margin-bottom: 30px;">
            <i class="fas fa-user-shield" style="font-size: 4rem; color: #667eea; margin-bottom: 20px;"></i>
            <h2>Screening & Vetting Module</h2>
            <p class="text-muted">This module will handle officer background checks, reference verification, and compliance tracking.</p>
        </div>
        
        <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin: 40px 0;">
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-search" style="font-size: 2rem; color: #28a745; margin-bottom: 15px;"></i>
                <h4>Background Checks</h4>
                <p>Manage DBS checks, criminal background verification, and employment history validation.</p>
            </div>
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-users" style="font-size: 2rem; color: #17a2b8; margin-bottom: 15px;"></i>
                <h4>Reference Verification</h4>
                <p>Track and verify employment references, character references, and professional recommendations.</p>
            </div>
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-certificate" style="font-size: 2rem; color: #ffc107; margin-bottom: 15px;"></i>
                <h4>Compliance Tracking</h4>
                <p>Monitor SIA license validity, training certifications, and document expiry dates.</p>
            </div>
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-clipboard-check" style="font-size: 2rem; color: #dc3545; margin-bottom: 15px;"></i>
                <h4>Vetting Status</h4>
                <p>Real-time tracking of vetting progress, approval status, and clearance levels.</p>
            </div>
        </div>
        
        <div class="alert" style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px; padding: 20px; margin: 30px 0;">
            <h5 style="color: #1976d2; margin-bottom: 10px;">
                <i class="fas fa-info-circle"></i> Module Status
            </h5>
            <p style="margin: 0; color: #1976d2;">
                This module is currently under development. It will integrate with leading background check providers 
                and compliance databases to streamline the vetting process.
            </p>
        </div>
        
        <div class="d-flex gap-15 justify-center">
            <a href="../dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="officers.php" class="btn btn-success">
                <i class="fas fa-users"></i> Manage Officers
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
