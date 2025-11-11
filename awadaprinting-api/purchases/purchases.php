<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchases</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .filters {
            text-align: center;
            margin-top: 20px;
        }

        input[type="text"],
        input[type="date"],
        select {
            padding: 6px;
            margin: 0 6px 10px 0;
        }

        button {
            padding: 6px 10px;
            margin: 0 4px;
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
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a,
        .pagination strong {
            margin: 0 4px;
            text-decoration: none;
            cursor: pointer;
        }

        .empty-row {
            text-align: center;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="filters">
        <button onclick="window.location.href='addpurchaseform.php'">Add Purchase</button>
        <input type="text" id="search" placeholder="Search by note, supplier, or total cost">
        <input type="date" id="from_date">
        <input type="date" id="to_date">
        <select id="sort">
            <option value="ASC">Date Asc</option>
            <option value="DESC" selected>Date Desc</option>
        </select>
        <button onclick="loadPurchases(1)">Search</button>
        <button onclick="resetFilters()">Reset</button>
    </div>

    <table id="purchases-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Note</th>
                <th>Total Cost</th>
                <th>Purchase Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <script>
        const rowsPerPage = 20;
        let currentPage = 1;

        async function loadPurchases(page = 1) {
            currentPage = page;

            const searchText = document.getElementById('search').value.trim();
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            const sortDir = document.getElementById('sort').value || 'DESC';

            const params = new URLSearchParams();
            params.set('api', '1');
            params.set('page', page);
            params.set('limit', rowsPerPage);
            params.set('sortColumn', 'purchase_date');
            params.set('sortDir', sortDir);
            if (searchText) params.set('search', searchText);
            if (fromDate) params.set('dateFrom', fromDate);
            if (toDate) params.set('dateTo', toDate);

            try {
                const res = await fetch(`readpurchases.php?${params.toString()}`);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                renderTable(data.data || []);
                renderPagination(data.page || 1, data.total_pages || 1);
            } catch (err) {
                console.error('loadPurchases error', err);
                const tbody = document.querySelector('#purchases-table tbody');
                tbody.innerHTML = `<tr><td colspan="6" class="empty-row">Error loading purchases</td></tr>`;
            }
        }

        function renderTable(purchases) {
            const tbody = document.querySelector('#purchases-table tbody');
            tbody.innerHTML = '';

            if (!purchases.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="empty-row">No purchases found</td></tr>`;
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
                <button onclick="editPurchase(${p.id})">Edit</button>
                <button onclick="deletePurchase(${p.id})">Delete</button>
            </td>
        `;
                tbody.appendChild(tr);
            });
        }

        function renderPagination(page, totalPages) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            if (totalPages <= 1) return;

            for (let i = 1; i <= totalPages; i++) {
                if (i === page) pagination.innerHTML += `<strong>${i}</strong>`;
                else pagination.innerHTML += `<a onclick="loadPurchases(${i})">${i}</a>`;
            }
        }

        function resetFilters() {
            document.getElementById('search').value = '';
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            document.getElementById('sort').value = 'DESC';
            loadPurchases(1);
        }

        function editPurchase(id) {
            window.location.href = `editpurchase.php?id=${id}`;
        }

        async function deletePurchase(id) {
            if (!confirm('Delete this purchase?')) return;
            try {
                const res = await fetch(`deletepurchase.php?id=${id}`, {
                    method: 'DELETE'
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                loadPurchases(currentPage);
            } catch (err) {
                console.error('Delete failed:', err);
                alert('Failed to delete purchase.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => loadPurchases(1));
    </script>
</body>

</html>