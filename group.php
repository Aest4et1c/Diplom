<?php
session_start();
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if(!$id){ header('Location: groups.php'); exit; }

/* группа */
$group = $pdo->prepare("SELECT * FROM groups WHERE id=?");
$group->execute([$id]);
$group = $group->fetch();
if(!$group){ header('Location: groups.php'); exit; }

/* педагоги */
$teachers = $pdo->prepare("
    SELECT s.full_name, s.position, s.photo_url
      FROM staff s
      JOIN group_staff gs ON gs.staff_id=s.id
     WHERE gs.group_id=?
  ORDER BY FIELD(s.position,'Заведующий','Воспитатель'), s.full_name");
$teachers->execute([$id]);
$teachers = $teachers->fetchAll();

/* дети */
$kids = $pdo->prepare("
    SELECT k.full_name, k.birth_date
      FROM kids k
      JOIN group_kid_history gkh ON gkh.kid_id=k.id
     WHERE gkh.group_id=? AND gkh.to_date IS NULL
  ORDER BY k.full_name");
$kids->execute([$id]);
$kids = $kids->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($group['name']) ?> | Детский сад «Ромашка»</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .teacher-photo{width:64px;height:85px;object-fit:cover;object-position:top;border-radius:.25rem;}
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- шапка (та же, что выше) -->
<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between align-items-center">
      <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка»</h1>
      <div>
          <?php if (!($_SESSION['user']??null)): ?>
              <a class="btn btn-light btn-sm" href="login.php">Вход</a>
          <?php else: ?>
              <span class="me-2">Здравствуйте, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></span>
              <?php if (($_SESSION['user']['role']??'')==='admin'): ?>
                  <a class="btn btn-warning btn-sm me-2" href="/admin/">Админ-панель</a>
              <?php endif; ?>
              <a class="btn btn-light btn-sm" href="logout.php">Выход</a>
          <?php endif; ?>
      </div>
  </div>
</header>

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

<main class="container my-4 flex-grow-1">
   <h1 class="mb-1"> <?= htmlspecialchars($group['name']) ?></h1>
   <p class="text-muted"><?= (int)$group['age_from'] ?>–<?= (int)$group['age_to'] ?> года</p>

   <!-- педагоги -->
   <div class="card p-3 mb-4">
       <h5 class="mb-3">Педагоги группы</h5>
       <div class="row g-3">
           <?php foreach($teachers as $t): ?>
               <?php $photo=$t['photo_url']?:'uploads/photos/placeholder.jpg'; ?>
               <div class="col-auto d-flex">
                   <img src="<?= htmlspecialchars($photo) ?>" class="teacher-photo me-2" alt="">
                   <div>
                       <p class="small text-muted mb-1"><?= htmlspecialchars($t['position']) ?></p>
                       <p class="mb-0"><?= implode('<br>',array_map('htmlspecialchars',explode(' ',$t['full_name']))) ?></p>
                   </div>
               </div>
           <?php endforeach; ?>
           <?php if(!$teachers): ?><p class="text-muted">Педагоги ещё не назначены.</p><?php endif; ?>
       </div>
   </div>

   <!-- описание группы -->
<?php if($group['description']): ?>
   <h4 class="mb-2">Описание</h4>              <!-- было “Особенности занятий…” -->
   <p><?= nl2br(htmlspecialchars($group['description'])) ?></p>
   <hr>
<?php endif; ?>


   <!-- дети -->
   <h5 class="mb-3">Дети группы</h5>
   <?php if($kids): ?>
       <ul>
           <?php foreach($kids as $k): ?>
               <li><?= htmlspecialchars($k['full_name']) ?> (<?= date('d.m.Y',strtotime($k['birth_date'])) ?>)</li>
           <?php endforeach; ?>
       </ul>
   <?php else: ?>
       <p class="text-muted">Пока ни одного ребёнка.</p>
   <?php endif; ?>
</main>

<footer class="bg-success text-white py-2 mt-auto">
   <div class="container small">© <?= date('Y') ?> Детский сад «Ромашка»</div>
</footer>
</body>
</html>
