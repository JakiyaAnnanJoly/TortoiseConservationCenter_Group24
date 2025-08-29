<?php
session_start();

// Database connection
$servername = "localhost";
$db_username = "root";   // default XAMPP user
$db_password = "";       // default XAMPP pass (empty)
$db_name     = "tortoise_center";

$conn = new mysqli($servername, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get login data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // match with MD5 stored password

    $sql = "SELECT * FROM admins WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['loggedIn'] = true;
        $_SESSION['username'] = $username;

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }
}
?>
