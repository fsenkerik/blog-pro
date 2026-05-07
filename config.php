<?php
/**
 * Blog Pro - Main Configuration
 * Profesionální blog systém s MySQL databází
 */

// Zabránit přímému přístupu
if (!defined('BLOG_PRO')) {
    define('BLOG_PRO', true);
}

// Error reporting (vypnuto na produkci, zapnout lokálně)
$isLocal = (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === false || getenv('APP_ENV') === '');
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Prague');

// Database Configuration
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'blog_pro');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: 'root');
define('DB_CHARSET', 'utf8mb4');

// Paths
define('ROOT_PATH',    dirname(__FILE__) . '/');
define('ADMIN_PATH',   ROOT_PATH . 'admin/');
define('INCLUDES_PATH',ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('BACKUPS_PATH', ROOT_PATH . 'backups/');
define('ASSETS_PATH',  ROOT_PATH . 'assets/');

// URLs
$baseUrl = getenv('BASE_URL') ?: 'http://localhost:8888/blog-pro/';
define('BASE_URL',    $baseUrl);
define('ADMIN_URL',   BASE_URL . 'admin/');
define('ASSETS_URL',  BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Security
define('SESSION_LIFETIME',    3600 * 2);
define('MAX_LOGIN_ATTEMPTS',  5);
define('LOGIN_TIMEOUT',       900);

// Upload settings
define('MAX_UPLOAD_SIZE',      5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// SEO defaults
define('SITE_NAME',        'Blog Pro');
define('SITE_DESCRIPTION', 'Profesionální blog systém');
define('SITE_KEYWORDS',    'blog, novinky, články');

// Pagination
define('POSTS_PER_PAGE', 12);

// Backup settings
define('AUTO_BACKUP_ENABLED',   true);
define('BACKUP_RETENTION_DAYS', 30);

// Image optimization
define('IMAGE_MAX_WIDTH',   1920);
define('IMAGE_MAX_HEIGHT',  1080);
define('IMAGE_QUALITY',     85);
define('THUMBNAIL_WIDTH',   400);
define('THUMBNAIL_HEIGHT',  300);

// Autoload classes
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . strtolower($class) . '.class.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start session with security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include essential classes
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

// Initialize database connection
$db = new Database();

// Helper functions
require_once INCLUDES_PATH . 'helpers.php';

// Monitoring classes
require_once INCLUDES_PATH . 'SessionTracker.class.php';
require_once INCLUDES_PATH . 'AuditLog.class.php';

// Inicializace
$sessionTracker = new SessionTracker();
$auditLog = new AuditLog();

// Auto-update aktivity při každém requestu
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $sessionTracker->updateActivity();
}

// ===== AUTOMATICKÉ ODHLÁŠENÍ =====
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
