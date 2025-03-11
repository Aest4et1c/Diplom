<?php declare(strict_types=1);
require_once("db.php");
$success = false;
$error = false;
if(isset($_GET["login"]))
{
    $enter = DbConection::instance()->query("SELECT * FROM `Users` WHERE `Login` = '{$_GET["login"]}' AND `Password` = '{$_GET["password"]}'");
    if($enter !== false && $enter->num_rows > 0)
    {
        $success = true;
        setcookie("loggedin", $enter->fetch_row()[0]);
    }
    else
    {
        $error = true;
    }
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
        <div class="main">
            <div class="login">
                <?php if($success){?>
                    <p>Вы успешно вошли</p>
                    <a href="/index.php">На главную</a>
                <?php } else {?>
                    <form style="text-align: center;" action="/login.php" method="GET">
                        <label for="login">Логин</label><br>
                        <input type="text" name="login"><br>
                        <label for="password">Пароль</label><br>
                        <input type="password" name="password"><br>
                        <input style="font-weight: bold;" type="submit" value="Войти">
                        <?php if($error) {?>
                            <p>Неверный логин или пароль</p>
                        <?php }?>
                    </form>
                <?php }?>
            </div>
        </div>
	</body>
</html>