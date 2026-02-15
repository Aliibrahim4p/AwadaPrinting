<?php
declare(strict_types=1);

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

require_once "../config/db.php";
// require_once "../utils/helpers.php"; // optional, keep if you need it

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

// Payload keys expected from your JS:
// { id, name, description, selling_price, components: [...], services: [...] }
$id          = isset($data['id']) ? (int)$data['id'] : 0;
$itemName    = trim((string)($data['name'] ?? ''));
$itemDesc    = trim((string)($data['description'] ?? ''));
$finalPrice  = (float)($data['selling_price'] ?? 0.0);
$components  = $data['components'] ?? [];
$services    = $data['services'] ?? [];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid item ID.']);
    exit;
}

if ($itemName === '') {
    http_response_code(400);
    echo json_encode(['error' => 'name is required.']);
    exit;
}

if (!is_array($components) || !is_array($services)) {
    http_response_code(400);
    echo json_encode(['error' => 'components and services must be arrays.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Ensure item exists and active
    $stmt = $pdo->prepare("SELECT id FROM items WHERE id = :id AND is_active = TRUE");
    $stmt->execute([':id' => $id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Item not found.']);
        exit;
    }

    // Update main item
    $updateStmt = $pdo->prepare("
        UPDATE items
        SET name = :name,
            description = :description,
            selling_price = :selling_price,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':name' => $itemName,
        ':description' => $itemDesc,
        ':selling_price' => $finalPrice,
        ':id' => $id
    ]);

    // Replace components (simple & safe)
    $deleteCompStmt = $pdo->prepare("DELETE FROM item_components WHERE parent_item_id = :id");
    $deleteCompStmt->execute([':id' => $id]);

    $insertCompStmt = $pdo->prepare("
        INSERT INTO item_components (parent_item_id, stock_id, quantity)
        VALUES (:item_id, :stock_id, :quantity)
    ");

    foreach ($components as $comp) {
        $stockId = isset($comp['stock_id']) ? (int)$comp['stock_id'] : 0;
        $qty     = isset($comp['quantity']) ? (float)$comp['quantity'] : 0.0;

        if ($stockId <= 0 || $qty <= 0) {
            continue;
        }

        $insertCompStmt->execute([
            ':item_id' => $id,
            ':stock_id' => $stockId,
            ':quantity' => $qty
        ]);
    }

    // Replace services
    $deleteServiceStmt = $pdo->prepare("DELETE FROM item_extra_costs WHERE item_id = :id");
    $deleteServiceStmt->execute([':id' => $id]);

    $insertServiceStmt = $pdo->prepare("
        INSERT INTO item_extra_costs (item_id, service_id, notes, cost, created_at)
        VALUES (:item_id, :service_id, :notes, :cost, NOW())
    ");

    foreach ($services as $srv) {
        $serviceId = isset($srv['service_id']) ? (int)$srv['service_id'] : 0;
        $cost      = isset($srv['cost']) ? (float)$srv['cost'] : 0.0;
        $notes     = isset($srv['notes']) ? (string)$srv['notes'] : '';

        if ($serviceId <= 0 || $cost < 0) {
            continue;
        }

        $insertServiceStmt->execute([
            ':item_id' => $id,
            ':service_id' => $serviceId,
            ':notes' => $notes,
            ':cost' => $cost
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'message' => 'Item updated successfully.',
        'id' => $id
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while updating the item.',
        'details' => $e->getMessage()
    ]);
    exit;
}