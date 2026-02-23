<?php
define('BLOG_PRO', true);
require_once '../config.php';

$auth = new Auth();
$auth->logout();

redirect(ADMIN_URL . 'login.php');
