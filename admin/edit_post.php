<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$post = new Post();
$category = new Category();
$upload = new Upload();
$media = new Media();
$auth = new Auth();

$id = get('id');
if (!$id) redirect(ADMIN_URL . 'dashboard.php');

$postData = $post->getById($id);
if (!$postData) {
    setFlash('error', 'Příspěvek nenalezen');
    redirect(ADMIN_URL . 'dashboard.php');
}

if (!$auth->canEdit($postData['author_id'])) {
    setFlash('error', 'Nemáte oprávnění');
    redirect(ADMIN_URL . 'dashboard.php');
}

$categories = $category->getAll();
$error = '';

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    if ($_POST['ajax_action'] === 'add_category') {
        $name = trim($_POST['category_name'] ?? '');
        if (empty($name)) { echo json_encode(['success'=>false,'message'=>'Název nesmí být prázdný']); exit; }
        $db = new Database();
        $slug = mb_strtolower($name,'UTF-8');
        $slug = strtr($slug,['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z']);
        $slug = preg_replace('/[^a-z0-9\s-]/','', $slug);
        $slug = preg_replace('/[\s-]+/','-',$slug);
        $slug = trim($slug,'-');
        $db->query('INSERT INTO categories (name,slug) VALUES (:name,:slug)');
        $db->bind(':name',$name); $db->bind(':slug',$slug);
        if ($db->execute()) { echo json_encode(['success'=>true,'id'=>$db->lastInsertId(),'name'=>$name]); }
        else { echo json_encode(['success'=>false,'message'=>'Chyba']); }
        exit;
    }
    if ($_POST['ajax_action'] === 'delete_category') {
        $catId = intval($_POST['category_id'] ?? 0);
        $db = new Database();
        $db->query('SELECT COUNT(*) as count FROM posts WHERE category_id = :id');
        $db->bind(':id',$catId);
        $result = $db->fetch();
        if ($result && $result['count'] > 0) { echo json_encode(['success'=>false,'message'=>'Obsahuje '.$result['count'].' příspěvků']); exit; }
        $db->query('DELETE FROM categories WHERE id = :id');
        $db->bind(':id',$catId);
        echo json_encode(['success'=>$db->execute()]);
        exit;
    }
}

if (isPost() && !isset($_POST['ajax_action'])) {
    if (!verifyCsrf()) {
        $error = 'Neplatný CSRF token';
    } else {
        $data = [
            'title' => post('title'),
            'content' => Security::cleanHTML(post('content')),
            'excerpt' => post('excerpt'),
            'category_id' => post('category_id') ?: null,
            'status' => post('status','draft'),
            'meta_title' => post('meta_title'),
            'meta_description' => post('meta_description'),
            'meta_keywords' => post('meta_keywords')
        ];
        if (post('featured_image_id')) {
            $mediaItem = $media->getById(post('featured_image_id'));
            if ($mediaItem) $data['featured_image'] = $mediaItem['path'];
        } else {
            $data['featured_image'] = null;
        }
        $result = $post->update($id, $data);
        if ($result['success']) { setFlash('success','Příspěvek byl aktualizován!'); redirect(ADMIN_URL.'dashboard.php'); }
        else $error = $result['message'] ?? 'Chyba při ukládání';
    }
}

