<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Stock Item</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 30px;
        background-color: #f9f9f9;
    }

    h2 {
        color: #333;
    }

    form {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input {
        width: 100%;
        padding: 6px;
        margin-top: 5px;
        box-sizing: border-box;
    }

    button {
        padding: 8px 15px;
        margin-top: 15px;
        cursor: pointer;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 4px;
    }

    button:hover {
        background-color: #0056b3;
    }

    .preview-box {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    pre {
        background: #222;
        color: #0f0;
        padding: 15px;
        border-radius: 8px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    </style>
</head>

<body>
    <h2>Add Stock Item</h2>

    <form id="stockForm">
        <label for="name">Stock Name:</label>
        <input type="text" id="name" placeholder="Enter stock name" required>

        <label for="unit">Unit of Measure:</label>
        <select id="unit" required>
        </select>

        <button type="button" onclick="submitStock()">Add Stock</button>
    </form>

    <div class="preview-box">
        <h3>Live Preview</h3>
        <pre id="stockPreview">{}</pre>
    </div>

    <script>
    const stockApi = '../stock/addstock.php';
    const unitApi = '../stock/readunitofmeasure.php';

    // Build stock data from form inputs
    function buildStockData() {
        return {
            name: document.getElementById('name').value.trim(),
            unit_of_measure: document.getElementById('unit').value.trim()
        };
    }

    // Update the live JSON preview
    function updatePreview() {
        const data = buildStockData();
        document.getElementById('stockPreview').textContent = JSON.stringify(data, null, 2);
    }

    // Load units from the API and populate the select dropdown
    async function loadUnits() {
        try {
            const res = await fetch(unitApi);
            if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);

            const result = await res.json();
            const unitSelect = document.getElementById('unit');
            unitSelect.innerHTML = ''; // Clear existing options

            result.data.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit;
                option.textContent = unit;
                unitSelect.appendChild(option);
            });

            // Update preview with first unit selected
            updatePreview();
        } catch (e) {
            console.error('Error loading units:', e);
            console.log('Response:', e.message);
            alert('Failed to load units of measure: ' + e.message);
        }
    }

    // Submit stock data to the API
    async function submitStock() {
        const data = buildStockData();

        if (!data.name || !data.unit_of_measure) {
            alert('Please fill in both fields.');
            return;
        }

        try {
            const res = await fetch(stockApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
            const result = await res.json();

            if (result.stock_id) {
                alert('Stock item added successfully! ID: ' + result.stock_id);
                document.getElementById('stockForm').reset();
                updatePreview();
            } else {
                alert('Error: ' + (result.error || 'Unknown'));
            }
        } catch (e) {
            console.error('Error adding stock item:', e);
            alert('Error adding stock item: ' + e.message);
        }
    }

    // Initialize form events and load units
    window.addEventListener('DOMContentLoaded', () => {
        const nameInput = document.getElementById('name');
        const unitSelect = document.getElementById('unit');

        nameInput.addEventListener('input', updatePreview);
        unitSelect.addEventListener('change', updatePreview);

        // Load units after DOM is ready
        loadUnits();
        updatePreview();
    });
    </script>

</body>

</html>