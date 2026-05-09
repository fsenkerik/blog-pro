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
if (!$postData) { setFlash('error','Příspěvek nenalezen'); redirect(ADMIN_URL.'dashboard.php'); }
if (!$auth->canEdit($postData['author_id'])) { setFlash('error','Nemáte oprávnění'); redirect(ADMIN_URL.'dashboard.php'); }

$categories = $category->getAll();
$error = '';

if (isset($_POST['ajax_action'])) {
    error_reporting(0); ini_set('display_errors',0); ob_start();
    header('Content-Type: application/json');
    ob_clean();
    if ($_POST['ajax_action'] === 'upload_image') {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'message'=>'Chyba uploadu']); exit;
        }
        $uploadResult = $upload->uploadImage($_FILES['image'], true, false);
        if (!$uploadResult['success']) {
            echo json_encode(['success'=>false,'message'=>$uploadResult['message']??'Chyba uploadu']); exit;
        }
        $imgInfo = @getimagesize(ROOT_PATH . $uploadResult['path']);
        $mediaData = [
            'filename'=>basename($uploadResult['path']),
            'original_name'=>$_FILES['image']['name'],
            'path'=>$uploadResult['path'],
            'mime_type'=>$imgInfo ? $imgInfo['mime'] : $_FILES['image']['type'],
            'size'=>$uploadResult['size'],
            'width'=>$imgInfo ? $imgInfo[0] : null,
            'height'=>$imgInfo ? $imgInfo[1] : null,
        ];
        $mr = $media->add($mediaData);
        echo json_encode(['success'=>true,'path'=>$uploadResult['path'],'url'=>BASE_URL.ltrim($uploadResult['path'],'/'),'media_id'=>$mr['id']??null]);
        exit;
    }
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
        if ($db->execute()) echo json_encode(['success'=>true,'id'=>$db->lastInsertId(),'name'=>$name]);
        else echo json_encode(['success'=>false,'message'=>'Chyba']);
        exit;
    }
    if ($_POST['ajax_action'] === 'edit_category') {
        $catId = intval($_POST['category_id'] ?? 0);
        $name  = trim($_POST['category_name'] ?? '');
        if (!$catId || empty($name)) { echo json_encode(['success'=>false,'message'=>'Neplatná data']); exit; }
        $db = new Database();
        $db->query('UPDATE categories SET name = :name WHERE id = :id');
        $db->bind(':name',$name); $db->bind(':id',$catId);
        echo json_encode(['success'=>$db->execute()]);
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
            'meta_keywords' => post('meta_keywords'),
            'tags' => post('tags') ?: null,
            'featured_image_alt' => post('featured_image_alt') ?: null,
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
$currentFeatAlt = $postData['featured_image_alt'] ?? '';
$currentTags = $postData['tags'] ?? '';
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
.title-input{width:100%;border:none;background:transparent;outline:none;font-family:var(--serif);font-weight:400;font-size:38px;line-height:1.15;color:var(--ink);letter-spacing:-0.02em}
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
.ed-btn.danger:hover{color:var(--danger);border-color:var(--danger-soft);background:var(--danger-soft)}
.ed-divider{width:1px;height:18px;background:var(--border);margin:0 4px}
.ed-select{display:inline-flex;align-items:center;gap:4px;padding:5px 8px 5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--card);font-size:12px;color:var(--ink-2);min-width:90px;cursor:pointer;transition:border-color .15s}
.ed-select:hover{border-color:var(--accent)}
.media-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.media-modal.on{display:flex}
.media-modal-box{background:var(--card);border:1px solid var(--border);border-radius:16px;width:min(680px,95vw);max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.media-modal-head{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.media-modal-tabs{display:flex;gap:4px;padding:12px 20px 0;border-bottom:1px solid var(--line);flex-shrink:0}
.media-modal-tab{padding:8px 14px;font-size:13px;border-radius:6px 6px 0 0;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;transition:color .15s}
.media-modal-tab.on{color:var(--accent-2);border-bottom-color:var(--accent)}
.media-modal-body{padding:20px;overflow-y:auto;flex:1}
.media-modal-dz{border:2px dashed var(--border);border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s}
.media-modal-dz:hover{border-color:var(--accent);background:var(--accent-soft)}
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px}
.media-grid-item{aspect-ratio:1;border-radius:8px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color .15s,transform .1s;position:relative}
.media-grid-item:hover{border-color:var(--accent);transform:scale(1.02)}
.media-grid-item.selected{border-color:var(--accent)}
.media-grid-item img{width:100%;height:100%;object-fit:cover}
.ed-content{min-height:480px;padding:28px 32px;font-size:16px;line-height:1.7;color:var(--ink);outline:none}
.ed-content:empty::before{content:attr(data-placeholder);color:var(--faint);font-style:italic}
.ed-content p{margin-bottom:1em}
.ed-content h2{font-family:var(--serif);font-size:28px;font-weight:400;line-height:1.2;margin:1.4em 0 .5em}
.ed-content h3{font-family:var(--serif);font-size:22px;font-weight:400;margin:1.2em 0 .4em}
.ed-content blockquote{border-left:3px solid var(--accent);padding:4px 0 4px 18px;margin:18px 0;font-family:var(--serif);font-size:19px;font-style:italic;color:var(--ink-2)}
.ed-content code{font-family:var(--mono);font-size:.9em;background:var(--paper-2);padding:1px 5px;border-radius:4px;color:var(--accent-2)}
.ed-content ul,.ed-content ol{padding-left:22px;margin-bottom:1em}
.ed-stats{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--line);background:var(--card-2)}
.ed-stat{padding:12px 18px;display:flex;align-items:center;gap:10px;border-right:1px solid var(--line)}
.ed-stat:last-child{border-right:none}
.ed-stat-ico{width:28px;height:28px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--card);border:1px solid var(--border);color:var(--muted)}
.ed-stat-label{font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.ed-stat-val{font-family:var(--serif);font-size:22px;line-height:1;color:var(--ink);margin-top:2px}
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
.status-switch button.on.scheduled{color:#7c3aed}
.status-switch button .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.75}
.sp-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:12.5px;border-bottom:1px solid var(--line)}
.sp-row:last-child{border-bottom:none}
.sp-row-label{color:var(--muted)}
.sp-row-val{color:var(--ink);font-family:var(--mono);font-size:12px}
.cat-tools{display:flex;gap:6px;margin-bottom:10px}
.cat-search-wrap{position:relative;flex:1}
.cat-search-wrap svg{position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
.cat-search-wrap input{width:100%;padding:6px 10px 6px 28px;border:1px solid var(--border);border-radius:7px;font-size:12px;font-family:inherit;background:var(--card);color:var(--ink);box-sizing:border-box}
.cat-search-wrap input:focus{outline:none;border-color:var(--accent)}
.cat-add-btn{padding:6px 9px;border-radius:7px;border:1px solid var(--border);background:var(--card);color:var(--body);display:inline-flex;align-items:center;gap:4px;font-size:11.5px;transition:all .15s;flex-shrink:0}
.cat-add-btn:hover{border-color:var(--accent);color:var(--accent-2);background:var(--accent-soft)}
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
.feat-preview{border-radius:10px;overflow:hidden;margin-bottom:10px;position:relative}
.feat-preview img{width:100%;display:block;max-height:160px;object-fit:cover}
.feat-remove{position:absolute;top:8px;right:8px;width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s}
.feat-remove:hover{background:rgba(153,27,27,.8)}
.dropzone{border:1.5px dashed var(--border);border-radius:12px;padding:24px 16px;text-align:center;background:var(--card-2);transition:border-color .15s,background .15s;cursor:pointer}
.dropzone:hover{border-color:var(--accent);background:var(--accent-soft)}
.dz-ico{width:44px;height:44px;border-radius:10px;margin:0 auto 10px;background:var(--accent-soft);color:var(--accent-2);display:flex;align-items:center;justify-content:center}
.dz-text{font-size:12.5px;color:var(--ink-2);margin-bottom:4px;font-weight:500}
.dz-sub{font-size:11px;color:var(--muted);margin-bottom:12px}
.dz-btn{padding:6px 11px;font-size:11.5px;border-radius:6px;display:inline-flex;align-items:center;gap:5px;background:var(--card);border:1px solid var(--border);color:var(--body);transition:all .15s}
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
.tag-input-wrap{display:flex;flex-wrap:wrap;gap:5px;padding:6px;border:1px solid var(--border);border-radius:8px;background:var(--card);min-height:38px;transition:border-color .15s}
.tag-input-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(102,126,234,.15)}
.tagchip{display:inline-flex;align-items:center;gap:5px;padding:3px 4px 3px 10px;border-radius:999px;background:var(--accent-soft);color:var(--accent-2);font-size:11.5px;font-weight:500}
.tagchip button{width:16px;height:16px;border-radius:50%;color:var(--accent-2);opacity:.6;display:inline-flex;align-items:center;justify-content:center;transition:all .15s}
.tagchip button:hover{background:rgba(102,126,234,.2);opacity:1}
.tag-input-wrap input{flex:1;min-width:80px;border:none;outline:none;background:transparent;font-family:inherit;font-size:12.5px;padding:4px 6px}
.seo-magic{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:7px;font-size:11.5px;font-weight:500;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;transition:box-shadow .15s,transform .1s;border:none;cursor:pointer}
.seo-magic:hover{box-shadow:0 4px 12px rgba(102,126,234,.35);transform:translateY(-1px)}
.field-label .help{width:13px;height:13px;border-radius:50%;background:var(--paper-2);color:var(--muted);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-family:var(--font);cursor:help}
.field-label .help:hover{background:var(--accent-soft);color:var(--accent-2)}
.field-label .opt{color:var(--faint);text-transform:none;letter-spacing:0}
@media(max-width:1100px){.ed-grid{grid-template-columns:1fr}.ed-side{position:static}.savebar{margin:28px -16px -64px;padding:14px 16px}}
body.dz-dragging .page-head,body.dz-dragging .title-field,body.dz-dragging .savebar,body.dz-dragging .editor{filter:blur(3px);opacity:.5;transition:filter .15s,opacity .15s;pointer-events:none}
body.dz-dragging .sp:not(.dz-target){filter:blur(3px);opacity:.5;transition:filter .15s,opacity .15s;pointer-events:none}
body.dz-dragging .sp.dz-target{box-shadow:0 0 0 2px var(--accent),0 8px 32px rgba(102,126,234,.3);border-radius:14px;transition:box-shadow .15s}
</style>
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><div class="brand-mark">BP</div><div><div class="brand-name"><?= e(SITE_NAME) ?></div><div class="brand-sub">CMS · Admin</div></div></div>
    <div><div class="nav-label">Workspace</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>dashboard.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Přehled</a>
      <a href="<?= ADMIN_URL ?>posts.php" class="active"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>Příspěvky</a>
      <a href="<?= ADMIN_URL ?>media.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Média</a>
    </nav></div>
    <div><div class="nav-label">Nastavení</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>settings.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Nastavení</a>
    </nav></div>
    <div class="side-user"><div class="avatar"><?= $userInitials ?></div><div class="side-user-info"><div class="side-user-name"><?= e($_SESSION['username'] ?? '') ?></div><div class="side-user-role"><?= e($_SESSION['user_role'] ?? 'Editor') ?></div></div></div>
  </aside>
  <main class="main">
    <?php if ($error): ?><div style="background:var(--danger-soft);color:var(--danger);padding:12px 32px;font-size:13px;border-bottom:1px solid rgba(153,27,27,.15)"><?= e($error) ?></div><?php endif; ?>
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
      <input type="hidden" name="tags" id="tagsInput" value="<?= e($currentTags) ?>">
      <input type="hidden" name="featured_image_alt" id="featAltInput" value="<?= e($currentFeatAlt) ?>">
      <div class="topbar">
        <div class="crumb"><a href="<?= ADMIN_URL ?>dashboard.php" style="color:var(--muted)">Blog Pro</a><span class="sep">/</span><a href="<?= ADMIN_URL ?>posts.php" style="color:var(--muted)">Příspěvky</a><span class="sep">/</span><span class="here">Upravit příspěvek</span></div>
        <div class="top-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-ghost btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Zpět</a></div>
      </div>
      <div class="content">
        <div class="page-head">
          <div>
            <div class="ph-meta"><div class="status-badge <?= $currentStatus ?>"><span class="dot"></span><?php $sl=['published'=>'Publikováno','draft'=>'Koncept','scheduled'=>'Naplánováno']; echo $sl[$currentStatus]??ucfirst($currentStatus); ?></div><span class="save-pill saved" id="savePill"><span class="dot"></span><span id="saveText">Uloženo</span></span></div>
            <h1 class="page-title">Upravit <em>příspěvek.</em></h1>
          </div>
          <div class="ph-actions"><button type="button" class="btn btn-primary btn-sm" id="topSave"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Uložit změny</button></div>
        </div>
        <div class="ed-grid">
          <div style="display:flex;flex-direction:column;gap:18px">
            <div class="title-field">
              <div class="title-label"><span>Titulek</span></div>
              <input type="text" name="title" class="title-input" id="titleInput" value="<?= e($postData['title']) ?>" autocomplete="off">
              <div class="title-foot"><div class="slug-field"><span class="pfx"><?= parse_url(BASE_URL, PHP_URL_HOST) ?>/</span><input type="text" class="slug-val" id="slugDisplay" value="<?= e($postData['slug'] ?? '') ?>" readonly></div><button type="button" class="slug-regen" id="slugRegen">Auto</button></div>
            </div>
            <div class="editor">
              <div class="ed-toolbar">
                <button type="button" class="ed-select" onclick="formatBlockEdit(this)" title="Styl odstavce"><span id="blockLabel">Normální</span><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Tučně" onclick="document.execCommand('bold')"><b>B</b></button>
                <button type="button" class="ed-btn" title="Kurzíva" onclick="document.execCommand('italic')"><i style="font-family:var(--serif)">I</i></button>
                <button type="button" class="ed-btn" title="Podtržení" onclick="document.execCommand('underline')" style="text-decoration:underline;">U</button>
                <button type="button" class="ed-btn" title="Přeškrtnutí" onclick="document.execCommand('strikeThrough')" style="text-decoration:line-through;">S</button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Dolní index" onclick="document.execCommand('subscript')" style="font-size:11px;">X₂</button>
                <button type="button" class="ed-btn" title="Horní index" onclick="document.execCommand('superscript')" style="font-size:11px;">X²</button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn active" title="Zarovnat vlevo" onclick="document.execCommand('justifyLeft')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg></button>
                <button type="button" class="ed-btn" title="Na střed" onclick="document.execCommand('justifyCenter')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="10" x2="6" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="18" y1="18" x2="6" y2="18"/></svg></button>
                <button type="button" class="ed-btn" title="Zarovnat vpravo" onclick="document.execCommand('justifyRight')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="7" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="7" y2="18"/></svg></button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Číslovaný seznam" onclick="document.execCommand('insertOrderedList')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg></button>
                <button type="button" class="ed-btn" title="Odrážky" onclick="document.execCommand('insertUnorderedList')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg></button>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Citace" onclick="document.execCommand('formatBlock',false,'blockquote')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.75-2-2-2H4c-1.25 0-2 .75-2 2v6c0 1.25.75 2 2 2h2c0 0 1 0 1 1s-1 5-4 5"/><path d="M14 21c3 0 7-1 7-8V5c0-1.25-.75-2-2-2h-4c-1.25 0-2 .75-2 2v6c0 1.25.75 2 2 2h2c0 0 1 0 1 1s-1 5-4 5"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit odkaz" onclick="insertLinkEdit()"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit obrázek" onclick="openMediaModalEdit('image')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit video" onclick="openMediaModalEdit('video')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></button>
                <div style="margin-left:auto;display:flex;gap:4px;">
                  <button type="button" class="ed-btn" title="Zpět" onclick="document.execCommand('undo')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg></button>
                  <button type="button" class="ed-btn" title="Vpřed" onclick="document.execCommand('redo')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg></button>
                  <button type="button" class="ed-btn danger" title="Vyčistit formátování" onclick="document.execCommand('removeFormat')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3 7 21"/><path d="M21 9H8"/></svg></button>
                </div>
              </div>
              <div class="ed-content" id="edContent" contenteditable="true" data-placeholder="Začněte psát obsah příspěvku…"><?= $currentContent ?></div>
              <div class="ed-stats">
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/></svg></div><div><div class="ed-stat-label">Slov</div><div class="ed-stat-val" id="statWords">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg></div><div><div class="ed-stat-label">Znaků</div><div class="ed-stat-val" id="statChars">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div><div><div class="ed-stat-label">Čtení</div><div class="ed-stat-val" id="statRead">0<span class="unit">min</span></div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div><div><div class="ed-stat-label">Čitelnost</div><div class="ed-stat-val" id="statReadability">—</div></div></div>
              </div>
            </div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>Perex</div><span class="sp-meta" id="excerptCount"><?= strlen($currentExcerpt) ?> / 280</span></div><div class="sp-body"><textarea class="field-textarea" id="excerptText" placeholder="Krátké uvedení článku…"><?= e($currentExcerpt) ?></textarea></div></div>
          </div>
          <div class="ed-side">
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Stav</div></div><div class="sp-body">
              <div class="status-switch" id="statusSwitch"><button type="button" class="draft <?= $currentStatus==='draft'?'on':'' ?>" data-val="draft"><span class="dot"></span>Koncept</button><button type="button" class="<?= $currentStatus==='published'?'on':'' ?>" data-val="published"><span class="dot"></span>Publikováno</button><button type="button" class="scheduled <?= $currentStatus==='scheduled'?'on':'' ?>" data-val="scheduled"><span class="dot"></span>Plán</button></div>
              <div style="margin-top:14px"><div class="sp-row"><div class="sp-row-label">Autor</div><span class="sp-row-val"><?= e($postData['author_name'] ?? $_SESSION['username'] ?? '') ?></span></div><div class="sp-row"><div class="sp-row-label">Vytvořeno</div><span class="sp-row-val"><?= date('d.m.Y', strtotime($postData['created_at'] ?? 'now')) ?></span></div><div class="sp-row"><div class="sp-row-label">Upraveno</div><span class="sp-row-val"><?= date('d.m.Y H:i', strtotime($postData['updated_at'] ?? 'now')) ?></span></div></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kategorie</div><span class="sp-meta"><span id="catCount"><?= $currentCatId?1:0 ?></span> / <?= count($categories) ?></span></div><div class="sp-body">
              <div class="cat-tools">
                <div class="cat-search-wrap"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="text" id="catSearchE" placeholder="Hledat nebo přidat…" oninput="filterCatsE(this.value)"></div>
                <button type="button" class="cat-add-btn" onclick="addCatFromSearchE()" title="Přidat kategorii"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg></button>
              </div>
              <div class="cat-list" id="catList"><?php foreach ($categories as $i => $cat): ?><div class="cat-item <?= ($cat['id']==$currentCatId)?'on':'' ?>" data-id="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>" onclick="selectCat(this)"><div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:<?= $catColors[$i%count($catColors)] ?>"></div><span class="cat-name"><?= e($cat['name']) ?></span><span class="cat-count"><?= $cat['post_count']??0 ?></span><button type="button" class="cat-edit-btn" onclick="editCatInlineE(event,this)" title="Přejmenovat" style="margin-left:auto;width:20px;height:20px;border-radius:4px;border:1px solid transparent;color:var(--muted);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button></div><?php endforeach; ?></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>Hlavní obrázek</div></div><div class="sp-body">
              <?php if ($currentFeatured): ?>
              <div class="feat-preview" id="featPreview"><img src="<?= e(BASE_URL.$currentFeatured) ?>" alt="Hlavní obrázek"><button type="button" class="feat-remove" onclick="removeFeatured()"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
              <?php else: ?>
              <div class="dropzone" id="dropzone" onclick="document.getElementById('featuredInput').click()"><div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button></div>
              <?php endif; ?>
              <input type="file" id="featuredInput" accept="image/*" style="display:none">
              <div class="field" style="margin-top:10px">
                <div class="field-label">Alt text <span class="opt">(volitelné)</span> <span class="help" title="Popis obrázku pro vyhledávače a čtečky">?</span></div>
                <input type="text" class="field-input" id="featAltField" value="<?= e($currentFeatAlt) ?>" placeholder="Popis obrázku…" maxlength="255" oninput="document.getElementById('featAltInput').value=this.value">
              </div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Štítky</div></div><div class="sp-body">
              <div class="tag-input-wrap" id="tagWrapE" onclick="document.getElementById('tagFieldE').focus()">
                <input type="text" id="tagFieldE" placeholder="Přidat štítek a potvrdit Enterem…" onkeydown="handleTagKeyE(event)">
              </div>
              <div style="font-family:var(--mono);font-size:10.5px;color:var(--muted);margin-top:6px">Oddělte štítky klávesou Enter nebo čárkou</div>
            </div></div>
            <div class="sp"><div class="seo-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>SEO &amp; sdílení</div><button type="button" class="seo-magic" onclick="generateSEOE()" title="Automaticky vyplnit SEO z obsahu"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>Auto SEO</button></div><div class="sp-body">
              <div class="field"><div class="field-label">SEO Title <span class="help" title="Titulek zobrazovaný ve výsledcích vyhledávání">?</span></div><input type="text" class="field-input" id="seoTitle" value="<?= e($currentMetaTitle) ?>" placeholder="Ponechte prázdné pro titulek…" maxlength="80"><div class="field-foot"><span>Optimum 50–60 znaků</span><span id="seoTitleCount" class="ok"><?= strlen($currentMetaTitle) ?> / 60</span></div></div>
              <div class="field"><div class="field-label">Meta Description <span class="help" title="Krátký popis ve výsledcích vyhledávání">?</span></div><textarea class="field-textarea" id="seoDesc" placeholder="Krátký popis…" maxlength="200"><?= e($currentMetaDesc) ?></textarea><div class="field-foot"><span>Optimum 150–160 znaků</span><span id="seoDescCount" class="ok"><?= strlen($currentMetaDesc) ?> / 160</span></div></div>
              <div class="field"><div class="field-label">Klíčová slova <span class="opt">(volitelné)</span></div><input type="text" class="field-input" id="seoKeywords" value="<?= e($currentMetaKw) ?>" placeholder="slovo 1, slovo 2…"></div>
              <div class="field-label" style="margin-top:6px">Náhled v Google</div>
              <div class="serp"><div class="serp-url"><?= parse_url(BASE_URL, PHP_URL_HOST) ?> › <span id="serpSlug"><?= e($postData['slug']??'clanek') ?></span></div><div class="serp-title" id="serpTitle"><?= e($currentMetaTitle?:$postData['title']) ?> — <?= e(SITE_NAME) ?></div><div class="serp-desc" id="serpDesc"><?= e($currentMetaDesc?:'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.') ?></div></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Pokročilé</div></div><div class="sp-body">
              <div class="field"><div class="field-label">Canonical URL <span class="opt">(volitelné)</span></div><input type="text" class="field-input" id="canonicalUrl" name="canonical_url" placeholder="https://…"></div>
              <div class="field"><div class="field-label">CSS třída <span class="opt">(volitelné)</span></div><input type="text" class="field-input" id="customClass" name="custom_class" placeholder="např. featured wide-layout"></div>
              <div class="sp-row"><div class="sp-row-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Připnout nahoře</div><label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12.5px"><input type="checkbox" name="pinned" id="pinnedCheck" style="accent-color:var(--accent);width:15px;height:15px;cursor:pointer"><span style="color:var(--muted)">Zobrazit jako první</span></label></div>
            </div></div>
          </div>
        </div>
        <div class="savebar">
          <div class="savebar-info"><span class="save-pill saved" id="savePill2"><span class="dot"></span><span id="saveText2">Uloženo</span></span><span style="color:var(--faint)">·</span><span class="mono" style="font-size:11.5px">Ctrl+S pro uložení</span></div>
          <div class="savebar-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-cancel btn-sm">Zrušit</a><button type="button" class="btn btn-ghost btn-sm" id="saveDraftBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Uložit jako koncept</button><button type="button" class="btn btn-primary btn-sm" id="saveBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Uložit změny</button></div>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
