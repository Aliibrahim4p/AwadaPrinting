<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customers</title>
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

        /* hide ID */
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
        <button onclick="window.location.href='addcustomer.php'">Add Customer</button>
        <input type="text" id="search" placeholder="Search by name">
        <select id="sort">
            <option value="ASC">Name Asc</option>
            <option value="DESC">Name Desc</option>
        </select>
        <button onclick="loadCustomers()">Search</button>
    </div>

    <table id="customers-table">
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
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="pagination" id="pagination" style="text-align:center; margin-top:20px;"></div>

    <script>
        let currentPage = 1;
        const rowsPerPage = 20;

        function loadCustomers(page = 1) {
            currentPage = page;
            const search = document.getElementById('search').value;
            const sort = document.getElementById('sort').value;

            fetch(
                `../customers/readcustomers.php?api=1&page=${page}&limit=${rowsPerPage}&search=${encodeURIComponent(search)}&sort=${sort}&sortColumn=name`
            )
                .then(res => res.json())
                .then(data => {
                    const tbody = document.querySelector('#customers-table tbody');
                    tbody.innerHTML = '';

                    if (data.data.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="7" style="text-align:center;">No customers found.</td></tr>`;
                    } else {
                        data.data.forEach(c => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${c.id}</td>
                                <td>${c.name}</td>
                                <td>${c.contact_info}</td>
                                <td>${c.notes}</td>
                                <td>${c.created_at}</td>
                                <td>${c.updated_at}</td>
                                <td>
                                    <button onclick="window.location.href='viewcustomer.php?id=${c.id}'">View</button>
                                    <button onclick="window.location.href='updatecustomer.php?id=${c.id}'">Update</button>
                                    
                                    <button onclick="if(confirm('Are you sure you want to delete this customer?')) deleteCustomer(${c.id})">Delete</button>
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
                            pagination.innerHTML += `<a onclick="loadCustomers(${i})">${i}</a> `;
                        }
                    }
                });
        }

        function deleteCustomer(id) {
            fetch(`deletecustomer.php?id=${id}`)
                .then(() => loadCustomers(currentPage));
        }

        // Initial load
        loadCustomers();
    </script>
</body>

</html>