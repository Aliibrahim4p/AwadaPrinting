<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Suppliers</title>
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    table {
        border-collapse: collapse;
        width: 80%;
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

    input[type="text"] {
        padding: 5px;
        width: 200px;
    }

    button {
        padding: 5px 10px;
    }

    .pagination a,
    .pagination strong {
        margin: 0 3px;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
</head>

<body>
    <div style="text-align:center; margin-top:20px;">
        <button onclick="window.location.href='addsupplierform.php'">Add Supplier</button>
        <input type="text" id="search" placeholder="Search by name">
        <select id="sort">
            <option value="ASC">Name Asc</option>
            <option value="DESC">Name Desc</option>
        </select>
        <button onclick="loadSuppliers()">Search</button>
    </div>

    <table id="suppliers-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact Info</th>
                <th>Notes</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
            <tr class="filter-row">
                <td></td>
                <td><input type=text id="name"></td>
                <td><input type=text id="contact_info"></td>
                <td><input type=text id="notes"></td>
                <td></td>
                <td></td>
                <td><button id="clear-filters">Clear Filter</button></td>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="pagination" id="pagination" style="text-align:center; margin-top:20px;"></div>

    <script>
    let currentPage = 1;
    const rowsPerPage = 20;

    document.getElementById('clear-filters').addEventListener('click', () => {
        document.querySelectorAll('.filter-row input').forEach(el => el.value = '');
        loadsuppliers(1); // reload after clearing
    });

    // Apply filters and return object
    function applyFilters() {
        const filters = {};
        const name = document.getElementById('name')?.value.trim();
        const contact = document.getElementById('contact_info')?.value.trim();
        const notes = document.getElementById('notes')?.value.trim();

        if (name) filters.name = name;
        if (contact) filters.contact_info = contact;
        if (notes) filters.notes = notes;

        return filters;
    }

    function loadSuppliers(page = 1) {
        currentPage = page;
        const sortDir = document.getElementById('sort').value;
        const search = document.getElementById('search').value.trim();
        const filters = applyFilters();

        // Build query parameters for PHP API
        const params = new URLSearchParams();
        params.append('api', 1);
        params.append('page', page);
        params.append('limit', rowsPerPage);
        params.append('sortColumn', 'name');
        params.append('sortDir', sortDir);

        if (search) params.append('query[general]', search);
        for (const key in filters) {
            params.append(`query[${key}]`, filters[key]);
        }

        fetch(`../suppliers/readsuppliers.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector('#suppliers-table tbody');
                tbody.innerHTML = '';

                if (data.data.length === 0) {
                    tbody.innerHTML =
                        `<tr><td colspan="7" style="text-align:center;">No suppliers found.</td></tr>`;
                } else {
                    data.data.forEach(s => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td>${s.id}</td>
                        <td>${s.name}</td>
                        <td>${s.contact_info}</td>
                        <td>${s.notes}</td>
                        <td>${s.created_at}</td>
                        <td>${s.updated_at}</td>
                        <td>
                            <button onclick="window.location.href='viewsupplier_demo.php?id=${s.id}'">View</button>
                            <button onclick="window.location.href='updatessupplierform.php?id=${s.id}'">Update</button>
                            <button onclick="deletesupplier(${s.id})">Delete</button>
                        </td>
                    `;
                        tbody.appendChild(tr);
                    });
                }

                // Pagination
                const pagination = document.getElementById('pagination');
                pagination.innerHTML = '';
                for (let i = 1; i <= data.total_pages; i++) {
                    if (i === data.page) {
                        pagination.innerHTML += `<strong>${i}</strong> `;
                    } else {
                        pagination.innerHTML += `<a onclick="loadsuppliers(${i})">${i}</a> `;
                    }
                }
            });
    }
    // Initial load
    loadSuppliers();
    </script>
</body>

</html>