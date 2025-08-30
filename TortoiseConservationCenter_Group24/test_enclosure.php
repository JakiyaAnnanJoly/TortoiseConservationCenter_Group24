<?php
// Simple test file to check if enclosure system works
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

echo "<h1>Enclosure System Test</h1>";

// Test 1: Database connection
echo "<h2>Test 1: Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test 2: Check if enclosures table exists
echo "<h2>Test 2: Enclosures Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'enclosures'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Enclosures table exists</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Enclosures table doesn't exist, creating it...</p>";
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
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✅ Enclosures table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create enclosures table: " . $conn->error . "</p>";
    }
}

// Test 3: Check table structure
echo "<h2>Test 3: Table Structure</h2>";
$result = $conn->query("DESCRIBE enclosures");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ Table structure is correct</p>";
}

// Test 4: Insert sample data if table is empty
echo "<h2>Test 4: Sample Data</h2>";
$count_result = $conn->query("SELECT COUNT(*) as count FROM enclosures");
$count = $count_result->fetch_assoc()['count'];

if ($count == 0) {
    echo "<p style='color: orange;'>⚠️ No data found, inserting sample enclosures...</p>";
    $sample_data = [
        ['Enclosure A1', 10, 5, 25.5, 65.0, 'Main habitat for adult tortoises'],
        ['Enclosure B2', 15, 8, 24.0, 70.0, 'Secondary habitat with pond'],
        ['Nursery C1', 5, 3, 26.0, 75.0, 'For juvenile tortoises']
    ];
    
    foreach ($sample_data as $enclosure) {
        $stmt = $conn->prepare("INSERT INTO enclosures (name, capacity, current_occupancy, temperature, humidity, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidds", $enclosure[0], $enclosure[1], $enclosure[2], $enclosure[3], $enclosure[4], $enclosure[5]);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added: " . $enclosure[0] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add: " . $enclosure[0] . "</p>";
        }
    }
} else {
    echo "<p style='color: green;'>✅ Found $count enclosure(s) in database</p>";
}

// Test 5: API endpoint test
echo "<h2>Test 5: API Endpoint Test</h2>";
$api_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/enclosures.php?action=list';
echo "<p>Testing API URL: <code>$api_url</code></p>";

// Test 6: File permissions
echo "<h2>Test 6: File Access</h2>";
$files_to_check = [
    'enclosure.php' => 'Main enclosure page',
    'api/enclosures.php' => 'API endpoint',
    'includes/header.php' => 'Header include',
    'styles/style.css' => 'Stylesheet'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description ($file) exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $description ($file) not found</p>";
    }
}

echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li><a href='enclosure.php'>Visit Enclosure Management Page</a></li>";
echo "<li><a href='api/enclosures.php?action=list'>Test API Directly</a></li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "</ol>";

$conn->close();
?>