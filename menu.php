<div class="tabinfo menu">
	<h2>РАЗДЕЛЫ</h2>
	<a href="/index.php"><p>ГЛАВНАЯ</p></a>
	<a href="/news.php"><p>НОВОСТИ</p></a>
	<a href="/reseption.php"><p>ПРИЕМ В ДЕТСКИЙ САД</p></a>
	<a href="/groups.php"><p>ГРУППЫ</p></a>
	<?php if(User::current() && User::current()->has_right('CanAccessAdminPanel')) {?>
	<a href="/adminpage.php"><p>АДМИН</p></a>
	<?php }?>
</div>  