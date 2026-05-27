<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();
$post = new Post();

if (!Security::verifyToken($_GET['csrf_token'] ?? '')) {
    setFlash('error', 'Platnost akce vyprsela. Zkuste smazani spustit znovu.');
    redirect(ADMIN_URL . 'posts.php');
}

$postId        = isset($_GET['id']) ? intval($_GET['id']) : 0;
$returnFilter  = $_GET['return_filter'] ?? 'all';
$returnCategory = isset($_GET['return_category']) ? intval($_GET['return_category']) : null;
$returnPage    = isset($_GET['return_page']) ? intval($_GET['return_page']) : 1;

if (!$postId) {
    setFlash('error', 'Neplatné ID příspěvku');
    redirect(ADMIN_URL . 'posts.php');
}

$postData = $post->getById($postId);

if (!$postData) {
    setFlash('error', 'Příspěvek nenalezen');
    redirect(ADMIN_URL . 'posts.php');
}

if (!$auth->canDelete($postData['author_id'])) {
    setFlash('error', 'Nemáte oprávnění smazat tento příspěvek. Editor může mazat pouze své vlastní příspěvky.');
    redirect(ADMIN_URL . 'posts.php');
}

$result = $post->delete($postId);

if ($result['success']) {
    setFlash('success', 'Příspěvek byl smazán');
} else {
    setFlash('error', $result['message'] ?? 'Nepodařilo se smazat příspěvek');
}

$redirectUrl = ADMIN_URL . 'posts.php?status=' . $returnFilter;
if ($returnCategory) {
    $redirectUrl .= '&category=' . $returnCategory;
}
$redirectUrl .= '&page=' . $returnPage;

redirect($redirectUrl);
