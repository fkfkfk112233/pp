<?php
session_start();
session_unset();   // 清除所有 session 變數
session_destroy(); // 銷毀 session

require_once "../vendor/autoload.php";

$loader = new \Twig\Loader\FilesystemLoader('../templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('logout.twig');
