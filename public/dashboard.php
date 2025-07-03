<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/twig.inc.php';
require_once __DIR__ . '/../inc/db.inc.php';

date_default_timezone_set('Asia/Taipei');

// 檢查登入狀態
if (!isset($_SESSION['backend_login_flag']) || $_SESSION['backend_login_flag'] !== true) {
    header("location: login.php?message=nologin");
    exit;
}

// 取得登入資訊
$useracc = $_SESSION['backend_login_acc'] ?? '';
$role = $_SESSION['backend_login_role'] ?? ''; // <-- 注意這裡改為正確欄位

// SQL 條件
$where = "";
$params = [];

if ($role === '一般') {
    $where = "WHERE al.name = :name";
    $params[':name'] = $useracc;
}elseif ($role === '管理者') {
    // 不加條件 => 可查看所有人資料
    $where = "";  // 或保留空字串
} else {
    // 其他角色預防性拒絕（例如未授權角色）
    die("未授權的角色");
}

// 1~7 統計資料
$stmt = $pdo->prepare("
    SELECT 
        SUM(al.class_hours) AS total_class_hours,
        COUNT(DISTINCT c.class_name) AS total_courses,
        COUNT(DISTINCT al.class_date) AS total_days,
        SUM(al.attended_hours) AS total_attended,
        SUM(al.late_hours) AS total_late,
        SUM(al.leave_early_hours) AS total_early,
        AVG(al.raw_hours) AS avg_raw
    FROM attendance_log al
    JOIN classes c ON al.class_date = c.class_date AND al.class_hours = c.class_hours
    $where
");
$stmt->execute($params);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// 預設值處理
$summary = array_map(fn($v) => $v ?? 0, $summary);

// 計算百分比
$attendance_rate = ($summary['total_class_hours'] > 0) 
    ? round(($summary['total_attended'] / $summary['total_class_hours']) * 100, 1) 
    : 0;
$late_rate = ($summary['total_class_hours'] > 0) 
    ? round(($summary['total_late'] / $summary['total_class_hours']) * 100, 1) 
    : 0;
$early_rate = ($summary['total_class_hours'] > 0) 
    ? round(($summary['total_early'] / $summary['total_class_hours']) * 100, 1) 
    : 0;

// 8 各課程出席率圖表資料
$stmt2 = $pdo->prepare("
    SELECT 
        c.class_name, 
        SUM(al.attended_hours) AS attended, 
        SUM(al.class_hours) AS total 
    FROM attendance_log al
    JOIN classes c ON al.class_date = c.class_date AND al.class_hours = c.class_hours
    $where
    GROUP BY c.class_name
");
$stmt2->execute($params);
$courseStats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$class_names = [];
$attendance_rates = [];

foreach ($courseStats as $row) {
    $class_names[] = $row['class_name'];
    $rate = ($row['total'] > 0) ? round(($row['attended'] / $row['total']) * 100, 2) : 0;
    $attendance_rates[] = $rate;
}

// 傳到 Twig 模板
echo $twig->render('dashboard.twig', [
    'useracc' => $useracc,
    'role' => $role,
    'total_class_hours' => $summary['total_class_hours'],
    'total_classs' => $summary['total_courses'],
    'total_days' => $summary['total_days'],
    'attendance_rate' => $attendance_rate,
    'late_rate' => $late_rate,
    'leave_early_rate' => $early_rate,
    'average_raw_hours' => round($summary['avg_raw'], 1),
    'class_names' => $class_names,
    'class_attendance_rates' => $attendance_rates
]);