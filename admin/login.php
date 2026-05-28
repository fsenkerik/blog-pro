<?php
session_start();
define('BLOG_PRO', true);

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'blog_pro';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';

        $pdo = new PDO(
            "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in']  = true;
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_role']  = $user['role'];
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Přihlášení · Blog Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.login-shell{min-height:100vh;display:grid;grid-template-columns:1fr 1fr;background:var(--paper)}
.login-art{position:relative;overflow:hidden;padding:48px 56px;display:flex;flex-direction:column;justify-content:space-between;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;box-shadow:0 6px 24px -8px rgba(102,126,234,.45)}
.login-art::before{content:'';position:absolute;inset:0;pointer-events:none;background-image:radial-gradient(circle at 75% 25%,rgba(252,211,77,.22) 0%,transparent 55%),radial-gradient(circle at 15% 85%,rgba(255,255,255,.16) 0%,transparent 50%)}
.login-art::after{content:'';position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px);background-size:32px 32px}
.login-art-brand{position:relative;z-index:2;display:flex;align-items:center;gap:12px}
.login-art-brand .m{width:38px;height:38px;border-radius:9px;background:#15162b;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 5px 16px -4px rgba(18,18,42,.45),inset 0 1px 0 rgba(255,255,255,.22);border:1px solid rgba(255,255,255,.25)}
.login-art-brand .m img{width:100%;height:100%;object-fit:cover;display:block}
.login-art-brand b{font-weight:600;font-size:16px;color:#fff}
.login-art-brand s{display:block;font-family:var(--mono);font-size:10.5px;color:rgba(255,255,255,.7);text-decoration:none;letter-spacing:.05em;margin-top:1px}
.login-quote{position:relative;z-index:2;max-width:480px}
.login-quote-mark{font-family:var(--serif);font-size:96px;line-height:1;color:#fcd34d;font-style:italic;margin-bottom:-30px;opacity:.95}
.login-quote blockquote{font-family:var(--serif);font-size:34px;line-height:1.15;font-weight:400;letter-spacing:-.01em;color:#fff}
.login-quote blockquote em{color:#fcd34d;font-style:italic}
.login-quote cite{display:block;margin-top:20px;font-family:var(--mono);font-size:11px;letter-spacing:.08em;color:rgba(255,255,255,.7);text-transform:uppercase;font-style:normal}
.login-foot{position:relative;z-index:2;display:flex;justify-content:space-between;font-family:var(--mono);font-size:11px;color:rgba(255,255,255,.7);letter-spacing:.05em}
.login-foot .pulse-wrap{display:inline-flex;align-items:center;gap:8px}
.login-foot .d{width:6px;height:6px;border-radius:50%;background:#6ee7b7;box-shadow:0 0 0 3px rgba(110,231,183,.25)}
.login-form-wrap{display:flex;align-items:center;justify-content:center;padding:48px;background:var(--paper)}
.login-card{width:100%;max-width:380px}
.login-eyebrow{font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:18px;display:flex;align-items:center;gap:8px}
.login-eyebrow::before{content:'';width:20px;height:1px;background:var(--muted)}
.login-title{font-family:var(--serif);font-size:40px;line-height:1.05;letter-spacing:-.02em;margin-bottom:8px;color:var(--ink)}
.login-title em{font-style:italic;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;background-clip:text;color:transparent}
.login-desc{font-size:14px;color:var(--body);margin-bottom:36px}
.login-form .form-group{margin-bottom:16px}
.login-submit{width:100%;padding:12px;font-size:14px;justify-content:center}
.login-error{background:var(--danger-soft);color:var(--danger);border:1px solid rgba(153,27,27,.15);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px}
@media(max-width:820px){.login-shell{grid-template-columns:1fr}.login-art{padding:32px;min-height:240px}.login-quote blockquote{font-size:22px}.login-quote-mark{font-size:64px;margin-bottom:-18px}}
</style>
</head>
<body>
<div class="login-shell">

  <div class="login-art">
    <div class="login-art-brand">
      <div class="m"><img src="../assets/img/blog-pro-logo.png" alt=""></div>
      <div><b>Blog Pro</b><s>CMS / v3.0</s></div>
    </div>
    <div class="login-quote">
      <div class="login-quote-mark">&ldquo;</div>
      <blockquote>Dobrá redakce píše jako <em>jeden hlas</em> &mdash; i když je nás dvanáct.</blockquote>
      <cite>&mdash; Manifesto redakčního nástroje &middot; 2026</cite>
    </div>
    <div class="login-foot">
      <span class="pulse-wrap"><span class="d"></span>Všechny systémy běží</span>
      <span>v3.0 &middot; PHP CMS</span>
    </div>
  </div>

  <div class="login-form-wrap">
    <div class="login-card">
      <div class="login-eyebrow">Přihlášení &middot; Interní</div>
      <h1 class="login-title">Vítejte <em>zpátky.</em></h1>
      <p class="login-desc">Přihlaste se ke správě redakce.</p>

      <?php if ($error): ?>
        <div class="login-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="login-form">
        <div class="form-group">
          <label class="form-label">Uživatelské jméno</label>
          <input type="text" name="username" class="form-input" required autofocus autocomplete="username">
        </div>
        <div class="form-group">
          <label class="form-label">Heslo</label>
          <input type="password" name="password" class="form-input" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary login-submit">
          Přihlásit se
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>
  </div>

</div>
</body>
</html>
