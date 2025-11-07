<?php
require_once '../config/helpers.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_response(['error' => 'Missing customer ID.'], 400);
    exit;
}

try {
    $ok = soft_delete_entity('customers', $id);
    if (!$ok) {
        json_response(['error' => 'Customer not found or already inactive.'], 404);
        exit;
    }
    json_response(['message' => 'Customer soft-deleted successfully.']);
} catch (Throwable $e) {
    json_response(['error' => 'Internal Server Error'], 500);
}