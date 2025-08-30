<?php
// Comprehensive error handling and debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to prevent any unwanted output
ob_start();

// Set comprehensive headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection with error handling
try {
    require_once '../db.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Function to send JSON response and exit cleanly
function sendJsonResponse($data, $status = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Function to send error response
function sendError($message, $code = 400, $debug = null) {
    $response = ['status' => 'error', 'message' => $message];
    if ($debug && error_reporting()) {
        $response['debug'] = $debug;
    }
    sendJsonResponse($response, $code);
}

// Validate database connection
if (!isset($conn) || $conn->connect_error) {
    sendError('Database connection failed', 500);
}

// Initialize database and tables with comprehensive error handling
try {
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS tortoise_center");
    $conn->select_db('tortoise_center');
    
    // Create tortoises table
    $createTortoises = "CREATE TABLE IF NOT EXISTS `tortoises` (
        `tortoise_id` INT PRIMARY KEY AUTO_INCREMENT,
        `species` VARCHAR(100) NOT NULL DEFAULT 'Unknown Species',
        `gender` ENUM('Male', 'Female', 'Unknown') NOT NULL DEFAULT 'Unknown',
        `date_of_birth` DATE DEFAULT NULL,
        `weight` DECIMAL(10,2) DEFAULT NULL,
        `length` DECIMAL(10,2) DEFAULT NULL,
        `status` ENUM('Active', 'Inactive', 'Deceased') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createTortoises)) {
        error_log("Failed to create tortoises table: " . $conn->error);
    } else {
        // Insert sample tortoises if table is empty
        $checkTortoises = $conn->query("SELECT COUNT(*) as count FROM tortoises");
        if ($checkTortoises && $checkTortoises->fetch_assoc()['count'] == 0) {
            $insertSample = "INSERT INTO tortoises (species, gender, date_of_birth, weight, length) VALUES 
                ('Giant Tortoise', 'Male', '2020-01-01', 50.5, 30.2),
                ('Hermann Tortoise', 'Female', '2019-05-15', 25.3, 20.1)";
            $conn->query($insertSample);
        }
    }
    
    // Create staff table
    $createStaff = "CREATE TABLE IF NOT EXISTS `staff` (
        `staff_id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL DEFAULT 'Unknown Staff',
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createStaff)) {
        error_log("Failed to create staff table: " . $conn->error);
    } else {
        // Insert default admin if table is empty
        $checkStaff = $conn->query("SELECT COUNT(*) as count FROM staff");
        if ($checkStaff && $checkStaff->fetch_assoc()['count'] == 0) {
            $insertAdmin = "INSERT INTO staff (name, email, password_hash, role) VALUES 
                ('Administrator', 'admin@tcc.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin')";
            $conn->query($insertAdmin);
        }
    }
    
    // Create health_records table
    $createHealth = "CREATE TABLE IF NOT EXISTS `health_records` (
        `health_id` INT PRIMARY KEY AUTO_INCREMENT,
        `tortoise_id` INT NOT NULL,
        `staff_id` INT NOT NULL,
        `check_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `weight` DECIMAL(10,2) NOT NULL,
        `temperature` DECIMAL(4,1) NOT NULL,
        `health_status` ENUM('Healthy', 'Needs Attention', 'Critical') NOT NULL,
        `notes` TEXT,
        INDEX (`tortoise_id`),
        INDEX (`staff_id`),
        INDEX (`check_date`)
    )";
    
    if (!$conn->query($createHealth)) {
        sendError('Failed to create health_records table: ' . $conn->error, 500);
    }
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    sendError('Database initialization failed', 500, $e->getMessage());
}

// Get action parameter
$action = $_GET['action'] ?? '';

