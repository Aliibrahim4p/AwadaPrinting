<?php
require_once '../config/db.php'; // Your PDO connection
require_once '../config/redis.php';

header('Content-Type: application/json');

// Get supplier ID from query string
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

// Handle update (POST or PUT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    $name = trim($input['name'] ?? '');
    $contact_info = trim($input['contact_info'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Name is required.']);
        exit;
    }

    $updateStmt = $pdo->prepare("
        UPDATE suppliers 
        SET name = :name, 
            contact_info = :contact_info, 
            notes = :notes,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':name' => $name,
        ':contact_info' => $contact_info,
        ':notes' => $notes,
        ':id' => $id
    ]);

    // Clear suppliers cache so reads are fresh
    if (function_exists('clearSuppliersCache')) {
        clearSuppliersCache($redis);
    }

    // Fetch updated record
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'message' => 'Supplier updated successfully.',
        'supplier' => $supplier
    ]);
    exit;
}

// GET request → return the supplier
echo json_encode(['supplier' => $supplier]);
exit;