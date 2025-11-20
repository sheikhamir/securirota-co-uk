<?php
/**
 * Country Helper Functions
 * Provides centralized country and nationality management
 */

/**
 * Get all countries ordered alphabetically by name
 * @param PDO $conn Database connection
 * @param bool $activeOnly Only return active countries
 * @return array Array of countries
 */
function getCountries($conn, $activeOnly = true) {
    $sql = "SELECT id, name, code FROM countries";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get countries for nationality dropdown
 * @param PDO $conn Database connection
 * @return array Array of countries suitable for nationality selection
 */
function getNationalityOptions($conn) {
    return getCountries($conn, true);
}

/**
 * Get countries for visa status dropdown (simplified list)
 * @param PDO $conn Database connection
 * @return array Array of visa status options
 */
function getVisaStatusOptions($conn) {
    // Get key countries/regions for visa status
    $sql = "SELECT id, name, code FROM countries 
            WHERE name IN ('British', 'European Union', 'United Kingdom', 'Ireland') 
               OR sort_order <= 5
            ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $visaOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add standard visa types as array elements (not from database)
    $standardVisaTypes = [
        ['name' => 'Dependent Visa', 'code' => 'DEP'],
        ['name' => 'EU Citizen', 'code' => 'EU'],
        ['name' => 'Other Visa', 'code' => 'OTH'],
        ['name' => 'Student Visa', 'code' => 'STU'],
        ['name' => 'Work Visa', 'code' => 'WRK']
    ];
    
    // Merge and sort all options alphabetically by name
    $allOptions = array_merge($visaOptions, $standardVisaTypes);
    usort($allOptions, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $allOptions;
}

/**
 * Get a country by ID
 * @param PDO $conn Database connection
 * @param int $id Country ID
 * @return array|false Country data or false if not found
 */
function getCountryById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM countries WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get a country by name (case-insensitive)
 * @param PDO $conn Database connection
 * @param string $name Country name
 * @return array|false Country data or false if not found
 */
function getCountryByName($conn, $name) {
    $stmt = $conn->prepare("SELECT * FROM countries WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Migrate existing nationality data to use country IDs
 * @param PDO $conn Database connection
 * @return array Migration results
 */
function migrateNationalityData($conn) {
    $results = ['updated' => 0, 'errors' => []];
    
    try {
        // Get all officers with nationality data
        $stmt = $conn->prepare("SELECT id, nationality FROM officers WHERE nationality IS NOT NULL AND nationality != ''");
        $stmt->execute();
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($officers as $officer) {
            $countryName = $officer['nationality'];
            
            // Handle special cases for backward compatibility
            $mapping = [
                'British' => 'United Kingdom',
                'European' => 'European Union',
                'Commonwealth' => 'Commonwealth'
            ];
            
            if (isset($mapping[$countryName])) {
                $countryName = $mapping[$countryName];
            }
            
            // Find matching country
            $country = getCountryByName($conn, $countryName);
            
            if ($country) {
                // Update officer record - for now we keep the text field but this helps validate data
                $results['updated']++;
            } else {
                $results['errors'][] = "Could not find country for nationality: " . $officer['nationality'] . " (Officer ID: " . $officer['id'] . ")";
            }
        }
        
    } catch (Exception $e) {
        $results['errors'][] = "Migration error: " . $e->getMessage();
    }
    
    return $results;
}

/**
 * Generate nationality dropdown HTML
 * @param PDO $conn Database connection
 * @param string $selectedValue Currently selected nationality
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param array $attributes Additional HTML attributes
 * @return string HTML dropdown
 */
function generateNationalityDropdown($conn, $selectedValue = '', $name = 'nationality', $id = 'nationality', $attributes = []) {
    $countries = getNationalityOptions($conn);
    
    $attributeString = '';
    foreach ($attributes as $attr => $value) {
        $attributeString .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
    }
    
    $html = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '"' . $attributeString . '>';
    
    foreach ($countries as $country) {
        $selected = ($country['name'] === $selectedValue) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($country['name']) . '"' . $selected . '>' . htmlspecialchars($country['name']) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Generate visa status dropdown HTML  
 * @param PDO $conn Database connection
 * @param string $selectedValue Currently selected visa status
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param array $attributes Additional HTML attributes
 * @return string HTML dropdown
 */
function generateVisaStatusDropdown($conn, $selectedValue = '', $name = 'visa_status', $id = 'visa_status', $attributes = []) {
    $visaOptions = getVisaStatusOptions($conn);
    
    $attributeString = '';
    foreach ($attributes as $attr => $value) {
        $attributeString .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
    }
    
    $html = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '"' . $attributeString . '>';
    
    foreach ($visaOptions as $option) {
        $value = $option['name'];
        $selected = ($value === $selectedValue) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($value) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}
?>
