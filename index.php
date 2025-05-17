<?php
/**
 * index.php — главная страница «Наш детский сад»
 * ФИО и фото заведующего теперь подтягиваются из таблицы staff
 */

session_start();
require_once __DIR__ . '/config.php';   // подключение к БД

/* ── текущий пользователь (для блока «Вход / Выход») ───────────────────────── */
$currentUser = $_SESSION['user'] ?? null;              // ['id'=>…, 'username'=>…, 'role'=>…]
$isAdmin     = $currentUser && $currentUser['role'] === 'admin';

/* ── берём заведующего из базы ────────────────────────────────────────────── */
$headStmt = $pdo->prepare(
    "SELECT full_name, photo_url
       FROM staff
      WHERE position = 'Заведующий'
        AND (fire_date IS NULL OR fire_date > CURDATE())
      ORDER BY hire_date ASC
      LIMIT 1"
);
$headStmt->execute();
$head = $headStmt->fetch() ?: ['full_name' => 'Заведующий', 'photo_url' => null];

$headName  = $head['full_name'];
$headPhoto = $head['photo_url'] ?: 'uploads/photos/placeholder.jpg';   // запасной файл
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Детский сад № 12 «Ромашка»</title>
    <link rel="icon" type="image/png" href="/image/web_logo.png">


    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">

<!-- ░░░ Верхняя полоска ░░░ -->
<header class="bg-success text-white py-2">
    <div class="container d-flex align-items-center justify-content-between">
        <h1 class="h5 m-0 fw-bold">
            ГКДОУ «Детский сад № 12 «Ромашка» г.&nbsp;о.&nbsp;Иловайск»
        </h1>

        <!-- Вход / Выход / Админ -->
        <div>
            <?php if (!$currentUser): ?>
                <a href="login.php" class="btn btn-light btn-sm">Вход</a>
            <?php else: ?>
                <span class="me-2">
                    Здравствуйте, <strong><?= htmlspecialchars($currentUser['name'] ?? $currentUser['username']) ?></strong>
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

<!-- ░░░ Меню ░░░ -->
<nav class="bg-white border-bottom">
    <div class="container">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item"><a class="nav-link active" href="index.php">Наш детский сад</a></li>
            <li class="nav-item"><a class="nav-link" href="news.php">Новости</a></li>
            <li class="nav-item"><a class="nav-link" href="groups.php">Группы</a></li>
            <li class="nav-item"><a class="nav-link" href="staff.php">Сотрудники</a></li>
            <li class="nav-item"><a class="nav-link" href="contacts.php">Контакты</a></li>
        </ul>
    </div>
</nav>

<!-- ░░░ Контент ░░░ -->
<main class="container my-4 flex-grow-1">
    <h1 class="mb-4">Наш детский сад</h1>

    <div class="card p-4">
        <div class="row g-4">

            <!-- Фото заведующей -->
            <div class="col-md-3 text-center">
                <img src="<?= htmlspecialchars($headPhoto) ?>" alt="Фото заведующей"
                     class="img-fluid rounded border">
                <p class="mt-2 mb-0 text-muted">Заведующий</p>
                <p class="fw-semibold mb-0"><?= htmlspecialchars($headName) ?></p>
            </div>

            <!-- Текст приветствия -->
            <div class="col-md-9">
                <p class="text-justify">
                    Уважаемые родители, коллеги и гости нашего сайта!<br>
                    Мы рады приветствовать вас на сайте нашего образовательного учреждения!
                    С помощью сайта вы сможете узнать о новостях и интересных событиях,
                    происходящих в нашем детском саду, получить информацию о том,
                    какие образовательные программы реализуются педагогическим коллективом,
                    задать интересующие вас вопросы по воспитанию и обучению детей
                    специалистам детского сада, узнать сведения о педагогических работниках,
                    увидеть фото и видео материалы увлекательной жизни воспитанников и сотрудников.
                </p>

                <p class="text-justify">
                    Сегодня наш детский сад — это современное дошкольное образовательное
                    учреждение общеразвивающего вида, в котором сохраняются лучшие традиции прошлого.
                    Коллектив детского сада живёт и работает, «отдавая своё сердце детям»,
                    совершенствуя педагогическое мастерство, создавая комфорт и уют.
                </p>

                <p class="text-justify">
                    Мы рады каждому посетителю! Оставляйте ваши отзывы и пожелания —
                    мы постараемся прислушаться к разумным и интересным предложениям.
                    Пусть общение с нами добавит вам хорошего настроения, уверенности в будущем,
                    поможет разобраться в современных вопросах воспитания и образования,
                    лучше понять себя и своих детей!
                </p>

                <p class="text-justify mb-0">
                    От души желаем вам успехов и удач, здоровья и оптимизма,
                    и надеемся на плодотворное сотрудничество нашего коллектива с вами!
                </p>

                <p class="text-end mt-4">
                    С уважением, заведующий ГКДОУ «Детский сад № 12 «РОМАШКА»<br>
                    г.&nbsp;о.&nbsp;Иловайск, ДНР<br>
                    <strong><?= htmlspecialchars($headName) ?></strong>
                </p>
            </div>
        </div>
    </div>
</main>

<footer class="bg-success text-white py-2 mt-auto">
    <div class="container small">
        © <?= date('Y') ?> Детский сад № 12 «Ромашка»
    </div>
</footer>

</body>
</html>
