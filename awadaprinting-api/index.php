<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awada Printing Dashboard</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 320px;
        }

        h1 {
            margin-bottom: 30px;
            font-size: 22px;
            color: #333;
        }

        a {
            display: block;
            padding: 12px;
            margin-top: 12px;
            background: #0066ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: 0.2s;
            font-size: 16px;
        }

        a:hover {
            background: #004bcc;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Awada Printing Panel</h1>

        <a href="customers/customers.php">Customers</a>
        <a href="suppliers/suppliers.php">Suppliers</a>
        <a href="purchases/purchases.php">Purchases</a>
        <!-- Add more options later easily -->
    </div>
</body>

</html>