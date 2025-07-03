<?php
session_start();
require_once "../inc/db.inc.php";
require_once __DIR__ . "/../vendor/autoload.php";

// Twig 設定
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

date_default_timezone_set('Asia/Taipei');

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/../cache', // 可改成 false 以便開發
    'debug' => true,
]);

if ($twig->isDebug()) {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
}

// 初始化訊息與提示樣式
$message = "";
$alert_type = "";

// 若帶入 GET 訊息，如未登入導向
if (isset($_GET['message']) && $_GET['message'] != "") {
    switch ($_GET['message']) {
        case 'nologin':
            $message .= "進入後台需登入";
            $alert_type = "alert-warning";
            break;
        default:
            $message .= "";
            $alert_type = "alert-success";
            break;
    }
}

// 登入表單提交處理
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $acc = $_POST["user"];
    $pwd = $_POST["password"];

    if (!empty($acc) && !empty($pwd)) {
        // 查詢帳號密碼與角色
        $stmt = $pdo->prepare("SELECT user, password, role FROM users WHERE user = :user AND password = :password");
        $stmt->execute([
            ":user" => $acc,
            ":password" => $pwd,
        ]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 設定登入 session
            $_SESSION['backend_login_flag'] = true;
            $_SESSION['backend_login_acc'] = $user['user'];
            $_SESSION['backend_login_role'] = $user['role'];

            header("Location: dashboard.php");
            exit;
        } else {
            $message .= "登入失敗！帳號或密碼錯誤";
            $alert_type = "alert-danger";
        }
    } else {
        $message .= "請輸入帳號與密碼";
        $alert_type = "alert-warning";
    }
}

// 顯示登入畫面
echo $twig->render('login.twig', [
    "title" => "後台登入表單",
    "message" => $message,
    "alert_type" => $alert_type
]);

