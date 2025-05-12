<?php
/**
 * Файл: config.php
 * Единая точка подключения к базе KindergartenDiplom через PDO
 */

$host = 'localhost';      // обычно localhost в Open Server
$db   = 'KindergartenDiplom';
$user = 'root';           // имя пользователя MySQL
$pass = '';               // пароль (по умолчанию в Open Server пустой)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // бросать исключения
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // результаты как ассоц-массив
    PDO::ATTR_EMULATE_PREPARES   => false,                  // использ. нативные prepared-statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Критическая ошибка соединения — выводим сообщение и прерываем скрипт
    echo "Ошибка подключения к базе данных: " . $e->getMessage();
    exit;
}