<?php
require_once __DIR__ . '/config.php';

$start = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

if ($start === '' || $end === '') {
    // default last 30 days
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-29 days', strtotime($end)));
}

// Aggregate by hour of day across the selected date range, counting check-ins
$stmt = $mysqli->prepare('SELECT HOUR(check_in_time) AS hh, COUNT(*) AS logins
  FROM attendance
  WHERE `date` BETWEEN ? AND ? AND check_in_time IS NOT NULL
  GROUP BY HOUR(check_in_time)
  ORDER BY hh ASC');
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$map = [];
while ($r = $res->fetch_assoc()){
    $map[(int)$r['hh']] = (int)$r['logins'];
}
$stmt->close();

// Ensure all 24 hours are present
$points = [];
for ($h = 0; $h < 24; $h++) {
    $label = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
    $points[] = [ 'hour' => $label, 'logins' => ($map[$h] ?? 0) ];
}

json_response(true, '', [ 'start_date' => $start, 'end_date' => $end, 'points' => $points ]);
?>


