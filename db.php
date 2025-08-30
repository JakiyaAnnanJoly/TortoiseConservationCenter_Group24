<?php
$host = "localhost";   // XAMPP default
$user = "root";        // XAMPP default user
$pass = "";            // XAMPP default password (blank)
$db   = "tortoise_center";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
