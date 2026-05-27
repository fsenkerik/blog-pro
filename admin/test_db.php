<?php
$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    http_response_code(404);
    exit;
}

echo "<h2>Test přihlášení:</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=blog_pro;charset=utf8mb4', 'root', 'root');
    echo "✅ Databáze připojena<br><br>";
    
    // Získat uživatele admin1
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin1']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Uživatel admin1 EXISTUJE<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Password hash: " . substr($user['password'], 0, 20) . "...<br><br>";
        
        // Test hesla
        $testPassword = 'heslo123';
        if (password_verify($testPassword, $user['password'])) {
            echo "✅ Heslo 'heslo123' JE SPRÁVNÉ!<br><br>";
            echo "<strong>Přihlášení by mělo fungovat!</strong><br><br>";
            echo "<a href='login.php'>Zkusit přihlášení</a>";
        } else {
            echo "❌ Heslo 'heslo123' NEFUNGUJE!<br>";
            echo "Musíme resetovat heslo v databázi.";
        }
    } else {
        echo "❌ Uživatel admin1 NEEXISTUJE v databázi!";
    }
    
} catch (PDOException $e) {
    echo "❌ Chyba: " . $e->getMessage();
}
?>
