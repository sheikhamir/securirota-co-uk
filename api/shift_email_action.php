<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/email_helper.php';

$db = new Database();
$conn = $db->getConnection();

function renderShiftEmailActionPage($title, $message, $type = 'info', $extra_html = '') {
    $alert_class = $type === 'success' ? 'alert-success' : ($type === 'danger' ? 'alert-danger' : 'alert-info');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">' . htmlspecialchars($title) . '</h1>
                        <div class="alert ' . $alert_class . '">' . htmlspecialchars($message) . '</div>
                        ' . $extra_html . '
                        <a class="btn btn-primary mt-3" href="' . htmlspecialchars(BASE_URL . 'login.php') . '">Open Officer Portal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit();
}

function insertShiftEmailActivity($conn, $shift_id, $officer_id, $activity_type, $reason = null) {
    try {
        if ($reason !== null) {
            $stmt = $conn->prepare("
                INSERT INTO shift_activities (shift_id, officer_id, activity_type, reason, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$shift_id, $officer_id, $activity_type, $reason]);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO shift_activities (shift_id, officer_id, activity_type, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$shift_id, $officer_id, $activity_type]);
    } catch (Exception $e) {
        error_log("Email shift activity logging failed: " . $e->getMessage());
        return false;
    }
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    $payload = verifyShiftEmailActionToken($token, $action);
    
    if (!$payload) {
        renderShiftEmailActionPage('Shift Response', 'This shift response link is invalid or has expired.', 'danger');
    }
    
    $shift_id = (int)$payload['shift_id'];
    $officer_id = (int)$payload['officer_id'];
    
    if ($action === 'accept') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $form = buildAcceptShiftForm($token);
            renderShiftEmailActionPage('Confirm Shift', 'Please confirm that you want to accept this shift.', 'info', $form);
        }
        
        $stmt = $conn->prepare("
            UPDATE shifts
            SET status = 'confirmed'
            WHERE id = ? AND officer_id = ? AND status = 'allocated'
        ");
        $stmt->execute([$shift_id, $officer_id]);
        
        if ($stmt->rowCount() > 0) {
            insertShiftEmailActivity($conn, $shift_id, $officer_id, 'accepted');
            renderShiftEmailActionPage('Shift Confirmed', 'Thank you. Your shift has been confirmed successfully.', 'success');
        }
        
        renderShiftEmailActionPage('Shift Response', 'This shift could not be confirmed. It may already have been updated, declined, cancelled, or moved.', 'danger');
    }
    
    if ($action === 'decline') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = trim($_POST['decline_reason'] ?? '');
            
            if ($reason === '') {
                $form = buildDeclineShiftForm($token, 'Please provide a reason for declining this shift.');
                renderShiftEmailActionPage('Decline Shift', 'A decline reason is required.', 'danger', $form);
            }
            
            $stmt = $conn->prepare("
                UPDATE shifts
                SET status = 'declined', decline_reason = ?
                WHERE id = ? AND officer_id = ? AND status = 'allocated'
            ");
            $stmt->execute([$reason, $shift_id, $officer_id]);
            
            if ($stmt->rowCount() > 0) {
                insertShiftEmailActivity($conn, $shift_id, $officer_id, 'declined', $reason);
                renderShiftEmailActionPage('Shift Declined', 'Thank you. Your shift has been declined and your reason has been recorded.', 'success');
            }
            
            renderShiftEmailActionPage('Shift Response', 'This shift could not be declined. It may already have been updated, confirmed, cancelled, or moved.', 'danger');
        }
        
        $form = buildDeclineShiftForm($token);
        renderShiftEmailActionPage('Decline Shift', 'Please provide a reason before declining this shift.', 'info', $form);
    }
    
    renderShiftEmailActionPage('Shift Response', 'Unsupported shift response action.', 'danger');
} catch (Exception $e) {
    error_log("Shift email action failed: " . $e->getMessage());
    renderShiftEmailActionPage('Shift Response', 'Unable to process this shift response right now.', 'danger');
}

function buildDeclineShiftForm($token, $error = '') {
    $error_html = $error ? '<div class="text-danger small mb-2">' . htmlspecialchars($error) . '</div>' : '';
    
    return '
        <form method="POST" class="mt-3">
            <input type="hidden" name="action" value="decline">
            <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
            <div class="mb-3">
                <label class="form-label" for="decline_reason">Reason for declining</label>
                ' . $error_html . '
                <textarea class="form-control" id="decline_reason" name="decline_reason" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Decline Shift</button>
        </form>';
}

function buildAcceptShiftForm($token) {
    return '
        <form method="POST" class="mt-3">
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
            <button type="submit" class="btn btn-success">Confirm Shift</button>
        </form>';
}
?>
