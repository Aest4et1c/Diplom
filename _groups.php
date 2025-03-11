<?php
require_once('db.php');
class Group implements IDBEntity
{
	public int $id = -1;
	public string $name;

	public function __construct(int $id = -1)
	{
		if ($id > 0)
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Groups` WHERE `ID`={$id}"
			);
			if ($response !== false && $response->num_rows > 0)
			{
				$row = $response->fetch_assoc();
				$this->id = $id;
				$this->name = $row['Name'];
			}
		}
	}

	public function insert()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"INSERT INTO `Groups`(`Name`) VALUES('{$this->name}')"
		);
		$this->id = $db->insert_id;
		return ($response ? true : false);
	}

	public function update()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"UPDATE `Groups` SET `Name`='{$this->name}' WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public function remove()
	{
		$response = DbConection::instance()->query(
			"DELETE FROM `Groups` WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public static function list()
	{
		$response = DbConection::instance()->query(
			"SELECT * FROM `Groups` WHERE 1"
		);
		if ($response !== false)
		{
			while($row = $response->fetch_assoc())
			{
				$item = new Group();
				$item->id = $row['ID'];
				$item->name = $row['Name'];
				yield $item;
			}
		}
	}
}
class Child implements IDBEntity
{
	public int $id = -1;
	public string $name;
	public int $birthday;
	public int $group;

	public function __construct($id = -1, $row = null)
	{
		if ($id > 0)
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Kids` WHERE `ID`={$id}"
			);
			if ($response !== false && $response->num_rows > 0)
			{
				$row = $response->fetch_assoc();
				$this->id = $id;
				$this->name = $row['Name'];
				$this->birthday = $row['Birthday'];
				$this->group = $row['IDGroup'];
			}
		}
		elseif (isset($row))
		{
			$this->id = $row['ID'];
			$this->name = $row['Name'];
			$this->birthday = $row['Birthday'];
			$this->group = $row['IDGroup'];
		}
	}

	public function insert()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"INSERT INTO `Kids`(`Name`,`Birthday`,`IDGroup`)
			 VALUES('{$this->name}',{$this->birthday},{$this->group})"
		);
		$this->id = $db->insert_id;
		return ($response ? true : false);
	}

	public function update()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"UPDATE `Kids`
			 SET `Name`='{$this->name}',
			 `Birthday`={$this->birthday},
			 `IDGroup`={$this->group}
			 WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public function remove()
	{
		$response = DbConection::instance()->query(
			"DELETE FROM `Kids` WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public static function list($groupId = -1)
	{
		$response = false;
		if ($groupId > 0)
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Kids` WHERE `IDGroup`={$groupId}"
			);
		}
		else
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Kids` WHERE 1"
			);
		}
		if ($response !== false)
		{
			while ($row = $response->fetch_assoc())
			{
				yield new Child(-1, $row);
			}
		}
	}
}
class Grade implements IDBEntity
{
	public int $id = -1;
	public int $child;
	public int $social;
	public int $speech;
	public int $educational;
	public int $artistic;
	public int $physical;
	public int $date;

	public function __construct(int $id = -1, $row = null)
	{
		if ($id > 0)
		{
			$response = DbConection::instance()->query(
				"SELECT * FROM `Grades` WHERE `ID`={$id}"
			);
			if ($response !== false && $response->num_rows > 0)
			{
				$row = $response->fetch_assoc();
				$this->id 			= $id;
				$this->child 		= $row['IDKid'];
				$this->social 		= $row['Social'];
				$this->speech 		= $row['Speech'];
				$this->educational 	= $row['Educational'];
				$this->artistic 	= $row['Artistic'];
				$this->physical 	= $row['Physical'];
				$this->date 		= $row['Date'];
			}
		}
		elseif (isset($row))
		{
			$this->id 			= $row['ID'];
			$this->child 		= $row['IDKid'];
			$this->social 		= $row['Social'];
			$this->speech 		= $row['Speech'];
			$this->educational 	= $row['Educational'];
			$this->artistic 	= $row['Artistic'];
			$this->physical 	= $row['Physical'];
			$this->date 		= $row['Date'];
		}
	}

	public function insert()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"INSERT INTO `Grades`(
				`IDKid`,
				`Social`,
				`Speech`,
				`Educational`,
				`Artistic`,
				`Physical`,
				`Date`
			) VALUES(
				{$this->child},
				{$this->social},
				{$this->speech},
				{$this->educational},
				{$this->artistic},
				{$this->physical},
				{$this->date}
			)"
		);
		$this->id = $db->insert_id;
		return ($response ? true : false);
	}

	public function update()
	{
		$db = DbConection::instance();
		$response = $db->query(
			"UPDATE `Grades` SET
			 `IDKid`={$this->child}
			 `Social`={$this->social}
			 `Speech`={$this->speech}
			 `Educational`={$this->educational}
			 `Artistic`={$this->artistic}
			 `Physical`={$this->physical}
			 `Date`={$this->date}
			 WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public function remove()
	{
		$response = DbConection::instance()->query(
			"DELETE FROM `Grades` WHERE `ID`={$this->id}"
		);
		return ($response ? true : false);
	}

	public static function list(int $child_id)
	{
		$response = DbConection::instance()->query(
			"SELECT * FROM `Grades` WHERE `IDKid`={$child_id}"
		);
		if ($response !== false)
		{
			while($row = $response->fetch_assoc())
			{
				yield new Grade(-1, $row);
			}
		}
	}
}
?>