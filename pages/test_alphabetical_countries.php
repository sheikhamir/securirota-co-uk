<?php
/**
 * Test alphabetical ordering of countries
 */
require_once '../config/database.php';
require_once '../includes/country_helper.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Alphabetical Country Ordering Test</h2>";
    
    // Test 1: Show all countries in alphabetical order
    $countries = getNationalityOptions($conn);
    echo "<h3>All Countries (Alphabetical Order)</h3>";
    echo "<p>Total countries: " . count($countries) . "</p>";
    
    echo "<div style='columns: 3; column-gap: 20px;'>";
    echo "<ol>";
    foreach ($countries as $country) {
        // Highlight important countries for visibility
        $highlight = '';
        if (in_array($country['name'], ['Afghanistan', 'Bangladesh', 'British', 'India', 'Pakistan', 'United Kingdom'])) {
            $highlight = ' style="font-weight: bold; color: #007bff;"';
        }
        echo "<li" . $highlight . ">" . htmlspecialchars($country['name']) . " (" . htmlspecialchars($country['code']) . ")</li>";
    }
    echo "</ol>";
    echo "</div>";
    
    // Test 2: Show visa status options in alphabetical order
    $visaOptions = getVisaStatusOptions($conn);
    echo "<h3>Visa Status Options (Alphabetical Order)</h3>";
    echo "<ol>";
    foreach ($visaOptions as $option) {
        echo "<li>" . htmlspecialchars($option['name']) . " (" . htmlspecialchars($option['code']) . ")</li>";
    }
    echo "</ol>";
    
    // Test 3: Check specific important countries positions
    echo "<h3>Important Countries Positions</h3>";
    $importantCountries = ['Afghanistan', 'Bangladesh', 'British', 'India', 'Pakistan', 'United Kingdom'];
    foreach ($importantCountries as $countryName) {
        $position = 1;
        foreach ($countries as $country) {
            if ($country['name'] === $countryName) {
                echo "<p><strong>" . htmlspecialchars($countryName) . "</strong> - Position: #" . $position . "</p>";
                break;
            }
            $position++;
        }
    }
    
    echo "<p><a href='officer_form.php'>Test in Officer Form</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