$userInitials = strtoupper(substr($_SESSION['username'] ?? 'U',0,2));
$catColors = ['#667eea','#764ba2','#5b21b6','#10b981','#f59e0b','#ef4444','#3b82f6','#6366f1'];
$currentCatId = $postData['category_id'] ?? '';
$currentStatus = $postData['status'] ?? 'draft';
$currentContent = $postData['content'] ?? '';
$currentExcerpt = $postData['excerpt'] ?? '';
$currentMetaTitle = $postData['meta_title'] ?? '';
$currentMetaDesc = $postData['meta_description'] ?? '';
$currentMetaKw = $postData['meta_keywords'] ?? '';
$currentFeatured = $postData['featured_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upravit: <?= e($postData['title']) ?> · <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.ed-grid{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:flex-start}
.ed-side{position:sticky;top:80px;display:flex;flex-direction:column;gap:16px}
.ph-meta{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:12px}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:500}
.status-badge.published{background:var(--ok-soft);color:var(--ok)}
.status-badge.draft{background:var(--warn-soft);color:var(--warn)}
.status-badge.scheduled{background:rgba(139,92,246,.1);color:#7c3aed}
.status-badge .dot{width:6px;height:6px;border-radius:50%;background:currentColor}
.save-pill{display:inline-flex;align-items:center;gap:8px;padding:4px 10px 4px 8px;border-radius:999px;background:var(--warn-soft);color:var(--warn);font-size:11.5px;font-weight:500}
.save-pill .dot{width:7px;height:7px;border-radius:50%;background:currentColor;animation:blink 1.4s infinite}
@keyframes blink{0%,100%{opacity:.4}50%{opacity:1}}
.save-pill.saved{background:var(--ok-soft);color:var(--ok)}
.save-pill.saved .dot{animation:none;opacity:.85}
.ph-actions{display:flex;gap:8px;align-items:center}
.title-field{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px 28px 22px;box-shadow:0 1px 2px rgba(31,41,55,.03)}
.title-label{font-family:var(--mono);font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:8px}
.title-label .req{color:var(--accent)}
.title-input{width:100%;border:none;background:transparent;outline:none;font-family:var(--serif);font-weight:400;font-size:38px;line-height:1.15;color:var(--ink);letter-spacing:-0.02em}
.title-input::placeholder{color:var(--faint);font-style:italic}
.title-foot{display:flex;align-items:center;gap:14px;margin-top:14px;padding-top:14px;border-top:1px solid var(--line)}
.slug-field{flex:1;display:flex;align-items:center;gap:6px;font-family:var(--mono);font-size:12px;color:var(--muted)}
.slug-field .pfx{color:var(--faint)}
.slug-field .slug-val{flex:1;border:none;background:transparent;outline:none;font-family:var(--mono);font-size:12px;color:var(--ink-2)}
.slug-regen{padding:3px 8px;border-radius:5px;font-size:10.5px;color:var(--muted);border:1px solid var(--border);background:var(--paper)}
.slug-regen:hover{color:var(--accent-2);border-color:var(--accent)}
.editor{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(31,41,55,.03)}
.ed-toolbar{display:flex;align-items:center;gap:4px;padding:8px 12px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,var(--card-2),var(--card));flex-wrap:wrap;position:sticky;top:65px;z-index:5}
.ed-btn{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:var(--body);border:1px solid transparent;font-size:13px;transition:background .15s,color .15s,border-color .15s}
.ed-btn:hover{background:var(--paper-2);color:var(--ink);border-color:var(--border)}
.ed-btn.active{background:var(--accent-soft);color:var(--accent-2)}
.ed-divider{width:1px;height:18px;background:var(--border);margin:0 4px}
.ed-content{min-height:480px;padding:28px 32px;font-size:16px;line-height:1.7;color:var(--ink);outline:none}
.ed-content:empty::before{content:attr(data-placeholder);color:var(--faint);font-style:italic}
.ed-content p{margin-bottom:1em}
.ed-content h2{font-family:var(--serif);font-size:28px;font-weight:400;line-height:1.2;margin:1.4em 0 .5em;letter-spacing:-0.01em}
.ed-content h3{font-family:var(--serif);font-size:22px;font-weight:400;margin:1.2em 0 .4em}
.ed-content blockquote{border-left:3px solid var(--accent);padding:4px 0 4px 18px;margin:18px 0;font-family:var(--serif);font-size:19px;font-style:italic;color:var(--ink-2)}
.ed-content code{font-family:var(--mono);font-size:.9em;background:var(--paper-2);padding:1px 5px;border-radius:4px;color:var(--accent-2)}
.ed-content ul,.ed-content ol{padding-left:22px;margin-bottom:1em}
.ed-stats{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--line);background:var(--card-2)}
.ed-stat{padding:12px 18px;display:flex;align-items:center;gap:10px;border-right:1px solid var(--line)}
.ed-stat:last-child{border-right:none}
.ed-stat-ico{width:28px;height:28px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--card);border:1px solid var(--border);color:var(--muted)}
.ed-stat-label{font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.ed-stat-val{font-family:var(--serif);font-size:22px;line-height:1;color:var(--ink);margin-top:2px;letter-spacing:-0.01em}
.ed-stat-val .unit{font-family:var(--mono);font-size:11px;color:var(--muted);margin-left:3px}
.sp{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(31,41,55,.03)}
.sp-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--line)}
.sp-title{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:var(--ink)}
.sp-title .ico{width:14px;height:14px;color:var(--accent-2)}
.sp-meta{font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.sp-body{padding:14px 16px}
.status-switch{display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;padding:4px;background:var(--paper-2);border:1px solid var(--border);border-radius:8px}
.status-switch button{padding:7px 4px;font-size:11.5px;border-radius:5px;color:var(--muted);font-weight:500;transition:background .15s,color .15s;display:flex;align-items:center;justify-content:center;gap:5px}
.status-switch button.on{background:var(--card);color:var(--accent-2);box-shadow:0 1px 2px rgba(102,126,234,.12)}
.status-switch button.on.draft{color:var(--warn)}
.status-switch button.on.scheduled{color:var(--violet)}
.status-switch button .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.75}
.sp-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:12.5px;border-bottom:1px solid var(--line)}
.sp-row:last-child{border-bottom:none}
.sp-row-label{color:var(--muted);display:flex;align-items:center;gap:8px}
.sp-row-val{color:var(--ink);font-family:var(--mono);font-size:12px}
.cat-list{display:flex;flex-direction:column;gap:2px;max-height:220px;overflow-y:auto}
.cat-item{display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:7px;cursor:pointer;transition:background .15s;user-select:none}
.cat-item:hover{background:var(--paper-2)}
.cat-item.on{background:var(--accent-soft)}
.cat-item.on .cat-name{color:var(--accent-2);font-weight:500}
.cat-check{width:16px;height:16px;border-radius:4px;border:1.5px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.cat-item.on .cat-check{background:var(--accent);border-color:var(--accent)}
.cat-item.on .cat-check svg{display:block}
.cat-check svg{display:none;color:#fff}
.cat-swatch{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.cat-name{flex:1;font-size:13px;color:var(--ink-2)}
.cat-count{font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.feat-preview{border-radius:10px;overflow:hidden;margin-bottom:10px;position:relative;background:var(--paper-2)}
.feat-preview img{width:100%;display:block;max-height:160px;object-fit:cover}
.feat-remove{position:absolute;top:8px;right:8px;width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s}
.feat-remove:hover{background:rgba(153,27,27,.8)}
.dropzone{border:1.5px dashed var(--border);border-radius:12px;padding:24px 16px;text-align:center;background:var(--card-2);transition:border-color .15s,background .15s;cursor:pointer}
.dropzone:hover{border-color:var(--accent);background:var(--accent-soft)}
.dz-ico{width:44px;height:44px;border-radius:10px;margin:0 auto 10px;background:var(--accent-soft);color:var(--accent-2);display:flex;align-items:center;justify-content:center}
.dz-text{font-size:12.5px;color:var(--ink-2);margin-bottom:4px;font-weight:500}
.dz-sub{font-size:11px;color:var(--muted);margin-bottom:12px}
.dz-btn{padding:6px 11px;font-size:11.5px;border-radius:6px;display:inline-flex;align-items:center;gap:5px;background:var(--card);border:1px solid var(--border);color:var(--body);transition:all .15s}
.dz-btn:hover{border-color:var(--accent);color:var(--accent-2)}
.dz-btn.primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-color:transparent}
.seo-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:linear-gradient(135deg,rgba(102,126,234,.06),rgba(118,75,162,.06));border-bottom:1px solid var(--line)}
.field{margin-bottom:12px}
.field:last-child{margin-bottom:0}
.field-label{display:flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.field-input,.field-textarea{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;color:var(--ink);background:var(--card);transition:border-color .15s,box-shadow .15s}
.field-input:focus,.field-textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(102,126,234,.15)}
.field-textarea{resize:vertical;min-height:64px;line-height:1.5}
.field-foot{display:flex;justify-content:space-between;font-family:var(--mono);font-size:10px;color:var(--muted);margin-top:4px}
.field-foot .ok{color:var(--ok)}
.field-foot .warn{color:var(--warn)}
.serp{background:var(--paper);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:12px}
.serp-url{font-family:var(--mono);font-size:11px;color:var(--ok);margin-bottom:4px}
.serp-title{font-size:14px;color:#1a0dab;line-height:1.3;margin-bottom:3px}
.serp-desc{font-size:11.5px;color:var(--body);line-height:1.4}
.savebar{position:sticky;bottom:0;z-index:30;margin:28px -32px -64px;padding:14px 32px;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-top:1px solid var(--border);display:flex;align-items:center;gap:12px}
.savebar-info{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--muted)}
.savebar-actions{margin-left:auto;display:flex;gap:8px}
.btn-cancel{background:transparent;color:var(--muted);border:1px solid transparent}
.btn-cancel:hover{color:var(--danger);border-color:var(--danger-soft);background:var(--danger-soft)}
@media(max-width:1100px){.ed-grid{grid-template-columns:1fr}.ed-side{position:static}.savebar{margin:28px -16px -64px;padding:14px 16px}}
</style>
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand">
      <div class="brand-mark">BP</div>
      <div>
        <div class="brand-name"><?= e(SITE_NAME) ?></div>
        <div class="brand-sub">CMS · Admin</div>
      </div>
    </div>
    <div>
      <div class="nav-label">Workspace</div>
      <nav class="nav">
        <a href="<?= ADMIN_URL ?>dashboard.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Přehled</a>
        <a href="<?= ADMIN_URL ?>posts.php" class="active"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>Příspěvky</a>
        <a href="<?= ADMIN_URL ?>media.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Média</a>
      </nav>
    </div>
    <div>
      <div class="nav-label">Nastavení</div>
      <nav class="nav">
        <a href="<?= ADMIN_URL ?>settings.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Nastavení</a>
      </nav>
    </div>
    <div class="side-user">
      <div class="avatar"><?= $userInitials ?></div>
      <div class="side-user-info">
        <div class="side-user-name"><?= e($_SESSION['username'] ?? '') ?></div>
        <div class="side-user-role"><?= e($_SESSION['role'] ?? 'Editor') ?></div>
      </div>
    </div>
  </aside>

  <main class="main">
    <?php if ($error): ?>
    <div style="background:var(--danger-soft);color:var(--danger);padding:12px 32px;font-size:13px;border-bottom:1px solid rgba(153,27,27,.15)"><?= e($error) ?></div>
    <?php endif; ?>

    <form id="postForm" method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
      <input type="hidden" name="status" id="statusInput" value="<?= e($currentStatus) ?>">
      <input type="hidden" name="category_id" id="catInput" value="<?= e($currentCatId) ?>">
      <input type="hidden" name="featured_image_id" id="featuredImageId" value="">
      <textarea name="content" id="contentInput" style="display:none"><?= e($currentContent) ?></textarea>
      <input type="hidden" name="meta_title" id="metaTitleInput" value="<?= e($currentMetaTitle) ?>">
      <input type="hidden" name="meta_description" id="metaDescInput" value="<?= e($currentMetaDesc) ?>">
      <input type="hidden" name="meta_keywords" id="metaKwInput" value="<?= e($currentMetaKw) ?>">
      <input type="hidden" name="excerpt" id="excerptInput" value="<?= e($currentExcerpt) ?>">

      <div class="topbar">
        <div class="crumb">
          <a href="<?= ADMIN_URL ?>dashboard.php" style="color:var(--muted)">Blog Pro</a>
          <span class="sep">/</span>
          <a href="<?= ADMIN_URL ?>posts.php" style="color:var(--muted)">Příspěvky</a>
          <span class="sep">/</span>
          <span class="here">Upravit příspěvek</span>
        </div>
        <div class="top-actions">
          <a href="<?= ADMIN_URL ?>posts.php" class="btn btn-ghost btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Zpět
          </a>
        </div>
      </div>

      <div class="content">
        <div class="page-head">
          <div>
            <div class="ph-meta">
              <div class="status-badge <?= $currentStatus ?>">
                <span class="dot"></span>
                <?php
                  $statusLabels = ['published'=>'Publikováno','draft'=>'Koncept','scheduled'=>'Naplánováno'];
                  echo $statusLabels[$currentStatus] ?? ucfirst($currentStatus);
                ?>
              </div>
              <span class="save-pill saved" id="savePill"><span class="dot"></span><span id="saveText">Uloženo</span></span>
            </div>
            <h1 class="page-title">Upravit <em>příspěvek.</em></h1>
          </div>
          <div class="ph-actions">
            <button type="button" class="btn btn-primary btn-sm" id="topSave">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
              Uložit změny
            </button>
          </div>
        </div>

        <div class="ed-grid">
          <div style="display:flex;flex-direction:column;gap:18px">
            <div class="title-field">
              <div class="title-label"><span>Titulek</span><span class="req">●</span><span class="form-label-sub">povinné · max. 90 znaků</span></div>
              <input type="text" name="title" class="title-input" id="titleInput" value="<?= e($postData['title']) ?>" autocomplete="off">
              <div class="title-foot">
                <div class="slug-field">
                  <span class="pfx"><?= parse_url(BASE_URL, PHP_URL_HOST) ?>/</span>
                  <input type="text" class="slug-val" id="slugDisplay" value="<?= e($postData['slug'] ?? '') ?>" readonly>
                </div>
                <button type="button" class="slug-regen" id="slugRegen">Auto</button>
              </div>
            </div>

            <div class="editor">
              <div class="ed-toolbar">
                <button type="button" class="ed-btn active" title="Zarovnat vlevo"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg></button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Tučně" onclick="document.execCommand('bold')"><b>B</b></button>
                <button type="button" class="ed-btn" title="Kurzíva" onclick="document.execCommand('italic')"><i style="font-family:var(--serif)">I</i></button>
                <button type="button" class="ed-btn" title="Odkaz"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Odrážky" onclick="document.execCommand('insertUnorderedList')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg></button>
                <button type="button" class="ed-btn" title="Číslovaný seznam" onclick="document.execCommand('insertOrderedList')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg></button>
              </div>
              <div class="ed-content" id="edContent" contenteditable="true" data-placeholder="Začněte psát obsah příspěvku…"><?= $currentContent ?></div>
              <div class="ed-stats">
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/></svg></div><div><div class="ed-stat-label">Slov</div><div class="ed-stat-val" id="statWords">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg></div><div><div class="ed-stat-label">Znaků</div><div class="ed-stat-val" id="statChars">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div><div><div class="ed-stat-label">Doba čtení</div><div class="ed-stat-val" id="statRead">0<span class="unit">min</span></div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div><div><div class="ed-stat-label">Čitelnost</div><div class="ed-stat-val" id="statReadability">—</div></div></div>
              </div>
            </div>

            <div class="sp">
              <div class="sp-head">
                <div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>Perex</div>
                <span class="sp-meta" id="excerptCount"><?= strlen($currentExcerpt) ?> / 280</span>
              </div>
              <div class="sp-body">
                <textarea class="field-textarea" id="excerptText" placeholder="Krátké uvedení článku…"><?= e($currentExcerpt) ?></textarea>
              </div>
            </div>
          </div>

          <div class="ed-side">
            <div class="sp">
              <div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Stav</div></div>
              <div class="sp-body">
                <div class="status-switch" id="statusSwitch">
                  <button type="button" class="draft <?= $currentStatus==='draft'?'on':'' ?>" data-val="draft"><span class="dot"></span>Koncept</button>
                  <button type="button" class="<?= $currentStatus==='published'?'on':'' ?>" data-val="published"><span class="dot"></span>Publikováno</button>
                  <button type="button" class="scheduled <?= $currentStatus==='scheduled'?'on':'' ?>" data-val="scheduled"><span class="dot"></span>Plán</button>
                </div>
                <div style="margin-top:14px">
                  <div class="sp-row"><div class="sp-row-label">Autor</div><span class="sp-row-val"><?= e($postData['author_name'] ?? $_SESSION['username'] ?? '') ?></span></div>
                  <div class="sp-row"><div class="sp-row-label">Vytvořeno</div><span class="sp-row-val"><?= date('d.m.Y', strtotime($postData['created_at'] ?? 'now')) ?></span></div>
                  <div class="sp-row"><div class="sp-row-label">Upraveno</div><span class="sp-row-val"><?= date('d.m.Y H:i', strtotime($postData['updated_at'] ?? 'now')) ?></span></div>
                </div>
              </div>
            </div>

            <div class="sp">
              <div class="sp-head">
                <div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kategorie</div>
                <span class="sp-meta"><span id="catCount"><?= $currentCatId ? 1 : 0 ?></span> / <?= count($categories) ?></span>
              </div>
              <div class="sp-body">
                <div class="cat-list" id="catList">
                  <?php foreach ($categories as $i => $cat): ?>
                  <div class="cat-item <?= ($cat['id'] == $currentCatId) ? 'on' : '' ?>" data-id="<?= $cat['id'] ?>" onclick="selectCat(this)">
                    <div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="cat-swatch" style="background:<?= $catColors[$i % count($catColors)] ?>"></div>
                    <span class="cat-name"><?= e($cat['name']) ?></span>
                    <span class="cat-count"><?= $cat['post_count'] ?? 0 ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="sp">
              <div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>Hlavní obrázek</div></div>
              <div class="sp-body">
                <?php if ($currentFeatured): ?>
                <div class="feat-preview" id="featPreview">
                  <img src="<?= e(BASE_URL . $currentFeatured) ?>" alt="Hlavní obrázek">
                  <button type="button" class="feat-remove" onclick="removeFeatured()" title="Odebrat obrázek">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
                <?php else: ?>
                <div class="dropzone" id="dropzone" onclick="document.getElementById('featuredInput').click()">
                  <div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
                  <div class="dz-text">Přetáhněte obrázek příspěvku</div>
                  <div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div>
                  <button type="button" class="dz-btn primary">Vybrat obrázek</button>
                </div>
                <?php endif; ?>
                <input type="file" id="featuredInput" accept="image/*" style="display:none">
              </div>
            </div>

            <div class="sp">
              <div class="seo-head">
                <div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>SEO &amp; sdílení</div>
              </div>
              <div class="sp-body">
                <div class="field">
                  <div class="field-label">SEO Title</div>
                  <input type="text" class="field-input" id="seoTitle" value="<?= e($currentMetaTitle) ?>" placeholder="Ponechte prázdné pro titulek…" maxlength="80">
                  <div class="field-foot"><span>Optimum 50–60 znaků</span><span id="seoTitleCount" class="ok"><?= strlen($currentMetaTitle) ?> / 60</span></div>
                </div>
                <div class="field">
                  <div class="field-label">Meta Description</div>
                  <textarea class="field-textarea" id="seoDesc" placeholder="Krátký popis pro vyhledávače…" maxlength="200"><?= e($currentMetaDesc) ?></textarea>
                  <div class="field-foot"><span>Optimum 150–160 znaků</span><span id="seoDescCount" class="ok"><?= strlen($currentMetaDesc) ?> / 160</span></div>
                </div>
                <div class="field">
                  <div class="field-label">Klíčová slova</div>
                  <input type="text" class="field-input" id="seoKeywords" value="<?= e($currentMetaKw) ?>" placeholder="klíčové slovo 1, klíčové slovo 2…">
                </div>
                <div class="field-label" style="margin-top:6px">Náhled v Google</div>
                <div class="serp">
                  <div class="serp-url"><?= parse_url(BASE_URL, PHP_URL_HOST) ?> › <span id="serpSlug"><?= e($postData['slug'] ?? 'clanek') ?></span></div>
                  <div class="serp-title" id="serpTitle"><?= e($currentMetaTitle ?: $postData['title']) ?> — <?= e(SITE_NAME) ?></div>
                  <div class="serp-desc" id="serpDesc"><?= e($currentMetaDesc ?: 'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.') ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="savebar">
          <div class="savebar-info">
            <span class="save-pill saved" id="savePill2"><span class="dot"></span><span id="saveText2">Uloženo</span></span>
            <span style="color:var(--faint)">·</span>
            <span class="mono" style="font-size:11.5px">Ctrl+S pro uložení</span>
          </div>
          <div class="savebar-actions">
            <a href="<?= ADMIN_URL ?>posts.php" class="btn btn-cancel btn-sm">Zrušit</a>
            <button type="button" class="btn btn-ghost btn-sm" id="saveDraftBtn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
              Uložit jako koncept
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="saveBtn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
              Uložit změny
            </button>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
const slugify = s => s.toLowerCase()
  .normalize('NFD').replace(/[̀-ͯ]/g,'')
  .replace(/[^a-z0-9 -]/g,'').trim().replace(/\s+/g,'-').slice(0,80) || 'clanek';

const titleEl = document.getElementById('titleInput');
const slugDisp = document.getElementById('slugDisplay');
const serpSlug = document.getElementById('serpSlug');
const serpTitle = document.getElementById('serpTitle');
const edContent = document.getElementById('edContent');

titleEl?.addEventListener('input', () => {
  serpTitle.textContent = (titleEl.value||'Upravit příspěvek')+' — <?= e(SITE_NAME) ?>';
  markChanged();
});
document.getElementById('slugRegen')?.addEventListener('click', () => {
  const s = slugify(titleEl.value);
  slugDisp.value = s;
  serpSlug.textContent = s;
});

function updateStats() {
  const txt = (edContent.innerText||'').trim();
  const words = txt ? txt.split(/\s+/).filter(Boolean).length : 0;
  document.getElementById('statWords').textContent = words.toLocaleString('cs-CZ');
  document.getElementById('statChars').textContent = txt.length.toLocaleString('cs-CZ');
  document.getElementById('statRead').innerHTML = Math.max(0,Math.round(words/220))+'<span class="unit">min</span>';
  document.getElementById('statReadability').textContent = words>800?'A':words>300?'B':words>50?'C':'—';
}
edContent?.addEventListener('input', () => { updateStats(); markChanged(); });
updateStats();

const excerptEl = document.getElementById('excerptText');
const excerptCountEl = document.getElementById('excerptCount');
excerptEl?.addEventListener('input', () => { excerptCountEl.textContent = excerptEl.value.length+' / 280'; });

const seoTitleEl = document.getElementById('seoTitle');
const seoDescEl = document.getElementById('seoDesc');
const seoTitleCount = document.getElementById('seoTitleCount');
const seoDescCount = document.getElementById('seoDescCount');
const serpDescEl = document.getElementById('serpDesc');
seoTitleEl?.addEventListener('input', () => { const n=seoTitleEl.value.length; seoTitleCount.textContent=n+' / 60'; seoTitleCount.className=n>60?'warn':'ok'; });
seoDescEl?.addEventListener('input', () => { const n=seoDescEl.value.length; seoDescCount.textContent=n+' / 160'; seoDescCount.className=n>160?'warn':'ok'; serpDescEl.textContent=seoDescEl.value||'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.'; });

document.querySelectorAll('#statusSwitch button').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#statusSwitch button').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    document.getElementById('statusInput').value = btn.dataset.val;
    markChanged();
  });
});

