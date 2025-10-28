<?php
// Temporary helper to generate bcrypt hashes. Delete after use.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$pwd = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pwd = isset($_GET['pwd']) ? (string)$_GET['pwd'] : '';
} else {
    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);
    $pwd = isset($in['pwd']) ? (string)$in['pwd'] : '';
}

if ($pwd === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provide pwd as query or JSON body']);
    exit();
}

$hash = password_hash($pwd, PASSWORD_DEFAULT);
echo json_encode(['success' => true, 'password' => $pwd, 'hash' => $hash]);
