<?php
session_start();
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tortoise Conservation Center - Admin Login</title>
    <link rel="stylesheet" href="styles/style.css">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🐢 Admin Login</h1>
                <p>Tortoise Conservation Center</p>
            </div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
                <div class="form-group">
                    <label style="display: inline;">
                        <input type="checkbox" id="rememberMe"> Remember Me
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Login
                </button>
                <div id="errorMsg" class="alert alert-error" style="display: none; margin-top: 20px;">Invalid credentials!</div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;
            const remember = document.getElementById("rememberMe").checked;

            // Show loading state
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.textContent = 'Logging in...';
            button.disabled = true;

            // Debug: Log the request
            console.log('Attempting login with email:', username);
            
            fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "username=" + encodeURIComponent(username) + "&password=" + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    sessionStorage.setItem("loggedIn", "true");
                    if(remember) { 
                        localStorage.setItem("loggedIn", "true"); 
                    }
                    window.location.href = 'dashboard.php';
                } else {
                    document.getElementById("errorMsg").textContent = data.message || 'Invalid credentials!';
                    document.getElementById("errorMsg").style.display = "block";
                    // Reset button
                    button.textContent = originalText;
                    button.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById("errorMsg").textContent = 'An error occurred. Please try again.';
                document.getElementById("errorMsg").classList.remove("hidden");
                // Reset button
                button.textContent = originalText;
                button.disabled = false;
            });
        });
    </script>
</body>
</html>
