<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$post     = new Post();
$category = new Category();
$media    = new Media();

$totalPublished = $post->count('published');
$totalDrafts    = $post->count('draft');
$totalScheduled = $post->count('scheduled');
$totalAll       = $totalPublished + $totalDrafts + $totalScheduled;
$categories     = $category->getAll();
$totalMedia     = $media->getCount('');

$published = $post->getAll('published', 6, 0);
$drafts    = $post->getAll('draft', 4, 0);
$scheduled = $post->getAll('scheduled', 4, 0);
$recent    = array_merge($published, $drafts, $scheduled);
usort($recent, static fn($a, $b) =>
    strtotime($b['published_at'] ?? $b['scheduled_at'] ?? $b['created_at']) -
    strtotime($a['published_at'] ?? $a['scheduled_at'] ?? $a['created_at'])
);
$recent = array_slice($recent, 0, 6);

$heroArr = $post->getAll('published', 1, 0);
$hero    = $heroArr[0] ?? null;

$scheduledPreview = $post->getAll('scheduled', 5, 0);

$flash = getFlash();
$tClasses = ['t-0', 't-1', 't-2', 't-3', 't-4', 't-5'];

function relTimeDashboard(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60) {
        return 'prave ted';
    }
    if ($diff < 3600) {
        return 'pred ' . floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return 'pred ' . floor($diff / 3600) . ' h';
    }
    if ($diff < 172800) {
        return 'vcera';
    }
    return date('j. n. Y', strtotime($date));
}

