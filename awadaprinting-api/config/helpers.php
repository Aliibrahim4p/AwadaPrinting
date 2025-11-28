<?php
require_once __DIR__ . '/redis.php';
require_once __DIR__ . '/db.php';

/** Global whitelist of allowed entity tables */
$ALLOWED_ENTITY_TABLES = ['customers', 'suppliers', 'purchases', 'stock'];

/** --- Helpers --- */

/** Safely get value from array */


function buildSearchQuery(array $searchquery): string {
    $where = [];

    foreach ($searchquery as $column => $value) {
        if (!empty($value)) {
            $where[] = "$column ILIKE '%" . addslashes($value) . "%'";
        }
    }

    return implode(' AND ', $where);
}
function buildPurchaseSearchQuery(array $searchquery): string
{
    $where = [];

    foreach ($searchquery as $column => $value) {
        if (empty($value)) continue;

        switch ($column) {

            // Special case: supplier name
            case 'supplier_name':
                $value = addslashes($value);
                $where[] = "supplier_id IN (
                    SELECT id FROM suppliers 
                    WHERE name ILIKE '%$value%'
                )";
                break;

            // Standard columns from purchase table
            default:
                $value = addslashes($value);
                $where[] = "$column ILIKE '%$value%'";
                break;
        }
    }

    return implode(' AND ', $where);
}

function param(array $src, string $key, $default = null)
{
    return $src[$key] ?? $default;
}

/** Normalize page and limit */
function normalize_pagination($page, $limit, $defaultLimit = 20): array
{
    $p = max(1, (int) ($page ?? 1));
    $l = max(1, (int) ($limit ?? $defaultLimit));
    return [$p, $l, ($p - 1) * $l];
}

/** Normalize sort column and direction */
function normalize_sort(string $col = 'id', string $dir = 'ASC', array $allowed = ['id']): array
{
    return [in_array($col, $allowed, true) ? $col : $allowed[0], strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC'];
}

/** Validate table name */
function validate_table(string $table): void
{
    global $ALLOWED_ENTITY_TABLES;
    if (!in_array($table, $ALLOWED_ENTITY_TABLES, true))
        throw new InvalidArgumentException("Invalid table: {$table}");
}

/** Build Redis cache key */
function build_cache_key(string $namespace, array $parts): string
{
    $encoded = array_map(fn($p) => is_array($p) ? md5(json_encode($p)) : (is_bool($p) ? ($p ? '1' : '0') : strtolower(trim((string) $p))), $parts);
    return $namespace . ':' . implode(':', $encoded);
}

/** Redis get/set JSON */
function cache_get_json(string $key)
{
    global $redis;
    try {
        $c = $redis->get($key);
        return $c === null ? null : json_decode($c, true);
    } catch (\Exception) {
        return null;
    }
}
function cache_set_json(string $key, $value, int $ttl = 300): void
{
    global $redis;
    try {
        $redis->setex($key, $ttl, json_encode($value));
    } catch (\Exception) {
    }
}

/** Parse JSON body */
function parse_json_body(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : ($_POST ?? []);
}

/** Require HTTP method */
function require_method(array $allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true))
        json_response(['error' => 'Method Not Allowed', 'allowed' => $allowed], 405);
}

/** JSON response */
function json_response($payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
}

/** JSON error shortcut */
function json_error(string $msg, int $code = 400): void
{
    json_response(['error' => $msg], $code);
}

/** Invalidate Redis caches for table */
function invalidate_cache_for_table(string $table): void
{
    global $redis;
    if (!isset($redis))
        return;
    validate_table($table);
    try {
        foreach ([$table . ':list:*', $table . ':count:*'] as $pattern) {
            foreach ($redis->scanIterator($pattern) as $key)
                $redis->del($key);
        }
    } catch (\Exception) {
    }
}

/** Generic entity fetch with search, date, sort, and pagination */
function fetch_entities(
    string $table,
    string $search = '1=1',
    array $allowedSortColumns = ['id'],
    string $sortColumn = 'id',
    string $sortDir = 'ASC',
    int $limit = 20,
    int $page = 1,
    string $extraWhere = 'is_active = TRUE',
    ?string $dateColumn = null,
    ?string $dateFrom = null,
    ?string $dateTo = null
): array {
    global $pdo;
    validate_table($table);
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSortColumns);
    $offset = max(0, ($page - 1) * $limit);

    $cacheKey = build_cache_key($table . ':list', [$page, $limit, $sortColumn, $extraWhere, $sortDir, $search, $dateFrom, $dateTo, $dateColumn]);
    if ($cached = cache_get_json($cacheKey))
        return $cached;
