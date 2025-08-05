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
if ($role !== '管理者') {
    // 非管理者角色，拒絕存取
    header("location: dashboard.php?message=no_permission");
    exit;
}

// 建立 Twig 環境
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); // 指向 templates 資料夾
$twig = new \Twig\Environment($loader);

// 資料庫連線
$pdo = new PDO("mysql:host=localhost;dbname=class_data;charset=utf8", "root", "");

// 取得使用者清單
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

// 確保有使用者
if (!$users) {
    die("尚未建立任何使用者");
}

// 取得當前選擇的使用者 ID
$selectedUserId = $_GET['user_id'] ?? $users[0]['id'];

// 查詢該使用者的權限
$stmt = $pdo->prepare("SELECT * FROM permissions WHERE user_id = ?");
$stmt->execute([$selectedUserId]);
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理權限資料
$permMap = [];
foreach ($permissions as $p) {
    $permMap[$p['module_name']] = $p;
}

// 模組清單
$modules = ['使用者管理', '文章管理', '報表分析'];

// 取得當前登入用戶信息（傳遞給模板）
$useracc = $_SESSION['backend_login_acc'] ?? '';

// 渲染 Twig 模板
echo $twig->render('permission.twig', [
    'useracc' => $useracc,
    'role' => $role,
    'users' => $users,
    'selectedUserId' => $selectedUserId,
    'permMap' => $permMap,
    'modules' => $modules
]);
