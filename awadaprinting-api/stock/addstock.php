<?php 
/// API endpoint to add a new stock item
ob_start();
require_once '../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests allowed.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE)
    $input = $_POST;
$name = isset($input['name']) ? trim($input['name']) : '';
$unit_of_measure = isset($input['unit_of_measure']) ? trim($input['unit_of_measure']) : '';

if (empty($name) || empty($unit_of_measure)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and unit of measure are required.']);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO stock (name, unit_of_measure, quantity_on_hand, quantity_reserved, created_at, updated_at, is_active) VALUES (?, ?, 0, 0, NOW(), NOW(), true) RETURNING id");
    $stmt->execute([$name, $unit_of_measure]);
    $stock_id = $stmt->fetchColumn();
    $pdo->commit();
    echo json_encode(['message' => 'Stock item added successfully.', 'stock_id' => $stock_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add stock item: ' . $e->getMessage()]);

}