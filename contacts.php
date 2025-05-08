<?php
/**
 * contacts.php — страница «Контакты»
 */
session_start();
require_once __DIR__ . '/config.php';   // база не нужна, но пусть будет единообразие

/* --- Текущий пользователь --- */
$currentUser = $_SESSION['user'] ?? null;
$isAdmin     = $currentUser && $currentUser['role'] === 'admin';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Контакты | Детский сад № 12 «Ромашка»</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">

<!-- ░░░ Верхняя полоска ░░░ -->
<header class="bg-success text-white py-2">
    <div class="container d-flex align-items-center justify-content-between">
        <h1 class="h5 m-0 fw-bold">
            ГКДОУ «Детский сад № 12 «Ромашка» г. о. Иловайск»
        </h1>

        <!-- Вход / Выход -->
        <div>
            <?php if (!$currentUser): ?>
                <a href="login.php" class="btn btn-light btn-sm">Вход</a>
            <?php else: ?>
                <span class="me-2">
                    Здравствуйте, <strong><?= htmlspecialchars($currentUser['username']) ?></strong>
                </span>
                <?php if ($isAdmin): ?>
                    <a href="/admin/" class="btn btn-warning btn-sm me-2">Админ-панель</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-light btn-sm">Выход</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ░░░ Меню ░░░ -->
<nav class="bg-white border-bottom">
    <div class="container">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item"><a class="nav-link"             href="index.php">Наш детский сад</a></li>
            <li class="nav-item"><a class="nav-link"             href="news.php">Новости</a></li>
            <li class="nav-item"><a class="nav-link"             href="groups.php">Группы</a></li>
            <li class="nav-item"><a class="nav-link"             href="staff.php">Сотрудники</a></li>
            <li class="nav-item"><a class="nav-link active"      href="contacts.php">Контакты</a></li>
        </ul>
    </div>
</nav>

<!-- ░░░ Контент ░░░ -->
<main class="container my-4 flex-grow-1">

    <h1 class="mb-4">Контакты</h1>

    <div class="card p-4">
        <div class="row g-4">

            <!-- Левая колонка: адрес, часы, телефон, email -->
            <div class="col-lg-6">

                <p class="small text-muted mb-1">Адрес</p>
                <p class="fw-semibold">
                    286793, ДОНЕЦКАЯ НАРОДНАЯ РЕСПУБЛИКА, г.&nbsp;о.&nbsp;Иловайск,<br>
                    г.&nbsp;Иловайск, ул.&nbsp;Левина, д.&nbsp;9А.
                </p>

                <p class="small text-muted mb-1">Режим работы</p>
                <p class="mb-2">
                    пн – пт: 06:30 — 18:30<br>
                    сб – вс: выходной<br>
                    праздничные дни — выходные
                </p>

                <p class="small text-muted mb-1">Телефон</p>
                <p class="mb-2">
                    <a href="tel:+79493091408" class="text-decoration-none">+7 949 309 1408</a>
                </p>

                <p class="small text-muted mb-1">Электронная почта</p>
                <p>
                    <a href="mailto:dou12.romashka@yandex.ru" class="text-decoration-none">
                        dou12.romashka@yandex.ru
                    </a>
                </p>

            </div>

            <!-- Правая колонка: как добраться -->
            <div class="col-lg-6">
                <div class="p-3 h-100 rounded-3 bg-light">
                    <h5 class="fw-semibold mb-2">Как добраться</h5>
                    <p class="mb-0">
                        Ближайшие автобусные остановки — ул.&nbsp;Ярославского, д.&nbsp;4
                    </p>
                </div>
            </div>

        </div>
    </div>

</main>

<footer class="bg-success text-white py-2">
    <div class="container small">
        © <?= date('Y') ?> Детский сад № 12 «Ромашка»
    </div>
</footer>

</body>
</html>
