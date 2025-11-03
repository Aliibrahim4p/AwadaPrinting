<?php
require_once "config/db.php";

$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "✅ PostgreSQL connected successfully!";
} else {
    echo "❌ Connection failed.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <a href="customers/customers.php">view</a>
</body>

</html>