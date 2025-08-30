<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/header.php';
require_once 'db.php';

// Test database connection and table existence
try {
    $testQuery = "SHOW TABLES LIKE 'enclosures'";
    $result = $conn->query($testQuery);
    if ($result->num_rows === 0) {
        // Create enclosures table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `enclosures` (
            `enclosure_id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `capacity` INT NOT NULL,
            `current_occupancy` INT DEFAULT 0,
            `temperature` DECIMAL(4,1),
            `humidity` DECIMAL(4,1),
            `last_cleaned` TIMESTAMP,
            `notes` TEXT
        )";
        $conn->query($createTable);
    } else {
        // Check if table has old structure and update if needed
        $columnsQuery = "SHOW COLUMNS FROM enclosures";
        $columnsResult = $conn->query($columnsQuery);
        $columns = [];
        while ($row = $columnsResult->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // If old structure detected, add new columns
        if (in_array('type', $columns) && !in_array('name', $columns)) {
            // Add missing columns for new structure
            $alterQueries = [
                "ALTER TABLE enclosures ADD COLUMN name VARCHAR(100) AFTER enclosure_id",
                "ALTER TABLE enclosures ADD COLUMN capacity INT AFTER name", 
                "ALTER TABLE enclosures ADD COLUMN current_occupancy INT DEFAULT 0 AFTER capacity",
                "ALTER TABLE enclosures ADD COLUMN humidity DECIMAL(4,1) AFTER temperature",
                "ALTER TABLE enclosures ADD COLUMN last_cleaned TIMESTAMP AFTER humidity",
                "ALTER TABLE enclosures ADD COLUMN notes TEXT AFTER last_cleaned"
            ];
            
            foreach ($alterQueries as $query) {
                try {
                    $conn->query($query);
                } catch (Exception $e) {
                    // Column might already exist, continue
                }
            }
            
            // Copy data from old columns to new ones
            $conn->query("UPDATE enclosures SET name = type WHERE name IS NULL OR name = ''");
            $conn->query("UPDATE enclosures SET capacity = capacity_or_humidity WHERE capacity IS NULL OR capacity = 0");
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enclosure Management - Tortoise Conservation Center</title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        .capacity-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .occupancy-full {
            background-color: #f8d7da;
            color: #721c24;
        }
        .occupancy-high {
            background-color: #fff3cd;
            color: #856404;
        }
        .occupancy-normal {
            background-color: #d4edda;
            color: #155724;
        }
        .status-indicator {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Debug information -->
        <div id="debug-info" style="background: #f8f9fa; padding: 10px; margin-bottom: 20px; border-radius: 5px; display: none;">
            <strong>Debug Information:</strong>
            <div id="debug-content">Loading...</div>
        </div>
        
        <div class="card">
            <h1 class="card-header">Enclosure Management</h1>
            
            <!-- Add a loading indicator -->
            <div id="loading-indicator" style="text-align: center; padding: 20px;">
                <div class="spinner"></div>
                <p>Loading enclosures...</p>
            </div>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by enclosure name..." class="search-input">
                <button onclick="showAddModal()" class="btn btn-primary">Add Enclosure</button>
                <button onclick="toggleDebug()" class="btn btn-secondary">Debug</button>
            </div>

            <table id="enclosureTable">
                <thead>
                    <tr>
                        <th>Enclosure ID</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Current Occupancy</th>
                        <th>Temperature (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Last Cleaned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="enclosureModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 id="modalTitle" class="card-header">Add Enclosure</h2>
            <form id="enclosureForm">
                <input type="hidden" id="enclosure_id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="name" required placeholder="e.g., Enclosure A1">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="capacity" required min="1" placeholder="Maximum number of tortoises">
                </div>
                <div class="form-group">
                    <label>Current Occupancy</label>
                    <input type="number" id="current_occupancy" min="0" placeholder="Current number of tortoises">
                    <div id="occupancyWarning" class="capacity-warning" style="display: none;">
                        Warning: Current occupancy cannot exceed capacity!
                    </div>
                </div>
                <div class="form-group">
                    <label>Temperature (°C)</label>
                    <input type="number" id="temperature" step="0.1" placeholder="e.g., 25.5">
                </div>
                <div class="form-group">
                    <label>Humidity (%)</label>
                    <input type="number" id="humidity" step="0.1" min="0" max="100" placeholder="e.g., 65.0">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="notes" placeholder="Additional notes about the enclosure"></textarea>
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" onclick="closeModal()" class="btn btn-danger">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Debug function
        function toggleDebug() {
            const debugDiv = document.getElementById('debug-info');
            if (debugDiv.style.display === 'none') {
                debugDiv.style.display = 'block';
                updateDebugInfo();
            } else {
                debugDiv.style.display = 'none';
            }
        }
        
        function updateDebugInfo() {
            const debugContent = document.getElementById('debug-content');
            debugContent.innerHTML = `
                <p>Current URL: ${window.location.href}</p>
                <p>API URL: ${window.location.origin}/TortoiseProjectFile/TortoiseConservationCenter_Group24/api/enclosures.php</p>
                <p>Page loaded at: ${new Date().toLocaleString()}</p>
            `;
        }
        
        async function loadEnclosures() {
            const loadingIndicator = document.getElementById('loading-indicator');
            const tbody = document.querySelector('#enclosureTable tbody');
            
            try {
                loadingIndicator.style.display = 'block';
                
                // Test API endpoint first
                console.log('Testing API connection...');
                const response = await fetch('api/enclosures.php?action=list');
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
                
                console.log('Parsed data:', data);
                
                if (data.status === 'error') {
                    console.error('API Error:', data.message);
                    showAlert('Error loading enclosures: ' + data.message, 'error');
                    return;
                }
                
                tbody.innerHTML = '';
                loadingIndicator.style.display = 'none';
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(enclosure => {
                        // Handle both old and new database structure
                        const id = enclosure.enclosure_id || enclosure.id;
                        const name = enclosure.name || enclosure.type || 'Unknown';
                        const capacity = enclosure.capacity || enclosure.capacity_or_humidity || 0;
                        const occupancy = enclosure.current_occupancy || 0;
                        const temperature = enclosure.temperature || 'N/A';
                        const humidity = enclosure.humidity || 'N/A';
                        const lastCleaned = enclosure.last_cleaned ? 
                            new Date(enclosure.last_cleaned).toLocaleDateString() : 'Never';
                        
                        // Calculate occupancy status
                        const occupancyRate = occupancy / capacity;
                        let occupancyClass = 'occupancy-normal';
                        let occupancyText = 'Normal';
                        
                        if (occupancyRate >= 1) {
                            occupancyClass = 'occupancy-full';
                            occupancyText = 'Full';
                        } else if (occupancyRate >= 0.8) {
                            occupancyClass = 'occupancy-high';
                            occupancyText = 'High';
                        }
                        
                        tbody.innerHTML += `
                            <tr>
                                <td>${id}</td>
                                <td>${name}</td>
                                <td>${capacity}</td>
                                <td>
                                    ${occupancy}
                                    <span class="status-indicator ${occupancyClass}">${occupancyText}</span>
                                </td>
                                <td>${temperature}</td>
                                <td>${humidity}</td>
                                <td>${lastCleaned}</td>
                                <td>
                                    <button onclick="editEnclosure('${id}')" class="btn btn-primary">Edit</button>
                                    <button onclick="deleteEnclosure('${id}')" class="btn btn-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No enclosures found - Click "Add Enclosure" to create your first enclosure</td></tr>';
                }
            } catch (error) {
                console.error('Error loading enclosures:', error);
                loadingIndicator.style.display = 'none';
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Error loading enclosures. Please check the console for details.</td></tr>';
                showAlert('Failed to load enclosures: ' + error.message, 'error');
            }
        }

        function showAddModal() {
            document.getElementById('enclosureForm').reset();
            document.getElementById('enclosure_id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Enclosure';
            document.getElementById('enclosureModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('enclosureModal').style.display = 'none';
        }

        async function editEnclosure(id) {
            try {
                const response = await fetch(`api/enclosures.php?action=get&id=${id}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const enclosure = await response.json();
                
                if (enclosure.status === 'error') {
                    showAlert('Error: ' + enclosure.message, 'error');
                    return;
                }
                
                // Handle both old and new database structure
                document.getElementById('enclosure_id').value = enclosure.enclosure_id || enclosure.id || '';
                document.getElementById('name').value = enclosure.name || enclosure.type || '';
                document.getElementById('capacity').value = enclosure.capacity || enclosure.capacity_or_humidity || '';
                document.getElementById('current_occupancy').value = enclosure.current_occupancy || 0;
                document.getElementById('temperature').value = enclosure.temperature || '';
                document.getElementById('humidity').value = enclosure.humidity || '';
                document.getElementById('notes').value = enclosure.notes || '';
                
                document.getElementById('modalTitle').innerText = 'Edit Enclosure';
                document.getElementById('enclosureModal').style.display = 'flex';
            } catch (error) {
                console.error('Error loading enclosure:', error);
                showAlert('Failed to load enclosure details.', 'error');
            }
        }

        async function deleteEnclosure(id) {
            if (!confirm('Are you sure you want to delete this enclosure?')) return;
            
            try {
                const response = await fetch(`api/enclosures.php?action=delete&id=${id}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Enclosure deleted successfully!', 'success');
                    loadEnclosures();
                } else {
                    showAlert('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting enclosure:', error);
                showAlert('Failed to delete enclosure.', 'error');
            }
        }

        document.getElementById('enclosureForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validation
            const currentOccupancy = parseInt(document.getElementById('current_occupancy').value) || 0;
            const capacity = parseInt(document.getElementById('capacity').value);
            
            if (currentOccupancy > capacity) {
                showAlert('Current occupancy cannot exceed capacity!', 'error');
                return;
            }
            
            const data = {
                enclosure_id: document.getElementById('enclosure_id').value,
                name: document.getElementById('name').value.trim(),
                capacity: capacity,
                current_occupancy: currentOccupancy,
                temperature: document.getElementById('temperature').value,
                humidity: document.getElementById('humidity').value,
                notes: document.getElementById('notes').value.trim()
            };

            const action = data.enclosure_id ? 'update' : 'add';
            
            try {
                const response = await fetch(`api/enclosures.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Enclosure ' + (action === 'add' ? 'added' : 'updated') + ' successfully!', 'success');
                    closeModal();
                    loadEnclosures();
                } else {
                    showAlert('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving enclosure:', error);
                showAlert('Failed to save enclosure.', 'error');
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#enclosureTable tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                row.style.display = name.includes(searchValue) ? '' : 'none';
            });
        });

        // Load enclosures when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadEnclosures();
            
            // Add real-time validation for occupancy vs capacity
            const capacityInput = document.getElementById('capacity');
            const occupancyInput = document.getElementById('current_occupancy');
            const warning = document.getElementById('occupancyWarning');
            
            function checkOccupancy() {
                const capacity = parseInt(capacityInput.value) || 0;
                const occupancy = parseInt(occupancyInput.value) || 0;
                
                if (occupancy > capacity && capacity > 0) {
                    warning.style.display = 'block';
                    occupancyInput.style.borderColor = '#e74c3c';
                } else {
                    warning.style.display = 'none';
                    occupancyInput.style.borderColor = '#e0e0e0';
                }
            }
            
            capacityInput.addEventListener('input', checkOccupancy);
            occupancyInput.addEventListener('input', checkOccupancy);
        });
        
        // Alert function for user feedback
        function showAlert(message, type = 'info') {
            // Remove existing alerts
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'error' : 'success'}`;
            alertDiv.textContent = message;
            
            // Insert at the top of the container
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
