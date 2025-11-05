<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customer (Demo)</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: auto; }
        .row { margin-bottom: 10px; }
        .label { font-weight: bold; width: 150px; display: inline-block; }
        .actions { margin-top: 20px; }
        button { padding: 8px 12px; margin-right: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <h2>View Customer</h2>
    <div id="details"></div>
    <div class="actions">
        <button onclick="goBack()">Back to Customers</button>
        <button id="editBtn" style="display:none;">Edit</button>
    </div>
    <div id="error" class="error"></div>
</div>

<script>
    function getQueryParam(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    function goBack() {
        window.location.href = 'customers.php';
    }

    async function loadCustomer() {
        const id = getQueryParam('id');
        const details = document.getElementById('details');
        const error = document.getElementById('error');
        const editBtn = document.getElementById('editBtn');
        error.textContent = '';
        details.innerHTML = '';

        if (!id) {
            error.textContent = 'Missing customer ID.';
            return;
        }
        try {
            const res = await fetch(`/AwadaPrinting/awadaprinting-api/customers/viewcustomer.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            if (!res.ok) {
                error.textContent = data.error || 'Failed to load customer.';
                return;
            }
            const c = data.customer;
            details.innerHTML = `
                <div class="row"><span class="label">ID:</span> <span>${c.id}</span></div>
                <div class="row"><span class="label">Name:</span> <span>${c.name || ''}</span></div>
                <div class="row"><span class="label">Contact Info:</span> <span>${c.contact_info || ''}</span></div>
                <div class="row"><span class="label">Notes:</span> <span>${c.notes || ''}</span></div>
                <div class="row"><span class="label">Created At:</span> <span>${c.created_at || ''}</span></div>
                <div class="row"><span class="label">Updated At:</span> <span>${c.updated_at || ''}</span></div>
            `;
            editBtn.style.display = 'inline-block';
            editBtn.onclick = () => window.location.href = `updatecustomerform.php?id=${encodeURIComponent(id)}`;
        } catch (e) {
            error.textContent = 'Error connecting to server.';
            console.error(e);
        }
    }

    loadCustomer();
</script>
</body>
</html>
