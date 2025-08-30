<?php
// Enable error reporting for debugging but prevent display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to send JSON response
function sendJsonResponse($data, $status = 200) {
    // Clean any output buffer
    if (ob_get_length()) ob_clean();
    
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle database connection errors
if (!isset($conn) || $conn->connect_error) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'Connection object not created')
    ], 500);
}

// Ensure staff table exists
try {
    // Check if database exists first
    $dbCheck = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'tortoise_center'");
    if (!$dbCheck || $dbCheck->num_rows === 0) {
        // Try to create database
        $conn->query("CREATE DATABASE IF NOT EXISTS tortoise_center");
        $conn->select_db('tortoise_center');
    }
    
    $tableCheck = "SHOW TABLES LIKE 'staff'";
    $result = $conn->query($tableCheck);
    if (!$result || $result->num_rows === 0) {
        $createTable = "CREATE TABLE IF NOT EXISTS `staff` (
            `staff_id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTable)) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Failed to create staff table: ' . $conn->error
            ], 500);
        }
        
        // Insert default admin if no staff exists
        $adminCheck = $conn->query("SELECT COUNT(*) as count FROM staff");
        if ($adminCheck && $adminCheck->fetch_assoc()['count'] == 0) {
            $defaultAdmin = "INSERT INTO staff (name, email, password_hash, role) VALUES 
                ('Administrator', 'admin@tcc.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin')";
            $conn->query($defaultAdmin);
        }
    }
} catch (Exception $e) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Database initialization error: ' . $e->getMessage()
    ], 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            // Temporarily bypass admin check for testing - ensure staff table exists first
            // Get all staff members
            $sql = "SELECT staff_id, name, email, role, created_at, 'active' as status FROM staff ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            if ($result === false) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database query failed: ' . $conn->error], 500);
            }
            
            $staff = array();
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }
            
            sendJsonResponse($staff);
        } catch (Exception $e) {
            sendJsonResponse(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
        break;

    case 'get':
        try {
            if (!isset($_GET['id'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Missing staff ID'], 400);
            }

            $id = (int)$_GET['id'];
            $sql = "SELECT staff_id, name, email, role, created_at, 'active' as status FROM staff WHERE staff_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $staff = $result->fetch_assoc();
                sendJsonResponse($staff);
            } else {
                sendJsonResponse(['status' => 'error', 'message' => 'Staff member not found'], 404);
            }
        } catch (Exception $e) {
            sendJsonResponse(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
        break;

    case 'add':
        try {
            // Add new staff member
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['status' => 'error', 'message' => 'Invalid JSON input: ' . json_last_error_msg()], 400);
            }
            
            if (!isset($data['name'], $data['email'], $data['password'], $data['role'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Missing required fields: name, email, password, role'], 400);
            }

            $name = $conn->real_escape_string(trim($data['name']));
            $email = $conn->real_escape_string(trim($data['email']));
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = $conn->real_escape_string($data['role']);
            
            // Validate role
            if (!in_array($role, ['admin', 'staff'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Invalid role. Must be "admin" or "staff"'], 400);
            }
            
            // Check if email already exists
            $checkSql = "SELECT staff_id FROM staff WHERE email = ?";
            $checkStmt = $conn->prepare($checkSql);
            
            if (!$checkStmt) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
            }
            
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendJsonResponse(['status' => 'error', 'message' => 'Email already exists'], 400);
            }
            
            $sql = "INSERT INTO staff (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
            }
            
            $stmt->bind_param("ssss", $name, $email, $password, $role);
            
            if ($stmt->execute()) {
                sendJsonResponse(['status' => 'success', 'message' => 'Staff member added successfully']);
            } else {
                sendJsonResponse(['status' => 'error', 'message' => 'Failed to add staff member: ' . $conn->error], 500);
            }
        } catch (Exception $e) {
            sendJsonResponse(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
        break;

    case 'update':
        try {
            // Update staff member
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['status' => 'error', 'message' => 'Invalid JSON input: ' . json_last_error_msg()], 400);
            }
            
            if (!isset($data['staff_id'], $data['name'], $data['email'], $data['role'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Missing required fields: staff_id, name, email, role'], 400);
            }

            $staff_id = (int)$data['staff_id'];
            $name = $conn->real_escape_string(trim($data['name']));
            $email = $conn->real_escape_string(trim($data['email']));
            $role = $conn->real_escape_string($data['role']);
            
            // Validate role
            if (!in_array($role, ['admin', 'staff'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Invalid role. Must be "admin" or "staff"'], 400);
            }
            
            // Check if email already exists for another user
            $checkSql = "SELECT staff_id FROM staff WHERE email = ? AND staff_id != ?";
            $checkStmt = $conn->prepare($checkSql);
            
            if (!$checkStmt) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
            }
            
            $checkStmt->bind_param("si", $email, $staff_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendJsonResponse(['status' => 'error', 'message' => 'Email already exists for another user'], 400);
            }
            
            // Update password if provided
            if (!empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE staff SET name = ?, email = ?, password_hash = ?, role = ? WHERE staff_id = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
                }
                
                $stmt->bind_param("ssssi", $name, $email, $password, $role, $staff_id);
            } else {
                $sql = "UPDATE staff SET name = ?, email = ?, role = ? WHERE staff_id = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
                }
                
                $stmt->bind_param("sssi", $name, $email, $role, $staff_id);
            }
            
            if ($stmt->execute()) {
                sendJsonResponse(['status' => 'success', 'message' => 'Staff member updated successfully']);
            } else {
                sendJsonResponse(['status' => 'error', 'message' => 'Failed to update staff member'], 500);
            }
        } catch (Exception $e) {
            sendJsonResponse(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        try {
            // Delete staff member
            if (!isset($_GET['id'])) {
                sendJsonResponse(['status' => 'error', 'message' => 'Missing staff ID'], 400);
            }

            $id = (int)$_GET['id'];
            
            // Prevent deleting the last admin
            $adminCountSql = "SELECT COUNT(*) as admin_count FROM staff WHERE role = 'admin'";
            $adminResult = $conn->query($adminCountSql);
            
            if ($adminResult) {
                $adminRow = $adminResult->fetch_assoc();
                
                if ($adminRow['admin_count'] <= 1) {
                    $checkIfAdminSql = "SELECT role FROM staff WHERE staff_id = ?";
                    $checkStmt = $conn->prepare($checkIfAdminSql);
                    
                    if ($checkStmt) {
                        $checkStmt->bind_param("i", $id);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result && $result->num_rows > 0) {
                            $staff = $result->fetch_assoc();
                            if ($staff['role'] === 'admin') {
                                sendJsonResponse(['status' => 'error', 'message' => 'Cannot delete the last admin user'], 400);
                            }
                        }
                    }
                }
            }
            
            $sql = "DELETE FROM staff WHERE staff_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error], 500);
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendJsonResponse(['status' => 'success', 'message' => 'Staff member deleted successfully']);
            } else {
                sendJsonResponse(['status' => 'error', 'message' => 'Failed to delete staff member'], 500);
            }
        } catch (Exception $e) {
            sendJsonResponse(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

$conn->close();
?>