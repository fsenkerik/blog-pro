<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order = $data['order'] ?? [];

$db = new Database();

foreach ($order as $index => $postId) {
    $db->query("UPDATE posts SET menu_order = :order WHERE id = :id");
    $db->bind(':order', $index);
    $db->bind(':id', intval($postId));
    $db->execute();
}

echo json_encode(['success' => true]);