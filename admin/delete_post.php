<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();
$post = new Post();

// Get post ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get return parameters
$returnFilter = $_GET['return_filter'] ?? 'all';
$returnCategory = isset($_GET['return_category']) ? intval($_GET['return_category']) : null;
$returnPage = isset($_GET['return_page']) ? intval($_GET['return_page']) : 1;

if (!$postId) {
    setFlash('error', 'Neplatné ID příspěvku');
    redirect(ADMIN_URL . 'dashboard.php');
}

// Get post to check permissions
$postData = $post->getById($postId);

if (!$postData) {
    setFlash('error', 'Příspěvek nenalezen');
    redirect(ADMIN_URL . 'dashboard.php');
}

// Check permissions
if (!$auth->canEdit($postData['author_id'])) {
    setFlash('error', 'Nemáte oprávnění smazat tento příspěvek');
    redirect(ADMIN_URL . 'dashboard.php');
}

// Delete post
$result = $post->delete($postId);

if ($result['success']) {
    setFlash('success', 'Příspěvek byl smazán');
} else {
    setFlash('error', $result['message'] ?? 'Nepodařilo se smazat příspěvek');
}

// Redirect back with filters
$redirectUrl = ADMIN_URL . 'dashboard.php?filter=' . $returnFilter;
if ($returnCategory) {
    $redirectUrl .= '&category=' . $returnCategory;
}
$redirectUrl .= '&page=' . $returnPage;

redirect($redirectUrl);