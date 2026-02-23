<?php
session_start();

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin1';
$_SESSION['user_role'] = 'admin';
$_SESSION['session_id'] = 'test123';

echo "<h1>Session vytvořena!</h1>";
echo "<p>Username: " . $_SESSION['username'] . "</p>";
echo "<p>Role: " . $_SESSION['user_role'] . "</p>";
echo "<p><a href='dashboard_simple.php'>Jít na dashboard</a></p>";
?>