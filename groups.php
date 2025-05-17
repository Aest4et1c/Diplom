<?php
/**
 * groups.php — список всех групп.
 * Теперь иконка берётся из колонки groups.icon (VARCHAR).
 * Если icon = NULL → показывается запасная «🌤️».
 */

session_start();
require_once __DIR__ . '/config.php';

$user    = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';

/* берём группы вместе с иконкой */
$groups = $pdo->query("
    SELECT id,
           name,
           age_from,
           age_to,
           description,
           icon               -- 👈 новая колонка
      FROM groups
  ORDER BY name
")->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Группы | Детский сад «Ромашка»</title>
    <link rel="icon" type="image/png" href="/image/web_logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .group-card       { transition: transform .15s; cursor: pointer; }
        .group-card:hover { transform: translateY(-4px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
        .group-icon       { font-size: 2.5rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- ── шапка ── -->
<header class="bg-success text-white py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка»</h1>

        <div>
            <?php if (!$user): ?>
                <a href="login.php" class="btn btn-light btn-sm">Вход</a>
            <?php else: ?>
                <span class="me-2">
                    Здравствуйте, <strong><?= htmlspecialchars($user['name']) ?></strong>
                </span>
                <?php if ($isAdmin): ?>
            <!-- только для администратора -->
            <a href="/admin/"      class="btn btn-warning btn-sm me-2">Админ‑панель</a>
        <?php else: ?>
            <!-- для воспитателя и родителя -->
            <a href="/profile.php" class="btn btn-primary btn-sm me-2">Личный кабинет</a>
        <?php endif; ?>
                <a href="logout.php" class="btn btn-light btn-sm">Выход</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ── меню ── -->
<nav class="bg-white border-bottom">
    <div class="container">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item"><a class="nav-link"         href="index.php">Наш детский сад</a></li>
            <li class="nav-item"><a class="nav-link"         href="news.php">Новости</a></li>
            <li class="nav-item"><a class="nav-link active"  href="groups.php">Группы</a></li>
            <li class="nav-item"><a class="nav-link"         href="staff.php">Сотрудники</a></li>
            <li class="nav-item"><a class="nav-link"         href="contacts.php">Контакты</a></li>
        </ul>
    </div>
</nav>

<!-- ── контент ── -->
<main class="container my-4 flex-grow-1">
    <h1 class="mb-4">Наши группы</h1>

    <?php if (!$groups): ?>
        <div class="alert alert-info">Группы ещё не заведены.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($groups as $g): ?>
                <?php
                    $icon = $g['icon'] ?: '🌤️';  // ← запасная иконка
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <a href="group.php?id=<?= (int)$g['id'] ?>"
                       class="text-decoration-none text-dark">
                        <div class="card group-card h-100 shadow-sm border-0 text-center p-3">
                            <div class="group-icon mb-3"><?= htmlspecialchars($icon) ?></div>
                            <h5 class="mb-1"><?= htmlspecialchars($g['name']) ?></h5>
                            <p class="text-muted small mb-2">
                                Возраст: <?= (int)$g['age_from'] ?>–<?= (int)$g['age_to'] ?>
                            </p>
                            
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- ── футер ── -->
<footer class="bg-success text-white py-2 mt-auto">
    <div class="container small">© <?= date('Y') ?> Детский сад «Ромашка»</div>
</footer>

</body>
</html>
