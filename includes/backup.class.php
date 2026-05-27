<?php
/**
 * Backup Class - KOMPLETNĚ OPRAVENÁ VERZE
 */

class Backup {
    private $db;
    private $backupDir;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->backupDir = BACKUPS_PATH;
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    private function resolveBackupPath($filepath) {
        $base = realpath($this->backupDir);
        $file = realpath((string)$filepath);

        if (!$base || !$file || !is_file($file)) {
            return false;
        }

        $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return strpos($file, $base) === 0 ? $file : false;
    }

    private function isPathInsideDirectory($path, $directory) {
        $base = realpath($directory);
        $target = realpath($path);

        if (!$base || !$target) {
            return false;
        }

        $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return strpos($target, $base) === 0 || $target === rtrim($base, DIRECTORY_SEPARATOR);
    }
    
    public function createDatabaseBackup() {
        $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupDir . $filename;
        
        $tables = $this->getTables();
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $sql .= $this->exportTable($table);
        }
        
        if (file_put_contents($filepath, $sql)) {
            $this->logBackup($filename, $filepath, filesize($filepath));
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        }
        
        return ['success' => false, 'message' => 'Nepodařilo se vytvořit zálohu'];
    }
    
  // OPRAVENÁ metoda getTables() v Backup.class.php
