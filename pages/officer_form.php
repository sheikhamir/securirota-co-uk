<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Officer Form';
require_once '../includes/header.php';
require_once '../includes/email_helper.php';
require_once '../includes/country_helper.php';
require_once '../includes/document_uploader.php';
require_once '../includes/staff_id_helper.php';

$officer_id = $_GET['id'] ?? null;
$is_edit = !empty($officer_id);
$officer = [];
$existing_documents = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle form submissions
    if ($_POST && isset($_POST['action'])) {
        if ($_POST['action'] === 'save') {
            if ($is_edit) {
                // Update existing officer
                // Check for unique SIA badge number (excluding current officer)
                if (!empty($_POST['sia_badge_number'])) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM officers WHERE sia_badge_number = ? AND id != ?");
                    $stmt->execute([$_POST['sia_badge_number'], $officer_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('SIA Badge number must be unique.');
                    }
                }
                
                $stmt = $conn->prepare("
                    UPDATE officers SET
                        first_name = ?, last_name = ?, email = ?, phone = ?, 
                        address = ?, address_city = ?, address_postal_code = ?,
                        sia_badge_number = ?, sia_expiry_date = ?, visa_status = ?, visa_expiry_date = ?,
                        employment_status = ?, hourly_rate = ?, 
                        bank_account = ?, sort_code = ?, bank_account_name = ?, bank_roll_number = ?,
                        emergency_contact_name = ?, emergency_contact_phone = ?, notes = ?,
                        date_of_birth = ?, national_insurance = ?, nationality = ?,
                        right_to_work_reference = ?, date_started = ?, date_left = ?, 
                        subcontractor_id = ?, suspend = ?, share_code = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'],
                    $_POST['address'], $_POST['address_city'], $_POST['address_postal_code'],
                    $_POST['sia_badge_number'], 
                    $_POST['sia_expiry_date'] ?: null, $_POST['visa_status'],
                    $_POST['visa_expiry_date'] ?: null, $_POST['employment_status'],
                    $_POST['hourly_rate'], 
                    $_POST['bank_account'], $_POST['sort_code'], $_POST['bank_account_name'], $_POST['bank_roll_number'],
                    $_POST['emergency_contact_name'], $_POST['emergency_contact_phone'],
                    $_POST['notes'],
                    $_POST['date_of_birth'] ?: null,
                    $_POST['national_insurance'],
                    $_POST['nationality'],
                    $_POST['right_to_work_reference'],
                    $_POST['date_started'] ?: null,
                    $_POST['date_left'] ?: null,
                    $_POST['subcontractor_id'] ?: null,
                    isset($_POST['suspend']) ? 1 : 0,
                    $_POST['share_code'] ?: null,
                    $officer_id
                ]);
                
                // Update the linked user's email and mobile if they exist
                if (!empty($_POST['email']) || !empty($_POST['mobile_number'])) {
                    $mobile_number_clean = str_replace(' ', '', trim($_POST['mobile_number']));
                    $stmt = $conn->prepare("
                        UPDATE users u 
                        JOIN officers o ON u.id = o.user_id 
                        SET u.email = ?, u.mobile_number = ?, u.username = ?
                        WHERE o.id = ?
                    ");
                    $stmt->execute([$_POST['email'], $mobile_number_clean, $mobile_number_clean, $officer_id]);
                }
                
                // Handle file uploads for existing officer
                $uploader = new DocumentUploader();
                $uploadResults = [];
                
                $documentTypes = [
                    'passport' => 'Passport',
                    'sia_badge_front' => 'SIA Badge Front', 
                    'sia_badge_back' => 'SIA Badge Back',
                    'full_body_photo' => 'Full Body Photo',
                    'proof_of_address_1' => 'Proof of Address 1',
                    'proof_of_address_2' => 'Proof of Address 2',
                    'brp_card' => 'BRP Card',
                    'visa_share_code_screenshot' => 'Visa Share Code Screenshot'
                ];
                
                foreach ($documentTypes as $type => $label) {
                    if (isset($_FILES[$type]) && $_FILES[$type]['error'] !== UPLOAD_ERR_NO_FILE) {
                        try {
                            // Delete old file if exists
                            $oldStmt = $conn->prepare("SELECT file_path FROM documents WHERE officer_id = ? AND document_type = ?");
                            $oldStmt->execute([$officer_id, $type]);
                            $oldFile = $oldStmt->fetch(PDO::FETCH_COLUMN);
                            if ($oldFile) {
                                $uploader->deleteOldFile($oldFile);
                            }
                            
                            $filePath = $uploader->uploadFile($_FILES[$type], $officer_id, $type);
                            $uploader->saveDocumentToDatabase($conn, $officer_id, $type, $filePath, $_FILES[$type]['name']);
                            $uploadResults[] = "$label updated successfully";
                        } catch (Exception $e) {
                            $uploadResults[] = "$label upload failed: " . $e->getMessage();
                        }
                    }
                }
                
                $success = "Officer updated successfully!";
                if (!empty($uploadResults)) {
                    $success .= "<br>" . implode("<br>", $uploadResults);
                }
            } else {
                // Add new officer
                $conn->beginTransaction();
                
                try {
                    // Validate mobile number and create PIN
                    $mobile_number = str_replace(' ', '', trim($_POST['mobile_number']));
                    $email = trim($_POST['email']);
                    
                    if (empty($mobile_number) || empty($email)) {
                        throw new Exception('Mobile number and email are required.');
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Please enter a valid email address.');
                    }
                    
                    // Check if mobile number already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE mobile_number = ? OR email = ?");
                    $stmt->execute([$mobile_number, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Mobile number or email already exists.');
                    }
                    
                    // Generate PIN
                    $pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Get the current admin's company_id
                    $current_company_id = getCurrentCompanyId();
                    
                    // Create user account
                    $hashed_password = password_hash($pin, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, mobile_number, pin, pin_generated_at, role, status, company_id) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 'officer', 'active', ?)
                    ");
                    $stmt->execute([$mobile_number, $email, $hashed_password, $mobile_number, $pin, $current_company_id]);
                    $user_id = $conn->lastInsertId();
                    
                    // Check for unique SIA badge number
                    if (!empty($_POST['sia_badge_number'])) {
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM officers WHERE sia_badge_number = ?");
                        $stmt->execute([$_POST['sia_badge_number']]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception('SIA Badge number must be unique.');
                        }
                    }
                    
                    // Generate company-specific staff_id
                    $staff_id = generateNextStaffId($conn, $current_company_id);
                    
                    // Create officer record
                    $stmt = $conn->prepare("
                        INSERT INTO officers (
                            user_id, company_id, staff_id, first_name, last_name, email, phone, 
                            address, address_city, address_postal_code,
                            sia_badge_number, sia_expiry_date, visa_status, visa_expiry_date,
                            employment_status, hourly_rate, 
                            bank_account, sort_code, bank_account_name, bank_roll_number,
                            emergency_contact_name, emergency_contact_phone, notes,
                            date_of_birth, national_insurance, nationality, 
                            right_to_work_reference, share_code, date_started, date_left, subcontractor_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $user_id, 
                        $current_company_id,  // Add company_id here
                        $staff_id,            // Add generated staff_id here
                        $_POST['first_name'], 
                        $_POST['last_name'], 
                        $email, 
                        $_POST['phone'],
                        $_POST['address'], 
                        $_POST['address_city'], 
                        $_POST['address_postal_code'],
                        $_POST['sia_badge_number'], 
                        $_POST['sia_expiry_date'] ?: null, 
                        $_POST['visa_status'],
                        $_POST['visa_expiry_date'] ?: null, 
                        $_POST['employment_status'],
                        $_POST['hourly_rate'], 
                        $_POST['bank_account'], 
                        $_POST['sort_code'],
                        $_POST['bank_account_name'],
                        $_POST['bank_roll_number'],
                        $_POST['emergency_contact_name'], 
                        $_POST['emergency_contact_phone'],
                        $_POST['notes'],
                        $_POST['date_of_birth'] ?: null,
                        $_POST['national_insurance'],
                        $_POST['nationality'],
                        $_POST['right_to_work_reference'],
                        $_POST['share_code'] ?: null,
                        $_POST['date_started'] ?: null,
                        $_POST['date_left'] ?: null,
                        $_POST['subcontractor_id'] ?: null
                    ]);
                    
                    $conn->commit();
                    
                    // Get the officer ID
                    $officer_id = $conn->lastInsertId();
                    
                    // Handle file uploads
                    $uploader = new DocumentUploader();
                    $uploadResults = [];
                    
                    $documentTypes = [
                        'passport' => 'Passport',
                        'sia_badge_front' => 'SIA Badge Front', 
                        'sia_badge_back' => 'SIA Badge Back',
                        'full_body_photo' => 'Full Body Photo',
                        'proof_of_address_1' => 'Proof of Address 1',
                        'proof_of_address_2' => 'Proof of Address 2',
                        'brp_card' => 'BRP Card',
                        'visa_share_code_screenshot' => 'Visa Share Code Screenshot'
                    ];
                    
                    foreach ($documentTypes as $type => $label) {
                        if (isset($_FILES[$type]) && $_FILES[$type]['error'] !== UPLOAD_ERR_NO_FILE) {
                            try {
                                $filePath = $uploader->uploadFile($_FILES[$type], $officer_id, $type);
                                $uploader->saveDocumentToDatabase($conn, $officer_id, $type, $filePath, $_FILES[$type]['name']);
                                $uploadResults[] = "$label uploaded successfully";
                            } catch (Exception $e) {
                                $uploadResults[] = "$label upload failed: " . $e->getMessage();
                            }
                        }
                    }
                    
                    // Send welcome email
                    $officer_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
                    $email_sent = sendWelcomeEmail($email, $officer_name, $mobile_number, $pin);
                    
                    if ($email_sent) {
                        $success = "Officer created successfully! Mobile: $mobile_number, PIN: $pin - Welcome email sent to $email";
                        if (!empty($uploadResults)) {
                            $success .= "<br>" . implode("<br>", $uploadResults);
                        }
                    } else {
                        $success = "Officer created successfully! Mobile: $mobile_number, PIN: $pin - Warning: Welcome email could not be sent";
                        if (!empty($uploadResults)) {
                            $success .= "<br>" . implode("<br>", $uploadResults);
                        }
                    }
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
            }
        }
    }
    
    // Load officer data for editing
    if ($is_edit) {
        $stmt = $conn->prepare("
            SELECT o.*, u.mobile_number, u.email as user_email 
            FROM officers o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$officer_id]);
        $officer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$officer) {
            throw new Exception("Officer not found.");
        }
        
        // Use user email if officer email is empty
        if (empty($officer['email']) && !empty($officer['user_email'])) {
            $officer['email'] = $officer['user_email'];
        }
        
        // Load existing documents
        $uploader = new DocumentUploader();
        $existing_documents = $uploader->getOfficerDocuments($conn, $officer_id);
    }
    
    // Get subcontractors for dropdown
    $stmt = $conn->prepare("SELECT id, name FROM subcontractors ORDER BY name");
    $stmt->execute();
    $subcontractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="content">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="page-title mb-1">
                            <i class="fas fa-user-<?php echo $is_edit ? 'edit' : 'plus'; ?> me-3 text-primary"></i>
                            <?php echo $is_edit ? 'Edit Officer Profile' : 'Create New Officer'; ?>
                        </h2>
                        <p class="page-subtitle text-muted mb-0">
                            <?php echo $is_edit ? 'Update officer information and account details' : 'Add a new security officer to the system'; ?>
                        </p>
                    </div>
                    <a href="officers.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Officers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <div class="row">
        <div class="col-12">
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="alert-icon me-3">
                            <i class="fas fa-check-circle fs-4 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">Success!</h6>
                            <p class="mb-0"><?php echo $success; ?></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="alert-icon me-3">
                            <i class="fas fa-exclamation-triangle fs-4 text-danger"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">Error!</h6>
                            <p class="mb-0"><?php echo $error; ?></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Officer Form -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-gradient bg-primary text-white border-0 rounded-top-3">
                    <div class="d-flex align-items-center py-2">
                        <div class="form-icon me-3">
                            <i class="fas fa-user-circle fs-2"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1 fw-bold">Officer Information</h5>
                            <p class="mb-0 small opacity-75">Please fill in all required fields marked with an asterisk (*)</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="action" value="save">
                    
                    <div class="card-body p-5">
                        <!-- Personal Information Section -->
                        <div class="form-section mb-5">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1">Personal Information</h6>
                                        <p class="section-subtitle text-muted mb-0">Basic personal details and identification</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="first_name" id="firstName"
                                               value="<?php echo safe_html($officer['first_name'] ?? ''); ?>" 
                                               placeholder="First Name" required>
                                        <label for="firstName"><i class="fas fa-user me-2 text-primary"></i>First Name <span class="text-danger">*</span></label>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle me-1"></i>Please provide a valid first name.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="last_name" id="lastName"
                                               value="<?php echo safe_html($officer['last_name'] ?? ''); ?>" 
                                               placeholder="Last Name" required>
                                        <label for="lastName"><i class="fas fa-user me-2 text-primary"></i>Last Name <span class="text-danger">*</span></label>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle me-1"></i>Please provide a valid last name.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="date_of_birth" id="dateOfBirth"
                                               value="<?php echo safe_html($officer['date_of_birth'] ?? ''); ?>">
                                        <label for="dateOfBirth"><i class="fas fa-calendar me-2 text-primary"></i>Date of Birth</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select" name="nationality" id="nationality">
                                            <?php 
                                            $countries = getNationalityOptions($conn);
                                            foreach ($countries as $country): 
                                                $selected = ($officer['nationality'] ?? 'British') === $country['name'] ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo safe_html($country['name']); ?>" <?php echo $selected; ?>>
                                                    <?php echo safe_html($country['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="nationality"><i class="fas fa-flag me-2 text-primary"></i>Nationality</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="national_insurance" id="nationalInsurance"
                                               value="<?php echo safe_html($officer['national_insurance'] ?? ''); ?>" 
                                               placeholder="AB123456C" style="text-transform: uppercase;" maxlength="9">
                                        <label for="nationalInsurance"><i class="fas fa-id-card me-2 text-primary"></i>National Insurance Number</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Format: AA123456A
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="right_to_work_reference" id="rightToWork"
                                               value="<?php echo safe_html($officer['right_to_work_reference'] ?? ''); ?>" 
                                               placeholder="Reference Number">
                                        <label for="rightToWork"><i class="fas fa-file-contract me-2 text-primary"></i>Right to Work Reference</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="share_code" id="shareCode"
                                               value="<?php echo safe_html($officer['share_code'] ?? ''); ?>" 
                                               placeholder="Share Code" maxlength="20">
                                        <label for="shareCode"><i class="fas fa-key me-2 text-primary"></i>UK Share Code Number</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>UK Government Share Code for right to work verification
                                        </div>
                                    </div>
                                </div>
                                <?php if ($is_edit): ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold text-dark mb-3">
                                            <i class="fas fa-user-cog me-2 text-warning"></i>Account Status
                                        </label>
                                        <div class="card bg-light border-0 p-4 rounded-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input form-check-input-lg" type="checkbox" name="suspend" 
                                                       id="suspendSwitch" <?php echo ($officer['suspend'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold text-danger fs-6" for="suspendSwitch">
                                                    <i class="fas fa-user-slash me-2"></i>Suspend Officer Account
                                                </label>
                                            </div>
                                            <div class="form-text text-muted mt-2">
                                                <i class="fas fa-info-circle me-1"></i>Suspended officers cannot log in or receive shifts
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Profile Photo & Documents Section -->
                        <div class="form-section mb-5">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-purple bg-opacity-15 rounded-circle p-3 shadow-sm">
                                            <i class="fas fa-camera text-purple fs-5"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1 text-dark fw-bold fs-6">Profile Photo & Documents</h6>
                                        <p class="section-subtitle text-muted mb-0 small">Upload officer photos and required documents</p>
                                    </div>
                                </div>
                                <hr class="section-divider mt-3 mb-0 opacity-25">
                            </div>
                            
                            <!-- First Row: 4 Documents -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-portrait me-2 text-purple"></i>Proof Of Photo ID - Passport
                                        </label>
                                        <?php 
                                        $has_passport = false;
                                        $passport_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'passport'):
                                                    $has_passport = true;
                                                    $passport_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_passport): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="passport-existing">
                                            <div class="document-preview">
                                                <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=passport" 
                                                     alt="Passport" class="document-thumbnail">
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($passport_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($passport_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('passport', <?php echo $officer_id; ?>, '<?php echo addslashes($passport_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('passport')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('passport', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_passport ? 'd-none' : ''; ?>" 
                                             id="passport-upload">
                                            <input type="file" id="passport" name="passport" accept="image/*" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload passport photo</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, GIF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-user-circle me-2 text-purple"></i>Full Body Picture In Uniform
                                        </label>
                                        <?php 
                                        $has_full_body = false;
                                        $full_body_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'full_body_photo'):
                                                    $has_full_body = true;
                                                    $full_body_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_full_body): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="full_body_photo-existing">
                                            <div class="document-preview">
                                                <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=full_body_photo" 
                                                     alt="Full Body Photo" class="document-thumbnail">
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($full_body_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($full_body_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('full_body_photo', <?php echo $officer_id; ?>, '<?php echo addslashes($full_body_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('full_body_photo')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('full_body_photo', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_full_body ? 'd-none' : ''; ?>" 
                                             id="full_body_photo-upload">
                                            <input type="file" id="full_body_photo" name="full_body_photo" accept="image/*" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload full body photo</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, GIF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-id-card me-2 text-purple"></i>SIA Badge Front
                                        </label>
                                        <?php 
                                        $has_sia_front = false;
                                        $sia_front_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'sia_badge_front'):
                                                    $has_sia_front = true;
                                                    $sia_front_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_sia_front): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="sia_badge_front-existing">
                                            <div class="document-preview">
                                                <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=sia_badge_front" 
                                                     alt="SIA Badge Front" class="document-thumbnail">
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($sia_front_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($sia_front_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('sia_badge_front', <?php echo $officer_id; ?>, '<?php echo addslashes($sia_front_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('sia_badge_front')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('sia_badge_front', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_sia_front ? 'd-none' : ''; ?>" 
                                             id="sia_badge_front-upload">
                                            <input type="file" id="sia_badge_front" name="sia_badge_front" accept="image/*" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload SIA badge front</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, GIF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-id-card me-2 text-purple"></i>SIA Badge Back
                                        </label>
                                        <?php 
                                        $has_sia_back = false;
                                        $sia_back_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'sia_badge_back'):
                                                    $has_sia_back = true;
                                                    $sia_back_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_sia_back): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="sia_badge_back-existing">
                                            <div class="document-preview">
                                                <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=sia_badge_back" 
                                                     alt="SIA Badge Back" class="document-thumbnail">
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($sia_back_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($sia_back_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('sia_badge_back', <?php echo $officer_id; ?>, '<?php echo addslashes($sia_back_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('sia_badge_back')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('sia_badge_back', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_sia_back ? 'd-none' : ''; ?>" 
                                             id="sia_badge_back-upload">
                                            <input type="file" id="sia_badge_back" name="sia_badge_back" accept="image/*" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload SIA badge back</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, GIF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Second Row: 4 Documents -->
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-home me-2 text-purple"></i>Proof Of Address 1 (Driving License/Bank Statement/Utility Bill)
                                        </label>
                                        <?php 
                                        $has_proof_addr1 = false;
                                        $proof_addr1_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'proof_of_address_1'):
                                                    $has_proof_addr1 = true;
                                                    $proof_addr1_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_proof_addr1): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="proof_of_address_1-existing">
                                            <div class="document-preview">
                                                <?php 
                                                $is_pdf = strpos($proof_addr1_doc['document_name'], '.pdf') !== false;
                                                if ($is_pdf): ?>
                                                    <div class="pdf-thumbnail">
                                                        <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=proof_of_address_1" 
                                                         alt="Proof of Address 1" class="document-thumbnail">
                                                <?php endif; ?>
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($proof_addr1_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($proof_addr1_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('proof_of_address_1', <?php echo $officer_id; ?>, '<?php echo addslashes($proof_addr1_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('proof_of_address_1')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('proof_of_address_1', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_proof_addr1 ? 'd-none' : ''; ?>" 
                                             id="proof_of_address_1-upload">
                                            <input type="file" id="proof_of_address_1" name="proof_of_address_1" accept="image/*,.pdf" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload proof of address</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, PDF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-home me-2 text-purple"></i>Proof Of Address 2 (Bank Statement/Utility Bill)
                                        </label>
                                        <?php 
                                        $has_proof_addr2 = false;
                                        $proof_addr2_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'proof_of_address_2'):
                                                    $has_proof_addr2 = true;
                                                    $proof_addr2_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_proof_addr2): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="proof_of_address_2-existing">
                                            <div class="document-preview">
                                                <?php 
                                                $is_pdf = strpos($proof_addr2_doc['document_name'], '.pdf') !== false;
                                                if ($is_pdf): ?>
                                                    <div class="pdf-thumbnail">
                                                        <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=proof_of_address_2" 
                                                         alt="Proof of Address 2" class="document-thumbnail">
                                                <?php endif; ?>
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($proof_addr2_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($proof_addr2_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('proof_of_address_2', <?php echo $officer_id; ?>, '<?php echo addslashes($proof_addr2_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('proof_of_address_2')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('proof_of_address_2', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_proof_addr2 ? 'd-none' : ''; ?>" 
                                             id="proof_of_address_2-upload">
                                            <input type="file" id="proof_of_address_2" name="proof_of_address_2" accept="image/*,.pdf" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload second proof of address</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, PDF up to 5MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-passport me-2 text-purple"></i>If Applicant Holds A Foreign Passport - Proof Of Residence In The UK (BRP Card)
                                        </label>
                                        <?php 
                                        $has_brp = false;
                                        $brp_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'brp_card'):
                                                    $has_brp = true;
                                                    $brp_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_brp): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="brp_card-existing">
                                            <div class="document-preview">
                                                <?php 
                                                $is_pdf = strpos($brp_doc['document_name'], '.pdf') !== false;
                                                if ($is_pdf): ?>
                                                    <div class="pdf-thumbnail">
                                                        <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=brp_card" 
                                                         alt="BRP Card" class="document-thumbnail">
                                                <?php endif; ?>
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($brp_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($brp_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('brp_card', <?php echo $officer_id; ?>, '<?php echo addslashes($brp_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('brp_card')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('brp_card', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_brp ? 'd-none' : ''; ?>" 
                                             id="brp_card-upload">
                                            <input type="file" id="brp_card" name="brp_card" accept="image/*,.pdf" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload BRP card</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, PDF up to 5MB (Optional)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                                
                                <div class="col-md-3">
                                    <div class="document-upload-card">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-mobile-alt me-2 text-purple"></i>If Applicant Holds A Foreign Passport - Visa Share Code Check Screenshot
                                        </label>
                                        <?php 
                                        $has_visa_screenshot = false;
                                        $visa_screenshot_doc = null;
                                        if ($is_edit && isset($existing_documents)):
                                            foreach ($existing_documents as $doc):
                                                if ($doc['document_type'] == 'visa_share_code_screenshot'):
                                                    $has_visa_screenshot = true;
                                                    $visa_screenshot_doc = $doc;
                                                    break;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <?php if ($has_visa_screenshot): ?>
                                        <!-- Existing Document Display -->
                                        <div class="existing-document-container" id="visa_share_code_screenshot-existing">
                                            <div class="document-preview">
                                                <?php 
                                                $is_pdf = strpos($visa_screenshot_doc['document_name'], '.pdf') !== false;
                                                if ($is_pdf): ?>
                                                    <div class="pdf-thumbnail">
                                                        <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=visa_share_code_screenshot" 
                                                         alt="Visa Share Code Screenshot" class="document-thumbnail">
                                                <?php endif; ?>
                                                <div class="document-overlay">
                                                    <div class="document-info">
                                                        <h6 class="mb-1"><?php echo safe_html($visa_screenshot_doc['document_name']); ?></h6>
                                                        <small class="text-muted">Uploaded: <?php echo date('d/m/Y', strtotime($visa_screenshot_doc['updated_at'])); ?></small>
                                                    </div>
                                                    <div class="document-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-light" 
                                                                onclick="viewDocumentInModal('visa_share_code_screenshot', <?php echo $officer_id; ?>, '<?php echo addslashes($visa_screenshot_doc['document_name']); ?>')" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="showReplaceUpload('visa_share_code_screenshot')" title="Replace">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeDocument('visa_share_code_screenshot', <?php echo $officer_id; ?>)" title="Remove">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Upload Area -->
                                        <div class="upload-area <?php echo $has_visa_screenshot ? 'd-none' : ''; ?>" 
                                             id="visa_share_code_screenshot-upload">
                                            <input type="file" id="visa_share_code_screenshot" name="visa_share_code_screenshot" accept="image/*,.pdf" style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
                                                <p class="mb-1 fw-semibold">Click to upload visa share code screenshot</p>
                                                <p class="small text-muted mb-0">JPEG, PNG, PDF up to 5MB (Optional)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information Section -->
                        <div class="form-section mb-5">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-success bg-opacity-15 rounded-circle p-3 shadow-sm">
                                            <i class="fas fa-phone text-success fs-5"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1 text-dark fw-bold fs-6">Contact Information</h6>
                                        <p class="section-subtitle text-muted mb-0 small">Phone numbers, email, and address details</p>
                                    </div>
                                </div>
                                <hr class="section-divider mt-3 mb-0 opacity-25">
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" name="mobile_number" id="mobileNumber"
                                               value="<?php echo safe_html($officer['mobile_number'] ?? ''); ?>" 
                                               placeholder="07XXX XXXXXX" required>
                                        <label for="mobileNumber"><i class="fas fa-mobile-alt me-2 text-success"></i>Mobile Number <span class="text-danger">*</span></label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-key me-1"></i>Used for login credentials
                                        </div>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle me-1"></i>Please provide a valid mobile number.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" name="email" id="emailAddress"
                                               value="<?php echo safe_html($officer['email'] ?? ''); ?>" 
                                               placeholder="officer@example.com" required>
                                        <label for="emailAddress"><i class="fas fa-envelope me-2 text-success"></i>Email Address <span class="text-danger">*</span></label>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle me-1"></i>Please provide a valid email address.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" name="phone" id="alternativePhone"
                                               value="<?php echo safe_html($officer['phone'] ?? ''); ?>" 
                                               placeholder="Optional contact number">
                                        <label for="alternativePhone"><i class="fas fa-phone me-2 text-success"></i>Alternative Phone</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address" id="streetAddress"
                                               value="<?php echo safe_html($officer['address'] ?? ''); ?>" 
                                               placeholder="Enter full address">
                                        <label for="streetAddress"><i class="fas fa-home me-2 text-success"></i>Street Address</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address_city" id="city"
                                               value="<?php echo safe_html($officer['address_city'] ?? ''); ?>" 
                                               placeholder="City">
                                        <label for="city">City</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address_postal_code" id="postalCode"
                                               value="<?php echo safe_html($officer['address_postal_code'] ?? ''); ?>" 
                                               placeholder="SW1A 1AA" style="text-transform: uppercase;">
                                        <label for="postalCode">Postal Code</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Employment & Visa Section -->
                        <div class="form-section mb-5">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-info bg-opacity-15 rounded-circle p-3 shadow-sm">
                                            <i class="fas fa-briefcase text-info fs-5"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1 text-dark fw-bold fs-6">Employment & Visa Information</h6>
                                        <p class="section-subtitle text-muted mb-0 small">Work status, rates, and visa details</p>
                                    </div>
                                </div>
                                <hr class="section-divider mt-3 mb-0 opacity-25">
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <select class="form-select" name="employment_status" id="employmentStatus">
                                            <option value="Part-time" <?php echo ($officer['employment_status'] ?? 'Part-time') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="Full-time" <?php echo ($officer['employment_status'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="Contractor" <?php echo ($officer['employment_status'] ?? '') === 'Contractor' ? 'selected' : ''; ?>>Contractor</option>
                                            <option value="Inactive" <?php echo ($officer['employment_status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <label for="employmentStatus"><i class="fas fa-clock me-2 text-info"></i>Employment Status</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="number" step="0.01" class="form-control" name="hourly_rate" id="hourlyRate"
                                               value="<?php echo safe_html($officer['hourly_rate'] ?? '0.00'); ?>" 
                                               placeholder="0.00" min="0">
                                        <label for="hourlyRate"><i class="fas fa-pound-sign me-2 text-info"></i>Hourly Rate (£)</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="date_started" id="dateStarted"
                                               value="<?php echo safe_html($officer['date_started'] ?? ''); ?>">
                                        <label for="dateStarted"><i class="fas fa-calendar-plus me-2 text-info"></i>Date Started</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="date_left" id="dateLeft"
                                               value="<?php echo safe_html($officer['date_left'] ?? ''); ?>">
                                        <label for="dateLeft"><i class="fas fa-calendar-minus me-2 text-info"></i>Date Left</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select" name="visa_status" id="visaStatus">
                                            <?php 
                                            $visaOptions = getVisaStatusOptions($conn);
                                            foreach ($visaOptions as $option): 
                                                $selected = ($officer['visa_status'] ?? 'British') === $option['name'] ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo safe_html($option['name']); ?>" <?php echo $selected; ?>>
                                                    <?php echo safe_html($option['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="visaStatus"><i class="fas fa-passport me-2 text-info"></i>Visa Status</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="visa_expiry_date" id="visaExpiry"
                                               value="<?php echo safe_html($officer['visa_expiry_date'] ?? ''); ?>">
                                        <label for="visaExpiry"><i class="fas fa-calendar-times me-2 text-info"></i>Visa Expiry Date</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Leave blank if not applicable
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select" name="subcontractor_id" id="subcontractor">
                                            <option value="">Direct Employee</option>
                                            <?php foreach ($subcontractors as $sub): ?>
                                                <option value="<?php echo $sub['id']; ?>" 
                                                    <?php echo ($officer['subcontractor_id'] ?? '') == $sub['id'] ? 'selected' : ''; ?>>
                                                    <?php echo safe_html($sub['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="subcontractor"><i class="fas fa-building me-2 text-info"></i>Subcontractor</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- SIA & Banking Section -->
                        <div class="form-section mb-5">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-warning bg-opacity-15 rounded-circle p-3 shadow-sm">
                                            <i class="fas fa-id-card text-warning fs-5"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1 text-dark fw-bold fs-6">SIA Certification & Banking</h6>
                                        <p class="section-subtitle text-muted mb-0 small">Security license and payment details</p>
                                    </div>
                                </div>
                                <hr class="section-divider mt-3 mb-0 opacity-25">
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="sia_badge_number" id="siaBadge"
                                               value="<?php echo safe_html($officer['sia_badge_number'] ?? ''); ?>" 
                                               placeholder="Enter SIA badge number" style="text-transform: uppercase;">
                                        <label for="siaBadge"><i class="fas fa-id-badge me-2 text-warning"></i>SIA Badge Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="sia_expiry_date" id="siaExpiry"
                                               value="<?php echo safe_html($officer['sia_expiry_date'] ?? ''); ?>">
                                        <label for="siaExpiry"><i class="fas fa-calendar-check me-2 text-warning"></i>SIA Expiry Date</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Important for license compliance
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="bank_account_name" id="bankAccountName"
                                               value="<?php echo safe_html($officer['bank_account_name'] ?? ''); ?>" 
                                               placeholder="Full name as on bank account">
                                        <label for="bankAccountName"><i class="fas fa-user me-2 text-warning"></i>Account Holder Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="bank_account" id="bankAccount"
                                               value="<?php echo safe_html($officer['bank_account'] ?? ''); ?>" 
                                               placeholder="12345678" maxlength="8">
                                        <label for="bankAccount"><i class="fas fa-university me-2 text-warning"></i>Account Number</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="sort_code" id="sortCode"
                                               maxlength="8" placeholder="12-34-56"
                                               value="<?php echo safe_html($officer['sort_code'] ?? ''); ?>" 
                                               pattern="[0-9]{2}-[0-9]{2}-[0-9]{2}">
                                        <label for="sortCode"><i class="fas fa-code me-2 text-warning"></i>Sort Code</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Format: XX-XX-XX
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="bank_roll_number" id="rollNumber"
                                               value="<?php echo safe_html($officer['bank_roll_number'] ?? ''); ?>" 
                                               placeholder="Building Society Roll Number (if applicable)">
                                        <label for="rollNumber"><i class="fas fa-hashtag me-2 text-warning"></i>Building Society Roll Number</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Only required for building society accounts
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Emergency Contact Section -->
                        <div class="form-section mb-4">
                            <div class="section-header mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <div class="bg-danger bg-opacity-15 rounded-circle p-3 shadow-sm">
                                            <i class="fas fa-phone-alt text-danger fs-5"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="section-title mb-1 text-dark fw-bold fs-6">Emergency Contact</h6>
                                        <p class="section-subtitle text-muted mb-0 small">Emergency contact person details</p>
                                    </div>
                                </div>
                                <hr class="section-divider mt-3 mb-0 opacity-25">
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="emergency_contact_name" id="emergencyName"
                                               value="<?php echo safe_html($officer['emergency_contact_name'] ?? ''); ?>" 
                                               placeholder="Full name of emergency contact">
                                        <label for="emergencyName"><i class="fas fa-user-friends me-2 text-danger"></i>Emergency Contact Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" name="emergency_contact_phone" id="emergencyPhone"
                                               value="<?php echo safe_html($officer['emergency_contact_phone'] ?? ''); ?>" 
                                               placeholder="Emergency contact number">
                                        <label for="emergencyPhone"><i class="fas fa-phone-alt me-2 text-danger"></i>Emergency Contact Phone</label>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" name="notes" id="notes" style="height: 120px;"
                                                  placeholder="Any additional information about the officer..."><?php echo safe_html($officer['notes'] ?? ''); ?></textarea>
                                        <label for="notes"><i class="fas fa-sticky-note me-2 text-danger"></i>Additional Notes</label>
                                        <div class="form-text text-muted mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Optional notes, special requirements, or observations
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="card shadow border-0 rounded-3 mt-4">
                        <div class="card-body bg-light p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    <i class="fas fa-shield-alt me-2 text-success"></i>
                                    <span class="fw-semibold">Secure Form</span> - All data is encrypted and protected
                                </div>
                                <div class="d-flex gap-3">
                                    <a href="officers.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4 shadow-sm">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $is_edit ? 'Update Officer' : 'Create Officer'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Full-Screen Document Viewer -->
<div id="documentViewer" class="document-viewer">
    <div class="document-viewer-overlay" onclick="closeDocumentViewer()"></div>
    <div class="document-viewer-container">
        <div class="document-viewer-header">
            <h5 id="documentViewerTitle">
                <i class="fas fa-file-image me-2"></i>Document Preview
            </h5>
            <button type="button" class="document-viewer-close" onclick="closeDocumentViewer()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="document-viewer-content">
            <div id="documentViewerContainer">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="document-viewer-footer">
            <div class="document-info">
                <small id="documentViewerInfo"></small>
            </div>
            <div class="document-actions">
                <a href="#" id="downloadViewerBtn" class="btn btn-outline-light btn-sm me-2" download>
                    <i class="fas fa-download me-1"></i>Download
                </a>
                <a href="#" id="openViewerBtn" class="btn btn-outline-light btn-sm" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Open in New Tab
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<style>
/* Professional Form Styling */
.bg-gradient-primary-soft {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.bg-gradient-success-soft {
    background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
}
.bg-gradient-info-soft {
    background: linear-gradient(135deg, #3498db 0%, #85c1e9 100%);
}
.bg-gradient-warning-soft {
    background: linear-gradient(135deg, #f39c12 0%, #f7dc6f 100%);
}
.bg-gradient-danger-soft {
    background: linear-gradient(135deg, #e74c3c 0%, #fadbd8 100%);
}

/* Enhanced Card Styling */
.card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

/* .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
} */

/* Form Control Enhancements */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
    color: #495057;
    font-weight: 600;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.form-control:focus,
.form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.15);
    transform: scale(1.02);
    transition: all 0.3s ease;
}

.form-control:valid {
    border-color: #28a745;
}

.form-control:invalid {
    border-color: #dc3545;
}

/* Button Enhancements */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn-outline-secondary {
    border-width: 2px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Section Headers */
.section-divider {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #dee2e6 50%, transparent 100%);
}

/* Alert Enhancements */
.alert {
    border-radius: 15px !important;
    border: none !important;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
}

/* Typography */
.text-gray-800 {
    color: #5a5c69 !important;
}

.lead {
    font-size: 1.1rem;
    font-weight: 400;
}

/* Purple color for documents section */
.text-purple {
    color: #6f42c1 !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

/* Document Upload Styling */
.document-upload-card {
    margin-bottom: 1.5rem;
}

.document-upload-card .form-label {
    font-size: 0.9rem;
    line-height: 1.3;
    margin-bottom: 1rem;
}

/* Optimize for 4-column layout */
@media (min-width: 1200px) {
    .document-upload-card .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.75rem;
    }
    
    .document-preview {
        height: 180px;
    }
    
    .upload-area {
        min-height: 180px;
    }
    
    .document-info h6 {
        font-size: 0.8rem;
    }
    
    .document-actions .btn {
        padding: 4px 8px;
        font-size: 0.75rem;
    }
}

/* Existing Document Preview Styling */
.existing-document-container {
    position: relative;
    margin-bottom: 1rem;
}

.document-preview {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    height: 200px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}

.document-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.pdf-thumbnail {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 100%;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.document-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%);
    color: white;
    padding: 15px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    opacity: 0;
    transition: all 0.3s ease;
}

.document-preview:hover .document-overlay {
    opacity: 1;
}

.document-preview:hover .document-thumbnail {
    transform: scale(1.05);
}

.document-info h6 {
    color: white;
    margin-bottom: 5px;
    font-size: 0.9rem;
    font-weight: 600;
}

.document-info small {
    color: rgba(255,255,255,0.8);
    font-size: 0.75rem;
}

.document-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.document-actions .btn {
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.document-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
    position: relative;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-area:hover {
    border-color: #6f42c1;
    background: rgba(111, 66, 193, 0.05);
    transform: translateY(-2px);
}

.upload-area.dragover {
    border-color: #6f42c1;
    background: rgba(111, 66, 193, 0.1);
}

.upload-content {
    text-align: center;
}

.existing-file {
    position: absolute;
    bottom: 10px;
    left: 15px;
    right: 15px;
    background: rgba(40, 167, 69, 0.1);
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid rgba(40, 167, 69, 0.2);
    color: #28a745;
    font-size: 0.875rem;
}

.file-selected {
    border-color: #28a745 !important;
    background: rgba(40, 167, 69, 0.05) !important;
}

.file-selected .upload-content {
    color: #28a745;
}

.upload-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #e9ecef;
    border-radius: 0 0 12px 12px;
    overflow: hidden;
}

.upload-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    width: 0%;
    transition: width 0.3s ease;
}

/* Animation Classes */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
    20%, 40%, 60%, 80% { transform: translateX(2px); }
}

.animate__shakeX {
    animation: shake 0.6s ease-in-out;
}

/* Form Floating Improvements */
.form-floating {
    position: relative;
}

.form-floating > .form-control,
.form-floating > .form-select {
    border-radius: 12px;
    border-width: 2px;
    transition: all 0.3s ease;
}

.form-floating > label {
    font-weight: 500;
    color: #6c757d;
}

/* Loading States */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    /* Switch to 2 columns on medium screens */
    .col-md-3 {
        flex: 0 0 auto;
        width: 50%;
    }
}

@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
    
    .btn-lg {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .d-flex.gap-3 {
        flex-direction: column;
    }
    
    /* Switch to 1 column on mobile */
    .col-md-3 {
        flex: 0 0 auto;
        width: 100%;
    }
    
    .document-preview {
        height: 150px;
    }
    
    .document-actions {
        flex-direction: column;
        gap: 4px;
    }
    
    .document-actions .btn {
        width: 100%;
        margin: 0;
    }
    
    .upload-area {
        min-height: 100px;
        padding: 1rem;
    }
    
    .document-overlay {
        padding: 10px;
    }
    
    .document-info h6 {
        font-size: 0.8rem;
    }
    
    .document-info small {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .document-preview {
        height: 120px;
    }
    
    .upload-area {
        min-height: 80px;
        padding: 0.5rem;
    }
    
    .upload-content p {
        font-size: 0.9rem;
    }
    
    .upload-content .small {
        font-size: 0.75rem;
    }
}

/* Full-Screen Document Viewer */
.document-viewer {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.document-viewer.show {
    display: flex;
    opacity: 1;
}

.document-viewer-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.document-viewer-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    max-width: 95vw;
    max-height: 95vh;
    margin: auto;
    z-index: 1;
}

.document-viewer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px 12px 0 0;
    color: white;
    margin-bottom: 1rem;
}

.document-viewer-header h5 {
    margin: 0;
    font-weight: 600;
    font-size: 1.1rem;
}

.document-viewer-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

.document-viewer-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.document-viewer-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 1.5rem;
    overflow: hidden;
}

#documentViewerContainer {
    max-width: 100%;
    max-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

#documentViewerContainer img {
    max-width: 100%;
    max-height: calc(100vh - 200px);
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s ease;
}

#documentViewerContainer img:hover {
    transform: scale(1.02);
}

.document-viewer-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 0 0 12px 12px;
    color: white;
    margin-top: 1rem;
}

.document-info {
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.8);
}

.document-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pdf-viewer-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
}

.pdf-viewer-icon {
    color: #dc3545;
    margin-bottom: 1rem;
}

.loading-viewer-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: white;
}

.loading-viewer-spinner .spinner-border {
    color: white;
    width: 3rem;
    height: 3rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .document-viewer-container {
        max-width: 100vw;
        max-height: 100vh;
    }
    
    .document-viewer-header,
    .document-viewer-footer {
        padding: 0.75rem 1rem;
    }
    
    .document-viewer-content {
        padding: 0 1rem;
    }
    
    #documentViewerContainer img {
        max-height: calc(100vh - 150px);
    }
    
    .document-viewer-footer {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .document-actions {
        width: 100%;
        justify-content: center;
    }
    
    .document-viewer-header h5 {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .document-viewer-header,
    .document-viewer-footer {
        padding: 0.5rem;
    }
    
    .document-viewer-close {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    #documentViewerContainer img {
        max-height: calc(100vh - 120px);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation enhancement
    const form = document.querySelector('.needs-validation');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Add input formatting
    formatInputs();
    
    // Enhanced form validation with better UX
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Show first invalid field with smooth animation
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'center'
                });
                
                // Add shake animation to invalid field
                firstInvalid.classList.add('animate__animated', 'animate__shakeX');
                setTimeout(() => {
                    firstInvalid.classList.remove('animate__animated', 'animate__shakeX');
                }, 1000);
            }
        } else {
            // Show professional loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Saving...';
            submitBtn.disabled = true;
            
            // Add loading animation to form
            form.style.opacity = '0.7';
            form.style.pointerEvents = 'none';
        }
        
        form.classList.add('was-validated');
    });
    
    // Real-time validation with improved feedback
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
    
    // Add floating label animation enhancement
    enhanceFloatingLabels();
    
    // Add progress indication
    addProgressIndicator();
    
    // Initialize file upload functionality
    initializeFileUploads();
});

function validateField(field) {
    if (field.checkValidity()) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        // Add success animation
        field.style.transform = 'scale(1.02)';
        setTimeout(() => {
            field.style.transform = 'scale(1)';
        }, 150);
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}

function formatInputs() {
    // Enhanced National Insurance Number formatting
    const niInput = document.querySelector('input[name="national_insurance"]');
    if (niInput) {
        niInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            
            // Format: 2 letters + 6 digits + 1 letter
            if (value.length <= 2) {
                // First two characters must be letters
                value = value.replace(/[^A-Z]/g, '');
            } else if (value.length <= 8) {
                // Next 6 characters must be digits
                let letters = value.substring(0, 2);
                let digits = value.substring(2).replace(/[^0-9]/g, '');
                value = letters + digits;
            } else {
                // Last character must be a letter
                let letters = value.substring(0, 2);
                let digits = value.substring(2, 8);
                let lastLetter = value.substring(8, 9).replace(/[^A-Z]/g, '');
                value = letters + digits + lastLetter;
            }
            
            e.target.value = value;
        });
    }
    
    // Enhanced Sort Code formatting
    const sortCodeInput = document.querySelector('input[name="sort_code"]');
    if (sortCodeInput) {
        sortCodeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '-' + value.substring(2);
            }
            if (value.length >= 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 7);
            }
            
            e.target.value = value;
        });
    }
    
    // Enhanced Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            
            // Handle international format
            if (value.startsWith('44') && value.length > 2) {
                value = '0' + value.substring(2);
            }
            
            // Format UK mobile numbers
            if (input.name === 'mobile_number' && value.length >= 5) {
                if (value.startsWith('07')) {
                    // Format as: 07XXX XXXXXX
                    value = value.substring(0, 5) + ' ' + value.substring(5, 11);
                }
            }
            
            e.target.value = value;
        });
    });
    
    // Postal code formatting
    const postalInput = document.querySelector('input[name="address_postal_code"]');
    if (postalInput) {
        postalInput.addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            
            // UK postcode validation and formatting
            value = value.replace(/[^A-Z0-9\s]/g, '');
            
            e.target.value = value;
        });
    }
}

