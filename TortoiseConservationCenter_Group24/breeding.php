<?php
require_once 'includes/header.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breeding Management - Tortoise Conservation Center</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 class="card-header">Breeding Management</h1>
            
            <!-- Breeding Events Section -->
            <div class="section">
                <h2>Breeding Events</h2>
                <div class="search-bar">
                    <input type="text" id="searchEventsInput" placeholder="Search by Breeding ID..." class="search-input">
                    <button onclick="showAddEventModal()" class="btn btn-primary">Add Breeding Event</button>
                </div>

                <table id="breedingEventsTable">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Breeding ID</th>
                            <th>Breeding Date</th>
                            <th>Offspring Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Breeding Seasons Section -->
            <div class="section" style="margin-top: 40px;">
                <h2>Breeding Seasons</h2>
                <div class="search-bar">
                    <button onclick="showAddSeasonModal()" class="btn btn-primary">Add Breeding Season</button>
                </div>

                <table id="breedingSeasonsTable">
                    <thead>
                        <tr>
                            <th>Season ID</th>
                            <th>Start Month</th>
                            <th>End Month</th>
                            <th>Temperature Range</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Breeding Event Modal -->
    <div id="breedingEventModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 id="eventModalTitle" class="card-header">Add Breeding Event</h2>
            <form id="breedingEventForm">
                <div class="form-group">
                    <label>Breeding ID</label>
                    <input type="text" id="breeding_id" required placeholder="e.g., BR001">
                </div>
                <div class="form-group">
                    <label>Breeding Date</label>
                    <input type="date" id="breeding_date" required>
                </div>
                <div class="form-group">
                    <label>Offspring Count</label>
                    <input type="number" id="offspring_count" required min="0">
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" onclick="closeEventModal()" class="btn btn-danger">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Breeding Season Modal -->
    <div id="breedingSeasonModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 id="seasonModalTitle" class="card-header">Add Breeding Season</h2>
            <form id="breedingSeasonForm">
                <div class="form-group">
                    <label>Start Month</label>
                    <select id="start_month" required>
                        <option value="">Select Month</option>
                        <option value="January">January</option>
                        <option value="February">February</option>
                        <option value="March">March</option>
                        <option value="April">April</option>
                        <option value="May">May</option>
                        <option value="June">June</option>
                        <option value="July">July</option>
                        <option value="August">August</option>
                        <option value="September">September</option>
                        <option value="October">October</option>
                        <option value="November">November</option>
                        <option value="December">December</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>End Month</label>
                    <select id="end_month" required>
                        <option value="">Select Month</option>
                        <option value="January">January</option>
                        <option value="February">February</option>
                        <option value="March">March</option>
                        <option value="April">April</option>
                        <option value="May">May</option>
                        <option value="June">June</option>
                        <option value="July">July</option>
                        <option value="August">August</option>
                        <option value="September">September</option>
                        <option value="October">October</option>
                        <option value="November">November</option>
                        <option value="December">December</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Temperature Range</label>
                    <input type="text" id="temperature_range" required placeholder="e.g., 25-30°C">
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" onclick="closeSeasonModal()" class="btn btn-danger">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load breeding events
        async function loadBreedingEvents() {
            try {
                const response = await fetch('api/breeding.php?action=getBreedingEvents');
                const data = await response.json();
                const tbody = document.querySelector('#breedingEventsTable tbody');
                tbody.innerHTML = '';
                
                data.forEach(event => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${event.id}</td>
                            <td>${event.breeding_id}</td>
                            <td>${event.breeding_date}</td>
                            <td>${event.offspring_count}</td>
                            <td>
                                <button onclick="deleteBreedingEvent('${event.id}')" class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Error loading breeding events:', error);
            }
        }

        // Load breeding seasons
        async function loadBreedingSeasons() {
            try {
                const response = await fetch('api/breeding.php?action=getBreedingSeasons');
                const data = await response.json();
                const tbody = document.querySelector('#breedingSeasonsTable tbody');
                tbody.innerHTML = '';
                
                data.forEach(season => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${season.id}</td>
                            <td>${season.start_month}</td>
                            <td>${season.end_month}</td>
                            <td>${season.temperature_range}</td>
                            <td>
                                <button onclick="deleteBreedingSeason('${season.id}')" class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Error loading breeding seasons:', error);
            }
        }

        // Modal functions for breeding events
        function showAddEventModal() {
            document.getElementById('breedingEventForm').reset();
            document.getElementById('eventModalTitle').innerText = 'Add Breeding Event';
            document.getElementById('breedingEventModal').style.display = 'flex';
        }

        function closeEventModal() {
            document.getElementById('breedingEventModal').style.display = 'none';
        }

        // Modal functions for breeding seasons
        function showAddSeasonModal() {
            document.getElementById('breedingSeasonForm').reset();
            document.getElementById('seasonModalTitle').innerText = 'Add Breeding Season';
            document.getElementById('breedingSeasonModal').style.display = 'flex';
        }

        function closeSeasonModal() {
            document.getElementById('breedingSeasonModal').style.display = 'none';
        }

        // Delete functions
        async function deleteBreedingEvent(id) {
            if (!confirm('Are you sure you want to delete this breeding event?')) return;
            
            try {
                await fetch(`api/breeding.php?action=deleteBreedingEvent&id=${id}`);
                loadBreedingEvents();
            } catch (error) {
                console.error('Error deleting breeding event:', error);
            }
        }

        async function deleteBreedingSeason(id) {
            if (!confirm('Are you sure you want to delete this breeding season?')) return;
            
            try {
                await fetch(`api/breeding.php?action=deleteBreedingSeason&id=${id}`);
                loadBreedingSeasons();
            } catch (error) {
                console.error('Error deleting breeding season:', error);
            }
        }

        // Form submission handlers
        document.getElementById('breedingEventForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                breeding_id: document.getElementById('breeding_id').value,
                breeding_date: document.getElementById('breeding_date').value,
                offspring_count: document.getElementById('offspring_count').value
            };
            
            try {
                await fetch('api/breeding.php?action=addBreedingEvent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                closeEventModal();
                loadBreedingEvents();
            } catch (error) {
                console.error('Error saving breeding event:', error);
            }
        });

        document.getElementById('breedingSeasonForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                start_month: document.getElementById('start_month').value,
                end_month: document.getElementById('end_month').value,
                temperature_range: document.getElementById('temperature_range').value
            };
            
            try {
                await fetch('api/breeding.php?action=addBreedingSeason', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                closeSeasonModal();
                loadBreedingSeasons();
            } catch (error) {
                console.error('Error saving breeding season:', error);
            }
        });

        // Search functionality
        document.getElementById('searchEventsInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#breedingEventsTable tbody tr');
            
            rows.forEach(row => {
                const breedingId = row.cells[1].textContent.toLowerCase();
                row.style.display = breedingId.includes(searchValue) ? '' : 'none';
            });
        });

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadBreedingEvents();
            loadBreedingSeasons();
        });
    </script>
</body>
</html>