<?php
require_once __DIR__ . '/config.php';

// Create holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) json_response(false, 'Invalid JSON', null, 400);

    $holiday_date = isset($input['holiday_date']) ? trim($input['holiday_date']) : '';
    $holiday_name = isset($input['holiday_name']) ? trim($input['holiday_name']) : '';
    $holiday_type = isset($input['holiday_type']) ? trim($input['holiday_type']) : 'National';

    if ($holiday_date === '' || $holiday_name === '') {
        json_response(false, 'holiday_date and holiday_name are required', null, 400);
    }

    // Basic date validation
    $d = date_create_from_format('Y-m-d', $holiday_date);
    if (!$d || $d->format('Y-m-d') !== $holiday_date) {
        json_response(false, 'holiday_date must be YYYY-MM-DD', null, 400);
    }

    // Insert
    $stmt = $mysqli->prepare('INSERT INTO holidays (holiday_date, holiday_name, holiday_type) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $holiday_date, $holiday_name, $holiday_type);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        if (strpos($err, 'Duplicate') !== false) {
            json_response(false, 'Holiday for this date already exists', null, 409);
        }
        json_response(false, 'Failed to create holiday: ' . $err, null, 500);
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    $stmtGet = $mysqli->prepare('SELECT id, holiday_date, holiday_name, holiday_type, created_at FROM holidays WHERE id = ?');
    $stmtGet->bind_param('i', $newId);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $holiday = $res->fetch_assoc();
    $stmtGet->close();

    if ($holiday) $holiday['id'] = (int)$holiday['id'];
    json_response(true, 'Holiday created', ['holiday' => $holiday], 201);
}

// GET upcoming holidays (today and future)
$today = date('Y-m-d');
$stmt = $mysqli->prepare('SELECT id, holiday_date, holiday_name, holiday_type, created_at FROM holidays WHERE holiday_date >= ? ORDER BY holiday_date ASC');
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => (int)$row['id'],
        'holiday_date' => $row['holiday_date'],
        'holiday_name' => $row['holiday_name'],
        'holiday_type' => $row['holiday_type'],
        'created_at' => $row['created_at'],
    ];
}
$stmt->close();

json_response(true, '', [ 'items' => $items ]);
?>


