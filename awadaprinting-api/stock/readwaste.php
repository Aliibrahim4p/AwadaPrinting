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
    $allowedSort = ['waste_date', 'quantity', 'stock_id'];
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSort);

    $searchColumns = ['stock_id::TEXT'];
    if ($search) {
        global $searchQuery;
        $searchQuery = "(" . implode(
            " ILIKE '%" . $search . "%' OR ",
            $searchColumns
        ) . " ILIKE '%" . $search . "%')";
    }

    return fetch_entities(
        'stock_waste',
        $searchQuery ?? '',
        $allowedSort,
        $sortColumn,
        $sortDir,
        $limit,
        $page,
        'stock_waste.quantity > 0'
    );
}

/**
 * Count all waste entries
 */
function countWaste(string $search = ''): int
{
    global $searchQuery;
    return count_entities(
        'stock_waste',
        $searchQuery ?? '',
        'stock_waste.quantity > 0'
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
    echo "<script>console.log('Waste data:', );</script>";

    json_response([
        'data'        => $waste,
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => ceil($total / max($limit, 1))
    ]);
    exit;
}