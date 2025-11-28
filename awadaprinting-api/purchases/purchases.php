<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchases</title>
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    table {
        border-collapse: collapse;
        width: 90%;
        margin: 20px auto;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }

    th {
        cursor: pointer;
    }

    th:first-child,
    td:first-child {
        display: none;
        /* hide ID */
    }

    .filters {
        text-align: center;
        margin-top: 20px;
    }

    input[type="text"],
    input[type="date"],
    select {
        padding: 6px;
    }

    button {
        padding: 6px 10px;
    }

    .pagination {
        text-align: center;
        margin-top: 20px;
    }

    .pagination a,
    .pagination strong {
        margin: 0 4px;
        cursor: pointer;
    }
    </style>
</head>

<body>
    <div class="filters">
        <button onclick="window.location.href='addpurchaseform.php'">Add Purchase</button>
        <input type="text" id="general_search" placeholder="Search notes, supplier, cost">
        <select id="sort">
            <option value="ASC">Date Asc</option>
            <option value="DESC" selected>Date Desc</option>
        </select>
        <button onclick="loadPurchases()">Search</button>
    </div>

    <table id="purchases-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Notes</th>
                <th>Total Cost</th>
                <th>Purchase Date</th>
                <th>Actions</th>
            </tr>

            <!-- Filter row like customers page -->
            <tr class="filter-row">
                <td></td>
                <td><input type="text" id="supplier_name"></td>
                <td><input type="text" id="notes"></td>
                <td><input type="text" id="total_cost"></td>
                <td><input type="date" id="purchase_date"></td>
                <td><button id="clear-filters">Clear Filter</button></td>
            </tr>
        </thead>

        <tbody></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <script>
    let currentPage = 1;
    const rowsPerPage = 20;

    // Clear filters
    document.getElementById('clear-filters').addEventListener('click', () => {
        document.querySelectorAll('.filter-row input').forEach(el => el.value = '');
        loadPurchases(1);
    });

    // Collect filters
    function applyFilters() {
        const filters = {};

        const supplier = document.getElementById('supplier_name').value.trim();
        const notes = document.getElementById('notes').value.trim();
        const total = document.getElementById('total_cost').value.trim();
        const date = document.getElementById('purchase_date').value;

        if (supplier) filters.supplier_name = supplier;
        if (notes) filters.notes = notes;
        if (total) filters.total_cost = total;
        if (date) filters.purchase_date = date;

        return filters;
    }

    // Load purchases
    function loadPurchases(page = 1) {
        currentPage = page;

        const sortDir = document.getElementById('sort').value;
        const general = document.getElementById('general_search').value.trim();
        const filters = applyFilters();

        const params = new URLSearchParams();
        params.append('api', 1);
        params.append('page', page);
        params.append('limit', rowsPerPage);
        params.append('sortColumn', 'purchase_date');
        params.append('sortDir', sortDir);

        if (general) params.append('query[general]', general);

        for (const key in filters) {
            params.append(`query[${key}]`, filters[key]);
        }

        fetch(`readpurchases.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                renderTable(data.data || []);
                renderPagination(data.page || 1, data.total_pages || 1);
            })
            .catch(err => console.error('Error loading purchases:', err));
    }

    // Render table
    function renderTable(purchases) {
        const tbody = document.querySelector('#purchases-table tbody');
        tbody.innerHTML = '';

        if (purchases.length === 0) {
            tbody.innerHTML =
                `<tr><td colspan="6" style="text-align:center;">No purchases found.</td></tr>`;
            return;
        }

        purchases.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${p.id}</td>
                    <td>${p.supplier_name || '-'}</td>
                    <td>${p.notes || '-'}</td>
                    <td>${p.total_cost || 0}</td>
                    <td>${p.purchase_date || '-'}</td>
                    <td>
                        <button onclick="viewPurchase(${p.id})">View</button>
                        <button onclick="updatePurchase(${p.id})">Update</button>
                        <button onclick="deletePurchase(${p.id})">Delete</button>
                    </td>
                `;
            tbody.appendChild(tr);
        });
    }

    // Pagination
    function renderPagination(page, totalPages) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            if (i === page) pagination.innerHTML += `<strong>${i}</strong> `;
            else pagination.innerHTML += `<a onclick="loadPurchases(${i})">${i}</a> `;
        }
    }

    // Actions
    function viewPurchase(id) {
        window.location.href = `viewpurchasedemo.php?id=${id}`;
    }

    function updatePurchase(id) {
        window.location.href = `updatepurchase.php?id=${id}`;
    }

    async function deletePurchase(id) {
        if (!confirm('Delete this purchase?')) return;

        await fetch(`deletepurchase.php?id=${id}`, {
            method: 'DELETE'
        });
        loadPurchases(currentPage);
    }

    // Initial load
    loadPurchases();
    </script>

</body>

</html>