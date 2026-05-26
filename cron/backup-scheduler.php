<?php

define('BLOG_PRO', true);
define('SKIP_SCHEDULED_PUBLISH', true);

require_once dirname(__DIR__) . '/config.php';

function backupCronJson(array $payload, int $status = 200): void {
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}

function backupCronDueByTime(
    DateTimeImmutable $now,
    string $time,
    string $lastRun,
    DateInterval $interval,
    ?int $weekday = null
): bool {
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return false;
    }

    if ($weekday !== null && (int)$now->format('w') !== $weekday) {
        return false;
    }

    $scheduledToday = $now->setTime((int)substr($time, 0, 2), (int)substr($time, 3, 2), 0);
    if ($now < $scheduledToday) {
        return false;
    }

    if ($lastRun === '') {
        return true;
    }

    try {
        $lastRunAt = new DateTimeImmutable($lastRun, appTimezone());
    } catch (Throwable $e) {
        return true;
    }

    if ($lastRunAt >= $scheduledToday) {
        return false;
    }

    return $lastRunAt->add($interval) <= $now;
}

try {
    if (PHP_SAPI !== 'cli') {
        $configuredToken = getenv('CRON_TOKEN') ?: '';
        $requestToken = $_GET['token'] ?? '';

        if ($configuredToken === '' || !hash_equals($configuredToken, $requestToken)) {
            backupCronJson([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
            exit;
        }
    }

    $lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'blog_pro_backups_' . md5(ROOT_PATH) . '.lock';
    $lockHandle = fopen($lockPath, 'c');

    if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
        backupCronJson([
            'success' => true,
            'skipped' => 'already_running',
            'checked_at' => currentLocalDateTimeString(),
        ]);
        exit;
    }

    $settings = (new AppSettings())->getMany([
        'auto_db_backup_enabled' => '1',
        'auto_db_backup_interval_hours' => '24',
        'auto_db_backup_time' => '01:00',
        'auto_db_backup_last_run' => '',
        'auto_full_backup_enabled' => '1',
        'auto_full_backup_interval_days' => '7',
        'auto_full_backup_weekday' => '0',
        'auto_full_backup_time' => '02:00',
        'auto_full_backup_last_run' => '',
    ]);

    $now = new DateTimeImmutable('now', appTimezone());
    $backup = new Backup();
    $appSettings = new AppSettings();
    $results = [];

    $dbIntervalHours = max(1, min(168, (int)$settings['auto_db_backup_interval_hours']));
    $dbDue = $settings['auto_db_backup_enabled'] === '1'
        && backupCronDueByTime(
            $now,
            $settings['auto_db_backup_time'],
            $settings['auto_db_backup_last_run'],
            new DateInterval('PT' . $dbIntervalHours . 'H')
        );

    if ($dbDue) {
        $result = $backup->createDatabaseBackup();
        if (!empty($result['success'])) {
            $appSettings->set('auto_db_backup_last_run', currentLocalDateTimeString());
        }
        $results['database'] = $result;
    } else {
        $results['database'] = ['success' => true, 'skipped' => 'not_due'];
    }

    $fullIntervalDays = max(1, min(30, (int)$settings['auto_full_backup_interval_days']));
    $fullWeekday = max(0, min(6, (int)$settings['auto_full_backup_weekday']));
    $fullDue = $settings['auto_full_backup_enabled'] === '1'
        && backupCronDueByTime(
            $now,
            $settings['auto_full_backup_time'],
            $settings['auto_full_backup_last_run'],
            new DateInterval('P' . $fullIntervalDays . 'D'),
            $fullWeekday
        );

    if ($fullDue) {
        $result = $backup->createFullBackup();
        if (!empty($result['success'])) {
            $appSettings->set('auto_full_backup_last_run', currentLocalDateTimeString());
        }
        $results['full'] = $result;
    } else {
        $results['full'] = ['success' => true, 'skipped' => 'not_due'];
    }

    backupCronJson([
        'success' => true,
        'checked_at' => currentLocalDateTimeString(),
        'results' => $results,
    ]);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
} catch (Throwable $e) {
    error_log(date('Y-m-d H:i:s') . " - Backup cron: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');

    backupCronJson([
        'success' => false,
        'message' => 'Backup scheduler failed',
    ], 500);
}
