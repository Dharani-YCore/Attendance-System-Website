<?php
require_once __DIR__ . '/config.php';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? (string)$input['password'] : '';

    if ($name === '' || $email === '' || $password === '') {
        json_response(false, 'Name, email and password are required', null, 400);
    }

    // Check duplicate email
    $stmtChk = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmtChk->bind_param('s', $email);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    if ($resChk->fetch_assoc()) {
        $stmtChk->close();
        json_response(false, 'Email already exists', null, 409);
    }
    $stmtChk->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare('INSERT INTO users (name, email, password, is_first_login) VALUES (?, ?, ?, TRUE)');
    $stmt->bind_param('sss', $name, $email, $hash);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        json_response(false, 'Failed to create user: ' . $err, null, 500);
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    $stmtGet = $mysqli->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
    $stmtGet->bind_param('i', $newId);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $user = $res->fetch_assoc();
    $stmtGet->close();

    json_response(true, 'User created', ['user' => $user], 201);
}

// GET single user by id
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id <= 0) json_response(false, 'Invalid id', null, 400);
    $stmt = $mysqli->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    if (!$user) json_response(false, 'User not found', null, 404);
    $user['id'] = (int)$user['id'];
    json_response(true, '', ['user' => $user]);
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) json_response(false, 'Invalid id', null, 400);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) json_response(false, 'Invalid JSON', null, 400);

    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? (string)$input['password'] : '';

    if ($name === '' || $email === '') json_response(false, 'Name and email are required', null, 400);

    // Check duplicate email on other users
    $stmtChk = $mysqli->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $stmtChk->bind_param('si', $email, $id);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    if ($resChk->fetch_assoc()) {
        $stmtChk->close();
        json_response(false, 'Email already exists', null, 409);
    }
    $stmtChk->close();

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $email, $hash, $id);
    } else {
        $stmt = $mysqli->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $email, $id);
    }
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        json_response(false, 'Failed to update user: ' . $err, null, 500);
    }
    $stmt->close();

    $stmtGet = $mysqli->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
    $stmtGet->bind_param('i', $id);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $user = $res->fetch_assoc();
    $stmtGet->close();
    if ($user) $user['id'] = (int)$user['id'];
    json_response(true, 'User updated', ['user' => $user]);
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) json_response(false, 'Invalid id', null, 400);
    $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        json_response(false, 'Failed to delete user: ' . $err, null, 500);
    }
    $affected = $stmt->affected_rows;
    $stmt->close();
    if ($affected <= 0) json_response(false, 'User not found', null, 404);
    json_response(true, 'User deleted');
}

// GET list users
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
$types = '';
if ($q !== '') {
    $where = 'WHERE name LIKE ? OR email LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like];
    $types = 'ss';
}

if ($where) {
    $stmtCount = $mysqli->prepare("SELECT COUNT(*) AS total FROM users $where");
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $total = (int)$rowCount['total'];
    $stmtCount->close();
} else {
    $res = $mysqli->query('SELECT COUNT(*) AS total FROM users');
    $row = $res->fetch_assoc();
    $total = (int)$row['total'];
}

if ($where) {
    $stmt = $mysqli->prepare("SELECT id, name, email, created_at FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $typesFetch = $types + 'ii';
    // Fix concatenation: ensure string types
}
// Rebuild fetch with correct binding regardless of where
if ($where) {
    $stmt = $mysqli->prepare("SELECT id, name, email, created_at FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $typesFetch = $types . 'ii';
    $stmt->bind_param($typesFetch, ...array_merge($params, [$perPage, $offset]));
} else {
    $stmt = $mysqli->prepare('SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'created_at' => $row['created_at'],
    ];
}
$stmt->close();

$totalPages = (int)ceil($total / $perPage);
json_response(true, '', [
    'items' => $items,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages
    ]
]);
