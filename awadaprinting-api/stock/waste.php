<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <a href="stock.php">Back to Stock</a>
    <table id="main-table">
        <tr>
            <th>Waste ID</th>
            <th>Stock ID</th>
            <th>Quantity</th>
            <th>Waste Date</th>
            <th>Reason</th>
        </tr>

    </table>
</body>
<script>
fetch('readwaste.php/api=1')
    .then(response => response.json())
    .then(data => {
        const table = document.getElementById('main-table');
        data.data.forEach(waste => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${waste.waste_id}</td>
                <td>${waste.stock_id}</td>
                <td>${waste.quantity}</td>
                <td>${new Date(waste.waste_date).toLocaleDateString()}</td>
                <td>${waste.reason || ''}</td>
            `;
            table.appendChild(row);
        });
    })
    .catch(error => console.error('Error fetching waste data:', error));
</script>

</html>