<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();
$backup = new Backup();

// Ensure monitoring_access column exists
try {
    $db_alter = new Database();
    $db_alter->query("ALTER TABLE users ADD COLUMN monitoring_access TINYINT(1) NOT NULL DEFAULT 1");
    $db_alter->execute();
} catch (\Throwable $e) {} // Column already exists - ignore

$success = '';
$error = '';
// Ochrana IT účtů - pouze IT může upravovat IT uživatele
if (isPost()) {
    $action = post('action');
    
    if (in_array($action, ['delete_user', 'change_user_password'])) {
        $targetUserId = intval(post('user_id'));
        
        // Zjistit roli cílového uživatele
        $db_check = new Database();
        $db_check->query("SELECT role FROM users WHERE id = :id");
        $db_check->bind(':id', $targetUserId);
        $targetUser = $db_check->fetch();
        
        // Pokud je cíl IT a aktuální user není IT, zamítnout
        if ($targetUser && $targetUser['role'] === 'IT' && $_SESSION['user_role'] !== 'IT') {
            setFlash('error', 'Nemáte oprávnění upravovat IT uživatele');
            redirect(ADMIN_URL . 'settings.php');
            exit;
        }
    }
}
if (isPost()) {
    if (!verifyCsrf()) {
        $error = 'Neplatný CSRF token';
    } else {
        $action = post('action');
        
        if ($action === 'change_password') {
            $result = $auth->changePassword(
                $_SESSION['user_id'],
                post('current_password'),
                post('new_password')
            );
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'generate_sitemap') {
            SEO::generateSitemap();
            SEO::generateRobots();
            $success = 'Sitemap a robots.txt byly vygenerovány!';
        } elseif ($action === 'create_backup') {
            $result = $backup->createDatabaseBackup();
            if ($result['success']) {
                $success = 'Záloha databáze byla vytvořena!';
            } else {
                $error = 'Nepodařilo se vytvořit zálohu';
            }
        } elseif ($action === 'delete_backup') {
            $backupId = intval(post('backup_id'));
            $result = $backup->deleteBackup($backupId);
            if ($result['success']) {
                $success = 'Záloha byla smazána!';
            } else {
                $error = 'Nepodařilo se smazat zálohu';
            }
        } elseif ($action === 'restore_backup') {
            $backupId = intval(post('backup_id'));
            
            // Zjisti typ zálohy
            $db = new Database();
            $db->query("SELECT type FROM backups WHERE id = :id");
            $db->bind(':id', $backupId);
            $backupInfo = $db->fetch();
            
            // Obnov podle typu
            if ($backupInfo && $backupInfo['type'] === 'full') {
                $result = $backup->restoreFullBackup($backupId);
            } else {
                $result = $backup->restoreBackup($backupId);
            }
            
            if ($result['success']) {
                // KROK 1: Resetuj všechny předchozí restore značky
                $db->query("UPDATE backups SET last_restored_at = NULL");
                $db->execute();
                
                // KROK 2: Označ JEN tuto zálohu jako aktuálně obnovenou
                $db->query("UPDATE backups SET last_restored_at = NOW() WHERE id = :id");
                $db->bind(':id', $backupId);
                $db->execute();
                
                $success = 'Záloha byla úspěšně obnovena! Databáze je nyní ve stavu ze zálohy.';
            } else {
                $error = 'Nepodařilo se obnovit zálohu: ' . ($result['message'] ?? '');
            }
        
        } elseif ($action === 'update_backup_schedule') {
            $db = new Database();
            
            // Databázové zálohy
            $dbEnabled = isset($_POST['db_enabled']) ? 1 : 0;
            $dbFreq = post('db_frequency');
            $dbTime = post('db_time') . ':00';
            
            $db->query("
                UPDATE backup_schedule 
                SET enabled = :enabled, frequency = :freq, time = :time
                WHERE backup_type = 'database'
            ");
            $db->bind(':enabled', $dbEnabled);
            $db->bind(':freq', $dbFreq);
            $db->bind(':time', $dbTime);
            $db->execute();
            
            // Kompletní zálohy
            $fullEnabled = isset($_POST['full_enabled']) ? 1 : 0;
            $fullFreq = post('full_frequency');
            $fullTime = post('full_time') . ':00';
            
            $db->query("
                UPDATE backup_schedule 
                SET enabled = :enabled, frequency = :freq, time = :time
                WHERE backup_type = 'full'
            ");
            $db->bind(':enabled', $fullEnabled);
            $db->bind(':freq', $fullFreq);
            $db->bind(':time', $fullTime);
            $db->execute();
            
            $success = 'Nastavení automatických záloh bylo uloženo!';
        
        
        } elseif ($action === 'create_full_backup') {
            $result = $backup->createFullBackup();
            if ($result['success']) {
                $success = 'Kompletní záloha (DB + soubory) byla vytvořena!';
            } else {
                $error = 'Nepodařilo se vytvořit kompletní zálohu';
            }
        
        } elseif ($action === 'add_user') {
            $username = trim(post('username'));
            $password = post('password');
            $role = post('role', 'editor');
            
            // IT uživatel může přidávat pouze další IT uživatele
            if ($_SESSION['user_role'] === 'IT' && $role !== 'IT') {
                $error = 'IT role může přidávat pouze IT uživatele.';
            } else {
                // Zkontrolovat zda username již neexistuje
                $db = new Database();
                $db->query("SELECT id FROM users WHERE username = :username");
                $db->bind(':username', $username);
                if ($db->fetch()) {
                    $error = 'Uživatelské jméno již existuje!';
                } else {
                    // Vytvořit uživatele
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    $db->query("INSERT INTO users (username, password, role, monitoring_access, created_at) VALUES (:username, :password, :role, :mon, NOW())");
                    $db->bind(':username', $username);
                    $db->bind(':password', $hashedPassword);
                    $db->bind(':role', $role);
                    $db->bind(':mon', ($role === 'IT') ? 1 : 0);
                    
                    if ($db->execute()) {
                        $success = 'Uživatel byl vytvořen!';
                    } else {
                        $error = 'Nepodařilo se vytvořit uživatele';
                    }
                }
            }
        } elseif ($action === 'delete_user') {
            $userId = intval(post('user_id'));
            if ($userId === $_SESSION['user_id']) {
                $error = 'Nemůžete smazat sám sebe!';
            } else {
                $db = new Database();
                $db->query("DELETE FROM users WHERE id = :id");
                $db->bind(':id', $userId);
                if ($db->execute()) {
                    $success = 'Uživatel byl smazán!';
                } else {
                    $error = 'Nepodařilo se smazat uživatele';
                }
            }
        } elseif ($action === 'change_user_password') {
            $userId = intval(post('user_id'));
            $newPassword = post('new_password');
            
            $db = new Database();
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->query("UPDATE users SET password = :password WHERE id = :id");
            $db->bind(':password', $hashedPassword);
            $db->bind(':id', $userId);
            
            if ($db->execute()) {
                $success = 'Heslo bylo změněno!';
            } else {
                $error = 'Nepodařilo se změnit heslo';
            }
        } elseif ($action === 'toggle_monitoring_access') {
            $userId = intval(post('user_id'));
            $db = new Database();
            $db->query("UPDATE users SET monitoring_access = NOT monitoring_access WHERE id = :id AND role = 'IT'");
            $db->bind(':id', $userId);
            if ($db->execute()) {
                $success = 'Přístup k monitoringu byl upraven!';
            } else {
                $error = 'Nepodařilo se změnit přístup k monitoringu';
            }
        }
    }
}

$backups = $backup->listBackups();

$db3 = new Database();
$db3->query("
    SELECT b.*, 
           CASE 
               WHEN b.created_by = 0 OR b.created_by IS NULL THEN 'Systém'
               ELSE u.username
           END as created_by_name
    FROM backups b
    LEFT JOIN users u ON b.created_by = u.id
    ORDER BY b.created_at DESC
");
$backups = $db3->fetchAll();

// Načíst všechny uživatele
$db = new Database();
$db->query("SELECT id, username, role, monitoring_access, created_at FROM users ORDER BY created_at DESC");
$users = $db->fetchAll();

// Načíst aktuálního uživatele
$db->query("SELECT id, username, role FROM users WHERE id = :id");
$db->bind(':id', $_SESSION['user_id']);
$currentUser = $db->fetch();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nastavení - Blog Pro Admin</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        /* Header */
        header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 70px; }
        .header-title { font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header-link { text-decoration: none; color: #4a5568; font-weight: 600; transition: color 0.2s; }
        .header-link:hover { color: #667eea; }
        
        /* Container */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 30px; }
        
        /* Alert */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; animation: slideIn 0.3s; }
        .alert-success { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
        .alert-error { background: #fed7d7; color: #742a2a; border-left: 4px solid #fc8181; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Cards */
        .card { background: white; border-radius: 16px; padding: 30px; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 15px; color: #2d3748; font-size: 22px; display: flex; align-items: center; gap: 10px; }
        .card-description { color: #718096; margin-bottom: 20px; line-height: 1.6; font-size: 15px; }
        .card-description strong { color: #2d3748; }
        
        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        
        /* Buttons */
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-success { background: linear-gradient(135deg, #48bb78, #38a169); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(72,187,120,0.4); }
        .btn-danger { background: linear-gradient(135deg, #fc8181, #f56565); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(252,129,129,0.4); }
        .btn-warning { background: linear-gradient(135deg, #f6ad55, #ed8936); color: white; }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(246,173,85,0.4); }
        .btn-small { padding: 8px 16px; font-size: 13px; }
        
        /* Table */
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table thead { background: #f7fafc; }
        .table th { padding: 12px; text-align: left; font-weight: 600; color: #2d3748; border-bottom: 2px solid #e2e8f0; }
        .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .table tr:hover { background: #f7fafc; }
        .table-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        
        /* Badge */
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-admin { background: #667eea; color: white; }
        .badge-editor { background: #48bb78; color: white; }
        .badge-IT { background: #9f7aea; color: white; }

        /* Toggle switch */
        .toggle-btn { padding: 6px 14px; font-size: 12px; border: none; border-radius: 20px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .toggle-btn.on { background: #48bb78; color: white; }
        .toggle-btn.off { background: #e2e8f0; color: #4a5568; }
        .toggle-btn:hover { transform: scale(1.05); }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 20px; color: #2d3748; }
        .modal-close { background: none; border: none; font-size: 28px; color: #a0aec0; cursor: pointer; padding: 0; }
        .modal-close:hover { color: #4a5568; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        
        /* Empty state */
        .empty-state { text-align: center; padding: 40px; color: #a0aec0; }
        .empty-state-icon { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="header-title">⚙️ Nastavení</div>
            <nav>
                <a href="dashboard.php" class="header-link">← Dashboard</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Změna hesla -->
        <div class="card">
            <h2>🔒 Změna hesla</h2>
            <p class="card-description">Změňte si heslo pro přihlášení do administrace.</p>
            <form method="POST">
                <?= Security::tokenInput() ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label>Současné heslo</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>Nové heslo (min. 6 znaků)</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">Změnit heslo</button>
            </form>
        </div>

        <!-- SEO -->
        <div class="card">
            <h2>🔍 SEO Optimalizace</h2>
            <p class="card-description">
                <strong>Co to je:</strong> Sitemap je seznam všech vašich stránek, který pomáhá Googlu rychleji objevit a zaindexovat váš obsah.<br>
                <strong>Kdy použít:</strong> Po přidání nových článků nebo změnách na webu.<br>
                <strong>Výsledek:</strong> Lepší viditelnost ve vyhledávačích a rychlejší indexování nového obsahu.
            </p>
            <form method="POST">
                <?= Security::tokenInput() ?>
                <input type="hidden" name="action" value="generate_sitemap">
                <button type="submit" class="btn btn-success">✓ Generovat Sitemap & Robots.txt</button>
            </form>
        </div>

<!-- Zálohy -->
<div class="card" id="backups">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>💾 Zálohy</h2>
        <div style="display: flex; gap: 10px;">
            <?php
            // Načíst nastavení automatických záloh
            $dbSched = new Database();
            $dbSched->query("SELECT * FROM backup_schedule WHERE backup_type = 'database'");
            $dbSchedule = $dbSched->fetch();
            
            $dbSched2 = new Database();
            $dbSched2->query("SELECT * FROM backup_schedule WHERE backup_type = 'full'");
            $fullSchedule = $dbSched2->fetch();
            
            $autoEnabled = ($dbSchedule && $dbSchedule['enabled']) || ($fullSchedule && $fullSchedule['enabled']);
            ?>
            
            <button onclick="toggleAutoBackup()" class="btn" style="background: <?= $autoEnabled ? '#48bb78' : '#e2e8f0' ?>; color: <?= $autoEnabled ? 'white' : '#4a5568' ?>;">
                <span id="autoBackupIcon"><?= $autoEnabled ? '●' : '○' ?></span> Auto <?= $autoEnabled ? 'ON' : 'OFF' ?>
            </button>
            <button onclick="openBackupScheduleModal()" class="btn btn-primary">⚙️ Nastavit</button>
        </div>
    </div>
    
    <p class="card-description">
        <strong>Záloha databáze:</strong> Rychlá záloha příspěvků, kategorií a uživatelů (BEZ obrázků).<br>
        <strong>Kompletní záloha:</strong> Databáze + všechny nahrané obrázky (doporučeno!).
    </p>
    
    <!-- Tlačítka pro ruční zálohy -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <form method="POST" style="margin: 0;">
            <?= Security::tokenInput() ?>
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="btn btn-primary">📄 Záloha databáze</button>
        </form>
        
        <form method="POST" style="margin: 0;">
            <?= Security::tokenInput() ?>
            <input type="hidden" name="action" value="create_full_backup">
            <button type="submit" class="btn btn-success">📦 Kompletní záloha</button>
        </form>
    </div>

    <!-- Přepínač zobrazení: Seznam / Kalendář -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0;">
        <button onclick="showBackupView('list')" id="viewList" class="view-tab active">📋 Seznam</button>
        <button onclick="showBackupView('calendar')" id="viewCalendar" class="view-tab">📅 Kalendář</button>
    </div>

    <!-- SEZNAMOVÉ ZOBRAZENÍ (PRIMÁRNÍ) -->
    <div id="backupListView">
        <?php if (empty($backups)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Zatím žádné zálohy</p>
            </div>
        <?php else: 
            $page = isset($_GET['backup_page']) ? (int)$_GET['backup_page'] : 1;
            $perPage = 5;
            $offset = ($page - 1) * $perPage;
            $totalBackups = count($backups);
            $totalPages = ceil($totalBackups / $perPage);
            $pagedBackups = array_slice($backups, $offset, $perPage);
        ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum vytvoření</th>
                        <th>Typ</th>
                        <th>Velikost</th>
                        <th>Vytvořil</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagedBackups as $b): ?>
                        <tr <?= $b['last_restored_at'] ? 'style="background: #f0fff4;"' : '' ?>>
                            <td>
                                <?= formatDate($b['created_at']) ?>
                                <?php if ($b['last_restored_at']): ?>
                                    <br>
                                    <span class="badge" style="background: #38a169; color: white; font-size: 11px; margin-top: 5px;">
                                        ✓ Obnoveno: <?= formatDate($b['last_restored_at']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($b['type']) && $b['type'] === 'full'): ?>
                                    <span class="badge" style="background: #48bb78; color: white;">📦 Kompletní</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #667eea; color: white;">📄 Databáze</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatBytes($b['size_bytes']) ?></td>
                            <td><?= e($b['created_by_name'] ?? 'Systém') ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="download_backup.php?id=<?= $b['id'] ?>" class="btn btn-primary btn-small">⬇ Stáhnout</a>
                                    <button onclick="openRestoreModal(<?= $b['id'] ?>, '<?= e($b['filename']) ?>', '<?= formatDate($b['created_at']) ?>')" class="btn btn-success btn-small">↻ Obnovit</button>
                                    <button onclick="openDeleteBackupModal(<?= $b['id'] ?>, '<?= e($b['filename']) ?>')" class="btn btn-danger btn-small">🗑️ Smazat</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Stránkování -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?backup_page=<?= $i ?>#backups" class="btn <?= $i === $page ? 'btn-primary' : '' ?>" style="<?= $i !== $page ? 'background: #e2e8f0; color: #4a5568;' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- KALENDÁŘOVÉ ZOBRAZENÍ -->
    <div id="backupCalendarView" style="display: none;">
        <?php
        $currentMonth = $_GET['month'] ?? date('Y-m');
        $monthStart = date('Y-m-01', strtotime($currentMonth));
        $monthEnd = date('Y-m-t', strtotime($currentMonth));
        
        $dbCal = new Database();
        $dbCal->query("
            SELECT DATE(created_at) as backup_date, COUNT(*) as count
            FROM backups
            WHERE DATE(created_at) BETWEEN :start AND :end
            GROUP BY DATE(created_at)
        ");
        $dbCal->bind(':start', $monthStart);
        $dbCal->bind(':end', $monthEnd);
        $backupDays = $dbCal->fetchAll();
        $daysWithBackups = array_column($backupDays, 'count', 'backup_date');
        
        $prevMonth = date('Y-m', strtotime($currentMonth . ' -1 month'));
        $nextMonth = date('Y-m', strtotime($currentMonth . ' +1 month'));
        
        $months = ['', 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
        $currentMonthNum = (int)date('n', strtotime($currentMonth));
        $prevMonthNum = (int)date('n', strtotime($prevMonth));
        $nextMonthNum = (int)date('n', strtotime($nextMonth));
        ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <a href="?month=<?= $prevMonth ?>#backups" class="btn btn-small" style="background: #e2e8f0; color: #4a5568;">◀ <?= $months[$prevMonthNum] ?></a>
            <h3 style="margin: 0; font-size: 18px;"><?= $months[$currentMonthNum] ?> <?= date('Y', strtotime($currentMonth)) ?></h3>
            <a href="?month=<?= $nextMonth ?>#backups" class="btn btn-small" style="background: #e2e8f0; color: #4a5568;"><?= $months[$nextMonthNum] ?> ▶</a>
        </div>
        
        <div class="calendar-grid-compact">
            <div class="calendar-header-compact">Po</div>
            <div class="calendar-header-compact">Út</div>
            <div class="calendar-header-compact">St</div>
            <div class="calendar-header-compact">Čt</div>
            <div class="calendar-header-compact">Pá</div>
            <div class="calendar-header-compact">So</div>
            <div class="calendar-header-compact">Ne</div>
            
            <?php
            $firstDay = date('N', strtotime($monthStart));
            $daysInMonth = date('t', strtotime($monthStart));
            
            for ($i = 1; $i < $firstDay; $i++) {
                echo '<div class="calendar-day-compact empty"></div>';
            }
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%s-%02d', $currentMonth, $day);
                $hasBackups = isset($daysWithBackups[$date]);
                $count = $hasBackups ? $daysWithBackups[$date] : 0;
                $isToday = $date === date('Y-m-d');
                
                $class = 'calendar-day-compact';
                if ($isToday) $class .= ' today';
                if ($hasBackups) $class .= ' has-backups';
                
                echo "<div class='$class' onclick='showBackupsForDay(\"$date\")'>";
                echo "<span class='day-number-compact'>$day</span>";
                if ($hasBackups) echo "<span class='backup-indicator-compact'>$count</span>";
                echo "</div>";
            }
            ?>
        </div>
        
        <!-- Detail zálohy vybraného dne -->
        <div id="dayBackups" style="margin-top: 20px; display: none;"></div>
    </div>
</div>

<style>
.calendar-grid-compact {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
    margin-bottom: 15px;
    max-width: 600px;
}
.calendar-header-compact {
    text-align: center;
    font-weight: 600;
    padding: 6px;
    background: #f7fafc;
    border-radius: 6px;
    font-size: 13px;
}
.calendar-day-compact {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    font-size: 13px;
}
.calendar-day-compact.empty {
    border: none;
    cursor: default;
}
.calendar-day-compact.today {
    border-color: #667eea;
    background: #f0f4ff;
}
.calendar-day-compact.has-backups {
    background: #f0fff4;
    border-color: #48bb78;
}
.calendar-day-compact:not(.empty):hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.day-number-compact {
    font-weight: 600;
}
.backup-indicator-compact {
    position: absolute;
    top: 3px;
    right: 3px;
    background: #48bb78;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
}
.view-tab {
    padding: 10px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 600;
    color: #718096;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
}
.view-tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}
</style>

<script>
function showBackupView(view) {
    localStorage.setItem('backupView', view);
    
    if (view === 'calendar') {
        document.getElementById('backupCalendarView').style.display = 'block';
        document.getElementById('backupListView').style.display = 'none';
        document.getElementById('viewCalendar').classList.add('active');
        document.getElementById('viewList').classList.remove('active');
    } else {
        document.getElementById('backupCalendarView').style.display = 'none';
        document.getElementById('backupListView').style.display = 'block';
        document.getElementById('viewCalendar').classList.remove('active');
        document.getElementById('viewList').classList.add('active');
    }
}

function showBackupsForDay(date) {
    fetch('ajax/get-backups-for-day.php?date=' + date)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('dayBackups');
            if (data.backups.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #a0aec0;">Žádné zálohy pro tento den</p>';
            } else {
                let html = '<h4 style="margin-bottom: 10px; font-size: 16px;">📅 Zálohy z ' + data.date_formatted + '</h4>';
                html += '<table class="table"><thead><tr><th>Čas</th><th>Typ</th><th>Velikost</th><th>Vytvořil</th><th>Akce</th></tr></thead><tbody>';
                data.backups.forEach(b => {
                    html += '<tr>';
                    html += '<td>' + b.time + '</td>';
                    html += '<td>' + b.type_badge + '</td>';
                    html += '<td>' + b.size + '</td>';
                    html += '<td>' + b.created_by + '</td>';
                    html += '<td>';
                    html += '<a href="download_backup.php?id=' + b.id + '" class="btn btn-primary btn-small">⬇</a> ';
                    html += '<button onclick="openRestoreModal(' + b.id + ', \'' + b.filename + '\', \'' + b.created_at + '\')" class="btn btn-success btn-small">↻</button> ';
                    html += '<button onclick="openDeleteBackupModal(' + b.id + ', \'' + b.filename + '\')" class="btn btn-danger btn-small">🗑️</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            }
            container.style.display = 'block';
        });
}

function toggleAutoBackup() {
    alert('Použij tlačítko "⚙️ Nastavit" pro konfiguraci automatických záloh');
}

function openBackupScheduleModal() {
    document.getElementById('backupScheduleModal').classList.add('active');
}

function closeBackupScheduleModal() {
    document.getElementById('backupScheduleModal').classList.remove('active');
}

function closeDeleteBackupModal() {
    document.getElementById('deleteBackupModal').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('backupView') || 'list';
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('month')) {
        showBackupView('calendar');
    } else {
        showBackupView(savedView);
    }
});
</script>
    
        <!-- Správa uživatelů -->
        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'IT'): ?>
        <div class="card">
            <h2>👥 Správa uživatelů</h2>
            <p class="card-description">
                <strong>Role IT:</strong> Přístup k monitoringu a správě IT uživatelů. Monitoring lze zapnout/vypnout tlačítkem u každého IT uživatele.<br>
                <strong>Role Admin:</strong> Plný přístup – správa obsahu, uživatelů a nastavení.<br>
                <strong>Role Editor:</strong> Může vytvářet a upravovat pouze vlastní příspěvky.
            </p>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                <button onclick="openAddUserModal()" class="btn btn-success">➕ Přidat uživatele</button>
                <?php if ($currentUser['role'] === 'IT'): ?>
                    <a href="monitoring.php" class="btn" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                        🔍 Přejít na Monitoring
                    </a>
                <?php endif; ?>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Uživatel</th>
                        <th>Role</th>
                        <th>Registrace</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= e($user['username']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $user['role'] ?>">
                                    <?php 
                                    if ($user['role'] === 'IT') {
                                        echo '🔧 IT';
                                    } elseif ($user['role'] === 'admin') {
                                        echo '👑 Admin';
                                    } else {
                                        echo '✏️ Editor';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?= formatDate($user['created_at']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <?php if ($user['role'] === 'IT' && $currentUser['role'] !== 'IT'): ?>
                                            <span style="color: #9f7aea; font-size: 13px;">🔒 IT účet – pouze IT může upravovat</span>
                                        <?php else: ?>
                                            <?php if ($user['role'] === 'IT'): ?>
                                                <!-- Monitoring toggle pro IT uživatele -->
                                                <form method="POST" style="margin:0; display:inline;" id="monForm<?= $user['id'] ?>">
                                                    <?= Security::tokenInput() ?>
                                                    <input type="hidden" name="action" value="toggle_monitoring_access">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit"
                                                        class="toggle-btn <?= $user['monitoring_access'] ? 'on' : 'off' ?>"
                                                        title="Přepnout přístup k monitoringu">
                                                        🔍 Monitoring <?= $user['monitoring_access'] ? 'ON' : 'OFF' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button onclick="openChangePasswordModal(<?= $user['id'] ?>, '<?= e($user['username']) ?>')" class="btn btn-primary btn-small">🔑 Heslo</button>
                                            <button onclick="confirmDeleteUser(<?= $user['id'] ?>, '<?= e($user['username']) ?>')" class="btn btn-danger btn-small">🗑️</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-size: 13px;">Jste přihlášeni jako tento uživatel</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal: Přidat uživatele -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Přidat nového uživatele</h3>
                <button class="modal-close" onclick="closeAddUserModal()">×</button>
            </div>
            <form method="POST" id="addUserForm">
                <?= Security::tokenInput() ?>
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Uživatelské jméno *</label>
                    <input type="text" name="username" required placeholder="např. jan.novak">
                </div>
                
                <div class="form-group">
                    <label>Heslo (min. 6 znaků) *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <?php if ($currentUser['role'] === 'IT'): ?>
                            <!-- IT role může přidávat pouze další IT uživatele -->
                            <option value="IT">🔧 IT – Přístup k monitoringu</option>
                        <?php else: ?>
                            <option value="editor">✏️ Editor – Může upravovat vlastní příspěvky</option>
                            <option value="admin">👑 Admin – Plný přístup ke všemu</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">✓ Vytvořit uživatele</button>
                    <button type="button" class="btn btn-danger" onclick="closeAddUserModal()">Zrušit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Změnit heslo uživatele -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔑 Změnit heslo uživatele</h3>
                <button class="modal-close" onclick="closeChangePasswordModal()">×</button>
            </div>
            <form method="POST" id="changePasswordForm">
                <?= Security::tokenInput() ?>
                <input type="hidden" name="action" value="change_user_password">
                <input type="hidden" name="user_id" id="changePasswordUserId">
                
                <p style="margin-bottom: 20px; color: #4a5568;">
                    Změnit heslo pro: <strong id="changePasswordUsername"></strong>
                </p>
                
                <div class="form-group">
                    <label>Nové heslo (min. 6 znaků)</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">✓ Změnit heslo</button>
                    <button type="button" class="btn btn-danger" onclick="closeChangePasswordModal()">Zrušit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Smazat uživatele -->
    <div class="modal" id="deleteUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Smazat uživatele</h3>
                <button class="modal-close" onclick="closeDeleteUserModal()">×</button>
            </div>
            <div style="padding: 20px;">
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <strong>⚠️ Varování:</strong> Tuto akci nelze vrátit zpět!
                </div>
                <p style="font-size: 16px; margin-bottom: 20px;">
                    Opravdu chcete smazat uživatele <strong id="deleteUserName" style="color: #667eea;"></strong>?
                </p>
                <p style="color: #718096; font-size: 14px; margin-bottom: 20px;">
                    ℹ️ Všechny příspěvky tohoto uživatele zůstanou zachovány.
                </p>
                <form method="POST" id="deleteUserForm">
                    <?= Security::tokenInput() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeDeleteUserModal()" class="btn" style="background: #e2e8f0; color: #4a5568;">Zrušit</button>
                        <button type="submit" class="btn btn-danger">🗑️ Ano, smazat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Restore zálohy -->
    <div class="modal" id="restoreModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>↻ Obnovit zálohu</h3>
                <button class="modal-close" onclick="closeRestoreModal()">×</button>
            </div>
            <div style="padding: 20px;">
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <strong>⚠️ VAROVÁNÍ:</strong> Obnovením zálohy přepíšete všechna současná data!
                </div>
                <p style="font-size: 16px; margin-bottom: 10px;">Opravdu chcete obnovit zálohu:</p>
                <p style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong id="restoreFilename" style="color: #667eea;"></strong><br>
                    <span id="restoreDate" style="font-size: 14px; color: #718096;"></span>
                </p>
                <form method="POST" id="restoreForm">
                    <?= Security::tokenInput() ?>
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" name="backup_id" id="restoreBackupId">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeRestoreModal()" class="btn" style="background: #e2e8f0; color: #4a5568;">Zrušit</button>
                        <button type="submit" class="btn btn-success">↻ Ano, obnovit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Smazat zálohu -->
    <div class="modal" id="deleteBackupModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Smazat zálohu</h3>
                <button class="modal-close" onclick="closeDeleteBackupModal()">×</button>
            </div>
            <div style="padding: 20px;">
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <strong>⚠️ Varování:</strong> Tuto akci nelze vrátit zpět!
                </div>
                <p style="font-size: 16px; margin-bottom: 20px;">
                    Opravdu chcete smazat zálohu <strong id="deleteBackupFilename" style="color: #667eea;"></strong>?
                </p>
                <form method="POST" id="deleteBackupForm">
                    <?= Security::tokenInput() ?>
                    <input type="hidden" name="action" value="delete_backup">
                    <input type="hidden" name="backup_id" id="deleteBackupId">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeDeleteBackupModal()" class="btn" style="background: #e2e8f0; color: #4a5568;">Zrušit</button>
                        <button type="submit" class="btn btn-danger">🗑️ Ano, smazat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Nastavení automatických záloh -->
    <div class="modal" id="backupScheduleModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>⚙️ Nastavení automatických záloh</h3>
                <button class="modal-close" onclick="closeBackupScheduleModal()">×</button>
            </div>
            <form method="POST" style="padding: 20px;">
                <?= Security::tokenInput() ?>
                <input type="hidden" name="action" value="update_backup_schedule">
                
                <!-- Databázové zálohy -->
                <div style="background: #f7fafc; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                        <span>📄 Zálohy databáze</span>
                        <label style="display: flex; align-items: center; cursor: pointer; margin-left: auto;">
                            <input type="checkbox" name="db_enabled" value="1" <?= ($dbSchedule && $dbSchedule['enabled']) ? 'checked' : '' ?> style="margin-right: 8px;">
                            <span>Zapnuto</span>
                        </label>
                    </h4>
                    <div class="form-group">
                        <label>Frekvence</label>
                        <select name="db_frequency" class="form-control">
                            <option value="daily" <?= ($dbSchedule && $dbSchedule['frequency'] === 'daily') ? 'selected' : '' ?>>Každý den</option>
                            <option value="weekly" <?= ($dbSchedule && $dbSchedule['frequency'] === 'weekly') ? 'selected' : '' ?>>Každý týden</option>
                            <option value="monthly" <?= ($dbSchedule && $dbSchedule['frequency'] === 'monthly') ? 'selected' : '' ?>>Každý měsíc</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Čas spuštění</label>
                        <input type="time" name="db_time" value="<?= $dbSchedule ? substr($dbSchedule['time'], 0, 5) : '02:00' ?>" class="form-control" required>
                    </div>
                </div>
                
                <!-- Kompletní zálohy -->
                <div style="background: #f0fff4; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                        <span>📦 Kompletní zálohy</span>
                        <label style="display: flex; align-items: center; cursor: pointer; margin-left: auto;">
                            <input type="checkbox" name="full_enabled" value="1" <?= ($fullSchedule && $fullSchedule['enabled']) ? 'checked' : '' ?> style="margin-right: 8px;">
                            <span>Zapnuto</span>
                        </label>
                    </h4>
                    <div class="form-group">
                        <label>Frekvence</label>
                        <select name="full_frequency" class="form-control">
                            <option value="daily" <?= ($fullSchedule && $fullSchedule['frequency'] === 'daily') ? 'selected' : '' ?>>Každý den</option>
                            <option value="weekly" <?= ($fullSchedule && $fullSchedule['frequency'] === 'weekly') ? 'selected' : '' ?>>Každý týden</option>
                            <option value="monthly" <?= ($fullSchedule && $fullSchedule['frequency'] === 'monthly') ? 'selected' : '' ?>>Každý měsíc</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Čas spuštění</label>
                        <input type="time" name="full_time" value="<?= $fullSchedule ? substr($fullSchedule['time'], 0, 5) : '03:00' ?>" class="form-control" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeBackupScheduleModal()" class="btn" style="background: #e2e8f0; color: #4a5568;">Zrušit</button>
                    <button type="submit" class="btn btn-success">💾 Uložit nastavení</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add User Modal
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('active');
            document.getElementById('addUserForm').reset();
        }
        
        // Change Password Modal
        function openChangePasswordModal(userId, username) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordUsername').textContent = username;
            document.getElementById('changePasswordModal').classList.add('active');
        }
        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.remove('active');
            document.getElementById('changePasswordForm').reset();
        }
        
        // Delete User Modal
        function confirmDeleteUser(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = username;
            document.getElementById('deleteUserModal').classList.add('active');
        }
        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.remove('active');
        }

        // Restore Modal
        function openRestoreModal(backupId, filename, date) {
            document.getElementById('restoreBackupId').value = backupId;
            document.getElementById('restoreFilename').textContent = filename;
            document.getElementById('restoreDate').textContent = date;
            document.getElementById('restoreModal').classList.add('active');
        }
        function closeRestoreModal() {
            document.getElementById('restoreModal').classList.remove('active');
        }

        // Delete Backup Modal
        function openDeleteBackupModal(backupId, filename) {
            document.getElementById('deleteBackupId').value = backupId;
            document.getElementById('deleteBackupFilename').textContent = filename;
            document.getElementById('deleteBackupModal').classList.add('active');
        }
        
        // Close modals on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddUserModal();
                closeChangePasswordModal();
                closeDeleteUserModal();
                closeRestoreModal();
                closeDeleteBackupModal();
                closeBackupScheduleModal();
            }
        });
        
        // Close modals on click outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddUserModal();
                    closeChangePasswordModal();
                    closeDeleteUserModal();
                    closeRestoreModal();
                    closeDeleteBackupModal();
                    closeBackupScheduleModal();
                }
            });
        });
    </script>

    <style>
    .form-control {
        width: 100%;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #4a5568;
    }
    </style>
</body>
</html>
