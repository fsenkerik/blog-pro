<?php
/**
 * admin/ajax/get-backups-for-day.php
 * Vrací zálohy pro konkrétní den
 */

define('BLOG_PRO', true);
require_once '../../config.php';
requireAuth();

if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'IT'], true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$db = new Database();
$db->query("
    SELECT 
        b.*,
        u.username as created_by_name
    FROM backups b
    LEFT JOIN users u ON b.created_by = u.id
    WHERE DATE(b.created_at) = :date
    ORDER BY b.created_at DESC
");
$db->bind(':date', $date);
$backups = $db->fetchAll();

$result = [
    'date' => $date,
    'date_formatted' => date('j. n. Y', strtotime($date)),
    'backups' => []
];

foreach ($backups as $b) {
    $result['backups'][] = [
        'id' => $b['id'],
        'time' => date('H:i', strtotime($b['created_at'])),
        'created_at' => formatDate($b['created_at']),
        'type' => $b['type'] ?? 'database',
        'type_badge' => ($b['type'] ?? 'database') === 'full' 
            ? '<span class="badge" style="background: #48bb78; color: white;">📦 Kompletní</span>'
            : '<span class="badge" style="background: #667eea; color: white;">📄 Databáze</span>',
        'size' => formatBytes($b['size_bytes']),
        'filename' => $b['filename'],
        'created_by' => e($b['created_by_name'] ?? 'Systém')
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
