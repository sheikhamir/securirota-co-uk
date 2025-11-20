<?php
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

echo "<h1>Billing Management Debug</h1>";
echo "<p>User authenticated successfully</p>";

try {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test if companies table exists
    $stmt = $conn->query("SELECT COUNT(*) as count FROM companies");
    $count = $stmt->fetch()['count'];
    echo "<p>✅ Companies table exists with {$count} records</p>";
    
    // Test if billing_history table exists
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'billing_history'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ billing_history table exists</p>";
            $stmt = $conn->query("SELECT COUNT(*) as count FROM billing_history");
            $count = $stmt->fetch()['count'];
            echo "<p>✅ billing_history has {$count} records</p>";
        } else {
            echo "<p>❌ billing_history table does NOT exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking billing_history: " . $e->getMessage() . "</p>";
    }
    
    // Test if subscription_tiers table exists
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM subscription_tiers");
        $count = $stmt->fetch()['count'];
        echo "<p>✅ subscription_tiers table exists with {$count} records</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error with subscription_tiers: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>