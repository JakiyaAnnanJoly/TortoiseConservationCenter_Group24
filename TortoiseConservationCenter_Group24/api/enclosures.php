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
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
    case 'list':
        // Get all enclosures
        $sql = "SELECT * FROM enclosures ORDER BY enclosure_id ASC";
        $result = $conn->query($sql);
        
        if ($result) {
            $enclosures = array();
            while ($row = $result->fetch_assoc()) {
                $enclosures[] = $row;
            }
            sendJsonResponse($enclosures);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to fetch enclosures'], 500);
        }
        break;

    case 'get':
        if (!isset($_GET['id'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing enclosure ID'], 400);
        }

        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM enclosures WHERE enclosure_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $enclosure = $result->fetch_assoc();
            sendJsonResponse($enclosure);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Enclosure not found'], 404);
        }
        break;

    case 'add':
        // Add new enclosure
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name'], $data['capacity'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        $name = $conn->real_escape_string($data['name']);
        $capacity = (int)$data['capacity'];
        $current_occupancy = isset($data['current_occupancy']) ? (int)$data['current_occupancy'] : 0;
        $temperature = isset($data['temperature']) ? (float)$data['temperature'] : null;
        $humidity = isset($data['humidity']) ? (float)$data['humidity'] : null;
        $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : null;
        
        $sql = "INSERT INTO enclosures (name, capacity, current_occupancy, temperature, humidity, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siidds", $name, $capacity, $current_occupancy, $temperature, $humidity, $notes);
        
        if ($stmt->execute()) {
            sendJsonResponse(['status' => 'success', 'message' => 'Enclosure added successfully']);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to add enclosure: ' . $conn->error], 500);
        }
        break;

    case 'update':
        // Update enclosure
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['enclosure_id'], $data['name'], $data['capacity'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        $enclosure_id = (int)$data['enclosure_id'];
        $name = $conn->real_escape_string($data['name']);
        $capacity = (int)$data['capacity'];
        $current_occupancy = isset($data['current_occupancy']) ? (int)$data['current_occupancy'] : 0;
        $temperature = isset($data['temperature']) ? (float)$data['temperature'] : null;
        $humidity = isset($data['humidity']) ? (float)$data['humidity'] : null;
        $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : null;
        
        $sql = "UPDATE enclosures SET name = ?, capacity = ?, current_occupancy = ?, temperature = ?, humidity = ?, notes = ? WHERE enclosure_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiddsi", $name, $capacity, $current_occupancy, $temperature, $humidity, $notes, $enclosure_id);
        
        if ($stmt->execute()) {
            sendJsonResponse(['status' => 'success', 'message' => 'Enclosure updated successfully']);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to update enclosure'], 500);
        }
        break;

    case 'delete':
        // Delete enclosure
        if (!isset($_GET['id'])) {
            sendJsonResponse(['status' => 'error', 'message' => 'Missing enclosure ID'], 400);
        }

        $id = (int)$_GET['id'];
        $sql = "DELETE FROM enclosures WHERE enclosure_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            sendJsonResponse(['status' => 'success', 'message' => 'Enclosure deleted successfully']);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to delete enclosure'], 500);
        }
        break;

    default:
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

$conn->close();
?>