if (trim($search) === '') {
    $search = '1=1';
}
    $sql = "SELECT * FROM {$table} WHERE {$extraWhere} AND {$search}";
    $params = [];

    // Multi-column search
    // if ($search !== '' && !empty($searchColumns)) {
    //     $searchColumns = (array) $searchColumns;
    //     $clauses = [];
    //     foreach ($searchColumns as $i => $col) {
    //         $param = ":search{$i}";
    //         $clauses[] = "{$col} ILIKE {$param}";
    //         $params[$param] = "%{$search}%";
    //     }
    //     $sql .= " AND (" . implode(" OR ", $clauses) . ")";
    // }

    // Date filter
    if ($dateColumn) {
        if ($dateFrom && $dateTo) {
            $dateTo .= ' 23:59:59';

            $sql .= " AND {$dateColumn} BETWEEN :dateFrom AND :dateTo";
            $params[':dateFrom'] = $dateFrom;
            $params[':dateTo'] = $dateTo;
        } elseif ($dateFrom) {
            $sql .= " AND {$dateColumn} >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        } elseif ($dateTo) {
            $sql .= " AND {$dateColumn} <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
    }

    $sql .= " ORDER BY {$sortColumn} {$sortDir} LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    cache_set_json($cacheKey, $data);
    return $data;
}

function count_entities(
    string $table,
    string $search = '1=1',
    string $extraWhere = 'is_active = TRUE',
    ?string $dateColumn = null,
    ?string $dateFrom = null,
    ?string $dateTo = null
): int {
    global $pdo;
    validate_table($table);

    $cacheKey = build_cache_key($table . ':count', [$search, $dateFrom, $dateTo, $extraWhere, $dateColumn]);
    if ($cached = cache_get_json($cacheKey))
        return (int) $cached;
if (trim($search) === '') {
    $search = '1=1';
}
    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$extraWhere} AND {$search}";
    $params = [];

   
    

    if ($dateColumn) {
        if ($dateFrom && $dateTo) {
            $dateTo .= ' 23:59:59';

            $sql .= " AND {$dateColumn} BETWEEN :dateFrom AND :dateTo";
            $params[':dateFrom'] = $dateFrom;
            $params[':dateTo'] = $dateTo;
        } elseif ($dateFrom) {
            $sql .= " AND {$dateColumn} >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        } elseif ($dateTo) {
            $sql .= " AND {$dateColumn} <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $count = (int) $stmt->fetchColumn();

    cache_set_json($cacheKey, $count);
    return $count;
}

/** Fetch single entity by ID */
function fetch_entity_by_id(string $table, int $id, bool $onlyActive = true): ?array
{
    global $pdo;
    validate_table($table);
    $where = 'id=:id' . ($onlyActive ? ' AND is_active=TRUE' : '');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$where} LIMIT 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** Generic update entity */
function update_entity(string $table, int $id, array $input, array $allowedFields, array $requiredNonEmpty = [], $validators = []): array
{
    global $pdo;
    validate_table($table);
    $data = array_intersect_key($input, array_flip($allowedFields));
    if (!$data)
        throw new InvalidArgumentException('No valid fields to update.');
    foreach ($requiredNonEmpty as $f) {
        $val = $data[$f] ?? $input[$f] ?? null;
        if ($val === null || (is_string($val) && trim($val) === ''))
            throw new InvalidArgumentException(ucfirst($f) . ' is required.');
    }
    foreach ($validators as $f => $fn) {
        if (isset($data[$f]) && ($msg = $fn($data[$f])) !== null)
            throw new InvalidArgumentException($msg);
    }

    $sets = [];
    $params = [':id' => $id];
    foreach ($data as $col => $val) {
        $sets[] = "$col=:$col";
        $params[":$col"] = $val === '' ? null : $val;
    }
    $sets[] = 'updated_at=NOW()';
    $stmt = $pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id=:id');
    $stmt->execute($params);

    invalidate_cache_for_table($table);
    $updated = fetch_entity_by_id($table, $id, false);
    if (!$updated)
        throw new RuntimeException('Update failed or record not found.');
    return $updated;
}

/** Soft delete entity */
function soft_delete_entity(string $table, int $id): bool
{
    global $pdo;
    validate_table($table);
    $stmt = $pdo->prepare('UPDATE ' . $table . ' SET is_active=FALSE, updated_at=NOW() WHERE id=:id AND is_active=TRUE');
    $stmt->execute([':id' => $id]);
    $ok = $stmt->rowCount() > 0;
    if ($ok)
        invalidate_cache_for_table($table);
    return $ok;
}