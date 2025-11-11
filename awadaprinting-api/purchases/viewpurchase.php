<?php
require_once "../config/db.php";
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing purchase ID.']);
    exit;
}

// Fetch purchase + supplier
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.purchase_date,
        p.total_cost,
        p.notes,
        p.supplier_id,
        s.name AS supplier_name,
        s.contact_info AS supplier_contact
    FROM purchases p
    JOIN suppliers s ON s.id = p.supplier_id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    http_response_code(404);
    echo json_encode(['error' => 'Purchase not found.']);
    exit;
}

// Fetch purchase items + stock info
$stmt = $pdo->prepare("
    SELECT 
        pi.id,
        pi.stock_id,
        st.name AS stock_name,
        pi.quantity,
        pi.price_per_unit,
        st.unit_of_measure,
        (pi.quantity * pi.price_per_unit) AS line_total
    FROM purchase_details pi
    JOIN stock st ON st.id = pi.stock_id
    WHERE pi.purchase_id = :id
");
$stmt->execute([':id' => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine everything
$response = [
    'purchase' => $purchase,
    'items' => $items,
];

echo json_encode($response);
exit;