function enhanceFloatingLabels() {
    const floatingInputs = document.querySelectorAll('.form-floating input, .form-floating select, .form-floating textarea');
    
    floatingInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
}

function addProgressIndicator() {
    const requiredInputs = document.querySelectorAll('input[required], select[required]');
    const progressBar = createProgressBar();
    
    function updateProgress() {
        let completed = 0;
        requiredInputs.forEach(input => {
            if (input.value.trim() !== '') {
                completed++;
            }
        });
        
        const percentage = Math.round((completed / requiredInputs.length) * 100);
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        
        if (percentage === 100) {
            progressBar.classList.remove('bg-warning');
            progressBar.classList.add('bg-success');
        } else {
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('bg-warning');
        }
    }
    
    requiredInputs.forEach(input => {
        input.addEventListener('input', updateProgress);
    });
    
    updateProgress();
}

function createProgressBar() {
    const formHeader = document.querySelector('.card-header');
    const progressContainer = document.createElement('div');
    progressContainer.className = 'progress mt-3';
    progressContainer.style.height = '4px';
    
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar bg-warning';
    progressBar.setAttribute('role', 'progressbar');
    progressBar.setAttribute('aria-valuenow', '0');
    progressBar.setAttribute('aria-valuemin', '0');
    progressBar.setAttribute('aria-valuemax', '100');
    
    progressContainer.appendChild(progressBar);
    formHeader.appendChild(progressContainer);
    
    return progressBar;
}