let selectedCatId = '<?= $currentCatId ?>';
function selectCat(el) {
  document.querySelectorAll('.cat-item').forEach(i => i.classList.remove('on'));
  el.classList.add('on');
  selectedCatId = el.dataset.id;
  document.getElementById('catInput').value = selectedCatId;
  document.getElementById('catCount').textContent = '1';
  markChanged();
}

const pill1 = document.getElementById('savePill');
const pill2 = document.getElementById('savePill2');
const txt1 = document.getElementById('saveText');
const txt2 = document.getElementById('saveText2');
function markChanged() {
  pill1.classList.remove('saved'); pill2.classList.remove('saved');
  txt1.textContent = 'Neuložené změny'; txt2.textContent = 'Neuložené změny';
}

function syncHiddenInputs() {
  document.getElementById('contentInput').value = edContent.innerHTML;
  document.getElementById('metaTitleInput').value = seoTitleEl?.value||'';
  document.getElementById('metaDescInput').value = seoDescEl?.value||'';
  document.getElementById('metaKwInput').value = document.getElementById('seoKeywords')?.value||'';
  document.getElementById('excerptInput').value = excerptEl?.value||'';
  document.getElementById('catInput').value = selectedCatId;
}

function submitForm(status) {
  syncHiddenInputs();
  document.getElementById('statusInput').value = status;
  document.getElementById('postForm').submit();
}

