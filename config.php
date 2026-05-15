<?php
/**
 * Blog Pro - Main Configuration
 */

if (!defined('BLOG_PRO')) {
    define('BLOG_PRO', true);
}

$isLocal = (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === false || getenv('APP_ENV') === '');
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('Europe/Prague');

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'blog_pro');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: 'root');
define('DB_CHARSET', 'utf8mb4');

define('ROOT_PATH',    dirname(__FILE__) . '/');
define('ADMIN_PATH',   ROOT_PATH . 'admin/');
define('INCLUDES_PATH',ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('BACKUPS_PATH', ROOT_PATH . 'backups/');
define('ASSETS_PATH',  ROOT_PATH . 'assets/');

$baseUrl = getenv('BASE_URL') ?: 'http://localhost:8888/blog-pro/';
define('BASE_URL',    $baseUrl);
define('ADMIN_URL',   BASE_URL . 'admin/');
define('ASSETS_URL',  BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

define('SESSION_LIFETIME',    3600 * 2);
define('MAX_LOGIN_ATTEMPTS',  5);
define('LOGIN_TIMEOUT',       900);

define('MAX_UPLOAD_SIZE',      5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

define('SITE_NAME',        'Blog Pro');
define('SITE_DESCRIPTION', 'Profesionální blog systém');
define('SITE_KEYWORDS',    'blog, novinky, články');

define('POSTS_PER_PAGE', 12);

define('AUTO_BACKUP_ENABLED',   true);
define('BACKUP_RETENTION_DAYS', 30);

define('IMAGE_MAX_WIDTH',   1920);
define('IMAGE_MAX_HEIGHT',  1080);
define('IMAGE_QUALITY',     85);
define('THUMBNAIL_WIDTH',   400);
define('THUMBNAIL_HEIGHT',  300);

spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . strtolower($class) . '.class.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once INCLUDES_PATH . 'database.class.php';
require_once INCLUDES_PATH . 'auth.class.php';
require_once INCLUDES_PATH . 'post.class.php';
require_once INCLUDES_PATH . 'category.class.php';
require_once INCLUDES_PATH . 'user.class.php';
require_once INCLUDES_PATH . 'upload.class.php';
require_once INCLUDES_PATH . 'security.class.php';
require_once INCLUDES_PATH . 'seo.class.php';
require_once INCLUDES_PATH . 'backup.class.php';
require_once INCLUDES_PATH . 'media.class.php';

$db = new Database();

// Auto-migrate lightweight schema drift from older installs.
if (!defined('SKIP_DB_MIGRATIONS') && !isset($_SESSION['db_migrated_v5'])) {
    try {
        $columnExists = function (string $table, string $column) use ($db): bool {
            $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column");
            $db->bind(':table', $table);
            $db->bind(':column', $column);
            $row = $db->fetch();
            return $row && (int)$row['c'] > 0;
        };

        // Create media table if it doesn't exist
        $db->query("CREATE TABLE IF NOT EXISTS `media` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `filename` varchar(255) NOT NULL,
            `original_name` varchar(255) NOT NULL,
            `path` varchar(500) NOT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `size` int(11) DEFAULT NULL,
            `width` int(11) DEFAULT NULL,
            `height` int(11) DEFAULT NULL,
            `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_filename` (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->execute();

        if (!$columnExists('posts', 'tags')) {
            $db->query("ALTER TABLE posts ADD COLUMN tags VARCHAR(500) DEFAULT NULL AFTER meta_keywords");
            $db->execute();
        }
        if (!$columnExists('posts', 'featured_image_alt')) {
            $db->query("ALTER TABLE posts ADD COLUMN featured_image_alt VARCHAR(255) DEFAULT NULL AFTER featured_image");
            $db->execute();
        }
        if (!$columnExists('users', 'monitoring_access')) {
            $db->query("ALTER TABLE users ADD COLUMN monitoring_access TINYINT(1) NOT NULL DEFAULT 0 AFTER role");
            $db->execute();
        }
        if ($columnExists('users', 'role')) {
            $db->query("ALTER TABLE users MODIFY role ENUM('admin','editor','IT') DEFAULT 'editor'");
            $db->execute();
        }
        if ($columnExists('sessions', 'id')) {
            $db->query("ALTER TABLE sessions MODIFY id VARCHAR(128) NOT NULL");
            $db->execute();
        }
        if (!$columnExists('backups', 'type')) {
            $db->query("ALTER TABLE backups ADD COLUMN type ENUM('database','full') DEFAULT 'database' AFTER size_bytes");
            $db->execute();
        }
        if (!$columnExists('backups', 'last_restored_at')) {
            $db->query("ALTER TABLE backups ADD COLUMN last_restored_at DATETIME DEFAULT NULL AFTER created_at");
            $db->execute();
        }
        $_SESSION['db_migrated_v5'] = true;
    } catch (\Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " - Migration v5: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
    }
}

if (!defined('SKIP_DB_MIGRATIONS') && !isset($_SESSION['db_migrated_v6'])) {
    try {
        $columnExists = function (string $table, string $column) use ($db): bool {
            $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column");
            $db->bind(':table', $table);
            $db->bind(':column', $column);
            $row = $db->fetch();
            return $row && (int)$row['c'] > 0;
        };

        $indexExists = function (string $table, string $index) use ($db): bool {
            $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND INDEX_NAME=:idx");
            $db->bind(':table', $table);
            $db->bind(':idx', $index);
            $row = $db->fetch();
            return $row && (int)$row['c'] > 0;
        };

        if ($columnExists('posts', 'status')) {
            $db->query("ALTER TABLE posts MODIFY status ENUM('draft','published','scheduled') DEFAULT 'draft'");
            $db->execute();
        }
        if (!$columnExists('posts', 'published_at')) {
            $db->query("ALTER TABLE posts ADD COLUMN published_at DATETIME NULL AFTER meta_keywords");
            $db->execute();
        }
        if (!$columnExists('posts', 'scheduled_at')) {
            $db->query("ALTER TABLE posts ADD COLUMN scheduled_at DATETIME NULL AFTER published_at");
            $db->execute();
        }
        if (!$indexExists('posts', 'idx_scheduled_at')) {
            $db->query("ALTER TABLE posts ADD INDEX idx_scheduled_at (scheduled_at)");
            $db->execute();
        }

        $_SESSION['db_migrated_v6'] = true;
    } catch (\Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " - Migration v6: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
    }
}

if (!defined('SKIP_DB_MIGRATIONS') && !isset($_SESSION['db_migrated_v7'])) {
    try {
        $columnExists = function (string $table, string $column) use ($db): bool {
            $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column");
            $db->bind(':table', $table);
            $db->bind(':column', $column);
            $row = $db->fetch();
            return $row && (int)$row['c'] > 0;
        };

        $columnType = function (string $table, string $column) use ($db): ?string {
            $db->query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column");
            $db->bind(':table', $table);
            $db->bind(':column', $column);
            $row = $db->fetch();
            return $row['DATA_TYPE'] ?? null;
        };

        if ($columnExists('posts', 'published_at') && $columnType('posts', 'published_at') !== 'datetime') {
            $db->query("ALTER TABLE posts MODIFY published_at DATETIME NULL");
            $db->execute();
        }
        if ($columnExists('posts', 'scheduled_at') && $columnType('posts', 'scheduled_at') !== 'datetime') {
            $db->query("ALTER TABLE posts MODIFY scheduled_at DATETIME NULL");
            $db->execute();
        }
        if ($columnExists('posts', 'created_at') && $columnType('posts', 'created_at') !== 'datetime') {
            $db->query("ALTER TABLE posts MODIFY created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            $db->execute();
        }
        if ($columnExists('posts', 'updated_at') && $columnType('posts', 'updated_at') !== 'datetime') {
            $db->query("ALTER TABLE posts MODIFY updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            $db->execute();
        }

        $_SESSION['db_migrated_v7'] = true;
    } catch (\Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " - Migration v7: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
    }
}
require_once INCLUDES_PATH . 'helpers.php';

require_once INCLUDES_PATH . 'sessionTracker.class.php';
require_once INCLUDES_PATH . 'auditLog.class.php';

$sessionTracker = new SessionTracker();
$auditLog = new AuditLog();

// Průběžně publikuj naplánované články při běžném provozu aplikace.
if (!defined('SKIP_SCHEDULED_PUBLISH')) {
    try {
        $scheduledPublisher = new Post();
        $scheduledPublisher->publishDueScheduledPosts();
    } catch (\Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " - Scheduled publish: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $sessionTracker->updateActivity();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $timeout = SESSION_LIFETIME;

    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];

        if ($elapsed > $timeout) {
            global $sessionTracker, $auditLog;
            if (isset($sessionTracker)) {
                $sessionTracker->recordLogout();
            }
            if (isset($auditLog)) {
                $auditLog->log('logout', 'user', $_SESSION['user_id'], $_SESSION['username'], 'Automatické odhlášení (timeout)');
            }

            session_unset();
            session_destroy();
            header('Location: ' . ADMIN_URL . 'login.php?timeout=1');
            exit;
        }
    }

    $_SESSION['last_activity'] = time();
}
