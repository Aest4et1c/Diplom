<?php declare(strict_types=1);
require_once('user.php');
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Главная страница</title>
		<meta charset="UTF-8">
		
		<link rel="stylesheet" href="css.css" type="text/css">
	</head>
	<body>
		<div class="titul">
            <h1 class="titul">МУНИЦИПАЛЬНОЕ БЮДЖЕТНОЕ ДОШКОЛЬНОЕ ОБРАЗОВАТЕЛЬНОЕ УЧРЕЖДЕНИЕ "ЯСЛИ-САД №12 "РОМАШКА" Г.ИЛОВАЙСК"</h1>
        </div>
		<?php if(!isset($_COOKIE["loggedin"])){?>
			<a class="button" href="/login.php">
				Вход
			</a>
			<?php } else {?>
			<a class="button" href="/index.php?action=logout">
				Выход
			</a>
			<?php }?>
		<div class="main">
			<?php require_once('menu.php'); ?>
        	<div class="tabinfo content">
				<h2>ПРИЕМ В ДЕТСКИЙ САД</h2>
				<p>Документы необходимые для вступления в детсад:
				</p>
				<p>1) Свмдетельство о рождении ребенка, СНИЛС ребенка, ОМС.</p>
				<p>2) Паспорт родителя, СНИЛС родителя</p>
				<p>3) Если родитель является одиночкой, или участником СВО, необходимо принести документ подтверждающий это</p>
        	</div>
		</div>
	</body>
</html>