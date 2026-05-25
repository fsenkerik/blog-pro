<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth   = new Auth();
$backup = new Backup();
$post   = new Post();
$media  = new Media();

try {
    $db_alter = new Database();
    $db_alter->query("ALTER TABLE users ADD COLUMN monitoring_access TINYINT(1) NOT NULL DEFAULT 1");
    $db_alter->execute();
} catch (\Throwable $e) {}

$success = '';
$error   = '';

if (isPost()) {
    $action = post('action');
    if (in_array($action, ['delete_user','change_user_password'])) {
        $tid = intval(post('user_id'));
        $db_chk = new Database();
        $db_chk->query('SELECT role FROM users WHERE id = :id');
        $db_chk->bind(':id', $tid);
        $tu = $db_chk->fetch();
        if ($tu && $tu['role'] === 'IT' && $_SESSION['user_role'] !== 'IT') {
            setFlash('error', 'Nemáte oprávnění upravovat IT uživatele');
            redirect(ADMIN_URL . 'settings.php');
        }
    }
}

if (isPost()) {
    if (!verifyCsrf()) {
        $error = 'Neplatný CSRF token';
    } else {
        $action = post('action');

        if ($action === 'change_password') {
            $result = $auth->changePassword($_SESSION['user_id'], post('current_password'), post('new_password'));
            if ($result['success']) $success = $result['message']; else $error = $result['message'];

        } elseif ($action === 'generate_sitemap') {
            SEO::generateSitemap(); SEO::generateRobots();
            $success = 'Sitemap a robots.txt byly vygenerovány!';

        } elseif ($action === 'create_backup') {
            $result = $backup->createDatabaseBackup();
            if ($result['success']) $success = 'Záloha databáze byla vytvořena!';
            else $error = 'Nepodařilo se vytvořit zálohu';

        } elseif ($action === 'create_full_backup') {
            $result = $backup->createFullBackup();
            if ($result['success']) $success = 'Kompletní záloha (DB + soubory) byla vytvořena!';
            else $error = 'Nepodařilo se vytvořit kompletní zálohu';

        } elseif ($action === 'delete_backup') {
            $result = $backup->deleteBackup(intval(post('backup_id')));
            if ($result['success']) $success = 'Záloha byla smazána!';
            else $error = 'Nepodařilo se smazat zálohu';

        } elseif ($action === 'restore_backup') {
            $bid = intval(post('backup_id'));
            $db_r = new Database();
            $db_r->query('SELECT type FROM backups WHERE id = :id');
            $db_r->bind(':id', $bid);
            $bi = $db_r->fetch();
            $result = ($bi && $bi['type'] === 'full') ? $backup->restoreFullBackup($bid) : $backup->restoreBackup($bid);
            if ($result['success']) {
                $db_r->query('UPDATE backups SET last_restored_at = NULL'); $db_r->execute();
                $db_r->query('UPDATE backups SET last_restored_at = NOW() WHERE id = :id');
                $db_r->bind(':id', $bid); $db_r->execute();
                $success = 'Záloha byla úspěšně obnovena!';
            } else { $error = 'Nepodařilo se obnovit zálohu: ' . ($result['message'] ?? ''); }

        } elseif ($action === 'add_user') {
            $uname = trim(post('username'));
            $upass = post('password');
            $urole = post('role', 'editor');
            if ($_SESSION['user_role'] === 'IT' && $urole !== 'IT') {
                $error = 'IT role může přidávat pouze IT uživatele.';
            } else {
                $db_u = new Database();
                $db_u->query('SELECT id FROM users WHERE username = :u'); $db_u->bind(':u', $uname);
                if ($db_u->fetch()) { $error = 'Uživatelské jméno již existuje!'; }
                else {
                    $db_u->query('INSERT INTO users (username, password, role, monitoring_access, created_at) VALUES (:u, :p, :r, :m, NOW())');
                    $db_u->bind(':u', $uname); $db_u->bind(':p', password_hash($upass, PASSWORD_BCRYPT));
                    $db_u->bind(':r', $urole); $db_u->bind(':m', ($urole === 'IT') ? 1 : 0);
                    if ($db_u->execute()) $success = 'Uživatel byl vytvořen!';
                    else $error = 'Nepodařilo se vytvořit uživatele';
                }
            }
        } elseif ($action === 'delete_user') {
            $uid = intval(post('user_id'));
            if ($uid === $_SESSION['user_id']) { $error = 'Nemůžete smazat sám sebe!'; }
            else {
                $db_u = new Database();
                $db_u->query('DELETE FROM users WHERE id = :id'); $db_u->bind(':id', $uid);
                if ($db_u->execute()) $success = 'Uživatel byl smazán!';
                else $error = 'Nepodařilo se smazat uživatele';
            }
        } elseif ($action === 'change_user_password') {
            $uid = intval(post('user_id'));
            $db_u = new Database();
            $db_u->query('UPDATE users SET password = :p WHERE id = :id');
            $db_u->bind(':p', password_hash(post('new_password'), PASSWORD_BCRYPT));
            $db_u->bind(':id', $uid);
            if ($db_u->execute()) $success = 'Heslo bylo změněno!';
            else $error = 'Nepodařilo se změnit heslo';
        }
    }
}

