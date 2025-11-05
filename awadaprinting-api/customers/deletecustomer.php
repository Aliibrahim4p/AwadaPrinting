<?php
require_once '../config/db.php'; // Your PDO connection
require_once '../config/redis.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing customer ID.']);
    exit;
}

// Soft delete: mark as inactive
$stmt = $pdo->prepare("UPDATE customers SET is_active = FALSE, updated_at = NOW() WHERE id = :id");
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Customer not found or already inactive.']);
} else {
    // Clear customers cache so reads reflect the deletion
    if (function_exists('clearCustomersCache')) {
        clearCustomersCache($redis);
    }
    echo json_encode(['message' => 'Customer soft-deleted successfully.']);
}