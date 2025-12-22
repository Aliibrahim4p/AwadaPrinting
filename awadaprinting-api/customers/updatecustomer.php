<?php
require_once '../utils/helpers.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_response(['error' => 'Missing customer ID.'], 400);
    exit;
}

// Support GET to fetch current entity (used by forms)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $customer = fetch_entity_by_id('customers', $id, true);
    if (!$customer) {
        json_response(['error' => 'Customer not found.'], 404);
        exit;
    }
    json_response(['customer' => $customer]);
    exit;
}

// Only allow POST/PUT for updates
require_method(['POST', 'PUT']);

$existing = fetch_entity_by_id('customers', $id, true);
if (!$existing) {
    json_response(['error' => 'Customer not found.'], 404);
    exit;
}

$input = parse_json_body();

// Run update using generic helper
try {
    $updated = update_entity(
        'customers',
        $id,
        $input,
        // Allowed fields
        ['name', 'contact_info', 'notes'],
        // Required non-empty fields
        ['name'],
        // Validators
        [
            'contact_info' => function ($v) {
                if ($v === null || $v === '') return null; // allow null/empty
                return preg_match('/^\d+$/', (string)$v) ? null : 'Contact info must contain only numbers.';
            }
        ]
    );

    json_response([
        'message' => 'Customer updated successfully.',
        'customer' => $updated
    ]);
} catch (InvalidArgumentException $e) {
    // Validation or bad input
    $code = (stripos($e->getMessage(), 'required') !== false) ? 422 : 400;
    json_response(['error' => $e->getMessage()], $code);
} catch (Throwable $e) {
    json_response(['error' => 'Internal Server Error'], 500);
}