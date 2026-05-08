<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$post     = new Post();
$category = new Category();
$auth     = new Auth();

$status   = $_GET['status'] ?? 'all';
$catFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 15;

$totalPublished = $post->count('published');
$totalDrafts    = $post->count('draft');
$totalAll       = $totalPublished + $totalDrafts;
$categories     = $category->getAll();

if ($status === 'draft') {
    $posts      = $post->getAll('draft', $perPage, ($page - 1) * $perPage, $catFilter);
    $totalCount = $post->count('draft', $catFilter);
} elseif ($status === 'published') {
    $posts      = $post->getAll('published', $perPage, ($page - 1) * $perPage, $catFilter);
    $totalCount = $post->count('published', $catFilter);
} else {
    $pub  = $post->getAll('published', 100, 0, $catFilter);
    $drft = $post->getAll('draft', 100, 0, $catFilter);
    $all  = array_merge($pub, $drft);
    usort($all, fn($a, $b) =>
        strtotime($b['published_at'] ?? $b['created_at']) -
        strtotime($a['published_at'] ?? $a['created_at'])
    );
    $totalCount = count($all);
    $posts      = array_slice($all, ($page - 1) * $perPage, $perPage);
}

$totalPages = max(1, (int) ceil($totalCount / $perPage));
$flash      = getFlash();

$gradients = [
    'linear-gradient(135deg,#a5b4fc,#667eea)',
    'linear-gradient(135deg,#c4b5fd,#764ba2)',
    'linear-gradient(135deg,#6ee7b7,#10b981)',
    'linear-gradient(135deg,#fcd34d,#f59e0b)',
    'linear-gradient(135deg,#fca5a5,#ef4444)',
    'linear-gradient(135deg,#93c5fd,#3b82f6)',
];

function statusChip(string $s): string {
    return match ($s) {
        'published' => '<span class="chip chip-ok"><span class="bullet"></span>Publikováno</span>',
        'draft'     => '<span class="chip chip-warn"><span class="bullet"></span>Koncept</span>',
        default     => '<span class="chip chip-outline">' . htmlspecialchars($s) . '</span>',
    };
}

$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 2));

