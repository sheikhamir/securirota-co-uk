<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';

// Get subscription status from session if available
$subscription_status = $_SESSION['company_subscription_status'] ?? null;
$company_name = $subscription_status['name'] ?? $_SESSION['company_name'] ?? 'Your Company';
$expired_date = $subscription_status['subscription_expires_at'] ?? null;

// Format the expired date nicely
$expired_date_formatted = $expired_date ? date('F j, Y', strtotime($expired_date)) : 'recently';

// Clear the temporary session data
unset($_SESSION['subscription_expired']);
unset($_SESSION['company_subscription_status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired - Security Rota Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .subscription-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 0;
        }
        
        .subscription-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .subscription-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="30" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="70" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="80" r="2" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="1.5" fill="white" opacity="0.1"/></svg>') repeat;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .icon-container {
            position: relative;
            z-index: 2;
            margin-bottom: 1rem;
        }
        
        .icon-container i {
            font-size: 4rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .subscription-header h1 {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .subscription-header p {
            position: relative;
            z-index: 2;
            margin: 0;
            opacity: 0.95;
            font-size: 1.1rem;
            font-weight: 300;
        }
        
        .subscription-body {
            padding: 3rem 2.5rem;
        }
        
        .company-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid #ff6b6b;
        }
        
        .company-info h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: #6c757d;
        }
        
        .info-item i {
            margin-right: 0.8rem;
            color: #ff6b6b;
            width: 20px;
            text-align: center;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            border-radius: 10px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            color: #6c757d;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #2d3436;
        }
        
        .contact-info {
            background: #e8f4f8;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }
        
        .contact-info h5 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .contact-info p {
            margin: 0;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .subscription-header {
                padding: 2rem 1.5rem;
            }
            
            .subscription-body {
                padding: 2rem 1.5rem;
            }
            
            .subscription-header h1 {
                font-size: 1.8rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-outline-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="subscription-card">
        <div class="subscription-header">
            <div class="icon-container">
                <i class="fas fa-credit-card"></i>
            </div>
            <h1>Subscription Expired</h1>
            <p>Your access has been temporarily suspended</p>
        </div>
        
        <div class="subscription-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Access Restricted:</strong> All system functions are currently unavailable due to an expired subscription.
            </div>
            
            <div class="company-info">
                <h3><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($company_name); ?></h3>
                <div class="info-item">
                    <i class="fas fa-calendar-times"></i>
                    <span><strong>Subscription Expired:</strong> <?php echo $expired_date_formatted; ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-lock"></i>
                    <span><strong>Status:</strong> <span class="text-danger">Suspended</span></span>
                </div>
            </div>
            
            <div class="actions">
                <a href="mailto:info@securirota.com?subject=Subscription Renewal - <?php echo urlencode($company_name); ?>" class="btn btn-primary">
                    <i class="fas fa-envelope me-2"></i>Contact Billing
                </a>
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
            
            <div class="contact-info">
                <h5><i class="fas fa-headset me-2"></i>Need Help?</h5>
                <p>
                    Contact our billing department at <strong>info@securirota.com</strong><br>
                    or call <strong>+971 56 277 5557</strong> for immediate assistance.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>