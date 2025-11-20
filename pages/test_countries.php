<?php
/**
 * Test script for countries functionality
 */
require_once '../config/database.php';
require_once '../includes/country_helper.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Countries Table Test</h2>";
    
    // Test 1: Get all countries count
    $countries = getNationalityOptions($conn);
    echo "<h3>Test 1: Total Countries Available</h3>";
    echo "<p>Total countries loaded: " . count($countries) . "</p>";
    
    // Test 2: Show first 10 countries (priority ones)
    echo "<h3>Test 2: First 10 Priority Countries</h3>";
    echo "<ul>";
    for ($i = 0; $i < min(10, count($countries)); $i++) {
        echo "<li>" . htmlspecialchars($countries[$i]['name']) . " (" . htmlspecialchars($countries[$i]['code']) . ")</li>";
    }
    echo "</ul>";
    
    // Test 3: Check if Pakistan is in the list
    echo "<h3>Test 3: Pakistan Availability</h3>";
    $pakistanFound = false;
    foreach ($countries as $country) {
        if ($country['name'] === 'Pakistan') {
            $pakistanFound = true;
            echo "<p style='color: green;'>✓ Pakistan found in countries list (Code: " . htmlspecialchars($country['code']) . ")</p>";
            break;
        }
    }
    if (!$pakistanFound) {
        echo "<p style='color: red;'>✗ Pakistan NOT found in countries list</p>";
    }
    
    // Test 4: Test visa status options
    $visaOptions = getVisaStatusOptions($conn);
    echo "<h3>Test 4: Visa Status Options</h3>";
    echo "<p>Total visa options: " . count($visaOptions) . "</p>";
    echo "<ul>";
    foreach ($visaOptions as $option) {
        echo "<li>" . htmlspecialchars($option['name']) . " (" . htmlspecialchars($option['code']) . ")</li>";
    }
    echo "</ul>";
    
    // Test 5: Test country lookup
    echo "<h3>Test 5: Country Lookup Tests</h3>";
    $testCountries = ['Pakistan', 'British', 'United Kingdom', 'India', 'Nigeria'];
    foreach ($testCountries as $testCountry) {
        $country = getCountryByName($conn, $testCountry);
        if ($country) {
            echo "<p style='color: green;'>✓ Found: " . htmlspecialchars($testCountry) . " (ID: " . $country['id'] . ", Code: " . htmlspecialchars($country['code']) . ")</p>";
        } else {
            echo "<p style='color: red;'>✗ Not found: " . htmlspecialchars($testCountry) . "</p>";
        }
    }
    
    echo "<h3>Test Complete</h3>";
    echo "<p><a href='officer_form.php'>Test Officer Form</a> | <a href='officers.php'>View Officers List</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p>Make sure the countries table has been created and populated.</p>";
}
?>
