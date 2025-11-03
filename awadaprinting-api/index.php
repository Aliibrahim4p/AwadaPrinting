<?php
require_once "config/db.php";


require_once "predis/autoload.php";
use Predis\Client as PredisClient;

$redis = new PredisClient();
$redis->set("namw", "Ali");
echo $redis->get("name");
$r = new PredisClient([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '',
    'database' => 0,
]);
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