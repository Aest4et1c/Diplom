<?php declare(strict_types=1);
require_once('user.php');
require_once('_groups.php');

if (User::current() === false)
{
	header('Location: /login.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$event = $_POST['event'];
	$events = [
		strtolower('onChildSelected') => function() {
			$child_id 	= $_POST['id'];
			$date 		= intval($_POST['date'] ?? -1);

			$child = new Child(intval($child_id));

			$response = [
				'child' => [
					'name' => $child->name,
					'birthday' => $child->birthday
				],
				'grades' => []
			];
			foreach (Grade::list($child->id) as $grade)
			{
				if ($date === -1 || $date === $grade->date)
				{
					$response['grades'][] = [
						'id' => $grade->id,
						'child' => $grade->child,
						'social' => $grade->social,
						'speech' => $grade->speech,
						'educational' => $grade->educational,
						'artistic' => $grade->artistic,
						'physical' => $grade->physical,
						'date' => (new DateTimeImmutable())->setTimestamp($grade->date)->format('d.m.Y')
					];
				}
			}
			return $response;
		}
	];
	if (isset($events[strtolower($event)]))
	{
		$response = $events[strtolower($event)]();
		if (isset($response)) echo json_encode($response);
		else echo json_encode([]);
	}
	else
	{
		http_response_code(400);
		exit();
	}
	exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Главная страница</title>
		<meta charset="UTF-8">
		
		<link rel="stylesheet" href="css.css" type="text/css">
		<script src="jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="groups.js" type="text/javascript"></script>
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
				<h2>ГРУППЫ</h2>
				<table id="groups-table" style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
					<tr>
						<th width="100%">Наименование</th>
					</tr>
					<?php foreach (Group::list() as $group) { ?>
						<tr id="group-<?php echo $group->id; ?>">
							<td id="name" style="cursor: pointer;" onclick="toggle_group(this, <?php echo $group->id; ?>)"><?php echo $group->name; ?></td>
						</tr>
						<tr id="group-<?php echo $group->id; ?>-sub">
							<td colspan="2">
								<table id="children-table" style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
									<tr>
										<th>Имя</th>
										<th>Дата рождения</th>
									</tr>
									<?php foreach (Child::list($group->id) as $child) { ?>
										<tr id="child-<?php echo $child->id; ?>">
											<td id="name" style="cursor: pointer;" onclick="show_child_grades(this, <?php echo $child->id; ?>)" ><?php echo $child->name; ?></td>
											<td id="birthday"><?php echo (new DateTimeImmutable())->setTimestamp($child->birthday)->format('d.m.Y'); ?></td>
										</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
					<?php } ?>
				</table>
				<div id="child-grades" style="display: none;">
					<h1 id="child-name"></h1>
					<table style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
						<thead>
							<tr>
								<th>Соц-комм развитие</th>
								<th>Речевое развитие</th>
								<th>Познавательное развитие</th>
								<th>Худож.-эстетич. развитие</th>
								<th>Физическое развитие</th>
								<th>Дата</th>
							</tr>
						</thead>
						<tbody>
							<tr id="child-grades-filter">
								<td colspan="6">
									<input type="date" onchange="filter_child_grades(this)">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
        	</div>
		</div>
	</body>
</html>