const slugify=s=>s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9 -]/g,'').trim().replace(/\s+/g,'-').slice(0,80)||'clanek';
const titleEl=document.getElementById('titleInput'),slugDisp=document.getElementById('slugDisplay'),serpSlug=document.getElementById('serpSlug'),serpTitle=document.getElementById('serpTitle'),edContent=document.getElementById('edContent');
titleEl?.addEventListener('input',()=>{serpTitle.textContent=(titleEl.value||'Upravit příspěvek')+' — <?= e(SITE_NAME) ?>';markChanged();});
document.getElementById('slugRegen')?.addEventListener('click',()=>{const s=slugify(titleEl.value);slugDisp.value=s;serpSlug.textContent=s;});
function updateStats(){const txt=(edContent.innerText||'').trim();const words=txt?txt.split(/\s+/).filter(Boolean).length:0;document.getElementById('statWords').textContent=words.toLocaleString('cs-CZ');document.getElementById('statChars').textContent=txt.length.toLocaleString('cs-CZ');document.getElementById('statRead').innerHTML=Math.max(0,Math.round(words/220))+'<span class="unit">min</span>';document.getElementById('statReadability').textContent=words>800?'A':words>300?'B':words>50?'C':'—';}
edContent?.addEventListener('input',()=>{updateStats();markChanged();});updateStats();
const excerptEl=document.getElementById('excerptText'),excerptCountEl=document.getElementById('excerptCount');
excerptEl?.addEventListener('input',()=>{excerptCountEl.textContent=excerptEl.value.length+' / 280';});
const seoTitleEl=document.getElementById('seoTitle'),seoDescEl=document.getElementById('seoDesc'),seoTitleCount=document.getElementById('seoTitleCount'),seoDescCount=document.getElementById('seoDescCount'),serpDescEl=document.getElementById('serpDesc');
seoTitleEl?.addEventListener('input',()=>{const n=seoTitleEl.value.length;seoTitleCount.textContent=n+' / 60';seoTitleCount.className=n>60?'warn':'ok';});
seoDescEl?.addEventListener('input',()=>{const n=seoDescEl.value.length;seoDescCount.textContent=n+' / 160';seoDescCount.className=n>160?'warn':'ok';serpDescEl.textContent=seoDescEl.value||'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.';});
document.querySelectorAll('#statusSwitch button').forEach(btn=>{btn.addEventListener('click',()=>{document.querySelectorAll('#statusSwitch button').forEach(b=>b.classList.remove('on'));btn.classList.add('on');document.getElementById('statusInput').value=btn.dataset.val;markChanged();});});
let selectedCatId='<?= $currentCatId ?>';
function selectCat(el){document.querySelectorAll('.cat-item').forEach(i=>i.classList.remove('on'));el.classList.add('on');selectedCatId=el.dataset.id;document.getElementById('catInput').value=selectedCatId;document.getElementById('catCount').textContent='1';markChanged();}
const pill1=document.getElementById('savePill'),pill2=document.getElementById('savePill2'),txt1=document.getElementById('saveText'),txt2=document.getElementById('saveText2');
function markChanged(){pill1.classList.remove('saved');pill2.classList.remove('saved');txt1.textContent='Neuložené změny';txt2.textContent='Neuložené změny';}
function syncHiddenInputs(){document.getElementById('contentInput').value=edContent.innerHTML;document.getElementById('metaTitleInput').value=seoTitleEl?.value||'';document.getElementById('metaDescInput').value=seoDescEl?.value||'';document.getElementById('metaKwInput').value=document.getElementById('seoKeywords')?.value||'';document.getElementById('excerptInput').value=excerptEl?.value||'';document.getElementById('catInput').value=selectedCatId;document.getElementById('tagsInput').value=getTagsE().join(',');}
// ── Tags (edit) ──────────────────────────────────────────────────────────────
let tagsE = <?= json_encode($currentTags ? array_filter(array_map('trim', explode(',', $currentTags))) : []) ?>;
function getTagsE(){return tagsE;}
function renderTagsE(){const wrap=document.getElementById('tagWrapE');const field=document.getElementById('tagFieldE');if(!wrap)return;wrap.querySelectorAll('.tagchip').forEach(c=>c.remove());tagsE.forEach((t,i)=>{const chip=document.createElement('span');chip.className='tagchip';chip.innerHTML=t+`<button type="button" onclick="removeTagE(${i})" title="Odebrat"><svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>`;wrap.insertBefore(chip,field);});}
function addTagE(val){val=(val||'').trim().replace(/,/g,'').substring(0,40);if(val&&!tagsE.includes(val)){tagsE.push(val);renderTagsE();}document.getElementById('tagFieldE').value='';}
function removeTagE(i){tagsE.splice(i,1);renderTagsE();}
function handleTagKeyE(e){if(e.key==='Enter'||e.key===','){e.preventDefault();addTagE(e.target.value);}else if(e.key==='Backspace'&&!e.target.value&&tagsE.length){removeTagE(tagsE.length-1);}}
document.addEventListener('DOMContentLoaded',()=>renderTagsE());
// ── Auto-SEO (edit) ───────────────────────────────────────────────────────────
function generateSEOE(){const title=titleEl?.value.trim()||'';const text=(edContent?.innerText||'').replace(/\s+/g,' ').trim();if(!title&&!text)return;const seoTitle=title.length>60?title.substring(0,57)+'…':title;const excerpt=text.substring(0,160);const words=text.split(/\s+/).filter(Boolean).slice(0,8).join(', ');if(seoTitleEl){seoTitleEl.value=seoTitle;seoTitleEl.dispatchEvent(new Event('input'));}if(seoDescEl){seoDescEl.value=excerpt;seoDescEl.dispatchEvent(new Event('input'));}const kwEl=document.getElementById('seoKeywords');if(kwEl&&!kwEl.value)kwEl.value=words;markChanged();}
function submitForm(status){syncHiddenInputs();document.getElementById('statusInput').value=status;document.getElementById('postForm').submit();}
document.getElementById('saveBtn')?.addEventListener('click',()=>submitForm(document.getElementById('statusInput').value));
document.getElementById('topSave')?.addEventListener('click',()=>submitForm(document.getElementById('statusInput').value));
document.getElementById('saveDraftBtn')?.addEventListener('click',()=>submitForm('draft'));
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();submitForm(document.getElementById('statusInput').value);}});
function removeFeatured(){const p=document.getElementById('featPreview');if(p)p.remove();document.getElementById('featuredImageId').value='';const dz=document.createElement('div');dz.className='dropzone';dz.id='dropzone';dz.onclick=()=>document.getElementById('featuredInput').click();dz.innerHTML='<div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button>';document.querySelector('.sp-body')?.prepend(dz);markChanged();}
// ── Category filter + inline edit (edit_post) ──────────────────────────────
function filterCatsE(q){ document.querySelectorAll('#catList .cat-item').forEach(el=>{ el.style.display=(el.dataset.name||'').toLowerCase().includes(q.toLowerCase())?'':'none'; }); }
document.querySelectorAll('#catList .cat-item').forEach(el=>{
  el.addEventListener('mouseenter',()=>{const b=el.querySelector('.cat-edit-btn');if(b)b.style.opacity='1';});
  el.addEventListener('mouseleave',()=>{const b=el.querySelector('.cat-edit-btn');if(b)b.style.opacity='0';});
});
async function addCatFromSearchE(){
  const name=(document.getElementById('catSearchE')?.value||'').trim();
  if(!name){document.getElementById('catSearchE')?.focus();return;}
  const fd=new FormData();fd.append('ajax_action','add_category');fd.append('category_name',name);
  try{
    const r=await fetch(location.href,{method:'POST',body:fd});const data=await r.json();
    if(data.success){
      const colors=<?= json_encode($catColors) ?>;
      const idx=document.querySelectorAll('#catList .cat-item').length%colors.length;
      const item=document.createElement('div');item.className='cat-item';item.dataset.id=data.id;item.dataset.name=data.name;
      item.innerHTML=`<div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:${colors[idx]}"></div><span class="cat-name">${data.name}</span><span class="cat-count">0</span><button type="button" class="cat-edit-btn" onclick="editCatInlineE(event,this)" style="margin-left:auto;width:20px;height:20px;border-radius:4px;border:1px solid transparent;color:var(--muted);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>`;
      item.addEventListener('click',function(e){if(e.target.closest('.cat-edit-btn'))return;selectCat(this);});
      item.addEventListener('mouseenter',()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='1';});
      item.addEventListener('mouseleave',()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='0';});
      document.getElementById('catList').appendChild(item);
      document.getElementById('catSearchE').value='';
    } else alert(data.message||'Chyba');
  }catch(e){}
}
async function editCatInlineE(event,btn){
  event.stopPropagation();
  const item=btn.closest('.cat-item');const nameSpan=item.querySelector('.cat-name');const oldName=nameSpan.textContent;
  const newName=prompt('Nový název kategorie:',oldName);
  if(!newName||newName===oldName) return;
  const fd=new FormData();fd.append('ajax_action','edit_category');fd.append('category_id',item.dataset.id);fd.append('category_name',newName);
  try{const r=await fetch(location.href,{method:'POST',body:fd});const data=await r.json();
    if(data.success){nameSpan.textContent=newName;item.dataset.name=newName;markChanged();}
    else alert(data.message||'Chyba');
  }catch(e){}
}
// ── Featured image upload ──────────────────────────────────────────────────
async function uploadFeaturedImage(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image',file);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      showFeaturedPreview(data.url, data.path);
    }
  } catch(e) {}
}
function showFeaturedPreview(url, path) {
  let p = document.getElementById('featPreview');
  if (!p) {
    const dz = document.getElementById('dropzone');
    p = document.createElement('div');
    p.className='feat-preview'; p.id='featPreview';
    p.innerHTML='<img src="" alt=""><button type="button" class="feat-remove" onclick="removeFeatured()"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    dz?.replaceWith(p);
  }
  p.querySelector('img').src = url;
  if (path) document.getElementById('featuredImageId').value = path;
  markChanged();
}
document.getElementById('featuredInput')?.addEventListener('change',function(){
  if(this.files[0]) uploadFeaturedImage(this.files[0]);
});
// Drag & drop with blur overlay
(function(){
  let dragCounter = 0;
  function showBlur() {
    const dz = document.getElementById('dropzone') || document.getElementById('featPreview');
    const sp = dz?.closest('.sp');
    if (sp) sp.classList.add('dz-target');
    document.body.classList.add('dz-dragging');
  }
  function hideBlur() {
    document.body.classList.remove('dz-dragging');
    document.querySelectorAll('.dz-target').forEach(el => el.classList.remove('dz-target'));
  }
  document.addEventListener('dragenter', e => {
    if(e.dataTransfer.types.includes('Files')){ dragCounter++; if(dragCounter===1) showBlur(); }
  });
  document.addEventListener('dragleave', e => {
    dragCounter--; if(dragCounter<=0){dragCounter=0; hideBlur();}
  });
  document.addEventListener('dragover', e => e.preventDefault());
  document.addEventListener('drop', e => {
    e.preventDefault(); dragCounter=0; hideBlur();
    const file=e.dataTransfer.files[0];
    if(file&&file.type.startsWith('image/')) uploadFeaturedImage(file);
  });
  const dz = document.getElementById('dropzone');
  if(dz){
    dz.addEventListener('dragover', e=>{e.preventDefault();dz.style.borderColor='var(--accent)';});
    dz.addEventListener('dragleave', ()=>{dz.style.borderColor='';});
    dz.addEventListener('drop', e=>{e.preventDefault();e.stopPropagation();dz.style.borderColor='';const f=e.dataTransfer.files[0];if(f)uploadFeaturedImage(f);});
  }
})();
</script>
<script src="<?= ASSETS_URL ?>js/admin.js"></script>
<!-- Media insert modal -->
<div class="media-modal" id="mediaModal">
  <div class="media-modal-box">
    <div class="media-modal-head">
      <span style="font-size:14px;font-weight:600;color:var(--ink)" id="mediaModalTitle">Vložit obrázek</span>
      <button type="button" onclick="closeMediaModalEdit()" style="width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--muted);border:1px solid var(--border)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <div class="media-modal-tabs">
      <div class="media-modal-tab on" onclick="switchMediaTabEdit('upload',this)">Z počítače</div>
      <div class="media-modal-tab" onclick="switchMediaTabEdit('gallery',this)">Z galerie</div>
    </div>
    <div class="media-modal-body">
      <div id="mediaTabUploadE">
        <div class="media-modal-dz" id="modalDzE" onclick="document.getElementById('modalFileInputE').click()">
          <div style="margin-bottom:10px;color:var(--accent-2)"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div style="font-size:13.5px;font-weight:500;color:var(--ink-2);margin-bottom:4px">Přetáhněte soubor nebo klikněte pro výběr</div>
          <div style="font-size:11.5px;color:var(--muted)" id="modalAcceptHintE">JPG, PNG, WebP, GIF · max. 8 MB</div>
        </div>
        <input type="file" id="modalFileInputE" style="display:none" accept="image/*">
      </div>
      <div id="mediaTabGalleryE" style="display:none">
        <input type="text" placeholder="Hledat v galerii…" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;color:var(--ink);background:var(--card);margin-bottom:12px;box-sizing:border-box" oninput="filterGalleryE(this.value)">
        <div class="media-grid" id="mediaGalleryGridE"><div style="color:var(--muted);font-size:13px;padding:8px">Načítám…</div></div>
      </div>
    </div>
    <div style="padding:12px 20px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:8px;flex-shrink:0">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeMediaModalEdit()">Zrušit</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="confirmMediaInsertEdit()" id="mediaInsertBtnE" disabled>Vložit</button>
    </div>
  </div>
