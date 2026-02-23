-- Database Backup
-- Generated: 2026-01-27 18:36:51


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
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `media` VALUES ('74', '6977c36ebdc23_1769456494.jpg', 'Screenshot_20230413_064104_Instagram.jpg', 'uploads/6977c36ebdc23_1769456494.jpg', 'image/jpeg', '699266', '1079', '1344', '2026-01-26 20:41:34');
INSERT INTO `media` VALUES ('75', '6977c375089a6_1769456501.jpg', 'Screenshot_20250413_150633_Instagram.jpg', 'uploads/6977c375089a6_1769456501.jpg', 'image/jpeg', '803693', '1080', '1346', '2026-01-26 20:41:41');
INSERT INTO `media` VALUES ('76', '6977c8c01c6c5_1769457856.jpg', '20221004_124214.jpg', 'uploads/6977c8c01c6c5_1769457856.jpg', 'image/jpeg', '261375', '1920', '909', '2026-01-26 21:04:16');
INSERT INTO `media` VALUES ('77', '6977c8c4c4650_1769457860.png', 'kartona_maskot_fin.png', 'uploads/6977c8c4c4650_1769457860.png', 'image/png', '230281', '1245', '1556', '2026-01-26 21:04:21');
INSERT INTO `media` VALUES ('78', '6977c91ca0200_1769457948.jpg', '20221004_124214.jpg', 'uploads/6977c91ca0200_1769457948.jpg', 'image/jpeg', '261375', '1920', '909', '2026-01-26 21:05:49');
INSERT INTO `media` VALUES ('79', '6977c92138c21_1769457953.png', 'kartona_maskot_fin.png', 'uploads/6977c92138c21_1769457953.png', 'image/png', '230281', '1245', '1556', '2026-01-26 21:05:53');
INSERT INTO `media` VALUES ('80', '6978f612ad7a1_1769534994.jpg', 'Screenshot_20250329_012624_Instagram.jpg', 'uploads/6978f612ad7a1_1769534994.jpg', 'image/jpeg', '1509687', '1080', '1906', '2026-01-27 18:29:55');
INSERT INTO `media` VALUES ('81', '6978f6275fc70_1769535015.jpg', 'Screenshot_20250730_183048_Instagram.jpg', 'uploads/6978f6275fc70_1769535015.jpg', 'image/jpeg', '1236820', '1080', '1420', '2026-01-27 18:30:15');
INSERT INTO `media` VALUES ('82', '6978f627a17a3_1769535015.jpg', 'Screenshot_20250515_203645_Instagram.jpg', 'uploads/6978f627a17a3_1769535015.jpg', 'image/jpeg', '620590', '1080', '1337', '2026-01-27 18:30:15');
INSERT INTO `media` VALUES ('83', '6978f627ce634_1769535015.jpg', 'Screenshot_20250610_191154_Instagram.jpg', 'uploads/6978f627ce634_1769535015.jpg', 'image/jpeg', '515957', '1080', '1336', '2026-01-27 18:30:15');
INSERT INTO `media` VALUES ('84', '6978f62805f2d_1769535016.jpg', 'Screenshot_20250725_195750_Instagram.jpg', 'uploads/6978f62805f2d_1769535016.jpg', 'image/jpeg', '1029505', '1080', '1429', '2026-01-27 18:30:16');


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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` VALUES ('27', '200000', '200000', '<p><img src=\"http://localhost/blog-pro/uploads/6977c91ca0200_1769457948.jpg\"></p>', '', NULL, NULL, '1', 'draft', '0', '', '', '', NULL, NULL, '2026-01-26 21:05:47', '2026-01-26 21:05:50');
INSERT INTO `posts` VALUES ('28', '200000', '200000-1', '<p><img src=\"http://localhost/blog-pro/uploads/6977c91ca0200_1769457948.jpg\"></p>', '', 'uploads/6977c92138c21_1769457953.png', NULL, '1', 'published', '0', '200000', '', '', '2026-01-26 21:05:53', NULL, '2026-01-26 21:05:53', '2026-01-26 21:05:53');
INSERT INTO `posts` VALUES ('29', 'Bugiing', 'bugiing', '<p>(:</p>', '(:', NULL, NULL, '1', 'draft', '0', '', '', '', NULL, NULL, '2026-01-27 18:29:28', '2026-01-27 18:29:31');
INSERT INTO `posts` VALUES ('30', 'Bugiing', 'bugiing-1', '<p>(:</p>', '(:...', 'uploads/6978f612ad7a1_1769534994.jpg', NULL, '1', 'published', '0', 'Bugiing', '(:...', '', '2026-01-27 18:29:55', NULL, '2026-01-27 18:29:55', '2026-01-27 18:29:55');


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
INSERT INTO `users` VALUES ('2', 'editor1', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'editor1@blog.cz', 'editor', '2026-01-22 21:31:36', NULL);
INSERT INTO `users` VALUES ('3', 'admin', '$2y$10$QnFE6YiwHJmQKf09m675peDhBDKezlkaVZx28Wpc0UM2TFlMzLOS2', NULL, 'admin', '2026-01-25 22:33:08', NULL);
INSERT INTO `users` VALUES ('4', 'editor', '$2y$10$/FvfegSYks8Ryvw1Bs4zOul3/u.NKrPzIX4jibtYi.ITHKxtplkDa', NULL, 'editor', '2026-01-25 22:33:59', NULL);

