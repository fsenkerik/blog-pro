<?php
define('BLOG_PRO', true);
define('SKIP_SCHEDULED_PUBLISH', true);

require_once dirname(__DIR__) . '/config.php';

try {
    $publisher = new Post();
    $publishedCount = $publisher->publishDueScheduledPosts();

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => true,
        'published' => $publishedCount,
        'checked_at' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
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