$db3 = new Database();
$db3->query("
    SELECT b.*,
           CASE WHEN b.created_by = 0 OR b.created_by IS NULL THEN 'Systém' ELSE u.username END as created_by_name
    FROM backups b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC
");
$backups = $db3->fetchAll();

$db_u = new Database();
$db_u->query('SELECT id, username, role, monitoring_access, created_at FROM users ORDER BY created_at DESC');
$users = $db_u->fetchAll();

$flash   = getFlash();
$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 2));
$tab     = $_GET['tab'] ?? 'obecne';
$totalPublished = $post->count('published');
$totalDrafts = $post->count('draft');
$totalScheduled = $post->count('scheduled');
$totalAll = $totalPublished + $totalDrafts + $totalScheduled;
$totalMedia = $media->getCount('');
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nastavení · Blog Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.settings-shell{display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:flex-start;margin-bottom:28px}
.set-nav{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:8px;position:sticky;top:78px;box-shadow:0 1px 2px rgba(31,41,55,.03)}
.set-nav-label{font-family:var(--mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:10px 12px 6px}
.set-nav a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--body);transition:background .15s,color .15s;position:relative}
.set-nav a:hover{background:var(--paper-2);color:var(--ink)}
.set-nav a.active{background:var(--accent-soft);color:var(--accent-2);font-weight:500}
.set-nav a.active::before{content:'';position:absolute;left:0;top:8px;bottom:8px;width:2px;background:linear-gradient(180deg,#667eea,#764ba2);border-radius:0 2px 2px 0}
.set-nav .ico{width:16px;height:16px;flex-shrink:0}
.set-nav .meta{margin-left:auto;font-family:var(--mono);font-size:10px;color:var(--muted)}
.set-nav a.active .meta{color:var(--accent-2)}
.set-nav .sep{height:1px;background:var(--line);margin:6px 8px}
.set-main{min-width:0;display:flex;flex-direction:column;gap:20px}
.set-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding-bottom:14px;border-bottom:1px solid var(--line);margin-bottom:6px}
.set-section-num{font-family:var(--mono);font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.set-section-title{font-family:var(--serif);font-size:30px;font-weight:400;line-height:1.1;letter-spacing:-0.01em}
.set-section-title em{font-style:italic;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;background-clip:text;color:transparent}
.set-section-sub{font-size:13px;color:var(--muted);max-width:360px;text-align:right}
.set-row{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(31,41,55,.03)}
.set-row-head{padding:16px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:20px}
.set-row-title{font-size:14px;font-weight:600;color:var(--ink)}
.set-row-desc{font-size:12.5px;color:var(--muted);margin-top:3px}
.set-row-body{padding:20px 22px}
.set-row-foot{padding:12px 22px;border-top:1px solid var(--line);background:var(--card-2);font-family:var(--mono);font-size:11.5px;color:var(--muted);display:flex;justify-content:space-between;align-items:center}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.form-row{display:grid;grid-template-columns:200px 1fr;gap:24px;padding:14px 0;border-bottom:1px solid var(--line);align-items:flex-start}
.form-row:last-child{border-bottom:none}
.form-row-label{padding-top:10px}
.form-row-label .form-label{margin-bottom:4px}
.form-row-label .hint{font-size:11.5px;color:var(--muted);line-height:1.5}
.toggle-list{display:flex;flex-direction:column}
.toggle-row{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;padding:14px 0;border-bottom:1px solid var(--line)}
.toggle-row:last-child{border-bottom:none}
.toggle-name{font-size:13.5px;font-weight:500;color:var(--ink);margin-bottom:2px;display:flex;align-items:center;gap:8px}
.toggle-desc{font-size:12.5px;color:var(--muted);line-height:1.5}
.toggle-meta{font-family:var(--mono);font-size:10.5px;color:var(--muted);margin-top:4px}
.switch{position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0}
.switch input{opacity:0;width:0;height:0}
.switch .slider{position:absolute;cursor:pointer;inset:0;background:var(--paper-2);border:1px solid var(--border);border-radius:999px;transition:background .2s,border-color .2s}
.switch .slider::before{content:'';position:absolute;left:2px;top:2px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(31,41,55,.18);transition:transform .2s}
.switch input:checked+.slider{background:linear-gradient(135deg,#667eea,#764ba2);border-color:transparent}
.switch input:checked+.slider::before{transform:translateX(16px)}
.backups-list{display:flex;flex-direction:column}
.backup-row{display:grid;grid-template-columns:32px 1fr auto auto;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--line)}
.backup-row:last-child{border-bottom:none}
.backup-ico{width:32px;height:32px;border-radius:8px;background:var(--ok-soft);color:var(--ok);display:flex;align-items:center;justify-content:center}
.backup-name{font-size:13px;font-weight:500;color:var(--ink);font-family:var(--mono)}
.backup-meta{font-size:11.5px;color:var(--muted);margin-top:2px}
.backup-size{font-family:var(--mono);font-size:12px;color:var(--body)}
.danger-zone{border:1px solid var(--danger-soft);border-radius:14px;background:linear-gradient(180deg,#fff,#fff8f8);overflow:hidden}
.danger-head{padding:16px 22px;border-bottom:1px solid var(--danger-soft)}
.danger-title{font-size:14px;font-weight:600;color:var(--danger);display:flex;align-items:center;gap:8px}
.danger-row{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;padding:16px 22px;border-bottom:1px solid var(--danger-soft)}
.danger-row:last-child{border-bottom:none}
.danger-name{font-size:13.5px;font-weight:500;color:var(--ink)}
.danger-desc{font-size:12px;color:var(--body);margin-top:2px;max-width:520px}
.save-bar{position:sticky;bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;background:rgba(255,255,255,.95);backdrop-filter:blur(8px);border:1px solid var(--border);border-radius:12px;box-shadow:0 8px 24px -8px rgba(31,41,55,.18);z-index:30}
.save-bar-status{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--muted)}
.save-bar-status .dot-ok{width:8px;height:8px;border-radius:50%;background:var(--ok);box-shadow:0 0 0 4px rgba(16,185,129,.18)}
.save-bar-actions{display:flex;gap:8px}
.users-table{width:100%;border-collapse:collapse;font-size:13px}
.users-table th{text-align:left;font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);font-weight:500;padding:8px 0;border-bottom:1px solid var(--line)}
.users-table td{padding:12px 0;border-bottom:1px solid var(--line);vertical-align:middle}
.users-table tr:last-child td{border-bottom:none}
@media(max-width:1100px){.settings-shell{grid-template-columns:1fr}.set-nav{position:static}.form-grid-2,.form-grid-3{grid-template-columns:1fr}.form-row{grid-template-columns:1fr;gap:8px}}
</style>
</head>
<body>
<div class="app">

<aside class="side">
  <div class="brand">
    <div class="brand-mark">BP</div>
    <div><div class="brand-name">Blog Pro</div><div class="brand-sub">CMS / v1.0</div></div>
  </div>
  <div>
    <div class="nav-label">Workspace</div>
    <nav class="nav">
      <a href="dashboard.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Přehled</a>
      <a href="posts.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>Příspěvky<span class="count"><?= $totalAll ?></span></a>
      <a href="media.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Média<span class="count"><?= $totalMedia ?></span></a>
    </nav>
  </div>
  <div>
    <div class="nav-label">Nástroje</div>
    <nav class="nav">
      <a href="settings.php" class="active"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Nastavení</a>
      <a href="logout.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Odhlásit</a>
    </nav>
  </div>
  <div class="side-user">
    <div class="avatar"><?= htmlspecialchars($initials) ?></div>
    <div class="side-user-info">
      <div class="side-user-name"><?= htmlspecialchars($username) ?></div>
      <div class="side-user-role"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Admin') ?></div>
    </div>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="crumb"><span>Workspace</span><span class="sep">/</span><span>Nástroje</span><span class="sep">/</span><span class="here">Nastavení</span></div>
    <div class="search">
      <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="text" id="topSearch" placeholder="Hledat články..." autocomplete="off">
      <span class="kbd">⌘ K</span>
    </div>
    <div class="top-actions">
      <a href="add_post.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Nový článek
      </a>
    </div>
  </div>

  <div class="content">

    <?php if ($flash): ?>
    <div style="background:var(--<?= $flash['type']==='success'?'ok':'danger' ?>-soft);color:var(--<?= $flash['type']==='success'?'ok':'danger' ?>);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div style="background:var(--ok-soft);color:var(--ok);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:var(--danger-soft);color:var(--danger);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="page-head">
      <div>
        <div class="eyebrow"><span class="pulse"></span>Konfigurace &middot; PHP CMS</div>
        <h1 class="page-title">Nastavení <em>Blog Pro.</em></h1>
        <p class="page-sub">Konfigurace CMS &mdash; zálohy, uživatelé, hesla, SEO a správa systému.</p>
      </div>
      <span class="chip chip-outline mono" style="font-size:11px;">v1.0 &middot; PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></span>
    </div>

    <div class="settings-shell">

      <!-- Levá navigace -->
      <aside class="set-nav">
        <div class="set-nav-label">Nastavení</div>
        <a href="#obecne" class="<?= $tab==='obecne'?'active':'' ?>">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2"/></svg>
          Obecné <span class="meta">01</span>
        </a>
        <a href="#autori" class="">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          Autoři &amp; role <span class="meta"><?= count($users) ?></span>
        </a>
        <a href="#seo" class="">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          SEO &amp; sitemap <span class="meta">05</span>
        </a>
        <div class="sep"></div>
        <a href="#zalohy" class="">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22 4 17V7l8-5 8 5v10z"/><path d="M12 22V12M4 7l8 5 8-5"/></svg>
          Zálohy &amp; obnova <span class="meta"><?= count($backups) ?></span>
        </a>
        <a href="#danger" class="">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
          Pokročilé
        </a>
      </aside>

      <!-- Pravý sloupec -->
      <div class="set-main">

        <!-- 01 · OBECNÉ -->
        <section id="obecne" style="display:flex;flex-direction:column;gap:20px">
          <div class="set-section-head">
            <div>
              <div class="set-section-num">01 &middot; Obecné</div>
              <h2 class="set-section-title">Identita <em>webu.</em></h2>
            </div>
            <div class="set-section-sub">Základní informace nastavené v <span class="mono" style="font-size:11px;">config.php</span> a proměnných prostředí.</div>
          </div>

          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Konfigurace systému</div><div class="set-row-desc">Aktuální nastavení (jen pro čtení).</div></div>
            </div>
            <div class="set-row-body">
              <div class="form-row">
                <div class="form-row-label"><label class="form-label">Název webu</label><div class="hint">Definováno v config.php jako SITE_NAME.</div></div>
                <div><input class="form-input" type="text" value="<?= htmlspecialchars(SITE_NAME) ?>" readonly></div>
              </div>
              <div class="form-row">
                <div class="form-row-label"><label class="form-label">Popis</label></div>
                <div><input class="form-input" type="text" value="<?= htmlspecialchars(SITE_DESCRIPTION) ?>" readonly></div>
              </div>
              <div class="form-row">
                <div class="form-row-label"><label class="form-label">Base URL</label></div>
                <div><input class="form-input mono" type="text" value="<?= htmlspecialchars(BASE_URL) ?>" readonly></div>
              </div>
              <div class="form-row">
                <div class="form-row-label"><label class="form-label">Časové pásmo</label></div>
                <div><input class="form-input" type="text" value="Europe/Prague" readonly></div>
              </div>
            </div>
            <div class="set-row-foot">
              <span>Změněno přes: <span style="color:var(--accent-2)">.env nebo config.php</span></span>
              <span class="mono">PHP <?= phpversion() ?> &middot; <?= php_uname('s') ?></span>
            </div>
          </div>

          <!-- Změna hesla -->
          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Změna hesla</div><div class="set-row-desc">Změna hesla pro účet <b><?= htmlspecialchars($username) ?></b>.</div></div>
              <span class="chip chip-outline"><?= htmlspecialchars($_SESSION['user_role'] ?? 'admin') ?></span>
            </div>
            <form method="POST" class="set-row-body">
              <input type="hidden" name="action" value="change_password">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <div class="form-grid-3">
                <div>
                  <label class="form-label">Aktuální heslo</label>
                  <input type="password" name="current_password" class="form-input" required>
                </div>
                <div>
                  <label class="form-label">Nové heslo</label>
                  <input type="password" name="new_password" class="form-input" required minlength="6">
                </div>
                <div style="display:flex;align-items:flex-end">
                  <button type="submit" class="btn btn-primary" style="width:100%">Změnit heslo</button>
                </div>
              </div>
            </form>
          </div>
        </section>

        <!-- 02 · AUTOŘI & ROLE -->
        <section id="autori" style="display:flex;flex-direction:column;gap:20px;margin-top:36px">
          <div class="set-section-head">
            <div>
              <div class="set-section-num">02 &middot; Autoři &amp; role</div>
              <h2 class="set-section-title">Správa <em>uživatelů.</em></h2>
            </div>
            <div class="set-section-sub"><?= count($users) ?> uživatelů v systému.</div>
          </div>

          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Uživatelé</div><div class="set-row-desc">Správa přístupových účtů.</div></div>
            </div>
            <div class="set-row-body">
              <table class="users-table">
                <thead><tr><th>Uživatel</th><th>Role</th><th>Vytvořen</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:600;flex-shrink:0"><?= strtoupper(substr($u['username'],0,2)) ?></div>
                        <span style="font-weight:500"><?= htmlspecialchars($u['username']) ?></span>
                        <?php if ($u['id'] == $_SESSION['user_id']): ?><span class="chip chip-outline" style="font-size:10px">já</span><?php endif; ?>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td style="font-family:var(--mono);font-size:12px;color:var(--muted)"><?= date('j.n.Y', strtotime($u['created_at'])) ?></td>
                    <td style="text-align:right">
                      <?php if ($u['id'] != $_SESSION['user_id']): ?>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Smazat uživatele <?= addslashes(htmlspecialchars($u['username'])) ?>?')">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);border-color:var(--danger-soft)">Smazat</button>
                      </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Přidat uživatele</div></div>
            </div>
            <form method="POST" class="set-row-body">
              <input type="hidden" name="action" value="add_user">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <div class="form-grid-3">
                <div><label class="form-label">Uživatelské jméno</label><input type="text" name="username" class="form-input" required></div>
                <div><label class="form-label">Heslo</label><input type="password" name="password" class="form-input" required minlength="6"></div>
                <div>
                  <label class="form-label">Role</label>
                  <select name="role" class="form-select">
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                    <?php if ($_SESSION['user_role'] === 'IT'): ?><option value="IT">IT</option><?php endif; ?>
                  </select>
                </div>
              </div>
              <div style="margin-top:14px">
                <button type="submit" class="btn btn-primary btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                  Přidat uživatele
                </button>
              </div>
            </form>
          </div>
        </section>

        <!-- 03 · SEO -->
        <section id="seo" style="display:flex;flex-direction:column;gap:20px;margin-top:36px">
          <div class="set-section-head">
            <div>
              <div class="set-section-num">03 &middot; SEO &amp; sitemap</div>
              <h2 class="set-section-title">Vyhledávače &amp; <em>sdílení.</em></h2>
            </div>
          </div>
          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Generování sitemap</div><div class="set-row-desc">Vygeneruje sitemap.xml a robots.txt.</div></div>
            </div>
            <div class="set-row-body">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="generate_sitemap">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.4 0 4.6.9 6.3 2.5L21 8"/><path d="M21 3v5h-5"/></svg>
                  Vygenerovat sitemap.xml &amp; robots.txt
                </button>
              </form>
            </div>
          </div>
        </section>

        <!-- 04 · ZÁLOHY -->
        <section id="zalohy" style="display:flex;flex-direction:column;gap:20px;margin-top:36px">
          <div class="set-section-head">
            <div>
              <div class="set-section-num">04 &middot; Zálohy &amp; obnova</div>
              <h2 class="set-section-title">Bezpečí <em>obsahu.</em></h2>
            </div>
            <div class="set-section-sub">Automatické zálohy databáze a souborů.</div>
          </div>

          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Vytvořit zálohu</div><div class="set-row-desc">Ruční spuštění zálohy.</div></div>
            </div>
            <div class="set-row-body" style="display:flex;gap:10px">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="create_backup">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.4 0 4.6.9 6.3 2.5L21 8"/><path d="M21 3v5h-5"/></svg>
                  Záloha DB
                </button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="create_full_backup">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Kompletní záloha (DB + soubory)</button>
              </form>
            </div>
          </div>

          <?php if (!empty($backups)): ?>
          <div class="set-row">
            <div class="set-row-head">
              <div><div class="set-row-title">Poslední zálohy</div><div class="set-row-desc"><?= count($backups) ?> záloh uloženo.</div></div>
            </div>
            <div class="set-row-body" style="padding-top:6px">
              <div class="backups-list">
                <?php foreach (array_slice($backups, 0, 10) as $bk): ?>
                <div class="backup-row">
                  <div class="backup-ico">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  <div>
                    <div class="backup-name"><?= htmlspecialchars($bk['filename'] ?? $bk['file_path'] ?? 'záloha-' . $bk['id']) ?></div>
                    <div class="backup-meta"><?= date('j.n.Y H:i', strtotime($bk['created_at'])) ?> &middot; <?= htmlspecialchars($bk['created_by_name'] ?? 'Systém') ?> &middot; <?= htmlspecialchars($bk['type'] ?? 'db') ?></div>
                  </div>
                  <div class="backup-size"><?= isset($bk['size_bytes']) ? round($bk['size_bytes']/1024/1024, 1) . ' MB' : '&mdash;' ?></div>
                  <div style="display:flex;gap:6px">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Obnovit tuto zálohu? Aktuální data budou nahrazena.')">
                      <input type="hidden" name="action" value="restore_backup">
                      <input type="hidden" name="backup_id" value="<?= $bk['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <button type="submit" class="btn btn-ghost btn-sm">Obnovit</button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Smazat tuto zálohu?')">
                      <input type="hidden" name="action" value="delete_backup">
                      <input type="hidden" name="backup_id" value="<?= $bk['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">Smazat</button>
                    </form>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </section>

        <!-- 05 · DANGER ZONE -->
        <section id="danger" style="display:flex;flex-direction:column;gap:20px;margin-top:36px">
          <div class="set-section-head">
            <div>
              <div class="set-section-num">05 &middot; Nevratné akce</div>
              <h2 class="set-section-title">Danger <em>zone.</em></h2>
            </div>
            <div class="set-section-sub">Akce v této sekci nelze vzít zpět bez ručního obnovení ze zálohy.</div>
          </div>

          <div class="danger-zone">
            <div class="danger-head">
              <div class="danger-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Nevratné akce
              </div>
            </div>
            <div class="danger-row">
              <div>
                <div class="danger-name">Vygenerovat sitemap &amp; robots.txt</div>
                <div class="danger-desc">Přepocte sitemap.xml a robots.txt podle aktuálních příspěvků.</div>
              </div>
              <form method="POST">
                <input type="hidden" name="action" value="generate_sitemap">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Generovat</button>
              </form>
            </div>
            <div class="danger-row">
              <div>
                <div class="danger-name">PHP informace</div>
                <div class="danger-desc">PHP <?= phpversion() ?>, Server: <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?></div>
              </div>
              <a href="?phpinfo=1" class="btn btn-ghost btn-sm" onclick="return confirm('Zobrazit phpinfo()?')">phpinfo</a>
            </div>
          </div>
        </section>

      </div><!-- /set-main -->
    </div><!-- /settings-shell -->

    <div class="save-bar" style="margin-top:32px">
      <div class="save-bar-status">
        <span class="dot-ok"></span>
        <span>Nastavení uložena &middot; <span class="mono" style="color:var(--muted)"><?= date('j.n.Y H:i') ?></span></span>
      </div>
      <div class="save-bar-actions">
        <a href="dashboard.php" class="btn btn-ghost btn-sm">← Zpět na přehled</a>
      </div>
    </div>

  </div>
</main>
</div>

<?php if (isset($_GET['phpinfo']) && $_SESSION['user_role'] === 'IT'): phpinfo(); endif; ?>
<script src="<?= ASSETS_URL ?>js/admin.js"></script>
</body>
</html>
