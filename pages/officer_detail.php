<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$officer_id = $_GET['id'] ?? null;

if (!$officer_id) {
    header('Location: officers.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM officers LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            // Multi-tenant mode active
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // Pre-migration mode, no company filtering
        $use_company_filter = false;
    }
    
    // Get officer details with user information and subcontractor
    $sql = "
        SELECT o.*, u.username, u.email as user_email, u.status as user_status, 
               u.mobile_number, u.pin, u.pin_generated_at,
               s.name as subcontractor_name, s.contact_email as subcontractor_email, 
               s.contact_phone as subcontractor_phone
        FROM officers o 
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN subcontractors s ON o.subcontractor_id = s.id
        WHERE o.id = ?";
    
    $params = [$officer_id];
    
    // SECURITY: Add company filtering to prevent cross-company data access
    if ($use_company_filter && $company_id) {
        $sql .= " AND o.company_id = ?";
        $params[] = $company_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$officer) {
        header('Location: officers.php');
        exit;
    }

$page_title = 'Officer Details';
require_once '../includes/header.php';
    
    // Initialize default values for missing fields
    $officer = array_merge([
        'first_name' => '',
        'last_name' => '',
        'staff_id' => '',
        'date_of_birth' => null,
        'nationality' => '',
        'national_insurance' => '',
        'email' => '',
        'phone' => '',
        'mobile_number' => '',
        'address' => '',
        'address_city' => '',
        'address_postal_code' => '',
        'emergency_contact_name' => '',
        'emergency_contact_phone' => '',
        'employment_status' => '',
        'hourly_rate' => 0,
        'date_started' => null,
        'date_left' => null,
        'subcontractor_name' => '',
        'subcontractor_email' => '',
        'subcontractor_phone' => '',
        'user_status' => '',
        'username' => '',
        'pin' => '',
        'pin_generated_at' => null,
        'sia_badge_number' => '',
        'sia_expiry_date' => null,
        'visa_status' => '',
        'visa_expiry_date' => null,
        'right_to_work_reference' => '',
        'bank_account' => '',
        'sort_code' => '',
        'bank_account_name' => '',
        'bank_roll_number' => '',
        'notes' => '',
        'suspend' => 0
    ], $officer);
    
    // Get shift statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_shifts,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_shifts,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_shifts,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_shifts,
            SUM(CASE WHEN status = 'allocated' THEN 1 ELSE 0 END) as allocated_shifts,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_shifts,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_shifts,
            MIN(shift_date) as first_shift_date,
            MAX(shift_date) as last_shift_date
        FROM shifts 
        WHERE officer_id = ?
    ");
    $stmt->execute([$officer_id]);
    $shift_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Initialize default values for shift stats
    $shift_stats = array_merge([
        'total_shifts' => 0,
        'confirmed_shifts' => 0,
        'in_progress_shifts' => 0,
        'completed_shifts' => 0,
        'allocated_shifts' => 0,
        'declined_shifts' => 0,
        'cancelled_shifts' => 0,
        'first_shift_date' => null,
        'last_shift_date' => null
    ], $shift_stats ?: []);
    
    // Get recent shifts
    $stmt = $conn->prepare("
        SELECT sh.*, si.site_name, si.address as site_address
        FROM shifts sh
        LEFT JOIN sites si ON sh.site_id = si.id
        WHERE sh.officer_id = ?
        ORDER BY sh.shift_date DESC, sh.start_time DESC
        LIMIT 10
    ");
    $stmt->execute([$officer_id]);
    $recent_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure recent_shifts is always an array
    if (!is_array($recent_shifts)) {
        $recent_shifts = [];
    }
    
    // Get officer documents
    require_once '../includes/document_uploader.php';
    $uploader = new DocumentUploader();
    $officer_documents = $uploader->getOfficerDocuments($conn, $officer_id);
    
    // Ensure officer_documents is always an array
    if (!is_array($officer_documents)) {
        $officer_documents = [];
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    // Initialize empty data to prevent further errors
    $officer = [
        'first_name' => '',
        'last_name' => '',
        'staff_id' => '',
        'date_of_birth' => null,
        'nationality' => '',
        'national_insurance' => '',
        'email' => '',
        'phone' => '',
        'mobile_number' => '',
        'address' => '',
        'address_city' => '',
        'address_postal_code' => '',
        'emergency_contact_name' => '',
        'emergency_contact_phone' => '',
        'employment_status' => '',
        'hourly_rate' => 0,
        'date_started' => null,
        'date_left' => null,
        'subcontractor_name' => '',
        'subcontractor_email' => '',
        'subcontractor_phone' => '',
        'user_status' => '',
        'username' => '',
        'pin' => '',
        'pin_generated_at' => null,
        'sia_badge_number' => '',
        'sia_expiry_date' => null,
        'visa_status' => '',
        'visa_expiry_date' => null,
        'right_to_work_reference' => '',
        'bank_account' => '',
        'sort_code' => '',
        'bank_account_name' => '',
        'bank_roll_number' => '',
        'notes' => '',
        'suspend' => 0
    ];
    $shift_stats = [
        'total_shifts' => 0,
        'confirmed_shifts' => 0,
        'in_progress_shifts' => 0,
        'completed_shifts' => 0,
        'allocated_shifts' => 0,
        'declined_shifts' => 0,
        'cancelled_shifts' => 0,
        'first_shift_date' => null,
        'last_shift_date' => null
    ];
    $recent_shifts = [];
    $officer_documents = [];
}
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="officers.php" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Back to Officers
            </a>
            <h4 class="mb-0">
                <i class="fas fa-user me-2 text-primary"></i>Officer Details
            </h4>
        </div>
        <div class="btn-group">
            <a href="officer_form.php?id=<?php echo $officer['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Officer
            </a>
            <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Actions
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="generatePIN(<?php echo $officer['id']; ?>)">
                    <i class="fas fa-key me-2"></i>Generate New PIN
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteOfficer(<?php echo $officer['id']; ?>)">
                    <i class="fas fa-trash me-2"></i>Delete Officer
                </a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Personal Information Card -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-4">
                                <div class="me-3" style="width: 80px; height: 80px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.8em;">
                                    <?php echo strtoupper(substr($officer['first_name'], 0, 1) . substr($officer['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h4 class="mb-1"><?php echo safe_html($officer['first_name'] . ' ' . $officer['last_name']); ?></h4>
                                    <p class="text-muted mb-1">Staff ID: <span class="badge bg-primary"><?php echo safe_html($officer['staff_id']); ?></span></p>
                                    <?php if ($officer['suspend']): ?>
                                        <span class="badge bg-warning text-dark">SUSPENDED</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td><?php echo $officer['date_of_birth'] ? date('d/m/Y', strtotime($officer['date_of_birth'])) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Nationality:</strong></td>
                                    <td><?php echo safe_html($officer['nationality']) ?: 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>National Insurance:</strong></td>
                                    <td><?php echo safe_html($officer['national_insurance']) ?: 'Not provided'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-address-card me-2"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Primary Contact</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><i class="fas fa-envelope text-primary me-2"></i><strong>Email:</strong></td>
                                    <td><?php echo safe_html($officer['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-phone text-success me-2"></i><strong>Phone:</strong></td>
                                    <td><?php echo safe_html($officer['phone']); ?></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-mobile-alt text-info me-2"></i><strong>Mobile:</strong></td>
                                    <td><?php echo safe_html($officer['mobile_number']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Address</h6>
                            <address>
                                <?php if ($officer['address']): ?>
                                    <?php echo nl2br(safe_html($officer['address'])); ?><br>
                                <?php endif; ?>
                                <?php if ($officer['address_city']): ?>
                                    <?php echo safe_html($officer['address_city']); ?><br>
                                <?php endif; ?>
                                <?php if ($officer['address_postal_code']): ?>
                                    <?php echo safe_html($officer['address_postal_code']); ?>
                                <?php endif; ?>
                            </address>
                        </div>
                    </div>
                    
                    <?php if ($officer['emergency_contact_name'] || $officer['emergency_contact_phone']): ?>
                    <hr>
                    <h6>Emergency Contact</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Name:</strong> <?php echo safe_html($officer['emergency_contact_name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong> <?php echo safe_html($officer['emergency_contact_phone']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Employment Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-briefcase me-2"></i>Employment Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Employment Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $officer['employment_status'] === 'Full-time' ? 'success' : ($officer['employment_status'] === 'Part-time' ? 'info' : 'secondary'); ?>">
                                            <?php echo safe_html($officer['employment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Hourly Rate:</strong></td>
                                    <td><strong class="text-success">£<?php echo number_format($officer['hourly_rate'] ?? 0, 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Date Started:</strong></td>
                                    <td><?php echo $officer['date_started'] ? date('d/m/Y', strtotime($officer['date_started'])) : 'Not provided'; ?></td>
                                </tr>
                                <?php if ($officer['date_left']): ?>
                                <tr>
                                    <td><strong>Date Left:</strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($officer['date_left'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <?php if ($officer['subcontractor_name']): ?>
                            <h6>Subcontractor</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Company:</strong></td>
                                    <td><?php echo safe_html($officer['subcontractor_name']); ?></td>
                                </tr>
                                <?php if ($officer['subcontractor_email']): ?>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo safe_html($officer['subcontractor_email']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($officer['subcontractor_phone']): ?>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo safe_html($officer['subcontractor_phone']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-upload me-2"></i>Uploaded Documents
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($officer_documents)): ?>
                        <div class="row g-3">
                            <?php 
                            $document_labels = [
                                'passport' => ['label' => 'Proof Of Photo ID - Passport', 'icon' => 'fas fa-passport'],
                                'full_body_photo' => ['label' => 'Full Body Picture In Uniform', 'icon' => 'fas fa-user-circle'],
                                'sia_badge_front' => ['label' => 'SIA Badge Front', 'icon' => 'fas fa-id-card'],
                                'sia_badge_back' => ['label' => 'SIA Badge Back', 'icon' => 'fas fa-id-card'],
                                'proof_of_address_1' => ['label' => 'Proof Of Address 1', 'icon' => 'fas fa-home'],
                                'proof_of_address_2' => ['label' => 'Proof Of Address 2', 'icon' => 'fas fa-home'],
                                'brp_card' => ['label' => 'BRP Card', 'icon' => 'fas fa-passport'],
                                'visa_share_code_screenshot' => ['label' => 'Visa Share Code Screenshot', 'icon' => 'fas fa-mobile-alt']
                            ];
                            
                            foreach ($officer_documents as $doc): 
                                $doc_info = $document_labels[$doc['document_type']] ?? ['label' => ucfirst(str_replace('_', ' ', $doc['document_type'])), 'icon' => 'fas fa-file'];
                                $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['document_name']);
                                $is_pdf = preg_match('/\.pdf$/i', $doc['document_name']);
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <!-- Document Preview -->
                                        <div class="document-preview-container position-relative" style="height: 200px; overflow: hidden;">
                                            <?php if ($is_image): ?>
                                                <img src="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=<?php echo $doc['document_type']; ?>" 
                                                     alt="<?php echo $doc_info['label']; ?>" 
                                                     class="w-100 h-100" 
                                                     style="object-fit: cover; transition: transform 0.3s ease;"
                                                     onmouseover="this.style.transform='scale(1.05)'"
                                                     onmouseout="this.style.transform='scale(1)'">
                                            <?php elseif ($is_pdf): ?>
                                                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                    <div class="text-center">
                                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                                        <p class="small text-muted mb-0">PDF Document</p>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                    <div class="text-center">
                                                        <i class="<?php echo $doc_info['icon']; ?> fa-3x text-primary mb-2"></i>
                                                        <p class="small text-muted mb-0">Document</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Overlay for actions -->
                                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                                 style="background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.3s ease;"
                                                 onmouseover="this.style.opacity='1'"
                                                 onmouseout="this.style.opacity='0'">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-light btn-sm" 
                                                            onclick="viewDocumentInModal('<?php echo $doc['document_type']; ?>', <?php echo $officer_id; ?>, '<?php echo addslashes($doc['document_name']); ?>')">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </button>
                                                    <a href="../api/view_document.php?officer_id=<?php echo $officer_id; ?>&type=<?php echo $doc['document_type']; ?>&download=1" 
                                                       class="btn btn-light btn-sm">
                                                        <i class="fas fa-download me-1"></i>Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Document Info -->
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <i class="<?php echo $doc_info['icon']; ?> fa-lg text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title mb-1"><?php echo $doc_info['label']; ?></h6>
                                                    <p class="small text-muted mb-2"><?php echo safe_html($doc['document_name']); ?></p>
                                                    <p class="small text-muted mb-0">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Uploaded: <?php echo date('d/m/Y H:i', strtotime($doc['updated_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-upload fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No documents uploaded yet</h6>
                            <p class="small text-muted mb-3">Upload documents by editing this officer's profile</p>
                            <a href="officer_form.php?id=<?php echo $officer_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload me-1"></i>Upload Documents
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Shift Statistics Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Shift Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($shift_stats['total_shifts'] > 0): ?>
                    <div class="row text-center mb-4">
                        <div class="col-md-2">
                            <div class="bg-primary text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['total_shifts']; ?></h4>
                                <small>Total Shifts</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-success text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['completed_shifts']; ?></h4>
                                <small>Completed</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-info text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['confirmed_shifts']; ?></h4>
                                <small>Confirmed</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-cyan text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['in_progress_shifts'] ?? 0; ?></h4>
                                <small>In Progress</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-warning text-dark rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['allocated_shifts']; ?></h4>
                                <small>Allocated</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-danger text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['declined_shifts']; ?></h4>
                                <small>Declined</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-secondary text-white rounded p-3">
                                <h4 class="mb-1"><?php echo $shift_stats['cancelled_shifts']; ?></h4>
                                <small>Cancelled</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <strong>First Shift:</strong> <?php echo $shift_stats['first_shift_date'] ? date('d/m/Y', strtotime($shift_stats['first_shift_date'])) : 'N/A'; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Shift:</strong> <?php echo $shift_stats['last_shift_date'] ? date('d/m/Y', strtotime($shift_stats['last_shift_date'])) : 'N/A'; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar fa-3x mb-3"></i>
                        <p>No shifts assigned yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Shifts Card -->
            <?php if (!empty($recent_shifts)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Shifts
                    </h5>
                    <!-- <a href="rota.php?officer_id=<?php echo $officer['id']; ?>" class="btn btn-sm btn-outline-primary">View All</a> -->
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Site</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_shifts as $shift): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($shift['shift_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($shift['start_time'])) . ' - ' . date('H:i', strtotime($shift['end_time'])); ?></td>
                                <td>
                                    <div><?php echo safe_html($shift['site_name']); ?></div>
                                    <div>
                                        <a href="<?php echo BASE_URL . 'pages/site_detail.php?id=' . $shift['site_id']; ?>" target="_blank">View Site</a>
                                        <a href="<?php echo BASE_URL . 'pages/rota.php?site_filter=' . $shift['site_id'] . '&week=' . $shift['shift_date']; ?>" target="_blank">View Shift in Rota</a>
                                    </div>
                                    <small class="text-muted"><?php echo safe_html($shift['site_address']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $shift['status'] === 'completed' ? 'success' : 
                                             ($shift['status'] === 'in_progress' ? 'info' :
                                             ($shift['status'] === 'confirmed' ? 'primary' : 
                                              ($shift['status'] === 'allocated' ? 'warning' : 
                                               ($shift['status'] === 'declined' ? 'danger' : 'secondary')))); ?>">
                                        <?php echo ucfirst($shift['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Account Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-check me-2"></i>Account Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Login Status:</strong>
                        <?php if ($officer['user_status'] === 'active'): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">
                                <i class="fas fa-times"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($officer['username']): ?>
                    <div class="mb-3">
                        <strong>Username:</strong> <?php echo safe_html($officer['username']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($officer['pin']): ?>
                    <div class="mb-3">
                        <strong>Current PIN:</strong> 
                        <span class="badge bg-info"><?php echo safe_html($officer['pin']); ?></span>
                        <?php if ($officer['pin_generated_at']): ?>
                            <br><small class="text-muted">Generated: <?php echo date('d/m/Y H:i', strtotime($officer['pin_generated_at'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($officer['pin_generated_at']): ?>
                    <div class="mb-3">
                        <strong>PIN Generated:</strong><br>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($officer['pin_generated_at'])); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documentation Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-id-card me-2"></i>Documentation
                    </h5>
                </div>
                <div class="card-body">
                    <!-- SIA Information -->
                    <div class="mb-4">
                        <h6>SIA License</h6>
                        <?php if ($officer['sia_badge_number']): ?>
                            <div class="mb-2">
                                <strong>Badge Number:</strong> <?php echo safe_html($officer['sia_badge_number']); ?>
                            </div>
                            <?php if ($officer['sia_expiry_date']): ?>
                                <div class="mb-2">
                                    <strong>Expiry Date:</strong> 
                                    <span class="<?php echo strtotime($officer['sia_expiry_date']) < strtotime('+30 days') ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('d/m/Y', strtotime($officer['sia_expiry_date'])); ?>
                                        <?php if (strtotime($officer['sia_expiry_date']) < strtotime('+30 days')): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No SIA license on file</p>
                        <?php endif; ?>
                    </div>

                    <!-- Visa Information -->
                    <?php if ($officer['visa_status'] && $officer['visa_status'] !== 'British'): ?>
                    <div class="mb-4">
                        <h6>Visa Status</h6>
                        <div class="mb-2">
                            <strong>Status:</strong> 
                            <span class="badge bg-info"><?php echo safe_html($officer['visa_status']); ?></span>
                        </div>
                        <?php if ($officer['visa_expiry_date']): ?>
                            <div class="mb-2">
                                <strong>Expiry Date:</strong>
                                <span class="<?php echo strtotime($officer['visa_expiry_date']) < strtotime('+60 days') ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo date('d/m/Y', strtotime($officer['visa_expiry_date'])); ?>
                                    <?php if (strtotime($officer['visa_expiry_date']) < strtotime('+60 days')): ?>
                                        <i class="fas fa-exclamation-triangle ms-1"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Right to Work -->
                    <?php if ($officer['right_to_work_reference']): ?>
                    <div class="mb-4">
                        <h6>Right to Work</h6>
                        <p><strong>Reference:</strong> <?php echo safe_html($officer['right_to_work_reference']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Banking Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-university me-2"></i>Banking Information
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($officer['bank_account'] && $officer['sort_code']): ?>
                        <div class="mb-2">
                            <strong>Account Name:</strong> <?php echo safe_html($officer['bank_account_name']) ?: 'Not provided'; ?>
                        </div>
                        <div class="mb-2">
                            <strong>Account Number:</strong> ****<?php echo substr($officer['bank_account'], -4); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Sort Code:</strong> <?php echo safe_html($officer['sort_code']); ?>
                        </div>
                        <?php if ($officer['bank_roll_number']): ?>
                        <div class="mb-2">
                            <strong>Roll Number:</strong> <?php echo safe_html($officer['bank_roll_number']); ?>
                        </div>
                        <?php endif; ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check"></i> Complete
                        </span>
                    <?php else: ?>
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Banking details incomplete
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes Card -->
            <?php if ($officer['notes']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Notes
                    </h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(safe_html($officer['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function generatePIN(id) {
    if (confirm('Generate a new PIN for this officer? This will invalidate their current PIN.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'officers.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="generate_pin">
            <input type="hidden" name="officer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteOfficer(id) {
    if (confirm('Are you sure you want to delete this officer? This action cannot be undone and will also delete their user account.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'officers.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="officer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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

<!-- Full-Screen Document Viewer -->
<div id="documentViewer" class="document-viewer">
    <div class="document-viewer-backdrop" onclick="closeDocumentViewer()"></div>
    <div class="document-viewer-content">
        <div class="document-viewer-header">
            <h5 class="document-viewer-title" id="documentViewerTitle">Document Preview</h5>
            <button type="button" class="document-viewer-close" onclick="closeDocumentViewer()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="document-viewer-body">
            <div id="documentViewerContainer">
                <!-- Document content will be loaded here -->
            </div>
            <div class="document-viewer-info">
                <small class="text-white-50" id="documentViewerInfo">Loading document...</small>
            </div>
        </div>
        <div class="document-viewer-footer">
            <a href="#" id="downloadViewerBtn" class="btn btn-success" download>
                <i class="fas fa-download me-2"></i>Download
            </a>
            <a href="#" id="openViewerBtn" class="btn btn-primary" target="_blank">
                <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
            </a>
            <button type="button" class="btn btn-secondary" onclick="closeDocumentViewer()">Close</button>
        </div>
    </div>
</div>
    </div>
</div>

<style>
/* Full-Screen Document Viewer Styling */
.document-viewer {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.document-viewer.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.document-viewer-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(8px);
    cursor: pointer;
}

.document-viewer-content {
    position: relative;
    width: 95%;
    height: 95%;
    max-width: 1200px;
    max-height: 90vh;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 10000;
}

.document-viewer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.document-viewer-title {
    color: white;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.document-viewer-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.document-viewer-close:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
}

.document-viewer-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    overflow: auto;
}

.document-viewer-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 300px;
}

.document-viewer-container img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.document-viewer-info {
    margin-top: 1rem;
    text-align: center;
}

.document-viewer-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-wrap: wrap;
    justify-content: center;
}

.loading-viewer-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    padding: 3rem;
}

.loading-viewer-spinner .spinner-border {
    width: 3rem;
    height: 3rem;
    border-color: rgba(255, 255, 255, 0.3);
    border-top-color: white;
}

.pdf-viewer-container {
    text-align: center;
    padding: 2rem;
    color: white;
}

.pdf-viewer-icon {
    color: #dc3545;
    margin-bottom: 1rem;
}

/* Responsive Design for Document Viewer */
@media (max-width: 768px) {
    .document-viewer-content {
        width: 98%;
        height: 98%;
        border-radius: 12px;
    }
    
    .document-viewer-header {
        padding: 0.75rem 1rem;
    }
    
    .document-viewer-title {
        font-size: 1rem;
    }
    
    .document-viewer-body {
        padding: 1rem;
    }
    
    .document-viewer-footer {
        padding: 0.75rem 1rem;
        gap: 0.5rem;
    }
    
    .document-viewer-footer .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 480px) {
    .document-viewer-content {
        width: 100%;
        height: 100%;
        border-radius: 0;
    }
    
    .document-viewer-footer {
        flex-direction: column;
    }
    
    .document-viewer-footer .btn {
        width: 100%;
    }
}

.document-info {
    display: flex;
    align-items: center;
}

.document-actions {
    display: flex;
    align-items: center;
}

.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    flex-direction: column;
}

.loading-spinner .spinner-border {
    color: #667eea;
}

/* Responsive modal */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    #documentPreviewContainer {
        min-height: 300px;
    }
    
    #documentPreviewContainer img {
        max-height: 50vh;
    }
    
    .modal-footer {
        flex-direction: column;
        gap: 1rem;
    }
    
    .document-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
