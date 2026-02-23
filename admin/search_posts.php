<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

$db = new Database();

// Search v title, content, excerpt
$sql = "SELECT p.id, p.title, p.status, p.created_at,
               COALESCE(c.name, 'Bez kategorie') as category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.title LIKE :query1
           OR p.content LIKE :query2
           OR p.excerpt LIKE :query3
        ORDER BY 
            CASE WHEN p.title LIKE :query4 THEN 1 ELSE 2 END,
            p.created_at DESC
        LIMIT 10";

$searchParam = '%' . $query . '%';

$db->query($sql);
$db->bind(':query1', $searchParam, PDO::PARAM_STR);
$db->bind(':query2', $searchParam, PDO::PARAM_STR);
$db->bind(':query3', $searchParam, PDO::PARAM_STR);
$db->bind(':query4', $searchParam, PDO::PARAM_STR);

$results = $db->fetchAll();

// Format dates
foreach ($results as &$result) {
    $result['created_at'] = formatDate($result['created_at']);
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'count' => count($results)
]);