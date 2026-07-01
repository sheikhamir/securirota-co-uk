<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../includes/subcontractor_helper.php';

if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                createSubcontractor($conn, $_POST);
                $_SESSION['success_message'] = 'Subcontractor created successfully.';
                break;

            case 'update':
                $id = $_POST['subcontractor_id'] ?? null;
                if (!$id) {
                    throw new Exception('Subcontractor not found.');
                }

                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['contact_email'] ?? '');
                $phone = trim($_POST['contact_phone'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if ($name === '') {
                    throw new Exception('Subcontractor name is required.');
                }

                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Please enter a valid email address.');
                }

                $sql = "
                    UPDATE subcontractors
                    SET name = ?, contact_email = ?, contact_phone = ?, address = ?
                    WHERE id = ?
                ";
                $params = [$name, $email ?: null, $phone ?: null, $address ?: null, $id];

                [$company_clause, $company_params] = buildSubcontractorCompanyClause($conn);
                $sql .= $company_clause;
                $params = array_merge($params, $company_params);

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Subcontractor updated successfully.';
                break;

            case 'delete':
                $id = $_POST['subcontractor_id'] ?? null;
                if (!$id) {
                    throw new Exception('Subcontractor not found.');
                }

                $usage_sql = "SELECT COUNT(*) FROM officers WHERE subcontractor_id = ?";
                $usage_params = [$id];

                [$company_clause, $company_params] = buildSubcontractorCompanyClause($conn);
                $usage_sql .= $company_clause;
                $usage_params = array_merge($usage_params, $company_params);

                $usage_stmt = $conn->prepare($usage_sql);
                $usage_stmt->execute($usage_params);
                $assigned_staff = (int) $usage_stmt->fetchColumn();

                if ($assigned_staff > 0) {
                    $_SESSION['error_message'] = "Cannot delete subcontractor: assigned to $assigned_staff staff record(s).";
                    break;
                }

                $sql = "DELETE FROM subcontractors WHERE id = ?";
                $params = [$id];

                [$company_clause, $company_params] = buildSubcontractorCompanyClause($conn);
                $sql .= $company_clause;
                $params = array_merge($params, $company_params);

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Subcontractor deleted successfully.';
                break;
        }

        ob_clean();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "
        SELECT s.*,
               COALESCE(staff_counts.assigned_staff, 0) as assigned_staff
        FROM subcontractors s
        LEFT JOIN (
            SELECT subcontractor_id, COUNT(*) as assigned_staff
            FROM officers
            WHERE subcontractor_id IS NOT NULL
    ";

    [$officer_company_clause, $officer_company_params] = buildSubcontractorCompanyClause($conn);
    $sql .= $officer_company_clause;
    $params = $officer_company_params;

    $sql .= "
            GROUP BY subcontractor_id
        ) staff_counts ON staff_counts.subcontractor_id = s.id
        WHERE 1=1
    ";

    [$company_clause, $company_params] = buildSubcontractorCompanyClause($conn, 's');
    $sql .= $company_clause;
    $params = array_merge($params, $company_params);
    $sql .= " ORDER BY s.name";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $subcontractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    $subcontractors = [];
}

ob_end_clean();
$page_title = 'Subcontractors';
require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-building-user me-2"></i>Subcontractors</h2>
            <p class="text-muted mb-0">Manage subcontractor companies used in staff records.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="showSubcontractorModal()">
            <i class="fas fa-plus me-1"></i>Add Subcontractor
        </button>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo safe_html($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo safe_html($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="subcontractorsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Staff</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcontractors as $subcontractor): ?>
                            <tr>
                                <td><?php echo safe_html($subcontractor['name']); ?></td>
                                <td><?php echo safe_html($subcontractor['contact_email'] ?? ''); ?></td>
                                <td><?php echo safe_html($subcontractor['contact_phone'] ?? ''); ?></td>
                                <td><?php echo safe_html($subcontractor['address'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo (int) $subcontractor['assigned_staff']; ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick='editSubcontractor(<?php echo json_encode($subcontractor, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick='deleteSubcontractor(<?php echo (int) $subcontractor['id']; ?>, <?php echo json_encode($subcontractor['name']); ?>)'>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="subcontractorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subcontractorModalTitle">Add Subcontractor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="subcontractorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="subcontractorAction" value="create">
                    <input type="hidden" name="subcontractor_id" id="subcontractorId">

                    <div class="mb-3">
                        <label for="subcontractorName" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subcontractorName" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="subcontractorEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="subcontractorEmail" name="contact_email">
                    </div>

                    <div class="mb-3">
                        <label for="subcontractorPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="subcontractorPhone" name="contact_phone">
                    </div>

                    <div class="mb-3">
                        <label for="subcontractorAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="subcontractorAddress" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="subcontractorSubmit">Create Subcontractor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSubcontractorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete subcontractor "<span id="deleteSubcontractorName"></span>"?</p>
                <p class="text-danger mb-0"><strong>Warning:</strong> This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="subcontractor_id" id="deleteSubcontractorId">
                    <button type="submit" class="btn btn-danger">Delete Subcontractor</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showSubcontractorModal() {
    document.getElementById('subcontractorModalTitle').textContent = 'Add Subcontractor';
    document.getElementById('subcontractorAction').value = 'create';
    document.getElementById('subcontractorSubmit').textContent = 'Create Subcontractor';
    document.getElementById('subcontractorForm').reset();
    document.getElementById('subcontractorId').value = '';
    new bootstrap.Modal(document.getElementById('subcontractorModal')).show();
}

function editSubcontractor(subcontractor) {
    document.getElementById('subcontractorModalTitle').textContent = 'Edit Subcontractor';
    document.getElementById('subcontractorAction').value = 'update';
    document.getElementById('subcontractorSubmit').textContent = 'Update Subcontractor';
    document.getElementById('subcontractorId').value = subcontractor.id;
    document.getElementById('subcontractorName').value = subcontractor.name || '';
    document.getElementById('subcontractorEmail').value = subcontractor.contact_email || '';
    document.getElementById('subcontractorPhone').value = subcontractor.contact_phone || '';
    document.getElementById('subcontractorAddress').value = subcontractor.address || '';
    new bootstrap.Modal(document.getElementById('subcontractorModal')).show();
}

function deleteSubcontractor(id, name) {
    document.getElementById('deleteSubcontractorId').value = id;
    document.getElementById('deleteSubcontractorName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteSubcontractorModal')).show();
}

$(document).ready(function() {
    $('#subcontractorsTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
