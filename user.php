<?php
	require_once('db.php');
	class Role
	{
		public static function id_to_name(int $id)
		{
			$response = DbConection::instance()->query("SELECT `Name` FROM `Roles` WHERE `ID`={$id}");
			if ($response !== false && $row = $response->fetch_assoc())
			{
				return $row['Name'];
			}
			return '';
		}

		public static function name_to_id(string $name)
		{
			$response = DbConection::instance()->query("SELECT `ID` FROM `Roles` WHERE `Name`={$name}");
			if ($response !== false && $row = $response->fetch_assoc())
			{
				return intval($row['ID']);
			}
			return -1;
		}

		public static function list()
		{
			$response = DbConection::instance()->query('SELECT * FROM `Roles` WHERE 1');
			if ($response !== false)
			{
				while ($row = $response->fetch_assoc())
				{
					yield $row['Name'] => $row['ID'];
				}
			}
		}
	}
	class User implements IDBEntity
	{
		public int $id = -1;
		public string $name;
		public string $login;
		public string $password;
		public int $role;
		public array $rights = [];

		public function __construct($id = -1, $row = null)
		{
			if ($id > 0)
			{
				$response = DbConection::instance()->query(
					"SELECT * FROM `Users` WHERE `ID`={$id}"
				);
				if ($response !== false && $response->num_rows > 0)
				{
					$row = $response->fetch_assoc();
					$this->id = $id;
					$this->name = $row['Name'];
					$this->login = $row['Login'];
					$this->password = $row['Password'];
					$this->role = $row['IDRole'];
				}
			}
			elseif (isset($row))
			{
				$this->id = $row['ID'];
				$this->name = $row['Name'];
				$this->login = $row['Login'];
				$this->password = $row['Password'];
				$this->role = $row['IDRole'];
			}
		}

		public function has_right(string $right)
		{
			$response = DbConection::instance()->query(
				"SELECT `{$right}` FROM `Roles` WHERE `ID`={$this->role}"
			);
			if ($response !== false && $response->num_rows > 0)
			{
				$row = $response->fetch_row();
				return intval($row[0]) != 0;
			}
			return false;
		}

		public function insert()
		{
			$db = DbConection::instance();
			$response = $db->query(
				"INSERT INTO `Users`(`Name`,`Login`,`Password`,`IDRole`)
				 VALUES('{$this->name}','{$this->login}','{$this->password}',{$this->role})"
			);
			$this->id = $db->insert_id;
			return ($response ? true : false);
		}

		public function update()
		{
			$db = DbConection::instance();
			$response = $db->query(
				"UPDATE `Users`
				 SET `Name`='{$this->name}',
				 `Login`='{$this->login}',
				 `Password`='{$this->password}',
				 `IDRole`={$this->role}
				 WHERE `ID`={$this->id}"
			);
			return ($response ? true : false);
		}

		public function remove()
		{
			$response = DbConection::instance()->query(
				"DELETE FROM `Users` WHERE `ID`={$this->id}"
			);
			return ($response ? true : false);
		}

		public function list_children()
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Kids_Users` WHERE `IDUser`={$this->id}"
			);
			if ($response !== false)
			{
				while ($row = $response->fetch_assoc())
				{
					yield new Child(-1, $row);
				}
			}
		}

		public function bind_child(int $id)
		{
			DbConection::instance()->query(
				"INSERT INTO `Kids_Users`(`IDKid`,`IDUser`)
				 VALUES({$id},{$this->id})"
			);
		}

		public static function list()
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Users` WHERE 1"
			);
			if ($response !== false)
			{
				while ($row = $response->fetch_assoc())
				{
					yield new User(-1, $row);
				}
			}
		}

		private static User $cur;
		public static function current()
		{
			if (!isset($_COOKIE['loggedin'])) return false;
			if (!isset(self::$cur))
			{
				self::$cur = new User(intval($_COOKIE['loggedin']));
			}
			return self::$cur;
		}
	}
?>
