<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();
if ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'IT') {
    die('Přístup odepřen.');
}

$db = new Database();
$results = [];

$migrations = [
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS tags VARCHAR(500) DEFAULT NULL AFTER meta_keywords",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS featured_image_alt VARCHAR(255) DEFAULT NULL AFTER featured_image",
];

foreach ($migrations as $sql) {
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
