<?php
require_once '../config/db.php'; // Your PDO connection
require_once '../config/redis.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing supplier ID.']);
    exit;
}

// Soft delete: mark as inactive
$stmt = $pdo->prepare("UPDATE suppliers SET is_active = FALSE, updated_at = NOW() WHERE id = :id");
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Supplier not found or already inactive.']);
} else {
    // Clear suppliers cache so reads reflect the deletion
    if (function_exists('clearSuppliersCache')) {
        clearSuppliersCache($redis);
    }
    echo json_encode(['message' => 'Supplier soft-deleted successfully.']);
}