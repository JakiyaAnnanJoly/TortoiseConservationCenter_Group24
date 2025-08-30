<?php
// Prevent any output before headers
ob_start();

// Error handler function to convert PHP errors to JSON responses
function errorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'status' => 'error',
        'message' => $errstr,
        'code' => $errno,
        'file' => basename($errfile),
        'line' => $errline
    ];
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode($error);
    exit;
}

// Set error handler
set_error_handler("errorHandler");

// Set exception handler
set_exception_handler(function($e) {
    $error = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode($error);
    exit;
});

// Function to send JSON response
function sendJsonResponse($data, $status = 200) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code($status);
    echo json_encode($data);
    exit;
}
