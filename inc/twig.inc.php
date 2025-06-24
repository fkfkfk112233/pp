<?php
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