function chipDashboard(string $status): string {
    return match ($status) {
        'published' => '<span class="chip chip-ok"><span class="bullet"></span>Publikovano</span>',
        'draft' => '<span class="chip chip-warn"><span class="bullet"></span>Koncept</span>',
        'scheduled' => '<span class="chip chip-violet"><span class="bullet"></span>Planovano</span>',
        default => '<span class="chip chip-outline">' . htmlspecialchars($status) . '</span>',
    };
}

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Dobre rano' : ($hour < 18 ? 'Dobre odpoledne' : 'Dobry vecer');
$username = $_SESSION['username'] ?? 'uzivateli';
$initials = strtoupper(substr($username, 0, 2));
$catColors = ['#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#06b6d4'];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Prehled | Blog Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:24px}
.kpi{padding:20px 22px;border-right:1px solid var(--border);transition:background .15s}
.kpi:last-child{border-right:none}
.kpi:hover{background:var(--card-2)}
.kpi-label{font-size:12px;color:var(--muted);font-weight:500;display:flex;align-items:center;gap:6px;margin-bottom:10px}
.kpi-label .ico{width:14px;height:14px;color:var(--faint)}
.kpi-value{font-family:var(--serif);font-size:38px;line-height:1;color:var(--ink);letter-spacing:-0.02em}
.kpi-note{margin-top:14px;font-size:11.5px;color:var(--muted)}
.grid-2{display:grid;grid-template-columns:1.55fr 1fr;gap:20px;margin-bottom:20px}
.grid-split{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.post-cards{padding:14px 14px 6px;display:flex;flex-direction:column;gap:10px}
.post-card{display:grid;grid-template-columns:120px 1fr auto;gap:16px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--card);transition:border-color .15s,background .15s,box-shadow .15s}
.post-card:hover{border-color:var(--ink-2);background:var(--card-2);box-shadow:0 1px 2px rgba(0,0,0,.02)}
.pc-thumb{width:120px;height:90px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--serif);font-style:italic;font-size:36px;border:1px solid var(--border)}
.pc-thumb.t-0{background:linear-gradient(135deg,#a5b4fc,#667eea)}
.pc-thumb.t-1{background:linear-gradient(135deg,#c4b5fd,#764ba2)}
.pc-thumb.t-2{background:linear-gradient(135deg,#6ee7b7,#10b981)}
.pc-thumb.t-3{background:linear-gradient(135deg,#fcd34d,#f59e0b)}
.pc-thumb.t-4{background:linear-gradient(135deg,#fca5a5,#ef4444)}
.pc-thumb.t-5{background:linear-gradient(135deg,#93c5fd,#3b82f6)}
.pc-body{min-width:0;display:flex;flex-direction:column;gap:6px}
.pc-title{font-size:14px;font-weight:500;color:var(--ink);line-height:1.35;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}
.pc-excerpt{font-size:12.5px;color:var(--body);line-height:1.5;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-top:2px}
.pc-meta{margin-top:auto;padding-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:11.5px;color:var(--muted)}
.pc-actions-row{display:flex;gap:2px;opacity:0;transition:opacity .15s}
.post-card:hover .pc-actions-row{opacity:1}
.pc-ico{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted);border:1px solid transparent;transition:background .15s,color .15s,border-color .15s}
.pc-ico:hover{background:var(--paper-2);color:var(--ink);border-color:var(--border)}
.pc-ico.danger:hover{color:var(--danger);border-color:var(--danger-soft);background:var(--danger-soft)}
.chip-violet{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.16);color:#7c3aed;font-size:11px;font-weight:500}
.chip-violet .bullet{width:6px;height:6px;border-radius:50%;background:currentColor}
.activity{padding:8px 0 4px}
.act{display:grid;grid-template-columns:28px 1fr auto;gap:10px;padding:10px 20px;align-items:flex-start;position:relative}
.act::before{content:'';position:absolute;left:34px;top:30px;bottom:-10px;width:1px;background:var(--line)}
.act:last-child::before{display:none}
.act-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;background:var(--paper);border:1px solid var(--border);color:var(--body);position:relative;z-index:1}
.act-dot.ok{background:var(--ok-soft);color:var(--ok);border-color:transparent}
.act-dot.accent{background:var(--accent-soft);color:var(--accent);border-color:transparent}
.act-dot.violet{background:rgba(124,58,237,.12);color:#7c3aed;border-color:transparent}
.act-body{font-size:13px;line-height:1.45;color:var(--ink-2)}
.act-body b{font-weight:500;color:var(--ink)}
.act-time{font-family:var(--mono);font-size:11px;color:var(--muted);white-space:nowrap}
.table-foot{padding:12px 20px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--muted)}
.cats{padding:16px 20px 20px}
.cats-bar{display:flex;height:8px;border-radius:999px;overflow:hidden;margin-bottom:16px;background:var(--paper-2)}
.cat-list{display:flex;flex-direction:column;gap:10px}
.cat-item{display:grid;grid-template-columns:10px 1fr auto auto;gap:10px;align-items:center;font-size:13px}
.cat-swatch{width:10px;height:10px;border-radius:3px}
.cat-name{color:var(--ink);font-weight:500}
.cat-count{color:var(--muted);font-family:var(--mono);font-size:12px}
.cat-pct{color:var(--body);font-family:var(--mono);font-size:12px;min-width:38px;text-align:right}
.hero{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:14px;overflow:hidden;color:#fff;display:grid;grid-template-columns:1.2fr 1fr;margin-bottom:20px;box-shadow:0 6px 24px -8px rgba(102,126,234,.45)}
.hero-body{padding:28px 32px;display:flex;flex-direction:column;justify-content:space-between;gap:24px}
.hero-tag{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.75)}
.hero-tag .bullet{width:6px;height:6px;border-radius:50%;background:#fff;box-shadow:0 0 10px rgba(255,255,255,.8)}
.hero-title{font-family:var(--serif);font-size:28px;line-height:1.15;font-weight:400;margin:10px 0 12px;letter-spacing:-0.01em}
.hero-excerpt{font-size:13.5px;color:rgba(255,255,255,.85);line-height:1.55}
.hero-foot{display:flex;gap:20px;padding-top:14px;border-top:1px solid rgba(255,255,255,.18)}
.hero-stat-label{font-family:var(--mono);font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.65);margin-bottom:2px}
.hero-stat-val{font-family:var(--serif);font-size:15px}
.hero-art{background:radial-gradient(circle at 70% 30%,rgba(252,211,77,.35) 0%,transparent 55%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.18) 0%,transparent 50%),linear-gradient(135deg,#764ba2,#667eea);position:relative;overflow:hidden}
.hero-art::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px);background-size:32px 32px}
.hero-art-letter{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:160px;color:rgba(255,255,255,.18);font-style:italic}
.strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.strip-item{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:border-color .15s,background .15s}
.strip-item:hover{border-color:var(--ink-2);background:var(--card-2)}
.strip-ico{width:34px;height:34px;border-radius:9px;background:var(--paper-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--body);flex-shrink:0}
.strip-body{flex:1;min-width:0}
.strip-title{font-size:13px;font-weight:500;color:var(--ink);margin-bottom:1px}
.strip-sub{font-size:11.5px;color:var(--muted)}
.mini-list{padding:12px 20px 16px;display:flex;flex-direction:column;gap:8px}
.mini-row{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;border:1px solid var(--border);background:var(--card-2);font-size:13px}
.mini-row-title{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ink);font-weight:500}
.mini-row-time{font-family:var(--mono);font-size:11px;color:var(--muted);white-space:nowrap}
.del-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.del-modal.on{display:flex}
.del-modal-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.del-modal-title{font-size:18px;font-weight:600;margin-bottom:8px;color:var(--ink)}
.del-modal-text{font-size:13.5px;color:var(--body);margin-bottom:24px}
.del-modal-actions{display:flex;gap:10px;justify-content:center}
@media(max-width:1100px){.grid-2{grid-template-columns:1fr}.grid-split{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.kpi:nth-child(2){border-right:none}.kpi:nth-child(1),.kpi:nth-child(2){border-bottom:1px solid var(--border)}.strip{grid-template-columns:repeat(2,1fr)}.hero{grid-template-columns:1fr}.hero-art{min-height:160px}}
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
      <a href="dashboard.php" class="active">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        Prehled
      </a>
      <a href="posts.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>
        Prispevky <span class="count"><?= $totalAll ?></span>
      </a>
      <a href="media.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>
        Media <span class="count"><?= $totalMedia ?></span>
      </a>
    </nav>
  </div>
  <div>
    <div class="nav-label">Nastroje</div>
    <nav class="nav">
      <a href="settings.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
        Nastaveni
      </a>
      <a href="logout.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Odhlasit
      </a>
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
    <div class="crumb"><span>Workspace</span><span class="sep">/</span><span class="here">Prehled</span></div>
    <div class="search">
      <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="text" id="topSearch" placeholder="Hledat clanky..." autocomplete="off">
      <span class="kbd">⌘ K</span>
    </div>
    <div class="top-actions">
      <a href="add_post.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Novy clanek
      </a>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
      <div style="background:var(--<?= $flash['type'] === 'success' ? 'ok' : 'danger' ?>-soft);color:var(--<?= $flash['type'] === 'success' ? 'ok' : 'danger' ?>);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="page-head">
      <div>
        <div class="eyebrow"><span class="pulse"></span><?= date('l, j. n. Y') ?></div>
        <h1 class="page-title"><?= $greeting ?>, <em><?= htmlspecialchars($username) ?>.</em></h1>
        <p class="page-sub">Celkem <b><?= $totalAll ?> prispevku</b> - <?= $totalPublished ?> publikovano, <?= $totalScheduled ?> planovano, <?= $totalDrafts ?> konceptu.</p>
      </div>
    </div>

    <div class="kpi-grid">
      <div class="kpi">
        <div class="kpi-label"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/></svg>Publikovano</div>
        <div class="kpi-value mono"><?= $totalPublished ?></div>
        <div class="kpi-note">z <?= $totalAll ?> celkem</div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Planovano</div>
        <div class="kpi-value mono"><?= $totalScheduled ?></div>
        <div class="kpi-note">ceka na automaticke vydani</div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7h10M7 12h10M7 17h7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>Kategorie</div>
        <div class="kpi-value mono"><?= count($categories) ?></div>
        <div class="kpi-note">aktivnich</div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Media</div>
        <div class="kpi-value mono"><?= $totalMedia ?></div>
        <div class="kpi-note">souboru</div>
      </div>
    </div>

    <?php if ($hero): ?>
    <div class="hero">
      <div class="hero-body">
        <div>
          <div class="hero-tag"><span class="bullet"></span>Nejnovejsi · Publikovano</div>
          <h2 class="hero-title"><?= htmlspecialchars($hero['title']) ?></h2>
          <?php if (!empty($hero['excerpt'])): ?>
            <p class="hero-excerpt"><?= htmlspecialchars(mb_substr($hero['excerpt'], 0, 180)) ?></p>
          <?php endif; ?>
        </div>
        <div class="hero-foot">
          <div>
            <div class="hero-stat-label">Kategorie</div>
            <div class="hero-stat-val"><?= htmlspecialchars($hero['category_name'] ?? '-') ?></div>
          </div>
          <div>
            <div class="hero-stat-label">Datum</div>
            <div class="hero-stat-val"><?= date('j. n. Y', strtotime($hero['published_at'] ?? $hero['created_at'])) ?></div>
          </div>
          <div>
            <div class="hero-stat-label">Autor</div>
            <div class="hero-stat-val"><?= htmlspecialchars($hero['author_name'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      <div class="hero-art"><div class="hero-art-letter"><?= mb_strtoupper(mb_substr($hero['title'], 0, 1)) ?></div></div>
    </div>
    <?php endif; ?>

    <div class="strip">
      <a href="add_post.php" class="strip-item">
        <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></div>
        <div class="strip-body"><div class="strip-title">Novy prispevek</div><div class="strip-sub">Napsat clanek</div></div>
      </a>
      <a href="posts.php" class="strip-item">
        <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg></div>
        <div class="strip-body"><div class="strip-title">Vsechny prispevky</div><div class="strip-sub"><?= $totalAll ?> celkem</div></div>
      </a>
      <a href="posts.php?status=scheduled" class="strip-item">
        <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <div class="strip-body"><div class="strip-title">Planovane</div><div class="strip-sub"><?= $totalScheduled ?> cekajicich</div></div>
      </a>
      <a href="media.php" class="strip-item">
        <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg></div>
        <div class="strip-body"><div class="strip-title">Galerie medii</div><div class="strip-sub"><?= $totalMedia ?> souboru</div></div>
      </a>
    </div>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title-row">
            <span class="panel-title">Posledni prispevky</span>
            <span class="tag mono"><?= $totalPublished ?> / <?= $totalAll ?></span>
          </div>
          <a href="posts.php" class="panel-link">Zobrazit vse →</a>
        </div>
        <div class="post-cards">
          <?php foreach ($recent as $i => $item): ?>
            <?php $tc = $tClasses[$i % count($tClasses)]; ?>
            <div class="post-card">
              <div class="pc-thumb <?= $tc ?>"><?= htmlspecialchars(mb_strtoupper(mb_substr($item['title'], 0, 1))) ?></div>
              <div class="pc-body">
                <div class="pc-title"><?= htmlspecialchars($item['title']) ?></div>
                <?php if (!empty($item['excerpt'])): ?>
                  <div class="pc-excerpt"><?= htmlspecialchars($item['excerpt']) ?></div>
                <?php endif; ?>
                <div class="pc-meta">
                  <?= chipDashboard($item['status']) ?>
                  <?php if (!empty($item['category_name'])): ?>
                    <span class="chip chip-outline"><?= htmlspecialchars($item['category_name']) ?></span>
                  <?php endif; ?>
                  <span><?= htmlspecialchars($item['author_name'] ?? '') ?></span>
                  <span class="mono"><?= relTimeDashboard($item['published_at'] ?? $item['scheduled_at'] ?? $item['created_at']) ?></span>
                </div>
              </div>
              <div class="pc-actions-row">
                <a href="edit_post.php?id=<?= $item['id'] ?>" class="pc-ico" title="Upravit">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </a>
                <button class="pc-ico danger" title="Smazat" onclick="confirmDel(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['title'])) ?>')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?>
            <p style="padding:20px;text-align:center;color:var(--muted);font-size:13px">Zatim zadne prispevky. <a href="add_post.php" style="color:var(--accent-2)">Vytvorte prvni</a></p>
          <?php endif; ?>
        </div>
        <div class="table-foot">
          <div>Zobrazuji <b style="color:var(--ink)"><?= count($recent) ?> z <?= $totalAll ?></b> prispevku</div>
          <a href="posts.php" class="btn btn-ghost btn-sm">Vsechny prispevky →</a>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><span class="panel-title">Aktivita</span></div>
        <div class="activity">
          <?php foreach (array_slice($recent, 0, 6) as $item): ?>
            <div class="act">
              <div class="act-dot <?= $item['status'] === 'published' ? 'ok' : ($item['status'] === 'scheduled' ? 'violet' : 'accent') ?>">
                <?php if ($item['status'] === 'published'): ?>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <?php elseif ($item['status'] === 'scheduled'): ?>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <?php else: ?>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                <?php endif; ?>
              </div>
              <div class="act-body">
                <b><?= htmlspecialchars($item['author_name'] ?? $username) ?></b>
                <?= $item['status'] === 'published' ? ' publikoval/a ' : ($item['status'] === 'scheduled' ? ' naplanoval/a ' : ' upravil/a ') ?>
                <span style="color:var(--body);font-size:12px"><?= htmlspecialchars(mb_substr($item['title'], 0, 50)) ?></span>
              </div>
              <div class="act-time"><?= relTimeDashboard($item['published_at'] ?? $item['scheduled_at'] ?? $item['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?>
            <p style="padding:20px;text-align:center;color:var(--muted);font-size:13px">Zatim zadna aktivita.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="grid-split">
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title-row">
            <span class="panel-title">Rozdeleni podle kategorii</span>
            <span class="tag mono"><?= $totalAll ?> clanku</span>
          </div>
        </div>
        <div class="cats">
          <?php if (!empty($categories) && $totalAll > 0): ?>
            <div class="cats-bar">
              <?php foreach ($categories as $index => $cat): ?>
                <?php
                $pct = $totalAll > 0 ? round(($cat['post_count'] ?? 0) / $totalAll * 100, 1) : 0;
                if ($pct <= 0) {
                    continue;
                }
                ?>
                <div style="width:<?= $pct ?>%;background:<?= $catColors[$index % count($catColors)] ?>" title="<?= htmlspecialchars($cat['name']) ?> <?= $pct ?>%"></div>
              <?php endforeach; ?>
            </div>
            <div class="cat-list">
              <?php foreach ($categories as $index => $cat): ?>
                <?php
                $count = $cat['post_count'] ?? 0;
                $pct = $totalAll > 0 ? round($count / $totalAll * 100, 1) : 0;
                ?>
                <div class="cat-item">
                  <div class="cat-swatch" style="background:<?= $catColors[$index % count($catColors)] ?>"></div>
                  <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                  <div class="cat-count"><?= $count ?> clanku</div>
                  <div class="cat-pct"><?= $pct ?>%</div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p style="padding:16px;color:var(--muted);font-size:13px">Zatim zadne kategorie.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <span class="panel-title">Planovane prispevky</span>
          <a href="posts.php?status=scheduled" class="panel-link">Vsechny →</a>
        </div>
        <div class="mini-list">
          <?php foreach ($scheduledPreview as $item): ?>
            <div class="mini-row">
              <div class="mini-row-title"><?= htmlspecialchars($item['title']) ?></div>
              <div class="mini-row-time"><?= !empty($item['scheduled_at']) ? date('d.m.Y H:i', strtotime($item['scheduled_at'])) : relTimeDashboard($item['created_at']) ?></div>
              <a href="edit_post.php?id=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">Upravit</a>
            </div>
          <?php endforeach; ?>
          <?php if (empty($scheduledPreview)): ?>
            <p style="padding:8px;color:var(--muted);font-size:13px">Zadne planovane prispevky.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
</div>

<div class="del-modal" id="delModal">
  <div class="del-modal-box">
    <div class="del-modal-title">Smazat prispevek?</div>
    <div class="del-modal-text" id="delModalText"></div>
    <div class="del-modal-actions">
      <button class="btn btn-danger" onclick="execDel()">Ano, smazat</button>
      <button class="btn btn-ghost" onclick="closeDel()">Zrusit</button>
    </div>
  </div>
</div>

<script src="<?= ASSETS_URL ?>js/admin.js"></script>
<script>
let delId = null;
function confirmDel(id, title) {
  delId = id;
  document.getElementById('delModalText').textContent = 'Opravdu chcete smazat "' + title + '"? Tato akce je nevratna.';
  document.getElementById('delModal').classList.add('on');
}
function closeDel() {
  document.getElementById('delModal').classList.remove('on');
  delId = null;
}
function execDel() {
  if (delId) {
    window.location.href = 'delete_post.php?id=' + delId;
  }
}
document.getElementById('delModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) {
    closeDel();
  }
});
</script>
</body>
</html>
