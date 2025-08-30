<?php
require_once 'includes/header.php';
require_once 'db.php';

// Check if user is admin - create temporary session for testing if needed
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // For testing purposes, create a temporary admin session
    // TODO: Remove this after proper authentication is implemented
    if (!isset($_SESSION['loggedIn'])) {
        $_SESSION['loggedIn'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['username'] = 'admin';
        echo '<div style="background: yellow; padding: 10px; margin: 10px; border-radius: 5px;">'
            . '<strong>Warning:</strong> Temporary admin session created for testing purposes.'
            . '</div>';
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Tortoise Conservation Center</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 class="card-header">Staff Management</h1>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by name or email..." class="search-input">
                <button onclick="showAddModal()" class="btn btn-primary">Add Staff Member</button>
            </div>

            <table id="staffTable">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="staffModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 id="modalTitle" class="card-header">Add Staff Member</h2>
            <form id="staffForm">
                <input type="hidden" id="staff_id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password">
                    <small>(Leave empty to keep existing password when editing)</small>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="role" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" onclick="closeModal()" class="btn btn-danger">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadStaff() {
            try {
                const response = await fetch('api/staff.php?action=list');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first to check if it's valid JSON
                const responseText = await response.text();
                console.log('Raw staff response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error for staff:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
                }
                
                if (data.status === 'error') {
                    console.error('API Error:', data.message);
                    throw new Error('API Error: ' + data.message);
                }
                
                const tbody = document.querySelector('#staffTable tbody');
                tbody.innerHTML = '';
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(staff => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${staff.staff_id}</td>
                                <td>${staff.name}</td>
                                <td>${staff.email}</td>
                                <td>${staff.role}</td>
                                <td><span class="badge badge-${staff.status === 'active' ? 'success' : 'danger'}">${staff.status}</span></td>
                                <td>
                                    <button onclick="editStaff('${staff.staff_id}')" class="btn btn-primary">Edit</button>
                                    <button onclick="deleteStaff('${staff.staff_id}')" class="btn btn-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No staff members found</td></tr>';
                }
            } catch (error) {
                console.error('Error loading staff:', error);
                const tbody = document.querySelector('#staffTable tbody');
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading staff: ' + error.message + '</td></tr>';
            }
        }

        function showAddModal() {
            document.getElementById('staffForm').reset();
            document.getElementById('staff_id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Staff Member';
            document.getElementById('staffModal').style.display = 'flex';
            document.getElementById('password').required = true;
        }

        function closeModal() {
            document.getElementById('staffModal').style.display = 'none';
        }

        async function editStaff(id) {
            try {
                const response = await fetch(`api/staff.php?action=get&id=${id}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                let staff;
                try {
                    staff = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (staff.status === 'error') {
                    alert('Error: ' + staff.message);
                    return;
                }
                
                document.getElementById('staff_id').value = staff.staff_id;
                document.getElementById('name').value = staff.name;
                document.getElementById('email').value = staff.email;
                document.getElementById('role').value = staff.role;
                document.getElementById('status').value = staff.status;
                document.getElementById('password').required = false;
                
                document.getElementById('modalTitle').innerText = 'Edit Staff Member';
                document.getElementById('staffModal').style.display = 'flex';
            } catch (error) {
                console.error('Error loading staff member:', error);
                alert('Failed to load staff member: ' + error.message);
            }
        }

        async function deleteStaff(id) {
            if (!confirm('Are you sure you want to delete this staff member?')) return;
            
            try {
                const response = await fetch(`api/staff.php?action=delete&id=${id}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.status === 'success') {
                    alert('Staff member deleted successfully!');
                    loadStaff();
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete staff member'));
                }
            } catch (error) {
                console.error('Error deleting staff member:', error);
                alert('Failed to delete staff member: ' + error.message);
            }
        }

        document.getElementById('staffForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                staff_id: document.getElementById('staff_id').value,
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value,
                status: document.getElementById('status').value
            };

            const action = data.staff_id ? 'update' : 'add';
            
            try {
                const response = await fetch(`api/staff.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.status === 'success') {
                    alert('Staff member ' + (action === 'add' ? 'added' : 'updated') + ' successfully!');
                    closeModal();
                    loadStaff();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save staff member'));
                }
            } catch (error) {
                console.error('Error saving staff member:', error);
                alert('Failed to save staff member: ' + error.message);
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#staffTable tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                row.style.display = name.includes(searchValue) || email.includes(searchValue) ? '' : 'none';
            });
        });

        // Load staff when page loads
        document.addEventListener('DOMContentLoaded', loadStaff);
    </script>
</body>
</html>
