<?php
require_once '../utils/helpers.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_response(['error' => 'Missing supplier ID.'], 400);
    exit;
}

// Support GET to fetch current entity (used by forms)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $supplier = fetch_entity_by_id('suppliers', $id, true);
    if (!$supplier) {
        json_response(['error' => 'Supplier not found.'], 404);
        exit;
    }
    json_response(['supplier' => $supplier]);
    exit;
}

// Only allow POST/PUT for updates
require_method(['POST', 'PUT']);

$existing = fetch_entity_by_id('suppliers', $id, true);
if (!$existing) {
    json_response(['error' => 'Supplier not found.'], 404);
    exit;
}

$input = parse_json_body();

try {
    $updated = update_entity(
        'suppliers',
        $id,
        $input,
        ['name', 'contact_info', 'notes'],
        ['name'],
        [
            'contact_info' => function ($v) {
                if ($v === null || $v === '') return null;
                return preg_match('/^\d+$/', (string)$v) ? null : 'Contact info must contain only numbers.';
            }
        ]
    );

    json_response([
        'message' => 'Supplier updated successfully.',
        'supplier' => $updated
    ]);
} catch (InvalidArgumentException $e) {
    $code = (stripos($e->getMessage(), 'required') !== false) ? 422 : 400;
    json_response(['error' => $e->getMessage()], $code);
} catch (Throwable $e) {
    json_response(['error' => 'Internal Server Error'], 500);
}