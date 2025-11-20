<?php
/**
 * Super Admin API - Get System Statistics
 * Returns real-time system statistics for dashboard charts
 */
header('Content-Type: application/json');

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get activity data for the last 7 days
    $user_activities = [];
    $system_activities = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // User activities (non-super admin actions)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM activity_log al
            JOIN users u ON al.user_id = u.id
            WHERE DATE(al.created_at) = ? AND u.role != 'super_admin'
        ");
        $stmt->execute([$date]);
        $user_activities[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // System activities (super admin actions)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM activity_log al
            JOIN users u ON al.user_id = u.id
            WHERE DATE(al.created_at) = ? AND u.role = 'super_admin'
        ");
        $stmt->execute([$date]);
        $system_activities[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Get company distribution
    $stmt = $conn->query("
        SELECT 
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_companies,
            COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_companies,
            COUNT(CASE WHEN status = 'trial' THEN 1 END) as trial_companies
        FROM companies
    ");
    $distribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get additional metrics
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE company_id IS NOT NULL) as total_users,
            (SELECT COUNT(*) FROM officers WHERE company_id IS NOT NULL) as total_officers,
            (SELECT COUNT(*) FROM sites WHERE company_id IS NOT NULL) as total_sites,
            (SELECT COUNT(*) FROM shifts WHERE DATE(created_at) = CURDATE()) as todays_shifts
    ");
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user_activities' => $user_activities,
        'system_activities' => $system_activities,
        'active_companies' => (int)$distribution['active_companies'],
        'suspended_companies' => (int)$distribution['suspended_companies'],
        'trial_companies' => (int)$distribution['trial_companies'],
        'metrics' => $metrics,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
