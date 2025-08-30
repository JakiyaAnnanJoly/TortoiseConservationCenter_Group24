<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unwanted output
ob_start();

require_once '../db.php';

// Function to send JSON response
function sendJsonResponse($data, $status = 200) {
    // Clean any output buffer
    if (ob_get_length()) ob_clean();
    
    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code($status);
    
    // Send JSON response
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle database connection errors
if (!isset($conn) || $conn->connect_error) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')
    ], 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Get all feeding records
        $sql = "SELECT * FROM feeding_records ORDER BY feed_time DESC";
        $result = $conn->query($sql);
        
        if ($result) {
            $records = array();
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            sendJsonResponse($records);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to fetch records'], 500);
        }
        break;

    case 'add':
        // Add new feeding record
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['tortoise_id'], $data['food_type'], $data['quantity'], $data['staff_id'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        $tortoise_id = $conn->real_escape_string($data['tortoise_id']);
        $food_type = $conn->real_escape_string($data['food_type']);
        $quantity = (float)$data['quantity'];
        $staff_id = (int)$data['staff_id'];
        
        $sql = "INSERT INTO feeding_records (tortoise_id, staff_id, food_type, quantity, feed_time) 
                VALUES ('$tortoise_id', $staff_id, '$food_type', $quantity, NOW())";
        
        if ($conn->query($sql)) {
            sendJsonResponse(['status' => 'success', 'message' => 'Feeding record added successfully']);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to add feeding record: ' . $conn->error], 500);
        }
        break;

    case 'delete':
        // Delete feeding record
        if (!isset($_GET['id'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing feeding ID'], 400);
        }

        $id = (int)$_GET['id'];
        $sql = "DELETE FROM feeding_records WHERE feeding_id = $id";
        
        if ($conn->query($sql)) {
            sendJsonResponse(['status' => 'success', 'message' => 'Feeding record deleted successfully']);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to delete feeding record'], 500);
        }
        break;

    default:
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

$conn->close();
?>
