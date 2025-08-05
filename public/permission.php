<?php
session_start();
require_once  __DIR__. '/../vendor/autoload.php';

// 檢查登入狀態
if (!isset($_SESSION['backend_login_flag']) || $_SESSION['backend_login_flag'] !== true) {
    header("location: login.php?message=nologin");
    exit;
}

// 檢查系統管理員權限
$role = $_SESSION['backend_login_role'] ?? '';
if ($role !== '系統管理員') {
    // 非系統管理員角色，拒絕存取
    header("location: dashboard.php?message=no_permission");
    exit;
}

// 建立 Twig 環境
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); // 指向 templates 資料夾
$twig = new \Twig\Environment($loader);

// 資料庫連線
$pdo = new PDO("mysql:host=localhost;dbname=class_data;charset=utf8", "root", "");

// 處理訊息
$message = "";
$alert_type = "";

// 處理表單提交（新增/編輯/刪除）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            try {
                $stmt = $pdo->prepare("INSERT INTO attendance_log (name, class_date, class_hours, raw_hours, attended_hours, late_hours, leave_early_hours, absent_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['class_date'],
                    $_POST['class_hours'],
                    $_POST['raw_hours'],
                    $_POST['attended_hours'],
                    $_POST['late_hours'],
                    $_POST['leave_early_hours'],
                    $_POST['absent_hours']
                ]);
                $message = "打卡紀錄新增成功！";
                $alert_type = "alert-success";
            } catch (Exception $e) {
                $message = "新增失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
            
        case 'edit':
            try {
                $stmt = $pdo->prepare("UPDATE attendance_log SET name=?, class_date=?, class_hours=?, raw_hours=?, attended_hours=?, late_hours=?, leave_early_hours=?, absent_hours=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['class_date'],
                    $_POST['class_hours'],
                    $_POST['raw_hours'],
                    $_POST['attended_hours'],
                    $_POST['late_hours'],
                    $_POST['leave_early_hours'],
                    $_POST['absent_hours'],
                    $_POST['id']
                ]);
                $message = "打卡紀錄更新成功！";
                $alert_type = "alert-success";
            } catch (Exception $e) {
                $message = "更新失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
            
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM attendance_log WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $message = "打卡紀錄刪除成功！";
                $alert_type = "alert-success";
            } catch (Exception $e) {
                $message = "刪除失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
    }
}

// 分頁設定
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 搜尋條件
$search_name = $_GET['search_name'] ?? '';
$search_date = $_GET['search_date'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search_name)) {
    $where_conditions[] = "name LIKE ?";
    $params[] = "%$search_name%";
}

if (!empty($search_date)) {
    $where_conditions[] = "class_date = ?";
    $params[] = $search_date;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// 取得總記錄數
$count_sql = "SELECT COUNT(*) FROM attendance_log $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 取得打卡紀錄
$sql = "SELECT * FROM attendance_log $where_sql ORDER BY class_date DESC, name ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得所有學生名單（用於新增表單）
$students_stmt = $pdo->query("SELECT DISTINCT name FROM attendance_log ORDER BY name");
$students = $students_stmt->fetchAll(PDO::FETCH_COLUMN);

// 取得當前登入用戶信息
$useracc = $_SESSION['backend_login_acc'] ?? '';

// 渲染 Twig 模板
echo $twig->render('permission.twig', [
    'useracc' => $useracc,
    'role' => $role,
    'message' => $message,
    'alert_type' => $alert_type,
    'attendance_records' => $attendance_records,
    'students' => $students,
    'search_name' => $search_name,
    'search_date' => $search_date,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_records' => $total_records
]);
