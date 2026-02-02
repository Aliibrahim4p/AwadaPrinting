<?php
ob_start();

require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Sanitize input
$name = trim($input['name'] ?? '');
$contact_info = trim($input['contact_info'] ?? '');
$notes = trim($input['notes'] ?? '');

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required.']);
    exit;
}

if (!empty($contact_info) && !preg_match('/^\d+$/', $contact_info)) {
    http_response_code(400);
    echo json_encode(['error' => 'Contact info must contain only numbers.']);
    exit;
}

try {
    // Insert new customer (PostgreSQL) and return its ID reliably
    $stmt = $pdo->prepare("INSERT INTO customers (name, contact_info, notes, is_active) VALUES (:name, :contact_info, :notes, TRUE) RETURNING id");
    $stmt->execute([
        ':name' => $name,
        ':contact_info' => $contact_info ?: null,
        ':notes' => $notes ?: null
    ]);
    $newId = (int) $stmt->fetchColumn();

   

    // Fetch updated data for the response
    $page = 1;
    $limit = 20;
    // Return newest first so the just-added customer is visible on page 1
    $customers = fetchCustomers('', 'id', 'DESC', $limit, $page);
    $total = countCustomers('');

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Customer added successfully.',
        'customer_id' => $newId,
        'data' => $customers,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred.',
        'debug_message' => $e->getMessage() // Remove this in production
    ]);
}