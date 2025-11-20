<?php
require_once 'config/config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Debugging Shift Time Calculations</h2>";
    
    // Check a few shifts with their time calculations
    $sql = "
        SELECT 
            o.first_name, o.last_name,
            s.shift_date,
            s.start_time,
            s.end_time,
            TIMEDIFF(s.end_time, s.start_time) as time_diff,
            TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) as time_seconds,
            TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600 as hours,
            s.officer_rate,
            (TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.officer_rate as calculated_pay
        FROM shifts s
        LEFT JOIN officers o ON s.officer_id = o.id
        WHERE s.officer_id IS NOT NULL
        LIMIT 10
    ";
    
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Officer</th><th>Date</th><th>Start</th><th>End</th><th>Time Diff</th><th>Seconds</th><th>Hours</th><th>Rate</th><th>Pay</th></tr>";
    
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['shift_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['time_diff']) . "</td>";
        echo "<td>" . htmlspecialchars($row['time_seconds']) . "</td>";
        echo "<td>" . htmlspecialchars($row['hours']) . "</td>";
        echo "<td>£" . htmlspecialchars($row['officer_rate']) . "</td>";
        echo "<td>£" . htmlspecialchars($row['calculated_pay']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Also check the summary for one officer
    echo "<h3>Summary for Ahtsham Shabbir (officer_id = ?)</h3>";
    
    $sql2 = "
        SELECT 
            o.id as officer_id,
            CONCAT(o.first_name, ' ', o.last_name) as officer_name,
            COUNT(s.id) as total_shifts,
            SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) as total_hours,
            AVG(s.officer_rate) as avg_rate,
            SUM((TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) * s.officer_rate) as total_pay
        FROM officers o
        LEFT JOIN shifts s ON o.id = s.officer_id 
            AND s.shift_date BETWEEN '2025-10-01' AND '2025-10-31'
            AND s.status IN ('confirmed', 'completed')
        WHERE o.first_name = 'Ahtsham' AND o.last_name = 'Shabbir'
        GROUP BY o.id
    ";
    
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute();
    $summary = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($summary) {
        echo "<p><strong>Officer:</strong> " . htmlspecialchars($summary['officer_name']) . "</p>";
        echo "<p><strong>Total Shifts:</strong> " . htmlspecialchars($summary['total_shifts']) . "</p>";
        echo "<p><strong>Total Hours:</strong> " . htmlspecialchars($summary['total_hours']) . "</p>";
        echo "<p><strong>Average Rate:</strong> £" . htmlspecialchars($summary['avg_rate']) . "</p>";
        echo "<p><strong>Total Pay:</strong> £" . htmlspecialchars($summary['total_pay']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>