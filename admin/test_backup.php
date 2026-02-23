<?php
// TEST SCRIPT - Zkontroluj co je v ZIP záloze
// Ulož jako test_backup.php do admin složky

define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$backup = new Backup();
$backups = $backup->listBackups();

echo "<h1>Test obsahu záloh</h1>";

foreach ($backups as $b) {
    if ($b['type'] === 'full' && pathinfo($b['filepath'], PATHINFO_EXTENSION) === 'zip') {
        echo "<h2>Záloha: " . $b['filename'] . "</h2>";
        echo "<p>Cesta: " . $b['filepath'] . "</p>";
        
        if (!file_exists($b['filepath'])) {
            echo "<p style='color:red'>❌ Soubor neexistuje!</p>";
            continue;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($b['filepath']) === TRUE) {
            echo "<p>✅ ZIP otevřen, soubory: " . $zip->numFiles . "</p>";
            echo "<ul>";
            
            $hasDatabase = false;
            $hasUploads = false;
            $uploadFiles = [];
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                echo "<li>" . $name;
                
                if ($name === 'database.sql') {
                    $hasDatabase = true;
                    $size = $zip->statIndex($i)['size'];
                    echo " (" . round($size/1024, 2) . " KB)";
                }
                
                if (strpos($name, 'uploads/') === 0) {
                    $hasUploads = true;
                    $uploadFiles[] = $name;
                    echo " ← UPLOAD";
                }
                
                echo "</li>";
            }
            
            echo "</ul>";
            
            if ($hasDatabase) {
                echo "<p style='color:green'>✅ database.sql PŘÍTOMEN</p>";
            } else {
                echo "<p style='color:red'>❌ database.sql CHYBÍ!</p>";
            }
            
            if ($hasUploads) {
                echo "<p style='color:green'>✅ Uploads PŘÍTOMNY (" . count($uploadFiles) . " souborů)</p>";
            } else {
                echo "<p style='color:red'>❌ Uploads CHYBÍ!</p>";
            }
            
            $zip->close();
        } else {
            echo "<p style='color:red'>❌ Nelze otevřít ZIP</p>";
        }
        
        echo "<hr>";
    }
}

echo "<h2>Aktuální uploads:</h2>";
echo "<p>Cesta: " . UPLOADS_PATH . "</p>";
$files = glob(UPLOADS_PATH . '/*');
echo "<ul>";
foreach ($files as $file) {
    echo "<li>" . basename($file) . "</li>";
}
echo "</ul>";
?>