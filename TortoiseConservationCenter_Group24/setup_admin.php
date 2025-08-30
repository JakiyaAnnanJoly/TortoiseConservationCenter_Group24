<?php
require_once 'db.php';

// Create staff table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    date_joined DATE,
    status ENUM('active', 'inactive') DEFAULT 'active'
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Check if admin user exists
$check = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
$email = "admin@example.com";
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    // Add admin user if it doesn't exist
    $sql = "INSERT INTO staff (name, email, password, position, date_joined, status) 
            VALUES (?, ?, ?, ?, CURRENT_DATE, 'active')";
    
    $stmt = $conn->prepare($sql);
    $name = "Administrator";
    $email = "admin@example.com";
    $password = "1234";  // In production, you should hash this
    $position = "Administrator";
    
    $stmt->bind_param("ssss", $name, $email, $password, $position);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
} else {
    // Update existing admin password
    $sql = "UPDATE staff SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $password = "1234";
    $email = "admin@example.com";
    $stmt->bind_param("ss", $password, $email);
    
    if ($stmt->execute()) {
        echo "Admin password updated successfully!";
    } else {
        echo "Error updating admin password: " . $stmt->error;
    }
}

$conn->close();
?>
