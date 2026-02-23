-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost:3306
-- Vytvořeno: Pon 23. úno 2026, 17:49
-- Verze serveru: 5.7.24
-- Verze PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `blog_pro`
--

DELIMITER $$
--
-- Procedury
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_old_logs` ()   BEGIN
    DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_old_sessions` ()   BEGIN
    DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktura tabulky `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Vypisuji data pro tabulku `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `entity_name`, `details`, `ip_address`, `created_at`) VALUES
(1, 3, 'create', 'post', 44, 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:09:22'),
(2, 3, 'update', 'post', 44, 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:09:24'),
(3, 3, 'delete', 'post', 39, 'Lol', NULL, '::1', '2026-01-28 22:09:27'),
(4, 6, 'delete', 'post', 40, '654654', NULL, '::1', '2026-01-28 22:11:18'),
(5, 3, 'delete', 'post', 44, 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:29:21'),
(6, 3, 'delete', 'post', 43, 'hfhfghfgh', NULL, '::1', '2026-01-28 22:29:21'),
(7, 3, 'delete', 'post', 42, 'fdsfdsfs', NULL, '::1', '2026-01-28 22:29:21'),
(8, 3, 'delete', 'post', 41, 'kjbhkjb', NULL, '::1', '2026-01-28 22:29:21'),
(9, 3, 'delete', 'post', 37, 'AHoj', NULL, '::1', '2026-01-28 22:29:21'),
(10, 3, 'delete', 'post', 35, '2030', NULL, '::1', '2026-01-28 22:29:21'),
(11, 3, 'delete', 'post', 33, 'mmmmm', NULL, '::1', '2026-01-28 22:29:21'),
(12, 3, 'delete', 'post', 31, 'trjhjjghf', NULL, '::1', '2026-01-28 22:29:21'),
(13, 3, 'delete', 'post', 28, '200000', NULL, '::1', '2026-01-28 22:29:21'),
(14, 3, 'create', 'post', 45, 'dsadasd', NULL, '::1', '2026-01-28 22:29:25'),
(15, 3, 'update', 'post', 45, 'dsadasd', NULL, '::1', '2026-01-28 22:29:30'),
(16, 3, 'delete', 'post', 45, 'dsadasd', NULL, '::1', '2026-01-28 22:33:28'),
(17, 3, 'create', 'post', 40, '54164', NULL, '::1', '2026-02-04 21:00:41'),
(18, 3, 'update', 'post', 40, '54164', NULL, '::1', '2026-02-04 21:00:46'),
(19, 3, 'delete', 'post', 40, '54164', NULL, '::1', '2026-02-04 21:10:35'),
(20, 3, 'delete', 'post', 39, 'Lol', NULL, '::1', '2026-02-10 20:04:12'),
(21, 3, 'delete', 'post', 37, 'AHoj', NULL, '::1', '2026-02-10 20:04:12'),
(22, 3, 'delete', 'post', 35, '2030', NULL, '::1', '2026-02-10 20:04:12'),
(23, 3, 'delete', 'post', 33, 'mmmmm', NULL, '::1', '2026-02-10 20:04:12'),
(24, 3, 'delete', 'post', 31, 'trjhjjghf', NULL, '::1', '2026-02-10 20:04:12'),
(25, 3, 'delete', 'post', 28, '200000', NULL, '::1', '2026-02-10 20:04:12'),
(26, 3, 'logout', 'user', 3, 'admin', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 20:28:36'),
(27, 3, 'create', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:34'),
(28, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:37'),
(29, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:41'),
(30, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:45'),
(31, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:47'),
(32, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:51'),
(33, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:54'),
(34, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:56'),
(35, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:30:58'),
(36, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:01'),
(37, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:05'),
(38, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:09'),
(39, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:11'),
(40, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:22'),
(41, 3, 'update', 'post', 41, 'dfsdfsdfsdgsdfg dfgsd', NULL, '::1', '2026-02-10 20:31:24'),
(42, 3, 'logout', 'user', 3, 'admin', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 20:51:28'),
(43, 3, 'logout', 'user', 3, 'admin', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 20:58:51'),
(44, 3, 'logout', 'user', 3, 'admin', '\"U\\u017eivatel se odhl\\u00e1sil\"', '::1', '2026-02-10 20:59:31'),
(45, 3, 'logout', 'user', 3, 'admin', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 21:07:08'),
(46, 3, 'logout', 'user', 3, 'admin', '\"U\\u017eivatel se odhl\\u00e1sil\"', '::1', '2026-02-10 21:10:37'),
(47, 3, 'logout', 'user', 3, 'admin', '\"U\\u017eivatel se odhl\\u00e1sil\"', '::1', '2026-02-10 21:17:45'),
(48, 3, 'logout', 'user', 3, 'admin', '\"U\\u017eivatel se odhl\\u00e1sil\"', '::1', '2026-02-10 21:18:24'),
(49, 6, 'logout', 'user', 6, 'filip', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 21:26:16'),
(50, 3, 'logout', 'user', 3, 'admin', '\"Automatick\\u00e9 odhl\\u00e1\\u0161en\\u00ed (timeout)\"', '::1', '2026-02-10 21:32:50'),
(51, 3, 'logout', 'user', 3, 'admin', '\"U\\u017eivatel se odhl\\u00e1sil\"', '::1', '2026-02-10 21:34:03');

-- --------------------------------------------------------

--
-- Struktura tabulky `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint(20) DEFAULT NULL,
  `type` enum('database','full') COLLATE utf8mb4_unicode_ci DEFAULT 'database',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_restored_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `backups`
--

INSERT INTO `backups` (`id`, `filename`, `filepath`, `size_bytes`, `type`, `created_by`, `created_at`, `last_restored_at`) VALUES
(36, 'full_backup_2026-02-10_20-04-05.zip', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/full_backup_2026-02-10_20-04-05.zip', 3135881, 'full', 3, '2026-02-10 19:04:06', NULL),
(37, 'db_backup_2026-02-10_20-04-07.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-02-10_20-04-07.sql', 50096, 'database', 3, '2026-02-10 19:04:07', NULL),
(39, 'full_backup_2026-02-10_20-04-47.zip', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/full_backup_2026-02-10_20-04-47.zip', 4528, 'full', 3, '2026-02-10 19:04:47', NULL),
(40, 'db_backup_2026-02-10_20-04-51.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-02-10_20-04-51.sql', 46743, 'database', 3, '2026-02-10 19:04:51', NULL),
(41, 'db_backup_2026-02-10_20-15-01.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-02-10_20-15-01.sql', 47332, 'database', NULL, '2026-02-10 19:15:01', NULL),
(42, 'full_backup_2026-02-10_20-15-01.zip', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/full_backup_2026-02-10_20-15-01.zip', 4555, 'full', NULL, '2026-02-10 19:15:01', NULL),
(43, 'db_backup_2026-02-10_20-31-46.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-02-10_20-31-46.sql', 51716, 'database', 3, '2026-02-10 19:31:47', NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `backup_schedule`
--

CREATE TABLE `backup_schedule` (
  `id` int(11) NOT NULL,
  `backup_type` enum('database','full') COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `frequency` enum('daily','weekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT 'daily',
  `time` time DEFAULT '02:00:00',
  `day_of_week` int(11) DEFAULT '1' COMMENT '1=Po, 7=Ne (pro weekly)',
  `day_of_month` int(11) DEFAULT '1' COMMENT '1-28 (pro monthly)',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `backup_schedule`
--

INSERT INTO `backup_schedule` (`id`, `backup_type`, `enabled`, `frequency`, `time`, `day_of_week`, `day_of_month`, `last_run`, `next_run`, `created_at`, `updated_at`) VALUES
(1, 'database', 1, 'daily', '20:05:00', 1, 1, '2026-02-10 20:15:01', NULL, '2026-02-05 18:41:44', '2026-02-10 19:15:01'),
(2, 'full', 1, 'daily', '20:09:00', 7, 1, '2026-02-10 20:15:01', NULL, '2026-02-05 18:41:44', '2026-02-10 19:15:01');

-- --------------------------------------------------------

--
-- Struktura tabulky `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Akce 2025', 'akce-2025', 'Události a akce z roku 2025', '2026-01-22 20:31:36'),
(2, 'Akce 2026', 'akce-2026', 'Události a akce z roku 2026', '2026-01-22 20:31:36'),
(3, 'Novinky', 'novinky', 'Nejnovější zprávy a informace', '2026-01-22 20:31:36'),
(5, 'Zahradní slavnosti', 'zahradni-slavnosti', NULL, '2026-01-23 18:40:36'),
(6, 'Zimní slavnosti', 'zimni-slavnosti', NULL, '2026-01-23 20:36:17');

-- --------------------------------------------------------

--
-- Struktura tabulky `error_logs`
--

CREATE TABLE `error_logs` (
  `id` int(11) NOT NULL,
  `error_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_number` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `error_logs`
--

INSERT INTO `error_logs` (`id`, `error_type`, `error_message`, `file_path`, `line_number`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:04:38'),
(2, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:04:53'),
(3, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:05:01'),
(4, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:19:15'),
(5, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:20:09'),
(6, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-22 21:20:17'),
(7, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 17:44:39'),
(8, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 17:55:21'),
(9, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 17:56:26'),
(10, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 17:58:53'),
(11, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 17:59:56'),
(12, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:00:07'),
(13, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:00:16'),
(14, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:01:04'),
(15, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:01:38'),
(16, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:01:41'),
(17, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:01:46'),
(18, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:01:58'),
(19, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:09:34'),
(20, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:09:39'),
(21, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:09:46'),
(22, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:10:10'),
(23, 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, 1, '::1', '2026-01-23 18:10:18'),
(24, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p class=\\\"ql-align-center\\\">TEST</p><p>dsadsafsdfsdfdsfsdfsdfdsfsdfsdgdfbxcbxc\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(25, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(26, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">fsdfsdfsdf df sdf sd fsdf sdf sd ds<em>f dsf ds</em> <em> d</em></strong><str\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(27, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(28, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><em>s</em></strong><strong><em> fs</em> fsd f</strong><strong style=\\\"color: \' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(29, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(30, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">s<u> ssf s s</u></strong><strong><u>d f sf s</u></strong><strong style=\\\"colo\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(31, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(32, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><u>d fsd fs fsdd</u></strong><strong><u> fds fsdf sd</u></strong></p><p><br><\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(33, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(34, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>f dsf s f fsdf s<span class=\\\"ql-font-serif\\\"> fsdf s fs</span></p><p><br></\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(35, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'10\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(36, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"> khkjhkl jklo ilipiopp uiozu iztbvbbtzh</span>dydrt bzty</p>\', \'f dsf s f fsd\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(37, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'10\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(38, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<h3 class=\\\"ql-align-center\\\">AHOJ</h3><p>gdfgdfggfdgdfgdgfgdgfdgdgfgdfgdfghfgh\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(39, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(40, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIbGNtcwIQAABtbnRyU\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(41, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(42, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>df sdf sd dfgdfhg fghfg hfg hgfh f  df gdfg fdh fgh fgh fgh ghjh fgh fg hf g\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(43, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(44, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">g fdhgf hfg hfg hfg hfg h</span><span style=\\\"background-color: rgb(240, 102,\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(45, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(46, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'color: rgb(230, 0, 0)\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(47, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(48, 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"> hfg gf hg ghjhg j hg j</span></p>\', \'df sdf sd dfgdfhg fghfg hfg hgfh f df g\' at line 1', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(49, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(50, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1217 Cannot delete or update a parent row: a foreign key constraint fails', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(51, 'Query Execute', 'SQLSTATE[42S01]: Base table or view already exists: 1050 Table \'users\' already exists', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(52, 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1\' for key \'PRIMARY\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:47'),
(53, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:48'),
(54, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-25 21:38:53'),
(55, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-25 21:45:31'),
(56, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-25 21:51:08'),
(57, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:25:00'),
(58, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:28:20'),
(59, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:28:20'),
(60, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:32:58'),
(61, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:32:58'),
(62, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:32:59'),
(63, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:32:59'),
(64, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:00'),
(65, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:00'),
(66, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:00'),
(67, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:00'),
(68, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:01'),
(69, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:01'),
(70, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:14'),
(71, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:14'),
(72, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:27'),
(73, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:30'),
(74, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:30'),
(75, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:31'),
(76, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, 1, '::1', '2026-01-26 18:33:31'),
(77, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(78, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(79, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(80, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(81, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(82, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(83, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(84, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(85, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(86, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(87, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(88, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(89, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(90, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(91, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(92, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(93, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(94, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(95, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(96, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(97, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(98, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(99, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:04'),
(100, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(101, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(102, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(103, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(104, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(105, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(106, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(107, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(108, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(109, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(110, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(111, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(112, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(113, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(114, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(115, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(116, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(117, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(118, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(119, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(120, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(121, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(122, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:31:12'),
(123, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(124, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(125, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(126, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(127, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(128, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(129, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(130, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(131, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(132, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(133, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(134, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(135, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(136, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(137, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(138, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(139, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(140, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(141, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(142, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(143, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(144, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(145, 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, 1, '::1', '2026-01-26 19:32:28'),
(146, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 19:44:55'),
(147, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 19:44:55'),
(148, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 19:44:55'),
(149, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 5, '::1', '2026-02-04 19:56:00'),
(150, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 5, '::1', '2026-02-04 19:56:00'),
(151, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 5, '::1', '2026-02-04 19:56:00'),
(152, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:21'),
(153, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:21'),
(154, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:21'),
(155, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:33'),
(156, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:33'),
(157, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:33'),
(158, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:55'),
(159, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:55'),
(160, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:00:55'),
(161, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:01:21'),
(162, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:01:21'),
(163, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:01:21'),
(164, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:05:23'),
(165, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:05:23'),
(166, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:05:23'),
(167, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:10:40'),
(168, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:10:40'),
(169, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:10:40'),
(170, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:11:42'),
(171, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-04 20:11:42'),
(172, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-04 20:11:42'),
(173, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:12:14'),
(174, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-10 19:12:14'),
(175, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:12:14'),
(176, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:22:13'),
(177, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, 3, '::1', '2026-02-10 19:22:13'),
(178, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:22:13'),
(179, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:29:32'),
(180, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:29:47'),
(181, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:30:07'),
(182, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:31:32'),
(183, 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, 3, '::1', '2026-02-10 19:31:51');

-- --------------------------------------------------------

--
-- Struktura tabulky `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','scheduled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `menu_order` int(11) DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `category_id`, `author_id`, `status`, `menu_order`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `scheduled_at`, `created_at`, `updated_at`) VALUES
(41, 'dfsdfsdfsdgsdfg dfgsd', 'dfsdfsdfsdgsdfg-dfgsd', '<p><span class=\"ql-font-times-new-roman\" style=\"color: rgb(178, 178, 0);\">d fsd fsd fds f sdsd f</span></p><p><span class=\"ql-font-verdana\" style=\"color: rgb(194, 133, 255); background-color: rgb(102, 185, 102);\"> fsdf sd</span></p><p><span class=\"ql-font-courier\" style=\"color: rgb(178, 178, 0);\"> fsd </span></p><p><span class=\"ql-font-impact\" style=\"color: rgb(178, 178, 0);\">s d</span></p><p><span class=\"ql-font-georgia\" style=\"color: rgb(230, 0, 0);\">d </span></p><h2><span style=\"color: rgb(255, 194, 102); background-color: rgb(0, 41, 102);\">s dfsd</span><span style=\"background-color: rgb(0, 41, 102);\"> </span></h2>', 'd fsd fsd fds f sdsd f fsdf sd fsd s dd s dfsd', NULL, NULL, 3, 'draft', 0, '', '', '', NULL, NULL, '2026-02-10 20:30:34', '2026-02-10 20:31:24');

-- --------------------------------------------------------

--
-- Struktura tabulky `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_at` datetime DEFAULT NULL,
  `logout_at` datetime DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `last_activity`) VALUES
('test123', 1, '127.0.0.1', 'Manual Test', '2026-02-10 21:12:44', NULL, '2026-02-10 20:12:44');

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('it','admin','editor') COLLATE utf8mb4_unicode_ci DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin1', '$2y$10$qyXTUt7rMvAVIk/YEarZ4eUMrrJbtFh9Yd8wP06utC7Ja1CNwbGBC', 'admin1@blog.cz', 'admin', '2026-01-22 20:31:36', NULL),
(2, 'editor1', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'editor1@blog.cz', 'editor', '2026-01-22 20:31:36', NULL),
(3, 'admin', '$2y$10$QnFE6YiwHJmQKf09m675peDhBDKezlkaVZx28Wpc0UM2TFlMzLOS2', NULL, 'it', '2026-01-25 21:33:08', NULL),
(6, 'filip', '$2y$10$cWLzoBd1rsCZ.sQkRM.gzODnIy3UYItPJW3JGsAeGcK/1UoTywLDC', NULL, 'editor', '2026-02-10 20:18:21', NULL);

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexy pro tabulku `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_backup_created` (`created_at`),
  ADD KEY `idx_backup_type` (`type`);

--
-- Indexy pro tabulku `backup_schedule`
--
ALTER TABLE `backup_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexy pro tabulku `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type` (`error_type`);

--
-- Indexy pro tabulku `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_filename` (`filename`);

--
-- Indexy pro tabulku `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_slug` (`slug`);
ALTER TABLE `posts` ADD FULLTEXT KEY `ft_search` (`title`,`content`,`excerpt`);

--
-- Indexy pro tabulku `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_login_at` (`login_at`),
  ADD KEY `idx_logout_at` (`logout_at`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pro tabulku `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT pro tabulku `backup_schedule`
--
ALTER TABLE `backup_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pro tabulku `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pro tabulku `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT pro tabulku `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
