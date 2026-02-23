<?php
/**
 * admin/download_backup.php
 * Stahování záloh
 */

define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$backupId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$backupId) {
    die('Neplatné ID zálohy');
}

$db = new Database();
$db->query("SELECT * FROM backups WHERE id = :id");
$db->bind(':id', $backupId);
$backup = $db->fetch();

if (!$backup) {
    die('Záloha nenalezena');
}

// Sestavit cestu k souboru
$backupsDir = ROOT_PATH . 'backups/';
$filePath = $backupsDir . $backup['filename'];

// Kontrola že soubor existuje
if (!file_exists($filePath)) {
    die('Soubor zálohy neexistuje: ' . $backup['filename'] . '<br>Hledáno v: ' . $filePath);
}

// Nastavení headers pro stažení
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($backup['filename']) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Vyčistit output buffer
ob_clean();
flush();

// Poslat soubor
readfile($filePath);
exit;
?>