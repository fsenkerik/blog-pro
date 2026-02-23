-- Database Backup
-- Generated: 2026-01-25 22:43:20


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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `backups` VALUES ('1', 'db_backup_2026-01-22_22-05-17.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-22_22-05-17.sql', '6237', '1', '2026-01-22 22:05:17');
INSERT INTO `backups` VALUES ('2', 'db_backup_2026-01-22_22-22-16.sql', 'C:\\Users\\filip\\Desktop\\www\\blog-pro/backups/db_backup_2026-01-22_22-22-16.sql', '7077', '1', '2026-01-22 22:22:16');


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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` VALUES ('1', 'Testovací příspěvek', 'testovaci-prispevek', '<p>Toto je testovací obsah příspěvku.</p>', 'Toto je testovací obsah příspěvku.', NULL, NULL, '1', 'draft', 'Testovací příspěvek', 'Popis testovacího příspěvku', 'test, příspěvek', NULL, '2026-01-23 19:03:52', '2026-01-23 19:03:52');
INSERT INTO `posts` VALUES ('2', 'Testovací příspěvek', 'testovaci-prispevek-1', '<p>Toto je testovací obsah příspěvku.</p>', 'Toto je testovací obsah příspěvku.', NULL, NULL, '1', 'draft', 'Testovací příspěvek', 'Popis testovacího příspěvku', 'test, příspěvek', NULL, '2026-01-23 19:13:05', '2026-01-23 19:13:05');
INSERT INTO `posts` VALUES ('3', 'sfsdfsd', 'sfsdfsd', '<p>dgdfhgdfhgd</p>', 'dgdfhgdfhgd', NULL, NULL, '1', 'draft', 'sfsdfsd', 'dgdfhgdfhgd', '', NULL, '2026-01-23 19:15:48', '2026-01-23 19:15:48');
INSERT INTO `posts` VALUES ('5', 'Slavnosti 2026', 'slavnosti-2026', '<h1><strong>Ajoj </strong><s>gzigi</s></h1><p>hgfhfghj<em><span class=\"ql-cursor\">﻿</span></em></p>', 'Ajoj gzigihgfhfghj﻿', NULL, '5', '1', 'published', 'Slavnosti 2026', 'Ajoj gzigihgfhfghj﻿', '', '2026-01-23 19:47:40', '2026-01-23 19:47:40', '2026-01-23 19:47:40');
INSERT INTO `posts` VALUES ('7', 'lhjljh', 'lhjljh', '<p>jljlkj</p>', 'jljlkj', NULL, NULL, '1', 'published', 'lhjljh', 'jljlkj', '', '2026-01-23 19:55:39', '2026-01-23 19:55:39', '2026-01-23 19:55:39');
INSERT INTO `posts` VALUES ('8', 'fsdfgds', 'fsdfgds', '<p>dfsdgdg</p>', 'dfsdgdg', 'uploads/img_6973c5c2e836e2.24535555_1769194946.jpg', NULL, '1', 'published', 'fsdfgds', 'dfsdgdg', '', '2026-01-23 20:02:27', '2026-01-23 20:02:27', '2026-01-23 20:02:27');
INSERT INTO `posts` VALUES ('9', 'dgsdfgdfg', 'dgsdfgdfg', '<p>fgdfgdfg</p>', 'fgdfgdfg', NULL, NULL, '1', 'published', 'dgsdfgdfg', 'fgdfgdfg', '', '2026-01-23 20:03:12', '2026-01-23 20:03:12', '2026-01-23 20:03:12');
INSERT INTO `posts` VALUES ('10', 'gfghfgh', 'gfghfgh', '<p>gfhfghfg</p>', 'gfhfghfg', 'uploads/6973c76f1de8d_1769195375.jpg', NULL, '1', 'published', 'gfghfgh', 'gfhfghfg', '', '2026-01-23 20:09:36', '2026-01-23 20:09:36', '2026-01-23 20:09:36');
INSERT INTO `posts` VALUES ('12', 'd asd ', 'd-asd', '<p><br></p>', '', NULL, NULL, '1', 'published', 'd asd ', '', '', '2026-01-23 20:14:52', '2026-01-23 20:14:52', '2026-01-23 20:14:52');
INSERT INTO `posts` VALUES ('13', 'dfs fs df', 'dfs-fs-df', '<p><br></p>', '', NULL, NULL, '1', 'published', 'dfs fs df', '', '', '2026-01-23 20:15:07', '2026-01-23 20:15:07', '2026-01-23 20:15:07');
INSERT INTO `posts` VALUES ('14', 'f dsf sd ', 'f-dsf-sd', '<p> fdsf gfd </p>', 'fdsf gfd', NULL, '2', '1', 'published', 'f dsf sd ', 'fdsf gfd', '', '2026-01-23 20:15:28', '2026-01-23 20:15:28', '2026-01-23 20:15:28');
INSERT INTO `posts` VALUES ('16', 'hjghkjh', 'hjghkjh', '<p>ljklkjljkl</p>', 'ljklkjljkl', 'uploads/6973cc4d4be09_1769196621.jpg', '1', '1', 'published', 'hjghkjh', 'ljklkjljkl', '', '2026-01-23 20:30:21', '2026-01-23 20:30:21', '2026-01-23 20:30:21');
INSERT INTO `posts` VALUES ('17', 'Bez názvu', 'bez-nazvu', '<p>gdf gf df gfd hgf hfg hfg g dfg fd sjh gikdtzdjg  shbsaiu foisj osjoisj éísdjgéíjeíéfhíéis jfdíéá jgáídh gíádg héís gejpéghíui rhogigdhripíg jdfipíj gípd rshoguíhedírui ghpéeriíj géi erjg píi jerpgijppiíéire erj iére jgreij geriéo jrié jeriéoj giéo jeoipjfisdiopgdggdi hgih gr hg hg h gh ghá hg h   hh h gh gh gh g hg hág hg háerjg éer jgéíerj éí jgeréí gjíéer j éígjerhéá géíeg éíjger ée éjí geéj geré jgeé=új sgéíegs=éíj gé jgeaé=újea=éúi gjpuif  t higehir weéíie ége jé=erjéíe é=g é= gegrjéig jéisdfj é=sj =aái jg=jage éjrea íui goip hasdf oihfsaih bfbi fsibiuvuh  hi uiug oág iosdf siph oewthi ptweah piaetw hpioagspiho sga hpio sag ehpsag hpsgad h sgd ah pgs ah s athp sea thipo sag ipho gs dhip sg aipohjsgaiohp sg aiopjhsga ioph sgad ioh sg aioh gs aiohp gs a hp tahp ig sahp f ewhp gsahgs aoihpg ohipg dshipog sdhigd ioioh pdioh pgdg i </p>', 'gdf gf df gfd hgf hfg hfg g dfg fd sjh gikdtzdjg shbsaiu foisj osjoisj éísdjgéíjeíéfhíéis jfdíéá jgáídh gíádg héís gejpéghíui rhogigdhripíg jdfipíj gípd rshoguíhedírui ghpéeriíj géi erjg píi jerpgijpp...', NULL, NULL, '1', 'draft', 'Bez názvu', 'gdf gf df gfd hgf hfg hfg g dfg fd sjh gikdtzdjg shbsaiu foisj osjoisj éísdjgéíjeíéfhíéis jfdíéá jgáídh gíádg héís gejpéghíui rhogigdhripíg jdfipíj gípd rshoguíhedírui ghpéeriíj géi erjg píi jerpgijpp...', '', NULL, '2026-01-23 20:52:19', '2026-01-23 20:52:19');
INSERT INTO `posts` VALUES ('19', 'Bez názvu', 'bez-nazvu-1', '<p><br></p>', '', NULL, NULL, '1', 'draft', 'Bez názvu', '', '', NULL, '2026-01-23 20:53:35', '2026-01-23 20:53:35');
INSERT INTO `posts` VALUES ('20', 'jhgjg', 'jhgjg', '<p>sfofoidsf dsa das fdsf ds ggdf g dgfdg ffd ggf g g g g g dg  gdaagda fag afs ger garg df hgdfh dfh  gf htz hgfh fgh oi </p>', 'sfofoidsf dsa das fdsf ds ggdf g dgfdg ffd ggf g g g g g dg gdaagda fag afs ger garg df hgdfh dfh gf htz hgfh fgh oi', NULL, NULL, '1', 'draft', 'jhgjg', 'sfofoidsf dsa das fdsf ds ggdf g dgfdg ffd ggf g g g g g dg gdaagda fag afs ger garg df hgdfh dfh gf htz hgfh fgh oi', '', NULL, '2026-01-23 20:57:23', '2026-01-23 20:57:23');
INSERT INTO `posts` VALUES ('21', '123465+8+9', '12346589', '<p>ázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztf</p><p> fdsf dsf sdf </p>', 'ázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gzzg uzguzg uigztfázghuz gu bho zg ztf tz fuztzf tz fzug iug iý uzguzgz gz...', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 20:58:00', '2026-01-23 20:59:00');
INSERT INTO `posts` VALUES ('22', 'gfjgfjjghj', 'gfjgfjjghj', '<p>fggdkjngodofi  <span class=\"ql-font-georgia\">gmdfpgm pdfmg pdmf sf f ds fds dfd gf h fgh fgh fs f ds fs uih iuh ifdsý fidsu </span></p>', 'fggdkjngodofi gmdfpgm pdfmg pdmf sf f ds fds dfd gf h fgh fgh fs f ds fs uih iuh ifdsý fidsu', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:01:03', '2026-01-23 21:01:26');
INSERT INTO `posts` VALUES ('23', 'jhgjhkhk', 'jhgjhkhk', '<p>hj jgh gj hdsgdgdfgdfg jghjiuhh uhiuhih nnnkjnkmlk lk m </p>', 'hj jgh gj hdsgdgdfgdfg jghjiuhh uhiuhih nnnkjnkmlk lk m', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:06:16', '2026-01-23 21:09:29');
INSERT INTO `posts` VALUES ('24', 'sdadasdasddfs dsf s', 'sdadasdasddfs-dsf-s', '<p>dsdada fds fsdf  jsn fdasjnkjbnkj jskand jj oij daosijd pojapojk podaspopod asd aoisjdp</p>', 'dsdada fds fsdf jsn fdasjnkjbnkj jskand jj oij daosijd pojapojk podaspopod asd aoisjdp', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:10:02', '2026-01-23 21:10:23');
INSERT INTO `posts` VALUES ('25', 'gfdgdfg', 'gfdgdfg-1', '<p>gfdgd</p>', 'gfdgd', NULL, NULL, '1', 'draft', 'gfdgdfg', 'gfdgd', '', NULL, '2026-01-23 21:10:33', '2026-01-23 21:10:33');
INSERT INTO `posts` VALUES ('26', 'g dfg df', 'g-dfg-df', '<p> gd d df</p>', 'gd d df', NULL, NULL, '1', 'draft', 'g dfg df', 'gd d df', '', NULL, '2026-01-23 21:10:41', '2026-01-23 21:10:41');
INSERT INTO `posts` VALUES ('27', '8489df s4f', '8489df-s4f', '<p><br></p>', '', NULL, NULL, '1', 'draft', '8489df s4f', '', '', NULL, '2026-01-23 21:10:49', '2026-01-23 21:10:49');
INSERT INTO `posts` VALUES ('28', 'fggdfgd', 'fggdfgd', '<p>dfgdfgdfg</p>', 'dfgdfgdfg', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:11:23', '2026-01-23 21:12:07');
INSERT INTO `posts` VALUES ('29', 'dgsdfgsdffs', 'dgsdfgsdffs', '<p>fdsfsdfsdffdfsdfsfdsfsdf sdf sdf </p>', 'fdsfsdfsdffdfsdfsfdsfsdf sdf sdf', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:13:22', '2026-01-23 21:13:35');
INSERT INTO `posts` VALUES ('30', 'dfsfdfsdg', 'dfsfdfsdg', '<p>gfdgdfg</p>', 'gfdgdfg', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:15:38', '2026-01-23 21:15:41');
INSERT INTO `posts` VALUES ('31', 'd', 'd', '<p>sdasad sd asd asd asd  das as ds asf sd sad </p>', 'sdasad sd asd asd asd das as ds asf sd sad', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:17:09', '2026-01-23 21:17:26');
INSERT INTO `posts` VALUES ('32', 'fds', 'fds', '<p> fds fsd fd gf fdg df g</p>', 'fds fsd fd gf fdg df g', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:17:45', '2026-01-23 21:17:49');
INSERT INTO `posts` VALUES ('33', 'gdfgdfgh', 'gdfgdfgh', '<p>gfj gfh fg hfg hfg hf  hfg gf</p><h1> hf fg fg h g  </h1><h3> jgj gj  hfg f hf gh </h3><p class=\"ql-align-right\"> hfg f <sup> hfg hfg h hgkhg </sup><sub> kghk hg  </sub> gh j ghjgh j h hfg hg g ghjgh jhg j651 j6565651165116516 516 56165 1651 615561</p>', 'gfj gfh fg hfg hfg hf hfg gf hf fg fg h g jgj gj hfg f hf gh hfg f hfg hfg h hgkhg kghk hg gh j ghjgh j h hfg hg g ghjgh jhg j651 j6565651165116516 516 56165 1651 615561', NULL, NULL, '1', 'draft', '', '', '', NULL, '2026-01-23 21:20:56', '2026-01-23 21:22:02');
INSERT INTO `posts` VALUES ('34', 'Bez názvu', 'bez-nazvu-2', '<p><br></p>', '', NULL, NULL, '1', 'draft', 'Bez názvu', '', '', NULL, '2026-01-23 21:22:38', '2026-01-23 21:22:38');
INSERT INTO `posts` VALUES ('35', 'Bez názvu', 'bez-nazvu-3', '<p><br></p>', '', NULL, '6', '1', 'draft', 'Bez názvu', '', '', NULL, '2026-01-23 21:36:27', '2026-01-23 21:36:27');
INSERT INTO `posts` VALUES ('37', 'Bez názvu', 'bez-nazvu-4', '<p><br></p>', '', NULL, NULL, '1', 'draft', 'Bez názvu', '', '', NULL, '2026-01-23 21:57:14', '2026-01-23 21:57:14');
INSERT INTO `posts` VALUES ('38', '14151', '14151', '<p><br></p>', '', NULL, NULL, '1', 'draft', '14151', '', '', NULL, '2026-01-23 21:58:54', '2026-01-23 21:58:54');


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

