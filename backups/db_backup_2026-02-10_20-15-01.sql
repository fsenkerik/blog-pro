-- Database Backup
-- Generated: 2026-02-10 20:15:01


--
-- Table: audit_log
--

DROP TABLE IF EXISTS `audit_log`;

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4;

INSERT INTO `audit_log` VALUES ('1', '3', 'create', 'post', '44', 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:09:22');
INSERT INTO `audit_log` VALUES ('2', '3', 'update', 'post', '44', 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:09:24');
INSERT INTO `audit_log` VALUES ('3', '3', 'delete', 'post', '39', 'Lol', NULL, '::1', '2026-01-28 22:09:27');
INSERT INTO `audit_log` VALUES ('4', '6', 'delete', 'post', '40', '654654', NULL, '::1', '2026-01-28 22:11:18');
INSERT INTO `audit_log` VALUES ('5', '3', 'delete', 'post', '44', 'dgsdgfdsg', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('6', '3', 'delete', 'post', '43', 'hfhfghfgh', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('7', '3', 'delete', 'post', '42', 'fdsfdsfs', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('8', '3', 'delete', 'post', '41', 'kjbhkjb', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('9', '3', 'delete', 'post', '37', 'AHoj', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('10', '3', 'delete', 'post', '35', '2030', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('11', '3', 'delete', 'post', '33', 'mmmmm', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('12', '3', 'delete', 'post', '31', 'trjhjjghf', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('13', '3', 'delete', 'post', '28', '200000', NULL, '::1', '2026-01-28 22:29:21');
INSERT INTO `audit_log` VALUES ('14', '3', 'create', 'post', '45', 'dsadasd', NULL, '::1', '2026-01-28 22:29:25');
INSERT INTO `audit_log` VALUES ('15', '3', 'update', 'post', '45', 'dsadasd', NULL, '::1', '2026-01-28 22:29:30');
INSERT INTO `audit_log` VALUES ('16', '3', 'delete', 'post', '45', 'dsadasd', NULL, '::1', '2026-01-28 22:33:28');
INSERT INTO `audit_log` VALUES ('17', '3', 'create', 'post', '40', '54164', NULL, '::1', '2026-02-04 21:00:41');
INSERT INTO `audit_log` VALUES ('18', '3', 'update', 'post', '40', '54164', NULL, '::1', '2026-02-04 21:00:46');
INSERT INTO `audit_log` VALUES ('19', '3', 'delete', 'post', '40', '54164', NULL, '::1', '2026-02-04 21:10:35');
INSERT INTO `audit_log` VALUES ('20', '3', 'delete', 'post', '39', 'Lol', NULL, '::1', '2026-02-10 20:04:12');
INSERT INTO `audit_log` VALUES ('21', '3', 'delete', 'post', '37', 'AHoj', NULL, '::1', '2026-02-10 20:04:12');
INSERT INTO `audit_log` VALUES ('22', '3', 'delete', 'post', '35', '2030', NULL, '::1', '2026-02-10 20:04:12');
INSERT INTO `audit_log` VALUES ('23', '3', 'delete', 'post', '33', 'mmmmm', NULL, '::1', '2026-02-10 20:04:12');
INSERT INTO `audit_log` VALUES ('24', '3', 'delete', 'post', '31', 'trjhjjghf', NULL, '::1', '2026-02-10 20:04:12');
INSERT INTO `audit_log` VALUES ('25', '3', 'delete', 'post', '28', '200000', NULL, '::1', '2026-02-10 20:04:12');


--
-- Table: backup_schedule
--

DROP TABLE IF EXISTS `backup_schedule`;

CREATE TABLE `backup_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('database','full') COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `frequency` enum('daily','weekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT 'daily',
  `time` time DEFAULT '02:00:00',
  `day_of_week` int(11) DEFAULT '1' COMMENT '1=Po, 7=Ne (pro weekly)',
  `day_of_month` int(11) DEFAULT '1' COMMENT '1-28 (pro monthly)',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `backup_schedule` VALUES ('1', 'database', '1', 'daily', '20:05:00', '1', '1', '2026-02-09 20:11:27', NULL, '2026-02-05 19:41:44', '2026-02-09 20:11:27');
INSERT INTO `backup_schedule` VALUES ('2', 'full', '1', 'daily', '20:09:00', '7', '1', NULL, NULL, '2026-02-05 19:41:44', '2026-02-09 20:08:39');


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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` VALUES ('1', 'Akce 2025', 'akce-2025', 'Události a akce z roku 2025', '2026-01-22 21:31:36');
INSERT INTO `categories` VALUES ('2', 'Akce 2026', 'akce-2026', 'Události a akce z roku 2026', '2026-01-22 21:31:36');
INSERT INTO `categories` VALUES ('3', 'Novinky', 'novinky', 'Nejnovější zprávy a informace', '2026-01-22 21:31:36');
INSERT INTO `categories` VALUES ('5', 'Zahradní slavnosti', 'zahradni-slavnosti', NULL, '2026-01-23 19:40:36');
INSERT INTO `categories` VALUES ('6', 'Zimní slavnosti', 'zimni-slavnosti', NULL, '2026-01-23 21:36:17');


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
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
INSERT INTO `error_logs` VALUES ('24', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p class=\\\"ql-align-center\\\">TEST</p><p>dsadsafsdfsdfdsfsdfsdfdsfsdfsdgdfbxcbxc\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('25', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('26', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">fsdfsdfsdf df sdf sd fsdf sdf sd ds<em>f dsf ds</em> <em> d</em></strong><str\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('27', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('28', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><em>s</em></strong><strong><em> fs</em> fsd f</strong><strong style=\\\"color: \' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('29', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('30', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">s<u> ssf s s</u></strong><strong><u>d f sf s</u></strong><strong style=\\\"colo\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('31', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('32', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"><u>d fsd fs fsdd</u></strong><strong><u> fds fsdf sd</u></strong></p><p><br><\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('33', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'5\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('34', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>f dsf s f fsdf s<span class=\\\"ql-font-serif\\\"> fsdf s fs</span></p><p><br></\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('35', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'10\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('36', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"> khkjhkl jklo ilipiopp uiozu iztbvbbtzh</span>dydrt bzty</p>\', \'f dsf s f fsd\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('37', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'10\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('38', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<h3 class=\\\"ql-align-center\\\">AHOJ</h3><p>gdfgdfggfdgdfgdgfgdgfdgdgfgdfgdfghfgh\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('39', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('40', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIbGNtcwIQAABtbnRyU\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('41', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'14\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('42', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\'<p>df sdf sd dfgdfhg fghfg hfg hgfh f  df gdfg fdh fgh fgh fgh ghjh fgh fg hf g\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('43', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('44', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\">g fdhgf hfg hfg hfg hfg h</span><span style=\\\"background-color: rgb(240, 102,\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('45', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('46', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'color: rgb(230, 0, 0)\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('47', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('48', 'Query Prepare', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\\\"> hfg gf hg ghjhg j hg j</span></p>\', \'df sdf sd dfgdfhg fghfg hfg hgfh f df g\' at line 1', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('49', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'35\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('50', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1217 Cannot delete or update a parent row: a foreign key constraint fails', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('51', 'Query Execute', 'SQLSTATE[42S01]: Base table or view already exists: 1050 Table \'users\' already exists', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('52', 'Query Execute', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1\' for key \'PRIMARY\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:47');
INSERT INTO `error_logs` VALUES ('53', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:48');
INSERT INTO `error_logs` VALUES ('54', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-25 22:38:53');
INSERT INTO `error_logs` VALUES ('55', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-25 22:45:31');
INSERT INTO `error_logs` VALUES ('56', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-25 22:51:08');
INSERT INTO `error_logs` VALUES ('57', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:25:00');
INSERT INTO `error_logs` VALUES ('58', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:28:20');
INSERT INTO `error_logs` VALUES ('59', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:28:20');
INSERT INTO `error_logs` VALUES ('60', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:32:58');
INSERT INTO `error_logs` VALUES ('61', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:32:58');
INSERT INTO `error_logs` VALUES ('62', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:32:59');
INSERT INTO `error_logs` VALUES ('63', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:32:59');
INSERT INTO `error_logs` VALUES ('64', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:00');
INSERT INTO `error_logs` VALUES ('65', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:00');
INSERT INTO `error_logs` VALUES ('66', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:00');
INSERT INTO `error_logs` VALUES ('67', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:00');
INSERT INTO `error_logs` VALUES ('68', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:01');
INSERT INTO `error_logs` VALUES ('69', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:01');
INSERT INTO `error_logs` VALUES ('70', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:14');
INSERT INTO `error_logs` VALUES ('71', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:14');
INSERT INTO `error_logs` VALUES ('72', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:27');
INSERT INTO `error_logs` VALUES ('73', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:30');
INSERT INTO `error_logs` VALUES ('74', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:30');
INSERT INTO `error_logs` VALUES ('75', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:31');
INSERT INTO `error_logs` VALUES ('76', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'p.menu_order\' in \'order clause\'', NULL, NULL, '1', '::1', '2026-01-26 19:33:31');
INSERT INTO `error_logs` VALUES ('77', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('78', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('79', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('80', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('81', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('82', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('83', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('84', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('85', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('86', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('87', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('88', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('89', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('90', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('91', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('92', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('93', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('94', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('95', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('96', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('97', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('98', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('99', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:04');
INSERT INTO `error_logs` VALUES ('100', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('101', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('102', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('103', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('104', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('105', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('106', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('107', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('108', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('109', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('110', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('111', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('112', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('113', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('114', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('115', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('116', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('117', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('118', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('119', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('120', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('121', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('122', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:31:12');
INSERT INTO `error_logs` VALUES ('123', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('124', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('125', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('126', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('127', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('128', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('129', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('130', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('131', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('132', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('133', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('134', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('135', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('136', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('137', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('138', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('139', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('140', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('141', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('142', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('143', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('144', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('145', 'Query Prepare', 'SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters', NULL, NULL, '1', '::1', '2026-01-26 20:32:28');
INSERT INTO `error_logs` VALUES ('146', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 20:44:55');
INSERT INTO `error_logs` VALUES ('147', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 20:44:55');
INSERT INTO `error_logs` VALUES ('148', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 20:44:55');
INSERT INTO `error_logs` VALUES ('149', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '5', '::1', '2026-02-04 20:56:00');
INSERT INTO `error_logs` VALUES ('150', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '5', '::1', '2026-02-04 20:56:00');
INSERT INTO `error_logs` VALUES ('151', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '5', '::1', '2026-02-04 20:56:00');
INSERT INTO `error_logs` VALUES ('152', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:21');
INSERT INTO `error_logs` VALUES ('153', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:21');
INSERT INTO `error_logs` VALUES ('154', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:21');
INSERT INTO `error_logs` VALUES ('155', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:33');
INSERT INTO `error_logs` VALUES ('156', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:33');
INSERT INTO `error_logs` VALUES ('157', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:33');
INSERT INTO `error_logs` VALUES ('158', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:55');
INSERT INTO `error_logs` VALUES ('159', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:55');
INSERT INTO `error_logs` VALUES ('160', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:00:55');
INSERT INTO `error_logs` VALUES ('161', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:01:21');
INSERT INTO `error_logs` VALUES ('162', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:01:21');
INSERT INTO `error_logs` VALUES ('163', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:01:21');
INSERT INTO `error_logs` VALUES ('164', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:05:23');
INSERT INTO `error_logs` VALUES ('165', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:05:23');
INSERT INTO `error_logs` VALUES ('166', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:05:23');
INSERT INTO `error_logs` VALUES ('167', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:10:40');
INSERT INTO `error_logs` VALUES ('168', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:10:40');
INSERT INTO `error_logs` VALUES ('169', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:10:40');
INSERT INTO `error_logs` VALUES ('170', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:11:42');
INSERT INTO `error_logs` VALUES ('171', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-04 21:11:42');
INSERT INTO `error_logs` VALUES ('172', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-04 21:11:42');
INSERT INTO `error_logs` VALUES ('173', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-10 20:12:14');
INSERT INTO `error_logs` VALUES ('174', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'logout_at\' in \'where clause\'', NULL, NULL, '3', '::1', '2026-02-10 20:12:14');
INSERT INTO `error_logs` VALUES ('175', 'Query Prepare', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'s.login_at\' in \'field list\'', NULL, NULL, '3', '::1', '2026-02-10 20:12:14');


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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table: posts
--

DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_author` (`author_id`),
  KEY `idx_slug` (`slug`),
  FULLTEXT KEY `ft_search` (`title`,`content`,`excerpt`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
  `role` enum('it','admin','editor') COLLATE utf8mb4_unicode_ci DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES ('1', 'admin1', '$2y$10$qyXTUt7rMvAVIk/YEarZ4eUMrrJbtFh9Yd8wP06utC7Ja1CNwbGBC', 'admin1@blog.cz', 'admin', '2026-01-22 21:31:36', NULL);
INSERT INTO `users` VALUES ('2', 'editor1', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'editor1@blog.cz', 'editor', '2026-01-22 21:31:36', NULL);
INSERT INTO `users` VALUES ('3', 'admin', '$2y$10$QnFE6YiwHJmQKf09m675peDhBDKezlkaVZx28Wpc0UM2TFlMzLOS2', NULL, 'it', '2026-01-25 22:33:08', NULL);

