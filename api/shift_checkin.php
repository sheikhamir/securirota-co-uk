<?php
// API for shift check-in/out with photo verification
header('Content-Type: application/json');

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an officer
if (!isLoggedIn() || !hasRole('officer')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get officer information
    $stmt = $conn->prepare("SELECT id FROM officers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$officer) {
        throw new Exception("Officer profile not found.");
    }
    
    $action = $_POST['action'] ?? '';
    $shift_id = $_POST['shift_id'] ?? '';
    
    if (empty($action) || empty($shift_id)) {
        throw new Exception("Action and shift ID are required.");
    }
    
    // Verify shift belongs to this officer and get current status
    $stmt = $conn->prepare("
        SELECT * FROM shifts 
        WHERE id = ? AND officer_id = ? AND status IN ('confirmed', 'in_progress')
    ");
    $stmt->execute([$shift_id, $officer['id']]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shift) {
        throw new Exception("Shift not found or not authorized.");
    }
    
    // Additional validation based on action
    if ($action === 'checkin' && $shift['status'] !== 'confirmed') {
        throw new Exception("Only confirmed shifts can be checked in.");
    }
    
    if ($action === 'checkout' && $shift['status'] !== 'in_progress') {
        throw new Exception("Shift must be in progress (checked in) to check out.");
    }
    
    if ($action === 'checkin') {
        // Check if shift is for today
        if ($shift['shift_date'] !== date('Y-m-d')) {
            throw new Exception("You can only check in for today's shifts.");
        }
        
        // Check time windows for check-in
        $current_time = time();
        $shift_start = strtotime($shift['shift_date'] . ' ' . $shift['start_time']);
        $checkin_window_start = $shift_start - (15 * 60); // 15 minutes before
        $checkin_window_end = $shift_start + (15 * 60);   // 15 minutes after
        
        $is_late_checkin = $current_time > $checkin_window_end;
        $is_too_early = $current_time < $checkin_window_start;
        
        if ($is_too_early) {
            $minutes_until = ceil(($checkin_window_start - $current_time) / 60);
            throw new Exception("Check-in window opens in {$minutes_until} minutes. You can check in from " . date('H:i', $checkin_window_start) . ".");
        }
        
        // Handle late check-in reason
        $late_reason = null;
        if ($is_late_checkin) {
            $late_reason = $_POST['late_reason'] ?? '';
            if (empty($late_reason) || strlen(trim($late_reason)) < 10) {
                throw new Exception("Late check-in requires a detailed reason (minimum 10 characters).");
            }
        }
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/checkin_photos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Only JPG, JPEG, and PNG files are allowed.");
            }
            
            $file_name = 'checkin_' . $shift_id . '_' . time() . '.' . $file_extension;
            $photo_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                throw new Exception("Failed to upload photo.");
            }
            
            // Store relative path for database
            $photo_path = 'uploads/checkin_photos/' . $file_name;
        } else {
            throw new Exception("Check-in photo is mandatory.");
        }
        
        // Update shift with check-in details and set status to in_progress
        $stmt = $conn->prepare("
            UPDATE shifts 
            SET checkin_image = ?, checkin_timestamp = NOW(),
                status = 'in_progress',
                notes = CONCAT(COALESCE(notes, ''), ?, 'Checked in at: ', NOW(), CHAR(10))
            WHERE id = ?
        ");
        
        $notes_addition = $is_late_checkin ? "Late check-in reason: {$late_reason}" . PHP_EOL : '';
        $stmt->execute([$photo_path, $notes_addition, $shift_id]);
        
        $message = $is_late_checkin ? 'Late check-in completed successfully!' : 'Checked in successfully!';
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'checkin_time' => date('H:i'),
            'is_late' => $is_late_checkin
        ]);
        
    } elseif ($action === 'checkout') {
        // Check if already checked in
        if (empty($shift['checkin_timestamp'])) {
            throw new Exception("You must check in first before checking out.");
        }
        
        // Time validation for checkout
        $current_time = time();
        $shift_end = strtotime($shift['shift_date'] . ' ' . $shift['end_time']);
        $checkout_window_start = $shift_end - (15 * 60); // 15 minutes before end
        
        $is_early_checkout = $current_time < $checkout_window_start;
        $is_overtime = $current_time > $shift_end;
        
        // Calculate worked hours
        $checkin_time = strtotime($shift['checkin_timestamp']);
        $worked_hours = round(($current_time - $checkin_time) / 3600, 2);
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/checkout_photos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Only JPG, JPEG, and PNG files are allowed.");
            }
            
            $file_name = 'checkout_' . $shift_id . '_' . time() . '.' . $file_extension;
            $photo_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                throw new Exception("Failed to upload photo.");
            }
            
            // Store relative path for database
            $photo_path = 'uploads/checkout_photos/' . $file_name;
        } else {
            throw new Exception("Check-out photo is mandatory.");
        }
        
        // Update shift with check-out details and mark as completed
        $notes_addition = '';
        if ($is_early_checkout) {
            $notes_addition = "Early checkout (before scheduled end time). ";
        } elseif ($is_overtime) {
            $overtime_minutes = round(($current_time - $shift_end) / 60);
            $notes_addition = "Overtime checkout ({$overtime_minutes} minutes past scheduled end). ";
        }
        $notes_addition .= "Worked hours: {$worked_hours}h. Checked out at: " . date('Y-m-d H:i:s') . PHP_EOL;
        
        $stmt = $conn->prepare("
            UPDATE shifts 
            SET checkout_image = ?, checkout_timestamp = NOW(), status = 'completed',
                notes = CONCAT(COALESCE(notes, ''), ?)
            WHERE id = ?
        ");
        $stmt->execute([$photo_path, $notes_addition, $shift_id]);
        
        $message = 'Checked out successfully! Shift completed.';
        if ($is_overtime) {
            $message .= ' Overtime recorded.';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'checkout_time' => date('H:i'),
            'worked_hours' => $worked_hours,
            'is_overtime' => $is_overtime
        ]);
        
    } else {
        throw new Exception("Invalid action.");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
