<?php
require_once 'includes/header.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tortoise Health Management</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<div class="container">
    <h1 class="card-header">Health Management</h1>

    <div class="card">
        <div class="search-bar">
            <input id="searchInput" placeholder="Search by Tortoise ID..." class="search-input">
            <button onclick="showAddModal()" class="btn btn-primary">Add Health Record</button>
        </div>

        <table id="healthTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tortoise</th>
                    <th>Staff</th>
                    <th>Date</th>
                    <th>Weight (kg)</th>
                    <th>Temp (°C)</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="healthModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2 id="modalTitle" class="card-header">Add Health Record</h2>
        <form id="healthForm">
            <input type="hidden" id="health_id">
            <div class="form-group">
                <label>Tortoise ID</label>
                <select id="tortoise_id" required>
                    <option value="">Select Tortoise</option>
                    <option value="1">Tortoise #1 - Giant Tortoise</option>
                    <option value="2">Tortoise #2 - Hermann Tortoise</option>
                </select>
            </div>
            <div class="form-group">
                <label>Staff ID</label>
                <select id="staff_id" required>
                    <option value="">Select Staff</option>
                    <option value="1">Administrator</option>
                </select>
            </div>
            <div class="form-group">
                <label>Check Date</label>
                <input type="datetime-local" id="check_date" required>
            </div>
            <div class="form-group">
                <label>Weight (kg)</label>
                <input type="number" id="weight" step="0.01" min="0" placeholder="Weight in kg" required>
            </div>
            <div class="form-group">
                <label>Temperature (°C)</label>
                <input type="number" id="temperature" step="0.1" min="-10" max="50" placeholder="Temperature in °C" required>
            </div>
            <div class="form-group">
                <label>Health Status</label>
                <select id="health_status" required>
                    <option value="">Select Status</option>
                    <option value="Healthy">Healthy</option>
                    <option value="Needs Attention">Needs Attention</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea id="notes" placeholder="Notes"></textarea>
            </div>
            <div class="form-group" style="text-align: right;">
                <button type="button" onclick="closeModal()" class="btn btn-danger">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
async function loadRecords() {
    try {
        const res = await fetch('api/health.php?action=get');
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        const tbody = document.querySelector('#healthTable tbody');
        tbody.innerHTML = '';
        
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(r => {
                tbody.innerHTML += `
                  <tr class="border-b">
                    <td>${r.health_id}</td>
                    <td>${r.tortoise_id}</td>
                    <td>${r.staff_id}</td>
                    <td>${r.check_date}</td>
                    <td>${r.weight}</td>
                    <td>${r.temperature}</td>
                    <td><span class="badge badge-${r.health_status === 'Healthy' ? 'success' : r.health_status === 'Needs Attention' ? 'warning' : 'danger'}">${r.health_status}</span></td>
                    <td>${r.notes || ''}</td>
                    <td>
                        <button onclick="editRecord(${r.health_id})" class="btn btn-primary">Edit</button>
                        <button onclick="deleteRecord(${r.health_id})" class="btn btn-danger">Delete</button>
                    </td>
                  </tr>`;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">No health records found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading health records:', error);
        const tbody = document.querySelector('#healthTable tbody');
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: red;">Error loading health records</td></tr>';
    }
}

async function showAddModal() {
    document.getElementById('healthForm').reset();
    document.getElementById('health_id').value = '';
    
    // Set current date and time as default
    const now = new Date();
    const localISOTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('check_date').value = localISOTime;
    
    document.getElementById('modalTitle').innerText = "Add Health Record";
    document.getElementById('healthModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('healthModal').style.display = 'none';
}

async function editRecord(id) {
    try {
        const res = await fetch('api/health.php?action=get_single&id=' + id);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const r = await res.json();
        
        if (r.status === 'error') {
            alert('Error: ' + r.message);
            return;
        }
        
        document.getElementById('health_id').value = r.health_id;
        document.getElementById('tortoise_id').value = r.tortoise_id;
        document.getElementById('staff_id').value = r.staff_id;
        
        // Format check_date for datetime-local input
        if (r.check_date) {
            const date = new Date(r.check_date);
            const localISOTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('check_date').value = localISOTime;
        }
        
        document.getElementById('weight').value = r.weight;
        document.getElementById('temperature').value = r.temperature;
        document.getElementById('health_status').value = r.health_status;
        document.getElementById('notes').value = r.notes || '';
        document.getElementById('modalTitle').innerText = "Edit Health Record";
        document.getElementById('healthModal').style.display = 'flex';
    } catch (error) {
        console.error('Error loading health record:', error);
        alert('Failed to load health record details.');
    }
}

document.getElementById('healthForm').addEventListener('submit', async e => {
    e.preventDefault();
    
    // Get form values
    const tortoiseId = document.getElementById('tortoise_id').value;
    const staffId = document.getElementById('staff_id').value;
    const weight = document.getElementById('weight').value;
    const temperature = document.getElementById('temperature').value;
    const healthStatus = document.getElementById('health_status').value;
    
    // Validate required fields
    if (!tortoiseId || !staffId || !weight || !temperature || !healthStatus) {
        alert('Please fill in all required fields (Tortoise ID, Staff ID, Weight, Temperature, Health Status)');
        return;
    }
    
    // Validate numeric fields
    if (isNaN(parseFloat(weight)) || parseFloat(weight) <= 0) {
        alert('Please enter a valid positive weight');
        return;
    }
    
    if (isNaN(parseFloat(temperature))) {
        alert('Please enter a valid temperature');
        return;
    }
    
    const data = {
        health_id: document.getElementById('health_id').value,
        tortoise_id: parseInt(tortoiseId),
        staff_id: parseInt(staffId),
        check_date: document.getElementById('check_date').value,
        weight: parseFloat(weight),
        temperature: parseFloat(temperature),
        health_status: healthStatus,
        notes: document.getElementById('notes').value || ''
    };
    
    console.log('Submitting data:', data);
    
    const action = data.health_id ? 'update' : 'add';
    
    try {
        const response = await fetch('api/health.php?action=' + action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get response text first to check if it's valid JSON
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
        }
        
        if (result.status === 'success') {
            alert('Health record ' + (action === 'add' ? 'added' : 'updated') + ' successfully!');
            closeModal();
            loadRecords();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving health record:', error);
        alert('Failed to save health record: ' + error.message);
    }
});

async function deleteRecord(id) {
    if (!confirm('Are you sure you want to delete this health record?')) return;
    
    try {
        const response = await fetch('api/health.php?action=delete&id=' + id);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Health record deleted successfully!');
            loadRecords();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting health record:', error);
        alert('Failed to delete health record.');
    }
}

document.addEventListener('DOMContentLoaded', loadRecords);
</script>
</body>
</html>




