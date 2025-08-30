<?php
require_once 'db.php';

// Check if the staff table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'staff'");
if ($tableCheck->num_rows == 0) {
    die("The staff table does not exist!");
}

// Check the structure of the staff table
$result = $conn->query("DESCRIBE staff");
echo "<h3>Staff Table Structure:</h3>";
while ($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}<br>";
}

// Check admin user exists
$stmt = $conn->prepare("SELECT staff_id, email, password, position FROM staff WHERE email = ?");
$email = "admin@example.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Admin User Check:</h3>";
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Admin found:<br>";
    echo "Staff ID: {$user['staff_id']}<br>";
    echo "Email: {$user['email']}<br>";
    echo "Position: {$user['position']}<br>";
    echo "Password length: " . strlen($user['password']) . " characters<br>";
} else {
    echo "Admin user not found!<br>";
    
    // Try to create the admin user
    $sql = "INSERT INTO staff (name, email, password, position, date_joined, status) 
            VALUES ('Administrator', 'admin@example.com', '1234', 'Administrator', CURRENT_DATE, 'active')";
    
    if ($conn->query($sql)) {
        echo "<br>Created new admin user!<br>";
    } else {
        echo "<br>Failed to create admin user: " . $conn->error . "<br>";
    }
}

// Show all users in the staff table
$result = $conn->query("SELECT staff_id, name, email, position, status FROM staff");
echo "<h3>All Staff Members:</h3>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['staff_id']}, ";
        echo "Name: {$row['name']}, ";
        echo "Email: {$row['email']}, ";
        echo "Position: {$row['position']}, ";
        echo "Status: {$row['status']}<br>";
    }
} else {
    echo "No staff members found in the database.";
}

$conn->close();
?>
