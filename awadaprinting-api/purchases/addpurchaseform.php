<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Purchase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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

        select,
        input,
        textarea {
            width: 100%;
            padding: 6px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            padding: 8px 15px;
            margin: 8px 5px 0 0;
            cursor: pointer;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
        }

        button:hover {
            background-color: #0056b3;
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

        pre {
            background: #222;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .row-actions button {
            background-color: #dc3545;
        }

        .row-actions button:hover {
            background-color: #b02a37;
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
    <h2>Add Purchase</h2>

    <form id="purchaseForm">
        <label for="supplier">Supplier:</label>
        <select id="supplier" required>
            <option>-- Loading suppliers --</option>
        </select>

        <label for="note">Note:</label>
        <textarea id="note" rows="2" placeholder="Additional notes..."></textarea>

        <h3>Purchase Details</h3>
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

        <button type="button" onclick="addRow()">Add Row</button>
        <br><br>
        <button type="button" onclick="submitPurchase()">Submit Purchase</button>
    </form>

    <div class="preview-box">
        <h3>Live Preview</h3>
        <pre id="purchasePreview">{}</pre>
    </div>

    <script>
        let suppliers = [];
        let stockData = [];

        const supplierApi = '../suppliers/readsuppliers.php';
        const stockApi = '../stock/readstock.php';
        const purchaseApi = '../purchases/addpurchase.php';

        async function loadSuppliers() {
            try {
                const res = await fetch(`${supplierApi}?api=1&limit=1000`);
                const data = await res.json();
                suppliers = data.data || [];
                const sel = document.getElementById('supplier');
                sel.innerHTML = '<option value="">-- Select Supplier --</option>';
                suppliers.forEach(s => sel.add(new Option(s.name, s.id)));
            } catch (e) {
                console.error(e);
            }
        }

        async function loadStock() {
            try {
                const res = await fetch(`${stockApi}?api=1`);
                const data = await res.json();
                stockData = data.data || [];
            } catch (e) {
                console.error(e);
            }
        }

        function addRow() {
            const tbody = document.querySelector('#detailsTable tbody');
            const tr = document.createElement('tr');

            // Create options for stock dropdown
            let options = '<option value="">-- New Stock --</option>';
            stockData.forEach(s =>
                options +=
                `<option value="${s.id}" data-name="${s.name}" data-unit="${s.unit_of_measure}">${s.name}</option>`
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
            const inputs = tr.querySelectorAll('input, select');

            select.addEventListener('change', () => {
                const selected = select.selectedOptions[0];
                if (select.value) {
                    nameInput.value = select.value; // just name
                    unitInput.value = selected.dataset.unit || '';
                    nameInput.readOnly = true;
                    unitInput.readOnly = true;
                } else {
                    nameInput.value = '';
                    unitInput.value = '';
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

        function buildPurchaseData() {
            const supplier = document.getElementById('supplier').value;
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
                    item.stock_id = parseInt(select.value); // sent to backend
                    item.name = selected.dataset.name; // just for display/preview
                    item.unit = selected.dataset.unit; // just for display/preview
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
                supplier,
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

            if (!data.supplier) return alert('Please select a supplier.');
            if (!data.items.length) return alert('Add at least one valid item.');

            try {
                const res = await fetch(purchaseApi, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await res.json();
                if (result.success) {
                    alert('Purchase submitted successfully!');
                    document.getElementById('purchaseForm').reset();
                    document.querySelector('#detailsTable tbody').innerHTML = '';
                    addRow();
                } else {
                    alert('Error: ' + (result.error || 'Unknown'));
                }
            } catch (e) {
                console.error(e);
                alert('Error submitting purchase');
            } finally {
                updatePreview();
            }
        }

        window.addEventListener('DOMContentLoaded', async () => {
            await loadSuppliers();
            await loadStock();
            addRow();
            document.getElementById('supplier').addEventListener('change', updatePreview);
            document.getElementById('note').addEventListener('input', updatePreview);
        });
    </script>
</body>

</html>