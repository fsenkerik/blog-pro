<?php
$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    http_response_code(404);
    exit;
}

$password = 'heslo123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Nový password hash:</h2>";
echo "<textarea style='width:100%; height:100px;'>$hash</textarea>";
echo "<br><br>";
echo "<h3>Zkopírujte hash výše a použijte ho v SQL:</h3>";
echo "<pre>";
echo "UPDATE users SET password = '$hash' WHERE username = 'admin1';";
echo "</pre>";
?>
