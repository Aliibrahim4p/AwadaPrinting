<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Supplier (Demo)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        form {
            max-width: 400px;
            margin: auto;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0069d9;
        }

        .error {
            color: red;
            margin-top: 5px;
        }

        .success {
            color: green;
            margin-top: 10px;
        }

        .actions {
            text-align: center;
            margin-top: 10px;
        }

        .actions a {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <h2 style="text-align:center;">Update Supplier (API Test)</h2>
    <form id="updateForm">
        <input type="hidden" id="id">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required>

        <label for="contact_info">Contact Info (numbers only)</label>
        <input type="text" id="contact_info" name="contact_info" pattern="^\d*$">

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"></textarea>

        <button type="submit">Save Changes</button>
        <div class="error" id="errorMsg"></div>
        <div class="success" id="successMsg"></div>
    </form>
    <div class="actions">
        <a href="suppliers.php">Back to Suppliers</a>
    </div>

    <script>
        function getQueryParam(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name);
        }

        async function loadSupplier() {
            const id = getQueryParam('id');
            const errorMsg = document.getElementById('errorMsg');
            errorMsg.textContent = '';
            if (!id) {
                errorMsg.textContent = 'Missing supplier ID.';
                return;
            }
            try {
                const res = await fetch(
                    `/AwadaPrinting/awadaprinting-api/suppliers/viewsupplier.php?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (!res.ok) {
                    errorMsg.textContent = data.error || 'Failed to load supplier.';
                    return;
                }
                const s = data.supplier;
                document.getElementById('id').value = s.id;
                document.getElementById('name').value = s.name || '';
                document.getElementById('contact_info').value = s.contact_info || '';
                document.getElementById('notes').value = s.notes || '';
            } catch (e) {
                errorMsg.textContent = 'Error connecting to server.';
                console.error(e);
            }
        }

        document.getElementById('updateForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorMsg = document.getElementById('errorMsg');
            const successMsg = document.getElementById('successMsg');
            errorMsg.textContent = '';
            successMsg.textContent = '';

            const id = document.getElementById('id').value;
            const name = document.getElementById('name').value.trim();
            const contact_info = document.getElementById('contact_info').value.trim();
            const notes = document.getElementById('notes').value.trim();

            if (!name) {
                errorMsg.textContent = 'Name is required.';
                return;
            }
            if (contact_info && !/^\d+$/.test(contact_info)) {
                errorMsg.textContent = 'Contact info must contain only numbers.';
                return;
            }

            try {
                const res = await fetch(
                    `/AwadaPrinting/awadaprinting-api/suppliers/updatesupplier.php?id=${encodeURIComponent(id)}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        contact_info,
                        notes
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    errorMsg.textContent = data.error || 'Update failed.';
                } else {
                    window.location.href = 'suppliers.php';
                }
            } catch (e) {
                errorMsg.textContent = 'Error connecting to server.';
                console.error(e);
            }
        });

        loadSupplier();
    </script>
</body>

</html>