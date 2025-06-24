<?php
// 連線資料庫
$pdo = new PDO("mysql:host=localhost;dbname=;charset=utf8", "root", "");


// 取得使用者列表
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

// 取得當前使用者權限（預設第一位）
$selectedUserId = $_GET['user_id'] ?? $users[0]['id'];
$stmt = $pdo->prepare("SELECT * FROM permissions WHERE user_id = ?");
$stmt->execute([$selectedUserId]);
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 權限以模組為鍵整理
$permMap = [];
foreach ($permissions as $p) {
  $permMap[$p['module_name']] = $p;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>權限管理系統</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
  <h2 class="mb-4">使用者權限管理</h2>
  <form method="POST" action="save_permissions.php">
    <div class="mb-3">
      <label class="form-label">選擇使用者</label>
      <select class="form-select" name="user_id" onchange="location.href='index.php?user_id=' + this.value">
        <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $selectedUserId == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 權限表格 -->
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>模組</th>
          <th>查看</th>
          <th>新增</th>
          <th>編輯</th>
          <th>刪除</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $modules = ['使用者管理', '文章管理', '報表分析'];
        foreach ($modules as $module):
          $perm = $permMap[$module] ?? ['can_view'=>0,'can_create'=>0,'can_edit'=>0,'can_delete'=>0];
        ?>
        <tr>
          <td><?= $module ?></td>
          <?php foreach (['view', 'create', 'edit', 'delete'] as $action): ?>
            <td>
              <input type="checkbox" name="perm[<?= $module ?>][<?= $action ?>]"
                     <?= $perm["can_$action"] ? 'checked' : '' ?>>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
    <button type="submit" class="btn btn-primary">儲存設定</button>
  </form>
</div>
</body>
</html>
