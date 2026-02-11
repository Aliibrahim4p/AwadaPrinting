<?php
require_once __DIR__ . '/../utils/helpers.php';

/**
 * Fetch all waste entries
 */
function fetchWaste(
    string $search = '',
    string $sortColumn = 'waste_date',
    string $sortDir = 'ASC',
    int $limit = 20,
    int $page = 1
): array {
    //get stock name for each waste entry
    $from = "stock_waste sw
         LEFT JOIN stock s ON sw.stock_id = s.id";
         
    $allowedSort = ['waste_date', 'quantity', 'stock_id'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);

$searchColumns = ['sw.stock_id::TEXT', 's.name'];
    if ($search) {
        global $searchQuery;
        $searchQuery = "(" . implode(
            " ILIKE '%" . $search . "%' OR ",
            $searchColumns
        ) . " ILIKE '%" . $search . "%')";
    }

    return fetch_entities(
        $from ,
        $searchQuery ?? '',
        $allowedSort,
        $sortColumn,
        $sortDir,
        $limit,
        $page,
        'sw.quantity > 0',
        selectColumns: 'sw.*, s.name AS stock_name'
    );
}

/**
 * Count all waste entries
 */
function countWaste(string $search = ''): int
{
     $from = "stock_waste sw
         LEFT JOIN stock s ON sw.stock_id = s.id";
    global $searchQuery;
    return count_entities(
        $from,
         
        $searchQuery ?? '',
        'sw.quantity > 0'
    );
}

/* ================= API ================= */

if (isset($_GET['api'])) {
    $page       = (int) param($_GET, 'page', 1);
    $limit      = (int) param($_GET, 'limit', 20);
    $sortColumn = (string) param($_GET, 'sortColumn', 'waste_date');
    $sortDir    = (string) param($_GET, 'sortDir', 'ASC');
    $search     = (string) param($_GET, 'search', '');

    $waste = fetchWaste($search, $sortColumn, $sortDir, $limit, $page);
    $total = countWaste($search);

    json_response([
        'data'        => $waste,
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}