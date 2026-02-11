<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';
$stmt = $pdo->query("
    SELECT unnest(enum_range(NULL::unit_enum)) AS unit
");
$units = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['data' => $units]);