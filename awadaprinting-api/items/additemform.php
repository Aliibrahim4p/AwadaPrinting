<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Item Builder (Real / Dummy)</title>
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

    .mode-label {
        margin: 0;
        font-size: 14px;
        color: #444;
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

    .section-title {
        margin: 0 0 10px 0;
        font-size: 15px;
        color: #222;
    }

    .hidden {
        display: none;
    }
    </style>
</head>

<body>
    <h2>Item Builder (Real / Dummy)</h2>



    <div class="toolbar">
        <button type="button" class="btn-secondary" id="modeBtn" onclick="toggleMode()">
            Switch to Dummy Mode
        </button>

        <p class="mode-label" id="modeLabel"><strong>Current Mode:</strong> REAL</p>

        <button type="button" class="btn-primary" id="addItemBtn" onclick="addStockRow()">➕ Add Stock Component</button>
        <button type="button" class="btn-primary" id="addServiceBtn" onclick="addServiceRow()">➕ Add Service</button>
        <button type="button" class="btn-primary hidden" id="addDummyBtn" onclick="addDummyRow()">➕ Add Dummy
            Line</button>
    </div>

    <form id="itemsForm">
        <!-- ================= REAL MODE ================= -->
        <div id="realMode">
            <p class="section-title">Real Builder (Stock + Services)</p>
            <label>Item Name</label>
            <input type="text" id="itemName" name="itemName" size="50" placeholder="Enter the name" />
            <label>Description</label>
            <input type="text" id="itemDesc" name="itemDesc" size="50" placeholder="Enter a description" />
            <!-- STOCK COMPONENTS -->
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

            <!-- SERVICES (aligned so Cost sits under Avg Cost column) -->
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
            <label>Set Final Price</label>
            <input type="number" id="finalPrice" name="finalPrice" step="0.01" />
        </div>

        <!-- ================= DUMMY MODE ================= -->
        <div id="dummyMode" class="hidden">
            <p class="section-title">Dummy Builder (Quotation / No Stock)</p>

            <table id="dummyTable">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Dummy Total:</th>
                        <th id="dummyTotal">0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <div class="hint">
                Dummy lines are view-only and do not affect stock. (Later in React you can decide if they should be
                saved.)
            </div>
        </div>

        <div class="actions">
            <button type="button" onclick="discardAll()">Discard</button>
            <button type="submit">Save</button>
        </div>
    </form>

    <script>
    // ================= STATE =================
    let currentMode = "REAL";
    let stockList = [];
    let serviceList = [];

    // ================= LOADERS =================
    async function loadStock() {
        const res = await fetch('../stock/readstock.php?api=1');
        const data = await res.json();
        stockList = data.data || [];
        return stockList;
    }

    async function loadServices() {
        const res = await fetch('../services/readservices.php?api=1');
        const data = await res.json();
        serviceList = data.data || [];
        return serviceList;
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

    // ================= MODE =================
    function toggleMode() {
        const real = document.getElementById('realMode');
        const dummy = document.getElementById('dummyMode');
        const label = document.getElementById('modeLabel');
        const btn = document.getElementById('modeBtn');

        const addItemBtn = document.getElementById('addItemBtn');
        const addServiceBtn = document.getElementById('addServiceBtn');
        const addDummyBtn = document.getElementById('addDummyBtn');

        if (currentMode === "REAL") {
            currentMode = "DUMMY";
            real.classList.add('hidden');
            dummy.classList.remove('hidden');
            label.innerHTML = "<strong>Current Mode:</strong> DUMMY";
            btn.textContent = "Switch to Real Mode";

            addItemBtn.classList.add('hidden');
            addServiceBtn.classList.add('hidden');
            addDummyBtn.classList.remove('hidden');

            updateDummyTotals();
        } else {
            currentMode = "REAL";
            dummy.classList.add('hidden');
            real.classList.remove('hidden');
            label.innerHTML = "<strong>Current Mode:</strong> REAL";
            btn.textContent = "Switch to Dummy Mode";

            addDummyBtn.classList.add('hidden');
            addItemBtn.classList.remove('hidden');
            addServiceBtn.classList.remove('hidden');

            updateRealTotals();
        }
    }

    // ================= REAL MODE TOTALS =================
    function updateRealTotals() {
        // Items totals
        let totalAvg = 0;
        let totalLast = 0;

        document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
            const inputs = tr.querySelectorAll('input');
            // row inputs order in items table: qty, unit, avg, last
            totalAvg += parseFloat(inputs[2].value) || 0;
            totalLast += parseFloat(inputs[3].value) || 0;
        });

        // Services total
        let servicesTotal = 0;
        document.querySelectorAll('#servicesTable tbody tr').forEach(tr => {
            const costInput = tr.querySelector('input[name*="[cost]"]');
            servicesTotal += parseFloat(costInput?.value) || 0;
        });

        document.getElementById('finalAvg').textContent = (totalAvg + servicesTotal).toFixed(2);
        document.getElementById('finalLast').textContent = (totalLast + servicesTotal).toFixed(2);
    }

    // ================= DUMMY MODE TOTALS =================
    function updateDummyTotals() {
        let total = 0;
        document.querySelectorAll('#dummyTable tbody tr').forEach(tr => {
            const lineTotalInput = tr.querySelector('input[data-role="lineTotal"]');
            total += parseFloat(lineTotalInput?.value) || 0;
        });
        document.getElementById('dummyTotal').textContent = total.toFixed(2);
    }

    // ================= REAL MODE ROWS =================
    function addStockRow() {
        const tbody = document.querySelector('#itemsTable tbody');
        const rowIndex = tbody.querySelectorAll('tr').length;

        const tr = document.createElement('tr');
        tr.classList.add('row');

        const stockSelect = document.createElement('select');
        stockSelect.name = `items[${rowIndex}][stock_id]`;
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
        qtyInput.name = `items[${rowIndex}][quantity]`;
        qtyInput.min = 0;
        qtyInput.value = 1;

        const unitInput = document.createElement('input');
        unitInput.type = 'text';
        unitInput.readOnly = true;

        const avgInput = document.createElement('input');
        avgInput.type = 'number';
        avgInput.step = "0.01";
        avgInput.readOnly = true;

        const lastInput = document.createElement('input');
        lastInput.type = 'number';
        lastInput.step = "0.01";
        lastInput.readOnly = true;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = "btn-danger";
        removeBtn.textContent = "❌";
        removeBtn.onclick = () => {
            tr.remove();
            updateRealTotals();
        };

        async function refreshCosts() {
            const selected = stockSelect.selectedOptions[0];
            unitInput.value = selected?.dataset.unit || '';

            if (!stockSelect.value) {
                avgInput.value = "";
                lastInput.value = "";
                updateRealTotals();
                return;
            }

            const res = await fetch(`../stock/getcosts.php?stock_id=${encodeURIComponent(stockSelect.value)}`);
            const data = await res.json();

            const qty = parseFloat(qtyInput.value) || 0;
            const avgCost = parseFloat(data.avg_cost || 0);
            const lastCost = parseFloat(data.last_cost || 0);

            avgInput.value = (qty * avgCost).toFixed(2);
            lastInput.value = (qty * lastCost).toFixed(2);

            updateRealTotals();
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
        updateRealTotals();
    }

    function addServiceRow() {
        const tbody = document.querySelector('#servicesTable tbody');
        const rowIndex = tbody.querySelectorAll('tr').length;

        const tr = document.createElement('tr');
        tr.classList.add('row');

        const serviceSelect = document.createElement('select');
        serviceSelect.name = `services[${rowIndex}][service_id]`;
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
        costInput.name = `services[${rowIndex}][cost]`;
        costInput.step = "0.01";
        costInput.min = "0";
        costInput.value = "0.00";
        costInput.addEventListener('input', updateRealTotals);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = "btn-danger";
        removeBtn.textContent = "❌";
        removeBtn.onclick = () => {
            tr.remove();
            updateRealTotals();
        };

        // Align cost under Avg column (4th):
        tr.appendChild(td(serviceSelect));
        tr.appendChild(emptyTd());
        tr.appendChild(emptyTd());
        tr.appendChild(td(costInput));
        tr.appendChild(emptyTd());
        tr.appendChild(td(removeBtn));

        tbody.appendChild(tr);
        updateRealTotals();
    }

    // ================= DUMMY MODE ROWS =================
    function addDummyRow() {
        const tbody = document.querySelector('#dummyTable tbody');
        const tr = document.createElement('tr');
        tr.classList.add('row');

        const desc = document.createElement('input');
        desc.type = 'text';
        desc.placeholder = 'Description (view-only)';

        const qty = document.createElement('input');
        qty.type = 'number';
        qty.min = '0';
        qty.value = '1';

        const unit = document.createElement('input');
        unit.type = 'text';
        unit.placeholder = 'pcs / m / box';

        const unitPrice = document.createElement('input');
        unitPrice.type = 'number';
        unitPrice.step = '0.01';
        unitPrice.min = '0';
        unitPrice.value = '0.00';

        const lineTotal = document.createElement('input');
        lineTotal.type = 'number';
        lineTotal.step = '0.01';
        lineTotal.readOnly = true;
        lineTotal.value = '0.00';
        lineTotal.setAttribute('data-role', 'lineTotal');

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = "btn-danger";
        removeBtn.textContent = "❌";
        removeBtn.onclick = () => {
            tr.remove();
            updateDummyTotals();
        };

        function refreshLineTotal() {
            const q = parseFloat(qty.value) || 0;
            const p = parseFloat(unitPrice.value) || 0;
            lineTotal.value = (q * p).toFixed(2);
            updateDummyTotals();
        }

        qty.addEventListener('input', refreshLineTotal);
        unitPrice.addEventListener('input', refreshLineTotal);

        tr.appendChild(td(desc));
        tr.appendChild(td(qty));
        tr.appendChild(td(unit));
        tr.appendChild(td(unitPrice));
        tr.appendChild(td(lineTotal));
        tr.appendChild(td(removeBtn));

        tbody.appendChild(tr);
        refreshLineTotal();
    }

    // ================= DISCARD =================
    function discardAll() {
        document.querySelector('#itemsTable tbody').innerHTML = '';
        document.querySelector('#servicesTable tbody').innerHTML = '';
        document.querySelector('#dummyTable tbody').innerHTML = '';

        // reset totals
        document.getElementById('finalAvg').textContent = "0.00";
        document.getElementById('finalLast').textContent = "0.00";
        document.getElementById('dummyTotal').textContent = "0.00";
    }

    // ================= SUBMIT =================
    document.getElementById('itemsForm').addEventListener('submit', function(e) {
        if (currentMode === "DUMMY") {
            e.preventDefault();
            alert("Dummy mode is view-only. Switch to REAL mode to save.");
            return;
        }

        e.preventDefault();

        // Build payload from your UI state (adjust these to your actual variables)
        const itemName = document.getElementById("itemName").value.trim();
        const itemDesc = document.getElementById("itemDesc").value.trim();
        const finalPrice = parseFloat(document.getElementById("finalPrice").value || "0");

        // IMPORTANT: these must be arrays of objects like your PHP expects
        // items: [{stock_id, quantity}, ...]
        // services: [{service_id, cost}, ...]
        const items = collectItems(); // <-- implement using your rows
        const services = collectServices(); // <-- implement using your rows
        console.log({
            itemName,
            itemDesc,
            finalPrice,
            items,
            services
        });

        fetch('additem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    itemName,
                    itemDesc,
                    finalPrice,
                    items,
                    services
                })
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    data = {
                        raw: text
                    };
                }
                if (!res.ok) throw data;
                return data;
            })
            .then(() => {
                alert("Saved successfully");
                discardAll();
                addStockRow();
                addServiceRow();
            })
            .catch(err => {
                console.error(err);
                alert("Error while saving: " + (err?.error || err?.raw || "Unknown error"));
            });
    });

    function collectItems() {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        const items = [];

        rows.forEach(tr => {
            const stockSelect = tr.querySelector('select[name*="[stock_id]"]');
            const qtyInput = tr.querySelector('input[name*="[quantity]"]');

            const stock_id = parseInt(stockSelect?.value, 10);
            const quantity = parseFloat(qtyInput?.value);

            // skip empty rows
            if (!stock_id || isNaN(quantity) || quantity <= 0) return;

            items.push({
                stock_id,
                quantity
            });
        });

        return items;
    }

    function collectServices() {
        const rows = document.querySelectorAll('#servicesTable tbody tr');
        const services = [];

        rows.forEach(tr => {
            const serviceSelect = tr.querySelector('select[name*="[service_id]"]');
            const costInput = tr.querySelector('input[name*="[cost]"]');

            const service_id = parseInt(serviceSelect?.value, 10);
            const cost = parseFloat(costInput?.value);

            // skip empty rows
            if (!service_id || isNaN(cost) || cost < 0) return;

            services.push({
                service_id,
                cost
            });
        });

        return services;
    }

    // ================= INIT =================
    (async function init() {
        await loadStock();
        await loadServices();
        addStockRow();
        addServiceRow();
        // dummy table starts empty; user adds lines when switching modes
        updateRealTotals();
        updateDummyTotals();
    })();
    </script>
</body>

</html>