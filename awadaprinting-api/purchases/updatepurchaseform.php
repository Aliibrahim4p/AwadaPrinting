<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Purchase</title>
    <style>
    body {
        font-family: Arial;
        margin: 30px;
        background-color: #f9f9f9;
    }

    h2,
    h3 {
        color: #333;
    }

    form {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 6px;
        margin-top: 5px;
        box-sizing: border-box;
    }

    input[readonly] {
        background-color: #eee;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 6px;
        text-align: left;
    }

    th {
        background-color: #f1f1f1;
    }

    .row-actions button {
        background-color: #dc3545;
    }

    .row-actions button:hover {
        background-color: #b02a37;
    }

    button {
        padding: 8px 15px;
        margin: 8px 5px 0 0;
        cursor: pointer;
        background-color: #007BFF;
        color: #fff;
        border: none;
        border-radius: 4px;
    }

    button:hover {
        background-color: #0056b3;
    }

    .preview-box {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }
    </style>
</head>

<body>
    <h2>Update Purchase</h2>
    <form id="purchaseForm">
        <label>Supplier Name:</label>
        <input type="text" id="supplier_name" readonly>

        <label>Supplier Contact:</label>
        <input type="text" id="supplier_contact" readonly>

        <label for="note">Note:</label>
        <textarea id="note" rows="2" placeholder="Additional notes..."></textarea>

        <h3>Purchase Items</h3>
        <table id="detailsTable">
            <thead>
                <tr>
                    <th>Stock</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Price/unit</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <button type="button" onclick="addRow()">Add Item</button>
        <br><br>
        <button type="button" onclick="submitPurchase()">Save Purchase</button>
    </form>

    <div class="preview-box">
        <h3>Live Preview</h3>
        <pre id="purchasePreview">{}</pre>
    </div>

    <script>
    let stockData = [];
    let purchaseId = new URLSearchParams(window.location.search).get('id');

    async function loadStock() {
        try {
            const res = await fetch('../stock/readstock.php?api=1');
            const data = await res.json();
            stockData = data.data || [];
        } catch (e) {
            console.error('Error loading stock:', e);
        }
    }

    function addRow(existingItem = null) {
        const tbody = document.querySelector('#detailsTable tbody');
        const tr = document.createElement('tr');

        let options = '<option value="">-- New Stock --</option>';
        stockData.forEach(s =>
            options +=
            `<option value="${s.id}" data-name="${s.name}" data-unit="${s.unit_of_measure}" data-price="${s.price_per_unit}">${s.name}</option>`
        );

        tr.innerHTML = `
                <td><select class="stock-select">${options}</select></td>
                <td><input type="text" placeholder="Name"></td>
                <td><input type="text" placeholder="Unit"></td>
                <td><input type="number" min="0.01" step="0.01" placeholder="Quantity"></td>
                <td><input type="number" min="0" step="0.01" placeholder="Price/unit"></td>
                <td class="row-actions"><button type="button" onclick="removeRow(this)">X</button></td>
            `;

        const select = tr.querySelector('.stock-select');
        const nameInput = tr.querySelector('input[placeholder="Name"]');
        const unitInput = tr.querySelector('input[placeholder="Unit"]');
        const priceInput = tr.querySelector('input[placeholder="Price/unit"]');
        const quantityInput = tr.querySelector('input[placeholder="Quantity"]');
        const inputs = tr.querySelectorAll('input, select');

        if (existingItem) {
            if (existingItem.stock_id) select.value = existingItem.stock_id;
            nameInput.value = existingItem.name || '';
            unitInput.value = existingItem.unit || '';
            quantityInput.value = existingItem.quantity || 0;
            priceInput.value = existingItem.price || 0;
            if (existingItem.stock_id) {
                nameInput.readOnly = true;
                unitInput.readOnly = true;
            }
        }

        select.addEventListener('change', () => {
            const selected = select.selectedOptions[0];
            if (select.value) {
                nameInput.value = selected.dataset.name;
                unitInput.value = selected.dataset.unit;
                priceInput.value = selected.dataset.price || 0;
                nameInput.readOnly = true;
                unitInput.readOnly = true;
            } else {
                nameInput.value = '';
                unitInput.value = '';
                priceInput.value = 0;
                nameInput.readOnly = false;
                unitInput.readOnly = false;
            }
            updatePreview();
        });

        inputs.forEach(input => input.addEventListener('input', updatePreview));

        tbody.appendChild(tr);
        updatePreview();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        updatePreview();
    }

    async function loadPurchase() {
        if (!purchaseId) return alert('Purchase ID missing');
        try {
            const res = await fetch(
                `/AwadaPrinting/awadaprinting-api/purchases/viewpurchase.php?id=${encodeURIComponent(purchaseId)}`
            );
            const data = await res.json();
            if (!res.ok || data.error) return alert(data.error || 'Failed to load purchase');

            const p = data.purchase;
            const items = data.items || [];

            document.getElementById('supplier_name').value = p.supplier_name || '';
            document.getElementById('supplier_contact').value = p.supplier_contact || '';
            document.getElementById('note').value = p.notes || '';

            items.forEach(item => {
                addRow({
                    stock_id: item.stock_id,
                    name: item.stock_name,
                    unit: item.unit_of_measure,
                    quantity: item.quantity,
                    price: item.price_per_unit
                });
            });

        } catch (e) {
            console.error(e);
            alert('Error fetching purchase');
        }
    }

    function buildPurchaseData() {
        const supplierName = document.getElementById('supplier_name').value;
        const supplierContact = document.getElementById('supplier_contact').value;
        const note = document.getElementById('note').value;
        const rows = document.querySelectorAll('#detailsTable tbody tr');
        const items = [];

        rows.forEach(row => {
            const select = row.querySelector('.stock-select');
            const inputs = row.querySelectorAll('input');
            const quantity = parseFloat(inputs[2].value);
            const price = parseFloat(inputs[3].value) || 0;
            if (!quantity || quantity <= 0) return;

            const item = {
                quantity,
                price
            };
            if (select.value) {
                const selected = select.selectedOptions[0];
                item.stock_id = parseInt(select.value);
                item.name = selected.dataset.name;
                item.unit = selected.dataset.unit;
            } else {
                const name = inputs[0].value.trim();
                const unit = inputs[1].value.trim();
                if (!name || !unit) return;
                item.name = name;
                item.unit = unit;
            }
            items.push(item);
        });

        return {
            purchaseId,
            supplierName,
            supplierContact,
            note,
            items
        };
    }

    function updatePreview() {
        const data = buildPurchaseData();
        document.getElementById('purchasePreview').textContent = JSON.stringify(data, null, 2);
    }

    async function submitPurchase() {
        const data = buildPurchaseData();
        if (!data.items.length) return alert('Add at least one item');

        try {
            const res = await fetch('../purchases/updatepurchase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) alert('Purchase updated successfully');
            else alert('Error: ' + (result.error || 'Unknown'));
        } catch (e) {
            console.error(e);
            alert('Error updating purchase');
        }
    }

    window.addEventListener('DOMContentLoaded', async () => {
        await loadStock();
        await loadPurchase();
        document.getElementById('note').addEventListener('input', updatePreview);
    });
    </script>
</body>

</html>