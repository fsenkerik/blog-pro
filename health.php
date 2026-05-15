<?php
define('BLOG_PRO', true);
require_once __DIR__ . '/config.php';

http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';
