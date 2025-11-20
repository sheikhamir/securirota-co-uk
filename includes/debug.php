<?php
// Debug test file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing includes...\n";

try {
    echo "1. Including config...\n";
    require_once dirname(__DIR__) . '/config/config.php';
    echo "2. Config included successfully\n";
    
    echo "3. Including database...\n";
    require_once dirname(__DIR__) . '/config/database.php';
    echo "4. Database included successfully\n";
    
    echo "5. Testing Database class...\n";
    $db = new Database();
    echo "6. Database object created\n";
    
    echo "7. Testing connection...\n";
    $conn = $db->getConnection();
    if ($conn) {
        echo "8. Database connection successful!\n";
    } else {
        echo "8. Database connection failed\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
