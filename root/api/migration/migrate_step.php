<?php
/**
 * Migration API - Migrate Data Step
 * Handles incremental data migration in steps
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

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $step = $input['step'] ?? 'users';
    $company_id = $input['company_id'] ?? null;
    $batch_size = $input['batch_size'] ?? 100;
    $offset = $input['offset'] ?? 0;
    
    if (!$company_id) {
        throw new Exception('Company ID is required');
    }
    
    $results = [];
    
    switch ($step) {
        case 'users':
            $results = migrateUsers($pdo, $company_id, $batch_size, $offset);
            break;
            
        case 'sites':
            $results = migrateSites($pdo, $company_id, $batch_size, $offset);
            break;
            
        case 'officers':
            $results = migrateOfficers($pdo, $company_id, $batch_size, $offset);
            break;
            
        case 'shifts':
            $results = migrateShifts($pdo, $company_id, $batch_size, $offset);
            break;
            
        case 'activities':
            $results = migrateActivities($pdo, $company_id, $batch_size, $offset);
            break;
            
        case 'templates':
            $results = migrateTemplates($pdo, $company_id, $batch_size, $offset);
            break;
            
        default:
            throw new Exception('Invalid migration step');
    }
    
    echo json_encode([
        'success' => true,
        'step' => $step,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function migrateUsers($pdo, $company_id, $batch_size, $offset) {
    // Get users that don't have company_id set (excluding super admin)
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (company_id IS NULL OR company_id = 0) 
        AND role != 'super_admin'
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($users as $user) {
        $update_stmt = $pdo->prepare("UPDATE users SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $user['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM users 
        WHERE (company_id IS NULL OR company_id = 0) 
        AND role != 'super_admin'
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}

function migrateSites($pdo, $company_id, $batch_size, $offset) {
    // Get sites that don't have company_id set
    $stmt = $pdo->prepare("
        SELECT * FROM sites 
        WHERE company_id IS NULL OR company_id = 0
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($sites as $site) {
        $update_stmt = $pdo->prepare("UPDATE sites SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $site['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM sites 
        WHERE company_id IS NULL OR company_id = 0
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}

function migrateOfficers($pdo, $company_id, $batch_size, $offset) {
    // Get officers that don't have company_id set
    $stmt = $pdo->prepare("
        SELECT * FROM officers 
        WHERE company_id IS NULL OR company_id = 0
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($officers as $officer) {
        $update_stmt = $pdo->prepare("UPDATE officers SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $officer['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM officers 
        WHERE company_id IS NULL OR company_id = 0
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}

function migrateShifts($pdo, $company_id, $batch_size, $offset) {
    // Get shifts that don't have company_id set
    $stmt = $pdo->prepare("
        SELECT * FROM shifts 
        WHERE company_id IS NULL OR company_id = 0
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($shifts as $shift) {
        $update_stmt = $pdo->prepare("UPDATE shifts SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $shift['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM shifts 
        WHERE company_id IS NULL OR company_id = 0
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}

function migrateActivities($pdo, $company_id, $batch_size, $offset) {
    // Get activity logs that don't have company_id set
    $stmt = $pdo->prepare("
        SELECT * FROM activity_log 
        WHERE company_id IS NULL OR company_id = 0
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($activities as $activity) {
        $update_stmt = $pdo->prepare("UPDATE activity_log SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $activity['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM activity_log 
        WHERE company_id IS NULL OR company_id = 0
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}

function migrateTemplates($pdo, $company_id, $batch_size, $offset) {
    // Get email templates that don't have company_id set
    $stmt = $pdo->prepare("
        SELECT * FROM email_templates 
        WHERE company_id IS NULL OR company_id = 0
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$batch_size, $offset]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($templates as $template) {
        $update_stmt = $pdo->prepare("UPDATE email_templates SET company_id = ? WHERE id = ?");
        $update_stmt->execute([$company_id, $template['id']]);
        $migrated++;
    }
    
    // Get total remaining
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM email_templates 
        WHERE company_id IS NULL OR company_id = 0
    ");
    $count_stmt->execute();
    $remaining = $count_stmt->fetchColumn();
    
    return [
        'migrated' => $migrated,
        'remaining' => $remaining,
        'has_more' => $remaining > 0
    ];
}
?>
