<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Customer</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 400px; margin: auto; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; margin-top: 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .error { color: red; margin-top: 5px; }
        .success { color: green; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Add Customer (API Test)</h2>
    <form id="customerForm">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required>

        <label for="contact_info">Contact Info (numbers only)</label>
        <input type="text" id="contact_info" name="contact_info" pattern="^\d*$">

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"></textarea>

        <button type="submit">Add Customer</button>
        <div class="error" id="errorMsg"></div>
        <div class="success" id="successMsg"></div>
    </form>

    <script>
        const form = document.getElementById('customerForm');
        const errorMsg = document.getElementById('errorMsg');
        const successMsg = document.getElementById('successMsg');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.textContent = '';
            successMsg.textContent = '';

            const formData = {
                name: document.getElementById('name').value.trim(),
                contact_info: document.getElementById('contact_info').value.trim(),
                notes: document.getElementById('notes').value.trim()
            };

            if (!formData.name) {
                errorMsg.textContent = 'Name is required.';
                return;
            }

            if (formData.contact_info && !/^\d+$/.test(formData.contact_info)) {
                errorMsg.textContent = 'Contact info must contain only numbers.';
                return;
            }

            try {
                const response = await fetch('/AwadaPrinting/awadaprinting-api/customers/addcustomer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (!response.ok) {
                    errorMsg.textContent = data.error || `Something went wrong (HTTP ${response.status})`;
                    console.error('Error response:', data);
                } else {
                    window.location.href = 'customers.php';
                }
            } catch (err) {
                errorMsg.textContent = 'Error connecting to server. Check console.';
                console.error('Error submitting form:', err);
            }
        });
    </script>
</body>
</html>
