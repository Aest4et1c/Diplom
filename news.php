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
				<h2>НОВОСТИ</h2>
				<p>Вся актуальные новости детского сада публикуются в группе во <a href="https://vk.com/public219868114">Вконтакте</a>

				<img src="./images/vkgroups.jpg">
				<p>или в группе в <a href="">Telegram</a></p>
				<img src="./images/tggroup.jpg">

				</p>
        	</div>
		</div>
	</body>
</html>