function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        const uploadArea = input.parentElement;
        
        // File selection change
        input.addEventListener('change', function(e) {
            handleFileSelection(this, uploadArea);
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileSelection(input, uploadArea);
            }
        });
        
        // Handle click event with check for cancel button
        uploadArea.addEventListener('click', function(e) {
            // Don't trigger file input if clicking on the cancel button or if it exists
            if (e.target.closest('.cancel-replace-btn')) {
                return;
            }
            
            // Check if there's a cancel button present (means we're in replace mode)
            const cancelBtn = this.querySelector('.cancel-replace-btn');
            if (cancelBtn) {
                // If there's a cancel button, don't trigger file input automatically
                // Only trigger if clicking directly on upload icon or text, not the cancel button
                if (e.target.closest('.fas') || e.target.closest('p')) {
                    input.click();
                }
            } else {
                // Normal upload mode - trigger file input
                input.click();
            }
        });
        
        // Remove the onclick attribute from the HTML since we're handling it with event listener
        uploadArea.removeAttribute('onclick');
    });
}

function handleFileSelection(input, uploadArea) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file
    if (!validateFileUpload(file, input.accept, input.id)) {
        input.value = '';
        return;
    }
    
    // Update UI
    uploadArea.classList.add('file-selected');
    
    const uploadContent = uploadArea.querySelector('.upload-content');
    
    // Create preview if it's an image
    const isImage = file.type.startsWith('image/');
    let previewHtml = '';
    
    if (isImage) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewHtml = `
                <div class="file-preview mb-2">
                    <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 8px; object-fit: cover;">
                </div>
            `;
            updateUploadContent(uploadContent, file, previewHtml);
        };
        reader.readAsDataURL(file);
    } else {
        const isPDF = file.type === 'application/pdf';
        if (isPDF) {
            previewHtml = `
                <div class="file-preview mb-2">
                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                </div>
            `;
        }
        updateUploadContent(uploadContent, file, previewHtml);
    }
    
    // Add progress bar
    if (!uploadArea.querySelector('.upload-progress')) {
        const progressDiv = document.createElement('div');
        progressDiv.className = 'upload-progress';
        progressDiv.innerHTML = '<div class="upload-progress-bar"></div>';
        uploadArea.appendChild(progressDiv);
        
        // Simulate progress
        const progressBar = progressDiv.querySelector('.upload-progress-bar');
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 30;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            progressBar.style.width = progress + '%';
        }, 150);
    }
}

