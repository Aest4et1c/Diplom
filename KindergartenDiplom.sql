-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 14 2025 г., 13:48
-- Версия сервера: 5.7.39-log
-- Версия PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `KindergartenDiplom`
--

-- --------------------------------------------------------

--
-- Структура таблицы `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` bigint(20) NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `articles`
--

INSERT INTO `articles` (`id`, `title`, `body`, `cover_image`, `staff_id`, `created_at`, `updated_at`, `status`) VALUES
(5, 'День здоровья: спортивные эстафеты на свежем воздухе', 'На территории детского сада прошёл «День здоровья». Воспитатели подготовили пять весёлых эстафет: «Бег с мячом», «Перенеси воду», «Меткий стрелок» и другие. Дети активно участвовали, учились поддерживать командный дух и радовались первым тёплым дням апреля.', 'image/DZ.jpg', NULL, '2025-05-01 09:56:17', '2025-05-05 15:53:22', 1),
(6, 'Наш огород на подоконнике: посадка зелени', 'В старшей группе «Радуга» стартовал познавательный проект «Огород на подоконнике». Вместе с воспитателем ребята посадили семена укропа и базилика. Теперь каждый день дети наблюдают, как появляются всходы, поливают растения и учатся ухаживать за ними.', 'image/Огород на.jpg', NULL, '2025-05-01 09:56:17', '2025-05-07 11:29:43', 1),
(9, 'Test', 'TestTestTestTestTestTestTestTestTestTestTestTestTestTest', 'image/6818a53464684.jpg', NULL, '2025-05-05 14:47:00', '2025-05-05 15:30:10', 1),
(10, 'Сегодня защита Диплома', 'Оберемок Янислав Николаевич защитил диплом на 5', 'image/6818b4814b713.jpg', NULL, '2025-05-05 15:52:17', '2025-05-05 16:57:57', 1),
(11, 'dfdhgsdfgh', 'gfhsdxfghdfghdfghdfghdrfh', 'image/681b1b6b70624.jpg', NULL, '2025-05-07 11:35:55', '2025-05-07 11:35:55', 0),
(14, 'Test 3', 'Test 4', 'image/6824558d24ef3.jpg', 7, '2025-05-14 11:34:21', '2025-05-14 13:39:33', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` bigint(20) NOT NULL,
  `kid_id` int(11) NOT NULL,
  `att_date` date NOT NULL,
  `present` tinyint(1) DEFAULT '1',
  `comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT '?️',
  `room_number` varchar(10) DEFAULT NULL,
  `age_from` tinyint(4) DEFAULT NULL,
  `age_to` tinyint(4) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `groups`
--

INSERT INTO `groups` (`id`, `name`, `icon`, `room_number`, `age_from`, `age_to`, `description`) VALUES
(1, 'Звёздочки', '⭐', '101', 3, 4, 'Приветствуем вас в группе Звездочки'),
(2, 'Солнышки', '☀️', '202', 4, 5, 'Приветствуем вас в группе Солнышки'),
(3, 'Радуга', '🌈', '303', 5, 6, 'Приветствуем вас в группе Радуга'),
(4, 'Мишки', '🐻', '404', 5, 6, 'Приветствуем вас в группе Мишкааааа');

-- --------------------------------------------------------

--
-- Структура таблицы `group_kid_history`
--

DROP TABLE IF EXISTS `group_kid_history`;
CREATE TABLE `group_kid_history` (
  `id` int(11) NOT NULL,
  `kid_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `group_kid_history`
--

INSERT INTO `group_kid_history` (`id`, `kid_id`, `group_id`, `from_date`, `to_date`) VALUES
(1, 4, 2, '2024-09-01', NULL),
(2, 5, 3, '2024-09-01', NULL),
(3, 6, 1, '2024-09-01', NULL),
(4, 7, 3, '2024-09-01', NULL),
(5, 8, 2, '2024-09-01', NULL),
(6, 9, 3, '2024-09-01', NULL),
(7, 10, 1, '2024-09-01', NULL),
(8, 11, 1, '2024-09-01', NULL),
(9, 12, 1, '2024-09-01', NULL),
(10, 13, 2, '2024-09-01', NULL),
(11, 14, 3, '2024-09-01', NULL),
(12, 15, 2, '2024-09-01', '2025-05-02'),
(13, 16, 3, '2024-09-01', NULL),
(14, 17, 1, '2024-09-01', NULL),
(15, 18, 1, '2024-09-01', NULL),
(16, 19, 4, '2025-05-02', NULL),
(17, 2, 2, '2025-05-04', '2025-05-04'),
(18, 2, 2, '2025-05-04', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `group_staff`
--

DROP TABLE IF EXISTS `group_staff`;
CREATE TABLE `group_staff` (
  `group_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `lead_teacher` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `group_staff`
--

INSERT INTO `group_staff` (`group_id`, `staff_id`, `lead_teacher`) VALUES
(1, 1, 0),
(1, 2, 0),
(2, 4, 0),
(2, 10, 0),
(3, 5, 0),
(3, 6, 0),
(4, 7, 0),
(4, 9, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `kids`
--

DROP TABLE IF EXISTS `kids`;
CREATE TABLE `kids` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `birth_date` date NOT NULL,
  `medical_note` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `kids`
--

INSERT INTO `kids` (`id`, `full_name`, `birth_date`, `medical_note`) VALUES
(1, 'Иванов Илья Владимирович', '2019-03-12', 'Аллергия на цветы'),
(2, 'Петрова Анна Ивановна', '2020-07-25', 'Нет особых замечаний'),
(4, 'Алексеев Павел Кириллович', '2020-05-17', 'Аллергия на молочные продукты'),
(5, 'Антонова Мария Егоровна', '2019-09-03', NULL),
(6, 'Белов Андрей Сергеевич', '2021-02-11', 'Частые ОРВИ'),
(7, 'Богданова София Артёмовна', '2019-12-22', NULL),
(8, 'Васильев Кирилл Олегович', '2020-07-08', 'Гиперактивность'),
(9, 'Григорьева Алиса Михайловна', '2020-11-19', NULL),
(10, 'Денисов Матвей Даниилович', '2019-03-30', 'Непереносимость глютена'),
(11, 'Егорова Дарья Павловна', '2021-01-15', NULL),
(12, 'Жуков Иван Романович', '2020-04-25', NULL),
(13, 'Захарова Виктория Евгеньевна', '2019-06-12', NULL),
(14, 'Ильин Никита Игоревиччч', '2020-10-03', ''),
(15, 'Карпова Ксения Максимовна', '2020-08-29', NULL),
(16, 'Лебедев Артём Константинович', '2019-11-07', 'Аллергия на пыльцу'),
(17, 'Мельникова Полина Владиславовна', '2021-03-02', NULL),
(18, 'Никифоров Тимофей Александрович', '2020-02-14', NULL),
(19, 'Иванов Иван Иванович', '2001-02-20', ''),
(21, 'Петров Роман Григорьевич', '2003-03-20', 'Нет');

-- --------------------------------------------------------

--
-- Структура таблицы `media_files`
--

DROP TABLE IF EXISTS `media_files`;
CREATE TABLE `media_files` (
  `id` bigint(20) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `article_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `media_files`
--

INSERT INTO `media_files` (`id`, `file_url`, `caption`, `uploaded_at`, `article_id`) VALUES
(4, 'image/6818af5268b76.jpg', '', '2025-05-05 15:30:10', 9),
(8, 'image/6818bd4583c2e.jpg', '', '2025-05-05 16:29:41', 10),
(9, 'image/6818c3e5f2e63.jpg', '', '2025-05-05 16:39:16', 10),
(10, 'image/6818c3e5f3064.jpg', '', '2025-05-05 16:57:57', 10),
(11, 'image/681b1b6b70ae9.jpg', '', '2025-05-07 11:35:55', 11),
(12, 'image/6824558d26109.jpg', '', '2025-05-14 11:34:21', 14);

-- --------------------------------------------------------

--
-- Структура таблицы `media_files_backup`
--

DROP TABLE IF EXISTS `media_files_backup`;
CREATE TABLE `media_files_backup` (
  `id` bigint(20) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `article_id` bigint(20) DEFAULT NULL,
  `kid_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `parents`
--

DROP TABLE IF EXISTS `parents`;
CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `social_category` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `parents`
--

INSERT INTO `parents` (`id`, `full_name`, `phone`, `email`, `address`, `social_category`) VALUES
(1, 'Петрова Ольга Николаевна', '+7-900-123-45-67', 'petrova_parent@mail.ru', NULL, NULL),
(2, 'Алексеева Ольга Дмитриевна', '+7-900-111-11-01', 'olga.alekseeva@mail.ru', 'sdfgsdhhjfgdhjndfghfgh', 'Чернобыль'),
(3, 'Антонова Елена Викторовна', '+7-900-111-11-02', 'elena.antonova@mail.ru', NULL, NULL),
(4, 'Белова Наталья Петровна', '+7-900-111-11-03', 'natalia.belova@mail.ru', NULL, NULL),
(5, 'Богданова Ирина Александровна', '+7-900-111-11-04', 'irina.bogdanova@mail.ru', NULL, NULL),
(6, 'Васильева Марина Сергеевна', '+7-900-111-11-05', 'marina.vasilieva@mail.ru', NULL, NULL),
(7, 'Григорьева Татьяна Андреевна', '+7-900-111-11-06', 'tatiana.grigorieva@mail.ru', NULL, NULL),
(8, 'Денисова Светлана Николаевна', '+7-900-111-11-07', 'svetlana.denisova@mail.ru', NULL, NULL),
(9, 'Егорова Алёна Геннадьевна', '+7-900-111-11-08', 'alena.egorova@mail.ru', NULL, NULL),
(10, 'Жукова Лариса Валерьевна', '+7-900-111-11-09', 'larisa.zhukova@mail.ru', NULL, NULL),
(11, 'Захарова Оксана Константиновна', '+7-900-111-11-10', 'oksana.zaharova@mail.ru', NULL, NULL),
(12, 'Ильина Екатерина Владимировна', '+7-900-111-11-11', 'ekaterina.ilina@mail.ru', NULL, NULL),
(13, 'Карпова Людмила Борисовна', '+7-900-111-11-12', 'lyudmila.karpova@mail.ru', NULL, NULL),
(14, 'Лебедева Елена Анатольевна', '+7-900-111-11-13', 'elena.lebedeva@mail.ru', NULL, NULL),
(15, 'Мельникова Наталия Юрьевна', '+7-900-111-11-14', 'natalia.melnikova@mail.ru', NULL, NULL),
(16, 'Никифорова Ольга Сергеевна', '+7-900-111-11-15', 'olga.nikiforova@mail.ru', NULL, NULL),
(17, 'Иванов Иван Игоревич', '+79493521542', 'test@index.com', 'ул. Пушкина д. 15', 'СВО'),
(18, 'Иванова Ирина Романова', '+78523691542', 'testtest@index.com', 'ул. Пушкина д. 15', 'Многодетная'),
(20, 'Петров Григорий Викторович', '+7156514123', 'jhsfdjiohhjkfsgd@mail.com', 'fhndgfdhgnjfdnjhg', 'СВО'),
(21, 'Шаманов Тимур Сасалович', '+712345456825', 'gdfgdfgdfgd@mail.com', 'fhndgfdhgnjfdnjhg', ''),
(22, 'Петрова Катерина Ивановна', '+7156514123', 'gdfgdfgdfgd@mail.com', 'jhfgdjhfdjfhfjdghfgjh', ''),
(23, 'Ильина Екатерина Николаевна', '+79493521542', '345354354345@mail.com', 'sdfgsdhhjfgdhjndfghfgh', '');

-- --------------------------------------------------------

--
-- Структура таблицы `parent_kid`
--

DROP TABLE IF EXISTS `parent_kid`;
CREATE TABLE `parent_kid` (
  `parent_id` int(11) NOT NULL,
  `kid_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `parent_kid`
--

INSERT INTO `parent_kid` (`parent_id`, `kid_id`) VALUES
(23, 1),
(22, 2),
(2, 4),
(3, 5),
(4, 6),
(5, 7),
(6, 8),
(7, 9),
(8, 10),
(9, 11),
(10, 12),
(11, 13),
(12, 14),
(13, 15),
(14, 16),
(15, 17),
(16, 18),
(17, 19),
(20, 21);

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Администратор сайта'),
(2, 'teacher', 'Воспитатель детского сада'),
(3, 'parent', 'Родитель ребёнка в детском саду');

-- --------------------------------------------------------

--
-- Структура таблицы `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `position` varchar(80) NOT NULL,
  `hire_date` date NOT NULL,
  `fire_date` date DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `staff`
--

INSERT INTO `staff` (`id`, `full_name`, `position`, `hire_date`, `fire_date`, `photo_url`) VALUES
(1, 'Оберемок Оксана Геннадиевна', 'Воспитатель', '2024-01-15', NULL, 'image/OOG.jpg'),
(2, 'Мандра Ольга Николаевна', 'Заведующий', '2023-05-20', NULL, 'image/zaved_photo.jpg'),
(4, 'Сталин', 'Воспитатель', '2025-04-29', NULL, 'image/stalin.jpg'),
(5, 'Вергилийq', 'Мотивационный инструктор', '2025-05-14', NULL, 'image/6814f7dd42968.jpg'),
(6, 'Катерина Ивановна', 'Воспитатель', '2025-01-25', NULL, 'image/kirill.jpg'),
(7, 'Арараги Каёми', 'Воспитатель', '2025-05-01', NULL, 'image/araragi.jpg'),
(9, 'Хосими Мияби', 'Воспитатель', '2002-03-20', NULL, 'image/681369a681812.jpg'),
(10, 'Стальной страж', 'Охранник', '2541-12-21', NULL, 'image/6817362323cf3.jpg');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `pass_hash` char(60) NOT NULL,
  `role_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `pass_hash`, `role_id`, `staff_id`, `parent_id`, `is_active`) VALUES
(4, 'admin', '$2y$10$p38ZIGL.u8ac.YdlX0uYzO.uIaFgqcdZqIn1pRFqFMrF7mNjVsaDy', 1, NULL, NULL, 1),
(7, 'araara', '$2y$10$nfSMY0cDRCUkPkD0YcvA2.fo9QKfYegZqjoZZI.sXD2UpZ7/60Hl6', 2, 7, NULL, 1),
(8, 'bog', '$2y$10$a3ckEBkCMH97JjuCx.mMIeWAmQ2WjUVKNTMWb1.lyt4RpYYb2h/ki', 3, NULL, 5, 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_articles_staff` (`staff_id`);

--
-- Индексы таблицы `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_att_once` (`kid_id`,`att_date`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `group_kid_history`
--
ALTER TABLE `group_kid_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gkh_kid` (`kid_id`),
  ADD KEY `fk_gkh_group` (`group_id`);

--
-- Индексы таблицы `group_staff`
--
ALTER TABLE `group_staff`
  ADD PRIMARY KEY (`group_id`,`staff_id`),
  ADD KEY `fk_gs_staff` (`staff_id`);

--
-- Индексы таблицы `kids`
--
ALTER TABLE `kids`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_media_article` (`article_id`);

--
-- Индексы таблицы `media_files_backup`
--
ALTER TABLE `media_files_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_media_article` (`article_id`),
  ADD KEY `fk_media_kid` (`kid_id`),
  ADD KEY `fk_media_group` (`group_id`);

--
-- Индексы таблицы `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `parent_kid`
--
ALTER TABLE `parent_kid`
  ADD PRIMARY KEY (`parent_id`,`kid_id`),
  ADD KEY `fk_pk_kid` (`kid_id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_role` (`role_id`),
  ADD KEY `fk_users_staff` (`staff_id`),
  ADD KEY `fk_users_parent` (`parent_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `articles`
--
ALTER TABLE `articles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `group_kid_history`
--
ALTER TABLE `group_kid_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `kids`
--
ALTER TABLE `kids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `media_files_backup`
--
ALTER TABLE `media_files_backup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `fk_articles_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_kid` FOREIGN KEY (`kid_id`) REFERENCES `kids` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `group_kid_history`
--
ALTER TABLE `group_kid_history`
  ADD CONSTRAINT `fk_gkh_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gkh_kid` FOREIGN KEY (`kid_id`) REFERENCES `kids` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `group_staff`
--
ALTER TABLE `group_staff`
  ADD CONSTRAINT `fk_gs_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gs_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `media_files`
--
ALTER TABLE `media_files`
  ADD CONSTRAINT `fk_media_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `parent_kid`
--
ALTER TABLE `parent_kid`
  ADD CONSTRAINT `fk_pk_kid` FOREIGN KEY (`kid_id`) REFERENCES `kids` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pk_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
