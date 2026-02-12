<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Unit of Measure Management</title>

    <style>
    body {
        font-family: Arial, sans-serif;
    }

    table {
        border-collapse: collapse;
        width: 70%;
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

    .actions button {
        margin-right: 5px;
    }

    .top-bar {
        text-align: center;
        margin-top: 20px;
    }

    input {
        padding: 6px;
        margin-right: 5px;
    }

    button {
        padding: 6px 10px;
        cursor: pointer;
    }
    </style>
</head>

<body>

    <h2 style="text-align:center;">Unit of Measure</h2>

    <div class="top-bar">
        <input type="text" id="newUnitName" placeholder="New unit name">
        <button onclick="addUnit()">Add Unit</button>
        <a href="stock.php">Back to Stock</a>
    </div>

    <table id="unit-table">
        <thead>
            <tr>
                <th>Unit Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script>
    const readApi = 'readunits.php';
    const addApi = 'addunit.php';
    const updateApi = 'updateunit.php';
    const deleteApi = 'deleteunit.php';

    async function loadUnits() {
        try {
            const res = await fetch(readApi);
            const data = await res.json();
            renderTable(data.data || []);
        } catch (e) {
            console.error('Error loading units:', e);
        }
    }

    function renderTable(units) {
        const tbody = document.querySelector('#unit-table tbody');
        tbody.innerHTML = '';

        if (!units.length) {
            tbody.innerHTML = `
            <tr>
                <td colspan="3" style="text-align:center;">
                    No units found.
                </td>
            </tr>`;
            return;
        }

        units.forEach(unit => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>
                <input type="text" value="${unit}" id="unit-${unit}">
            </td>
            <td class="actions">
                <button onclick="updateUnit(${unit})">Save</button>
                <button onclick="deleteUnit(${unit})">Delete</button>
            </td>
        `;
            tbody.appendChild(tr);
        });
    }

    async function addUnit() {
        const name = document.getElementById('newUnitName').value.trim();
        if (!name) return alert('Enter unit name.');

        try {
            const res = await fetch(addApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name
                })
            });

            const result = await res.json();

            if (result.success) {
                document.getElementById('newUnitName').value = '';
                loadUnits();
            } else {
                alert(result.error || 'Failed to add unit.');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function updateUnit(id) {
        const name = document.getElementById(`unit-${id}`).value.trim();
        if (!name) return alert('Unit name cannot be empty.');

        try {
            const res = await fetch(updateApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id,
                    name
                })
            });

            const result = await res.json();

            if (!result.success) {
                alert(result.error || 'Update failed.');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function deleteUnit(id) {
        if (!confirm('Are you sure you want to delete this unit?')) return;

        try {
            const res = await fetch(deleteApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id
                })
            });

            const result = await res.json();

            if (result.success) {
                loadUnits();
            } else {
                alert(result.error || 'Delete failed.');
            }
        } catch (e) {
            console.error(e);
        }
    }

    loadUnits();
    </script>

</body>

</html>