<?php
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
if (json_last_error() !== JSON_ERROR_NONE) $input = $_POST;

$purchase_id = isset($input['purchaseId']) ? intval($input['purchaseId']) : 0;
$supplier_name = trim($input['supplierName'] ?? '');
$supplier_contact = trim($input['supplierContact'] ?? '');
$note = trim($input['note'] ?? '');
$items = $input['items'] ?? [];

if ($purchase_id <= 0 || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Purchase ID and at least one item required.']);
    exit;
}


try {
    $pdo->beginTransaction();

    // Fetch old items to revert stock quantity
    $oldItemsStmt = $pdo->prepare("SELECT stock_id, quantity FROM purchase_details WHERE purchase_id = ?");
    $oldItemsStmt->execute([$purchase_id]);
    $oldItems = $oldItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $stockUpdateStmt = $pdo->prepare("UPDATE stock SET quantity_on_hand = quantity_on_hand - ? WHERE id = ?");
    foreach ($oldItems as $old) {
        if ($old['stock_id']) $stockUpdateStmt->execute([$old['quantity'], $old['stock_id']]);
    }

    // Delete old purchase details
    $pdo->prepare("DELETE FROM purchase_details WHERE purchase_id = ?")->execute([$purchase_id]);

    // Recalculate total cost
    $total_cost = 0;
    foreach ($items as $item) {
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $total_cost += $quantity * $price;
    }

    // Update purchase header
    $stmt = $pdo->prepare("UPDATE purchases SET notes = ?, total_cost = ? WHERE id = ?");
    $stmt->execute([$note, $total_cost, $purchase_id]);

    // Prepare statements for details
    $detailStmt = $pdo->prepare("INSERT INTO purchase_details (purchase_id, stock_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
$stockInsertStmt = $pdo->prepare("
    INSERT INTO stock 
    (name, unit_of_measure, quantity_on_hand, quantity_reserved, created_at, updated_at)
    VALUES (?, ?, ?, 0, NOW(), NOW())
    RETURNING id
");
    $stockAddStmt = $pdo->prepare("UPDATE stock SET quantity_on_hand = quantity_on_hand + ?,updated_at=NOW() WHERE id = ?");

    foreach ($items as $item) {
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);

        if (!empty($item['stock_id'])) {
            // Existing stock
            $stock_id = intval($item['stock_id']);
            $stockAddStmt->execute([$quantity, $stock_id]);
        } else {
            // New stock
            $name = trim($item['name'] ?? '');
            $unit = trim($item['unit'] ?? '');
            if ($name === '' || $unit === '') throw new Exception('Name and unit required for new stock');
            $stockInsertStmt->execute([$name, $unit, $quantity]);
            $stock_id = $stockInsertStmt->fetchColumn();
        }

        $detailStmt->execute([$purchase_id, $stock_id, $quantity, $price]);
    }

    $pdo->commit();

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