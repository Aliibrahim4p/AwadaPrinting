<?php
require_once '../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests allowed.']);
    exit;
}

$stockId = isset($_GET['stock_id']) ? (int) $_GET['stock_id'] : 0;
if (!$stockId) {
    echo json_encode(['avg_cost' => 0, 'last_cost' => 0]);
    exit;
}

 function getLastCost($stockId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT pd.price_per_unit 
        FROM purchase_details pd 
        JOIN purchases p ON pd.purchase_id = p.id 
        WHERE pd.stock_id = :stock_id 
        ORDER BY p.purchase_date DESC 
        LIMIT 1
    ");
    $stmt->execute([':stock_id' => $stockId]);
    return (float) ($stmt->fetchColumn() ?: 0);
}

function getAverageCost($stockId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT AVG(pd.price_per_unit) 
        FROM purchase_details pd 
        JOIN purchases p ON pd.purchase_id = p.id 
        WHERE pd.stock_id = :stock_id
    ");
    $stmt->execute([':stock_id' => $stockId]);
    return (float) ($stmt->fetchColumn() ?: 0);
}

echo json_encode([
    'avg_cost' => getAverageCost($stockId),
    'last_cost' => getLastCost($stockId)
]);
exit; 