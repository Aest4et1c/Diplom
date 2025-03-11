<?php declare(strict_types=1);
require_once("db.php");
require_once("user.php");
require_once("_groups.php");

if (User::current() === false)
{
	header('Location: /login.php');
	exit();
}
if (!User::current()->has_right('CanAccessAdminPanel'))
{
	header('Location: /index.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$event = $_POST['event'];
	$events = [
		strtolower('onUserEdit') => function() {
			$user_id 	= $_POST['id'];
			$user_name 	= $_POST['name'];
			$user_login = $_POST['login'];
			// #TODO : check if user exists
			$user_pass 	= $_POST['password'];
			$user_role 	= Role::name_to_id($_POST['role']);
			if ($user_role <= 0)
			{
				return [
					'error' => 'Указанной роли не существует'
				];
			}

			$user = new User(intval($user_id));
			$user->name = $user_name;
			$user->login = $user_login;
			$user->password = $user_pass;
			$user->role = $user_role;

			if (!$user->update())
			{
				return [ 'error' => 'Неизвестная ошибка' ];
			}
		},
		strtolower('onUserRemove') => function() {
			$user_id = $_POST['id'];
			$user = new User(intval($user_id));
			if (!$user->remove())
			{
				return [ 'error' => 'Неизвестная ошибка' ];
			}
		},
		strtolower('onUserAdd') => function() {
			$user_name 	= $_POST['name'];
			$user_login = $_POST['login'];
			$user_pass 	= $_POST['password'];
			$user_role 	= $_POST['role'];

			$user = new User();
			$user->name = $user_name;
			$user->login = $user_login;
			$user->password = $user_pass;
			$user->role = intval($user_role);

			if (!$user->insert())
			{
				return [ 'error' => 'Неизвестная ошибка' ];
			}
			return [
				'id' => $user->id,
				'role' => Role::id_to_name($user_role)
			];
		},
		strtolower('onChildAdd') => function() {
			$name 		= $_POST['name'];
			$birthday 	= $_POST['birthday'];
			$group 		= $_POST['group'];
			$real_bd	= new DateTimeImmutable($birthday);

			$k 				= new Child();
			$k->name 		= $name;
			$k->birthday 	= $real_bd->getTimestamp();
			$k->group 		= intval($group);
			if (!$k->insert())
			{
				return [ 'error' => 'Неизвестная ошибка' ];
			}
			return [ 'id' => $k->id ];
		},
		strtolower('onChildEdit') => function() {
			$id			= $_POST['id'];
			$name 		= $_POST['name'];
			$birthday 	= $_POST['birthday'];
			$real_bd	= new DateTimeImmutable($birthday);

			$k 				= new Child(intval($id));
			$k->name 		= $name;
			$k->birthday 	= $real_bd->getTimestamp();
			$k->update();
		},
		strtolower('onChildRemove') => function() {
			$id			= $_POST['id'];
			$k 			= new Child(intval($id));
			$k->remove();
		},
		strtolower('onGroupAdd') => function() {
			$name = $_POST['name'];

			$g = new Group();
			$g->name = $name;

			if (!$g->insert())
			{
				return [ 'error' => 'Неизвестная ошибка' ];
			}
			return [ 'id' => $g->id ];
		},
		strtolower('onGroupEdit') => function() {
			$id 		= $_POST['id'];
			$new_name 	= $_POST['name'];

			$g = new Group(intval($id));
			$g->name = $new_name;
			$g->update();
		},
		strtolower('onGroupRemove') => function() {
			$id = intval($_POST['id']);

			$g = new Group($id);

			if (iterator_count(Child::list($g->id)) > 0)
			{
				return [
					'error' => 'Нельзя удалить группу в которой есть дети!'
				];
			}

			$g->remove();
		},
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
		},
		strtolower('onGradeRemove') => function() {
			$id = intval($_POST['id']);

			$g = new Grade($id);
			$g->remove();
		},
		strtolower('onGradeAdd') => function() {
			$child_id 	= intval($_POST['child']);
			$social 	= intval($_POST['social']);
			$speech 	= intval($_POST['speech']);
			$educational= intval($_POST['educational']);
			$artistic	= intval($_POST['artistic']);
			$physical	= intval($_POST['physical']);
			$date		= intval($_POST['date']);

			$g = new Grade();
			$g->child = $child_id;
			$g->social = $social;
			$g->speech = $speech;
			$g->educational = $educational;
			$g->artistic = $artistic;
			$g->physical = $physical;
			$g->date = $date;
			if (!$g->insert())
			{
				return [
					'error' => 'Не удалось добавить оценку!'
				];
			}
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
		<script src="adminpage.js" type="text/javascript"></script>

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
			<div class="tabinfo menu">
				<h2>РАЗДЕЛЫ</h2>
				<?php if (User::current()->has_right('CanEditUsers')) { ?>
					<a class="item" href="#users"><p>ПОЛЬЗОВАТЕЛИ</p></a>
				<?php } ?>
				<?php if (User::current()->has_right('CanEditGrade')) { ?>
					<a class="item" href="#children"><p>ДЕТИ</p></a>
				<?php } ?>
				<a class="item" href="/index.php"><p>НАЗАД</p></a>
			</div>
        	<div class="tabinfo content">
				<?php if (User::current()->has_right('CanEditUsers')) { ?>
					<span id="users">
						<h2>ПОЛЬЗОВАТЕЛИ</h2>
						<table id="users-table" style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
							<tr>
								<th>Имя</th>
								<th>Логин</th>
								<th>Пароль</th>
								<th>Роль</th>
								<th>Действия</th>
							</tr>
							<?php
								$db = DbConection::instance();
								$response = $db->query('SELECT * FROM `Users` WHERE 1');
								if ($response !== false)
								{
									while ($row = $response->fetch_assoc())
									{
										printf(
											'<tr id="user-%u">
											<td id="name">%s</td>
											<td id="login">%s</td>
											<td id="password">%s</td>
											<td id="role">%s</td>
											<td>
											<input type="button" value="Редактировать" onclick="edit_user(this, %u)">
											<input type="button" value="Удалить"  onclick="remove_user(this, %u)">
											</td>
											</tr>',
											$row['ID'],
											$row['Name'],
											$row['Login'],
											$row['Password'],
											Role::id_to_name(intval($row['IDRole'])),
											$row['ID'],
											$row['ID']
										);
									}
								}
							?>
							<tr id="user-new">
								<td><input id="name" type="text" required></td>
								<td><input id="login" type="text" required></td>
								<td><input id="password" type="text" required></td>
								<td>
									<select id="role">
										<?php
											foreach (Role::list() as $name => $id)
											{
												printf(
													'<option value="%s">%s</option>',
													$id,
													$name
												);
											}
										?>
									</select>
								</td>
								<td><input type="button" value="Добавить" onclick="add_user(this)"></td>
							</tr>
						</table>
					</span>
				<?php } ?>
				<?php if (User::current()->has_right('CanEditGrade')) { ?>
					<span id="children">
						<h2>ГРУППЫ</h2>
						<table id="groups-table" style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
							<tr>
								<th width="100%">Наименование</th>
								<th>Действия</th>
							</tr>
							<?php foreach (Group::list() as $group) { ?>
								<tr id="group-<?php echo $group->id; ?>">
									<td id="name" style="cursor: pointer;" onclick="toggle_group(this, <?php echo $group->id; ?>)"><?php echo $group->name; ?></td>
									<td>
										<div style="white-space: nowrap;">
											<input type="button" value="Редактировать" onclick="edit_group(this, <?php echo $group->id; ?>)" >
											<input type="button" value="Удалить" onclick="remove_group(this, <?php echo $group->id; ?>)" >
										</div>
									</td>
								</tr>
								<tr style="display: none;" id="group-<?php echo $group->id; ?>-sub">
									<td colspan="2">
										<table id="children-table" style="width: 100%; border: 1px solid black; text-align: center;" rules="rows">
											<tr>
												<th>Имя</th>
												<th>Дата рождения</th>
												<th>Действия</th>
											</tr>
											<?php foreach (Child::list($group->id) as $child) { ?>
												<tr id="child-<?php echo $child->id; ?>">
													<td id="name" style="cursor: pointer;" onclick="show_child_grades(this, <?php echo $child->id; ?>)" ><?php echo $child->name; ?></td>
													<td id="birthday"><?php echo (new DateTimeImmutable())->setTimestamp($child->birthday)->format('d.m.Y'); ?></td>
													<td>
														<input type="button" value="Редактировать" onclick="edit_child(this, <?php echo $child->id; ?>)">
														<input type="button" value="Удалить" onclick="remove_child(this, <?php echo $child->id; ?>)">
													</td>
												</tr>
											<?php } ?>
											<tr id="group-<?php echo $group->id; ?>-child-new">
												<td><input id="name" type="text" required></td>
												<td><input id="birthday" type="date" required></td>
												<td><input type="button" value="Добавить" onclick="add_child(this, <?php echo $group->id; ?>)"></td>
											</tr>
										</table>
									</td>
								</tr>
							<?php } ?>
							<tr id="group-new">
								<td><input id="name" type="text" required></td>
								<td><input type="button" value="Добавить" onclick="add_group(this)"></td>
							</tr>
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
										<th>Действия</th>
									</tr>
								</thead>
								<tbody>
									<tr id="child-grades-filter">
										<td colspan="7">
											<input type="date" onchange="filter_child_grades(this)">
										</td>
									</tr>
									<tr id="child-grade-new">
										<td><input type="text" id="social" onchange="parse_score(this)"></td>
										<td><input type="text" id="speech" onchange="parse_score(this)"></td>
										<td><input type="text" id="educational" onchange="parse_score(this)"></td>
										<td><input type="text" id="artistic" onchange="parse_score(this)"></td>
										<td><input type="text" id="physical" onchange="parse_score(this)"></td>
										<td><input type="date" id="date"></td>
										<td><input type="button" value="Добавить" onclick="add_grade(this)"></td>
									</tr>
								</tbody>
							</table>
						</div>
					</span>
				<?php } ?>
        	</div>
		</div>
	</body>
</html>