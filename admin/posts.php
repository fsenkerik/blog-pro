<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$post = new Post();
$cat  = new Category();
$auth = new Auth();

$status         = $_GET['status'] ?? 'all';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$page           = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage        = 15;

$totalPublished = $post->count('published', $categoryFilter);
$totalDrafts    = $post->count('draft', $categoryFilter);

if ($status === 'published') {
    $posts      = $post->getAll('published', $perPage, ($page - 1) * $perPage, $categoryFilter);
    $totalCount = $totalPublished;
} elseif ($status === 'draft') {
    $posts      = $post->getAll('draft', $perPage, ($page - 1) * $perPage, $categoryFilter);
    $totalCount = $totalDrafts;
} else {
    $pub   = $post->getAll('published', 500, 0, $categoryFilter);
    $dft   = $post->getAll('draft', 500, 0, $categoryFilter);
    $all   = array_merge($pub, $dft);
    usort($all, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $totalCount = count($all);
    $posts      = array_slice($all, ($page - 1) * $perPage, $perPage);
}

$categories = $cat->getAll();
$totalPages = max(1, ceil($totalCount / $perPage));
$totalAll   = $totalPublished + $totalDrafts;

$username  = $_SESSION['username'] ?? '';
$userRole  = $_SESSION['user_role'] ?? '';
$initials  = mb_strtoupper(mb_substr($username, 0, 2));
$roleLabel = match($userRole) {
    'admin'  => 'Administrátor',
    'editor' => 'Editor',
    'it'     => 'IT správce',
    default  => ucfirst($userRole),
};

$gradients = [
    ['#e9a578','#8b3c1e'],['#4d5b8a','#2a3459'],['#6ba386','#2f5d46'],
    ['#c8b68a','#87733d'],['#a55f7a','#643248'],['#4a6b6a','#1f3a3a'],
    ['#8688b0','#4b4d73'],['#b8906d','#6b4a2e'],
];

function statusChip(string $s): string {
    return match($s) {
        'published' => '<span class="chip chip-ok"><span class="bullet"></span>Publikováno</span>',
        'draft'     => '<span class="chip chip-warn"><span class="bullet"></span>Koncept</span>',
        default     => '<span class="chip chip-outline">' . htmlspecialchars($s) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Příspěvky · Blog Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.filters{display:flex;gap:10px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.search-mini{position:relative;flex:1;max-width:260px}
.search-mini input{width:100%;padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;background:var(--card);color:var(--ink)}
.search-mini input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px rgba(102,126,234,.12)}
.search-mini svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--muted)}
.bulk-bar{display:none;align-items:center;gap:12px;padding:10px 20px;background:var(--ink);color:#f3efe2;font-size:12.5px;position:sticky;top:65px;z-index:10}
.bulk-bar.on{display:flex}
.bulk-bar b{color:#fff}
.bulk-actions{margin-left:auto;display:flex;gap:6px}
.bulk-btn{padding:5px 10px;border-radius:5px;font-size:11.5px;color:#f3efe2;border:1px solid rgba(243,239,226,.15);transition:background .15s;font-family:inherit;cursor:pointer;background:none}
.bulk-btn:hover{background:rgba(243,239,226,.08)}
.bulk-btn.danger{color:#f1dcd7;border-color:rgba(241,220,215,.2)}
table.posts{width:100%;border-collapse:collapse}
table.posts thead{background:var(--card-2);border-bottom:1px solid var(--border)}
table.posts th{padding:10px 16px;text-align:left;font-size:11px;font-weight:500;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;font-family:var(--mono)}
table.posts th:first-child{padding-left:20px;width:34px}
table.posts td{padding:12px 16px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:middle}
table.posts td:first-child{padding-left:20px;width:34px}
table.posts tr:hover td{background:var(--card-2)}
table.posts input[type=checkbox]{accent-color:var(--ink)}
.post-cell{display:flex;gap:12px;align-items:center;min-width:0}
.post-cell-thumb{width:44px;height:44px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--serif);font-size:20px;font-style:italic}
.post-cell-text{min-width:0}
.post-cell-title{font-size:13.5px;font-weight:500;color:var(--ink);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px}
.post-cell-slug{font-family:var(--mono);font-size:11px;color:var(--muted)}
.views-cell{font-family:var(--mono);font-size:12.5px;color:var(--body);text-align:right}
.row-actions{display:flex;gap:2px;opacity:0;transition:opacity .15s}
tr:hover .row-actions{opacity:1}
.row-ico{width:26px;height:26px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted);transition:background .15s,color .15s;border:none;background:none;cursor:pointer}
.row-ico:hover{background:var(--paper-2);color:var(--ink)}
.row-ico.danger:hover{background:var(--danger-soft);color:var(--danger)}
.pagination{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;font-size:12.5px;color:var(--muted);border-top:1px solid var(--line)}
.page-btns{display:flex;gap:4px}
.pg{width:30px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:12px;color:var(--body);border:1px solid transparent;text-decoration:none;background:none;cursor:pointer}
.pg:hover{background:var(--paper-2)}
.pg.on{background:var(--ink);color:#f3efe2;border-color:var(--ink)}
.pg.nav{border:1px solid var(--border);padding:0 10px;width:auto}
.pg.nav:hover{border-color:var(--ink-2)}
.del-modal{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.del-modal.on{display:flex}
.del-modal-box{background:var(--card);padding:32px;border-radius:16px;max-width:420px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.del-modal-box h3{font-size:20px;font-weight:600;color:var(--ink);margin-bottom:8px}
.del-modal-box p{font-size:13.5px;color:var(--body);margin-bottom:24px}
.del-modal-actions{display:flex;gap:10px;justify-content:center}
.search-results-drop{position:absolute;top:100%;left:0;right:0;background:var(--card);border:1px solid var(--border);border-radius:10px;margin-top:6px;max-height:320px;overflow-y:auto;box-shadow:0 8px 30px rgba(0,0,0,.1);display:none;z-index:100}
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
          Kategorie
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
      <div class="crumb"><span>Workspace</span><span class="sep">/</span><span class="here">Příspěvky</span></div>
      <div class="search">
        <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="text" id="quickSearch" placeholder="Hledat v článcích…" autocomplete="off">
        <div id="searchResults" class="search-results-drop"></div>
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
      <div class="page-head">
        <div>
          <div class="eyebrow"><span class="pulse"></span>Knihovna obsahu</div>
          <h1 class="page-title"><em><?= $totalAll ?> příspěvků</em> &mdash; váš archív.</h1>
          <p class="page-sub"><?= $totalPublished ?> publikováno, <?= $totalDrafts ?> konceptů. Filtrujte, řaďte a spravujte obsah.</p>
        </div>
        <div class="seg">
          <button onclick="location.href='posts.php'" class="<?= $status === 'all' ? 'active' : '' ?>">Vše <?= $totalAll ?></button>
          <button onclick="location.href='posts.php?status=published'" class="<?= $status === 'published' ? 'active' : '' ?>">Publikováno <?= $totalPublished ?></button>
          <button onclick="location.href='posts.php?status=draft'" class="<?= $status === 'draft' ? 'active' : '' ?>">Koncepty <?= $totalDrafts ?></button>
        </div>
      </div>

      <div class="panel">
        <div class="filters">
          <div class="search-mini">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="tableFilter" placeholder="Filtr názvu…" oninput="filterTable(this.value)">
          </div>
          <?php if (!empty($categories)): ?>
          <select onchange="location.href='posts.php?status=<?= htmlspecialchars($status) ?>&category='+this.value" style="padding:6px 10px;border:1px solid var(--border);border-radius:7px;background:var(--card);font-size:12.5px;color:var(--body);font-family:inherit;cursor:pointer">
            <option value="">Kategorie: Vše</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $categoryFilter === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
          <div style="flex:1"></div>
          <div style="font-family:var(--mono);font-size:11px;color:var(--muted)">Zobrazeno <b style="color:var(--ink)" id="shownCount"><?= count($posts) ?></b> z <?= $totalCount ?></div>
        </div>

        <div class="bulk-bar" id="bulkBar">
          <b><span id="bulkCount">0</span> vybráno</b>
          <span style="color:rgba(243,239,226,.55)">· hromadné akce</span>
          <div class="bulk-actions">
            <button class="bulk-btn" onclick="bulkPublish()">Publikovat</button>
            <button class="bulk-btn danger" onclick="bulkDelete()">Smazat</button>
          </div>
        </div>

        <table class="posts">
          <thead>
            <tr>
              <th><input type="checkbox" id="checkAll"></th>
              <th style="width:40%">Název článku</th>
              <th>Stav</th>
              <th>Kategorie</th>
              <th>Autor</th>
              <th style="text-align:right">Zobrazení</th>
              <th>Datum</th>
              <th style="width:80px"></th>
            </tr>
          </thead>
          <tbody id="postsBody">
            <?php if (empty($posts)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted)">Žádné příspěvky. <a href="add_post.php" style="color:var(--accent-2)">Vytvořte první →</a></td></tr>
            <?php else: ?>
            <?php foreach ($posts as $p): ?>
            <?php $g = $gradients[$p['id'] % count($gradients)]; $lt = mb_strtoupper(mb_substr($p['title'], 0, 1)); ?>
            <tr data-title="<?= htmlspecialchars(mb_strtolower($p['title'])) ?>">
              <td><input type="checkbox" class="post-cb" value="<?= $p['id'] ?>"></td>
              <td>
                <div class="post-cell">
                  <div class="post-cell-thumb" style="background:linear-gradient(135deg,<?= $g[0] ?>,<?= $g[1] ?>)"><?= htmlspecialchars($lt) ?></div>
                  <div class="post-cell-text">
                    <div class="post-cell-title"><?= htmlspecialchars($p['title']) ?></div>
                    <?php if (!empty($p['slug'])): ?><div class="post-cell-slug">/<?= htmlspecialchars($p['slug']) ?></div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td><?= statusChip($p['status']) ?></td>
              <td><?php if (!empty($p['category_name'])): ?><span class="chip chip-outline"><?= htmlspecialchars($p['category_name']) ?></span><?php else: ?>&mdash;<?php endif; ?></td>
              <td style="color:var(--body)"><?= htmlspecialchars($p['author_name'] ?? '—') ?></td>
              <td class="views-cell">&mdash;</td>
              <td class="mono" style="color:var(--body);font-size:12px"><?= formatDate($p['published_at'] ?? $p['created_at']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="edit_post.php?id=<?= $p['id'] ?>" class="row-ico" title="Upravit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></a>
                  <?php if ($auth->canEdit($p['author_id'] ?? 0)): ?>
                  <button class="row-ico danger" title="Smazat" onclick="confirmDel(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['title'])) ?>')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <div>Stránka <b style="color:var(--ink);font-family:var(--mono)"><?= $page ?></b> z <?= $totalPages ?></div>
          <div class="page-btns">
            <?php if ($page > 1): ?>
            <a href="?status=<?= $status ?>&page=<?= $page-1 ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>" class="pg nav">← Předchozí</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <a href="?status=<?= $status ?>&page=<?= $i ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>" class="pg <?= $i === $page ? 'on' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?status=<?= $status ?>&page=<?= $page+1 ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>" class="pg nav">Další →</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<div class="del-modal" id="delModal">
  <div class="del-modal-box">
    <h3>Smazat příspěvek?</h3>
    <p id="delModalText">Tato akce je nevratná.</p>
    <div class="del-modal-actions">
      <button class="btn btn-danger" onclick="execDel()">Ano, smazat</button>
      <button class="btn btn-ghost" onclick="closeDel()">Zrušit</button>
    </div>
  </div>
</div>

<script>
let delId = null;
const checkAll = document.getElementById('checkAll');
const bulkBar  = document.getElementById('bulkBar');
const bulkCnt  = document.getElementById('bulkCount');

function getChecked() { return [...document.querySelectorAll('.post-cb:checked')]; }
function updateBulk() {
  const n = getChecked().length;
  bulkCnt.textContent = n;
  bulkBar.classList.toggle('on', n > 0);
  const all = document.querySelectorAll('.post-cb').length;
  checkAll.indeterminate = n > 0 && n < all;
  checkAll.checked = n > 0 && n === all;
}
checkAll.addEventListener('change', () => { document.querySelectorAll('.post-cb').forEach(b => b.checked = checkAll.checked); updateBulk(); });
document.querySelectorAll('.post-cb').forEach(b => b.addEventListener('change', updateBulk));

function bulkDelete() {
  const ids = getChecked().map(b => b.value);
  if (!ids.length) return;
  if (confirm('Smazat ' + ids.length + ' vybraných příspěvků? Tato akce je nevratná.')) {
    location.href = 'bulk_action.php?action=delete&ids=' + ids.join(',') + '&return=' + encodeURIComponent(location.search);
  }
}
function bulkPublish() {
  const ids = getChecked().map(b => b.value);
  if (!ids.length) return;
  location.href = 'bulk_action.php?action=publish&ids=' + ids.join(',') + '&return=' + encodeURIComponent(location.search);
}

function confirmDel(id, title) {
  delId = id;
  document.getElementById('delModalText').textContent = 'Opravdu smazat "' + title + '"? Tato akce je nevratná.';
  document.getElementById('delModal').classList.add('on');
}
function closeDel() { document.getElementById('delModal').classList.remove('on'); delId = null; }
function execDel() { if (delId) location.href = 'delete_post.php?id=' + delId + '&return_filter=all&return_page=1'; }
document.getElementById('delModal').addEventListener('click', e => { if (e.target === document.getElementById('delModal')) closeDel(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDel(); });

function filterTable(q) {
  const rows = document.querySelectorAll('#postsBody tr[data-title]');
  const lq = q.toLowerCase();
  let shown = 0;
  rows.forEach(r => { const show = r.dataset.title.includes(lq); r.style.display = show ? '' : 'none'; if (show) shown++; });
  document.getElementById('shownCount').textContent = shown;
}

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
                <div style="font-size:11.5px;color:var(--muted);margin-top:2px">${p.status === 'published' ? 'Publikováno' : 'Koncept'}</div>
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
