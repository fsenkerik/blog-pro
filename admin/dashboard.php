<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$post     = new Post();
$category = new Category();
$media    = new Media();
$auth     = new Auth();

$totalPublished = $post->count('published');
$totalDrafts    = $post->count('draft');
$totalMedia     = $media->getCount('');
$categories     = $category->getAll();
$totalCats      = count($categories);
$totalAll       = $totalPublished + $totalDrafts;

$pub   = $post->getAll('published', 6, 0);
$dft   = $post->getAll('draft', 4, 0);
$_mix  = array_merge($pub, $dft);
usort($_mix, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$recent = array_slice($_mix, 0, 6);
$hero   = !empty($pub) ? $pub[0] : null;

$username  = $_SESSION['username'] ?? '';
$userRole  = $_SESSION['user_role'] ?? '';
$initials  = mb_strtoupper(mb_substr($username, 0, 2));
$roleLabel = match($userRole) {
    'admin'  => 'Administrátor',
    'editor' => 'Editor',
    'it'     => 'IT správce',
    default  => ucfirst($userRole),
};

$hour     = (int)date('H');
$greeting = $hour < 12 ? 'Dobré ráno' : ($hour < 18 ? 'Dobré odpoledne' : 'Dobrý večer');
$days     = ['neděle','pondělí','úterý','středa','čtvrtek','pátek','sobota'];
$months   = ['ledna','února','března','dubna','května','června','července','srpna','září','října','listopadu','prosince'];
$dayLabel = ucfirst($days[(int)date('w')]) . ' · ' . date('j') . '. ' . $months[(int)date('n') - 1] . ' ' . date('Y');

$catColors    = ['#667eea','#764ba2','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899'];
$totalCatPost = max(1, array_sum(array_map(fn($c) => (int)($c['post_count'] ?? 0), $categories)));

$gradients = [
    ['#a5b4fc','#667eea'],['#c4b5fd','#764ba2'],['#6ee7b7','#10b981'],
    ['#fcd34d','#f59e0b'],['#fca5a5','#ef4444'],['#93c5fd','#3b82f6'],
];

function relTime(string $d): string {
    $s = time() - strtotime($d);
    if ($s < 120)    return 'právě';
    if ($s < 3600)   return (int)($s/60) . ' min';
    if ($s < 86400)  return (int)($s/3600) . ' h';
    if ($s < 172800) return 'včera';
    return date('d.m.', strtotime($d));
}

function chipSt(string $s): string {
    return match($s) {
        'published' => '<span class="chip chip-ok"><span class="bullet"></span>Publikováno</span>',
        'draft'     => '<span class="chip chip-warn"><span class="bullet"></span>Koncept</span>',
        default     => '<span class="chip chip-outline">' . htmlspecialchars($s) . '</span>',
    };
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Přehled · Blog Pro</title>
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
.kpi-ico{width:14px;height:14px;color:var(--faint)}
.kpi-value{font-family:var(--serif);font-size:38px;line-height:1;color:var(--ink);letter-spacing:-.02em}
.kpi-note{margin-top:12px;font-size:11.5px;color:var(--muted)}
.grid-2{display:grid;grid-template-columns:1.55fr 1fr;gap:20px;margin-bottom:20px}
.grid-split{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.hero{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:14px;overflow:hidden;color:#fff;display:grid;grid-template-columns:1.2fr 1fr;gap:0;margin-bottom:20px;box-shadow:0 6px 24px -8px rgba(102,126,234,.45)}
.hero-body{padding:28px 32px;display:flex;flex-direction:column;justify-content:space-between;gap:20px}
.hero-tag{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.75)}
.hero-tag .hb{width:6px;height:6px;border-radius:50%;background:#fff;box-shadow:0 0 10px rgba(255,255,255,.8)}
.hero-title{font-family:var(--serif);font-size:30px;line-height:1.1;font-weight:400;margin:8px 0 10px;letter-spacing:-.01em}
.hero-excerpt{font-size:13.5px;color:rgba(255,255,255,.85);line-height:1.55}
.hero-meta{display:flex;gap:20px;padding-top:14px;border-top:1px solid rgba(255,255,255,.18)}
.hero-meta-lbl{font-family:var(--mono);font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.65);margin-bottom:2px}
.hero-meta-val{font-size:14px;font-family:var(--mono)}
.hero-art{background:radial-gradient(circle at 70% 30%,rgba(252,211,77,.35) 0%,transparent 55%),linear-gradient(135deg,#764ba2,#667eea);position:relative;overflow:hidden;min-height:160px}
.hero-art::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px);background-size:32px 32px}
.hero-art-letter{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:160px;color:rgba(255,255,255,.18);font-style:italic}
.strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.strip-item{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:border-color .15s,background .15s}
.strip-item:hover{border-color:var(--ink-2);background:var(--card-2)}
.strip-ico{width:34px;height:34px;border-radius:9px;background:var(--paper-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--body)}
.strip-body{flex:1;min-width:0}
.strip-title{font-size:13px;font-weight:500;color:var(--ink);margin-bottom:1px}
.strip-sub{font-size:11.5px;color:var(--muted)}
.strip-chev{color:var(--faint)}
.post-cards{padding:14px 14px 6px;display:flex;flex-direction:column;gap:10px}
.post-card{display:grid;grid-template-columns:100px 1fr auto;gap:14px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--card);transition:border-color .15s,background .15s}
.post-card:hover{border-color:var(--ink-2);background:var(--card-2)}
.pc-thumb{width:100px;height:76px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--serif);font-style:italic;font-size:32px;border:1px solid var(--border);flex-shrink:0;position:relative;overflow:hidden}
.pc-thumb::after{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:18px 18px;pointer-events:none}
.pc-body{min-width:0;display:flex;flex-direction:column;gap:5px}
.pc-title{font-size:13.5px;font-weight:500;color:var(--ink);line-height:1.3;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}
.pc-excerpt{font-size:12px;color:var(--body);line-height:1.5;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.pc-meta{margin-top:auto;padding-top:5px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:11.5px;color:var(--muted)}
.pc-meta .dot-sep{color:var(--faint)}
.pc-actions{display:flex;flex-direction:column;align-items:flex-end;justify-content:flex-start;gap:4px}
.pc-actions-row{display:flex;gap:2px;opacity:0;transition:opacity .15s}
.post-card:hover .pc-actions-row{opacity:1}
.pc-ico{width:26px;height:26px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted);border:1px solid transparent;transition:background .15s,color .15s,border-color .15s}
.pc-ico:hover{background:var(--paper-2);color:var(--ink);border-color:var(--border)}
.activity{padding:8px 0 4px}
.act{display:grid;grid-template-columns:28px 1fr auto;gap:10px;padding:10px 20px;align-items:flex-start;position:relative}
.act::before{content:'';position:absolute;left:34px;top:30px;bottom:-10px;width:1px;background:var(--line)}
.act:last-child::before{display:none}
.act-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;background:var(--paper);border:1px solid var(--border);color:var(--body);position:relative;z-index:1}
.act-dot.ok{background:var(--ok-soft);color:var(--ok);border-color:transparent}
.act-dot.accent{background:var(--accent-soft);color:var(--accent);border-color:transparent}
.act-body{font-size:13px;line-height:1.45;color:var(--ink-2)}
.act-body b{font-weight:500;color:var(--ink)}
.act-body .ref{color:var(--body);font-family:var(--mono);font-size:12px}
.act-time{font-family:var(--mono);font-size:11px;color:var(--muted);white-space:nowrap}
.table-foot{padding:12px 20px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--muted)}
.cats{padding:16px 20px 20px}
.cats-bar{display:flex;height:8px;border-radius:999px;overflow:hidden;margin-bottom:16px;background:var(--paper-2)}
.cat-list{display:flex;flex-direction:column;gap:10px}
.cat-item{display:grid;grid-template-columns:10px 1fr auto;gap:10px;align-items:center;font-size:13px}
.cat-swatch{width:10px;height:10px;border-radius:3px}
.cat-name{color:var(--ink);font-weight:500}
.cat-count{color:var(--muted);font-family:var(--mono);font-size:12px}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px}
.flash-success{background:var(--ok-soft);color:var(--ok)}
.flash-error{background:var(--danger-soft);color:var(--danger)}
.search-results{position:absolute;top:100%;left:0;right:0;background:var(--card);border:1px solid var(--border);border-radius:10px;margin-top:6px;max-height:320px;overflow-y:auto;box-shadow:0 8px 30px rgba(0,0,0,.1);display:none;z-index:100}
@media(max-width:1100px){.grid-2{grid-template-columns:1fr}.grid-split{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.kpi:nth-child(2){border-right:none}.kpi:nth-child(1),.kpi:nth-child(2){border-bottom:1px solid var(--border)}.strip{grid-template-columns:repeat(2,1fr)}.hero{grid-template-columns:1fr}.hero-art{min-height:120px}}
</style>
</head>
<body>
<div class="app">

  <aside class="side">
    <div class="brand">
      <div class="brand-mark">BP</div>
      <div>
        <div class="brand-name">Blog Pro</div>
        <div class="brand-sub">CMS / v1.0</div>
      </div>
    </div>
    <div>
      <div class="nav-label">Workspace</div>
      <nav class="nav">
        <a href="dashboard.php" class="active">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
          Přehled
        </a>
        <a href="posts.php">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>
          Příspěvky <span class="count"><?= $totalAll ?></span>
        </a>
        <a href="media.php">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>
          Média <span class="count"><?= $totalMedia ?></span>
        </a>
        <a href="#">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 7h10M7 12h10M7 17h7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
          Kategorie <span class="count"><?= $totalCats ?></span>
        </a>
        <a href="#">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          Uživatelé
        </a>
      </nav>
    </div>
    <div>
      <div class="nav-label">Nástroje</div>
      <nav class="nav">
        <a href="settings.php">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
          Nastavení
        </a>
        <a href="logout.php">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Odhlásit se
        </a>
      </nav>
    </div>
    <div class="side-user">
      <div class="avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="side-user-info">
        <div class="side-user-name"><?= htmlspecialchars($username) ?></div>
        <div class="side-user-role"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="crumb">
        <span>Workspace</span><span class="sep">/</span><span class="here">Přehled</span>
      </div>
      <div class="search">
        <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="text" id="quickSearch" placeholder="Hledat články…" autocomplete="off">
        <div id="searchResults" class="search-results"></div>
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
        <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
      <?php endif; ?>

      <div class="page-head">
        <div>
          <div class="eyebrow"><span class="pulse"></span><?= htmlspecialchars($dayLabel) ?></div>
          <h1 class="page-title"><?= htmlspecialchars($greeting) ?>, <em><?= htmlspecialchars($username) ?>.</em></h1>
          <p class="page-sub">Máte <b><?= $totalPublished ?> publikovaných</b> a <b><?= $totalDrafts ?> konceptů</b>. Celkem <?= $totalMedia ?> mediálních souborů.</p>
        </div>
      </div>

      <!-- KPI -->
      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">
            <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/></svg>
            Publikováno
          </div>
          <div class="kpi-value mono"><?= $totalPublished ?></div>
          <div class="kpi-note">z <?= $totalAll ?> celkem</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">
            <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
            Koncepty
          </div>
          <div class="kpi-value mono"><?= $totalDrafts ?></div>
          <div class="kpi-note">čeká na publikaci</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">
            <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7h10M7 12h10M7 17h7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
            Kategorie
          </div>
          <div class="kpi-value mono"><?= $totalCats ?></div>
          <div class="kpi-note">aktivních kategorií</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">
            <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/></svg>
            Média
          </div>
          <div class="kpi-value mono"><?= $totalMedia ?></div>
          <div class="kpi-note">nahraných souborů</div>
        </div>
      </div>

      <!-- Hero -->
      <?php if ($hero): ?>
      <?php $hL = mb_strtoupper(mb_substr($hero['title'], 0, 1)); ?>
      <div class="hero">
        <div class="hero-body">
          <div>
            <div class="hero-tag"><span class="hb"></span>Poslední publikace</div>
            <h2 class="hero-title"><?= htmlspecialchars($hero['title']) ?></h2>
            <?php if (!empty($hero['excerpt'])): ?>
            <p class="hero-excerpt"><?= htmlspecialchars(mb_substr($hero['excerpt'], 0, 160)) ?></p>
            <?php endif; ?>
          </div>
          <div class="hero-meta">
            <div><div class="hero-meta-lbl">Autor</div><div class="hero-meta-val"><?= htmlspecialchars($hero['author_name'] ?? '—') ?></div></div>
            <div><div class="hero-meta-lbl">Datum</div><div class="hero-meta-val"><?= formatDate($hero['published_at'] ?? $hero['created_at']) ?></div></div>
            <div><div class="hero-meta-lbl">Kategorie</div><div class="hero-meta-val"><?= htmlspecialchars($hero['category_name'] ?? '—') ?></div></div>
          </div>
        </div>
        <div class="hero-art">
          <div class="hero-art-letter"><?= $hL ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Strip -->
      <div class="strip">
        <a href="add_post.php" class="strip-item">
          <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></div>
          <div class="strip-body"><div class="strip-title">Nový článek</div><div class="strip-sub">Začít psát</div></div>
          <svg class="strip-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="posts.php?status=draft" class="strip-item">
          <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg></div>
          <div class="strip-body"><div class="strip-title">Koncepty</div><div class="strip-sub"><?= $totalDrafts ?> čeká na dokončení</div></div>
          <svg class="strip-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="media.php" class="strip-item">
          <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg></div>
          <div class="strip-body"><div class="strip-title">Galerie médií</div><div class="strip-sub"><?= $totalMedia ?> souborů</div></div>
          <svg class="strip-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="settings.php" class="strip-item">
          <div class="strip-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg></div>
          <div class="strip-body"><div class="strip-title">Nastavení</div><div class="strip-sub">Konfigurace webu</div></div>
          <svg class="strip-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>

      <!-- Posts + Activity -->
      <div class="grid-2">
        <div class="panel">
          <div class="panel-head">
            <div class="panel-title-row">
              <span class="panel-title">Poslední příspěvky</span>
              <span class="tag mono"><?= $totalPublished ?> / <?= $totalAll ?></span>
            </div>
            <a href="posts.php" class="panel-link">Zobrazit vše →</a>
          </div>
          <div class="post-cards">
            <?php if (empty($recent)): ?>
              <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">Zatím žádné příspěvky. <a href="add_post.php" style="color:var(--accent-2)">Vytvořte první →</a></div>
            <?php else: ?>
            <?php foreach ($recent as $p): ?>
            <?php $g = $gradients[$p['id'] % count($gradients)]; $lt = mb_strtoupper(mb_substr($p['title'], 0, 1)); ?>
            <div class="post-card">
              <div class="pc-thumb" style="background:linear-gradient(135deg,<?= $g[0] ?>,<?= $g[1] ?>)"><?= htmlspecialchars($lt) ?></div>
              <div class="pc-body">
                <div class="pc-title"><?= htmlspecialchars($p['title']) ?></div>
                <?php if (!empty($p['excerpt'])): ?>
                <div class="pc-excerpt"><?= htmlspecialchars(mb_substr($p['excerpt'], 0, 100)) ?></div>
                <?php endif; ?>
                <div class="pc-meta">
                  <?= chipSt($p['status']) ?>
                  <?php if (!empty($p['category_name'])): ?><span class="chip chip-outline"><?= htmlspecialchars($p['category_name']) ?></span><?php endif; ?>
                  <span class="dot-sep" style="color:var(--faint)">·</span>
                  <span><?= htmlspecialchars($p['author_name'] ?? '') ?></span>
                  <span class="dot-sep" style="color:var(--faint)">·</span>
                  <span class="mono"><?= relTime($p['created_at']) ?></span>
                </div>
              </div>
              <div class="pc-actions">
                <div class="pc-actions-row">
                  <a href="edit_post.php?id=<?= $p['id'] ?>" class="pc-ico" title="Upravit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="table-foot">
            <div>Zobrazuji <b style="color:var(--ink)"><?= count($recent) ?> z <?= $totalAll ?></b> příspěvků</div>
            <a href="posts.php" class="btn btn-ghost btn-sm">Všechny příspěvky →</a>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span class="panel-title">Aktivita</span>
            <a href="posts.php" class="panel-link">Všechny příspěvky →</a>
          </div>
          <div class="activity">
            <?php if (empty($recent)): ?>
              <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">Žádná aktivita.</div>
            <?php else: ?>
            <?php foreach (array_slice($recent, 0, 7) as $p): ?>
            <div class="act">
              <div class="act-dot <?= $p['status'] === 'published' ? 'ok' : 'accent' ?>">
                <?php if ($p['status'] === 'published'): ?>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                <?php endif; ?>
              </div>
              <div class="act-body">
                <b><?= htmlspecialchars($p['author_name'] ?? 'Systém') ?></b>
                <?= $p['status'] === 'published' ? 'publikoval/a' : 'upravil/a koncept' ?>
                <span class="ref"><?= htmlspecialchars(mb_substr($p['title'], 0, 45)) ?><?= mb_strlen($p['title']) > 45 ? '…' : '' ?></span>
              </div>
              <div class="act-time"><?= relTime($p['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($categories)): ?>
      <div class="grid-split">
        <div class="panel">
          <div class="panel-head">
            <div class="panel-title-row">
              <span class="panel-title">Kategorie</span>
              <span class="tag mono"><?= $totalCats ?> aktivních</span>
            </div>
          </div>
          <div class="cats">
            <?php if ($totalCatPost > 1): ?>
            <div class="cats-bar">
              <?php foreach ($categories as $ci => $cat): ?>
              <?php $pct = round((($cat['post_count'] ?? 0) / $totalCatPost) * 100, 1); $color = $catColors[$ci % count($catColors)]; ?>
              <?php if ($pct > 0): ?>
              <div style="width:<?= $pct ?>%;background:<?= $color ?>" title="<?= htmlspecialchars($cat['name']) ?>"></div>
              <?php endif; ?>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="cat-list">
              <?php foreach ($categories as $ci => $cat): ?>
              <?php $color = $catColors[$ci % count($catColors)]; ?>
              <div class="cat-item">
                <div class="cat-swatch" style="background:<?= $color ?>"></div>
                <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                <div class="cat-count"><?= isset($cat['post_count']) ? $cat['post_count'] . ' čl.' : '' ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span class="panel-title">Koncepty</span>
            <a href="posts.php?status=draft" class="panel-link">Zobrazit vše →</a>
          </div>
          <div class="activity">
            <?php if (empty($dft)): ?>
              <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">Žádné koncepty.</div>
            <?php else: ?>
            <?php foreach (array_slice($dft, 0, 4) as $p): ?>
            <div class="act">
              <div class="act-dot accent">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
              </div>
              <div class="act-body">
                <b><?= htmlspecialchars(mb_substr($p['title'], 0, 40)) ?><?= mb_strlen($p['title']) > 40 ? '…' : '' ?></b><br>
                <span style="color:var(--muted);font-size:12px"><?= htmlspecialchars($p['author_name'] ?? '') ?> · <?= htmlspecialchars($p['category_name'] ?? '—') ?></span>
              </div>
              <a href="edit_post.php?id=<?= $p['id'] ?>" class="act-time" style="color:var(--accent-2)">Upravit →</a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<script>
const qi = document.getElementById('quickSearch');
const qr = document.getElementById('searchResults');
let qt = null;
if (qi) {
  qi.addEventListener('input', function() {
    clearTimeout(qt);
    const q = this.value.trim();
    if (q.length < 2) { qr.style.display = 'none'; return; }
    qr.style.display = 'block';
    qr.innerHTML = '<div style="padding:14px;color:var(--muted);font-size:13px">Hledám…</div>';
    qt = setTimeout(() => {
      fetch('search_posts.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(d => {
          if (d.success && d.results.length) {
            qr.innerHTML = d.results.map(p =>
              `<div onclick="location.href='edit_post.php?id=${p.id}'" style="padding:11px 16px;border-bottom:1px solid var(--line);cursor:pointer" onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background=''">
                <div style="font-weight:500;font-size:13px;color:var(--ink)">${p.title.replace(/</g,'&lt;')}</div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:2px">${p.status === 'published' ? 'Publikováno' : 'Koncept'} · ${p.category_name || '—'}</div>
              </div>`
            ).join('');
          } else {
            qr.innerHTML = '<div style="padding:18px;text-align:center;color:var(--muted);font-size:13px">Nic nenalezeno</div>';
          }
        });
    }, 280);
  });
  document.addEventListener('click', e => { if (!e.target.closest('.search')) qr.style.display = 'none'; });
}
</script>
</body>
</html>
