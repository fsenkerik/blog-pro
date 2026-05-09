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

// Auto-migrate: přidat nové sloupce pokud ještě neexistují
if (!isset($_SESSION['db_migrated_v3'])) {
    try {
        $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='tags'");
        $r = $db->fetch();
        if (!$r || (int)$r['c'] === 0) {
            $db->query("ALTER TABLE posts ADD COLUMN tags VARCHAR(500) DEFAULT NULL AFTER meta_keywords");
            $db->execute();
        }
        $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='featured_image_alt'");
        $r = $db->fetch();
        if (!$r || (int)$r['c'] === 0) {
            $db->query("ALTER TABLE posts ADD COLUMN featured_image_alt VARCHAR(255) DEFAULT NULL AFTER featured_image");
            $db->execute();
        }
    } catch (\Throwable $e) {}
    $_SESSION['db_migrated_v3'] = true;
}

require_once INCLUDES_PATH . 'helpers.php';

require_once INCLUDES_PATH . 'sessionTracker.class.php';
require_once INCLUDES_PATH . 'auditLog.class.php';

$sessionTracker = new SessionTracker();
$auditLog = new AuditLog();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $sessionTracker->updateActivity();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $timeout = 5 * 60;

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
