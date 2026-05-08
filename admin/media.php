<?php
define('BLOG_PRO', true);
require_once '../config.php';

if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_media') {
    error_reporting(0);
    ob_start();
    header('Content-Type: application/json');
    requireAuth();
    if (!isset($_FILES['file'])) { echo json_encode(['success'=>false,'message'=>'Žádný soubor']); exit; }
    $upload = new Upload();
    $result = $upload->uploadImage($_FILES['file'], true, true);
    if ($result['success']) {
        $media = new Media();
        $stats = $media->getStats();
        echo json_encode(['success'=>true,'message'=>'Soubor nahrán','filename'=>$result['filename'],'stats'=>$stats]);
    } else { echo json_encode($result); }
    exit;
}

if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_media') {
    error_reporting(0);
    ob_start();
    header('Content-Type: application/json');
    requireAuth();
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo json_encode(['success'=>false,'message'=>'Žádné ID']); exit; }
    $media = new Media();
    $result = $media->deleteMultiple($ids);
    if ($result['success']) {
        $stats = $media->getStats();
        echo json_encode(['success'=>true,'message'=>'Smazáno '.$result['deleted'].' souborů','stats'=>$stats]);
    } else { echo json_encode($result); }
    exit;
}

requireAuth();

$media = new Media();
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 24;

