<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>Demo Page for Adding Waste</h1>
    <form id="wasteForm">
        <label for="stock_id">Stock ID:</label>
        <input type="number" id="stock_id" name="stock_id"
            value="<?php echo isset($_GET['stockid']) ? $_GET['stockid'] : '';  ?>" disabled required><br><br>
        <label for="quantity">Quantity:</label>
        <input type="number" step="0.01" id="quantity" name="quantity" required><br><br>
        <label for="reason">Reason:</label>
        <input type="text" id="reason" name="reason"><br><br>
        <button type="submit">Add Waste</button>
    </form>
    <div id="response"></div>

</body>
<script>
document
document.getElementById('wasteForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const stock_id = document.getElementById('stock_id').value;
    const quantity = document.getElementById('quantity').value;
    const reason = document.getElementById('reason').value;

    fetch('addwaste.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                stock_id: parseInt(stock_id),
                quantity: parseFloat(quantity),
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('response').innerText = JSON.stringify(data);
        })
        .catch(error => {
            document.getElementById('response').innerText = 'Error: ' + error;
        });
});
</script>

</html>