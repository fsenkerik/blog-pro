<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

$media = new Media();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 9;

// DEBUG info
$debugInfo = [
    'search_received' => $search,
    'search_length' => strlen($search),
    'page' => $page,
    'perPage' => $perPage
];

$items = $media->getAll($search, $page, $perPage);
$total = $media->getCount($search);

$debugInfo['items_found'] = count($items);
$debugInfo['total_count'] = $total;

// Přidej názvy prvních 3 obrázků pro debug
if(count($items) > 0) {
    $debugInfo['sample_names'] = array_slice(array_map(function($i) { 
        return $i['original_name']; 
    }, $items), 0, 3);
}

echo json_encode([
    'success' => true, 
    'items' => $items, 
    'total' => $total, 
    'page' => $page, 
    'debug' => $debugInfo
]);