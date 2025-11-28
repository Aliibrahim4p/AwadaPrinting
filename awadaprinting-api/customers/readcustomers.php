<?php
require_once __DIR__ . '/../config/helpers.php';

// Wrappers to keep compatibility while using generic helpers
function fetchCustomers(string $search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 20, $page = 1): array
{
    $allowedSort = ['id', 'name'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);

    return fetch_entities('customers', $search, $allowedSort, $sortColumn, $sortDir, (int)$limit, (int)$page, 'is_active = TRUE' . ( $search ? " AND $search" : ''));
}

function countCustomers(string $search = ''): int
{
    return count_entities('customers', $search, 'is_active = TRUE');
}

// API endpoint
if (isset($_GET['api'])) {
    $page = (int) param($_GET, 'page', 1);
    $limit = (int) param($_GET, 'limit', 20);
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'ASC');
    $searchquery = (array) param($_GET, 'query', []);

    $where = []; 
if (!empty($searchquery['name'])) {
    $where[] = "name ILIKE '%" . addslashes($searchquery['name']) . "%'";
}
if (!empty($searchquery['notes'])) {
    $where[] = "notes ILIKE '%" . addslashes($searchquery['notes']) . "%'";
}
if (!empty($searchquery['contact_info'])) {
    $where[] = "contact_info ILIKE '%" . addslashes($searchquery['contact_info']) . "%'";
}

// Combine conditions, no 'WHERE'
$search = implode(' AND ', $where);


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