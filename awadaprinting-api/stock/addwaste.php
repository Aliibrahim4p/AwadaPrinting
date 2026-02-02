<?php
require_once __DIR__ . '/../utils/helpers.php';
/**
 * Add waste to the inventory (no quantity filter)
 */
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
$stockId = isset($input['stock_id']) ? intval($input['stock_id']) : 0;
$quantity = isset($input['quantity']) ? floatval($input['quantity']) :
0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';
if ($stockId <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid stock_id and quantity required.']);
    exit;
}

   
    if($reason === '') {
        $reason = 'No reason provided';
    }
    try{
        $pdo->beginTransaction();

        // Insert waste record
        $stmt = $pdo->prepare("INSERT INTO stock_waste (stock_id, quantity, reason, waste_date) VALUES (:stock_id, :quantity, :reason, NOW())");
        $stmt->execute([
            ':stock_id' => $stockId,
            ':quantity' => $quantity,
            ':reason' => $reason
        ]);

        // Update stock quantity
        $updateStmt = $pdo->prepare("UPDATE stock SET quantity_on_hand = quantity_on_hand - :quantity, updated_at = NOW() WHERE id = :stock_id");
        $updateStmt->execute([
            ':quantity' => $quantity,
            ':stock_id' => $stockId
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
        
        
    } catch (Exception $e) {
        $pdo->rollBack();
            http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }