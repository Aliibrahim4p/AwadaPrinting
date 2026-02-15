<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Items</title>
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
        <button onclick="window.location.href='additemform.php'">Add New Item</button>

        <input type="text" id="search" placeholder="Search name, description, price">

        <select id="sort">
            <option value="ASC">Created Asc</option>
            <option value="DESC" selected>Created Desc</option>
        </select>

        <button onclick="loadItems()">Search</button>
    </div>

    <table id="items-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Selling Price</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>

            <!-- Filter row like purchases page -->

        </thead>

        <tbody></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <script>
    let currentPage = 1;
    const rowsPerPage = 20;





    // Load items
    function loadItems(page = 1) {
        currentPage = page;

        const sortDir = document.getElementById('sort').value;
        const general = document.getElementById('search').value.trim();

        const params = new URLSearchParams();
        params.append('api', 1);
        params.append('page', page);
        params.append('limit', rowsPerPage);
        params.append('sortColumn', 'created_at');
        params.append('sortDir', sortDir);

        if (general) params.append('search', general);


        // IMPORTANT: change this endpoint if your backend file is different
        fetch(`readitems.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                renderTable(data.data || []);
                renderPagination(data.page || 1, data.total_pages || 1);
            })
            .catch(err => console.error('Error loading items:', err));
    }

    // Render table
    function renderTable(items) {
        const tbody = document.querySelector('#items-table tbody');
        tbody.innerHTML = '';

        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No items found.</td></tr>`;
            return;
        }

        items.forEach(i => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${i.name || '-'}</td>
                    <td>${i.description || '-'}</td>
                    <td>${i.selling_price || 0}</td>
                    <td>${i.created_at || '-'}</td>
                    <td>
                        <button onclick="viewItem(${i.id})">View</button>
                        <button onclick="deleteItem(${i.id})">Delete</button>
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
            else pagination.innerHTML += `<a onclick="loadItems(${i})">${i}</a> `;
        }
    }

    // Actions (adjust URLs to your pages)
    function viewItem(id) {
        window.location.href = `viewitemdemo.php?id=${id}`;
    }



    async function deleteItem(id) {
        if (!confirm('Delete this item?')) return;

        // IMPORTANT: change endpoint if yours differs
        await fetch(`deleteitem.php?id=${id}`, {
            method: 'DELETE'
        });

        loadItems(currentPage);
    }

    // Initial load
    loadItems();
    </script>

</body>

</html>