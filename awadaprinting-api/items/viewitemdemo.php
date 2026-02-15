<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Missing or invalid item id.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>View Item Demo</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 40px;
        background-color: #fafafa;
        color: #333;
    }

    h2 {
        margin-bottom: 12px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin: 12px 0 18px 0;
    }

    .section-title {
        margin: 0 0 10px 0;
        font-size: 15px;
        color: #222;
    }

    .hint {
        font-size: 12px;
        color: #666;
        margin-top: 6px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        font-size: 14px;
        vertical-align: middle;
        text-align: center;
    }

    th {
        background-color: #f1f1f1;
        font-weight: bold;
    }

    tfoot th {
        background-color: #eee;
    }

    .row input,
    .row select {
        width: 100%;
        box-sizing: border-box;
        padding: 4px;
        font-size: 14px;
    }

    input[readonly] {
        background-color: #f3f3f3;
    }

    button {
        padding: 7px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    button:hover {
        opacity: 0.92;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background-color: #2980b9;
    }

    .btn-danger {
        background-color: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c0392b;
    }

    .btn-secondary {
        background-color: #555;
        color: white;
    }

    .actions {
        margin-top: 18px;
    }

    .actions button {
        margin-right: 10px;
    }
    </style>
</head>

<body>
    <h2>View Item (Editable Demo)</h2>

    <div class="toolbar">
        <button type="button" class="btn-secondary" onclick="history.back()">⬅ Back</button>
        <button type="button" class="btn-primary" onclick="addStockRow()">➕ Add Stock Component</button>
        <button type="button" class="btn-primary" onclick="addServiceRow()">➕ Add Service</button>
    </div>

    <form id="itemForm">
        <p class="section-title">Item</p>

        <label>Item Name</label>
        <input type="text" id="itemName" name="itemName" size="50" placeholder="Enter the name" />

        <label>Description</label>
        <input type="text" id="itemDesc" name="itemDesc" size="50" placeholder="Enter a description" />

        <!-- STOCK COMPONENTS -->
        <p class="section-title">Stock Components</p>
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>Stock</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Avg Cost</th>
                    <th>Last Cost</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <!-- SERVICES -->
        <p class="section-title">Services</p>
        <table id="servicesTable">
            <thead>
                <tr>
                    <th>Service</th>
                    <th></th>
                    <th></th>
                    <th>Cost</th>
                    <th></th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Grand Total (Items + Services):</th>
                    <th id="finalAvg">0.00</th>
                    <th id="finalLast">0.00</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <label>Final Price</label>
        <input type="number" id="finalPrice" name="finalPrice" step="0.01" />

        <div class="actions">
            <button type="submit" class="btn-primary">Update (connect later)</button>
        </div>

        <div class="hint">
            This page is editable. When you’re ready, you’ll connect your update endpoint using the payload printed in
            console.
        </div>
    </form>

    <script>
    const itemId = <?= (int)$id ?>;

    let stockList = [];
    let serviceList = [];
    let loadedItem = null;

    // ================= LOADERS =================
    async function loadStock() {
        const res = await fetch('../stock/readstock.php?api=1');
        const data = await res.json();
        stockList = data.data || [];
    }

    async function loadServices() {
        const res = await fetch('../services/readservices.php?api=1');
        const data = await res.json();
        serviceList = data.data || [];
    }

    // ================= HELPERS =================
    function td(el) {
        const cell = document.createElement('td');
        cell.appendChild(el);
        return cell;
    }

    function emptyTd() {
        return td(document.createTextNode(''));
    }

    // ================= TOTALS =================
    function updateTotals() {
        // Items totals
        let totalAvg = 0;
        let totalLast = 0;

        document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
            const avgInput = tr.querySelector('input[data-role="avg"]');
            const lastInput = tr.querySelector('input[data-role="last"]');
            totalAvg += parseFloat(avgInput?.value) || 0;
            totalLast += parseFloat(lastInput?.value) || 0;
        });

        // Services totals (count it in both avg/last as in your builder)
        let servicesTotal = 0;
        document.querySelectorAll('#servicesTable tbody tr').forEach(tr => {
            const costInput = tr.querySelector('input[data-role="serviceCost"]');
            servicesTotal += parseFloat(costInput?.value) || 0;
        });

        document.getElementById('finalAvg').textContent = (totalAvg + servicesTotal).toFixed(2);
        document.getElementById('finalLast').textContent = (totalLast + servicesTotal).toFixed(2);
    }

    // ================= STOCK ROW =================
    function addStockRow(existing = null) {
        const tbody = document.querySelector('#itemsTable tbody');
        const tr = document.createElement('tr');
        tr.classList.add('row');

        // Keep DB row id for future update/delete (item_components.id)
        if (existing?.id) tr.dataset.componentId = existing.id;

        const stockSelect = document.createElement('select');
        stockSelect.required = true;

        const defaultOpt = document.createElement('option');
        defaultOpt.value = "";
        defaultOpt.textContent = "Select Stock";
        stockSelect.appendChild(defaultOpt);

        stockList.forEach(stock => {
            const opt = document.createElement('option');
            opt.value = stock.id;
            opt.textContent = stock.name;
            opt.dataset.unit = stock.unit_of_measure || '';
            stockSelect.appendChild(opt);
        });

        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.min = 0;
        qtyInput.value = existing?.quantity ?? 1;

        const unitInput = document.createElement('input');
        unitInput.type = 'text';
        unitInput.readOnly = true;

        const avgInput = document.createElement('input');
        avgInput.type = 'number';
        avgInput.step = "0.01";
        avgInput.readOnly = true;
        avgInput.setAttribute('data-role', 'avg');

        const lastInput = document.createElement('input');
        lastInput.type = 'number';
        lastInput.step = "0.01";
        lastInput.readOnly = true;
        lastInput.setAttribute('data-role', 'last');

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = "btn-danger";
        removeBtn.textContent = "❌";
        removeBtn.onclick = () => {
            tr.remove();
            updateTotals();
        };

        async function refreshCosts() {
            const selected = stockSelect.selectedOptions[0];
            unitInput.value = selected?.dataset.unit || '';

            if (!stockSelect.value) {
                avgInput.value = "";
                lastInput.value = "";
                updateTotals();
                return;
            }

            const res = await fetch(`../stock/getcosts.php?stock_id=${encodeURIComponent(stockSelect.value)}`);
            const data = await res.json();

            const qty = parseFloat(qtyInput.value) || 0;
            const avgCost = parseFloat(data.avg_cost || 0);
            const lastCost = parseFloat(data.last_cost || 0);

            avgInput.value = (qty * avgCost).toFixed(2);
            lastInput.value = (qty * lastCost).toFixed(2);

            updateTotals();
        }

        stockSelect.addEventListener('change', refreshCosts);
        qtyInput.addEventListener('input', refreshCosts);

        tr.appendChild(td(stockSelect));
        tr.appendChild(td(qtyInput));
        tr.appendChild(td(unitInput));
        tr.appendChild(td(avgInput));
        tr.appendChild(td(lastInput));
        tr.appendChild(td(removeBtn));
        tbody.appendChild(tr);

        // Apply existing selection AFTER append
        if (existing?.stock_id) {
            stockSelect.value = existing.stock_id;
        }

        refreshCosts();
    }

    // ================= SERVICE ROW =================
    function addServiceRow(existing = null) {
        const tbody = document.querySelector('#servicesTable tbody');
        const tr = document.createElement('tr');
        tr.classList.add('row');

        // Keep DB row id for future update/delete (item_services.id or whatever you return as "id")
        if (existing?.id) tr.dataset.itemServiceId = existing.id;

        const serviceSelect = document.createElement('select');
        serviceSelect.required = true;

        const defaultOpt = document.createElement('option');
        defaultOpt.value = "";
        defaultOpt.textContent = "Select Service";
        serviceSelect.appendChild(defaultOpt);

        serviceList.forEach(service => {
            const opt = document.createElement('option');
            opt.value = service.id;
            opt.textContent = service.name;
            serviceSelect.appendChild(opt);
        });

        const costInput = document.createElement('input');
        costInput.type = 'number';
        costInput.step = "0.01";
        costInput.min = "0";
        costInput.value = (existing?.cost ?? 0).toString();
        costInput.setAttribute('data-role', 'serviceCost');
        costInput.addEventListener('input', updateTotals);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = "btn-danger";
        removeBtn.textContent = "❌";
        removeBtn.onclick = () => {
            tr.remove();
            updateTotals();
        };

        // Align cost under Avg column (4th)
        tr.appendChild(td(serviceSelect));
        tr.appendChild(emptyTd());
        tr.appendChild(emptyTd());
        tr.appendChild(td(costInput));
        tr.appendChild(emptyTd());
        tr.appendChild(td(removeBtn));

        tbody.appendChild(tr);

        if (existing?.service_id) {
            serviceSelect.value = existing.service_id;
        }

        updateTotals();
    }

    // ================= LOAD ITEM =================
    async function loadItem() {
        const res = await fetch(`viewitem.php?id=${encodeURIComponent(itemId)}`);
        const data = await res.json();

        if (data?.error) {
            alert(data.error);
            return;
        }

        loadedItem = data;

        // Adjust these keys if your items columns are different
        document.getElementById('itemName').value = data.name ?? '';
        document.getElementById('itemDesc').value = data.description ?? '';
        document.getElementById('finalPrice').value = data.selling_price ?? 0;

        // Clear tables
        document.querySelector('#itemsTable tbody').innerHTML = '';
        document.querySelector('#servicesTable tbody').innerHTML = '';

        // Components
        (data.components || []).forEach(c => addStockRow(c));

        // Services
        (data.services || []).forEach(s => addServiceRow(s));

        updateTotals();
    }

    // ================= SUBMIT PLACEHOLDER =================
    document.getElementById('itemForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const payload = {
            id: itemId,
            name: document.getElementById('itemName').value.trim(),
            description: document.getElementById('itemDesc').value.trim(),
            selling_price: parseFloat(document.getElementById('finalPrice').value || "0"),
            components: collectComponents(),
            services: collectItemServices()
        };
        fetch(`updateitem.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })

        // console.log("EDIT PAYLOAD (for your future update endpoint):", payload);
        // alert("Payload printed in console. Connect your update endpoint when ready.");
    });

    function collectComponents() {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        const comps = [];

        rows.forEach(tr => {
            const component_id = tr.dataset.componentId ? parseInt(tr.dataset.componentId, 10) : null;
            const stock_id = parseInt(tr.querySelector('select')?.value || "0", 10);
            const qtyInput = tr.querySelector('input[type="number"]');
            const quantity = parseFloat(qtyInput?.value || "0");

            if (!stock_id || quantity <= 0) return;

            comps.push({
                id: component_id,
                stock_id,
                quantity
            });
        });

        return comps;
    }


    function collectItemServices() {
        const rows = document.querySelectorAll('#servicesTable tbody tr');
        const services = [];

        rows.forEach(tr => {
            const item_service_id = tr.dataset.itemServiceId ? parseInt(tr.dataset.itemServiceId, 10) : null;
            const service_id = parseInt(tr.querySelector('select')?.value || "0", 10);
            const costInput = tr.querySelector('input[data-role="serviceCost"]');
            const cost = parseFloat(costInput?.value || "0");

            if (!service_id || cost < 0) return;

            services.push({
                id: item_service_id,
                service_id,
                cost
            });
        });

        return services;
    }
    //update function call
    const Form = document.getElementById('itemForm');


    // ================= INIT =================
    (async function init() {
        await loadStock();
        await loadServices();
        await loadItem();
    })();
    </script>
</body>

</html>