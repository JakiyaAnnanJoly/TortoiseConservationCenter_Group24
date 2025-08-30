<?php
session_start();
require_once 'db.php';

// Set JSON content type only for POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
}

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Username and password are required"]);
        exit;
    }

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT staff_id, name, email, password_hash, role FROM staff WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error"]);
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // For development purposes, allow admin login with default credentials
        if ($username === 'admin@tcc.com' && $password === 'admin123') {
            $_SESSION['loggedIn'] = true;
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            
            echo json_encode([
                "status" => "success",
                "message" => "Login successful"
            ]);
            exit;
        }
        
        // For regular staff, verify password hash
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['user_id'] = $user['staff_id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['is_admin'] = ($user['role'] === 'admin');
            
            echo json_encode([
                "status" => "success",
                "message" => "Login successful"
            ]);
            exit;
        }
    }
    
    // Invalid credentials
    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password"
    ]);
    exit;
}

// Display login form for GET requests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tortoise Conservation Center</title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Tortoise Conservation Center</h1>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="username" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Login
                </button>
            </form>
            <div id="errorMessage" class="alert alert-error" style="display: none;"></div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorDiv = document.getElementById('errorMessage');
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    window.location.href = 'dashboard.php';
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>
?>
