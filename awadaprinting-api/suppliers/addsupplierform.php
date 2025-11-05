<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Supplier</title>
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
        textarea,
        button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        .error {
            color: red;
            margin-top: 5px;
        }

        .success {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h2>Add Supplier (API Test)</h2>
    <form id="supplierForm">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required>

        <label for="contact_info">Contact Info (numbers only)</label>
        <input type="text" id="contact_info" name="contact_info">

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"></textarea>

        <button type="submit">Add Supplier</button>
        <div class="error" id="errorMsg"></div>
        <div class="success" id="successMsg"></div>
    </form>

    <script>
        const form = document.getElementById('supplierForm');
        const errorMsg = document.getElementById('errorMsg');
        const successMsg = document.getElementById('successMsg');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.textContent = '';
            successMsg.textContent = '';

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
                const response = await fetch('/awadaprinting/awadaprinting-api/suppliers/addsupplier.php', {
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

                const text = await response.text(); // get raw response for debugging
                console.log('Raw response:', text);

                let data;
                try {
                    data = JSON.parse(text); // parse JSON
                } catch (err) {
                    console.error('Invalid JSON:', err);
                    errorMsg.textContent = 'Invalid response from server. Check console.';
                    return;
                }

                if (!response.ok) {
                    errorMsg.textContent = data.error || `Something went wrong (HTTP ${response.status})`;
                    console.error('Error response:', data);
                } else {
                    successMsg.textContent = `Supplier added successfully. ID: ${data.supplier_id}`;
                    form.reset();

                    // Redirect to suppliers.php after 1 second
                    setTimeout(() => {
                        window.location.href =
                            '/awadaprinting/awadaprinting-api/suppliers/suppliers.php';
                    }, 1000);
                }
            } catch (err) {
                console.error('Fetch error:', err);
                errorMsg.textContent = 'Error connecting to server. Check console for details.';
            }
        });
    </script>
</body>

</html>