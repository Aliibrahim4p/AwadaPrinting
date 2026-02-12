<?php 
// a page to add services into the database
require_once '../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests allowed.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
try{
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO third_party_services (name,is_active) VALUES (:name,true) RETURNING id");
    $stmt->execute([':name' => $name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pdo->commit();
    echo json_encode(['message' => 'Service added successfully', 'id' => $result['id']]);
}
catch (PDOException $e) {
    $pdo->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add service: ' . $e->getMessage()]);
}