function pageUrl(string $status, ?int $cat, int $p): string {
    $q = ['status' => $status, 'page' => $p];
    if ($cat) $q['category'] = $cat;
    return 'posts.php?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Příspěvky · Blog Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.filters{display:flex;gap:10px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.search-mini{position:relative;flex:1;max-width:280px}
.search-mini input{width:100%;padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;background:var(--card);color:var(--ink)}
.search-mini input:focus{outline:none;border-color:var(--accent)}
.search-mini svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--muted)}
.cat-sel{padding:6px 10px;border:1px solid var(--border);border-radius:7px;background:var(--card);font-size:12.5px;font-family:inherit;color:var(--body);cursor:pointer}
.cat-sel:focus{outline:none;border-color:var(--accent)}
.bulk-bar{display:none;align-items:center;gap:12px;padding:10px 20px;background:var(--ink);color:#f3efe2;font-size:12.5px;position:sticky;top:65px;z-index:10}
.bulk-bar.on{display:flex}
.bulk-bar b{color:#fff}
.bulk-actions{margin-left:auto;display:flex;gap:6px}
.bulk-btn{padding:5px 10px;border-radius:5px;font-size:11.5px;color:#f3efe2;border:1px solid rgba(243,239,226,.15);background:none;cursor:pointer;transition:background .15s}
.bulk-btn:hover{background:rgba(243,239,226,.08)}
.bulk-btn.danger{color:#fca5a5;border-color:rgba(252,165,165,.2)}
table.posts{width:100%;border-collapse:collapse}
table.posts thead{background:var(--card-2);border-bottom:1px solid var(--border)}
table.posts th{padding:10px 16px;text-align:left;font-size:11px;font-weight:500;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;font-family:var(--mono)}
table.posts th:first-child{padding-left:20px;width:34px}
table.posts td{padding:12px 16px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:middle}
table.posts td:first-child{padding-left:20px;width:34px}
table.posts tr:hover{background:var(--card-2)}
table.posts tr:last-child td{border-bottom:none}
table.posts input[type=checkbox]{accent-color:var(--ink)}
.post-cell{display:flex;gap:12px;align-items:center;min-width:0}
.post-cell-thumb{width:44px;height:44px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--serif);font-size:20px;font-style:italic}
.post-cell-text{min-width:0}
.post-cell-title{font-size:13.5px;font-weight:500;color:var(--ink);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px}
.post-cell-slug{font-family:var(--mono);font-size:11px;color:var(--muted)}
.row-actions{display:flex;gap:2px;opacity:0;transition:opacity .15s}
tr:hover .row-actions{opacity:1}
.row-ico{width:26px;height:26px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted);transition:background .15s,color .15s;background:none;border:none;cursor:pointer}
.row-ico:hover{background:var(--paper-2);color:var(--ink)}
.row-ico.danger:hover{background:var(--danger-soft);color:var(--danger)}
.pagination{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;font-size:12.5px;color:var(--muted);border-top:1px solid var(--line)}
.page-btns{display:flex;gap:4px}
.pg{width:30px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:12px;color:var(--body);border:1px solid transparent;cursor:pointer;text-decoration:none;transition:background .15s}
.pg:hover{background:var(--paper-2)}
.pg.on{background:var(--ink);color:#f3efe2;border-color:var(--ink)}
.pg.nav-btn{border:1px solid var(--border);padding:0 10px;width:auto;white-space:nowrap}
.pg.nav-btn:hover{border-color:var(--ink-2)}
.pg.disabled{opacity:.4;pointer-events:none}
.del-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.del-modal.on{display:flex}
.del-modal-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.del-modal-title{font-size:18px;font-weight:600;margin-bottom:8px;color:var(--ink)}
.del-modal-text{font-size:13.5px;color:var(--body);margin-bottom:24px}
.del-modal-actions{display:flex;gap:10px;justify-content:center}
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
      <a href="dashboard.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        Přehled
      </a>
      <a href="posts.php" class="active">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>
        Příspěvky <span class="count"><?= $totalAll ?></span>
      </a>
      <a href="media.php">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>
        Média
      </a>
      <a href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 7h10M7 12h10M7 17h7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
        Kategorie <span class="count"><?= count($categories) ?></span>
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
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Odhlásit
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
    <div class="crumb"><span>Workspace</span><span class="sep">/</span><span class="here">Příspěvky</span></div>
    <div class="search">
      <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="text" id="topSearch" placeholder="Hledat v článcích…" autocomplete="off">
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

    <div class="page-head">
      <div>
        <div class="eyebrow"><span class="pulse"></span>Knihovna obsahu</div>
        <h1 class="page-title"><em><?= $totalAll ?> příspěvků</em> &mdash; váš archiv.</h1>
        <p class="page-sub"><?= $totalPublished ?> publikováno, <?= $totalDrafts ?> konceptů. Filtrujte, řaďte a spravujte všechen obsah.</p>
      </div>
      <div class="seg">
        <button onclick="location.href='posts.php?status=all'" <?= $status==='all'?'class="active"':'' ?>>Vše <?= $totalAll ?></button>
        <button onclick="location.href='posts.php?status=published'" <?= $status==='published'?'class="active"':'' ?>>Publikováno <?= $totalPublished ?></button>
        <button onclick="location.href='posts.php?status=draft'" <?= $status==='draft'?'class="active"':'' ?>>Koncepty <?= $totalDrafts ?></button>
      </div>
    </div>

    <div class="panel">
      <div class="filters">
        <div class="search-mini">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <input type="text" id="tableFilter" placeholder="Filtr názvu…" oninput="filterTable()">
        </div>
        <select class="cat-sel" id="catSel" onchange="applyCategory()">
          <option value="">Kategorie: Vše</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div style="flex:1"></div>
        <div style="font-family:var(--mono);font-size:11px;color:var(--muted)">Zobrazeno <b style="color:var(--ink)"><?= count($posts) ?> z <?= $totalCount ?></b></div>
      </div>

      <div class="bulk-bar" id="bulkBar">
        <b><span id="bulkCount">0</span> vybráno</b>
        <span style="color:rgba(243,239,226,.55)">&middot; hromadné akce</span>
        <div class="bulk-actions">
          <button class="bulk-btn" onclick="bulkPublish()">Publikovat</button>
          <button class="bulk-btn danger" onclick="bulkDelete()">Smazat</button>
          <button class="bulk-btn" onclick="clearSel()">× Zrušit</button>
        </div>
      </div>

      <table class="posts">
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th>
            <th style="width:40%">Název článku</th>
            <th>Stav</th>
            <th>Kategorie</th>
            <th>Autor</th>
            <th>Datum</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody id="postsBody">
          <?php foreach ($posts as $i => $p): ?>
          <?php $grad = $gradients[$i % 6]; $letter = mb_strtoupper(mb_substr($p['title'], 0, 1)); ?>
          <tr data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>">
            <td><input type="checkbox" value="<?= $p['id'] ?>" onchange="updateBulk()"></td>
            <td>
              <div class="post-cell">
                <div class="post-cell-thumb" style="background:<?= $grad ?>"><?= htmlspecialchars($letter) ?></div>
                <div class="post-cell-text">
                  <div class="post-cell-title"><?= htmlspecialchars($p['title']) ?></div>
                  <div class="post-cell-slug">/<?= htmlspecialchars($p['slug'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td><?= statusChip($p['status']) ?></td>
            <td><?php if (!empty($p['category_name'])): ?><span class="chip chip-outline"><?= htmlspecialchars($p['category_name']) ?></span><?php else: ?><span style="color:var(--faint)">-</span><?php endif; ?></td>
            <td style="font-size:12.5px;color:var(--body)"><?= htmlspecialchars($p['author_name'] ?? '') ?></td>
            <td style="font-family:var(--mono);font-size:12px;color:var(--body);white-space:nowrap"><?= date('j.n.Y', strtotime($p['published_at'] ?? $p['created_at'])) ?></td>
            <td>
              <div class="row-actions">
                <a href="edit_post.php?id=<?= $p['id'] ?>" class="row-ico" title="Upravit">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </a>
                <?php if ($auth->canEdit($p['author_id'])): ?>
                <button class="row-ico danger" title="Smazat" onclick="confirmDel(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['title'])) ?>')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($posts)): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Zatím žádné příspěvky. <a href="add_post.php" style="color:var(--accent-2)">Vytvořte první</a></td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <div>Stránka <b style="color:var(--ink);font-family:var(--mono)"><?= $page ?></b> z <?= $totalPages ?></div>
        <div class="page-btns">
          <a href="<?= pageUrl($status, $catFilter, 1) ?>" class="pg nav-btn <?= $page<=1?'disabled':'' ?>">&#8592; První</a>
          <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          for ($pn = $start; $pn <= $end; $pn++):
          ?>
          <a href="<?= pageUrl($status, $catFilter, $pn) ?>" class="pg <?= $pn===$page?'on':'' ?>"><?= $pn ?></a>
          <?php endfor; ?>
          <a href="<?= pageUrl($status, $catFilter, $totalPages) ?>" class="pg nav-btn <?= $page>=$totalPages?'disabled':'' ?>">Poslední &#8594;</a>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>
</div>

<div class="del-modal" id="delModal">
  <div class="del-modal-box">
    <div class="del-modal-title">Smazat příspěvek?</div>
    <div class="del-modal-text" id="delModalText"></div>
    <div class="del-modal-actions">
      <button class="btn btn-danger" onclick="execDel()">Ano, smazat</button>
      <button class="btn btn-ghost" onclick="closeDel()">Zrušit</button>
    </div>
  </div>
</div>

<script>
const currentStatus = '<?= $status ?>';
const currentCat    = <?= $catFilter ?: 'null' ?>;
const currentPage   = <?= $page ?>;

function filterTable() {
  const q = document.getElementById('tableFilter').value.toLowerCase();
  document.querySelectorAll('#postsBody tr[data-title]').forEach(tr => {
    tr.style.display = tr.dataset.title.includes(q) ? '' : 'none';
  });
}

function applyCategory() {
  const val = document.getElementById('catSel').value;
  const url = new URL(location.href);
  if (val) url.searchParams.set('category', val);
  else url.searchParams.delete('category');
  url.searchParams.set('page', '1');
  location.href = url.toString();
}

function toggleAll(cb) {
  document.querySelectorAll('#postsBody input[type=checkbox]').forEach(b => b.checked = cb.checked);
  updateBulk();
}

function updateBulk() {
  const n = document.querySelectorAll('#postsBody input[type=checkbox]:checked').length;
  document.getElementById('bulkCount').textContent = n;
  document.getElementById('bulkBar').classList.toggle('on', n > 0);
  const ca = document.getElementById('checkAll');
  const total = document.querySelectorAll('#postsBody input[type=checkbox]').length;
  ca.checked = n === total;
  ca.indeterminate = n > 0 && n < total;
}

function clearSel() {
  document.querySelectorAll('#postsBody input[type=checkbox]').forEach(b => b.checked = false);
  document.getElementById('checkAll').checked = false;
  updateBulk();
}

function getSelectedIds() {
  return Array.from(document.querySelectorAll('#postsBody input[type=checkbox]:checked')).map(b => b.value);
}

function bulkPublish() {
  const ids = getSelectedIds();
  if (!ids.length) return;
  location.href = 'bulk_action.php?action=publish&ids=' + ids.join(',') + '&return=' + encodeURIComponent('?status=' + currentStatus + '&page=' + currentPage);
}

function bulkDelete() {
  const ids = getSelectedIds();
  if (!ids.length) return;
  if (!confirm('Smazat ' + ids.length + ' příspěvků? Tato akce je nevratná.')) return;
  location.href = 'bulk_action.php?action=delete&ids=' + ids.join(',') + '&return=' + encodeURIComponent('?status=' + currentStatus + '&page=' + currentPage);
}

let delId = null;
function confirmDel(id, title) {
  delId = id;
  document.getElementById('delModalText').textContent = 'Opravdu chcete smazat "' + title + '"? Tato akce je nevratná.';
  document.getElementById('delModal').classList.add('on');
}
function closeDel() { document.getElementById('delModal').classList.remove('on'); delId = null; }
function execDel() {
  if (!delId) return;
  location.href = 'delete_post.php?id=' + delId + '&return_filter=' + currentStatus + '&return_page=' + currentPage;
}
document.getElementById('delModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDel(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDel(); });
</script>
</body>
</html>
