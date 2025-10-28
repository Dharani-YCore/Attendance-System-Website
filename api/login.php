<?php
require_once __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($email === '' || $password === '') {
    json_response(false, 'Missing credentials', null, 400);
}

// Seed default admin if table empty
$seedRes = $mysqli->query('SELECT COUNT(*) AS c FROM admins');
if ($seedRes) {
    $row = $seedRes->fetch_assoc();
    if (isset($row['c']) && (int)$row['c'] === 0) {
        $stmtSeed = $mysqli->prepare('INSERT INTO admins (admin_id, email, name, password_hash) VALUES (?, ?, ?, ?)');
        $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
        $id = 'admin';
        $e = 'admin@example.com'; $n = 'Administrator';
        $stmtSeed->bind_param('ssss', $id, $e, $n, $defaultHash);
        $stmtSeed->execute();
        $stmtSeed->close();
    }
}

// Lookup by email only
$stmt = $mysqli->prepare('SELECT id, email, name, password_hash FROM admins WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    json_response(false, 'Invalid credentials', null, 401);
}

if (!password_verify($password, $user['password_hash'])) {
    json_response(false, 'Invalid credentials', null, 401);
}

$token = bin2hex(random_bytes(16));

json_response(true, '', [
    'token' => $token,
    'user' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'name' => $user['name']
    ]
]);

