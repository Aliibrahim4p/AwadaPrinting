<?php
/**
 * Generic helper functions to DRY API endpoints.
 */
require_once __DIR__ . '/redis.php';
require_once __DIR__ . '/db.php';

/**
 * Safely get a value from an array (e.g., $_GET) with default.
 */
function param(array $src, string $key, $default = null) {
    return isset($src[$key]) ? $src[$key] : $default;
}

/**
 * Normalize page and limit to sensible integers.
 */
function normalize_pagination($page, $limit, $defaultLimit = 20): array
{
    $p = (int) ($page ?? 1);
    $l = (int) ($limit ?? $defaultLimit);
    if ($p < 1) $p = 1;
    if ($l < 1) $l = $defaultLimit;
    return [$p, $l, ($p - 1) * $l];
}

/**
 * Validate the requested sort column and direction.
 */
function normalize_sort(string $requestedColumn = 'id', string $requestedDir = 'ASC', array $allowedColumns = ['id']): array
{
    $col = in_array($requestedColumn, $allowedColumns, true) ? $requestedColumn : $allowedColumns[0];
    $dir = strtoupper($requestedDir) === 'DESC' ? 'DESC' : 'ASC';
    return [$col, $dir];
}

/**
 * Build a cache key from pieces.
 */
function build_cache_key(string $namespace, array $parts): string
{
    $encoded = array_map(function ($p) {
        if (is_bool($p)) return $p ? '1' : '0';
        if (is_array($p)) return md5(json_encode($p));
        return strtolower(trim((string)$p));
    }, $parts);
    return $namespace . ':' . implode(':', $encoded);
}

/**
 * Get JSON value from Redis cache, decoded to array; returns null if not found.
 */
function cache_get_json(string $key)
{
    global $redis;
    try {
        $cached = $redis->get($key);
        return $cached === null ? null : json_decode($cached, true);
    } catch (\Exception $e) {
        return null; // ignore cache failures
    }
}

/**
 * Set an array/object to Redis as JSON with TTL.
 */
function cache_set_json(string $key, $value, int $ttl = 300): void
{
    global $redis;
    try {
        $redis->setex($key, $ttl, json_encode($value));
    } catch (\Exception $e) {
        // ignore cache failures
    }
}

/**
 * Parse JSON body; fallback to form POST; returns array.
 */
function parse_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        return $data;
    }
    return $_POST ?? [];
}

/** Require method(s), emit 405 JSON if mismatch. */
function require_method(array $allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        json_response(['error' => 'Method Not Allowed', 'allowed' => $allowed], 405);
        exit;
    }
}

/** Standard JSON error helper. */
function json_error(string $message, int $code = 400): void
{
    json_response(['error' => $message], $code);
}

/**
 * Generic entity list fetcher with optional LIKE search on a single column and active flag.
 */
function fetch_entities(string $table, string $searchColumn, string $search = '', array $allowedSortColumns = ['id'], string $sortColumn = 'id', string $sortDir = 'ASC', int $limit = 20, int $page = 1, string $extraWhere = 'is_active = TRUE'): array
{
    global $pdo;

    // Normalize inputs
    [$sortColumn, $sortDir] = normalize_sort($sortColumn, $sortDir, $allowedSortColumns);
    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 20;
    $offset = ($page - 1) * $limit;

    // Build cache key
    $cacheKey = build_cache_key($table . ':list', [$page, $limit, $sortColumn, $sortDir, $search]);
    if ($cached = cache_get_json($cacheKey)) {
        return $cached;
    }

    // Whitelist table name to avoid injection
    $allowedTables = ['customers', 'suppliers'];

    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table requested');
    }

    $sql = "SELECT * FROM {$table} WHERE {$extraWhere}";
    $params = [];
    if ($search !== '') {
        $sql .= " AND {$searchColumn} ILIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY {$sortColumn} {$sortDir} LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    cache_set_json($cacheKey, $data);
    return $data;
}

