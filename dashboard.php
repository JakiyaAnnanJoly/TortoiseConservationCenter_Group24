<?php
require_once 'includes/header.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tortoise Conservation Center</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <div class="container">
        <h1 class="card-header">Dashboard Overview</h1>
        
        <div class="stats-grid">
            <!-- Quick Stats -->
            <div class="stat-card">
                <div class="stat-number" id="totalTortoises">-</div>
                <div class="stat-label">Total Tortoises</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalEnclosures">-</div>
                <div class="stat-label">Active Enclosures</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="breedingEvents">-</div>
                <div class="stat-label">Breeding Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="healthChecks">-</div>
                <div class="stat-label">Health Checks Today</div>
            </div>
        </div>

        <div class="stats-grid">
            <!-- Charts -->
            <div class="card">
                <h3 class="card-header">Species Distribution</h3>
                <div style="height: 300px">
                    <canvas id="speciesChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h3 class="card-header">Health Status</h3>
                <div style="height: 300px">
                    <canvas id="healthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card">
            <h2 class="card-header">Recent Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="activitiesTable">
                    <!-- Activities will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('api/dashboard.php');
                const data = await response.json();
                
                // Update stats
                document.getElementById('totalTortoises').textContent = data.stats.totalTortoises;
                document.getElementById('totalEnclosures').textContent = data.stats.activeEnclosures;
                document.getElementById('breedingEvents').textContent = data.stats.breedingEvents;
                document.getElementById('healthChecks').textContent = data.stats.healthChecksToday;

                // Update species chart
                new Chart(document.getElementById('speciesChart'), {
                    type: 'pie',
                    data: {
                        labels: Object.keys(data.speciesDistribution),
                        datasets: [{
                            data: Object.values(data.speciesDistribution),
                            backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#F44336', '#9C27B0']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Update health status chart
                new Chart(document.getElementById('healthChart'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(data.healthStatus),
                        datasets: [{
                            data: Object.values(data.healthStatus),
                            backgroundColor: ['#4CAF50', '#FF9800', '#F44336']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Update activities table
                const activitiesTable = document.getElementById('activitiesTable');
                activitiesTable.innerHTML = data.recentActivities.map(activity => `
                    <tr>
                        <td>${activity.date}</td>
                        <td>${activity.activity}</td>
                        <td>${activity.details}</td>
                    </tr>
                `).join('');

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
