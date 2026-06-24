<?php

function subcontractorsHasCompanyId(PDO $conn) {
    static $has_company_id = null;

    if ($has_company_id !== null) {
        return $has_company_id;
    }

    try {
        $stmt = $conn->query("SHOW COLUMNS FROM subcontractors LIKE 'company_id'");
        $has_company_id = $stmt && $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $has_company_id = false;
    }

    return $has_company_id;
}

function buildSubcontractorCompanyClause(PDO $conn, $alias = '') {
    if (!subcontractorsHasCompanyId($conn) || isSuperAdmin()) {
        return ['', []];
    }

    $company_id = getCurrentCompanyId();
    if (!$company_id) {
        return ['', []];
    }

    $column = $alias ? "$alias.company_id" : 'company_id';
    return [" AND $column = ?", [$company_id]];
}

function createSubcontractor(PDO $conn, array $data) {
    $name = trim($data['name'] ?? '');
    $email = trim($data['contact_email'] ?? '');
    $phone = trim($data['contact_phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($name === '') {
        throw new Exception('Subcontractor name is required.');
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid subcontractor email address.');
    }

    if (subcontractorsHasCompanyId($conn)) {
        $stmt = $conn->prepare("
            INSERT INTO subcontractors (name, contact_email, contact_phone, address, company_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email ?: null, $phone ?: null, $address ?: null, getCurrentCompanyId()]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO subcontractors (name, contact_email, contact_phone, address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email ?: null, $phone ?: null, $address ?: null]);
    }

    return $conn->lastInsertId();
}

function resolveOfficerSubcontractorId(PDO $conn, array $post) {
    if (!empty($post['new_subcontractor_name'])) {
        return createSubcontractor($conn, [
            'name' => $post['new_subcontractor_name'],
            'contact_email' => $post['new_subcontractor_email'] ?? '',
            'contact_phone' => $post['new_subcontractor_phone'] ?? '',
            'address' => $post['new_subcontractor_address'] ?? ''
        ]);
    }

    return !empty($post['subcontractor_id']) ? $post['subcontractor_id'] : null;
}
