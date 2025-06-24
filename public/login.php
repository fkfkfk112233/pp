<?php
session_start();
require_once "../inc/db.inc.php";
// 載入 Composer 的自動載入器。
// 由於這是應用程式的核心依賴，我們使用 require_once。
require_once __DIR__ . "/../vendor/autoload.php";

// 使用 use 關鍵字引入 Twig 相關的類別
// 這樣我們就可以直接使用 FilesystemLoader 和 Environment，而不是寫完整的 \Twig\Loader\FilesystemLoader
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// 設定時區
date_default_timezone_set('Asia/Taipei');

// Twig 模板載入器：指定模板檔案位於專案根目錄下的 templates 資料夾
$loader = new FilesystemLoader(__DIR__ . '/../templates');

// Twig 環境設定
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/../cache', // 模板緩存路徑，開發時可以設為 false，上線後建議啟用
    'debug' => true,                  // 開啟除錯模式，有利於開發
]);

// 載入 Twig 除錯擴展 (只有在 debug 為 true 時才啟用)
// 這裡也可以使用 use Twig\Extension\DebugExtension; 然後直接用 new DebugExtension()
if ($twig->isDebug()) {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
}

// ... 接下來是 PHP 處理邏輯 ...
//$_SERVER是全域變數, 使用陣列存取不同的伺服器參數

//用來放提示文字
$message = "";
//用來放 Bootstrap 警告框的樣式
$alert_type = "";

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

//判斷是否有使用者透過"POST"的方法送出表單
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $acc = $_POST["name"];
    $pwd = $_POST["password"];

    //prepare :防止 SQL injection
    //查詢 users 資料表中是否有這個名字（name）與密碼（password）的資料
    if (!empty($acc)) {
        $stmt = $pdo->prepare("SELECT name, password FROM users WHERE name = :name AND password = :password");
        $stmt->execute([
            ":name" => $acc,     // $acc 是從 $_POST["name"] 取得的
            ":password" => $pwd
        ]);

        //判斷帳號是否正確
        //header 導向 dashboard.php
        if ($stmt->rowCount() == 1) {
            $_SESSION['backend_login_flag'] = true;
            $_SESSION['backend_login_acc'] = $acc;
            header("Location: dashboard.php");
            exit;
        } else {
            $message .= "登入失敗!";
            $alert_type = "alert-danger";
        }
    } else {
        $message .= "請輸入正確的使用者!";
        $alert_type = "alert-warning";
    }
}

//使用 Twig 模板引擎渲染頁面
//把 $message 和 $alert_type 傳入 Twig 模板 login.twig，用來顯示提示訊息
echo $twig->render('login.twig', [
    "title" => "後台登入表單",
    "message" => $message,
    "alert_type" => $alert_type
]);
