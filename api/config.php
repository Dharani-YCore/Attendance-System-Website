<?php
// Custom error handler to return JSON instead of HTML
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'error_details' => [
            'file' => basename($errfile),
            'line' => $errline,
            'type' => $errno
        ]
    ]);
    exit();
});

// Custom exception handler
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $exception->getMessage(),
        'error_details' => [
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine()
        ]
    ]);
    exit();
});

// Suppress HTML error display but log to file
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// DB config for XAMPP (adjust if needed)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'attendance_system';

// CORS & JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $mysqli->connect_error,
        'hint' => 'Please ensure MySQL is running in XAMPP and the attendance_system database exists. Import database.sql via phpMyAdmin.'
    ]);
    exit();
}

$mysqli->set_charset('utf8mb4');

function json_response($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    $res = ['success' => $success];
    if ($message !== '') $res['message'] = $message;
    if (!is_null($data)) $res['data'] = $data;
    echo json_encode($res);
    exit();
}