/**
 * Generic counter for entities.
 */
function count_entities(string $table, string $searchColumn, string $search = '', string $extraWhere = 'is_active = TRUE'): int
{
    global $pdo;

    // Build cache key
    $cacheKey = build_cache_key($table . ':count', [$search]);
    $cached = cache_get_json($cacheKey);
    if ($cached !== null) return (int)$cached;

    // Whitelist table
    $allowedTables = ['customers', 'suppliers'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table requested');
    }

    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$extraWhere}";
    $params = [];
    if ($search !== '') {
        $sql .= " AND {$searchColumn} ILIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();

    cache_set_json($cacheKey, $count);
    return $count;
}

/** Fetch single entity by ID (optionally only active). */
function fetch_entity_by_id(string $table, int $id, bool $onlyActive = true): ?array
{
    global $pdo;

    $allowedTables = ['customers', 'suppliers'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table requested');
    }

    $where = 'id = :id';
    if ($onlyActive) {
        $where .= ' AND is_active = TRUE';
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$where} LIMIT 1");
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    return $row ?: null;
}

/** Invalidate list/count caches for a table. */
function invalidate_cache_for_table(string $table): void
{
    global $redis;
    if (!isset($redis)) return;
    if (function_exists('clearCustomersCache') && $table === 'customers') {
        clearCustomersCache($redis);
        return;
    }
    if (function_exists('clearSuppliersCache') && $table === 'suppliers') {
        clearSuppliersCache($redis);
        return;
    }
}

/**
 * Generic update helper for a single row.
 * - $allowedFields: whitelist of updatable columns.
 * - $requiredNonEmpty: fields that must be non-empty strings if present or always required if missing.
 * - $validators: associative array field => callable($value): string|null returns error string or null.
 */
function update_entity(string $table, int $id, array $input, array $allowedFields, array $requiredNonEmpty = [], array $validators = []): array
{
    global $pdo;

    $allowedTables = ['customers', 'suppliers'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table requested');
    }

    // Filter to allowed fields
    $data = [];
    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $input)) {
            $data[$f] = $input[$f];
        }
    }

    if (empty($data)) {
        throw new InvalidArgumentException('No valid fields to update.');
    }

    // Required non-empty validation
    foreach ($requiredNonEmpty as $f) {
        $val = $data[$f] ?? $input[$f] ?? null;
        if ($val === null || (is_string($val) && trim($val) === '')) {
            throw new InvalidArgumentException(ucfirst($f) . ' is required.');
        }
    }

    // Custom validators
    foreach ($validators as $field => $callable) {
        if (array_key_exists($field, $data)) {
            $msg = $callable($data[$field]);
            if (is_string($msg) && $msg !== '') {
                throw new InvalidArgumentException($msg);
            }
        }
    }

    // Build dynamic SET clause
    $sets = [];
    $params = [':id' => $id];
    foreach ($data as $col => $val) {
        $sets[] = "$col = :$col";
        $params[":$col"] = $val === '' ? null : $val;
    }
    $sets[] = 'updated_at = NOW()';

    $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Invalidate caches
    invalidate_cache_for_table($table);

    $updated = fetch_entity_by_id($table, $id, false);
    if (!$updated) {
        throw new RuntimeException('Update failed or record not found.');
    }
    return $updated;
}

/** Soft delete helper (sets is_active = FALSE). */
function soft_delete_entity(string $table, int $id): bool
{
    global $pdo;

    $allowedTables = ['customers', 'suppliers'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table requested');
    }

    $stmt = $pdo->prepare('UPDATE ' . $table . ' SET is_active = FALSE, updated_at = NOW() WHERE id = :id AND is_active = TRUE');
    $stmt->execute([':id' => $id]);
    $ok = $stmt->rowCount() > 0;

    if ($ok) {
        invalidate_cache_for_table($table);
    }
    return $ok;
}

/**
 * Standard JSON response emitter.
 */
function json_response($payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
}
