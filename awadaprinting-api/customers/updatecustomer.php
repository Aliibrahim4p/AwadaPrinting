<?php
require_once '../config/db.php'; // Your PDO connection

header('Content-Type: application/json');

// Get customer ID from query string
$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing customer ID.']);
    exit;
}

// Fetch existing customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id AND is_active = TRUE");
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    echo json_encode(['error' => 'Customer not found.']);
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
        UPDATE customers 
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

    // Fetch updated record
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'message' => 'Customer updated successfully.',
        'customer' => $customer
    ]);
    exit;
}

// GET request → return the customer
echo json_encode(['customer' => $customer]);
exit;