<?php
require_once "../config/db.php";
header('Content-Type: application/json');
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
// GET request → return the customer
echo json_encode(['customer' => $customer]);
exit;