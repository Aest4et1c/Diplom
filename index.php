<?php declare(strict_types=1);
require_once('user.php');
$action = $_GET["action"]??"";
if($action == "logout")
{
	unset($_COOKIE["loggedin"]);
}
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
			<?php if(!isset($_COOKIE["loggedin"])){?>
			<a class="button" href="/login.php">
				Вход
			</a>
			<?php } else {?>
			<a class="button" href="/index.php?action=logout">
				Выход
			</a>
			<?php }?>
        </div>
		<div class="main">
			<?php require_once('menu.php'); ?>
        	<div class="tabinfo content">
        	    
        	    <!-- <center> -->
					<h2>ГЛАВНАЯ</h2>
					<p>УВАЖАЕМЫЕ РОДИТЕЛИ и ГОСТИ ! Мы рады приветствовать Вас на сайте МУНИЦИПАЛЬНОГО БЮДЖЕТНОГО ДОШКОЛЬНОГО ОБРАЗОВАТЕЛЬНОГО УЧРЕЖДЕНИЯ "ЯСЛИ-САД № 12 "РОМАШКА" 
						Г. ИЛОВАЙСКА". Мы расскажем Вам об истории детского сада, педагогических программах и технологиях, по которым работает наш коллектив, о здоровье и питании 
						наших воспитанников, о достижениях, мероприятиях и победах нашего детского сада.
					</p>
					
					<img src="./images/mainimage.jpg">
				<!-- </center> -->
        	</div>
		</div>

		<?php
		
		?>
	</body>
</html>