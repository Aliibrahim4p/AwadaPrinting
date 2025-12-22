<?php require_once '../utils/helpers.php';
header('Content-Type: application/json');
$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($purchase_id <= 0) {
    json_response(['error' => 'Missing purchase ID.'], 400);
    exit;
}
try{
    $pdo->beginTransaction();
$purchase_id = intval($purchase_id);
$pdo->prepare("SELECT id FROM purchases WHERE id = ?")->execute([$purchase_id]);
// block if payemnts exist for this purchase
$stmt= $pdo->prepare("SELECT COUNT(*) FROM payments WHERE related_purchase = ?");
$stmt->execute([$purchase_id]);
if($stmt->fetchColumn()>0){
    http_response_code(400);
    echo json_encode(['error' => 'Cannot delete purchase with existing payments.']);
    exit;
}
//fetch details:
$detailStmt = $pdo->prepare("SELECT stock_id, quantity FROM purchase_details WHERE purchase_id = ?");
$detailStmt->execute([$purchase_id]);
//revert stock quantities
$detailStmts = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
$stockUpdateStmt = $pdo->prepare("UPDATE stock SET quantity_on_hand = quantity_on_hand - ? WHERE id = ?");
foreach ($detailStmts as $detail) {
    if ($detail['stock_id']) $stockUpdateStmt->execute([$detail['quantity'], $detail['stock_id']]);
}
//delete details
$pdo->prepare("DELETE FROM purchase_details WHERE purchase_id = ?")->execute([$purchase_id]);
//delete purchase
$pdo->prepare("DELETE FROM purchases WHERE id = ?")->execute([$purchase_id]);
$pdo->commit();
json_response(['message' => 'Purchase deleted successfully.']);


}
catch (Throwable $e) {
    json_response(['error' => 'Internal Server Error'], 500);
}