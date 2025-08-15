# 出缺勤後台管理系統

## 專案簡介
本專案是一個以 PHP 與 Twig 模板引擎打造的出缺勤後台管理系統，適用於教學班級或團體，提供使用者登入、權限管理、學員出缺勤紀錄維護、使用者管理等功能。

## 目錄結構
```
pp/
├── composer.json         # PHP 套件管理設定（Twig）
├── composer.lock         # PHP 套件鎖定檔
├── inc/                  # 共用程式碼（資料庫、Twig 初始化）
│   ├── db.inc.php        # 資料庫連線設定
│   └── twig.inc.php      # Twig 模板初始化
├── public/               # 主要功能頁面（入口、登入、登出、權限、使用者管理）
│   ├── dashboard.php     # 後台首頁
│   ├── login.php         # 登入頁
│   ├── logout.php        # 登出頁
│   ├── permission.php    # 權限管理（出缺勤紀錄）
│   └── user_management.php # 使用者管理
├── templates/            # Twig 模板檔案
│   ├── dashboard.twig    # 後台首頁模板
│   ├── login.twig        # 登入頁模板
│   ├── logout.twig       # 登出頁模板
│   ├── permission.twig   # 權限管理模板
│   ├── user_management.twig # 使用者管理模板
│   ├── menu.inc.twig     # 側邊選單模板
│   ├── nav.inc.twig      # 導覽列模板
│   └── footer.inc.twig   # 頁尾模板
```

## 主要功能
- 使用者登入、登出
- 權限控管（管理者、系統管理員）
- 學員出缺勤紀錄維護
- 使用者帳號管理（新增、編輯、刪除）
- 介面採用 Bootstrap 5，支援 RWD

## 技術說明
- PHP 8.x
- Twig 3.x
- MySQL 資料庫
- Bootstrap 5

## 安裝與執行
1. 安裝 PHP 與 MySQL（建議使用 XAMPP）
2. `composer install` 安裝相依套件（Twig）
3. 建立 MySQL 資料庫 `class_data`，並依需求建立 `users`、`attendance_log` 等資料表
4. 調整 `inc/db.inc.php` 內的資料庫連線設定
5. 將 `public/` 設為 Web 伺服器根目錄
6. 以瀏覽器開啟 `dashboard.php` 開始使用

## 資料庫結構（範例）
- `users`：儲存使用者帳號、密碼、角色
- `attendance_log`：儲存學員出缺勤紀錄

## 授權
本專案僅供教學與學術用途。
