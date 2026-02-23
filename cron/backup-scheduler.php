<?php
/**
 * Automatický plánovač záloh
 * Spouštět každou hodinu přes cron/Task Scheduler
 */

define('BLOG_PRO', true);
require_once __DIR__ . '/../config.php';

$db = new Database();
$backup = new Backup();

echo "[" . date('Y-m-d H:i:s') . "] Kontrolujem automatické zálohy...\n";

// Získat všechna aktivní nastavení
$db->query("
    SELECT * FROM backup_schedule 
    WHERE enabled = 1
");
$schedules = $db->fetchAll();

foreach ($schedules as $schedule) {
    echo "Kontrolujem: {$schedule['backup_type']} zálohu...\n";
    
    // Zjistit zda je čas spustit zálohu
    $shouldRun = false;
    $now = new DateTime();
    $scheduledTime = DateTime::createFromFormat('H:i:s', $schedule['time']);
    $currentHour = (int)$now->format('H');
    $scheduledHour = (int)$scheduledTime->format('H');
    
    // Kontrola zda běžíme ve správnou hodinu
    if ($currentHour !== $scheduledHour) {
        echo "  → Není čas (plánováno: {$scheduledHour}:xx, teď je: {$currentHour}:xx)\n";
        continue;
    }
    
    // Kontrola zda už dnes běželo
    $lastRun = $schedule['last_run'] ? new DateTime($schedule['last_run']) : null;
    if ($lastRun && $lastRun->format('Y-m-d') === $now->format('Y-m-d')) {
        echo "  → Už dnes běželo v " . $lastRun->format('H:i') . "\n";
        continue;
    }
    
    // Kontrola frekvence
    switch ($schedule['frequency']) {
        case 'daily':
            $shouldRun = true;
            break;
            
        case 'weekly':
            $dayOfWeek = (int)$now->format('N'); // 1=Po, 7=Ne
            if ($dayOfWeek === (int)$schedule['day_of_week']) {
                $shouldRun = true;
            }
            break;
            
        case 'monthly':
            $dayOfMonth = (int)$now->format('j');
            if ($dayOfMonth === (int)$schedule['day_of_month']) {
                $shouldRun = true;
            }
            break;
    }
    
    if (!$shouldRun) {
        echo "  → Dnes neběží (frekvence: {$schedule['frequency']})\n";
        continue;
    }
    
    // SPUSTIT ZÁLOHU
    echo "  ✓ Spouštím zálohu...\n";
    
    if ($schedule['backup_type'] === 'database') {
        $result = $backup->createDatabaseBackup(0); // 0 = systémová záloha
    } else {
        $result = $backup->createFullBackup(0);
    }
    
    if ($result['success']) {
        echo "  ✓ Záloha vytvořena úspěšně!\n";
        
        // Aktualizovat last_run
        $db->query("
            UPDATE backup_schedule 
            SET last_run = NOW() 
            WHERE id = :id
        ");
        $db->bind(':id', $schedule['id']);
        $db->execute();
    } else {
        echo "  ✗ Chyba při vytváření zálohy\n";
    }
}

echo "\n=== Automatické zálohy dokončeny ===\n";
?>