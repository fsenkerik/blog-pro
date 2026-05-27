<?php
/**
 * admin/download_backup.php
 * Stahovani zaloh
 */

define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

if (($_SESSION['user_role'] ?? '') !== 'IT') {
    http_response_code(403);
    exit('Stahovani zaloh je dostupne jen pro roli IT.');
}

$backupId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$backupId) {
    http_response_code(400);
    exit('Neplatne ID zalohy.');
}

$db = new Database();
$db->query("SELECT * FROM backups WHERE id = :id");
$db->bind(':id', $backupId);
$backup = $db->fetch();

if (!$backup) {
    http_response_code(404);
    exit('Zaloha nebyla nalezena.');
}

$backupsDir = realpath(BACKUPS_PATH);
$candidatePath = !empty($backup['filepath'])
    ? $backup['filepath']
    : BACKUPS_PATH . basename((string)$backup['filename']);
$filePath = realpath($candidatePath);

if (!$backupsDir || !$filePath) {
    http_response_code(404);
    exit('Soubor zalohy nebyl nalezen.');
}

$backupsDir = rtrim($backupsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strpos($filePath, $backupsDir) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('Soubor zalohy nebyl nalezen.');
}

$downloadName = str_replace(["\\", '"', "\r", "\n"], '', basename((string)$backup['filename']));
if ($downloadName === '') {
    $downloadName = basename($filePath);
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');

if (ob_get_level()) {
    ob_clean();
}
flush();
readfile($filePath);
exit;
