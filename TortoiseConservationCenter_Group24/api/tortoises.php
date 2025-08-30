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
session_start();

// Function to send JSON response
function sendJsonResponse($data, $status = 200) {
    // Clean any output buffer
    if (ob_get_length()) ob_clean();
    
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Error handler
function sendError($message, $code = 400) {
    sendJsonResponse(['status' => 'error', 'message' => $message], $code);
}

// Handle database connection errors
if (!isset($conn) || $conn->connect_error) {
    sendError('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'), 500);
}

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    sendError('Unauthorized', 401);
}

// Ensure tortoises table exists
try {
    $tableCheck = "SHOW TABLES LIKE 'tortoises'";
    $result = $conn->query($tableCheck);
    if ($result->num_rows === 0) {
        $createTable = "CREATE TABLE IF NOT EXISTS `tortoises` (
            `tortoise_id` VARCHAR(50) PRIMARY KEY,
            `species` VARCHAR(100) NOT NULL,
            `date_of_birth` DATE,
            `gender` ENUM('male', 'female', 'unknown') DEFAULT 'unknown',
            `weight` DECIMAL(10,2),
            `length` DECIMAL(10,2),
            `status` ENUM('healthy', 'sick', 'quarantine') DEFAULT 'healthy',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($createTable);
    }
} catch (Exception $e) {
    error_log("Database table creation error: " . $e->getMessage());
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        try {
            $sql = "SELECT * FROM tortoises ORDER BY tortoise_id";
            $result = $conn->query($sql);
            
            if ($result === false) {
                sendError("Database error: " . $conn->error, 500);
            }
            
            $tortoises = [];
            while ($row = $result->fetch_assoc()) {
                $tortoises[] = $row;
            }
            
            sendJsonResponse($tortoises);
        } catch (Exception $e) {
            sendError("Failed to fetch tortoises: " . $e->getMessage(), 500);
        }
        break;

    case 'add':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                sendError("Invalid input data");
            }
            
            if (!isset($data['tortoise_id'], $data['species'])) {
                sendError("Missing required fields: tortoise_id and species");
            }
            
            $sql = "INSERT INTO tortoises (tortoise_id, species, date_of_birth, gender, weight, length, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sendError("Database error: " . $conn->error, 500);
            }
            
            $stmt->bind_param('ssssdds', 
                $data['tortoise_id'],
                $data['species'],
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? 'unknown',
                $data['weight'] ?? null,
                $data['length'] ?? null,
                $data['status'] ?? 'healthy'
            );
            
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) { // Duplicate entry
                    sendError("Tortoise ID already exists", 400);
                } else {
                    sendError("Failed to add tortoise: " . $stmt->error, 500);
                }
            }
            
            sendJsonResponse(['status' => 'success', 'message' => 'Tortoise added successfully']);
        } catch (Exception $e) {
            sendError("Failed to add tortoise: " . $e->getMessage(), 500);
        }
        break;

    case 'delete':
        try {
            $id = $_GET['id'] ?? '';
            
            if (!$id) {
                sendError("Missing tortoise ID");
            }
            
            $sql = "DELETE FROM tortoises WHERE tortoise_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                sendError("Database error: " . $conn->error, 500);
            }
            
            $stmt->bind_param('s', $id);
            
            if (!$stmt->execute()) {
                sendError("Failed to delete tortoise: " . $stmt->error, 500);
            }
            
            if ($stmt->affected_rows === 0) {
                sendError("Tortoise not found", 404);
            }
            
            sendJsonResponse(['status' => 'success', 'message' => 'Tortoise deleted successfully']);
        } catch (Exception $e) {
            sendError("Failed to delete tortoise: " . $e->getMessage(), 500);
        }
        break;

    default:
        sendError("Invalid action", 400);
}

$conn->close();
?>
