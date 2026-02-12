<?php
// a page to read all 3rdparty services from the database
require_once '../config/db.php';
$stmt = $pdo->query("SELECT * FROM third_party_services WHERE is_active = TRUE");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode(['data' => $services]);

exit;