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
    <style>
        /* Override any external CSS with important declarations */
        body {
            background: #f8fafc !important;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .container {
            background: #f8fafc !important;
        }
        /* Ensure navigation styles don't interfere */
        header {
            margin-bottom: 0 !important;
        }
        /* Override any table styles */
        table, th, td {
            border: none !important;
        }
    </style>

</head>
<body>
    <div class="container" style="max-width: 1200px !important; margin: 0 auto !important; padding: 20px !important; background: #f8fafc !important;">
        <h1 style="color: #2d3748 !important; font-size: 2.5rem !important; font-weight: 700 !important; margin-bottom: 30px !important; text-align: left !important; font-family: 'Segoe UI', system-ui, sans-serif !important;">Dashboard Overview</h1>
        
        <div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important; gap: 20px !important; margin-bottom: 30px !important;">
            <!-- Quick Stats Cards -->
            <div style="background: white !important; border-radius: 8px !important; padding: 24px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important; transition: transform 0.2s ease !important;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="text-align: center !important;">
                    <div style="font-size: 3rem !important; font-weight: 700 !important; color: #2b6cb0 !important; margin-bottom: 8px !important; line-height: 1 !important;" id="totalTortoises">-</div>
                    <div style="color: #4a5568 !important; font-weight: 500 !important; font-size: 0.95rem !important;">Total Tortoises</div>
                </div>
            </div>
            <div style="background: white !important; border-radius: 8px !important; padding: 24px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important; transition: transform 0.2s ease !important;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="text-align: center !important;">
                    <div style="font-size: 3rem !important; font-weight: 700 !important; color: #38a169 !important; margin-bottom: 8px !important; line-height: 1 !important;" id="totalEnclosures">-</div>
                    <div style="color: #4a5568 !important; font-weight: 500 !important; font-size: 0.95rem !important;">Active Enclosures</div>
                </div>
            </div>
            <div style="background: white !important; border-radius: 8px !important; padding: 24px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important; transition: transform 0.2s ease !important;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="text-align: center !important;">
                    <div style="font-size: 3rem !important; font-weight: 700 !important; color: #d69e2e !important; margin-bottom: 8px !important; line-height: 1 !important;" id="breedingEvents">-</div>
                    <div style="color: #4a5568 !important; font-weight: 500 !important; font-size: 0.95rem !important;">Breeding Events</div>
                </div>
            </div>
            <div style="background: white !important; border-radius: 8px !important; padding: 24px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important; transition: transform 0.2s ease !important;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="text-align: center !important;">
                    <div style="font-size: 3rem !important; font-weight: 700 !important; color: #e53e3e !important; margin-bottom: 8px !important; line-height: 1 !important;" id="healthChecks">-</div>
                    <div style="color: #4a5568 !important; font-weight: 500 !important; font-size: 0.95rem !important;">Health Checks Today</div>
                </div>
            </div>
        </div>

        <div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)) !important; gap: 20px !important; margin-bottom: 30px !important;">
            <!-- Charts -->
            <div style="background: white !important; border-radius: 8px !important; padding: 20px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important;">
                <h3 style="color: #2d3748 !important; font-size: 1.5rem !important; font-weight: 600 !important; margin-bottom: 16px !important; padding-bottom: 12px !important; border-bottom: 2px solid #e2e8f0 !important;">Species Distribution</h3>
                <div style="height: 300px !important; padding: 20px !important;">
                    <canvas id="speciesChart"></canvas>
                </div>
            </div>
            <div style="background: white !important; border-radius: 8px !important; padding: 20px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important;">
                <h3 style="color: #2d3748 !important; font-size: 1.5rem !important; font-weight: 600 !important; margin-bottom: 16px !important; padding-bottom: 12px !important; border-bottom: 2px solid #e2e8f0 !important;">Health Status</h3>
                <div style="height: 300px !important; padding: 20px !important;">
                    <canvas id="healthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div style="background: white !important; border-radius: 8px !important; padding: 20px !important; box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15) !important; border: 1px solid #e2e8f0 !important;">
            <div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 20px !important; gap: 16px !important;">
                <input type="text" id="activitySearch" placeholder="Search activities..." 
                       style="flex: 1 !important; padding: 12px 16px !important; border: 1px solid #d1d5db !important; border-radius: 6px !important; font-size: 14px !important; background: white !important;">
                <button onclick="filterActivities()" 
                        style="background: #48bb78 !important; color: white !important; border: none !important; padding: 12px 24px !important; border-radius: 6px !important; font-weight: 600 !important; cursor: pointer !important; transition: background-color 0.2s !important; font-size: 14px !important;"
                        onmouseover="this.style.backgroundColor='#38a169'" onmouseout="this.style.backgroundColor='#48bb78'">Search</button>
                <button onclick="refreshActivities()" 
                        style="background: #4299e1 !important; color: white !important; border: none !important; padding: 12px 24px !important; border-radius: 6px !important; font-weight: 600 !important; cursor: pointer !important; transition: background-color 0.2s !important; font-size: 14px !important;"
                        onmouseover="this.style.backgroundColor='#3182ce'" onmouseout="this.style.backgroundColor='#4299e1'">Refresh</button>
            </div>
            
            <h2 style="color: #2d3748 !important; font-size: 1.8rem !important; font-weight: 600 !important; margin-bottom: 20px !important; padding-bottom: 12px !important; border-bottom: 2px solid #e2e8f0 !important;">Recent Activities</h2>
            <div style="overflow-x: auto !important;">
                <table style="width: 100% !important; border-collapse: collapse !important; background: white !important;">
                    <thead>
                        <tr style="background: linear-gradient(90deg, #4a5568 0%, #2d3748 100%) !important;">
                            <th style="padding: 16px 12px !important; text-align: left !important; font-weight: 600 !important; color: white !important; font-size: 14px !important; border-right: 1px solid #718096 !important;">Date</th>
                            <th style="padding: 16px 12px !important; text-align: left !important; font-weight: 600 !important; color: white !important; font-size: 14px !important; border-right: 1px solid #718096 !important;">Activity Type</th>
                            <th style="padding: 16px 12px !important; text-align: left !important; font-weight: 600 !important; color: white !important; font-size: 14px !important; border-right: 1px solid #718096 !important;">Details</th>
                            <th style="padding: 16px 12px !important; text-align: left !important; font-weight: 600 !important; color: white !important; font-size: 14px !important;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTableBody">
                        <!-- Activities will be loaded here -->
                    </tbody>
                </table>
            </div>
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
                const activitiesTableBody = document.getElementById('activitiesTableBody');
                if (data.recentActivities && data.recentActivities.length > 0) {
                    activitiesTableBody.innerHTML = data.recentActivities.map((activity, index) => `
                        <tr style="border-bottom: 1px solid #e2e8f0; background-color: ${index % 2 === 0 ? '#f7fafc' : 'white'}; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#edf2f7'" 
                            onmouseout="this.style.backgroundColor='${index % 2 === 0 ? '#f7fafc' : 'white'}'">
                            <td style="padding: 16px 12px; color: #4a5568; font-size: 14px;">${activity.date}</td>
                            <td style="padding: 16px 12px; font-weight: 600; color: #2d3748; font-size: 14px;">${activity.activity}</td>
                            <td style="padding: 16px 12px; color: #4a5568; font-size: 14px;">${activity.details}</td>
                            <td style="padding: 16px 12px;">
                                <span style="background-color: ${activity.status === 'completed' ? '#48bb78' : activity.status === 'pending' ? '#ed8936' : '#4299e1'}; 
                                             color: white; padding: 6px 12px; border-radius: 16px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                                    ${activity.status || 'Active'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    activitiesTableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #718096; font-style: italic;">No recent activities found</td></tr>';
                }

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Search and filter functions
        function filterActivities() {
            const searchValue = document.getElementById('activitySearch').value.toLowerCase();
            const rows = document.querySelectorAll('#activitiesTableBody tr');
            
            rows.forEach(row => {
                if (row.cells.length > 1) {
                    const activityText = row.cells[1].textContent.toLowerCase();
                    const detailsText = row.cells[2].textContent.toLowerCase();
                    const isVisible = activityText.includes(searchValue) || detailsText.includes(searchValue);
                    row.style.display = isVisible ? '' : 'none';
                }
            });
        }
        
        function refreshActivities() {
            loadDashboardData();
        }
        
        // Add search event listener
        document.getElementById('activitySearch').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterActivities();
            }
        });

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
