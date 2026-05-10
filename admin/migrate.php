<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'IT') {
    die('Přístup odepřen.');
}

$db = new Database();
$results = [];

function columnExists(Database $db, string $table, string $column): bool {
    $db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column");
    $db->bind(':table', $table);
    $db->bind(':column', $column);
    $row = $db->fetch();
    return $row && (int)$row['c'] > 0;
}

$migrations = [
    ['posts', 'tags', "ALTER TABLE posts ADD COLUMN tags VARCHAR(500) DEFAULT NULL AFTER meta_keywords"],
    ['posts', 'featured_image_alt', "ALTER TABLE posts ADD COLUMN featured_image_alt VARCHAR(255) DEFAULT NULL AFTER featured_image"],
    ['users', 'monitoring_access', "ALTER TABLE users ADD COLUMN monitoring_access TINYINT(1) NOT NULL DEFAULT 0 AFTER role"],
    ['backups', 'type', "ALTER TABLE backups ADD COLUMN type ENUM('database','full') DEFAULT 'database' AFTER size_bytes"],
    ['backups', 'last_restored_at', "ALTER TABLE backups ADD COLUMN last_restored_at DATETIME DEFAULT NULL AFTER created_at"],
];

foreach ($migrations as [$table, $column, $sql]) {
    try {
        if (columnExists($db, $table, $column)) {
            $results[] = ['ok' => true, 'sql' => $sql . ' -- skipped, already exists'];
        } else {
            $db->query($sql);
            $db->execute();
            $results[] = ['ok' => true, 'sql' => $sql];
        }
    } catch (Exception $e) {
        $results[] = ['ok' => false, 'sql' => $sql, 'error' => $e->getMessage()];
    }
}

foreach ([
    "ALTER TABLE users MODIFY role ENUM('admin','editor','IT') DEFAULT 'editor'",
    "ALTER TABLE sessions MODIFY id VARCHAR(128) NOT NULL"
] as $sql) {
    try {
        $db->query($sql);
        $db->execute();
        $results[] = ['ok' => true, 'sql' => $sql];
    } catch (Exception $e) {
        $results[] = ['ok' => false, 'sql' => $sql, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head><meta charset="UTF-8"><title>Migrace DB</title>
<style>body{font-family:monospace;padding:32px;background:#f8f8f8}pre{background:#fff;border:1px solid #ddd;padding:12px;border-radius:6px}.ok{color:green}.err{color:red}</style>
</head><body>
<h2>Migrace databáze</h2>
<?php foreach ($results as $r): ?>
<pre class="<?= $r['ok']?'ok':'err' ?>">
<?= $r['ok']?'✓':'✗' ?> <?= htmlspecialchars($r['sql']) ?>
<?= !$r['ok'] ? '  Chyba: '.htmlspecialchars($r['error']) : '' ?>
</pre>
<?php endforeach; ?>
<p><a href="dashboard.php">← Zpět na dashboard</a></p>
</body></html>
