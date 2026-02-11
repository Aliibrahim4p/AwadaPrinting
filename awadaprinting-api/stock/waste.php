<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Waste Management</title>

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
        background: #f5f5f5;
    }

    .filters {
        text-align: center;
        margin-top: 20px;
    }

    input,
    select,
    button {
        padding: 6px;
        margin-right: 6px;
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
        <input type="text" id="search" placeholder="Search by stock ID">
        <select id="sortDir">
            <option value="ASC">Date Asc</option>
            <option value="DESC" selected>Date Desc</option>
        </select>
        <button onclick="loadWaste()">Search</button>
    </div>

    <div style="text-align:center;margin-top:10px;">
        <a href="stock.php">← Back to Stock</a>
    </div>

    <table id="waste-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Stock ID</th>
                <th>Stock Name</th>
                <th>Quantity</th>
                <th>Waste Date</th>
                <th>Reason</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <script>
    let currentPage = 1;
    const rowsPerPage = 20;

    function loadWaste(page = 1) {
        currentPage = page;

        const search = document.getElementById('search').value.trim();
        const sortDir = document.getElementById('sortDir').value;

        const params = new URLSearchParams();
        params.append('api', 1);
        params.append('page', page);
        params.append('limit', rowsPerPage);
        params.append('sortColumn', 'waste_date');
        params.append('sortDir', sortDir);

        if (search) params.append('search', search);

        fetch(`readwaste.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                renderTable(data.data || []);
                renderPagination(data.page || 1, data.total_pages || 1);
            })
            .catch(err => console.error('Error loading waste:', err));
    }

    function renderTable(items) {
        const tbody = document.querySelector('#waste-table tbody');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center;">
                    No waste records found.
                </td>
            </tr>`;
            return;
        }

        items.forEach(waste => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${waste.id}</td>
            <td>${waste.stock_id}</td>
            <td>${waste.stock_name || '-'}</td>
            <td>${waste.quantity}</td>
            <td>${new Date(waste.waste_date).toLocaleDateString()}</td>
            <td>${waste.reason || '-'}</td>
            <td>${waste.cost || '-'}</td>
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
                pagination.innerHTML += `<a onclick="loadWaste(${i})">${i}</a> `;
            }
        }
    }

    // Initial load
    loadWaste();
    </script>

</body>

</html>