</div>
<script>
let blockIdxE=0; const blockCycleE=['p','h2','h3'], blockLabelsE={p:'Normální',h2:'Nadpis 2',h3:'Nadpis 3'};
function formatBlockEdit(btn){ blockIdxE=(blockIdxE+1)%blockCycleE.length; document.execCommand('formatBlock',false,blockCycleE[blockIdxE]); document.getElementById('blockLabel').textContent=blockLabelsE[blockCycleE[blockIdxE]]; }
function insertLinkEdit(){ const u=prompt('URL odkazu:','https://'); if(u) document.execCommand('createLink',false,u); }
let mediaInsertTypeE='image', selectedGalleryUrlE=null, savedRangeE=null, galleryItemsE=[];
function openMediaModalEdit(type){
  mediaInsertTypeE=type; selectedGalleryUrlE=null;
  document.getElementById('mediaInsertBtnE').disabled=true;
  document.getElementById('mediaModalTitle').textContent=type==='image'?'Vložit obrázek':'Vložit video';
  document.getElementById('modalFileInputE').accept=type==='image'?'image/*':'video/*';
  document.getElementById('modalAcceptHintE').textContent=type==='image'?'JPG, PNG, WebP · max. 8 MB':'MP4, WebM · max. 50 MB';
  const sel=window.getSelection(); if(sel.rangeCount) savedRangeE=sel.getRangeAt(0).cloneRange();
  document.getElementById('mediaModal').classList.add('on');
  switchMediaTabEdit('upload', document.querySelector('.media-modal-tab'));
}
function closeMediaModalEdit(){ document.getElementById('mediaModal').classList.remove('on'); }
function switchMediaTabEdit(tab,el){
  document.querySelectorAll('.media-modal-tab').forEach(t=>t.classList.remove('on')); el.classList.add('on');
  document.getElementById('mediaTabUploadE').style.display=tab==='upload'?'':'none';
  document.getElementById('mediaTabGalleryE').style.display=tab==='gallery'?'':'none';
  if(tab==='gallery') loadGalleryE();
}
document.getElementById('modalFileInputE').addEventListener('change', async function(){
  if(!this.files[0]) return;
  const fd=new FormData(); fd.append('ajax_action','upload_image'); fd.append('image',this.files[0]);
  try{ const r=await fetch(location.href,{method:'POST',body:fd}); const data=await r.json();
    if(data.success){ selectedGalleryUrlE=data.url; document.getElementById('mediaInsertBtnE').disabled=false; document.getElementById('modalDzE').innerHTML=`<img src="${data.url}" style="max-height:160px;border-radius:8px;max-width:100%;">`; }
  }catch(e){}
});
async function loadGalleryE(){
  const grid=document.getElementById('mediaGalleryGridE');
  if(galleryItemsE.length){renderGalleryE(galleryItemsE);return;}
  grid.innerHTML='<div style="color:var(--muted);font-size:13px;padding:8px">Načítám…</div>';
  try{ const r=await fetch('get_media_ajax.php?action=list&limit=60'); const data=await r.json(); galleryItemsE=data.items||data||[]; renderGalleryE(galleryItemsE); }catch(e){ grid.innerHTML='<div style="color:var(--muted);font-size:13px">Chyba.</div>'; }
}
function renderGalleryE(items){
  const grid=document.getElementById('mediaGalleryGridE');
  const baseUrl='<?= rtrim(BASE_URL,"/") ?>';
  const filtered=items.filter(it=>mediaInsertTypeE==='image'?(it.mime_type||'').startsWith('image'):(it.mime_type||'').startsWith('video'));
  if(!filtered.length){grid.innerHTML='<div style="color:var(--muted);font-size:13px">Žádné soubory.</div>';return;}
  grid.innerHTML=filtered.map(it=>`<div class="media-grid-item" onclick="selectGalleryItemE(this,'${baseUrl}${it.path}')" data-url="${it.path}"><img src="${baseUrl}${it.path}" alt="" onerror="this.style.display='none'"></div>`).join('');
}
function filterGalleryE(q){ renderGalleryE(galleryItemsE.filter(it=>(it.original_name||it.filename||'').toLowerCase().includes(q.toLowerCase()))); }
function selectGalleryItemE(el,url){ document.querySelectorAll('.media-grid-item').forEach(i=>i.classList.remove('selected')); el.classList.add('selected'); selectedGalleryUrlE=url; document.getElementById('mediaInsertBtnE').disabled=false; }
function confirmMediaInsertEdit(){
  if(!selectedGalleryUrlE) return;
  const ed=document.getElementById('edContent'); ed.focus();
  if(savedRangeE){ const sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(savedRangeE); }
  const baseUrl='<?= rtrim(BASE_URL,"/") ?>';
  const fullUrl=selectedGalleryUrlE.startsWith('http')?selectedGalleryUrlE:baseUrl+selectedGalleryUrlE;
  const html=mediaInsertTypeE==='image'?`<img src="${fullUrl}" alt="" style="max-width:100%;border-radius:6px;margin:8px 0;">`:`<video src="${fullUrl}" controls style="max-width:100%;border-radius:6px;margin:8px 0;"></video>`;
  document.execCommand('insertHTML',false,html);
  closeMediaModalEdit(); markChanged();
}
document.getElementById('mediaModal').addEventListener('click', e=>{ if(e.target===e.currentTarget) closeMediaModalEdit(); });
const modalDzE=document.getElementById('modalDzE');
modalDzE.addEventListener('dragover',e=>{e.preventDefault();modalDzE.style.borderColor='var(--accent)';});
modalDzE.addEventListener('dragleave',()=>modalDzE.style.borderColor='');
modalDzE.addEventListener('drop',e=>{ e.preventDefault();modalDzE.style.borderColor=''; const f=e.dataTransfer.files[0]; if(f){const dt=new DataTransfer();dt.items.add(f);document.getElementById('modalFileInputE').files=dt.files;document.getElementById('modalFileInputE').dispatchEvent(new Event('change'));} });
</script>
</body>
</html>