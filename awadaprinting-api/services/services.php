<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management</title>
</head>

<body>
    <!-- // A page to manage 3rd party services -->
    <h1>Services Management</h1>
    <input type="text" id="serviceName" size="50" placeholder="Enter service name" />
    <button type="button" onclick="addService()">➕ Add Service</button>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="servicesTableBody">
            <!-- Services will be loaded here via JavaScript -->
        </tbody>
    </table>
</body>
<script>
async function loadServices() {
    const response = await fetch('readservices.php?api=1');
    const data = await response.json();
    const services = data.data;
    const tableBody = document.getElementById('servicesTableBody');
    tableBody.innerHTML = '';
    services.forEach(service => {
        const row = document.createElement('tr');
        row.innerHTML = `
                <td>${service.id}</td>
                <td>${service.name}</td>
                <td>
                    <button onclick="deleteService(${service.id})">Delete</button>
                </td>
            `;
        tableBody.appendChild(row);
    });
}



function deleteService(id) {
    if (!confirm('Are you sure you want to delete this service?')) {
        return;
    }
    fetch('deleteService.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Error deleting service: ' + data.error);
            } else {
                alert(data.message);
                loadServices();
            }
        })
        .catch(err => {
            alert('Failed to delete service: ' + err.message);
        });
}

function addService() {
    const nameInput = document.getElementById('serviceName');
    const name = nameInput.value.trim();
    if (!name) {
        alert('Please enter a service name.');
        return;
    }
    fetch('addService.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Error adding service: ' + data.error);
            } else {
                alert(data.message);
                nameInput.value = '';
                loadServices();
            }
        })
        .catch(err => {
            alert('Failed to add service: ' + err.message);
        });
}

// Load services when the page loads
loadServices();
</script>

</html>