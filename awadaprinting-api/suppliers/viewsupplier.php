<?php
require_once "../config/db.php";
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing supplier ID.']);
    exit;
}

// Fetch existing supplier
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id AND is_active = TRUE");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    http_response_code(404);
    echo json_encode(['error' => 'Supplier not found.']);
    exit;
}

// GET request → return the supplier
echo json_encode(['supplier' => $supplier]);
exit;