function updateUploadContent(uploadContent, file, previewHtml) {
    uploadContent.innerHTML = `
        ${previewHtml}
        <i class="fas fa-check-circle fs-2 text-success mb-2"></i>
        <p class="mb-1 fw-semibold text-success">File selected: ${file.name}</p>
        <p class="small text-muted mb-0">Size: ${formatFileSize(file.size)}</p>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearFileSelection(this)">
            <i class="fas fa-times me-1"></i>Clear
        </button>
    `;
}

function clearFileSelection(button) {
    const uploadArea = button.closest('.upload-area');
    const fileInput = uploadArea.querySelector('input[type="file"]');
    const uploadContent = uploadArea.querySelector('.upload-content');
    const progressDiv = uploadArea.querySelector('.upload-progress');
    
    // Clear file input
    fileInput.value = '';
    
    // Remove file selected class
    uploadArea.classList.remove('file-selected');
    
    // Remove progress bar
    if (progressDiv) {
        progressDiv.remove();
    }
    
    // Reset upload content based on document type
    const documentType = fileInput.id;
    let uploadText = 'Click to upload file';
    let helpText = 'JPEG, PNG, GIF up to 5MB';
    
    switch (documentType) {
        case 'passport':
            uploadText = 'Click to upload passport photo';
            break;
        case 'full_body_photo':
            uploadText = 'Click to upload full body photo';
            break;
        case 'sia_badge_front':
            uploadText = 'Click to upload SIA badge front';
            break;
        case 'sia_badge_back':
            uploadText = 'Click to upload SIA badge back';
            break;
        case 'proof_of_address_1':
        case 'proof_of_address_2':
            uploadText = 'Click to upload proof of address';
            helpText = 'JPEG, PNG, PDF up to 5MB';
            break;
        case 'brp_card':
            uploadText = 'Click to upload BRP card';
            helpText = 'JPEG, PNG, PDF up to 5MB (Optional)';
            break;
        case 'visa_share_code_screenshot':
            uploadText = 'Click to upload visa share code screenshot';
            helpText = 'JPEG, PNG, PDF up to 5MB (Optional)';
            break;
    }
    
    uploadContent.innerHTML = `
        <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2"></i>
        <p class="mb-1 fw-semibold">${uploadText}</p>
        <p class="small text-muted mb-0">${helpText}</p>
    `;
}