// Přeskoč backups tabulku aby se neobnovovala

    private function getTables() {
    $this->db->query("SHOW TABLES");
    $results = $this->db->fetchAll();
    
    $tables = [];
    foreach ($results as $row) {
        $tableName = array_values($row)[0];
        
        // Přeskoč backups tabulku - má zůstat zachovaná!
        // Jinak by se po restore smazaly nové zálohy
        if ($tableName === 'backups') {
            continue;
        }
        
        $tables[] = $tableName;
    }
    
    return $tables;
}
    private function exportTable($table) {
        $sql = "\n--\n-- Table: $table\n--\n\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n\n";
        
        $this->db->query("SHOW CREATE TABLE `$table`");
        $row = $this->db->fetch();
        $sql .= $row['Create Table'] . ";\n\n";
        
        $this->db->query("SELECT * FROM `$table`");
        $rows = $this->db->fetchAll();
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $values = array_map(function($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . addslashes($value) . "'";
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        return $sql;
    }
    
    private function logBackup($filename, $filepath, $size) {
        $userId = $_SESSION['user_id'] ?? null;
        
        $this->db->query(
            "INSERT INTO backups (filename, filepath, size_bytes, created_by) 
             VALUES (:filename, :filepath, :size, :user_id)"
        );
        $this->db->bind(':filename', $filename);
        $this->db->bind(':filepath', $filepath);
        $this->db->bind(':size', $size);
        $this->db->bind(':user_id', $userId);
        $this->db->execute();
    }
    
    private function cleanOldBackups() {
        $cutoffDate = date('Y-m-d', strtotime('-' . BACKUP_RETENTION_DAYS . ' days'));
        
        $this->db->query("SELECT filepath FROM backups WHERE DATE(created_at) < :cutoff");
        $this->db->bind(':cutoff', $cutoffDate);
        $oldBackups = $this->db->fetchAll();
        
        foreach ($oldBackups as $backup) {
            $backupPath = $this->resolveBackupPath($backup['filepath']);
            if ($backupPath) {
                unlink($backupPath);
            }
        }
        
        $this->db->query("DELETE FROM backups WHERE DATE(created_at) < :cutoff");
        $this->db->bind(':cutoff', $cutoffDate);
        $this->db->execute();
    }
    
    public function listBackups() {
        $this->db->query(
            "SELECT b.*, u.username as created_by_name 
             FROM backups b
             LEFT JOIN users u ON b.created_by = u.id
             ORDER BY b.created_at DESC"
        );
        return $this->db->fetchAll();
    }
    
    public function downloadBackup($id) {
        $this->db->query("SELECT * FROM backups WHERE id = :id");
        $this->db->bind(':id', $id);
        $backup = $this->db->fetch();
        
        $backupPath = $backup ? $this->resolveBackupPath($backup['filepath']) : false;
        if ($backupPath) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            header('Content-Length: ' . filesize($backupPath));
            header('X-Content-Type-Options: nosniff');
            readfile($backupPath);
            exit;
        }
        
        return false;
    }
    
    /**
     * Smazat zálohu - OPRAVENO
     */
    public function deleteBackup($backupId) {
        try {
            $this->db->query("SELECT filepath FROM backups WHERE id = :id");
            $this->db->bind(':id', $backupId);
            $backup = $this->db->fetch();
            
            if (!$backup) {
                return ['success' => false, 'message' => 'Záloha nenalezena'];
            }
            
            // Smazat soubor
            $backupPath = $this->resolveBackupPath($backup['filepath']);
            if ($backupPath) {
                if (!unlink($backupPath)) {
                    return ['success' => false, 'message' => 'Nepodařilo se smazat soubor'];
                }
            }
            
            // Smazat z DB
            $this->db->query("DELETE FROM backups WHERE id = :id");
            $this->db->bind(':id', $backupId);
            $this->db->execute();
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Obnovit zálohu - ROBUSTNÍ VERZE
     */
    public function restoreBackup($backupId) {
        try {
            $this->db->query("SELECT filepath FROM backups WHERE id = :id");
            $this->db->bind(':id', $backupId);
            $backup = $this->db->fetch();
            
            if (!$backup) {
                return ['success' => false, 'message' => 'Záloha nenalezena'];
            }
            
            $backupPath = $this->resolveBackupPath($backup['filepath']);
            if (!$backupPath) {
                return ['success' => false, 'message' => 'Soubor zálohy neexistuje'];
            }
            
            // Načíst SQL ze zálohy
            $sql = file_get_contents($backupPath);
            
            if (!$sql) {
                return ['success' => false, 'message' => 'Nepodařilo se načíst zálohu'];
            }
            
            // KROK 1: Vypnout foreign key checks
            $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->execute();
            
            // KROK 2: Rozdělit SQL na příkazy (lepší parsování)
            $statements = [];
            $currentStatement = '';
            $lines = explode("\n", $sql);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Přeskočit komentáře a prázdné řádky
                if (empty($line) || 
                    substr($line, 0, 2) === '--' || 
                    substr($line, 0, 2) === '/*') {
                    continue;
                }
                
                $currentStatement .= $line . "\n";
                
                // Konec příkazu
                if (substr($line, -1) === ';') {
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                }
            }
            
            // KROK 3: Provést každý příkaz
            $errors = [];
            foreach ($statements as $statement) {
                if (empty(trim($statement))) continue;
                
                try {
                    $this->db->query($statement);
                    $this->db->execute();
                } catch (PDOException $e) {
                    // Logovat chybu ale pokračovat
                    $errors[] = substr($e->getMessage(), 0, 100);
                    continue;
                }
            }
            
            // KROK 4: Zapnout foreign key checks zpět
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
            $this->db->execute();
            
            if (count($errors) > 0) {
                return [
                    'success' => true,
                    'message' => 'Záloha byla obnovena s ' . count($errors) . ' varováními.'
                ];
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Kritická chyba: ' . $e->getMessage()];
        }
    }
// PŘIDEJ TUTO METODU do Backup.class.php

/**
 * Vytvoření KOMPLETNÍ zálohy (DB + soubory)
 */
// OPRAVENÁ metoda createFullBackup() - BEZ duplicity

// KOMPLETNĚ OPRAVENÁ createFullBackup() metoda
// Nahraď celou metodu v Backup.class.php

public function createFullBackup() {
    try {
        // 1. Vytvoř SQL dump (BEZ uložení do DB)
        $filename = 'database_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupDir . $filename;
        
        $tables = $this->getTables();
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $sql .= $this->exportTable($table);
        }
        
        if (!file_put_contents($filepath, $sql)) {
            return ['success' => false, 'message' => 'Nepodařilo se vytvořit SQL'];
        }
        
        // 2. Vytvoř ZIP
        $zipFilename = 'full_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $this->backupDir . $zipFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            unlink($filepath);
            return ['success' => false, 'message' => 'Nelze vytvořit ZIP'];
        }
        
        // 3. Přidej SQL do ZIPu
        $zip->addFile($filepath, 'database.sql');
        
        // 4. Přidej všechny soubory z uploads REKURZIVNĚ
        $uploadsDir = rtrim(UPLOADS_PATH, '/');
        
        if (is_dir($uploadsDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadsDir) + 1);
                    
                    // Normalizuj path separátory
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        
        $zip->close();
        
        // 5. Smaž dočasný SQL
        unlink($filepath);
        
        // 6. Ulož JEN ZIP zálohu do DB
        $this->db->query(
            "INSERT INTO backups (filename, filepath, size_bytes, created_by, type) 
             VALUES (:filename, :filepath, :size, :user_id, 'full')"
        );
        $this->db->bind(':filename', $zipFilename);
        $this->db->bind(':filepath', $zipPath);
        $this->db->bind(':size', filesize($zipPath));
        $this->db->bind(':user_id', $_SESSION['user_id'] ?? null);
        $this->db->execute();
        
        $this->cleanOldBackups();
        
        return [
            'success' => true,
            'filename' => $zipFilename,
            'size' => filesize($zipPath)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// OPRAVENÁ restoreFullBackup() metoda
public function restoreFullBackup($backupId) {
    try {
        $this->db->query("SELECT filepath, type FROM backups WHERE id = :id");
        $this->db->bind(':id', $backupId);
        $backup = $this->db->fetch();
        
        if (!$backup) {
            return ['success' => false, 'message' => 'Záloha nenalezena'];
        }
        
        // Je to ZIP záloha?
        $backupPath = $this->resolveBackupPath($backup['filepath']);
        if ($backup['type'] === 'full' && $backupPath && pathinfo($backupPath, PATHINFO_EXTENSION) === 'zip') {
            $zip = new ZipArchive();
            
            if ($zip->open($backupPath) !== TRUE) {
                return ['success' => false, 'message' => 'Nelze otevřít ZIP'];
            }
            
            // 1. Extrahuj SQL
            $sqlContent = $zip->getFromName('database.sql');
            
            if (!$sqlContent) {
                $zip->close();
                return ['success' => false, 'message' => 'SQL soubor nenalezen v záloze'];
            }
            
            // 2. Obnov databázi
            $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->execute();
            
            $statements = [];
            $currentStatement = '';
            $lines = explode("\n", $sqlContent);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line) || substr($line, 0, 2) === '--') {
                    continue;
                }
                
                $currentStatement .= $line . "\n";
                
                if (substr($line, -1) === ';') {
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                }
            }
            
            foreach ($statements as $stmt) {
                if (empty(trim($stmt))) continue;
                
                try {
                    $this->db->query($stmt);
                    $this->db->execute();
                } catch (Exception $e) {
                    continue;
                }
            }
            
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
            $this->db->execute();
            
            // 3. Obnov soubory
            $uploadsDir = rtrim(UPLOADS_PATH, '/\\');
            
            // Smazat staré uploads (kromě .htaccess a index.php)
            if (is_dir($uploadsDir)) {
                $items = glob($uploadsDir . '/*');
                foreach ($items as $item) {
                    $basename = basename($item);
                    if ($basename !== '.htaccess' && $basename !== 'index.php') {
                        if (is_file($item)) {
                            unlink($item);
                        } elseif (is_dir($item)) {
                            $this->deleteFolder($item);
                            @rmdir($item);
                        }
                    }
                }
            } else {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Extrahovat soubory z uploads/ v ZIPu
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                // Normalizuj path
                $filename = str_replace('\\', '/', $filename);
                
                if (strpos($filename, 'uploads/') === 0 && $filename !== 'uploads/') {
                    $localName = substr($filename, 8); // Odřízni "uploads/"
                    if (
                        $localName === '' ||
                        strpos($localName, "\0") !== false ||
                        strpos($localName, '../') !== false ||
                        strpos($localName, '/..') !== false ||
                        strpos($localName, '..\\') !== false ||
                        preg_match('/^[a-zA-Z]:/', $localName) ||
                        substr($localName, 0, 1) === '/'
                    ) {
                        continue;
                    }
                    $localPath = $uploadsDir . '/' . $localName;
                    
                    if (substr($filename, -1) === '/') {
                        // Je to složka
                        if (!is_dir($localPath)) {
                            mkdir($localPath, 0755, true);
                        }
                    } else {
                        // Je to soubor
                        $dir = dirname($localPath);
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        if (!$this->isPathInsideDirectory($dir, $uploadsDir)) {
                            continue;
                        }
                        
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            file_put_contents($localPath, $content);
                        }
                    }
                }
            }
            
            $zip->close();
            
            return ['success' => true, 'message' => 'Kompletní záloha obnovena'];
            
        } else {
            // Stará záloha (jen DB)
            return $this->restoreBackup($backupId);
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Přidat složku do ZIPu
 */
private function addFolderToZip($folder, &$zipFile, $zipPath = '') {
    if (!is_dir($folder)) return;
    
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $folder . '/' . $file;
        
        if (is_dir($filePath)) {
            $this->addFolderToZip($filePath, $zipFile, $zipPath . $file . '/');
        } else {
            $zipFile->addFile($filePath, $zipPath . $file);
        }
    }
}



/**
 * Smazat složku rekurzivně
 */
private function deleteFolder($folder) {
    if (!is_dir($folder)) return;
    
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $folder . '/' . $file;
        
        if (is_dir($path)) {
            $this->deleteFolder($path);
        } else {
            unlink($path);
        }
    }
}

/**
 * Přesunout soubory
 */
private function moveFiles($source, $dest) {
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $sourcePath = $source . '/' . $file;
        $destPath = $dest . '/' . $file;
        
        if (is_dir($sourcePath)) {
            mkdir($destPath, 0755, true);
            $this->moveFiles($sourcePath, $destPath);
        } else {
            rename($sourcePath, $destPath);
        }
    }
}
}
?>
