<?php
require_once '../utils/helpers.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_response(['error' => 'Missing supplier ID.'], 400);
    exit;
}

try {
    $ok = soft_delete_entity('suppliers', $id);
    if (!$ok) {
        json_response(['error' => 'Supplier not found or already inactive.'], 404);
        exit;
    }
    json_response(['message' => 'Supplier soft-deleted successfully.']);
} catch (Throwable $e) {
    json_response(['error' => 'Internal Server Error'], 500);
}