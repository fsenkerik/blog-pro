<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();

if (!$auth->isIT()) {
    setFlash('error', 'Nemáte oprávnění k této stránce');
    redirect(ADMIN_URL . 'dashboard.php');
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'period' => $_GET['period'] ?? 'today',
        'action' => $_GET['filter_action'] ?? '',
        'user_id' => $_GET['filter_user'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    $csv = $auditLog->exportCSV($filters);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo $csv;
    exit;
}

$period = $_GET['period'] ?? 'today';
$filterAction = $_GET['filter_action'] ?? '';
$filterUser = $_GET['filter_user'] ?? '';
$search = $_GET['search'] ?? '';

$filters = ['period' => $period, 'action' => $filterAction, 'user_id' => $filterUser, 'search' => $search];

$activities = $auditLog->getRecent(100, $filters);
$stats = $auditLog->getStats($period);

$db = new Database();
$db->query("SELECT id, username FROM users ORDER BY username");
$users = $db->fetchAll();

// Historie přihlášení
$db2 = new Database();
$db2->query("
    SELECT 
        s.*,
        u.username,
        u.role,
        TIMESTAMPDIFF(MINUTE, s.login_at, COALESCE(s.logout_at, s.last_activity)) as session_duration
    FROM sessions s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.login_at DESC
    LIMIT 100
");
$loginHistory = $db2->fetchAll();

// Pokud je vybrána session, načti detail aktivit
$selectedSessionId = isset($_GET['session']) ? intval($_GET['session']) : null;
$sessionActivities = [];

if ($selectedSessionId) {
    $db3 = new Database();
    $db3->query("
        SELECT s.*, u.username 
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = :id
    ");
    $db3->bind(':id', $selectedSessionId);
    $selectedSession = $db3->fetch();
    
    if ($selectedSession) {
        // Načíst všechny aktivity v rámci této session
        $db4 = new Database();
        $db4->query("
            SELECT 
                action,
                entity_type,
                entity_id,
                entity_name,
                MIN(created_at) as first_action,
                MAX(created_at) as last_action,
                COUNT(*) as action_count,
                GROUP_CONCAT(DISTINCT details SEPARATOR ', ') as combined_details,
                ip_address
            FROM audit_log
            WHERE user_id = :user_id
            AND created_at BETWEEN :start AND :end
            GROUP BY action, entity_type, entity_id
            ORDER BY last_action ASC
        ");
        $db4->bind(':user_id', $selectedSession['user_id']);
        $db4->bind(':start', $selectedSession['login_at']);
        $db4->bind(':end', $selectedSession['logout_at'] ?? date('Y-m-d H:i:s'));
        $sessionActivities = $db4->fetchAll();
    }
}

function getActionIcon($action) {
    $icons = ['create' => '➕', 'update' => '✏️', 'delete' => '🗑️', 'login' => '🔐', 'logout' => '🚪', 'backup' => '💾', 'upload' => '🖼️', 'restore' => '↻'];
    return $icons[$action] ?? '📝';
}

function getActionLabel($action) {
    $labels = ['create' => 'Vytvořil', 'update' => 'Upravil', 'delete' => 'Smazal', 'login' => 'Přihlásil se', 'logout' => 'Odhlásil se', 'backup' => 'Záloha', 'upload' => 'Nahrál', 'restore' => 'Obnovil'];
    return $labels[$action] ?? $action;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring - Blog Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        
        /* Header & Navigation */
        header { 
            background: white; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .header-content { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 0 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            height: 70px; 
        }
        .header-title { 
            font-size: 24px; 
            font-weight: 700; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        nav { display: flex; gap: 20px; align-items: center; }
        nav a { 
            text-decoration: none; 
            color: #4a5568; 
            font-weight: 600; 
            transition: color 0.2s; 
            padding: 8px 16px;
            border-radius: 8px;
        }
        nav a:hover { 
            color: #667eea; 
            background: #f7fafc;
        }
        nav a.active {
            color: #667eea;
            background: #f0f4ff;
        }
        
        .container { max-width: 1400px; margin: 30px auto; padding: 0 30px; }
        .card { 
            background: white; 
            border-radius: 16px; 
            padding: 30px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
        }
        .card h2 { font-size: 22px; color: #2d3748; margin-bottom: 20px; }
        
        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-card { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
        }
        .stat-value { font-size: 36px; font-weight: 700; margin: 10px 0; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        
        /* Filters */
        .filters { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
            flex-wrap: wrap; 
        }
        .filters select, .filters input { 
            padding: 10px 15px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px; 
        }
        
        /* Buttons */
        .btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-1px); }
        .btn-small { padding: 6px 12px; font-size: 13px; }
        
        /* Table */
        .table { width: 100%; border-collapse: collapse; }
        .table thead { background: #f7fafc; }
        .table th { 
            padding: 15px; 
            text-align: left; 
            font-weight: 600; 
            color: #4a5568; 
            border-bottom: 2px solid #e2e8f0; 
        }
        .table td { 
            padding: 15px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        .table tr:hover { background: #f7fafc; }
        .table tr.clickable { cursor: pointer; transition: all 0.2s; }
        .table tr.clickable:hover { background: #f0f4ff; }
        .table tr.selected { 
            background: #d4edda !important; 
            border-left: 4px solid #48bb78 !important; 
        }
        
        /* Badges */
        .badge { 
            display: inline-block; 
            padding: 4px 12px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: 600; 
        }
        .badge-it { background: #9f7aea; color: white; }
        .badge-admin { background: #667eea; color: white; }
        .badge-editor { background: #48bb78; color: white; }
        
        /* Status */
        .status-online { color: #48bb78; font-weight: 600; }
        .status-offline { color: #cbd5e0; }
        
        /* Empty state */
        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            color: #a0aec0; 
        }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; }
        
        /* Activity Timeline */
        .timeline { position: relative; padding-left: 30px; }
        .timeline-item { 
            position: relative; 
            padding-bottom: 20px; 
            border-left: 2px solid #e2e8f0; 
            padding-left: 20px; 
            margin-left: 10px;
        }
        .timeline-item:last-child { border-left: 2px solid transparent; }
        .timeline-dot { 
            position: absolute; 
            left: -11px; 
            top: 5px; 
            width: 20px; 
            height: 20px; 
            border-radius: 50%; 
            background: #667eea; 
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
        .timeline-time { 
            font-size: 12px; 
            color: #718096; 
            margin-bottom: 5px; 
        }
        .timeline-content { 
            background: #f7fafc; 
            padding: 12px; 
            border-radius: 8px; 
            font-size: 14px;
        }
        .timeline-icon { 
            display: inline-block; 
            width: 24px; 
            text-align: center; 
        }
        
        /* Session Detail Panel */
        .session-detail { 
            background: #f0f4ff; 
            border-left: 4px solid #667eea; 
            padding: 20px; 
            border-radius: 8px; 
            margin-top: 20px;
        }
        .session-detail h3 { 
            color: #667eea; 
            margin-bottom: 15px; 
            font-size: 18px;
        }
        .session-info { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-bottom: 20px;
        }
        .session-info-item { 
            background: white; 
            padding: 12px; 
            border-radius: 8px;
        }
        .session-info-label { 
            font-size: 12px; 
            color: #718096; 
            margin-bottom: 5px;
        }
        .session-info-value { 
            font-weight: 600; 
            color: #2d3748;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="header-title">🔍 Monitoring</div>
            <nav>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="posts.php">📝 Příspěvky</a>
                <a href="media.php">🖼️ Média</a>
                <a href="monitoring.php" class="active">🔍 Monitoring</a>
                <a href="settings.php">⚙️ Nastavení</a>
                <a href="logout.php">🚪 Odhlásit</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Celkem akcí (<?= $period === 'today' ? 'dnes' : ($period === 'week' ? 'tento týden' : 'tento měsíc') ?>)</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Přidáno</div>
                <div class="stat-value"><?= $stats['create'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Upraveno</div>
                <div class="stat-value"><?= $stats['update'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Smazáno</div>
                <div class="stat-value"><?= $stats['delete'] ?></div>
            </div>
        </div>

        <!-- Historie přihlášení -->
        <div class="card">
            <h2>📜 Historie přihlášení</h2>
            
            <?php if (empty($loginHistory)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📜</div>
                    <p>Žádná historie přihlášení</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Uživatel</th>
                            <th>Role</th>
                            <th>IP adresa</th>
                            <th>Přihlášen</th>
                            <th>Odhlášen</th>
                            <th>Doba trvání</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loginHistory as $h): ?>
                            <tr class="clickable <?= $selectedSessionId == $h['id'] ? 'selected' : '' ?>" 
                                onclick="window.location.href='?session=<?= $h['id'] ?>#session-detail'">
                                <td><strong><?= e($h['username']) ?></strong></td>
                                <td><span class="badge badge-<?= $h['role'] ?>"><?= ucfirst($h['role']) ?></span></td>
                                <td><?= e($h['ip_address']) ?></td>
                                <td><?= date('d.m. H:i', strtotime($h['login_at'])) ?></td>
                                <td>
                                    <?php if ($h['logout_at']): ?>
                                        <?= date('d.m. H:i', strtotime($h['logout_at'])) ?>
                                    <?php else: ?>
                                        <span class="status-online">🟢 Online</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $duration = $h['session_duration'];
                                    if ($duration < 60) {
                                        echo $duration . ' min';
                                    } else {
                                        $hours = floor($duration / 60);
                                        $mins = $duration % 60;
                                        echo $hours . 'h ' . $mins . 'min';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($h['logout_at']): ?>
                                        <span style="color: #cbd5e0;">Odhlášen</span>
                                    <?php else: ?>
                                        <span class="status-online">Aktivní</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Detail vybrané session -->
        <?php if ($selectedSessionId && isset($selectedSession)): ?>
        <div class="card" id="session-detail">
            <div class="session-detail">
                <h3>📊 Detail session: <?= e($selectedSession['username']) ?></h3>
                
                <div class="session-info">
                    <div class="session-info-item">
                        <div class="session-info-label">Přihlášení</div>
                        <div class="session-info-value"><?= formatDate($selectedSession['login_at']) ?></div>
                    </div>
                    <div class="session-info-item">
                        <div class="session-info-label">Odhlášení</div>
                        <div class="session-info-value">
                            <?= $selectedSession['logout_at'] ? formatDate($selectedSession['logout_at']) : '<span class="status-online">🟢 Aktivní</span>' ?>
                        </div>
                    </div>
                    <div class="session-info-item">
                        <div class="session-info-label">IP adresa</div>
                        <div class="session-info-value"><?= e($selectedSession['ip_address']) ?></div>
                    </div>
                    <div class="session-info-item">
                        <div class="session-info-label">Prohlížeč</div>
                        <div class="session-info-value" style="font-size: 12px;"><?= e(substr($selectedSession['user_agent'], 0, 50)) ?>...</div>
                    </div>
                </div>

                <h4 style="margin: 20px 0 15px 0; color: #2d3748;">🕐 Aktivita během session</h4>
                
                <?php if (empty($sessionActivities)): ?>
                    <p style="color: #a0aec0; text-align: center; padding: 20px;">Žádná zaznamenaná aktivita</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($sessionActivities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-time">
                                    <?= date('H:i:s', strtotime($activity['first_action'])) ?>
                                    <?php if ($activity['action_count'] > 1): ?>
                                        - <?= date('H:i:s', strtotime($activity['last_action'])) ?>
                                        <span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">
                                            <?= $activity['action_count'] ?>x
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <span class="timeline-icon"><?= getActionIcon($activity['action']) ?></span>
                                    <strong><?= getActionLabel($activity['action']) ?></strong>
                                    <?php if ($activity['entity_type']): ?>
                                        - <?= ucfirst($activity['entity_type']) ?>: 
                                        <em><?= e($activity['entity_name']) ?></em>
                                    <?php endif; ?>
                                    <?php if ($activity['combined_details']): ?>
                                        <br><small style="color: #718096;"><?= e($activity['combined_details']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($activity['action_count'] > 1): ?>
                                        <br><small style="color: #48bb78;">✓ Provedeno <?= $activity['action_count'] ?>× mezi <?= date('H:i', strtotime($activity['first_action'])) ?> - <?= date('H:i', strtotime($activity['last_action'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="monitoring.php" class="btn btn-primary">← Zpět na seznam</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Celková aktivita -->
        <div class="card">
            <h2>📊 Celková aktivita</h2>
            
            <div class="filters">
                <select id="periodFilter" onchange="applyFilters()">
                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Dnes</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Tento týden</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Tento měsíc</option>
                </select>
                
                <select id="actionFilter" onchange="applyFilters()">
                    <option value="">Všechny akce</option>
                    <option value="create" <?= $filterAction === 'create' ? 'selected' : '' ?>>Přidání</option>
                    <option value="update" <?= $filterAction === 'update' ? 'selected' : '' ?>>Úpravy</option>
                    <option value="delete" <?= $filterAction === 'delete' ? 'selected' : '' ?>>Smazání</option>
                    <option value="backup" <?= $filterAction === 'backup' ? 'selected' : '' ?>>Zálohy</option>
                    <option value="login" <?= $filterAction === 'login' ? 'selected' : '' ?>>Přihlášení</option>
                    <option value="logout" <?= $filterAction === 'logout' ? 'selected' : '' ?>>Odhlášení</option>
                </select>
                
                <select id="userFilter" onchange="applyFilters()">
                    <option value="">Všichni uživatelé</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= e($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="searchInput" placeholder="Vyhledat..." value="<?= e($search) ?>" onkeyup="if(event.key==='Enter') applyFilters()">
                
                <a href="?export=csv&period=<?= $period ?>&filter_action=<?= $filterAction ?>&filter_user=<?= $filterUser ?>&search=<?= $search ?>" class="btn btn-primary">📥 Export CSV</a>
            </div>

            <?php if (empty($activities)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <p>Žádná aktivita</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Čas</th>
                            <th>Uživatel</th>
                            <th>Akce</th>
                            <th>Detail</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($a['created_at'])) ?></td>
                                <td><strong><?= e($a['username']) ?></strong></td>
                                <td><?= getActionIcon($a['action']) ?> <?= getActionLabel($a['action']) ?></td>
                                <td>
                                    <?php if ($a['entity_type']): ?>
                                        <?= ucfirst($a['entity_type']) ?>: <strong><?= e($a['entity_name']) ?></strong>
                                    <?php endif; ?>
                                    <?php if ($a['details']): ?>
                                        <br><small style="color: #718096;"><?= e($a['details']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color: #718096;"><?= e($a['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function applyFilters() {
            const period = document.getElementById('periodFilter').value;
            const action = document.getElementById('actionFilter').value;
            const user = document.getElementById('userFilter').value;
            const search = document.getElementById('searchInput').value;
            
            let url = '?period=' + period;
            if (action) url += '&filter_action=' + action;
            if (user) url += '&filter_user=' + user;
            if (search) url += '&search=' + encodeURIComponent(search);
            
            window.location.href = url;
        }
        
        // Scroll to session detail if in URL
        if (window.location.hash === '#session-detail') {
            setTimeout(() => {
                document.getElementById('session-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    </script>
</body>
</html>