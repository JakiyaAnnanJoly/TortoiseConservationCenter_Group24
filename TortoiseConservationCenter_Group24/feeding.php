<?php
require_once 'includes/header.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feeding Management - Tortoise Conservation Center</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 class="card-header">Feeding Management</h1>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by Tortoise ID..." class="search-input">
                <button onclick="showAddModal()" class="btn btn-primary">Add Feeding Record</button>
            </div>

            <table id="feedingTable">
                <thead>
                    <tr>
                        <th>Feeding ID</th>
                        <th>Tortoise ID</th>
                        <th>Feed Time</th>
                        <th>Food Type</th>
                        <th>Quantity (g)</th>
                        <th>Staff</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="feedingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 id="modalTitle" class="card-header">Add Feeding Record</h2>
            <form id="feedingForm">
                <input type="hidden" id="feeding_id">
                <div class="form-group">
                    <label>Tortoise ID</label>
                    <input type="text" id="tortoise_id" required>
                </div>
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="number" id="staff_id" required placeholder="Enter Staff ID">
                </div>
                <div class="form-group">
                    <label>Food Type</label>
                    <select id="food_type" required>
                        <option value="">Select Food Type</option>
                        <option value="Vegetables">Vegetables</option>
                        <option value="Fruits">Fruits</option>
                        <option value="Grass">Grass</option>
                        <option value="Hay">Hay</option>
                        <option value="Supplements">Supplements</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity (g)</label>
                    <input type="number" id="quantity" required>
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
                const response = await fetch('api/feeding.php?action=get');
                const data = await response.json();
                const tbody = document.querySelector('#feedingTable tbody');
                tbody.innerHTML = '';
                
                data.forEach(record => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${record.feeding_id}</td>
                            <td>${record.tortoise_id}</td>
                            <td>${new Date(record.feed_time).toLocaleDateString()}</td>
                            <td>${record.food_type}</td>
                            <td>${record.quantity}</td>
                            <td>Staff ID: ${record.staff_id}</td>
                            <td>
                                <button onclick="editRecord('${record.feeding_id}')" class="btn btn-primary">Edit</button>
                                <button onclick="deleteRecord('${record.feeding_id}')" class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Error loading feeding records:', error);
            }
        }

        function showAddModal() {
            document.getElementById('feedingForm').reset();
            document.getElementById('feeding_id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Feeding Record';
            document.getElementById('feedingModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('feedingModal').style.display = 'none';
        }

        async function editRecord(id) {
            alert('Edit functionality temporarily disabled. Please delete and re-add record.');
        }

        async function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this feeding record?')) return;
            
            try {
                await fetch(`api/feeding.php?action=delete&id=${id}`);
                loadRecords();
            } catch (error) {
                console.error('Error deleting record:', error);
            }
        }

        document.getElementById('feedingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                tortoise_id: document.getElementById('tortoise_id').value,
                staff_id: document.getElementById('staff_id').value,
                food_type: document.getElementById('food_type').value,
                quantity: document.getElementById('quantity').value
            };

            const action = 'add';
            
            try {
                await fetch(`api/feeding.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                closeModal();
                loadRecords();
            } catch (error) {
                console.error('Error saving record:', error);
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#feedingTable tbody tr');
            
            rows.forEach(row => {
                const tortoiseId = row.cells[1].textContent.toLowerCase();
                row.style.display = tortoiseId.includes(searchValue) ? '' : 'none';
            });
        });

        // Load records when page loads
        document.addEventListener('DOMContentLoaded', loadRecords);
    </script>
</body>
</html>
