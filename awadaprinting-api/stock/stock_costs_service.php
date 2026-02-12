<?php
require_once '../config/db.php';

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