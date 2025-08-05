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
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader);

// 資料庫連線
$pdo = new PDO("mysql:host=localhost;dbname=class_data;charset=utf8", "root", "");

// 處理訊息
$message = "";
$alert_type = "";

// 處理表單提交（新增/編輯/刪除使用者）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            try {
                // 檢查使用者是否已存在
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user = ?");
                $check_stmt->execute([$_POST['username']]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    $message = "使用者名稱已存在！";
                    $alert_type = "alert-danger";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (user, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['password'], // 實際環境中應該要加密
                        $_POST['role']
                    ]);
                    $message = "使用者新增成功！";
                    $alert_type = "alert-success";
                }
            } catch (Exception $e) {
                $message = "新增失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
            
        case 'edit_user':
            try {
                // 檢查是否有其他使用者使用相同名稱
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user = ? AND id != ?");
                $check_stmt->execute([$_POST['username'], $_POST['user_id']]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    $message = "使用者名稱已被其他人使用！";
                    $alert_type = "alert-danger";
                } else {
                    if (!empty($_POST['password'])) {
                        // 如果有輸入新密碼，則更新密碼
                        $stmt = $pdo->prepare("UPDATE users SET user=?, password=?, role=? WHERE id=?");
                        $stmt->execute([
                            $_POST['username'],
                            $_POST['password'], // 實際環境中應該要加密
                            $_POST['role'],
                            $_POST['user_id']
                        ]);
                    } else {
                        // 沒有輸入新密碼，不更新密碼
                        $stmt = $pdo->prepare("UPDATE users SET user=?, role=? WHERE id=?");
                        $stmt->execute([
                            $_POST['username'],
                            $_POST['role'],
                            $_POST['user_id']
                        ]);
                    }
                    $message = "使用者資料更新成功！";
                    $alert_type = "alert-success";
                }
            } catch (Exception $e) {
                $message = "更新失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
            
        case 'delete_user':
            try {
                // 檢查是否為當前登入使用者
                $current_user = $_SESSION['backend_login_acc'] ?? '';
                $user_to_delete_stmt = $pdo->prepare("SELECT user FROM users WHERE id = ?");
                $user_to_delete_stmt->execute([$_POST['user_id']]);
                $user_to_delete = $user_to_delete_stmt->fetchColumn();
                
                if ($user_to_delete === $current_user) {
                    $message = "不能刪除當前登入的使用者！";
                    $alert_type = "alert-danger";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                    $stmt->execute([$_POST['user_id']]);
                    $message = "使用者刪除成功！";
                    $alert_type = "alert-success";
                }
            } catch (Exception $e) {
                $message = "刪除失敗：" . $e->getMessage();
                $alert_type = "alert-danger";
            }
            break;
    }
}

// 分頁設定
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// 搜尋條件
$search_username = $_GET['search_username'] ?? '';
$search_role = $_GET['search_role'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search_username)) {
    $where_conditions[] = "user LIKE ?";
    $params[] = "%$search_username%";
}

if (!empty($search_role)) {
    $where_conditions[] = "role = ?";
    $params[] = $search_role;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// 取得總記錄數
$count_sql = "SELECT COUNT(*) FROM users $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 取得使用者清單
$sql = "SELECT * FROM users $where_sql ORDER BY role DESC, user ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 角色選項
$roles = ['一般', '管理者', '系統管理員'];

// 取得當前登入用戶信息
$useracc = $_SESSION['backend_login_acc'] ?? '';

// 渲染 Twig 模板
echo $twig->render('user_management.twig', [
    'useracc' => $useracc,
    'role' => $role,
    'message' => $message,
    'alert_type' => $alert_type,
    'users' => $users,
    'roles' => $roles,
    'search_username' => $search_username,
    'search_role' => $search_role,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_records' => $total_records,
    'current_user' => $useracc // 用於防止刪除自己
]);
?>
