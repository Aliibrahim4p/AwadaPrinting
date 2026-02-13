<?php
require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

$items      = $data['items'] ?? [];
$itemName   = trim($data['itemName'] ?? '');
$itemDesc   = trim($data['itemDesc'] ?? '');
$services   = $data['services'] ?? [];
$finalPrice = (float)($data['finalPrice'] ?? 0.0);

if ($itemName === '') {
    http_response_code(400);
    echo json_encode(['error' => 'itemName is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO items (name, description, is_active, selling_price, created_at)
        VALUES (:name, :description, true, :selling_price, NOW())
        RETURNING id
    ");
    $stmt->execute([
        ':name' => $itemName,
        ':description' => $itemDesc,
        ':selling_price' => $finalPrice
    ]);
    $itemId = $stmt->fetchColumn();

    $componentStmt = $pdo->prepare("
        INSERT INTO item_components (parent_item_id, stock_id, quantity)
        VALUES (:item_id, :stock_id, :quantity)
        RETURNING id
    ");

    foreach ($items as $comp) {
        // optional validation
        if (!isset($comp['stock_id'], $comp['quantity'])) continue;

        $componentStmt->execute([
            ':item_id' => $itemId,
            ':stock_id' => $comp['stock_id'],
            ':quantity' => $comp['quantity']
        ]);
    }

    $serviceStmt = $pdo->prepare("
        INSERT INTO item_extra_costs (item_id, service_id, notes, cost, created_at)
        VALUES (:item_id, :service_id, :notes, :cost, NOW())
        RETURNING id
    ");

    foreach ($services as $serv) {
        if (!isset($serv['service_id'], $serv['cost'])) continue;

        $serviceStmt->execute([
            ':item_id' => $itemId,
            ':service_id' => $serv['service_id'],
            ':notes' => $serv['service_id'] ?? '',
            ':cost' => $serv['cost']
        ]);
    }

    $pdo->commit();
    echo json_encode(['message' => 'Item added successfully', 'item_id' => $itemId]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add item: ' . $e->getMessage()]);
}