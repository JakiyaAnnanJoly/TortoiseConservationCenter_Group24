<?php
require_once 'includes/header.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tortoise Management - Conservation Center</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <div class="container">
        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by Tortoise ID" class="search-input">
                <button onclick="searchTortoise()" class="btn btn-primary">Search</button>
            </div>

            <h2 class="card-header">General Tortoise Records</h2>
            <table id="tortoiseTable">
                <thead>
                    <tr>
                        <th>Tortoise ID</th>
                        <th>Species</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Weight (kg)</th>
                        <th>Length (cm)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn btn-primary" onclick="addTortoise()">Add Tortoise</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
                <h3 class="card-header">Weight Distribution</h3>
                <div style="height: 300px; padding: 20px;">
                    <canvas id="weightChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h3 class="card-header">Species Distribution</h3>
                <div style="height: 300px; padding: 20px;">
                    <canvas id="speciesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        const speciesCtx = document.getElementById('speciesChart').getContext('2d');

        let weightChart = new Chart(weightCtx, {
            type: 'bar',
            data: { 
                labels: [], 
                datasets: [{ 
                    label: 'Weight (kg)', 
                    data: [], 
                    backgroundColor: '#4CAF50' 
                }] 
            },
            options: { 
                responsive: true, 
                plugins: { 
                    legend: { display: false } 
                }, 
                scales: { 
                    y: { beginAtZero: true } 
                } 
            }
        });

        let speciesChart = new Chart(speciesCtx, {
            type: 'pie',
            data: { 
                labels: [], 
                datasets: [{ 
                    data: [], 
                    backgroundColor: ['#4CAF50','#FF9800','#2196F3'] 
                }] 
            },
            options: { responsive: true }
        });

        // Load data from database
        async function loadTortoises() {
            try {
                const response = await fetch('api/tortoises.php?action=get');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'error') {
                    console.error('API Error:', data.message);
                    throw new Error('API Error: ' + data.message);
                }
                
                const table = document.getElementById('tortoiseTable');
                const tbody = table.querySelector('tbody');
                tbody.innerHTML = '';
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(tortoise => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td>${tortoise.tortoise_id}</td>
                            <td>${tortoise.species}</td>
                            <td>${tortoise.date_of_birth}</td>
                            <td>${tortoise.gender}</td>
                            <td>${tortoise.weight}</td>
                            <td>${tortoise.length}</td>
                            <td>${tortoise.status}</td>
                            <td>
                                <button class="btn btn-danger" onclick="deleteTortoise('${tortoise.tortoise_id}')">Delete</button>
                            </td>
                        `;
                    });
                    updateCharts(data);
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No tortoises found</td></tr>';
                }
            } catch (error) {
                console.error('Error loading tortoises:', error);
                const tbody = document.querySelector('#tortoiseTable tbody');
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Error loading tortoises: ' + error.message + '</td></tr>';
            }
        }

        async function addTortoise() {
            const data = {
                tortoise_id: prompt('Enter Tortoise ID:'),
                species: prompt('Enter Species:'),
                date_of_birth: prompt('Enter Date of Birth (YYYY-MM-DD):'),
                gender: prompt('Enter Gender (male/female/unknown):'),
                weight: prompt('Enter Weight (kg):'),
                length: prompt('Enter Length (cm):'),
                status: prompt('Enter Status (healthy/sick/quarantine):')
            };

            if (!data.tortoise_id) {
                alert('Tortoise ID is required');
                return;
            }

            try {
                const response = await fetch('api/tortoises.php?action=add', {
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
                    alert('Tortoise added successfully!');
                    loadTortoises();
                } else {
                    alert('Error: ' + (result.message || 'Failed to add tortoise'));
                }
            } catch (error) {
                console.error('Error adding tortoise:', error);
                alert('Failed to add tortoise: ' + error.message);
            }
        }

        async function deleteTortoise(id) {
            if (!confirm('Are you sure you want to delete this tortoise?')) return;

            try {
                const response = await fetch(`api/tortoises.php?action=delete&id=${id}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Tortoise deleted successfully!');
                    loadTortoises();
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete tortoise'));
                }
            } catch (error) {
                console.error('Error deleting tortoise:', error);
                alert('Failed to delete tortoise: ' + error.message);
            }
        }

        function updateCharts(data) {
            // Weight chart
            weightChart.data.labels = data.map(t => t.tortoise_id);
            weightChart.data.datasets[0].data = data.map(t => parseFloat(t.weight) || 0);
            weightChart.update();

            // Species chart
            const speciesCounts = data.reduce((acc, t) => {
                acc[t.species] = (acc[t.species] || 0) + 1;
                return acc;
            }, {});
            
            speciesChart.data.labels = Object.keys(speciesCounts);
            speciesChart.data.datasets[0].data = Object.values(speciesCounts);
            speciesChart.update();
        }

        function searchTortoise() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tortoiseTable tbody tr');
            
            rows.forEach(row => {
                if (row.cells.length > 0) {
                    const id = row.cells[0].textContent.toLowerCase();
                    row.style.display = id.includes(input) ? '' : 'none';
                }
            });
        }

        // Add search event listener
        document.getElementById('searchInput').addEventListener('keyup', searchTortoise);
        
        // Load tortoises when page loads
        document.addEventListener('DOMContentLoaded', loadTortoises);
    </script>
</body>
</html>