<?php
require_once __DIR__ . '/../config/helpers.php';

// Wrappers to keep compatibility while using generic helpers
function fetchCustomers($search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 20, $page = 1): array
{
    $allowedSort = ['id', 'name'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);
    return fetch_entities('customers' ,$search, $allowedSort, $sortColumn, $sortDir, (int)$limit, (int)$page, 'is_active = TRUE');
}

function countCustomers($search = ''): int
{
    return count_entities('customers', $search, 'is_active = TRUE');
}

// API endpoint
if (isset($_GET['api'])) {
    $page = (int) (param($_GET, 'page', 1));
    $limit = (int) (param($_GET, 'limit', 20));
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'ASC');
    $search = (string) param($_GET, 'search', '');

    $customers = fetchCustomers($search, $sortColumn, $sortDir, $limit, $page);
    $total = countCustomers($search);

    json_response([
        'data' => $customers,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}