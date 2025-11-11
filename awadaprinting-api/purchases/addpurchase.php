<?php
ob_start();
require_once '../config/db.php';
require_once '../config/redis.php';
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

$supplier_id = isset($input['supplier']) ? intval($input['supplier']) : 0;
$note = isset($input['note']) ? trim($input['note']) : '';
$items = $input['items'] ?? [];

if ($supplier_id <= 0 || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Supplier and at least one item required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calculate total cost
    $total_cost = 0;
    foreach ($items as $item) {
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $total_cost += $quantity * $price;
    }

    // Insert purchase with total_cost
    $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id,  notes, total_cost) VALUES (?,  ?, ?) RETURNING id");
    $stmt->execute([$supplier_id, $note, $total_cost]);
    $purchase_id = $stmt->fetchColumn();

    // Prepare statements
    $detailStmt = $pdo->prepare("INSERT INTO purchase_details (purchase_id, stock_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
    $stockUpdateStmt = $pdo->prepare("UPDATE stock SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW() WHERE id = ?");
    $stockInsertStmt = $pdo->prepare("INSERT INTO stock (name, unit_of_measure, quantity_on_hand, quantity_reserved, created_at, updated_at, is_active) VALUES (?, ?, ?, 0, NOW(), NOW(), true) RETURNING id");

    foreach ($items as $item) {
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);

        if (!empty($item['stock_id'])) {
            // Existing stock
            $stock_id = intval($item['stock_id']);
            $stockUpdateStmt->execute([$quantity, $stock_id]);
        } else {
            // New stock
            $name = trim($item['name'] ?? '');
            $unit = trim($item['unit'] ?? '');
            if ($name === '' || $unit === '')
                throw new Exception('Name and unit required for new stock');
            $stockInsertStmt->execute([$name, $unit, $quantity]);
            $stock_id = $stockInsertStmt->fetchColumn();
        }

        // Insert purchase detail
        $detailStmt->execute([$purchase_id, $stock_id, $quantity, $price]);
    }

    $pdo->commit();
    clearPurchasesCache($redis);
    clearStockCache($redis);
    echo json_encode([
        'success' => true,
        'purchase_id' => $purchase_id,
        'total_cost' => $total_cost
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}