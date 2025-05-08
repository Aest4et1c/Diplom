<?php
session_start();
require_once __DIR__ . '/config.php';   // $pdo

/* если уже вошли — на главную */
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

/* ---------- обработка формы ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';

    /* 1. ищем пользователя и активен ли он */
    $stmt = $pdo->prepare("
        SELECT id, username, pass_hash, role_id, is_active
          FROM users
         WHERE username = ?
         LIMIT 1
    ");
    $stmt->execute([$login]);
    $u = $stmt->fetch();

    if (!$u || !$u['is_active']) {
        $error = 'Неверный логин или пароль.';
    } elseif (!password_verify($pass, $u['pass_hash'])) {
        $error = 'Неверный логин или пароль.';
    } elseif ($u['role_id'] != 1) {          // 1 = admin
        $error = 'Доступ разрешён только администраторам.';
    } else {
        /* успех */
        $_SESSION['user'] = [
            'id'       => $u['id'],
            'username' => $u['username'],
            'role'     => 'admin'
        ];
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вход | Детский сад «Ромашка»</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{display:flex;align-items:center;justify-content:center;height:100vh;background:#f8f9fa;}</style>
</head>
<body>
<div class="card shadow-sm" style="min-width:320px;max-width:360px;">
    <div class="card-body p-4">
        <h4 class="mb-3 text-center">Вход</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Логин</label>
                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-success w-100" type="submit">Войти</button>
        </form>
    </div>
</div>
</body>
</html>