// Main switch statement for handling different actions
switch ($action) {
    case 'get':
        try {
            $sql = "SELECT h.*, t.species, s.name as staff_name 
                    FROM health_records h 
                    LEFT JOIN tortoises t ON h.tortoise_id = t.tortoise_id 
                    LEFT JOIN staff s ON h.staff_id = s.staff_id 
                    ORDER BY h.check_date DESC";
            
            $result = $conn->query($sql);
            if ($result === false) {
                sendError("Database query failed: " . $conn->error, 500);
            }

            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }

            sendJsonResponse($records);
        } catch (Exception $e) {
            sendError('Error retrieving health records', 500, $e->getMessage());
        }
        break;

    case 'get_single':
        try {
            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) {
                sendError("Invalid or missing health record ID");
            }

            $sql = "SELECT h.*, t.species, s.name as staff_name 
                    FROM health_records h 
                    LEFT JOIN tortoises t ON h.tortoise_id = t.tortoise_id 
                    LEFT JOIN staff s ON h.staff_id = s.staff_id 
                    WHERE h.health_id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sendError("Database prepare failed: " . $conn->error, 500);
            }

            $stmt->bind_param("i", (int)$id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendError("Health record not found", 404);
            }

            $record = $result->fetch_assoc();
            sendJsonResponse($record);
        } catch (Exception $e) {
            sendError('Error retrieving health record', 500, $e->getMessage());
        }
        break;

    case 'add':
        try {
            // Get and validate JSON input
            $input = file_get_contents('php://input');
            if (empty($input)) {
                sendError('No input data received');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                sendError('Input data must be a JSON object');
            }

            // Comprehensive field validation
            $requiredFields = ['tortoise_id', 'weight', 'temperature', 'health_status'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                sendError('Missing required fields: ' . implode(', ', $missingFields));
            }
            
            // Data type validation
            if (!is_numeric($data['tortoise_id']) || (int)$data['tortoise_id'] <= 0) {
                sendError('tortoise_id must be a positive number');
            }
            
            if (!is_numeric($data['weight']) || (float)$data['weight'] <= 0) {
                sendError('weight must be a positive number');
            }
            
            if (!is_numeric($data['temperature'])) {
                sendError('temperature must be a valid number');
            }
            
            // Health status validation
            $validStatuses = ['Healthy', 'Needs Attention', 'Critical'];
            if (!in_array($data['health_status'], $validStatuses)) {
                sendError('health_status must be one of: ' . implode(', ', $validStatuses));
            }
            
            // Set defaults and sanitize
            $tortoise_id = (int)$data['tortoise_id'];
            $staff_id = isset($data['staff_id']) && is_numeric($data['staff_id']) ? (int)$data['staff_id'] : 1;
            $weight = (float)$data['weight'];
            $temperature = (float)$data['temperature'];
            $health_status = trim($data['health_status']);
            $notes = isset($data['notes']) ? trim($data['notes']) : '';
            
            // Handle check_date
            $check_date = null;
            if (isset($data['check_date']) && !empty($data['check_date'])) {
                $timestamp = strtotime($data['check_date']);
                if ($timestamp !== false) {
                    $check_date = date('Y-m-d H:i:s', $timestamp);
                }
            }

            // Prepare SQL statement
            if ($check_date) {
                $sql = "INSERT INTO health_records (tortoise_id, staff_id, check_date, weight, temperature, health_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    sendError('Database prepare failed: ' . $conn->error, 500);
                }
                $stmt->bind_param("iisddss", $tortoise_id, $staff_id, $check_date, $weight, $temperature, $health_status, $notes);
            } else {
                $sql = "INSERT INTO health_records (tortoise_id, staff_id, weight, temperature, health_status, notes) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    sendError('Database prepare failed: ' . $conn->error, 500);
                }
                $stmt->bind_param("iiddss", $tortoise_id, $staff_id, $weight, $temperature, $health_status, $notes);
            }

            // Execute the statement
            if (!$stmt->execute()) {
                sendError('Failed to save health record: ' . $stmt->error, 500);
            }

            sendJsonResponse([
                'status' => 'success',
                'message' => 'Health record added successfully',
                'id' => $stmt->insert_id
            ]);

        } catch (Exception $e) {
            sendError('Error saving health record', 500, $e->getMessage());
        }
        break;

    case 'update':
        try {
            // Get and validate JSON input
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input: ' . json_last_error_msg());
            }

            // Validate required fields for update
            $requiredFields = ['health_id', 'tortoise_id', 'weight', 'temperature', 'health_status'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    sendError("Missing required field: $field");
                }
            }
            
            // Data validation
            $health_id = (int)$data['health_id'];
            $tortoise_id = (int)$data['tortoise_id'];
            $staff_id = isset($data['staff_id']) && is_numeric($data['staff_id']) ? (int)$data['staff_id'] : 1;
            $weight = (float)$data['weight'];
            $temperature = (float)$data['temperature'];
            $health_status = trim($data['health_status']);
            $notes = isset($data['notes']) ? trim($data['notes']) : '';
            
            // Handle check_date
            $check_date = null;
            if (isset($data['check_date']) && !empty($data['check_date'])) {
                $timestamp = strtotime($data['check_date']);
                if ($timestamp !== false) {
                    $check_date = date('Y-m-d H:i:s', $timestamp);
                }
            }

            // Prepare update statement
            if ($check_date) {
                $sql = "UPDATE health_records SET tortoise_id=?, staff_id=?, check_date=?, weight=?, temperature=?, health_status=?, notes=? WHERE health_id=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    sendError('Database prepare failed: ' . $conn->error, 500);
                }
                $stmt->bind_param("iisddss", $tortoise_id, $staff_id, $check_date, $weight, $temperature, $health_status, $notes, $health_id);
            } else {
                $sql = "UPDATE health_records SET tortoise_id=?, staff_id=?, weight=?, temperature=?, health_status=?, notes=? WHERE health_id=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    sendError('Database prepare failed: ' . $conn->error, 500);
                }
                $stmt->bind_param("iiddss", $tortoise_id, $staff_id, $weight, $temperature, $health_status, $notes, $health_id);
            }

            if (!$stmt->execute()) {
                sendError('Failed to update health record: ' . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                sendError('Health record not found or no changes made', 404);
            }

            sendJsonResponse([
                'status' => 'success',
                'message' => 'Health record updated successfully'
            ]);

        } catch (Exception $e) {
            sendError('Error updating health record', 500, $e->getMessage());
        }
        break;

    case 'delete':
        try {
            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id) || (int)$id <= 0) {
                sendError('Invalid or missing health record ID');
            }

            $sql = "DELETE FROM health_records WHERE health_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                sendError('Database prepare failed: ' . $conn->error, 500);
            }

            $stmt->bind_param("i", (int)$id);

            if (!$stmt->execute()) {
                sendError('Failed to delete health record: ' . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                sendError('Health record not found', 404);
            }

            sendJsonResponse([
                'status' => 'success',
                'message' => 'Health record deleted successfully'
            ]);

        } catch (Exception $e) {
            sendError('Error deleting health record', 500, $e->getMessage());
        }
        break;

    default:
        sendError('Invalid action. Supported actions: get, get_single, add, update, delete', 400);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>