document.getElementById('saveBtn')?.addEventListener('click', () => submitForm(document.getElementById('statusInput').value));
document.getElementById('topSave')?.addEventListener('click', () => submitForm(document.getElementById('statusInput').value));
document.getElementById('saveDraftBtn')?.addEventListener('click', () => submitForm('draft'));

document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && e.key==='s') { e.preventDefault(); submitForm(document.getElementById('statusInput').value); }
});

function removeFeatured() {
  const preview = document.getElementById('featPreview');
  if (preview) preview.remove();
  document.getElementById('featuredImageId').value = '';
  const dz = document.createElement('div');
  dz.className = 'dropzone'; dz.id = 'dropzone';
  dz.onclick = () => document.getElementById('featuredInput').click();
  dz.innerHTML = '<div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button>';
  document.querySelector('.sp-body .feat-preview')?.replaceWith(dz) || document.querySelector('.sp-body').prepend(dz);
  markChanged();
}

document.getElementById('featuredInput')?.addEventListener('change', function() {
  if (this.files[0]) {
    const r = new FileReader();
    r.onload = e => {
      let preview = document.getElementById('featPreview');
      if (!preview) {
        const dz = document.getElementById('dropzone');
        preview = document.createElement('div');
        preview.className = 'feat-preview'; preview.id = 'featPreview';
        preview.innerHTML = '<img src="" alt=""><button type="button" class="feat-remove" onclick="removeFeatured()" title="Odebrat"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
        dz?.replaceWith(preview);
      }
      preview.querySelector('img').src = e.target.result;
    };
    r.readAsDataURL(this.files[0]);
    markChanged();
  }
});
</script>
</body>
</html>