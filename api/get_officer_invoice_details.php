<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $officer_id = $_GET['officer_id'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    if (!$officer_id || !$start_date || !$end_date) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    // Get detailed shift information
    $sql = "
        SELECT 
            s.shift_date,
            s.start_time,
            s.end_time,
            s.officer_rate,
            si.site_name,
            c.company_name as client_name,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600
                ELSE TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600
            END as hours,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN (TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600) * s.officer_rate
                ELSE (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.officer_rate
            END as pay
        FROM shifts s
        JOIN sites si ON s.site_id = si.id
        JOIN clients c ON si.client_id = c.id
        WHERE s.officer_id = ?
        AND s.shift_date BETWEEN ? AND ?
        AND s.status IN ('confirmed', 'completed')
        ORDER BY s.shift_date, s.start_time
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$officer_id, $start_date, $end_date]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_hours = 0;
    $total_pay = 0;
    $total_shifts = count($shifts);

    foreach ($shifts as &$shift) {
        $shift['hours'] = round($shift['hours'], 2);
        $shift['pay'] = round($shift['pay'], 2);
        $total_hours += $shift['hours'];
        $total_pay += $shift['pay'];
    }

    echo json_encode([
        'success' => true,
        'shifts' => $shifts,
        'total_hours' => round($total_hours, 2),
        'total_pay' => round($total_pay, 2),
        'total_shifts' => $total_shifts
    ]);

} catch (Exception $e) {
    error_log("GET_OFFICER_INVOICE_DETAILS ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>