-- Database Backup
-- Generated: 2026-01-25 22:38:27


--
-- Table: backups
--

DROP TABLE IF EXISTS `backups`;

CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `backups` VALUES ('1', 'db_backup_2026-01-22_22-05-17.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-22_22-05-17.sql', '6237', '1', '2026-01-22 22:05:17');
INSERT INTO `backups` VALUES ('2', 'db_backup_2026-01-22_22-22-16.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-22_22-22-16.sql', '7077', '1', '2026-01-22 22:22:16');
INSERT INTO `backups` VALUES ('3', 'db_backup_2026-01-23_22-02-15.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-23_22-02-15.sql', '576688', '1', '2026-01-23 22:02:15');
INSERT INTO `backups` VALUES ('4', 'db_backup_2026-01-24_16-38-06.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-24_16-38-06.sql', '657051', '1', '2026-01-24 16:38:06');


--
-- Table: categories
--

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` VALUES ('1', 'Akce 2025', 'akce-2025', 'Události a akce z roku 2025', '2026-01-22 21:31:36');
INSERT INTO `categories` VALUES ('2', 'Akce 2026', 'akce-2026', 'Události a akce z roku 2026', '2026-01-22 21:31:36');
INSERT INTO `categories` VALUES ('5', 'Zahradní slavnosti', 'zahradni-slavnosti', NULL, '2026-01-23 19:40:36');


--
-- Table: error_logs
--

DROP TABLE IF EXISTS `error_logs`;

CREATE TABLE `error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_number` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_type` (`error_type`)
) ENGINE=InnoDB AUTO_INCREMENT=322 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `error_logs` VALUES ('1', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:04:38');
INSERT INTO `error_logs` VALUES ('2', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:04:53');
INSERT INTO `error_logs` VALUES ('3', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:05:01');
INSERT INTO `error_logs` VALUES ('4', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:19:15');
INSERT INTO `error_logs` VALUES ('5', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:20:09');
INSERT INTO `error_logs` VALUES ('6', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-22 22:20:17');
INSERT INTO `error_logs` VALUES ('7', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 18:44:39');
INSERT INTO `error_logs` VALUES ('8', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 18:55:21');
INSERT INTO `error_logs` VALUES ('9', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 18:56:26');
INSERT INTO `error_logs` VALUES ('10', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 18:58:53');
INSERT INTO `error_logs` VALUES ('11', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 18:59:56');
INSERT INTO `error_logs` VALUES ('12', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:00:07');
INSERT INTO `error_logs` VALUES ('13', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:00:16');
INSERT INTO `error_logs` VALUES ('14', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:01:04');
INSERT INTO `error_logs` VALUES ('15', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:01:38');
INSERT INTO `error_logs` VALUES ('16', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:01:41');
INSERT INTO `error_logs` VALUES ('17', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:01:46');
INSERT INTO `error_logs` VALUES ('18', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:01:58');
INSERT INTO `error_logs` VALUES ('19', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:09:34');
INSERT INTO `error_logs` VALUES ('20', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:09:39');
INSERT INTO `error_logs` VALUES ('21', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:09:46');
INSERT INTO `error_logs` VALUES ('22', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:10:10');
INSERT INTO `error_logs` VALUES ('23', 'Query Execute', 'SQLSTATE[HY000]: General error: 1366 Incorrect integer value: \'\' for column \'category_id\' at row 1', NULL, NULL, '1', '::1', '2026-01-23 19:10:18');
INSERT INTO `error_logs` VALUES ('24', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:16:35');
INSERT INTO `error_logs` VALUES ('25', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:16');
INSERT INTO `error_logs` VALUES ('26', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:28');
INSERT INTO `error_logs` VALUES ('27', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:33');
INSERT INTO `error_logs` VALUES ('28', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:36');
INSERT INTO `error_logs` VALUES ('29', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:38');
INSERT INTO `error_logs` VALUES ('30', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:17:40');
INSERT INTO `error_logs` VALUES ('31', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:28');
INSERT INTO `error_logs` VALUES ('32', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:28');
INSERT INTO `error_logs` VALUES ('33', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:30');
INSERT INTO `error_logs` VALUES ('34', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:30');
INSERT INTO `error_logs` VALUES ('35', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:32');
INSERT INTO `error_logs` VALUES ('36', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:32');
INSERT INTO `error_logs` VALUES ('37', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:38');
INSERT INTO `error_logs` VALUES ('38', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:30:38');
INSERT INTO `error_logs` VALUES ('39', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:31:26');
INSERT INTO `error_logs` VALUES ('40', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 10:31:26');
INSERT INTO `error_logs` VALUES ('41', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 11:25:16');
INSERT INTO `error_logs` VALUES ('42', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 11:25:33');
INSERT INTO `error_logs` VALUES ('43', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:51');
INSERT INTO `error_logs` VALUES ('44', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:51');
INSERT INTO `error_logs` VALUES ('45', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:53');
INSERT INTO `error_logs` VALUES ('46', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:57');
INSERT INTO `error_logs` VALUES ('47', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:57');
INSERT INTO `error_logs` VALUES ('48', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 12:22:57');
INSERT INTO `error_logs` VALUES ('49', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:29');
INSERT INTO `error_logs` VALUES ('50', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:29');
INSERT INTO `error_logs` VALUES ('51', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:29');
INSERT INTO `error_logs` VALUES ('52', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:29');
INSERT INTO `error_logs` VALUES ('53', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:31');
INSERT INTO `error_logs` VALUES ('54', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:31');
INSERT INTO `error_logs` VALUES ('55', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:36');
INSERT INTO `error_logs` VALUES ('56', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:36');
INSERT INTO `error_logs` VALUES ('57', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:36');
INSERT INTO `error_logs` VALUES ('58', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:36');
INSERT INTO `error_logs` VALUES ('59', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:39');
INSERT INTO `error_logs` VALUES ('60', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:14:39');
INSERT INTO `error_logs` VALUES ('61', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 14:18:49');
INSERT INTO `error_logs` VALUES ('62', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 14:23:42');
INSERT INTO `error_logs` VALUES ('63', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 14:24:44');
INSERT INTO `error_logs` VALUES ('64', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:40');
INSERT INTO `error_logs` VALUES ('65', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:40');
INSERT INTO `error_logs` VALUES ('66', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:40');
INSERT INTO `error_logs` VALUES ('67', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:41');
INSERT INTO `error_logs` VALUES ('68', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:42');
INSERT INTO `error_logs` VALUES ('69', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:42');
INSERT INTO `error_logs` VALUES ('70', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:43');
INSERT INTO `error_logs` VALUES ('71', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:44');
INSERT INTO `error_logs` VALUES ('72', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:44');
INSERT INTO `error_logs` VALUES ('73', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:27:44');
INSERT INTO `error_logs` VALUES ('74', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:33:20');
INSERT INTO `error_logs` VALUES ('75', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:33:20');
INSERT INTO `error_logs` VALUES ('76', 'Query Execute', 'SQLSTATE[01000]: Warning: 1265 Data truncated for column \'status\' at row 1', NULL, NULL, '1', '::1', '2026-01-24 14:33:41');
INSERT INTO `error_logs` VALUES ('77', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:35:37');
INSERT INTO `error_logs` VALUES ('78', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:35:37');
INSERT INTO `error_logs` VALUES ('79', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:35:41');
INSERT INTO `error_logs` VALUES ('80', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:35:41');
INSERT INTO `error_logs` VALUES ('81', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:43:53');
INSERT INTO `error_logs` VALUES ('82', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:43:53');
INSERT INTO `error_logs` VALUES ('83', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:52:54');
INSERT INTO `error_logs` VALUES ('84', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 14:52:54');
INSERT INTO `error_logs` VALUES ('85', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:02:07');
INSERT INTO `error_logs` VALUES ('86', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:02:07');
INSERT INTO `error_logs` VALUES ('87', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:02:10');
INSERT INTO `error_logs` VALUES ('88', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:02:10');
INSERT INTO `error_logs` VALUES ('89', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:45');
INSERT INTO `error_logs` VALUES ('90', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:45');
INSERT INTO `error_logs` VALUES ('91', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:46');
INSERT INTO `error_logs` VALUES ('92', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:46');
INSERT INTO `error_logs` VALUES ('93', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:46');
INSERT INTO `error_logs` VALUES ('94', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:46');
INSERT INTO `error_logs` VALUES ('95', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:47');
INSERT INTO `error_logs` VALUES ('96', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:47');
INSERT INTO `error_logs` VALUES ('97', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:48');
INSERT INTO `error_logs` VALUES ('98', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:48');
INSERT INTO `error_logs` VALUES ('99', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:49');
INSERT INTO `error_logs` VALUES ('100', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:06:49');
INSERT INTO `error_logs` VALUES ('101', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:09');
INSERT INTO `error_logs` VALUES ('102', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:09');
INSERT INTO `error_logs` VALUES ('103', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('104', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('105', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('106', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('107', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('108', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:12');
INSERT INTO `error_logs` VALUES ('109', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:13');
INSERT INTO `error_logs` VALUES ('110', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:13');
INSERT INTO `error_logs` VALUES ('111', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:13');
INSERT INTO `error_logs` VALUES ('112', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:13');
INSERT INTO `error_logs` VALUES ('113', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:48');
INSERT INTO `error_logs` VALUES ('114', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:07:48');
INSERT INTO `error_logs` VALUES ('115', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:30');
INSERT INTO `error_logs` VALUES ('116', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:30');
INSERT INTO `error_logs` VALUES ('117', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:31');
INSERT INTO `error_logs` VALUES ('118', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:31');
INSERT INTO `error_logs` VALUES ('119', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:33');
INSERT INTO `error_logs` VALUES ('120', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:33');
INSERT INTO `error_logs` VALUES ('121', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:34');
INSERT INTO `error_logs` VALUES ('122', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:34');
INSERT INTO `error_logs` VALUES ('123', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:39');
INSERT INTO `error_logs` VALUES ('124', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:39');
INSERT INTO `error_logs` VALUES ('125', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:40');
INSERT INTO `error_logs` VALUES ('126', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:40');
INSERT INTO `error_logs` VALUES ('127', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:41');
INSERT INTO `error_logs` VALUES ('128', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:41');
INSERT INTO `error_logs` VALUES ('129', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('130', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('131', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('132', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('133', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('134', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('135', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('136', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:57');
INSERT INTO `error_logs` VALUES ('137', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:58');
INSERT INTO `error_logs` VALUES ('138', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:58');
INSERT INTO `error_logs` VALUES ('139', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:58');
INSERT INTO `error_logs` VALUES ('140', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:10:58');
INSERT INTO `error_logs` VALUES ('141', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('142', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('143', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('144', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('145', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('146', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('147', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('148', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:12:57');
INSERT INTO `error_logs` VALUES ('149', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:18');
INSERT INTO `error_logs` VALUES ('150', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:18');
INSERT INTO `error_logs` VALUES ('151', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:19');
INSERT INTO `error_logs` VALUES ('152', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:19');
INSERT INTO `error_logs` VALUES ('153', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:20');
INSERT INTO `error_logs` VALUES ('154', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:20');
INSERT INTO `error_logs` VALUES ('155', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:20');
INSERT INTO `error_logs` VALUES ('156', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:20');
INSERT INTO `error_logs` VALUES ('157', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:21');
INSERT INTO `error_logs` VALUES ('158', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:21');
INSERT INTO `error_logs` VALUES ('159', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:22');
INSERT INTO `error_logs` VALUES ('160', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:22');
INSERT INTO `error_logs` VALUES ('161', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:22');
INSERT INTO `error_logs` VALUES ('162', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:22');
INSERT INTO `error_logs` VALUES ('163', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:25');
INSERT INTO `error_logs` VALUES ('164', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:25');
INSERT INTO `error_logs` VALUES ('165', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:25');
INSERT INTO `error_logs` VALUES ('166', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:25');
INSERT INTO `error_logs` VALUES ('167', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:26');
INSERT INTO `error_logs` VALUES ('168', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:26');
INSERT INTO `error_logs` VALUES ('169', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:26');
INSERT INTO `error_logs` VALUES ('170', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:14:26');
INSERT INTO `error_logs` VALUES ('171', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:40');
INSERT INTO `error_logs` VALUES ('172', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:40');
INSERT INTO `error_logs` VALUES ('173', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:40');
INSERT INTO `error_logs` VALUES ('174', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:40');
INSERT INTO `error_logs` VALUES ('175', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:42');
INSERT INTO `error_logs` VALUES ('176', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:42');
INSERT INTO `error_logs` VALUES ('177', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:43');
INSERT INTO `error_logs` VALUES ('178', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:43');
INSERT INTO `error_logs` VALUES ('179', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:43');
INSERT INTO `error_logs` VALUES ('180', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:43');
INSERT INTO `error_logs` VALUES ('181', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:58');
INSERT INTO `error_logs` VALUES ('182', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:58');
INSERT INTO `error_logs` VALUES ('183', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:58');
INSERT INTO `error_logs` VALUES ('184', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:20:58');
INSERT INTO `error_logs` VALUES ('185', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:28');
INSERT INTO `error_logs` VALUES ('186', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:28');
INSERT INTO `error_logs` VALUES ('187', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:28');
INSERT INTO `error_logs` VALUES ('188', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:28');
INSERT INTO `error_logs` VALUES ('189', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:29');
INSERT INTO `error_logs` VALUES ('190', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:29');
INSERT INTO `error_logs` VALUES ('191', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:30');
INSERT INTO `error_logs` VALUES ('192', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:23:30');
INSERT INTO `error_logs` VALUES ('193', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:26:42');
INSERT INTO `error_logs` VALUES ('194', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:26:42');
INSERT INTO `error_logs` VALUES ('195', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:26:42');
INSERT INTO `error_logs` VALUES ('196', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:26:42');
INSERT INTO `error_logs` VALUES ('197', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:15');
INSERT INTO `error_logs` VALUES ('198', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:15');
INSERT INTO `error_logs` VALUES ('199', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:16');
INSERT INTO `error_logs` VALUES ('200', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:16');
INSERT INTO `error_logs` VALUES ('201', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:49');
INSERT INTO `error_logs` VALUES ('202', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:49');
INSERT INTO `error_logs` VALUES ('203', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:49');
INSERT INTO `error_logs` VALUES ('204', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:38:49');
INSERT INTO `error_logs` VALUES ('205', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:08');
INSERT INTO `error_logs` VALUES ('206', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:08');
INSERT INTO `error_logs` VALUES ('207', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:09');
INSERT INTO `error_logs` VALUES ('208', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:09');
INSERT INTO `error_logs` VALUES ('209', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:10');
INSERT INTO `error_logs` VALUES ('210', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:10');
INSERT INTO `error_logs` VALUES ('211', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:10');
INSERT INTO `error_logs` VALUES ('212', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 15:39:10');
INSERT INTO `error_logs` VALUES ('213', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 16:57:29');
INSERT INTO `error_logs` VALUES ('214', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 16:57:29');
INSERT INTO `error_logs` VALUES ('215', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:56');
INSERT INTO `error_logs` VALUES ('216', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:56');
INSERT INTO `error_logs` VALUES ('217', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:57');
INSERT INTO `error_logs` VALUES ('218', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:57');
INSERT INTO `error_logs` VALUES ('219', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:58');
INSERT INTO `error_logs` VALUES ('220', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:58');
INSERT INTO `error_logs` VALUES ('221', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:58');
INSERT INTO `error_logs` VALUES ('222', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:12:58');
INSERT INTO `error_logs` VALUES ('223', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:13:00');
INSERT INTO `error_logs` VALUES ('224', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:13:00');
INSERT INTO `error_logs` VALUES ('225', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:13:04');
INSERT INTO `error_logs` VALUES ('226', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:13:04');
INSERT INTO `error_logs` VALUES ('227', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:24');
INSERT INTO `error_logs` VALUES ('228', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:24');
INSERT INTO `error_logs` VALUES ('229', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:26');
INSERT INTO `error_logs` VALUES ('230', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:26');
INSERT INTO `error_logs` VALUES ('231', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:27');
INSERT INTO `error_logs` VALUES ('232', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:27');
INSERT INTO `error_logs` VALUES ('233', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:27');
INSERT INTO `error_logs` VALUES ('234', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:27');
INSERT INTO `error_logs` VALUES ('235', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:28');
INSERT INTO `error_logs` VALUES ('236', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:28');
INSERT INTO `error_logs` VALUES ('237', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('238', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('239', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('240', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('241', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('242', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('243', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('244', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('245', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('246', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:30');
INSERT INTO `error_logs` VALUES ('247', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('248', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('249', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('250', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('251', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('252', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('253', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('254', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:31');
INSERT INTO `error_logs` VALUES ('255', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:33');
INSERT INTO `error_logs` VALUES ('256', 'Query Execute', 'SQLSTATE[HY093]: Invalid parameter number', NULL, NULL, '1', '::1', '2026-01-24 19:14:33');
INSERT INTO `error_logs` VALUES ('257', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:36:21');
INSERT INTO `error_logs` VALUES ('258', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:36:24');
INSERT INTO `error_logs` VALUES ('259', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:36:58');
INSERT INTO `error_logs` VALUES ('260', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:36:58');
INSERT INTO `error_logs` VALUES ('261', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:37:03');
INSERT INTO `error_logs` VALUES ('262', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:37:03');
INSERT INTO `error_logs` VALUES ('263', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:37:17');
INSERT INTO `error_logs` VALUES ('264', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:31');
INSERT INTO `error_logs` VALUES ('265', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:35');
INSERT INTO `error_logs` VALUES ('266', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:37');
INSERT INTO `error_logs` VALUES ('267', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:42');
INSERT INTO `error_logs` VALUES ('268', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:47');
INSERT INTO `error_logs` VALUES ('269', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:50');
INSERT INTO `error_logs` VALUES ('270', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:53');
INSERT INTO `error_logs` VALUES ('271', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:38:56');
INSERT INTO `error_logs` VALUES ('272', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'title\' cannot be null', NULL, NULL, '1', '::1', '2026-01-25 14:40:43');
INSERT INTO `error_logs` VALUES ('273', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:41:22');
INSERT INTO `error_logs` VALUES ('274', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:41:39');
INSERT INTO `error_logs` VALUES ('275', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:41:43');
INSERT INTO `error_logs` VALUES ('276', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:41:48');
INSERT INTO `error_logs` VALUES ('277', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:08');
INSERT INTO `error_logs` VALUES ('278', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:11');
INSERT INTO `error_logs` VALUES ('279', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:21');
INSERT INTO `error_logs` VALUES ('280', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:34');
INSERT INTO `error_logs` VALUES ('281', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:39');
INSERT INTO `error_logs` VALUES ('282', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'menu_order\' in \'field list\'', NULL, NULL, '1', '::1', '2026-01-25 14:46:53');
INSERT INTO `error_logs` VALUES ('283', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p class=\\\"ql-align-center\\\">TEST</p><p>dsadsafsdfsdfdsfsdfsdfdsfsdfsdgdfbxcbxc\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('284', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('285', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">fsdfsdfsdf df sdf sd fsdf sdf sd ds<em>f dsf ds</em> <em> d</em></strong><str\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('286', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('287', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><em>s</em></strong><strong><em> fs</em> fsd f</strong><strong style=\\\"color: \' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('288', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('289', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">s<u> ssf s s</u></strong><strong><u>d f sf s</u></strong><strong style=\\\"colo\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('290', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('291', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><u>d fsd fs fsdd</u></strong><strong><u> fds fsdf sd</u></strong></p><p><br><\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('292', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('293', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<h3 class=\\\"ql-align-center\\\">AHOJ</h3><p>gdfgdfggfdgdfgdgfgdgfdgdgfgdfgdfghfgh\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('294', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('295', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIbGNtcwIQAABtbnRyU\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('296', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('297', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>df sdf sd dfgdfhg fghfg hfg hgfh f  df gdfg fdh fgh fgh fgh ghjh fgh fg hf g\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('298', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'34\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('299', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">g fdhgf hfg hfg hfg hfg h</span><span style=\\\"background-color: rgb(240, 102,\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('300', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'34\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('301', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'color: rgb(230, 0, 0)\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('302', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'34\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('303', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"> hfg gf hg ghjhg j hg j</span></p>\', \'df sdf sd dfgdfhg fghfg hfg hgfh f df g\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('304', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'34\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('305', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>Ve středu 26. listopadu 2025 se studenti druhého ročníku zúčastnili ex\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('306', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'42\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('307', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'</p><p>Následovala prohlídka čtyř moderních laboratoří. Studenti viděli \' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('308', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'42\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('309', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'</p><p>Exkurze studentům přiblížila, jak vypadá moderní technické zázem\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('310', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'42\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('311', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'</p><p>Ing. Filip Šenkeřík</p>\', \'Ve středu 26. listopadu 2025 se studenti d\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('312', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'42\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('313', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>jhgjghkjhkhjkh</p><p class=\\\"ql-align-center\\\"><img src=\\\"data:image/png\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('314', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'50\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('315', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'base64,iVBORw0KGgoAAAANSUhEUgAAAMIAAAAqCAYAAADh9oTeAAAAAXNSR0IArs4c6QAAAAlwSFlzA\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('316', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'50\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:02');
INSERT INTO `error_logs` VALUES ('317', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1217 Cannot delete or update a parent row: a foreign key constraint fails', NULL, NULL, '1', '::1', '2026-01-25 22:38:03');
INSERT INTO `error_logs` VALUES ('318', 'Query Execute', 'SQLSTATE[42S01]: Base table or view already exists: 1050 Table \'users\' already exists', NULL, NULL, '1', '::1', '2026-01-25 22:38:03');
INSERT INTO `error_logs` VALUES ('319', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:03');
INSERT INTO `error_logs` VALUES ('320', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'3\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:03');
INSERT INTO `error_logs` VALUES ('321', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'4\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:03');


--
-- Table: media
--

DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `media` VALUES ('1', '69748de63544c_1769246182.jpg', '20251126_085508.jpg', 'uploads/69748de63544c_1769246182.jpg', 'image/jpeg', '279015', '1200', '676', '2026-01-24 10:16:22');
INSERT INTO `media` VALUES ('2', '69748e014acea_1769246209.jpg', '499481417_732278115996170_2818724089644817126_n.jpg', 'uploads/69748e014acea_1769246209.jpg', 'image/jpeg', '85386', '1080', '766', '2026-01-24 10:16:49');
INSERT INTO `media` VALUES ('3', '69748e016d51c_1769246209.jpg', '499681515_732278009329514_1034088013822926955_n.jpg', 'uploads/69748e016d51c_1769246209.jpg', 'image/jpeg', '56785', '932', '766', '2026-01-24 10:16:49');
INSERT INTO `media` VALUES ('5', '69748e10f07dc_1769246224.png', 'Čed xdd.png', 'uploads/69748e10f07dc_1769246224.png', 'image/png', '22894', '767', '304', '2026-01-24 10:17:05');
INSERT INTO `media` VALUES ('6', '69748e16e4b51_1769246230.jpg', 'Report srbsko.jpg', 'uploads/69748e16e4b51_1769246230.jpg', 'image/jpeg', '159623', '1024', '1536', '2026-01-24 10:17:11');
INSERT INTO `media` VALUES ('7', '69748e3e8fc82_1769246270.jpg', '516733149_1206676031472429_6173501354438327263_n.jpg', 'uploads/69748e3e8fc82_1769246270.jpg', 'image/jpeg', '130670', '1536', '1920', '2026-01-24 10:17:51');
INSERT INTO `media` VALUES ('8', '69749198cb501_1769247128.png', 'Čed xdd.png', 'uploads/69749198cb501_1769247128.png', 'image/png', '22894', '767', '304', '2026-01-24 10:32:08');
INSERT INTO `media` VALUES ('9', '697492027685e_1769247234.jpg', '02.jpg', 'uploads/697492027685e_1769247234.jpg', 'image/jpeg', '171184', '1343', '1920', '2026-01-24 10:33:54');
INSERT INTO `media` VALUES ('10', '697492062625d_1769247238.jpg', '67efbcddefd10f1150ee5bc5_toyota.jpg', 'uploads/697492062625d_1769247238.jpg', 'image/jpeg', '604966', '1280', '1920', '2026-01-24 10:33:58');
INSERT INTO `media` VALUES ('11', '697492093d238_1769247241.jpg', '4d0c088f-1e05-449a-abbe-4365487b9b89 kopie.jpg', 'uploads/697492093d238_1769247241.jpg', 'image/jpeg', '634281', '1280', '1920', '2026-01-24 10:34:01');
INSERT INTO `media` VALUES ('12', '6974920c53198_1769247244.png', '678282f2e6f5b3c094f85e35_banner-bus-1.png', 'uploads/6974920c53198_1769247244.png', 'image/png', '46052', '1812', '1195', '2026-01-24 10:34:04');
INSERT INTO `media` VALUES ('13', '6974920fa1c4d_1769247247.png', '01.png', 'uploads/6974920fa1c4d_1769247247.png', 'image/png', '968127', '2560', '1440', '2026-01-24 10:34:08');
INSERT INTO `media` VALUES ('14', '6974921364748_1769247251.jpg', '01.jpg', 'uploads/6974921364748_1769247251.jpg', 'image/jpeg', '270710', '1344', '1920', '2026-01-24 10:34:11');
INSERT INTO `media` VALUES ('15', '697492172c998_1769247255.jpeg', 'toyota.jpeg', 'uploads/697492172c998_1769247255.jpeg', 'image/jpeg', '301492', '1342', '1080', '2026-01-24 10:34:15');
INSERT INTO `media` VALUES ('16', '6974921c3fd76_1769247260.png', 'dodávka menší komprese.png', 'uploads/6974921c3fd76_1769247260.png', 'image/png', '262510', '1920', '1280', '2026-01-24 10:34:20');
INSERT INTO `media` VALUES ('18', '697492220ff1d_1769247266.jpg', '3310.jpg', 'uploads/697492220ff1d_1769247266.jpg', 'image/jpeg', '292543', '1280', '1920', '2026-01-24 10:34:26');
INSERT INTO `media` VALUES ('19', '6974935865a36_1769247576.jpg', '01.jpg', 'uploads/6974935865a36_1769247576.jpg', 'image/jpeg', '270710', '1344', '1920', '2026-01-24 10:39:36');
INSERT INTO `media` VALUES ('20', '69749359031ec_1769247577.jpg', '02 kopie.jpg', 'uploads/69749359031ec_1769247577.jpg', 'image/jpeg', '6802027', '1451', '1920', '2026-01-24 10:39:38');
INSERT INTO `media` VALUES ('21', '6974935ae4ef4_1769247578.jpg', '02.jpg', 'uploads/6974935ae4ef4_1769247578.jpg', 'image/jpeg', '171184', '1343', '1920', '2026-01-24 10:39:39');
INSERT INTO `media` VALUES ('24', '6974935c2df42_1769247580.jpg', '8147.jpg', 'uploads/6974935c2df42_1769247580.jpg', 'image/jpeg', '699884', '1280', '1920', '2026-01-24 10:39:40');
INSERT INTO `media` VALUES ('25', '6974935c68697_1769247580.jpg', '8407.jpg', 'uploads/6974935c68697_1769247580.jpg', 'image/jpeg', '791435', '1280', '1920', '2026-01-24 10:39:40');
INSERT INTO `media` VALUES ('26', '6974935ca8f97_1769247580.jpg', 'dodavka final.jpg', 'uploads/6974935ca8f97_1769247580.jpg', 'image/jpeg', '229958', '1280', '1920', '2026-01-24 10:39:40');
INSERT INTO `media` VALUES ('28', '6974935d28774_1769247581.png', 'kamion 2_11zon.png', 'uploads/6974935d28774_1769247581.png', 'image/png', '220587', '1920', '1280', '2026-01-24 10:39:41');
INSERT INTO `media` VALUES ('29', '6974935d6371a_1769247581.png', 'kamion dodávka komprese.png', 'uploads/6974935d6371a_1769247581.png', 'image/png', '204309', '1920', '1280', '2026-01-24 10:39:41');
INSERT INTO `media` VALUES ('30', '6974935d9f6d2_1769247581.png', 'kamion menší komprese.png', 'uploads/6974935d9f6d2_1769247581.png', 'image/png', '292747', '1873', '1280', '2026-01-24 10:39:41');
INSERT INTO `media` VALUES ('31', '6974935ddda49_1769247581.jpg', 'reklama01.jpg', 'uploads/6974935ddda49_1769247581.jpg', 'image/jpeg', '250504', '1920', '1280', '2026-01-24 10:39:42');
INSERT INTO `media` VALUES ('32', '6974935e24dd9_1769247582.jpg', 'reklama02.jpg', 'uploads/6974935e24dd9_1769247582.jpg', 'image/jpeg', '283506', '1920', '1280', '2026-01-24 10:39:42');
INSERT INTO `media` VALUES ('33', '6974935e61432_1769247582.jpg', 'reklama03.jpg', 'uploads/6974935e61432_1769247582.jpg', 'image/jpeg', '308460', '1920', '1280', '2026-01-24 10:39:42');
INSERT INTO `media` VALUES ('34', '6974935e9d034_1769247582.jpg', 'ROS_0105.JPG', 'uploads/6974935e9d034_1769247582.jpg', 'image/jpeg', '197710', '1132', '1920', '2026-01-24 10:39:42');
INSERT INTO `media` VALUES ('41', '697496f6290d3_1769248502.jpg', '500283764_732278162662832_570859775054356208_n.jpg', 'uploads/697496f6290d3_1769248502.jpg', 'image/jpeg', '37277', '1080', '640', '2026-01-24 10:55:02');
INSERT INTO `media` VALUES ('42', '6974973bcdc8b_1769248571.jpg', '516733149_1206676031472429_6173501354438327263_n.jpg', 'uploads/6974973bcdc8b_1769248571.jpg', 'image/jpeg', '130670', '1536', '1920', '2026-01-24 10:56:12');
INSERT INTO `media` VALUES ('44', '6974999596747_1769249173.jpg', '500168492_732278072662841_8179886613072456017_n.jpg', 'uploads/6974999596747_1769249173.jpg', 'image/jpeg', '75460', '1080', '667', '2026-01-24 11:06:13');
INSERT INTO `media` VALUES ('45', '697499f945a66_1769249273.jpg', '20251126_085508.jpg', 'uploads/697499f945a66_1769249273.jpg', 'image/jpeg', '279015', '1200', '676', '2026-01-24 11:07:53');
INSERT INTO `media` VALUES ('46', '69749c652bd94_1769249893.png', 'favicon.png', 'uploads/69749c652bd94_1769249893.png', 'image/png', '343', '32', '32', '2026-01-24 11:18:13');
INSERT INTO `media` VALUES ('47', '6974abc5c73b0_1769253829.jpg', '67efbcddefd10f1150ee5bc5_toyota.jpg', 'uploads/6974abc5c73b0_1769253829.jpg', 'image/jpeg', '604966', '1280', '1920', '2026-01-24 12:23:50');
INSERT INTO `media` VALUES ('48', '6974c595dfe02_1769260437.jpg', '20231028_162543.jpg', 'uploads/6974c595dfe02_1769260437.jpg', 'image/jpeg', '3086278', '1920', '865', '2026-01-24 14:13:58');
INSERT INTO `media` VALUES ('49', '6974c5e69e3a7_1769260518.png', '2coolguy.png', 'uploads/6974c5e69e3a7_1769260518.png', 'image/png', '401234', '4420', '3552', '2026-01-24 14:15:19');
INSERT INTO `media` VALUES ('52', '6974cf1453a22_1769262868.jpg', '20231003_174743.jpg', 'uploads/6974cf1453a22_1769262868.jpg', 'image/jpeg', '6189748', '1920', '1081', '2026-01-24 14:54:29');
INSERT INTO `media` VALUES ('55', '69750f2c28761_1769279276.jpg', '67efbcddefd10f1150ee5bc5_toyota.jpg', 'uploads/69750f2c28761_1769279276.jpg', 'image/jpeg', '604966', '1280', '1920', '2026-01-24 19:27:56');
INSERT INTO `media` VALUES ('56', '6976108b078de_1769345163.png', 'favicon.png', 'uploads/6976108b078de_1769345163.png', 'image/png', '343', '32', '32', '2026-01-25 13:46:03');
INSERT INTO `media` VALUES ('57', '697679b3731da_1769372083.png', 'favicon.png', 'uploads/697679b3731da_1769372083.png', 'image/png', '343', '32', '32', '2026-01-25 21:14:43');


--
-- Table: posts
--

DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `menu_order` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_author` (`author_id`),
  KEY `idx_published` (`published_at`),
  FULLTEXT KEY `idx_search` (`title`,`content`,`excerpt`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` VALUES ('5', 'Slavnosti 2026', 'slavnosti-2026', '<h1><strong>Ajoj </strong><s>gzigi</s></h1><p>hgfhfghj<em><span class=\"ql-cursor\">﻿</span></em></p>', 'Ajoj gzigihgfhfghj﻿', NULL, '5', '1', 'published', NULL, 'Slavnosti 2026', 'Ajoj gzigihgfhfghj﻿', '', '2026-01-23 19:47:40', '2026-01-23 19:47:40', '2026-01-25 15:35:07', '8');
INSERT INTO `posts` VALUES ('7', 'lhjljh', 'lhjljh', '<p>jljlkj</p>', 'jljlkj', NULL, NULL, '1', 'published', NULL, 'lhjljh', 'jljlkj', '', '2026-01-23 19:55:39', '2026-01-23 19:55:39', '2026-01-25 15:35:07', '6');
INSERT INTO `posts` VALUES ('8', 'fsdfgds', 'fsdfgds', '<p>dfsdgdg</p>', 'dfsdgdg', 'uploads/img_6973c5c2e836e2.24535555_1769194946.jpg', NULL, '1', 'published', NULL, 'fsdfgds', 'dfsdgdg', '', '2026-01-23 20:02:27', '2026-01-23 20:02:27', '2026-01-25 15:35:07', '5');
INSERT INTO `posts` VALUES ('9', 'dgsdfgdfg', 'dgsdfgdfg', '<p>fgdfgdfg</p>', 'fgdfgdfg', NULL, NULL, '1', 'published', NULL, 'dgsdfgdfg', 'fgdfgdfg', '', '2026-01-23 20:03:12', '2026-01-23 20:03:12', '2026-01-25 15:35:07', '4');
INSERT INTO `posts` VALUES ('10', 'gfghfgh', 'gfghfgh', '<p>gfhfghfg</p>', 'gfhfghfg', 'uploads/6973c76f1de8d_1769195375.jpg', NULL, '1', 'published', NULL, 'gfghfgh', 'gfhfghfg', '', '2026-01-23 20:09:36', '2026-01-23 20:09:36', '2026-01-25 15:35:07', '3');
INSERT INTO `posts` VALUES ('11', 'dfsdfs 2050 778874865844', 'dfsdfs-2050-778874865844', '<p> AHOJ TOOT JE TESTf dsf s f fsdf s fsdf s fs</p><p><br></p><p>dffd fdg ghhg jhj khkjhkl jklo ilipiopp uiozu iztbvbbtzhdydrt bzty</p>', 'AHOJ TOOT JE TESTf dsf s f fsdf s fsdf s fs

dffd fdg ghhg jhj khkjhkl jklo ilipiopp uiozu iztbvbbtzhdydrt...', 'uploads/69748e016d51c_1769246209.jpg', '1', '1', 'published', NULL, '', '', '', '2026-01-25 14:45:56', '2026-01-23 20:13:52', '2026-01-25 21:19:07', '0');
INSERT INTO `posts` VALUES ('12', 'd asd ', 'd-asd', '<p><br></p>', '', NULL, NULL, '1', 'published', NULL, 'd asd ', '', '', '2026-01-23 20:14:52', '2026-01-23 20:14:52', '2026-01-25 15:35:07', '2');
INSERT INTO `posts` VALUES ('13', 'dfs fs df', 'dfs-fs-df', '<p><br></p>', '', NULL, NULL, '1', 'published', NULL, 'dfs fs df', '', '', '2026-01-23 20:15:07', '2026-01-23 20:15:07', '2026-01-25 15:35:07', '1');
INSERT INTO `posts` VALUES ('14', 'f dsf sd ', 'f-dsf-sd', '<p> fdsf gfd </p>', 'fdsf gfd', NULL, '2', '1', 'published', NULL, 'f dsf sd ', 'fdsf gfd', '', '2026-01-23 20:15:28', '2026-01-23 20:15:28', '2026-01-25 15:36:13', '0');
INSERT INTO `posts` VALUES ('16', 'hjghkjh', 'hjghkjh', '<p>ljklkjljkl</p>', 'ljklkjljkl...', 'uploads/69748de63544c_1769246182.jpg', '1', '1', 'published', NULL, '', '', '', '2026-01-23 20:30:21', '2026-01-23 20:30:21', '2026-01-25 21:19:12', '2');
INSERT INTO `posts` VALUES ('40', 'Bez názvu', 'bez-nazvu-6', '<p><br></p>', '', NULL, '2', '1', 'published', NULL, 'Bez názvu', '', '', '2026-01-25 20:52:26', '2026-01-23 22:05:05', '2026-01-25 20:52:26', '1');
INSERT INTO `posts` VALUES ('50', 'fsdfsd', 'fsdfsd-1', '<p>fdsfsdf dsg gsdfg df gxd gd fgh ftz hf fv ghgerg edfg dc df gfg rtg rt rt</p>', 'fdsfsdf dsg gsdfg df gxd gd fgh ftz hf fv ghgerg edfg dc df gfg rtg rt...', 'uploads/69748ebca8b5b_1769246396.jpg', NULL, '1', 'published', NULL, 'fsdfsd - 2026', 'fdsfsdf dsg gsdfg df gxd gd fgh ftz hf fv ghgerg edfg dc df gfg rtg rt rt...', 'fsdfsd, fdsfsdf, gsdfg, ghgerg, edfg', '2026-01-24 10:19:56', '2026-01-24 10:19:56', '2026-01-25 15:17:42', '8');
INSERT INTO `posts` VALUES ('56', 'fdsf sdf', 'fdsf-sdf-1', '<p> fsdg dfg dfg fd gdf gdf gsdfg dsf</p><p><img src=\"http://localhost/blog-pro/uploads/6974998513111_1769249157.png\"></p>', 'fsdg dfg dfg fd gdf gdf gsdfg...', 'uploads/6974999596747_1769249173.jpg', NULL, '1', 'published', NULL, 'fdsf sdf', 'fsdg dfg dfg fd gdf gdf gsdfg...', '', '2026-01-24 11:06:13', '2026-01-24 11:06:13', '2026-01-25 15:17:42', '6');
INSERT INTO `posts` VALUES ('58', 'fdsgdfgdfgdfgdfg', 'fdsgdfgdfgdfgdfg-1', '<p><br></p>', '', 'uploads/697499f945a66_1769249273.jpg', NULL, '1', 'published', NULL, 'fdsgdfgdfgdfgdfg', '', '', '2026-01-24 11:07:53', '2026-01-24 11:07:53', '2026-01-25 15:17:42', '4');
INSERT INTO `posts` VALUES ('61', 'Pokračování Claude AI', 'pokracovani-claude-ai-1', '<p>Oki super, ještě malinké úpravy když dávám drag and drop obrázek do obsahu tak mi to bliká. Rád bych to upravil ještě jinak. Možná dávám drag and drop obrázek tak bych chtěl aby se pozadí rozostřilo, a zůstalo jen ostré to kam ho můžu vložit, tedy Obsah nebo hlavní obrázek. Poté bych chtěl pokud nahraji hlavní obrázek tak by se přímo do toho vložil a ne pod něj a tím by se skryl obsah (Hlavní obrázek příspěvku atd...) Taky se mi nelíbí když otevřu Z galerie tak náhled těch obrázků je strašně malý, dal bych opět stránkování a max tak 9 posledních by se zobrazilo :)</p>', 'Oki super, ještě malinké úpravy když dávám drag and drop obrázek do obsahu tak mi to bliká. Rád bych to upravil ještě jinak. Možná dávám drag and drop obrázek...', NULL, NULL, '1', 'published', NULL, 'Pokračování Claude AI - 2026', 'Oki super, ještě malinké úpravy když dávám drag and drop obrázek do obsahu tak mi to bliká. Rád bych to upravil ještě jinak. Možná dávám drag and drop...', 'obrazek, bych, hlavni, jeste, kdyz, davam, drag', '2026-01-24 11:23:22', '2026-01-24 11:23:22', '2026-01-25 15:17:42', '3');
INSERT INTO `posts` VALUES ('64', 'Rodinný dům test', 'rodinny-dum-test', '<p><br></p>', '', 'uploads/6974abc5c73b0_1769253829.jpg', '1', '1', 'published', NULL, '', '', '', '2026-01-24 12:23:50', '2026-01-24 12:23:50', '2026-01-25 21:19:12', '3');
INSERT INTO `posts` VALUES ('75', 'dsadsada', 'dsadsada', '<p><br></p>', '', NULL, NULL, '1', 'published', NULL, 'dsadsada', '', '', '2026-01-24 14:45:29', '2026-01-24 14:45:29', '2026-01-25 15:17:42', '2');
INSERT INTO `posts` VALUES ('83', 'TEST64984981982165000000000 sad as fs df ds gdf gdf g', 'test64984981982165000000000-sad-as-fs-df-ds-gdf-gdf-g', '<p>dgscs gdf gfdf g dfgd fgd f</p>', 'dgscs gdf gfdf g dfgd fgd...', 'uploads/6974c595dfe02_1769260437.jpg', '1', '1', 'published', NULL, '', '', '', '2026-01-25 15:04:06', '2026-01-24 15:02:20', '2026-01-25 21:20:00', '1');
INSERT INTO `posts` VALUES ('98', 'Bez názvu', 'bez-nazvu-3', '<p><br></p>', '', NULL, NULL, '1', 'published', NULL, 'Bez názvu', '', '', '2026-01-25 14:37:29', '2026-01-24 15:43:12', '2026-01-25 15:35:07', '10');
INSERT INTO `posts` VALUES ('102', 'Bez názvu 15555', 'bez-nazvu-15555', '<p><img src=\"http://localhost/blog-pro/uploads/697679b3731da_1769372083.png\">8484</p>', '8484...', NULL, NULL, '1', 'published', NULL, '', '', '', '2026-01-25 21:14:51', '2026-01-25 20:57:02', '2026-01-25 21:14:51', '0');
INSERT INTO `posts` VALUES ('103', 'TEST', 'test-1', '<p><strong><em><u>21545</u></em></strong></p>', '21545...', 'uploads/69750f2c28761_1769279276.jpg', NULL, '1', 'published', NULL, '', '', '', '2026-01-25 21:09:46', '2026-01-25 21:09:28', '2026-01-25 21:10:37', '0');
INSERT INTO `posts` VALUES ('105', 'Zkouška editora', 'zkouska-editora-1', '<h1 class=\"ql-align-center\">Ahoj já jsem editor</h1><p>Jak se dnes máš</p>', 'Ahoj já jsem editor
Jak se dnes...', 'uploads/69750f2c28761_1769279276.jpg', NULL, '4', 'published', NULL, '', '', '', '2026-01-25 22:34:59', '2026-01-25 22:34:59', '2026-01-25 22:35:06', '0');
INSERT INTO `posts` VALUES ('107', 'TEST', 'test-2', '<p>sdfafsdaf</p>', 'sdfafsdaf...', 'uploads/697499f945a66_1769249273.jpg', NULL, '4', 'published', NULL, '', '', '', '2026-01-25 22:35:19', '2026-01-25 22:35:19', '2026-01-25 22:36:02', '0');
INSERT INTO `posts` VALUES ('109', 'Admin test fotka', 'admin-test-fotka-1', '<p>asdasd</p>', 'asdasd...', 'uploads/69750f2c28761_1769279276.jpg', NULL, '1', 'published', NULL, '', '', '', '2026-01-25 22:36:37', '2026-01-25 22:36:37', '2026-01-25 22:36:55', '0');


--
-- Table: sessions
--

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table: users
--

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','editor') COLLATE utf8mb4_unicode_ci DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES ('1', 'admin1', '$2y$10$qyXTUt7rMvAVIk/YEarZ4eUMrrJbtFh9Yd8wP06utC7Ja1CNwbGBC', 'admin1@blog.cz', 'admin', '2026-01-22 21:31:36', NULL);
INSERT INTO `users` VALUES ('3', 'admin', '$2y$10$QnFE6YiwHJmQKf09m675peDhBDKezlkaVZx28Wpc0UM2TFlMzLOS2', NULL, 'admin', '2026-01-25 22:33:08', NULL);
INSERT INTO `users` VALUES ('4', 'editor', '$2y$10$/FvfegSYks8Ryvw1Bs4zOul3/u.NKrPzIX4jibtYi.ITHKxtplkDa', NULL, 'editor', '2026-01-25 22:33:59', NULL);

