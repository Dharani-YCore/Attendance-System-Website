<?php
require_once __DIR__ . '/config.php';

// Total users
$resUsers = $mysqli->query('SELECT COUNT(*) AS c FROM users');
$rowUsers = $resUsers ? $resUsers->fetch_assoc() : ['c' => 0];
$totalUsers = (int)$rowUsers['c'];

// Total attendance records (optional filter today)
$today = date('Y-m-d');
// Schema uses 'date' column of type DATE
$stmtToday = $mysqli->prepare('SELECT COUNT(*) AS c FROM attendance WHERE `date` = ?');
if ($stmtToday) {
    $stmtToday->bind_param('s', $today);
    $stmtToday->execute();
    $resToday = $stmtToday->get_result();
    $rowToday = $resToday ? $resToday->fetch_assoc() : ['c' => 0];
    $totalAttendanceToday = isset($rowToday['c']) ? (int)$rowToday['c'] : 0;
    $stmtToday->close();
} else {
    $totalAttendanceToday = 0;
}

json_response(true, '', [
    'total_users' => $totalUsers,
    'attendance_today' => $totalAttendanceToday
]);
