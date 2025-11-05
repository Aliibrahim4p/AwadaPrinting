<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Customer (Demo)</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 500px; margin: auto; text-align: center; }
        .warn { color: #b30000; font-weight: bold; }
        button { padding: 10px 16px; margin: 8px; }
        .danger { background: #dc3545; color: #fff; border: none; }
        .secondary { background: #6c757d; color: #fff; border: none; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Delete Customer</h2>
    <p class="warn">This will mark the customer as inactive (soft delete).</p>
    <p id="cust"></p>
    <div>
        <button class="secondary" onclick="cancel()">Cancel</button>
        <button class="danger" onclick="doDelete()">Delete</button>
    </div>
    <div id="error" class="error"></div>
</div>

<script>
    function getQueryParam(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    function cancel() {
        window.location.href = 'customers.php';
    }

    async function preload() {
        const id = getQueryParam('id');
        const el = document.getElementById('cust');
        const error = document.getElementById('error');
        if (!id) { error.textContent = 'Missing customer ID.'; return; }
        try {
            const res = await fetch(`/AwadaPrinting/awadaprinting-api/customers/viewcustomer.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            if (!res.ok) {
                error.textContent = data.error || 'Failed to load customer.';
                return;
            }
            const c = data.customer;
            el.textContent = `Are you sure you want to delete: [${c.id}] ${c.name}?`;
        } catch (e) {
            error.textContent = 'Error connecting to server.';
            console.error(e);
        }
    }

    async function doDelete() {
        const id = getQueryParam('id');
        const error = document.getElementById('error');
        error.textContent = '';
        try {
            const res = await fetch(`/AwadaPrinting/awadaprinting-api/customers/deletecustomer.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            if (!res.ok) {
                error.textContent = data.error || 'Delete failed.';
                return;
            }
            window.location.href = 'customers.php';
        } catch (e) {
            error.textContent = 'Error connecting to server.';
            console.error(e);
        }
    }

    preload();
</script>
</body>
</html>
