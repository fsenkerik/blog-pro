<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();
$post = new Post();

$action = $_GET['action'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$return = $_GET['return'] ?? '';
$success = 0;

foreach ($ids as $id) {
    if ($id <= 0) {
        continue;
    }

    $existing = $post->getById($id);
    if (!$existing) {
        continue;
    }

    if ($action === 'delete') {
        if (!$auth->canDelete($existing['author_id'])) {
            continue;
        }
        $result = $post->delete($id);
        if ($result['success']) {
            $success++;
        }
        continue;
    }

    if ($action === 'publish' || $action === 'draft') {
        if (!$auth->canEdit($existing['author_id'])) {
            continue;
        }
        $updateData = [
            'title' => $existing['title'],
            'slug' => $existing['slug'],
            'content' => $existing['content'],
            'excerpt' => $existing['excerpt'],
            'category_id' => $existing['category_id'],
            'status' => $action === 'publish' ? 'published' : 'draft',
            'meta_title' => $existing['meta_title'],
            'meta_description' => $existing['meta_description'],
            'meta_keywords' => $existing['meta_keywords'],
        ];

        if (!empty($existing['featured_image'])) {
            $updateData['featured_image'] = $existing['featured_image'];
        }

        $result = $post->update($id, $updateData);
        if ($result['success']) {
            $success++;
        }
    }
}

$message = $action === 'delete'
    ? "$success prispevku smazano"
    : "$success prispevku aktualizovano";

setFlash($success > 0 ? 'success' : 'error', $success > 0 ? $message : 'Nebyl vybran zadny platny prispevek');
redirect(ADMIN_URL . 'posts.php' . $return);
