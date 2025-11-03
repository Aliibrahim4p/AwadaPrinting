<?php
require_once '../config/db.php'; // PDO connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

// Get POSTed JSON data if sent as raw JSON, otherwise fallback to form data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$name = trim($input['name'] ?? '');
$contact_info = trim($input['contact_info'] ?? '');
$notes = trim($input['notes'] ?? '');

if ($name === '') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Name is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO customers (name, contact_info, notes) VALUES (:name, :contact_info, :notes)");
    $stmt->execute([
        ':name' => $name,
        ':contact_info' => $contact_info,
        ':notes' => $notes
    ]);

    $newId = $pdo->lastInsertId();

    echo json_encode([
        'message' => 'Customer added successfully.',
        'customer_id' => $newId
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}