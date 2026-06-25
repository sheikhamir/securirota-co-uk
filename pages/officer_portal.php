<?php
// Officer Portal - Limited access for officers
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an officer
if (!isLoggedIn() || !hasRole('officer')) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Get officer data
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize variables
    $upcoming_shifts = [];
    $completed_shifts = [];
    $all_shifts = [];
    $total_hours = 0;
    $total_earnings = 0;
    
    // Get officer information
    $stmt = $conn->prepare("
        SELECT o.*, u.mobile_number, u.email as user_email 
        FROM officers o 
        JOIN users u ON o.user_id = u.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$officer) {
        throw new Exception("Officer profile not found.");
    }
    
    // Check if officer is suspended
    if ($officer['suspend']) {
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php?error=suspended');
        exit();
    }
    
    // Helper function to get shift display information based on current time
    function getShiftDisplayInfo($shift) {
        $current_time = time();
        $shift_date = $shift['shift_date'];
        $start_time = $shift['start_time'];
        $end_time = $shift['end_time'];
        $checkin_timestamp = $shift['checkin_timestamp'];
        $checkout_timestamp = $shift['checkout_timestamp'];
        
        // Only show check-in/out options for today's confirmed or in_progress shifts
        if ($shift_date != date('Y-m-d') || !in_array($shift['status'], ['confirmed', 'in_progress'])) {
            return ['action' => 'none', 'message' => '', 'class' => ''];
        }
        
        $shift_start = strtotime("$shift_date $start_time");
        $shift_end = strtotime("$shift_date $end_time");
        $checkin_window_start = $shift_start - (15 * 60); // 15 min before
        $checkin_window_end = $shift_start + (15 * 60);   // 15 min after
        $checkout_window_start = $shift_end - (15 * 60);  // 15 min before end
        
        if (!$checkin_timestamp) {
            // Not checked in yet
            if ($current_time < $checkin_window_start) {
                $minutes_until = ceil(($checkin_window_start - $current_time) / 60);
                return [
                    'action' => 'wait', 
                    'message' => "Check-in available in $minutes_until minutes",
                    'class' => 'text-info',
                    'countdown_target' => $checkin_window_start
                ];
            } elseif ($current_time >= $checkin_window_start && $current_time <= $checkin_window_end) {
                return [
                    'action' => 'checkin', 
                    'message' => 'Ready to check in',
                    'class' => 'text-success fw-bold',
                    'button_class' => 'btn-success',
                    'button_text' => '<i class="fas fa-clock"></i> Check In Now'
                ];
            } else {
                return [
                    'action' => 'late_checkin', 
                    'message' => 'Late check-in required',
                    'class' => 'text-warning fw-bold',
                    'button_class' => 'btn-warning',
                    'button_text' => '<i class="fas fa-exclamation-triangle"></i> Late Check In'
                ];
            }
        } else {
            // Already checked in
            if (!$checkout_timestamp) {
                if ($current_time < $checkout_window_start) {
                    $checkin_time = date('H:i', strtotime($checkin_timestamp));
                    return [
                        'action' => 'working', 
                        'message' => "On duty since $checkin_time",
                        'class' => 'text-primary fw-bold',
                        'working_since' => $checkin_timestamp
                    ];
                } else {
                    return [
                        'action' => 'checkout', 
                        'message' => 'Ready to check out',
                        'class' => 'text-info fw-bold',
                        'button_class' => 'btn-info',
                        'button_text' => '<i class="fas fa-clock"></i> Check Out Now'
                    ];
                }
            } else {
                $checkin_time = date('H:i', strtotime($checkin_timestamp));
                $checkout_time = date('H:i', strtotime($checkout_timestamp));
                return [
                    'action' => 'completed', 
                    'message' => "Completed ($checkin_time - $checkout_time)",
                    'class' => 'text-success'
                ];
            }
        }
    }

    // Handle shift actions
    if ($_POST && isset($_POST['action'])) {
        if ($_POST['action'] === 'accept_shift') {
            $stmt = $conn->prepare("
                UPDATE shifts SET status = 'confirmed' 
                WHERE id = ? AND officer_id = ? AND status = 'allocated'
            ");
            $stmt->execute([$_POST['shift_id'], $officer['id']]);
            
            // Log the activity
            $stmt = $conn->prepare("
                INSERT INTO shift_activities (shift_id, officer_id, activity_type, created_at) 
                VALUES (?, ?, 'accepted', NOW())
            ");
            $stmt->execute([$_POST['shift_id'], $officer['id']]);
            
            $success = "Shift confirmed successfully!";
        }
        
        if ($_POST['action'] === 'decline_shift') {
            $reason = trim($_POST['decline_reason']);
            if (empty($reason)) {
                $error = "Please provide a reason for declining the shift.";
            } else {
                $stmt = $conn->prepare("
                    UPDATE shifts SET status = 'declined', decline_reason = ? 
                    WHERE id = ? AND officer_id = ? AND status = 'allocated'
                ");
                $stmt->execute([$reason, $_POST['shift_id'], $officer['id']]);
                
                // Log the activity
                $stmt = $conn->prepare("
                    INSERT INTO shift_activities (shift_id, officer_id, activity_type, reason, created_at) 
                    VALUES (?, ?, 'declined', ?, NOW())
                ");
                $stmt->execute([$_POST['shift_id'], $officer['id'], $reason]);
                
                $success = "Shift declined successfully!";
            }
        }
    }
    
    $today = date('Y-m-d');
    $current_month = date('m');
    $current_year = date('Y');

    // Get recent unconfirmed shifts (allocated status)
    $stmt = $conn->prepare("
        SELECT s.*, sites.site_name, c.company_name as client_name,
               r.name as role_name
        FROM shifts s
        JOIN sites ON s.site_id = sites.id
        JOIN clients c ON sites.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        WHERE s.officer_id = ? AND s.status = 'allocated' AND s.shift_date >= ?
        ORDER BY s.shift_date ASC, s.start_time ASC
        LIMIT 10
    ");
    $stmt->execute([$officer['id'], $today]);
    $unconfirmed_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming shifts (all statuses)
    $stmt = $conn->prepare("
        SELECT s.*, sites.site_name, c.company_name as client_name,
               r.name as role_name
        FROM shifts s
        JOIN sites ON s.site_id = sites.id
        JOIN clients c ON sites.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        WHERE s.officer_id = ? AND s.shift_date >= ?
        ORDER BY s.shift_date ASC, s.start_time ASC
        LIMIT 20
    ");
    $stmt->execute([$officer['id'], $today]);
    $upcoming_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed shifts for this month
    $stmt = $conn->prepare("
        SELECT s.*, sites.site_name, c.company_name as client_name,
               r.name as role_name,
               CASE 
                   WHEN s.checkin_timestamp IS NOT NULL AND s.checkout_timestamp IS NOT NULL 
                   THEN TIMESTAMPDIFF(HOUR, s.checkin_timestamp, s.checkout_timestamp)
                   WHEN s.end_time < s.start_time 
                   THEN TIMESTAMPDIFF(HOUR, CONCAT(s.shift_date, ' ', s.start_time), CONCAT(DATE_ADD(s.shift_date, INTERVAL 1 DAY), ' ', s.end_time))
                   ELSE TIMESTAMPDIFF(HOUR, CONCAT(s.shift_date, ' ', s.start_time), CONCAT(s.shift_date, ' ', s.end_time))
               END as hours_worked
        FROM shifts s
        JOIN sites ON s.site_id = sites.id
        JOIN clients c ON sites.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        WHERE s.officer_id = ? AND s.status = 'completed'
        AND MONTH(s.shift_date) = ?
        AND YEAR(s.shift_date) = ?
        ORDER BY s.shift_date DESC, s.start_time DESC
    ");
    $stmt->execute([$officer['id'], $current_month, $current_year]);
    $completed_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total earnings for this month
    if (!empty($completed_shifts)) {
        foreach ($completed_shifts as $shift) {
            if ($shift['hours_worked'] && $shift['officer_rate']) {
                $total_hours += $shift['hours_worked'];
                $total_earnings += $shift['hours_worked'] * $shift['officer_rate'];
            }
        }
    }
    
    // Get shifts based on filter
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $where_clause = "WHERE s.officer_id = ?";
    $params = [$officer['id']];
    
    if ($status_filter !== 'all') {
        $where_clause .= " AND s.status = ?";
        $params[] = $status_filter;
    }
    
    $stmt = $conn->prepare("
        SELECT s.*, sites.site_name, c.company_name as client_name,
               r.name as role_name,
               CASE 
                   WHEN s.checkin_timestamp IS NOT NULL AND s.checkout_timestamp IS NOT NULL 
                   THEN TIMESTAMPDIFF(HOUR, s.checkin_timestamp, s.checkout_timestamp)
                   WHEN s.end_time < s.start_time 
                   THEN TIMESTAMPDIFF(HOUR, CONCAT(s.shift_date, ' ', s.start_time), CONCAT(DATE_ADD(s.shift_date, INTERVAL 1 DAY), ' ', s.end_time))
                   ELSE TIMESTAMPDIFF(HOUR, CONCAT(s.shift_date, ' ', s.start_time), CONCAT(s.shift_date, ' ', s.end_time))
               END as hours_worked,
               sa.reason as decline_reason
        FROM shifts s
        JOIN sites ON s.site_id = sites.id
        JOIN clients c ON sites.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        LEFT JOIN shift_activities sa ON s.id = sa.shift_id AND sa.activity_type = 'declined'
        $where_clause
        ORDER BY s.shift_date DESC, s.start_time DESC
        LIMIT ? OFFSET ?
    ");
    
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $all_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count unconfirmed shifts for stats
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM shifts 
        WHERE officer_id = ? AND status = 'allocated' AND shift_date >= ?
    ");
    $stmt->execute([$officer['id'], $today]);
    $unconfirmed_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    $error = $e->getMessage();
    // Ensure all variables are initialized even on error
    if (!isset($unconfirmed_shifts)) $unconfirmed_shifts = [];
    if (!isset($upcoming_shifts)) $upcoming_shifts = [];
    if (!isset($completed_shifts)) $completed_shifts = [];
    if (!isset($all_shifts)) $all_shifts = [];
    if (!isset($total_hours)) $total_hours = 0;
    if (!isset($total_earnings)) $total_earnings = 0;
    if (!isset($unconfirmed_count)) $unconfirmed_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Portal - SecuriRota</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .shift-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        .shift-pending { border-left-color: #ffc107; }
        .shift-allocated { border-left-color: #ffc107; }
        .shift-confirmed { border-left-color: #28a745; }
        .shift-in_progress { 
            border-left-color: #17a2b8; 
            background-color: #e6f9ff;
        }
        .shift-declined { 
            border-left-color: #6c757d; 
            background-color: #f8f9fa;
            opacity: 0.8;
        }
        .shift-completed { border-left-color: #007bff; }
        .unconfirmed-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        .filter-tabs .nav-link {
            border: none;
            border-radius: 25px;
            margin: 0 5px;
            padding: 8px 20px;
        }
        .filter-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .btn-xs {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1;
            border-radius: 0.2rem;
        }
        .working-indicator .blink {
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        .shift-status-info {
            text-align: center;
        }
        .countdown-timer {
            font-family: monospace;
            font-weight: bold;
        }
        .checkin-modal .modal-body {
            text-align: center;
        }
        .photo-preview {
            max-width: 300px;
            max-height: 300px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        .camera-container {
            position: relative;
            display: inline-block;
        }
        .take-photo-btn {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>SecuriRota Officer Portal
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h4 class="text-primary">Welcome, <?php echo htmlspecialchars($officer['first_name']); ?>!</h4>
                        <p class="text-muted mb-0">Staff ID: <strong><?php echo htmlspecialchars($officer['staff_id']); ?></strong></p>
                        <p class="text-muted">Mobile: <?php echo htmlspecialchars($officer['mobile_number']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h5><?php echo $unconfirmed_count; ?></h5>
                        <small>Unconfirmed Shifts</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                        <h5><?php echo is_array($upcoming_shifts) ? count($upcoming_shifts) : 0; ?></h5>
                        <small>Upcoming Shifts</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h5><?php echo $total_hours; ?>h</h5>
                        <small>Hours This Month</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-pound-sign fa-2x mb-2"></i>
                        <h5>£<?php echo number_format($total_earnings, 2); ?></h5>
                        <small>Earnings This Month</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unconfirmed Shifts Section -->
        <?php if (!empty($unconfirmed_shifts) && is_array($unconfirmed_shifts)): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Shifts Requiring Your Response
                    <span class="badge bg-dark ms-2 unconfirmed-badge"><?php echo count($unconfirmed_shifts); ?></span>
                </h5>
                <small>Please confirm or decline these shifts</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($unconfirmed_shifts as $shift): ?>
                        <div class="col-md-6">
                            <div class="card shift-card shift-allocated border-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title"><?php echo htmlspecialchars($shift['site_name']); ?></h6>
                                            <?php /*<p class="card-text text-muted mb-1">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($shift['client_name']); ?>
                                            </p>*/ ?>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('D, M j, Y', strtotime($shift['shift_date'])); ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?>
                                            </p>
                                            <p class="card-text">
                                                <i class="fas fa-user-tag me-1"></i><?php echo htmlspecialchars($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <div class="btn-group-vertical">
                                                <button class="btn btn-success btn-sm" onclick="acceptShift(<?php echo $shift['id']; ?>)">
                                                    <i class="fas fa-check"></i> Confirm
                                                </button>
                                                <button class="btn btn-danger btn-sm mt-1" onclick="declineShift(<?php echo $shift['id']; ?>)">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upcoming Shifts -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>All Upcoming Shifts</h5>
                <small class="text-muted">Next 20 shifts</small>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_shifts) || !is_array($upcoming_shifts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No upcoming shifts scheduled.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($upcoming_shifts as $shift): ?>
                            <div class="col-md-6">
                                <div class="card shift-card shift-<?php echo $shift['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title"><?php echo htmlspecialchars($shift['site_name']); ?></h6>
                                                <?php /*<p class="card-text text-muted mb-1">
                                                    <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($shift['client_name']); ?>
                                                </p>*/ ?>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-calendar me-1"></i><?php echo date('D, M j, Y', strtotime($shift['shift_date'])); ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?>
                                                </p>
                                                <p class="card-text">
                                                    <i class="fas fa-user-tag me-1"></i><?php echo htmlspecialchars($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?>
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($shift['status'] === 'allocated'): ?>
                                                    <div class="btn-group-vertical">
                                                        <button class="btn btn-success btn-sm" onclick="acceptShift(<?php echo $shift['id']; ?>)">
                                                            <i class="fas fa-check"></i> Confirm
                                                        </button>
                                                        <button class="btn btn-danger btn-sm mt-1" onclick="declineShift(<?php echo $shift['id']; ?>)">
                                                            <i class="fas fa-times"></i> Decline
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <?php 
                                                    $display_info = getShiftDisplayInfo($shift);
                                                    ?>
                                                    <div class="shift-status-info">
                                                        <span class="badge bg-<?php 
                                                            echo $shift['status'] === 'confirmed' ? 'success' : 
                                                                ($shift['status'] === 'in_progress' ? 'info' : 
                                                                ($shift['status'] === 'declined' ? 'secondary' : 
                                                                ($shift['status'] === 'completed' ? 'primary' : 
                                                                ($shift['status'] === 'cancelled' ? 'secondary' : 'warning')))); 
                                                        ?>">
                                                            <?php echo ucfirst($shift['status']); ?>
                                                        </span>
                                                        
                                                        <?php if ($display_info['action'] !== 'none'): ?>
                                                            <div class="mt-2">
                                                                <small class="<?php echo $display_info['class']; ?>">
                                                                    <?php echo $display_info['message']; ?>
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($display_info['action'] === 'checkin' || $display_info['action'] === 'late_checkin'): ?>
                                                            <div class="mt-2">
                                                                <button class="btn <?php echo $display_info['button_class']; ?> btn-sm" 
                                                                        onclick="showCheckinModal(<?php echo $shift['id']; ?>, '<?php echo $display_info['action']; ?>')">
                                                                    <?php echo $display_info['button_text']; ?>
                                                                </button>
                                                            </div>
                                                        <?php elseif ($display_info['action'] === 'checkout'): ?>
                                                            <div class="mt-2">
                                                                <button class="btn <?php echo $display_info['button_class']; ?> btn-sm" 
                                                                        onclick="showCheckoutModal(<?php echo $shift['id']; ?>)">
                                                                    <?php echo $display_info['button_text']; ?>
                                                                </button>
                                                            </div>
                                                        <?php elseif ($display_info['action'] === 'working'): ?>
                                                            <div class="mt-2">
                                                                <div class="working-indicator">
                                                                    <i class="fas fa-circle text-success blink"></i>
                                                                    <small class="text-muted">Working</small>
                                                                </div>
                                                            </div>
                                                        <?php elseif ($display_info['action'] === 'wait' && isset($display_info['countdown_target'])): ?>
                                                            <div class="mt-2">
                                                                <div class="countdown-timer" data-target="<?php echo $display_info['countdown_target']; ?>">
                                                                    <small class="text-muted">Countdown updating...</small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($shift['status'] === 'confirmed' && strtotime($shift['shift_date']) > time() && $display_info['action'] === 'none'): ?>
                                                            <div class="mt-2">
                                                                <button class="btn btn-warning btn-sm" onclick="cancelOfficerShift(<?php echo $shift['id']; ?>)">
                                                                    <i class="fas fa-ban"></i> Cancel
                                                                </button>
                                                            </div>
                                                        <?php elseif ($shift['status'] === 'declined'): ?>
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-ban me-1"></i>Declined
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shift History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-3"><i class="fas fa-history me-2"></i>Complete Shift History</h5>
                
                <!-- Filter Tabs -->
                <ul class="nav nav-pills filter-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'active' : ''; ?>" 
                           href="?status=all">
                            <i class="fas fa-list me-1"></i>All Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($_GET['status']) && $_GET['status'] === 'allocated') ? 'active' : ''; ?>" 
                           href="?status=allocated">
                            <i class="fas fa-hourglass-half me-1"></i>Unconfirmed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($_GET['status']) && $_GET['status'] === 'confirmed') ? 'active' : ''; ?>" 
                           href="?status=confirmed">
                            <i class="fas fa-check-circle me-1"></i>Confirmed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($_GET['status']) && $_GET['status'] === 'in_progress') ? 'active' : ''; ?>" 
                           href="?status=in_progress">
                            <i class="fas fa-play-circle me-1"></i>In Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'active' : ''; ?>" 
                           href="?status=completed">
                            <i class="fas fa-flag-checkered me-1"></i>Completed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($_GET['status']) && $_GET['status'] === 'declined') ? 'active' : ''; ?>" 
                           href="?status=declined">
                            <i class="fas fa-times-circle me-1"></i>Declined
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Site</th>
                                <th>Time</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Hours</th>
                                <th>Earnings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($all_shifts)): ?>
                                <?php foreach ($all_shifts as $shift): ?>
                                <tr class="<?php echo $shift['status'] === 'declined' ? 'table-secondary' : ''; ?>">
                                    <td><?php echo date('M j, Y', strtotime($shift['shift_date'])); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($shift['site_name']); ?></small>
                                        <?php /*<small class="text-muted"><?php echo htmlspecialchars($shift['client_name']); ?></small>*/ ?>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $shift['status'] === 'completed' ? 'success' : 
                                                ($shift['status'] === 'in_progress' ? 'info' : 
                                                ($shift['status'] === 'confirmed' ? 'primary' : 
                                                ($shift['status'] === 'declined' ? 'secondary' : 
                                                ($shift['status'] === 'allocated' ? 'warning' : 'light')))); 
                                        ?>">
                                            <?php echo ucfirst($shift['status']); ?>
                                        </span>
                                        <?php if ($shift['status'] === 'declined' && $shift['decline_reason']): ?>
                                            <br><small class="text-muted" title="<?php echo htmlspecialchars($shift['decline_reason']); ?>">
                                                <i class="fas fa-info-circle"></i> Reason provided
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($shift['hours_worked']): ?>
                                            <?php echo $shift['hours_worked']; ?>h
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($shift['hours_worked'] && $shift['officer_rate']): ?>
                                            £<?php echo number_format($shift['hours_worked'] * $shift['officer_rate'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($shift['status'] === 'allocated' && strtotime($shift['shift_date']) >= strtotime('today')): ?>
                                            <div class="btn-group-vertical">
                                                <button class="btn btn-success btn-xs" onclick="acceptShift(<?php echo $shift['id']; ?>)" title="Confirm Shift">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-xs mt-1" onclick="declineShift(<?php echo $shift['id']; ?>)" title="Decline Shift">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No shifts found for the selected filter.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Accept Shift Form -->
    <form id="acceptShiftForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="accept_shift">
        <input type="hidden" name="shift_id" id="acceptShiftId">
    </form>

    <!-- Decline Shift Modal -->
    <div class="modal fade" id="declineShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Decline Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="decline_shift">
                    <input type="hidden" name="shift_id" id="declineShiftId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Please provide a reason for declining this shift:</label>
                            <textarea class="form-control" name="decline_reason" rows="3" required placeholder="e.g., Personal commitment, illness, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Decline Shift</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Check-in Modal -->
    <div class="modal fade" id="checkinModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content checkin-modal">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clock"></i> Check In to Shift
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="checkinContent">
                        <p class="mb-3">Please take a selfie to confirm your check-in</p>
                        
                        <div class="camera-container">
                            <video id="checkinVideo" width="300" height="225" autoplay playsinline muted style="display: none; border-radius: 8px;"></video>
                            <canvas id="checkinCanvas" width="300" height="225" style="display: none;"></canvas>
                            <img id="checkinPreview" class="photo-preview" style="display: none;" />
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" id="startCheckinCamera" class="btn btn-primary take-photo-btn">
                                <i class="fas fa-camera"></i> Open Camera
                            </button>
                            <button type="button" id="takeCheckinPhoto" class="btn btn-success take-photo-btn" style="display: none;">
                                <i class="fas fa-camera"></i> Take Photo
                            </button>
                            <button type="button" id="retakeCheckinPhoto" class="btn btn-warning take-photo-btn" style="display: none;">
                                <i class="fas fa-redo"></i> Retake Photo
                            </button>
                        </div>
                        
                        <div id="checkinLateReason" style="display: none;" class="mt-3">
                            <label class="form-label">Reason for late check-in:</label>
                            <textarea class="form-control" rows="2" placeholder="Please explain why you're checking in late..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submitCheckin" class="btn btn-success" disabled>
                        <i class="fas fa-check"></i> Confirm Check In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content checkin-modal">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clock"></i> Check Out of Shift
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="checkoutContent">
                        <p class="mb-3">Please take a selfie to confirm your check-out</p>
                        
                        <div class="camera-container">
                            <video id="checkoutVideo" width="300" height="225" autoplay playsinline muted style="display: none; border-radius: 8px;"></video>
                            <canvas id="checkoutCanvas" width="300" height="225" style="display: none;"></canvas>
                            <img id="checkoutPreview" class="photo-preview" style="display: none;" />
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" id="startCheckoutCamera" class="btn btn-primary take-photo-btn">
                                <i class="fas fa-camera"></i> Open Camera
                            </button>
                            <button type="button" id="takeCheckoutPhoto" class="btn btn-success take-photo-btn" style="display: none;">
                                <i class="fas fa-camera"></i> Take Photo
                            </button>
                            <button type="button" id="retakeCheckoutPhoto" class="btn btn-warning take-photo-btn" style="display: none;">
                                <i class="fas fa-redo"></i> Retake Photo
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submitCheckout" class="btn btn-info" disabled>
                        <i class="fas fa-check"></i> Confirm Check Out
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentShiftId = null;
    let currentAction = null;
    let checkinStream = null;
    let checkoutStream = null;

    function getCameraErrorMessage(error) {
        if (!window.isSecureContext) {
            return 'Camera access requires HTTPS. Please open the portal using the secure https:// address.';
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return 'This browser does not support camera access. Please try a recent version of Chrome, Safari, Edge, or Firefox.';
        }

        if (error && error.name === 'NotAllowedError') {
            return 'Camera permission was blocked. Please allow camera access in your browser settings and try again.';
        }

        if (error && error.name === 'NotFoundError') {
            return 'No camera was found on this device.';
        }

        return 'Error accessing camera: ' + (error && error.message ? error.message : 'Unknown error');
    }

    function stopCameraStream(stream) {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    }

    function startCamera(videoId, startButtonId, takeButtonId, setStream) {
        const startButton = document.getElementById(startButtonId);
        const originalLabel = startButton.innerHTML;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
            alert(getCameraErrorMessage());
            return;
        }

        startButton.disabled = true;
        startButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening...';

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(function(stream) {
                setStream(stream);
                const video = document.getElementById(videoId);
                video.srcObject = stream;
                video.style.display = 'block';
                video.play().catch(function() {});

                startButton.style.display = 'none';
                document.getElementById(takeButtonId).style.display = 'inline-block';
            })
            .catch(function(err) {
                alert(getCameraErrorMessage(err));
            })
            .finally(function() {
                startButton.disabled = false;
                startButton.innerHTML = originalLabel;
            });
    }

    // Show check-in modal
    function showCheckinModal(shiftId, action) {
        currentShiftId = shiftId;
        currentAction = action;
        
        // Reset modal state
        resetCheckinModal();

        // Show/hide late reason field
        if (action === 'late_checkin') {
            document.getElementById('checkinLateReason').style.display = 'block';
        } else {
            document.getElementById('checkinLateReason').style.display = 'none';
        }

        // Show modal
        new bootstrap.Modal(document.getElementById('checkinModal')).show();
    }

    // Show check-out modal
    function showCheckoutModal(shiftId) {
        currentShiftId = shiftId;
        currentAction = 'checkout';
        
        // Reset modal state
        resetCheckoutModal();
        
        // Show modal
        new bootstrap.Modal(document.getElementById('checkoutModal')).show();
    }

    // Reset check-in modal
    function resetCheckinModal() {
        document.getElementById('checkinVideo').style.display = 'none';
        document.getElementById('checkinPreview').style.display = 'none';
        document.getElementById('startCheckinCamera').style.display = 'inline-block';
        document.getElementById('takeCheckinPhoto').style.display = 'none';
        document.getElementById('retakeCheckinPhoto').style.display = 'none';
        document.querySelector('#checkinLateReason textarea').value = '';
        document.getElementById('submitCheckin').disabled = true;
        
        if (checkinStream) {
            stopCameraStream(checkinStream);
            checkinStream = null;
        }
    }

    // Reset check-out modal
    function resetCheckoutModal() {
        document.getElementById('checkoutVideo').style.display = 'none';
        document.getElementById('checkoutPreview').style.display = 'none';
        document.getElementById('startCheckoutCamera').style.display = 'inline-block';
        document.getElementById('takeCheckoutPhoto').style.display = 'none';
        document.getElementById('retakeCheckoutPhoto').style.display = 'none';
        document.getElementById('submitCheckout').disabled = true;
        
        if (checkoutStream) {
            stopCameraStream(checkoutStream);
            checkoutStream = null;
        }
    }

    // Start check-in camera
    document.getElementById('startCheckinCamera').addEventListener('click', function() {
        startCamera('checkinVideo', 'startCheckinCamera', 'takeCheckinPhoto', function(stream) {
            checkinStream = stream;
        });
    });

    // Take check-in photo
    document.getElementById('takeCheckinPhoto').addEventListener('click', function() {
        const video = document.getElementById('checkinVideo');
        const canvas = document.getElementById('checkinCanvas');
        const preview = document.getElementById('checkinPreview');
        const context = canvas.getContext('2d');
        
        context.drawImage(video, 0, 0, 300, 225);
        const dataURL = canvas.toDataURL('image/jpeg');
        
        preview.src = dataURL;
        preview.style.display = 'block';
        video.style.display = 'none';
        
        document.getElementById('takeCheckinPhoto').style.display = 'none';
        document.getElementById('retakeCheckinPhoto').style.display = 'inline-block';
        document.getElementById('submitCheckin').disabled = false;
        
        // Stop camera stream
        if (checkinStream) {
            stopCameraStream(checkinStream);
            checkinStream = null;
        }
    });

    // Retake check-in photo
    document.getElementById('retakeCheckinPhoto').addEventListener('click', function() {
        document.getElementById('checkinPreview').style.display = 'none';
        document.getElementById('retakeCheckinPhoto').style.display = 'none';
        document.getElementById('startCheckinCamera').style.display = 'inline-block';
        document.getElementById('submitCheckin').disabled = true;
    });

    // Start check-out camera
    document.getElementById('startCheckoutCamera').addEventListener('click', function() {
        startCamera('checkoutVideo', 'startCheckoutCamera', 'takeCheckoutPhoto', function(stream) {
            checkoutStream = stream;
        });
    });

    // Take check-out photo
    document.getElementById('takeCheckoutPhoto').addEventListener('click', function() {
        const video = document.getElementById('checkoutVideo');
        const canvas = document.getElementById('checkoutCanvas');
        const preview = document.getElementById('checkoutPreview');
        const context = canvas.getContext('2d');
        
        context.drawImage(video, 0, 0, 300, 225);
        const dataURL = canvas.toDataURL('image/jpeg');
        
        preview.src = dataURL;
        preview.style.display = 'block';
        video.style.display = 'none';
        
        document.getElementById('takeCheckoutPhoto').style.display = 'none';
        document.getElementById('retakeCheckoutPhoto').style.display = 'inline-block';
        document.getElementById('submitCheckout').disabled = false;
        
        // Stop camera stream
        if (checkoutStream) {
            stopCameraStream(checkoutStream);
            checkoutStream = null;
        }
    });

    // Retake check-out photo
    document.getElementById('retakeCheckoutPhoto').addEventListener('click', function() {
        document.getElementById('checkoutPreview').style.display = 'none';
        document.getElementById('retakeCheckoutPhoto').style.display = 'none';
        document.getElementById('startCheckoutCamera').style.display = 'inline-block';
        document.getElementById('submitCheckout').disabled = true;
    });

    // Submit check-in
    document.getElementById('submitCheckin').addEventListener('click', function() {
        const canvas = document.getElementById('checkinCanvas');
        
        canvas.toBlob(function(blob) {
            const formData = new FormData();
            formData.append('action', 'checkin');
            formData.append('shift_id', currentShiftId);
            formData.append('photo', blob, 'checkin.jpg');
            
            if (currentAction === 'late_checkin') {
                const reason = document.querySelector('#checkinLateReason textarea').value;
                if (reason.trim().length < 10) {
                    alert('Please provide a detailed reason for late check-in (minimum 10 characters)');
                    return;
                }
                formData.append('late_reason', reason);
            }
            
            // Disable button
            document.getElementById('submitCheckin').disabled = true;
            document.getElementById('submitCheckin').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../api/shift_checkin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh page to update status
                } else {
                    alert('Error: ' + data.error);
                    document.getElementById('submitCheckin').disabled = false;
                    document.getElementById('submitCheckin').innerHTML = '<i class="fas fa-check"></i> Confirm Check In';
                }
            })
            .catch(error => {
                alert('Error processing check-in');
                document.getElementById('submitCheckin').disabled = false;
                document.getElementById('submitCheckin').innerHTML = '<i class="fas fa-check"></i> Confirm Check In';
            });
        }, 'image/jpeg');
    });

    // Submit check-out
    document.getElementById('submitCheckout').addEventListener('click', function() {
        const canvas = document.getElementById('checkoutCanvas');
        
        canvas.toBlob(function(blob) {
            const formData = new FormData();
            formData.append('action', 'checkout');
            formData.append('shift_id', currentShiftId);
            formData.append('photo', blob, 'checkout.jpg');
            
            // Disable button
            document.getElementById('submitCheckout').disabled = true;
            document.getElementById('submitCheckout').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../api/shift_checkin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh page to update status
                } else {
                    alert('Error: ' + data.error);
                    document.getElementById('submitCheckout').disabled = false;
                    document.getElementById('submitCheckout').innerHTML = '<i class="fas fa-check"></i> Confirm Check Out';
                }
            })
            .catch(error => {
                alert('Error processing check-out');
                document.getElementById('submitCheckout').disabled = false;
                document.getElementById('submitCheckout').innerHTML = '<i class="fas fa-check"></i> Confirm Check Out';
            });
        }, 'image/jpeg');
    });

    // Clean up camera streams when modals are closed
    document.getElementById('checkinModal').addEventListener('hidden.bs.modal', function() {
        if (checkinStream) {
            stopCameraStream(checkinStream);
            checkinStream = null;
        }
    });

    document.getElementById('checkoutModal').addEventListener('hidden.bs.modal', function() {
        if (checkoutStream) {
            stopCameraStream(checkoutStream);
            checkoutStream = null;
        }
    });

    // Update countdown timers
    function updateCountdowns() {
        const countdownElements = document.querySelectorAll('.countdown-timer');
        countdownElements.forEach(element => {
            const target = parseInt(element.dataset.target);
            const now = Math.floor(Date.now() / 1000);
            const diff = target - now;
            
            if (diff > 0) {
                const minutes = Math.floor(diff / 60);
                const seconds = diff % 60;
                element.innerHTML = `<small class="text-info">Check-in available in ${minutes}m ${seconds}s</small>`;
            } else {
                element.innerHTML = '<small class="text-success">Check-in now available!</small>';
                setTimeout(() => location.reload(), 2000); // Refresh after 2 seconds
            }
        });
    }

    // Update countdowns every second
    setInterval(updateCountdowns, 1000);
    updateCountdowns(); // Initial call

    </script>

    <script>
    function acceptShift(shiftId) {
        if (confirm('Are you sure you want to confirm this shift?')) {
            document.getElementById('acceptShiftId').value = shiftId;
            document.getElementById('acceptShiftForm').submit();
        }
    }

    function declineShift(shiftId) {
        document.getElementById('declineShiftId').value = shiftId;
        new bootstrap.Modal(document.getElementById('declineShiftModal')).show();
    }

    function cancelOfficerShift(shiftId) {
        const reason = prompt('Please provide a reason for cancelling this shift:');
        if (reason && reason.trim().length >= 10) {
            // Create a form to submit the cancellation
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const idInput = document.createElement('input');
            idInput.name = 'id';
            idInput.value = shiftId;
            form.appendChild(idInput);
            
            const reasonInput = document.createElement('input');
            reasonInput.name = 'cancellation_reason';
            reasonInput.value = reason.trim();
            form.appendChild(reasonInput);
            
            document.body.appendChild(form);
            
            // Submit to the cancel API
            fetch('../api/cancel_shift.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Shift cancelled successfully');
                    window.location.reload();
                } else {
                    alert('Error cancelling shift: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling shift');
            });
        } else if (reason !== null) {
            alert('Please provide a detailed reason (minimum 10 characters)');
        }
    }
    </script>
</body>
</html>
