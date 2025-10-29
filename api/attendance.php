<?php
require_once __DIR__ . '/config.php';

// GET list of attendance records with user names and computed working hours
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
$offset = ($page - 1) * $perPage;

// Optional search by user name or email
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$onlyCheckin = isset($_GET['only_checkin']) ? (int)$_GET['only_checkin'] : 0;
$clauses = [];
$params = [];
$types = '';
if ($q !== '') {
    $clauses[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($startDate !== '') {
    $clauses[] = 'a.date >= ?';
    $params[] = $startDate; $types .= 's';
}
if ($endDate !== '') {
    $clauses[] = 'a.date <= ?';
    $params[] = $endDate; $types .= 's';
}
if ($onlyCheckin === 1) {
    $clauses[] = '(a.check_in_time IS NOT NULL OR a.attendance_type = "check_in")';
}
$where = count($clauses) ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total count
if ($where) {
    $stmtCount = $mysqli->prepare("SELECT COUNT(*) AS total FROM attendance a JOIN users u ON a.user_id = u.id $where");
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $totalCount = isset($rowCount['total']) ? (int)$rowCount['total'] : 0;
    $stmtCount->close();
} else {
    $res = $mysqli->query('SELECT COUNT(*) AS total FROM attendance');
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    $totalCount = (int)$row['total'];
}

// Fetch page
if ($where) {
    $stmt = $mysqli->prepare(
        "SELECT a.id, u.id AS user_id, u.name AS user_name, u.email AS user_email, a.date, a.check_in_time, a.check_out_time, a.total_hours
         FROM attendance a
         JOIN users u ON a.user_id = u.id
         $where
         ORDER BY a.date DESC, a.check_in_time DESC
         LIMIT ? OFFSET ?"
    );
    $typesFetch = $types . 'ii';
    $stmt->bind_param($typesFetch, ...array_merge($params, [$perPage, $offset]));
} else {
    $stmt = $mysqli->prepare(
        'SELECT a.id, u.id AS user_id, u.name AS user_name, u.email AS user_email, a.date, a.check_in_time, a.check_out_time, a.total_hours
         FROM attendance a
         JOIN users u ON a.user_id = u.id
         ORDER BY a.date DESC, a.check_in_time DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $checkIn = $row['check_in_time'];
    $checkOut = $row['check_out_time'];
    $working = null;
    if (!is_null($row['total_hours'])) {
        // total_hours is decimal hours like 7.50
        $totalHours = (float)$row['total_hours'];
        $hours = (int)floor($totalHours);
        $mins = (int)round(($totalHours - $hours) * 60);
        $working = sprintf('%02d:%02d', $hours, $mins);
    } elseif ($checkIn && $checkOut) {
        // Fallback compute from times on same date
        $start = strtotime($date . ' ' . $checkIn);
        $end = strtotime($date . ' ' . $checkOut);
        if ($start && $end && $end >= $start) {
            $diff = $end - $start; // seconds
            $hours = floor($diff / 3600);
            $mins = floor(($diff % 3600) / 60);
            $working = sprintf('%02d:%02d', $hours, $mins);
        }
    }
    $items[] = [
        'id' => (int)$row['id'],
        'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
        'user_name' => $row['user_name'],
        'user_email' => $row['user_email'] ?? null,
        'check_in' => $date . ( $checkIn ? (' ' . $checkIn) : ''),
        'check_out' => $checkOut ? ($date . ' ' . $checkOut) : null,
        'working_hours' => $working,
    ];
}
$stmt->close();

$totalPages = (int)ceil(($totalCount ?: 0) / $perPage);
json_response(true, '', [
    'items' => $items,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => (int)$totalCount,
        'total_pages' => $totalPages
    ]
]);
