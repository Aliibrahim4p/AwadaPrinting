<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Overview</title>

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
        background: #f5f5f5;
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
    select {
        padding: 6px;
        margin-right: 6px;
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
        <input type="text" id="search" placeholder="Search stock name">
        <select id="sortDir">
            <option value="ASC">Quantity Asc</option>
            <option value="DESC" selected>Quantity Desc</option>
        </select>
        <button onclick="loadStock()">Search</button>


    </div>
    <a href="waste.php">Waste Management </a>
    <table id="stock-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Unit</th>
                <th>On Hand</th>
                <th>Reserved</th>
                <th>Available</th>
                <th>Add waste</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <script>
    let currentPage = 1;
    const rowsPerPage = 20;

    function loadStock(page = 1) {
        currentPage = page;

        const search = document.getElementById('search').value.trim();
        const sortDir = document.getElementById('sortDir').value;

        const params = new URLSearchParams();
        params.append('api', 1);
        params.append('page', page);
        params.append('limit', rowsPerPage);
        params.append('sortColumn', 'quantity_on_hand');
        params.append('sortDir', sortDir);

        if (search) params.append('search', search);

        fetch(`readstock.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                renderTable(data.data || []);
                renderPagination(data.page || 1, data.total_pages || 1);
            })
            .catch(err => console.error('Error loading stock:', err));
    }

    function renderTable(items) {
        const tbody = document.querySelector('#stock-table tbody');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;">
                        No stock items found.
                    </td>
                </tr>`;
            return;
        }

        items.forEach(item => {
            const available =
                (parseFloat(item.quantity_on_hand) || 0) -
                (parseFloat(item.quantity_reserved) || 0);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${item.name || '-'}</td>
                <td>${item.unit_of_measure || '-'}</td>
                <td>${item.quantity_on_hand || 0}</td>
                <td>${item.quantity_reserved || 0}</td>
                <td>${available}</td>
                <td>
                    <a href="addwastedemo.php?stockid=${item.id}">Add Waste</a>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderPagination(page, totalPages) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            if (i === page) {
                pagination.innerHTML += `<strong>${i}</strong> `;
            } else {
                pagination.innerHTML += `<a onclick="loadStock(${i})">${i}</a> `;
            }
        }
    }

    // initial load
    loadStock();
    </script>

</body>

</html>