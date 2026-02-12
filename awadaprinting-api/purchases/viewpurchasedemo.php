<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Purchase</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: auto;
    }

    .section {
        margin-bottom: 25px;
    }


    .row {
        margin-bottom: 8px;
    }

    .label {
        font-weight: bold;
        width: 180px;
        display: inline-block;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }

    th {
        background: #f8f8f8;
    }

    .actions {
        margin-top: 25px;
    }

    button {
        padding: 8px 12px;
        margin-right: 10px;
    }

    .error {
        color: red;
        margin-top: 15px;
    }

    .total {
        text-align: right;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Purchase Details</h2>
        <div id="purchaseInfo" class="section"></div>

        <h3>Items</h3>
        <table id="itemsTable" style="display:none;">
            <thead>
                <tr>
                    <th>Stock Name</th>
                    <th>Quantity</th>
                    <th>Unit of Measure</th>
                    <th>Price per Unit</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody id="itemsBody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="total">Total:</td>
                    <td id="grandTotal"></td>
                </tr>
            </tfoot>
        </table>

        <div class="actions">
            <button onclick="goBack()">Back to Purchases</button>
            <button id="editBtn" style="display:none;">Edit</button>
        </div>
        <div id="error" class="error"></div>
    </div>

    <script>
    function getQueryParam(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    function goBack() {
        window.location.href = 'purchases.php';
    }

    async function loadPurchase() {
        const id = getQueryParam('id');
        const info = document.getElementById('purchaseInfo');
        const error = document.getElementById('error');
        const editBtn = document.getElementById('editBtn');
        const table = document.getElementById('itemsTable');
        const body = document.getElementById('itemsBody');
        const grandTotal = document.getElementById('grandTotal');

        info.innerHTML = '';
        error.textContent = '';
        body.innerHTML = '';
        grandTotal.textContent = '';
        table.style.display = 'none';

        if (!id) {
            error.textContent = 'Missing purchase ID.';
            return;
        }

        try {
            const res = await fetch(
                `/AwadaPrinting/awadaprinting-api/purchases/viewpurchase.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();

            if (!res.ok || data.error) {
                error.textContent = data.error || 'Failed to load purchase.';
                return;
            }

            const p = data.purchase;
            const items = data.items || [];

            info.innerHTML = `
            <div class="row"><span class="label">Purchase ID:</span> <span>${p.id}</span></div>
            <div class="row"><span class="label">Purchase Date:</span> <span>${p.purchase_date || ''}</span></div>
            <div class="row"><span class="label">Supplier:</span> <span>${p.supplier_name || ''}</span></div>
            <div class="row"><span class="label">Supplier Contact:</span> <span>${p.supplier_contact || ''}</span></div>
            <div class="row"><span class="label">Total Cost:</span> <span>${p.total_cost || 0}</span></div>
            <div class="row"><span class="label">Notes:</span> <span>${p.notes || ''}</span></div>
        `;

            if (items.length > 0) {
                let total = 0;
                items.forEach(item => {
                    const lineTotal = parseFloat(item.line_total || 0);
                    total += lineTotal;
                    body.innerHTML += `
                    <tr>
                        <td>${item.stock_name || ''}</td>
                        <td>${item.quantity || 0}</td>
                        <td>${item.unit_of_measure || ''}</td>
                        <td>${item.price_per_unit || 0}</td>
                        <td>${lineTotal.toFixed(2)}</td>
                    </tr>
                `;
                });
                grandTotal.textContent = total.toFixed(2);
                table.style.display = 'table';
            }

            editBtn.style.display = 'inline-block';
            editBtn.onclick = () => window.location.href = `updatepurchaseform.php?id=${encodeURIComponent(id)}`;
        } catch (e) {
            error.textContent = 'Error connecting to server.';
            console.error(e);
        }
    }

    loadPurchase();
    </script>
</body>

</html>