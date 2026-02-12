<?php 
// a page to deactivate a service from the database
require_once '../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests allowed.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$serviceId = isset($data['id']) ? (int) $data['id'] : 0;
if (!$serviceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Service ID is required.']);
    exit;
}
try{
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE third_party_services SET is_active = FALSE WHERE id = :id");
    $stmt->execute([':id' => $serviceId]);
    $pdo->commit();
    echo json_encode(['message' => 'Service deactivated successfully']);
}
catch (PDOException $e) {
    $pdo->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to deactivate service: ' . $e->getMessage()]);
}