<?php
define('BLOG_PRO', true);
define('SKIP_SCHEDULED_PUBLISH', true);
define('SKIP_DB_MIGRATIONS', true);

require_once dirname(__DIR__) . '/config.php';

try {
    if (PHP_SAPI !== 'cli') {
        $configuredToken = getenv('CRON_TOKEN') ?: '';
        $requestToken = $_GET['token'] ?? '';

        if ($configuredToken === '' || !hash_equals($configuredToken, $requestToken)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden',
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
            exit;
        }
    }

    $lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'blog_pro_publish_' . md5(ROOT_PATH) . '.lock';
    $lockHandle = fopen($lockPath, 'c');

    if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => true,
            'published' => 0,
            'skipped' => 'already_running',
            'checked_at' => currentLocalDateTimeString(),
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit;
    }

    $publisher = new Post();
    $publishedCount = $publisher->publishDueScheduledPosts();

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => true,
        'published' => $publishedCount,
        'checked_at' => currentLocalDateTimeString(),
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
} catch (Throwable $e) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    error_log(date('Y-m-d H:i:s') . " - Scheduled publish cron: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');

    echo json_encode([
        'success' => false,
        'message' => 'Scheduled publishing failed',
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
