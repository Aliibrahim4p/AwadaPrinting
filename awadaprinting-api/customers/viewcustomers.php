<?php
require_once '../config/db.php'; // Your PDO connection $pdo

function fetchCustomers($search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 20, $page = 1)
{
    global $pdo;
    if ($page < 1)
        $page = 1;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM customers WHERE is_active = TRUE";

    if (!empty($search)) {
        $sql .= " AND name ILIKE :search";
    }

    // Sanitize sort
    $allowedColumns = ['id', 'name'];
    $sortColumn = in_array($sortColumn, $allowedColumns) ? $sortColumn : 'id';
    $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

    $sql .= " ORDER BY $sortColumn $sortDir LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count total active customers (no LIMIT/OFFSET or ORDER BY)
function countCustomers($search = '')
{
    global $pdo;
    $sql = "SELECT COUNT(*) FROM customers WHERE is_active = TRUE";

    if (!empty($search)) {
        $sql .= " AND name ILIKE :search";
    }

    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

// Optional: return JSON for API calls
if (isset($_GET['api'])) {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $sortColumn = $_GET['sortColumn'] ?? 'id';
    $sortDir = $_GET['sortDir'] ?? 'ASC';
    $search = $_GET['search'] ?? '';

    $data = fetchCustomers($search, $sortColumn, $sortDir, $limit, $page);
    $total = countCustomers($search);

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
    exit;
}