function mediaType(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico','bmp'])) return 'image';
    if (in_array($ext, ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv'])) return 'document';
    if (in_array($ext, ['mp4','mov','avi','webm','mkv'])) return 'video';
    if (in_array($ext, ['mp3','wav','ogg','m4a','flac'])) return 'audio';
    return 'other';
}

$allItems = $media->getAll($search, $page, $perPage);
$totalCount = $media->getCount($search);
$totalPages = ceil($totalCount / $perPage);
$stats = $media->getStats();
$mediaItems = $allItems;

$gradients = [
    'linear-gradient(135deg,#c7d0f5,#dfd1ee)',
    'linear-gradient(135deg,#d6c2ec,#e8c8b8)',
    'linear-gradient(135deg,#fde0a3,#f7c9b4)',
    'linear-gradient(135deg,#bee9d3,#c7d0f5)',
    'linear-gradient(135deg,#fbcfe8,#c7d0f5)',
    'linear-gradient(135deg,#cfd9fb,#bcb3e8)',
];

$userInitials = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2));
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Média · <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.ph{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:22px;flex-wrap:wrap}
.ph-actions{display:flex;gap:8px;flex-wrap:wrap}
.storage{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;padding:14px 18px;background:var(--card);border:1px solid var(--border);border-radius:12px;margin-bottom:22px}
.storage-bar{height:8px;border-radius:999px;background:var(--paper-2);overflow:hidden}
.storage-fill{height:100%;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:999px;transition:width .4s}
.storage-meta{font-size:11.5px;color:var(--muted);margin-top:8px}
.storage-num strong{font-family:var(--mono);font-size:18px;color:var(--ink);font-weight:500}
.media-shell{display:grid;grid-template-columns:210px 1fr;gap:22px}
.filters{display:flex;flex-direction:column;gap:14px;position:sticky;top:78px;align-self:start}
.filt{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px}
.filt h4{font-family:var(--mono);font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;font-weight:500}
.filt-list{display:flex;flex-direction:column;gap:1px}
.filt-item{display:flex;align-items:center;gap:10px;padding:7px 9px;border-radius:7px;font-size:13px;color:var(--ink-2);cursor:pointer;transition:background .12s;text-decoration:none}
.filt-item:hover{background:var(--paper-2);color:var(--ink)}
.filt-item.on{background:var(--accent-soft);color:var(--accent-2);font-weight:500}
.filt-item .ico{width:14px;height:14px;flex-shrink:0}
.filt-item .ct{margin-left:auto;font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.filt-item.on .ct{color:var(--accent-2)}
.toolbar{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--card);border:1px solid var(--border);border-radius:12px;margin-bottom:14px;flex-wrap:wrap}
.tb-search{position:relative;flex:1;min-width:200px}
.tb-search input{width:100%;padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:7px;background:var(--paper);font-family:inherit;font-size:12.5px;color:var(--ink)}
.tb-search input:focus{outline:none;border-color:var(--accent);background:var(--card)}
.tb-search-ico{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--muted)}
.tb-divider{width:1px;height:20px;background:var(--border)}
.tb-meta{font-family:var(--mono);font-size:11px;color:var(--muted)}
.selbar{display:none;align-items:center;gap:10px;padding:10px 14px;background:linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.08));border:1px solid var(--accent-soft);border-radius:12px;margin-bottom:14px}
.selbar.show{display:flex}
.selbar-count{font-size:13px;color:var(--accent-2);font-weight:500}
.selbar-actions{margin-left:auto;display:flex;gap:6px}
.upload-strip{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;padding:12px 16px;border:1.5px dashed var(--border);border-radius:12px;background:var(--card-2);margin-bottom:14px;cursor:pointer;transition:border-color .15s,background .15s}
.upload-strip:hover{border-color:var(--accent);background:var(--accent-soft)}
.upload-ico{width:38px;height:38px;border-radius:9px;background:var(--accent-soft);color:var(--accent-2);display:flex;align-items:center;justify-content:center}
.upload-text{font-size:12.5px;color:var(--ink-2);font-weight:500}
.upload-sub{font-size:11px;color:var(--muted);margin-top:2px}
.grid-m{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:12px}
.card-m{background:var(--card);border:1px solid var(--border);border-radius:11px;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s,border-color .15s;position:relative}
.card-m:hover{transform:translateY(-2px);box-shadow:0 8px 18px -8px rgba(31,41,55,.18);border-color:var(--accent)}
.card-m.sel{border-color:var(--accent);box-shadow:0 0 0 3px rgba(102,126,234,.18)}
.thumb{aspect-ratio:1/1;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--paper-2)}
.thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.thumb-vid{background:#1f2937;color:#fff}
.thumb-aud{background:linear-gradient(135deg,#d1fae5,#bee9d3);color:var(--ok)}
.check-box{position:absolute;top:7px;left:7px;width:19px;height:19px;border-radius:5px;background:rgba(255,255,255,.92);border:1.4px solid rgba(255,255,255,.95);display:flex;align-items:center;justify-content:center;font-size:11px;color:transparent;box-shadow:0 1px 3px rgba(0,0,0,.18);opacity:0;transition:opacity .12s,background .12s,color .12s;pointer-events:none}
.card-m:hover .check-box,.card-m.sel .check-box{opacity:1}
.card-m.sel .check-box{background:var(--accent);color:#fff;border-color:var(--accent)}
.badge-type{position:absolute;top:7px;right:7px;font-family:var(--mono);font-size:9.5px;letter-spacing:.04em;text-transform:uppercase;padding:2px 6px;border-radius:4px;background:rgba(0,0,0,.55);color:#fff}
.badge-type.light{background:rgba(255,255,255,.85);color:var(--ink-2)}
.meta-m{padding:9px 11px 11px;border-top:1px solid var(--line)}
.meta-m-name{font-size:12.5px;color:var(--ink);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.meta-m-sub{margin-top:3px;font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.pg{display:flex;align-items:center;gap:6px;margin-top:22px;justify-content:center}
.pg a,.pg span{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;border:1px solid var(--border);font-size:12.5px;color:var(--body);background:var(--card);text-decoration:none}
.pg a:hover{border-color:var(--accent);color:var(--accent-2)}
.pg .cur{background:var(--accent);border-color:var(--accent);color:#fff}
.modal-back{position:fixed;inset:0;background:rgba(31,41,55,.4);backdrop-filter:blur(4px);z-index:80;display:none;align-items:center;justify-content:center}
.modal-back.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px;max-width:400px;width:90%;text-align:center}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:8px}
.modal-box p{font-size:13px;color:var(--body);margin-bottom:20px}
.modal-actions{display:flex;gap:8px}
</style>
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><div class="brand-mark">BP</div><div><div class="brand-name"><?= e(SITE_NAME) ?></div><div class="brand-sub">CMS · Admin</div></div></div>
    <div><div class="nav-label">Workspace</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>dashboard.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Přehled</a>
      <a href="<?= ADMIN_URL ?>posts.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>Příspěvky</a>
      <a href="<?= ADMIN_URL ?>media.php" class="active"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Média<span class="count"><?= $totalCount ?></span></a>
    </nav></div>
    <div><div class="nav-label">Nastavení</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>settings.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Nastavení</a>
    </nav></div>
    <div class="side-user"><div class="avatar"><?= $userInitials ?></div><div class="side-user-info"><div class="side-user-name"><?= e($_SESSION['username'] ?? '') ?></div><div class="side-user-role"><?= e($_SESSION['role'] ?? 'Editor') ?></div></div></div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="crumb"><a href="<?= ADMIN_URL ?>dashboard.php" style="color:var(--muted)">Blog Pro</a><span class="sep">/</span><span class="here">Média</span></div>
      <div class="top-actions"><a href="<?= ADMIN_URL ?>add_post.php" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Nový příspěvek</a></div>
    </div>

    <div class="content">
      <div class="ph">
        <div>
          <div class="eyebrow"><span class="pulse"></span>Knihovna · <?= $totalCount ?> souborů · <?= $stats['total_size_formatted'] ?></div>
          <h1 class="page-title">Vaše <em>média.</em></h1>
          <p class="page-sub">Centrální úložiště obrázků a dokumentů pro celý blog.</p>
        </div>
        <div class="ph-actions"><button class="btn btn-primary btn-sm" onclick="document.getElementById('uploadInput').click()"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>Nahrát soubory</button></div>
      </div>

      <div class="storage">
        <div>
          <div class="storage-bar"><div class="storage-fill" style="width:42%"></div></div>
          <div class="storage-meta"><?= $totalCount ?> souborů celkem</div>
        </div>
        <div class="storage-num"><strong><?= $stats['total_size_formatted'] ?></strong></div>
      </div>

      <div class="media-shell">
        <aside class="filters">
          <div class="filt">
            <h4>Typ souboru</h4>
            <div class="filt-list">
              <a href="?search=<?= urlencode($search) ?>" class="filt-item <?= (!$typeFilter||$typeFilter==='all')?'on':'' ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/></svg>Vše<span class="ct"><?= $totalCount ?></span></a>
              <a href="?type=image&search=<?= urlencode($search) ?>" class="filt-item <?= $typeFilter==='image'?'on':'' ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>Obrázky</a>
              <a href="?type=document&search=<?= urlencode($search) ?>" class="filt-item <?= $typeFilter==='document'?'on':'' ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Dokumenty</a>
              <a href="?type=video&search=<?= urlencode($search) ?>" class="filt-item <?= $typeFilter==='video'?'on':'' ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>Videa</a>
              <a href="?type=audio&search=<?= urlencode($search) ?>" class="filt-item <?= $typeFilter==='audio'?'on':'' ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>Zvuk</a>
            </div>
          </div>
        </aside>

        <div>
          <form action="" method="GET" id="searchForm">
            <?php if ($typeFilter && $typeFilter!=='all'): ?><input type="hidden" name="type" value="<?= e($typeFilter) ?>"><?php endif; ?>
            <div class="toolbar">
              <div class="tb-search"><svg class="tb-search-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="text" name="search" placeholder="Hledat v médiích…" value="<?= e($search) ?>" onchange="this.form.submit()"></div>
              <div class="tb-divider"></div>
              <span class="tb-meta"><?= $totalCount ?> souborů</span>
            </div>
          </form>

          <div class="selbar" id="selbar"><span class="selbar-count"><strong id="selCount">0</strong> vybráno</span><div class="selbar-actions"><button class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="openDeleteModal()"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>Smazat</button><button class="btn btn-ghost btn-sm" id="selClear">Zrušit výběr</button></div></div>

          <div class="upload-strip" id="uploadStrip">
            <div class="upload-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
            <div><div class="upload-text">Přetáhněte soubory sem nebo klikněte pro výběr</div><div class="upload-sub">JPG, PNG, WebP, PDF · max. 50 MB / soubor</div></div>
            <button class="btn btn-ghost btn-sm" type="button" onclick="document.getElementById('uploadInput').click()">Vybrat</button>
          </div>
          <input type="file" id="uploadInput" multiple accept="image/*,.pdf,.doc,.docx" style="display:none">

          <?php if (empty($mediaItems)): ?>
            <div style="text-align:center;padding:60px 20px;color:var(--muted)"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3;display:block;margin:0 auto 16px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg><p>Žádná média<?= $search ? ' pro &ldquo;'.e($search).'&rdquo;' : '' ?>.</p></div>
          <?php else: ?>
            <div class="grid-m" id="mediaGrid">
              <?php foreach ($mediaItems as $i => $item):
                $mtype = mediaType($item['original_name']);
                $ext = strtolower(pathinfo($item['original_name'], PATHINFO_EXTENSION));
                $grad = $gradients[$i % 6];
                $sizeKb = $item['size'] > 0 ? number_format($item['size']/1024, 0, ',', ' ').' KB' : '—';
                $dims = (!empty($item['width']) && !empty($item['height'])) ? $item['width'].'×'.$item['height'] : '';
                $isLight = in_array($mtype, ['image','document']);
              ?>
              <div class="card-m" data-id="<?= $item['id'] ?>" data-type="<?= $mtype ?>">
                <div class="thumb <?= $mtype==='video'?'thumb-vid':($mtype==='audio'?'thumb-aud':'') ?>" style="<?= $mtype==='image'?'background:'.$grad.';':'' ?>">
                  <?php if ($mtype==='image'): ?><img src="<?= BASE_URL.$item['path'] ?>" alt="<?= e($item['original_name']) ?>" loading="lazy" onerror="this.style.display='none'">
                  <?php elseif ($mtype==='video'): ?><svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                  <?php elseif ($mtype==='audio'): ?><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                  <?php else: ?><svg width="32" height="42" viewBox="0 0 24 30" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--secondary)"><path d="M14 2H6a2 2 0 0 0-2 2v22a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><?php endif; ?>
                  <span class="check-box">✓</span>
                  <span class="badge-type <?= $isLight?'light':'' ?>"><?= $ext ?></span>
                  <?php if ($dims): ?><span style="position:absolute;bottom:7px;left:7px;font-family:var(--mono);font-size:9.5px;color:rgba(255,255,255,.92);padding:2px 6px;border-radius:4px;background:rgba(0,0,0,.4)"><?= $dims ?></span><?php endif; ?>
                </div>
                <div class="meta-m"><div class="meta-m-name" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></div><div class="meta-m-sub"><?= $sizeKb ?></div></div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($totalPages > 1): ?>
          <div class="pg">
            <?php if ($page>1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?><?= $typeFilter&&$typeFilter!=='all'?'&type='.e($typeFilter):'' ?>">&#8592;</a><?php endif; ?>
            <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
              <?php if ($p===$page): ?><span class="cur"><?= $p ?></span><?php else: ?><a href="?page=<?= $p ?>&search=<?= urlencode($search) ?><?= $typeFilter&&$typeFilter!=='all'?'&type='.e($typeFilter):'' ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?><?= $typeFilter&&$typeFilter!=='all'?'&type='.e($typeFilter):'' ?>">&#8594;</a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="modal-back" id="deleteModal">
  <div class="modal-box">
    <h3>Smazat vybrané soubory?</h3>
    <p>Opravdu chcete smazat <strong id="deleteCount">0</strong> soubor(ů)? Tato akce je nevratná.</p>
    <div class="modal-actions"><button class="btn btn-danger" style="flex:1" onclick="confirmDelete()">Smazat</button><button class="btn btn-ghost" style="flex:1" onclick="closeModal()">Zrušit</button></div>
  </div>
</div>

<script>
const grid=document.getElementById('mediaGrid');
const selbar=document.getElementById('selbar');
const selCountEl=document.getElementById('selCount');
function getSelected(){return grid?[...grid.querySelectorAll('.card-m.sel')]:[];}
function refreshSel(){const n=getSelected().length;if(selCountEl)selCountEl.textContent=n;if(selbar)selbar.classList.toggle('show',n>0);}
if(grid){grid.addEventListener('click',e=>{const card=e.target.closest('.card-m');if(card){card.classList.toggle('sel');refreshSel();}});}
document.getElementById('selClear')?.addEventListener('click',()=>{getSelected().forEach(c=>c.classList.remove('sel'));refreshSel();});
const typeFilter='<?= e($typeFilter) ?>';
if(typeFilter&&typeFilter!=='all'&&grid){grid.querySelectorAll('.card-m').forEach(c=>{if(c.dataset.type!==typeFilter)c.style.display='none';});}
function openDeleteModal(){document.getElementById('deleteCount').textContent=getSelected().length;document.getElementById('deleteModal').classList.add('show');}
function closeModal(){document.getElementById('deleteModal').classList.remove('show');}
async function confirmDelete(){const ids=getSelected().map(c=>c.dataset.id);const fd=new FormData();fd.append('ajax_action','delete_media');ids.forEach(id=>fd.append('ids[]',id));try{const r=await fetch(location.href,{method:'POST',body:fd});const data=await r.json();if(data.success)location.reload();else{alert('Chyba: '+data.message);closeModal();}}catch{closeModal();}}
document.getElementById('deleteModal')?.addEventListener('click',e=>{if(e.target===document.getElementById('deleteModal'))closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
const uploadStrip=document.getElementById('uploadStrip');
const uploadInput=document.getElementById('uploadInput');
uploadStrip?.addEventListener('dragover',e=>{e.preventDefault();uploadStrip.style.borderColor='var(--accent)';});
uploadStrip?.addEventListener('dragleave',()=>{uploadStrip.style.borderColor='';});
uploadStrip?.addEventListener('drop',e=>{e.preventDefault();uploadStrip.style.borderColor='';handleFiles(e.dataTransfer.files);});
uploadInput?.addEventListener('change',e=>handleFiles(e.target.files));
async function handleFiles(files){const arr=[...files];if(!arr.length)return;for(const file of arr){const fd=new FormData();fd.append('ajax_action','upload_media');fd.append('file',file);try{await fetch(location.href,{method:'POST',body:fd});}catch(e){}}location.reload();}
</script>
</body>
</html>