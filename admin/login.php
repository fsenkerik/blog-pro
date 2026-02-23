<?php
session_start();
define('BLOG_PRO', true);

// Pokud je už přihlášen
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';



// Zpracování přihlášení
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Připojení k databázi
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=blog_pro;charset=utf8mb4', 'root', 'root');

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Získat uživatele
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Úspěšné přihlášení
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Nesprávné uživatelské jméno nebo heslo';
        }
    } catch (PDOException $e) {
        $error = 'Chyba databáze: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení - Blog Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        input:focus { outline: none; border-color: #667eea; }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { opacity: 0.9; }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Přihlášení</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Uživatelské jméno</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Heslo</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Přihlásit se</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center; color: #999; font-size: 13px;">
            Výchozí: admin1 / heslo123
        </p>
    </div>
    <!-- Modal: Timeout upozornění -->
<?php if (isset($_GET['timeout'])): ?>
<div class="timeout-modal" id="timeoutModal">
    <div class="timeout-modal-content">
        <div class="timeout-icon">⏱️</div>
        <h2>Session vypršela</h2>
        <p>Byli jste odhlášeni z důvodu nečinnosti.</p>
        <p style="font-size: 14px; color: #718096; margin-top: 10px;">
            Pro zabezpečení vašeho účtu se automaticky odhlašujeme po 5 minutách nečinnosti.
        </p>
        <button onclick="closeTimeoutModal()" class="timeout-btn">
            Rozumím, přihlásit se znovu
        </button>
    </div>
</div>

<style>
.timeout-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

.timeout-modal-content {
    background: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

.timeout-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.timeout-modal-content h2 {
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 24px;
}

.timeout-modal-content p {
    color: #4a5568;
    margin-bottom: 10px;
    line-height: 1.6;
}

.timeout-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 20px;
    transition: transform 0.2s;
}

.timeout-btn:hover {
    transform: translateY(-2px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
function closeTimeoutModal() {
    document.getElementById('timeoutModal').style.display = 'none';
    // Odstranit ?timeout=1 z URL
    const url = new URL(window.location);
    url.searchParams.delete('timeout');
    window.history.replaceState({}, '', url);
}

// Zavřít po 10 sekundách automaticky
setTimeout(closeTimeoutModal, 10000);
</script>
<?php endif; ?>
</body>
</html>