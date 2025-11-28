<?php
require_once __DIR__ . '/../config/helpers.php';

function fetchSuppliers($search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 20, $page = 1): array
{
    $allowedSort = ['id', 'name'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);
    return fetch_entities('suppliers',  $search, $allowedSort, $sortColumn, $sortDir, (int)$limit, (int)$page, 'is_active = TRUE');
}

function countSuppliers($search = ''): int
{
    return count_entities('suppliers',  $search, 'is_active = TRUE');
}

// API endpoint
if (isset($_GET['api'])) {
    $page = (int) (param($_GET, 'page', 1));
    $limit = (int) (param($_GET, 'limit', 20));
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'ASC');
    $search = (string) param($_GET, 'search', '');

    $suppliers = fetchSuppliers($search, $sortColumn, $sortDir, $limit, $page);
    $total = countSuppliers($search);

    json_response([
        'data' => $suppliers,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}