<?php
require_once __DIR__ . '/config.php';

// GET list of attendance records with user names and computed working hours
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
$offset = ($page - 1) * $perPage;

// Optional search by user name or email
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
$params = [];
$types = '';
if ($q !== '') {
    $where = 'WHERE u.name LIKE ? OR u.email LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like];
    $types = 'ss';
}

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
        "SELECT a.id, u.name AS user_name, a.date, a.check_in_time, a.check_out_time, a.total_hours
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
        'SELECT a.id, u.name AS user_name, a.date, a.check_in_time, a.check_out_time, a.total_hours
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
        'user_name' => $row['user_name'],
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
