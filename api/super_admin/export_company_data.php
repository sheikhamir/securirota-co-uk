<?php
/**
 * API endpoint to export company data (Super Admin feature)
 */

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

$company_id = (int)($_GET['company_id'] ?? 0);

if (!$company_id) {
    http_response_code(400);
    exit('Invalid company ID');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get company details
    $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ? AND status != 'deleted'");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        http_response_code(404);
        exit('Company not found');
    }
    
    // Prepare data export
    $export_data = [
        'company' => $company,
        'export_date' => date('Y-m-d H:i:s'),
        'exported_by' => $_SESSION['username'] ?? 'Super Admin'
    ];
    
    // Get users
    $stmt = $conn->prepare("SELECT id, username, email, role, status, created_at FROM users WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $export_data['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get officers
    $stmt = $conn->prepare("SELECT * FROM officers WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $export_data['officers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sites
    $stmt = $conn->prepare("SELECT * FROM sites WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $export_data['sites'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get shifts (last 3 months)
    $stmt = $conn->prepare("
        SELECT * FROM shifts 
        WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$company_id]);
    $export_data['shifts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get activity logs (last 30 days)
    $stmt = $conn->prepare("
        SELECT al.*, u.username 
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE u.company_id = ? AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $export_data['activity_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the export activity
    $stmt = $conn->prepare("
        INSERT INTO activity_log (
            user_id, action, details, ip_address, user_agent, created_at
        ) VALUES (?, 'company_data_export', ?, ?, ?, NOW())
    ");
    
    $details = json_encode([
        'company_id' => $company_id,
        'company_name' => $company['name'],
        'export_type' => 'full_data'
    ]);
    
    $stmt->execute([
        $_SESSION['user_id'],
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Set headers for file download
    $filename = 'company_' . $company['slug'] . '_export_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen(json_encode($export_data, JSON_PRETTY_PRINT)));
    
    // Output the data
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Export company data error: " . $e->getMessage());
    http_response_code(500);
    exit('Export failed: ' . $e->getMessage());
}
?>
