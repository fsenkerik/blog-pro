<?php
/**
 * Helper Functions
 * Pomocné funkce použité v celé aplikaci
 */

/**
 * Redirect s HTTP status kódem
 */
function redirect($url, $permanent = false) {
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    exit;
}

/**
 * Formátování data
 */
function formatDate($date, $format = 'j. n. Y') {
    if (!$date) return '';
    
    $months = [
        1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna',
        5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna',
        9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince'
    ];
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day. $month $year";
}

/**
 * Formátování času
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'před chvílí';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "před $mins " . ($mins == 1 ? 'minutou' : ($mins < 5 ? 'minutami' : 'minutami'));
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "před $hours " . ($hours == 1 ? 'hodinou' : ($hours < 5 ? 'hodinami' : 'hodinami'));
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "před $days " . ($days == 1 ? 'dnem' : 'dny');
    } else {
        return formatDate($datetime);
    }
}

/**
 * Escape HTML
 */
function e($text) {
    if ($text === null || $text === '') {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Získat flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Zkrátit text
 */
function truncate($text, $length = 100, $suffix = '...') {
    $text = strip_tags($text);
    
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formátovat velikost souboru
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Kontrola požadavku (GET/POST)
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Získat POST hodnotu
 */
function post($key, $default = '') {
    return $_POST[$key] ?? $default;
}

/**(Změna: `$default = null` → `$default = ''`)**/

/**
 * Získat GET hodnotu
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Ověřit CSRF token
 */
function verifyCsrf() {
    $token = post('csrf_token');
    return Security::verifyToken($token);
}

/**
 * Kontrola práv
 */
function requireAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        setFlash('error', 'Pro přístup k této stránce musíte být přihlášeni');
        redirect(ADMIN_URL . 'login.php');
    }
}

function requireAdmin() {
    requireAuth();
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        setFlash('error', 'Nemáte oprávnění k této akci');
        redirect(ADMIN_URL . 'dashboard.php');
    }
}

/**
 * Vytvořit URL pro příspěvek
 */
function postUrl($slug) {
    return BASE_URL . 'post.php?slug=' . urlencode($slug);
}

/**
 * Vytvořit URL pro kategorii
 */
function categoryUrl($slug) {
    return BASE_URL . 'category.php?slug=' . urlencode($slug);
}

/**
 * Debug funkce
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}
?>
