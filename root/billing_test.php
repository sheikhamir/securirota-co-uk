<?php
session_start();
require_once '../config/config.php';

if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Billing Test</title>
    <style>
        body { background: #1a1a1a; color: white; padding: 20px; }
        .test-content { background: red; padding: 20px; margin: 20px; }
    </style>
</head>
<body>
    <h1>BILLING MANAGEMENT TEST</h1>
    <div class='test-content'>
        <p>This is a test to see if content is visible</p>
        <p>If you can see this red box, the page is working</p>
    </div>
";

try {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM companies");
    $count = $stmt->fetch()['count'];
    echo "<p>Companies in database: $count</p>";
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>