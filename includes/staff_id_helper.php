<?php
/**
 * Company-specific Staff ID Generator
 * This helper function generates company-specific staff IDs for officers
 */

function generateCompanySpecificStaffId($conn, $company_id) {
    try {
        // Get the maximum staff_id for this company
        $stmt = $conn->prepare("
            SELECT staff_id 
            FROM officers 
            WHERE company_id = ? AND staff_id IS NOT NULL 
            ORDER BY CAST(REGEXP_REPLACE(staff_id, '[^0-9]', '') AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute([$company_id]);
        $last_staff_id = $stmt->fetchColumn();
        
        if ($last_staff_id) {
            // Extract the numeric part and increment
            $numeric_part = (int) preg_replace('/[^0-9]/', '', $last_staff_id);
            $next_number = $numeric_part + 1;
        } else {
            // This is the first officer for this company, start from 1
            $next_number = 1;
        }
        
        // Format based on company_id
        switch ($company_id) {
            case 1:
                // FRK company - keep existing format with 10000 series
                return sprintf('%d', 10000 + $next_number);
                
            case 4:
                // Vestra company - use V prefix
                return sprintf('V%03d', $next_number);
                
            default:
                // Other companies - use company prefix
                return sprintf('C%d%03d', $company_id, $next_number);
        }
        
    } catch (Exception $e) {
        // Fallback: use timestamp-based ID
        return 'EMP' . time();
    }
}

function resetStaffIdsForCompany($conn, $company_id) {
    try {
        // Get all officers for this company ordered by creation date
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name 
            FROM officers 
            WHERE company_id = ? 
            ORDER BY id ASC
        ");
        $stmt->execute([$company_id]);
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counter = 1;
        foreach ($officers as $officer) {
            // Generate new staff_id
            switch ($company_id) {
                case 1:
                    // FRK company - keep existing format but company-specific
                    $new_staff_id = sprintf('%d', 10000 + $counter);
                    break;
                    
                case 4:
                    // Vestra company - use V prefix
                    $new_staff_id = sprintf('V%03d', $counter);
                    break;
                    
                default:
                    // Other companies - use company prefix
                    $new_staff_id = sprintf('C%d%03d', $company_id, $counter);
                    break;
            }
            
            // Update the officer
            $update_stmt = $conn->prepare("UPDATE officers SET staff_id = ? WHERE id = ?");
            $update_stmt->execute([$new_staff_id, $officer['id']]);
            
            $counter++;
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>