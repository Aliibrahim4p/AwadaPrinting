<?php
require_once __DIR__ . '/../config/helpers.php';

/**
 * Fetch all stock items (no quantity filter)
 */
function fetchStock($search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 1000, $page = 1): array
{
    $allowedSort = ['id', 'name', 'unit_of_measure', 'quantity_on_hand'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);

    return fetch_entities(
        'stock',
        $search,
        $allowedSort,
        $sortColumn,
        $sortDir,
        (int) $limit,
        (int) $page,
        '1=1' // no filter
    );
}

/**
 * Count all stock items (no quantity filter)
 */
function countStock($search = ''): int
{
    return count_entities('stock', $search, '1=1');
}

// API endpoint
if (isset($_GET['api'])) {
    $page = (int) param($_GET, 'page', 1);
    $limit = (int) param($_GET, 'limit', 1000);
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'ASC');
    $search = (string) param($_GET, 'search', '');

    $stock = fetchStock($search, $sortColumn, $sortDir, $limit, $page);
    $total = countStock($search);

    json_response([
        'data' => $stock,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}