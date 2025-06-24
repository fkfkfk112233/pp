<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/twig.inc.php';
require_once __DIR__ . '/../inc/db.inc.php';

//主要程式區
//判斷後台是否有登入
// 如果 session 中有 backend_login_flag，且值為 true，就什麼都不做，繼續執行後面的程式
if(isset($_SESSION['backend_login_flag']) && $_SESSION['backend_login_flag'] ==true){

//沒有就導向 login.php 並顯示 nologin 訊息的"進入後台需登入"
}else{
    header("location: login.php?message=nologin");
}

//組合 Twig 模板用的資料
//傳一個變數 useracc 給 Twig 模板使用。這個變數的值是目前登入者的帳號
$data['useracc'] = $_SESSION['backend_login_acc'];

//使用 Twig 引擎將 dashboard.twig 模板渲染出 HTML，並回傳給瀏覽器
echo $twig ->render('dashboard.twig', $data);