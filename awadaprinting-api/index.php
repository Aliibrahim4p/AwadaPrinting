<?php
require_once "config/database.php";

$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "✅ PostgreSQL connected successfully!";
} else {
    echo "❌ Connection failed.";
}
?>