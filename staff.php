<?php
/**
 * staff.php — страница «Сотрудники»
 * Карточки фикс‑размера 250 × (3/4), целиком кликабельны: переход на profile.php?staff_id=…
 */

session_start();
require_once __DIR__ . '/config.php';

$user    = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';

/* Выбираем сотрудников: заведующий → воспитатель → остальные */
$sql = "
    SELECT id, full_name, position, photo_url          -- id нужен для ссылки
      FROM staff
     WHERE fire_date IS NULL OR fire_date > CURDATE()
  ORDER BY
      CASE position
           WHEN 'Заведующий'  THEN 1
           WHEN 'Воспитатель' THEN 2
           ELSE 3
      END,
      position,
      full_name
";
$staff = $pdo->query($sql)->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Сотрудники | Детский сад № 12 «Ромашка»</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    .staff-card        { max-width: 250px; margin: 0 auto; cursor: pointer; }

    .staff-card__photo {
        width: 100%;
        aspect-ratio: 3 / 4;
        object-fit: cover;
        object-position: top;
        border-top-left-radius: .375rem;
        border-top-right-radius: .375rem;
    }

    .staff-card__body { padding: 1rem; }
    .staff-card__pos  { font-size: .85rem; color: #6c757d; }
    .staff-card__name { font-weight: 600; line-height: 1.15; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- верхняя полоска -->
<header class="bg-success text-white py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h5 m-0 fw-bold">ГКДОУ «Детский сад № 12 «Ромашка»</h1>
        <div>
            <?php if (!$user): ?>
                <a class="btn btn-light btn-sm" href="login.php">Вход</a>
            <?php else: ?>
                <span class="me-2">Здравствуйте, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                <?php if ($isAdmin): ?>
                    <a href="/admin/"      class="btn btn-warning btn-sm me-2">Админ‑панель</a>
                <?php else: ?>
                    <a href="/profile.php" class="btn btn-primary btn-sm me-2">Личный кабинет</a>
                <?php endif; ?>
                <a class="btn btn-light btn-sm" href="logout.php">Выход</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- меню -->
<nav class="bg-white border-bottom">
    <div class="container">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item"><a class="nav-link"         href="index.php">Наш детский сад</a></li>
            <li class="nav-item"><a class="nav-link"         href="news.php">Новости</a></li>
            <li class="nav-item"><a class="nav-link"         href="groups.php">Группы</a></li>
            <li class="nav-item"><a class="nav-link active"  href="staff.php">Сотрудники</a></li>
            <li class="nav-item"><a class="nav-link"         href="contacts.php">Контакты</a></li>
        </ul>
    </div>
</nav>

<!-- контент -->
<main class="container my-4 flex-grow-1">
    <h1 class="mb-4">Сотрудники</h1>

    <?php if (!$staff): ?>
        <div class="alert alert-info">Данные о сотрудниках пока не внесены.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($staff as $p): ?>
                <?php
                    $id    = (int)$p['id'];
                    $photo = $p['photo_url'] ?: 'uploads/photos/placeholder.jpg';
                    $name  = implode('<br>', array_map('htmlspecialchars', explode(' ', $p['full_name'])));
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <a href="profile.php?staff_id=<?= $id ?>"
                       class="card staff-card h-100 shadow-sm border-0 text-decoration-none text-dark d-block">
                        <img class="staff-card__photo" src="<?= htmlspecialchars($photo) ?>" alt="Фото сотрудника">
                        <div class="staff-card__body">
                            <p class="staff-card__pos mb-1"><?= htmlspecialchars($p['position']) ?></p>
                            <p class="staff-card__name mb-0"><?= $name ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-success text-white py-2 mt-auto">
    <div class="container small">© <?= date('Y') ?> Детский сад № 12 «Ромашка»</div>
</footer>

</body>
</html>
