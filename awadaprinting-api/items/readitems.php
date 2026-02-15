<?php
require_once __DIR__ . '/../utils/helpers.php';

// a page to fetch the items with pagination, sorting and searching
function fetchItems(string $search = '', $sortColumn = 'id', $sortDir = 'ASC', $limit = 20, $page = 1): array
{
    $allowedSort = [ 'name', 'selling_price','description', 'created_at'];
    $searchColumns = ['name', 'description'];
    if($search) {
        global $searchQuery;
        $searchQuery="(" . implode(" ILIKE '%" . $search . "%' OR ", $searchColumns) . " ILIKE '%" . $search . "%')";
    };
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);

    return fetch_entities('items', $searchQuery ?? '', $allowedSort, $sortColumn, $sortDir, (int)$limit, (int)$page, 'is_active = TRUE' . ( $search ? " AND $searchQuery" : ''));
}
function countItems(string $search = ''): int
{
        global $searchQuery;
    return count_entities('items', $searchQuery ?? '', 'is_active = TRUE');
}
// API endpoint
if (isset($_GET['api'])) {
    $page = (int) param($_GET, 'page', 1);
    $limit = (int) param($_GET, 'limit', 20);
    $sortColumn = (string) param($_GET, 'sortColumn', 'id');
    $sortDir = (string) param($_GET, 'sortDir', 'ASC');
    $search = (string) param($_GET, 'search', '');

    $items = fetchItems($search, $sortColumn, $sortDir, $limit, $page);
    $total = countItems($search);

    json_response([
        'data' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
}