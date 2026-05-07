<?php
// JEDNORÁZOVÝ SETUP - PO POUZITI SMAZ TENTO SOUBOR

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'blog_pro';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$errors = [];
$success = [];

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $success[] = "Pripojeni k databazi OK ($host:$port/$name)";

    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('admin','editor') DEFAULT 'editor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(255),
            category_id INT,
            author_id INT NOT NULL,
            status ENUM('draft','published') DEFAULT 'draft',
            menu_order INT DEFAULT 0,
            meta_title VARCHAR(255),
            meta_description TEXT,
            meta_keywords VARCHAR(255),
            published_at TIMESTAMP NULL,
            scheduled_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            FULLTEXT INDEX idx_search (title, content, excerpt)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            logout_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50),
            entity_id INT NULL,
            entity_name VARCHAR(255),
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS error_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            error_type VARCHAR(50),
            error_message TEXT,
            user_id INT NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            filepath VARCHAR(255) NOT NULL,
            size_bytes BIGINT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
        $success[] = "Tabulka '{$m[1]}' OK";
    }

    $pdo->exec("INSERT IGNORE INTO categories (name, slug, description) VALUES
        ('Akce 2025','akce-2025','Udalosti z roku 2025'),
        ('Akce 2026','akce-2026','Udalosti z roku 2026'),
        ('Novinky','novinky','Nejnovejsi zpravy'),
        ('Ostatni','ostatni','Ostatni prispevky')");
    $success[] = "Kategorie OK";

    // Uzivatele
    $users = [
        ['admin', 'VAXNa239', 'admin@blog.cz', 'admin'],
        ['IT',    'VAXNa239', 'it@blog.cz',    'admin'],
    ];

    foreach ($users as [$uname, $upass, $uemail, $urole]) {
        $hash = password_hash($upass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)");
        $stmt->execute([$uname, $hash, $uemail, $urole]);
        $success[] = "Uzivatel '$uname' ($urole) OK";
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Setup</title>
<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px}
.ok{color:green}.err{color:red}h2{margin-top:20px}</style>
</head><body>
<h1>Blog Pro - Setup</h1>
<?php foreach ($success as $s): ?><p class="ok">✓ <?= htmlspecialchars($s) ?></p><?php endforeach; ?>
<?php foreach ($errors as $e): ?><p class="err">✗ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
<?php if (empty($errors)): ?>
<h2>Hotovo!</h2>
<p>Prihlasovacie udaje:<br>
<strong>admin / VAXNa239</strong><br>
<strong>IT / VAXNa239</strong></p>
<p><a href="/admin/login.php"><strong>Prejit na prihlaseni &rarr;</strong></a></p>
<p style="color:red;margin-top:20px"><strong>SMAZ tento soubor po prihlaseni!</strong></p>
<?php endif; ?>
</body></html>
