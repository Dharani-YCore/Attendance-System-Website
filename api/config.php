<?php
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
        'message' => 'Database connection failed: ' . $mysqli->connect_error
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
