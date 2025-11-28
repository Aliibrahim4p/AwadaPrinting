<?php
require_once __DIR__ . '/../config/helpers.php';

function fetchPurchases(string $search = '', string $sortColumn = 'id', string $sortDir = 'DESC', int $limit = 20, int $page = 1): array
{
    global $pdo;

    // Columns we allow sorting on
    $allowedSort = ['id', 'purchase_date', 'supplier_id', 'total_cost', 'notes'];

    // Columns to search in
    $searchColumns = ['notes', 'total_cost::TEXT'];


    // Date range filter
    $dateFrom = param($_GET, 'dateFrom', '');
    $dateTo = param($_GET, 'dateTo', '');
    $extraWhere = '1=1';

    if ($search) {
        // See if search matches supplier names
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE name ILIKE :name");
        $stmt->execute([':name' => "%{$search}%"]);
        $supplierIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($supplierIds)) {
            $extraWhere .= " AND supplier_id IN (" . implode(',', array_map('intval', $supplierIds)) . ")";
            $search = ''; // disable normal text search
        }

    }

    // Fetch purchases with generic helper
    $purchases = fetch_entities(
        'purchases',
        $search,
        $allowedSort,
        $sortColumn,
        $sortDir,
        $limit,
        $page,
        $extraWhere,
        'purchase_date',
        $dateFrom ?: null,
        $dateTo ?: null
    );
    // Add supplier names efficiently
    if (!empty($purchases)) {
        $supplierIds = array_unique(array_column($purchases, 'supplier_id'));
        $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE id = ANY(:ids)");
        $stmt->bindValue(':ids', '{' . implode(',', $supplierIds) . '}', PDO::PARAM_STR);
        $stmt->execute();
        $suppliers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($purchases as &$p) {
            $p['supplier_name'] = $suppliers[$p['supplier_id']] ?? null;
        }
    }

    return $purchases;
}

function countPurchases(string $search = ''): int
{
    global $pdo;
    $searchColumns = ['notes', 'total_cost::TEXT'];
    $dateFrom = param($_GET, 'dateFrom', '');
    $dateTo = param($_GET, 'dateTo', '');
    $extraWhere = '1=1';

    if ($search) {
        // See if search matches supplier names
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE name ILIKE :name");
        $stmt->execute([':name' => "%{$search}%"]);
        $supplierIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($supplierIds)) {
            $extraWhere .= " AND supplier_id IN (" . implode(',', array_map('intval', $supplierIds)) . ")";
            $search = ''; // disable normal text search
        }

    }
    return count_entities(
        'purchases',
        $search,
        $extraWhere,
        'purchase_date',
        $dateFrom ?: null,
        $dateTo ?: null
    );
}

// API endpoint
if (isset($_GET['api'])) {
    $page = (int) param($_GET, 'page', 1);
    $limit = (int) param($_GET, 'limit', 20);
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'DESC');
    $search = (string) param($_GET, 'search', '');

    $purchases = fetchPurchases($search, $sortColumn, $sortDir, $limit, $page);
    $total = countPurchases($search);

    json_response([
        'data' => $purchases,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}