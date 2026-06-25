<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

requireActiveSubscriptionAPI();

try {
    $db = new Database();
    $conn = $db->getConnection();

    $use_company_filter = false;
    $company_id = null;

    try {
        $column_check = $conn->query("SHOW COLUMNS FROM officers LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        $use_company_filter = false;
    }

    $query = trim($_GET['q'] ?? '');
    $limit = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 50) : 20;
    $selected_id = isset($_GET['selected_id']) && $_GET['selected_id'] !== '' ? (int)$_GET['selected_id'] : null;

    $base_where = "employment_status != 'Inactive'";
    $base_params = [];

    if ($use_company_filter && $company_id) {
        $base_where .= " AND company_id = ?";
        $base_params[] = $company_id;
    }

    if ($selected_id && strlen($query) < 2) {
        $stmt = $conn->prepare("
            SELECT id, staff_id, first_name, last_name, phone
            FROM officers
            WHERE {$base_where} AND id = ?
            LIMIT 1
        ");
        $stmt->execute(array_merge($base_params, [$selected_id]));
        $officer = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'officers' => $officer ? [$officer] : [],
            'query' => $query,
            'count' => $officer ? 1 : 0
        ]);
        exit;
    }

    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'officers' => [], 'query' => $query, 'count' => 0]);
        exit;
    }

    $sql = "
        SELECT id, staff_id, first_name, last_name, phone
        FROM officers
        WHERE {$base_where}
        AND (
            LOWER(first_name) LIKE LOWER(?)
            OR LOWER(last_name) LIKE LOWER(?)
            OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?)
            OR LOWER(staff_id) LIKE LOWER(?)
            OR LOWER(phone) LIKE LOWER(?)
        )
        ORDER BY
            CASE
                WHEN LOWER(CONCAT(first_name, ' ', last_name)) = LOWER(?) THEN 1
                WHEN LOWER(staff_id) = LOWER(?) THEN 2
                WHEN LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?) THEN 3
                WHEN LOWER(first_name) LIKE LOWER(?) THEN 4
                WHEN LOWER(last_name) LIKE LOWER(?) THEN 5
                ELSE 6
            END,
            first_name,
            last_name
        LIMIT {$limit}
    ";

    $contains = '%' . $query . '%';
    $starts = $query . '%';
    $params = array_merge($base_params, [
        $contains,
        $contains,
        $contains,
        $contains,
        $contains,
        $query,
        $query,
        $starts,
        $starts,
        $starts
    ]);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'officers' => $officers,
        'query' => $query,
        'count' => count($officers)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
