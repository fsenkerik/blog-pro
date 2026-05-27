<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}
if (!Security::verifyToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Neplatny bezpecnostni token.']);
    exit;
}

$order = $data['order'] ?? [];

$db = new Database();

foreach ($order as $index => $postId) {
    $db->query("UPDATE posts SET menu_order = :order WHERE id = :id");
    $db->bind(':order', $index);
    $db->bind(':id', intval($postId));
    $db->execute();
}

echo json_encode(['success' => true]);
