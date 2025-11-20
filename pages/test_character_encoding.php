<?php
/**
 * Character encoding test for countries
 */
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Character Encoding Test</h2>";
    
    // Test UTF-8 characters in countries
    $stmt = $conn->prepare("SELECT name, code FROM countries WHERE name LIKE '%ã%' OR name LIKE '%ô%' OR name LIKE '%é%' OR name LIKE '%í%' ORDER BY name");
    $stmt->execute();
    $countries_with_accents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Countries with Special Characters:</h3>";
    if (count($countries_with_accents) > 0) {
        echo "<ul>";
        foreach ($countries_with_accents as $country) {
            echo "<li><strong>" . htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') . "</strong> (" . htmlspecialchars($country['code']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No countries with special characters found.</p>";
    }
    
    // Test specific problematic countries
    echo "<h3>Specific Test Cases:</h3>";
    $test_countries = ['São Tomé and Príncipe', 'Côte d\'Ivoire'];
    
    foreach ($test_countries as $test_name) {
        $stmt = $conn->prepare("SELECT name, code FROM countries WHERE name = ?");
        $stmt->execute([$test_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Found: <strong>" . htmlspecialchars($result['name'], ENT_QUOTES, 'UTF-8') . "</strong> (" . htmlspecialchars($result['code']) . ")</p>";
        } else {
            echo "<p style='color: red;'>✗ Not found: " . htmlspecialchars($test_name, ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }
    
    echo "<h3>Database Character Set Info:</h3>";
    $stmt = $conn->prepare("SHOW TABLE STATUS LIKE 'countries'");
    $stmt->execute();
    $table_info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Table Collation: <strong>" . htmlspecialchars($table_info['Collation']) . "</strong></p>";
    
    echo "<p><a href='officer_form.php'>Test in Officer Form</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