function validateFileUpload(file, acceptTypes, inputId = '') {
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check file size
    if (file.size > maxSize) {
        alert('File size must be less than 5MB');
        return false;
    }
    
    // Check file type
    const allowedTypes = acceptTypes.split(',').map(type => type.trim());
    const fileType = file.type;
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    let isValid = false;
    for (let type of allowedTypes) {
        if (type.startsWith('.') && fileExtension === type) {
            isValid = true;
            break;
        } else if (fileType.startsWith(type.replace('*', ''))) {
            isValid = true;
            break;
        }
    }
    
    if (!isValid) {
        let message = 'Invalid file type. Please select a valid file.';
        
        // Add specific messages for proof of address documents
        if (inputId === 'proof_of_address_1') {
            message = 'Invalid file type. For Proof of Address 1, please upload:\n• Driving License\n• Bank Statement\n• Utility Bill\n\nAccepted formats: JPEG, PNG, PDF';
        } else if (inputId === 'proof_of_address_2') {
            message = 'Invalid file type. For Proof of Address 2, please upload:\n• Bank Statement\n• Utility Bill\n\nAccepted formats: JPEG, PNG, PDF';
        }
        
        alert(message);
        return false;
    }
    
    return true;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Enhanced document management functions
function showReplaceUpload(documentType) {
    // Hide existing document container
    const existingContainer = document.getElementById(documentType + '-existing');
    const uploadArea = document.getElementById(documentType + '-upload');
    
    if (existingContainer && uploadArea) {
        existingContainer.classList.add('d-none');
        uploadArea.classList.remove('d-none');
        
        // Clear any previous file selection state
        uploadArea.classList.remove('file-selected');
        const progressDiv = uploadArea.querySelector('.upload-progress');
        if (progressDiv) {
            progressDiv.remove();
        }
        
        // Update upload text to indicate replacement
        const uploadContent = uploadArea.querySelector('.upload-content');
        if (uploadContent) {
            const uploadText = uploadContent.querySelector('p');
            if (uploadText) {
                uploadText.textContent = 'Click to upload replacement file';
            }
        }
        
        // Add cancel button to go back to existing document
        const uploadContentDiv = uploadArea.querySelector('.upload-content');
        if (uploadContentDiv && !uploadContentDiv.querySelector('.cancel-replace-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-sm btn-outline-secondary mt-2 cancel-replace-btn';
            cancelBtn.innerHTML = '<i class="fas fa-arrow-left me-1"></i>Keep Current';
            cancelBtn.onclick = (e) => {
                e.stopPropagation(); // Prevent triggering the upload area click
                cancelReplace(documentType);
            };
            uploadContentDiv.appendChild(cancelBtn);
        }
    }
}

function cancelReplace(documentType) {
    // Show existing document container
    const existingContainer = document.getElementById(documentType + '-existing');
    const uploadArea = document.getElementById(documentType + '-upload');
    
    if (existingContainer && uploadArea) {
        existingContainer.classList.remove('d-none');
        uploadArea.classList.add('d-none');
        
        // Clear any selected file
        const fileInput = uploadArea.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.value = '';
        }
        
        // Reset upload area state
        uploadArea.classList.remove('file-selected');
        const progressDiv = uploadArea.querySelector('.upload-progress');
        if (progressDiv) {
            progressDiv.remove();
        }
        
        // Remove cancel button
        const cancelBtn = uploadArea.querySelector('.cancel-replace-btn');
        if (cancelBtn) {
            cancelBtn.remove();
        }
    }
}

function removeDocument(documentType, officerId) {
    if (!confirm('Are you sure you want to remove this document? This action cannot be undone.')) {
        return;
    }
    
    // Show loading state
    const existingContainer = document.getElementById(documentType + '-existing');
    const uploadArea = document.getElementById(documentType + '-upload');
    
    if (existingContainer) {
        existingContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0">Removing document...</p></div>';
    }
    
    // Send AJAX request to remove document
    fetch('../api/remove_document.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            officer_id: officerId,
            document_type: documentType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide existing container and show upload area
            if (existingContainer) {
                existingContainer.classList.add('d-none');
            }
            if (uploadArea) {
                uploadArea.classList.remove('d-none');
                // Reset upload text
                const uploadContent = uploadArea.querySelector('.upload-content p');
                if (uploadContent) {
                    uploadContent.textContent = uploadContent.textContent.replace('replacement ', '');
                }
            }
            
            // Show success message
            showNotification('Document removed successfully', 'success');
        } else {
            // Show error message and restore original state
            showNotification('Failed to remove document: ' + (data.message || 'Unknown error'), 'error');
            location.reload(); // Reload to restore original state
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while removing the document', 'error');
        location.reload(); // Reload to restore original state
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1055; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Full-screen document viewer functionality
function viewDocumentInModal(documentType, officerId, documentName) {
    const viewer = document.getElementById('documentViewer');
    const viewerTitle = document.getElementById('documentViewerTitle');
    const viewerContainer = document.getElementById('documentViewerContainer');
    const documentInfo = document.getElementById('documentViewerInfo');
    const downloadBtn = document.getElementById('downloadViewerBtn');
    const openBtn = document.getElementById('openViewerBtn');
    
    // Update viewer title
    const documentLabels = {
        'passport': 'Proof Of Photo ID - Passport',
        'full_body_photo': 'Full Body Picture In Uniform',
        'sia_badge_front': 'SIA Badge Front',
        'sia_badge_back': 'SIA Badge Back',
        'proof_of_address_1': 'Proof Of Address 1',
        'proof_of_address_2': 'Proof Of Address 2',
        'brp_card': 'BRP Card',
        'visa_share_code_screenshot': 'Visa Share Code Screenshot'
    };
    
    viewerTitle.innerHTML = `<i class="fas fa-file-image me-2"></i>${documentLabels[documentType] || 'Document Preview'}`;
    
    // Show loading state
    viewerContainer.innerHTML = `
        <div class="loading-viewer-spinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading document...</p>
        </div>
    `;
    
    // Update document info
    documentInfo.textContent = documentName;
    
    // Set up action buttons
    const documentUrl = `../api/view_document.php?officer_id=${officerId}&type=${documentType}`;
    const downloadUrl = `../api/view_document.php?officer_id=${officerId}&type=${documentType}&download=1`;
    
    downloadBtn.href = downloadUrl;
    openBtn.href = documentUrl;
    
    // Show viewer
    viewer.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Load document content
    loadDocumentInViewer(documentUrl, documentName, viewerContainer);
    
    // Add keyboard event listener for ESC key
    document.addEventListener('keydown', handleViewerKeydown);
}

function loadDocumentInViewer(documentUrl, documentName, container) {
    const isPDF = documentName.toLowerCase().endsWith('.pdf');
    const isImage = /\.(jpg|jpeg|png|gif)$/i.test(documentName);
    
    if (isPDF) {
        // For PDF files, show PDF icon and embed
        container.innerHTML = `
            <div class="pdf-viewer-container">
                <i class="fas fa-file-pdf fa-4x pdf-viewer-icon"></i>
                <h5 class="mt-3 mb-3">PDF Document</h5>
                <p class="text-muted mb-4">${documentName}</p>
                <div class="d-grid gap-2 d-md-block">
                    <button onclick="openPDFInViewer('${documentUrl}')" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>View PDF
                    </button>
                    <a href="${documentUrl}" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                    </a>
                </div>
                <div id="pdfViewerFrame" class="mt-4" style="display: none;">
                    <iframe src="" width="100%" height="500" style="border: none; border-radius: 8px;"></iframe>
                </div>
            </div>
        `;
    } else if (isImage) {
        // For images, create image element
        const img = new Image();
        img.onload = function() {
            container.innerHTML = `<img src="${documentUrl}" alt="${documentName}">`;
        };
        img.onerror = function() {
            container.innerHTML = `
                <div class="text-center p-4 text-white">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Failed to load image</h5>
                    <p class="text-muted">The image could not be displayed.</p>
                    <a href="${documentUrl}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                    </a>
                </div>
            `;
        };
        img.src = documentUrl;
    } else {
        // For other file types, show generic preview
        container.innerHTML = `
            <div class="text-center p-4 text-white">
                <i class="fas fa-file fa-4x text-secondary mb-3"></i>
                <h5>Document Preview</h5>
                <p class="text-muted mb-4">${documentName}</p>
                <a href="${documentUrl}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                </a>
            </div>
        `;
    }
}

function openPDFInViewer(pdfUrl) {
    const pdfFrame = document.getElementById('pdfViewerFrame');
    const iframe = pdfFrame.querySelector('iframe');
    
    iframe.src = pdfUrl;
    pdfFrame.style.display = 'block';
    
    // Scroll to the iframe
    iframe.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeDocumentViewer() {
    const viewer = document.getElementById('documentViewer');
    viewer.classList.remove('show');
    document.body.style.overflow = ''; // Restore background scrolling
    
    // Remove keyboard event listener
    document.removeEventListener('keydown', handleViewerKeydown);
    
    // Clear content after animation
    setTimeout(() => {
        const container = document.getElementById('documentViewerContainer');
        if (container) {
            container.innerHTML = '';
        }
    }, 300);
}

function handleViewerKeydown(event) {
    if (event.key === 'Escape') {
        closeDocumentViewer();
    }
}
</script>
