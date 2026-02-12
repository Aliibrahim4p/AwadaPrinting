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
if (json_last_error() !== JSON_ERROR_NONE)
    $input = $_POST;
$name = isset($input['name']) ? trim($input['name']) : '';

if ($name === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid unit name.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "ALTER TYPE unit_enum ADD VALUE IF NOT EXISTS '$name'";
    $pdo->exec($sql);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}