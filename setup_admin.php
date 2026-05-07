<?php
// JEDNORÁZOVÝ SETUP - PO POUZITI SMAZ TENTO SOUBOR
// Navstiv: https://blog-pro-production.up.railway.app/setup_admin.php

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'blog_pro';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $username = 'admin';
    $password = 'VAXNa239';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password, email, role)
         VALUES (?, ?, 'admin@blog.cz', 'admin')
         ON DUPLICATE KEY UPDATE password = VALUES(password)"
    );
    $stmt->execute([$username, $hash]);

    echo "<h2>Hotovo!</h2>";
    echo "<p>Uživatel <strong>$username</strong> byl vytvořen/aktualizován.</p>";
    echo "<p>Přihlašovací údaje: <strong>$username / $password</strong></p>";
    echo "<p><a href='/admin/login.php'>Přejít na přihlášení</a></p>";
    echo "<p style='color:red'><strong>POZOR: Smaz tento soubor ihned po použití!</strong></p>";

} catch (Exception $e) {
    echo "<h2>Chyba</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
