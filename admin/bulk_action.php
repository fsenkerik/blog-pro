<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$action  = $_GET['action'] ?? '';
$ids     = explode(',', $_GET['ids'] ?? '');
$return  = $_GET['return'] ?? '';

$post    = new Post();
$success = 0;

foreach ($ids as $id) {
    $id = intval($id);
    if ($id <= 0) continue;

    if ($action === 'delete') {
        $result = $post->delete($id);
        if ($result['success']) $success++;
    } elseif ($action === 'publish' || $action === 'draft') {
        $existing = $post->getById($id);
        if ($existing) {
            $updateData = [
                'title'            => $existing['title'],
                'slug'             => $existing['slug'],
                'content'          => $existing['content'],
                'excerpt'          => $existing['excerpt'],
                'category_id'      => $existing['category_id'],
                'status'           => $action === 'publish' ? 'published' : 'draft',
                'meta_title'       => $existing['meta_title'],
                'meta_description' => $existing['meta_description'],
                'meta_keywords'    => $existing['meta_keywords'],
            ];
            if (!empty($existing['featured_image'])) {
                $updateData['featured_image'] = $existing['featured_image'];
            }
            $result = $post->update($id, $updateData);
            if ($result['success']) $success++;
        }
    }
}

setFlash('success', "$success příspěvků aktualizováno");
redirect(ADMIN_URL . 'posts.php' . $return);
