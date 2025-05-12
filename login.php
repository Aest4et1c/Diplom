<?php
/*  login.php  — авторизация для всех ролей
    (сохранение staff_id / parent_id для личного кабинета)                        */

session_start();
require_once __DIR__ . '/config.php';

/* если уже авторизован — на главную */
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

/* ────────────── обработка формы ────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';

    /* ищем пользователя + тащим ID сотрудника / родителя */
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.pass_hash, u.role_id, u.is_active,
               u.staff_id, u.parent_id,                     -- ← добавлено
               s.full_name AS staff_name,
               p.full_name AS parent_name
          FROM users u
     LEFT JOIN staff   s ON s.id = u.staff_id
     LEFT JOIN parents p ON p.id = u.parent_id
         WHERE u.username = ?
         LIMIT 1
    ");
    $stmt->execute([$login]);
    $u = $stmt->fetch();

    /* проверяем учётку */
    if (!$u || !$u['is_active']) {
        $error = 'Неверный логин или пароль.';
    } elseif (!password_verify($pass, $u['pass_hash'])) {
        $error = 'Неверный логин или пароль.';
    } else {
        /* определяем имя для приветствия */
        $displayName = $u['username'];
        if ($u['role_id'] == 2 && $u['staff_name'])  $displayName = $u['staff_name'];   // воспитатель
        if ($u['role_id'] == 3 && $u['parent_name']) $displayName = $u['parent_name'];  // родитель

        /* сохраняем в сессию */
        $_SESSION['user'] = [
            'id'        => $u['id'],
            'username'  => $u['username'],
            'role'      => ($u['role_id']==1 ? 'admin'
                            : ($u['role_id']==2 ? 'teacher' : 'parent')),
            'name'      => $displayName,
            'staff_id'  => ($u['role_id']==2 ? (int)$u['staff_id']  : null),
            'parent_id' => ($u['role_id']==3 ? (int)$u['parent_id'] : null)
        ];

        /* перенаправление */
        if ($u['role_id'] == 1) {            // администратор
            header('Location: /admin/');
        } else {                             // воспитатель или родитель
            header('Location: profile.php');
        }
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
    <style>
        body{display:flex;align-items:center;justify-content:center;height:100vh;background:#f8f9fa;}
    </style>
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
                <input type="text"
                       name="username"
                       class="form-control"
                       required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
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
