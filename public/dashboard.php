<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/twig.inc.php';
require_once __DIR__ . '/../inc/db.inc.php';

date_default_timezone_set('Asia/Taipei');

if (!isset($_SESSION['backend_login_flag']) || $_SESSION['backend_login_flag'] !== true) {
    header("location: login.php?message=nologin");
    exit;
}

$role = $_SESSION['role'] ?? '一般';
$useracc = $_SESSION['backend_login_acc'];

$where = "";
$params = [];

if ($role === '一般') {
    $where = "WHERE al.name = :name";
    $params[':name'] = $useracc;
}

// 1-7 統計資料
$stmt = $pdo->prepare("SELECT 
    SUM(al.class_hours) AS total_class_hours,
    COUNT(DISTINCT c.class_name) AS total_courses,
    COUNT(DISTINCT al.class_date) AS total_days,
    SUM(al.attended_hours) AS total_attended,
    SUM(al.late_hours) AS total_late,
    SUM(al.leave_early_hours) AS total_early,
    AVG(al.raw_hours) AS avg_raw
FROM attendance_log al
JOIN classes c ON al.class_date = c.class_date AND al.class_hours = c.class_hours
$where");
$stmt->execute($params);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// 8 各課程出席率統計
$stmt2 = $pdo->prepare("SELECT 
    c.class_name, 
    SUM(al.attended_hours) AS attended, 
    SUM(al.class_hours) AS total 
FROM attendance_log al
JOIN classes c ON al.class_date = c.class_date AND al.class_hours = c.class_hours
$where
GROUP BY c.class_name");
$stmt2->execute($params);
$courseStats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData = [];
foreach ($courseStats as $row) {
    $chartLabels[] = $row['class_name'];
    $rate = ($row['total'] > 0) ? round(($row['attended'] / $row['total']) * 100, 2) : 0;
    $chartData[] = $rate;
}

echo $twig->render('dashboard.twig', [
    'useracc' => $useracc,
    'summary' => $summary,
    'chart_labels' => json_encode($chartLabels),
    'chart_data' => json_encode($chartData)
]);
