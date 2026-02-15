<?php
require_once "../config/db.php";
header('Content-Type: application/json');
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing item ID.']);
    exit;
}
try{
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id AND is_active = TRUE");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found.']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT ic.id, ic.stock_id, st.name AS stock_name, ic.quantity FROM item_components ic JOIN stock st ON st.id = ic.stock_id WHERE ic.parent_item_id = :id");
    $stmt->execute([':id' => $id]);
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $item['components'] = $components;
$stmt = $pdo->prepare("
  SELECT
    isv.id AS id,
    isv.service_id AS service_id,
    s.name AS name,
    isv.cost AS cost
  FROM item_extra_costs isv
  JOIN third_party_services s ON s.id = isv.service_id
  WHERE isv.item_id = :id
");
$stmt->execute([':id' => $id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$item['services'] = $services;
    $stmt->execute([':id' => $id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $item['services'] = $services;
    $pdo->commit();
    echo json_encode($item);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching the item.' ]);
    exit;
} 