<?php

function redirect($url, $permanent = false) {
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    exit;
}

function formatDate($date, $format = 'j. n. Y') {
    if (!$date) return '';
    $months = [
        1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna',
        5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna',
        9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince'
    ];
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date('j', $timestamp) . '. ' . $months[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'před chvílí';
    if ($diff < 3600)   { $m = floor($diff/60);   return "před $m minut" . ($m==1?'ou':'ami'); }
    if ($diff < 86400)  { $h = floor($diff/3600);  return "před $h hodin" . ($h==1?'ou':'ami'); }
    if ($diff < 604800) { $d = floor($diff/86400); return "před $d dn" . ($d==1?'em':'y'); }
    return formatDate($datetime);
}

function e($text) {
    if ($text === null || $text === '') return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function truncate($text, $length = 100, $suffix = '...') {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) $bytes /= 1024;
    return round($bytes, $precision) . ' ' . $units[$i];
}

function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function isGet()  { return $_SERVER['REQUEST_METHOD'] === 'GET'; }

function post($key, $default = '') { return $_POST[$key] ?? $default; }
function get($key, $default = null) { return $_GET[$key] ?? $default; }

function verifyCsrf() {
    return Security::verifyToken(post('csrf_token'));
}

function requireAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        setFlash('error', 'Pro přístup k této stránce musíte být přihlášeni');
        redirect(ADMIN_URL . 'login.php');
    }
}

function requireAdmin() {
    requireAuth();
    $adminRoles = ['admin', 'IT'];
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $adminRoles)) {
        setFlash('error', 'Nemáte oprávnění k této akci');
        redirect(ADMIN_URL . 'dashboard.php');
    }
}

function postUrl($slug) { return BASE_URL . 'post.php?slug=' . urlencode($slug); }
function categoryUrl($slug) { return BASE_URL . 'category.php?slug=' . urlencode($slug); }

function dd($var) { echo '<pre>'; var_dump($var); echo '</pre>'; die(); }
?>
