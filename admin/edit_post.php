<?php
define('BLOG_PRO', true);
require_once '../config.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($_POST) &&
    !empty($_SERVER['CONTENT_LENGTH']) &&
    (int) $_SERVER['CONTENT_LENGTH'] > 0
) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Soubor je větší než serverový limit pro upload. Zkuste menší soubor nebo upravte PHP post_max_size.']);
    exit;
}

requireAuth();

$csrfToken = Security::generateToken();
$post = new Post();
$category = new Category();
$upload = new Upload();
$media = new Media();
$auth = new Auth();

$id = get('id');
if (!$id) redirect(ADMIN_URL . 'dashboard.php');

$postData = $post->getById($id);
if (!$postData) { setFlash('error','Příspěvek nenalezen'); redirect(ADMIN_URL.'dashboard.php'); }
if (!$auth->canEdit($postData['author_id'])) { setFlash('error','Role Editor může upravovat jen své vlastní příspěvky.'); redirect(ADMIN_URL.'dashboard.php'); }

$categories = $category->getAll();
$totalPublished = $post->count('published');
$totalDrafts = $post->count('draft');
$totalScheduled = $post->count('scheduled');
$totalAll = $totalPublished + $totalDrafts + $totalScheduled;
$totalMedia = $media->getCount('');
$error = '';

if (isset($_POST['ajax_action'])) {
    error_reporting(0); ini_set('display_errors',0); ob_start();
    header('Content-Type: application/json');
    ob_clean();
    $ajaxAction = $_POST['ajax_action'] ?? '';
    if (!in_array($ajaxAction, ['upload_image', 'upload_media'], true) && !verifyCsrf()) {
        echo json_encode(['success'=>false,'message'=>'Neplatny bezpecnostni token. Obnovte stranku a zkuste to znovu.']);
        exit;
    }
    try {
    if ($_POST['ajax_action'] === 'upload_image' || $_POST['ajax_action'] === 'upload_media') {
        $file = $_FILES['media'] ?? $_FILES['image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'message'=>'Chyba uploadu']); exit;
        }
        $uploadResult = $upload->uploadMedia($file, true, true);
        if (!$uploadResult['success']) {
            echo json_encode(['success'=>false,'message'=>$uploadResult['message']??'Chyba uploadu']); exit;
        }
        echo json_encode([
            'success'=>true,
            'path'=>$uploadResult['path'],
            'url'=>rtrim(BASE_URL, '/').'/'.ltrim($uploadResult['path'],'/'),
            'mime_type'=>$uploadResult['mime_type'] ?? '',
            'original_name'=>$uploadResult['original_name'] ?? $file['name'],
            'filename'=>$uploadResult['filename'] ?? '',
            'media_kind'=>$uploadResult['media_kind'] ?? '',
            'size'=>$uploadResult['size'] ?? 0,
        ]);
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
    echo json_encode(['success'=>false,'message'=>'Neznámá akce']);
    exit;
    } catch (Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " - Edit post AJAX: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode(['success'=>false,'message'=>'Serverová chyba uploadu: '.$e->getMessage()]);
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
            'status' => post('status','published'),
            'meta_title' => post('meta_title'),
            'meta_description' => post('meta_description'),
            'meta_keywords' => post('meta_keywords'),
            'tags' => post('tags') ?: null,
            'featured_image_alt' => post('featured_image_alt') ?: null,
        ];
        $requestedStatus = $data['status'] ?? 'published';
        if ($requestedStatus === 'scheduled') {
            $scheduledAtInput = trim((string) post('scheduled_at'));
            if ($scheduledAtInput === '') {
                $error = 'Vyberte datum a cas publikace.';
            } else {
                $scheduledAt = parseLocalDateTimeInput($scheduledAtInput);
                $now = new DateTimeImmutable('now', appTimezone());
                if (!$scheduledAt || $scheduledAt <= $now) {
                    $error = 'Naplanovane publikovani musi byt v budoucnu.';
                } else {
                    $data['scheduled_at'] = $scheduledAt->format('Y-m-d H:i:s');
                    $data['status'] = 'scheduled';
                }
            }
        } elseif ($requestedStatus === 'draft') {
            $data['scheduled_at'] = null;
            $data['status'] = 'draft';
        } else {
            $data['scheduled_at'] = null;
            $data['status'] = 'published';
        }
        if ($error === '') {
            $featuredImage = trim(post('featured_image_id') ?? '');
            if ($featuredImage !== '') {
                if (ctype_digit($featuredImage)) {
                    $mediaItem = $media->getById($featuredImage);
                    if ($mediaItem) $data['featured_image'] = $mediaItem['path'];
                } else {
                    $data['featured_image'] = $featuredImage;
                }
            } else {
                $data['featured_image'] = null;
            }
            $result = $post->update($id, $data);
            if ($result['success']) { setFlash('success',$data['status'] === 'scheduled' ? 'Prispevek byl naplanovan.' : 'Prispevek byl aktualizovan.'); redirect(ADMIN_URL.'dashboard.php'); }
            else $error = $result['message'] ?? 'Chyba pri ukladani';
        }
    }
}

$userInitials = strtoupper(substr($_SESSION['username'] ?? 'U',0,2));
$catColors = ['#667eea','#764ba2','#5b21b6','#10b981','#f59e0b','#ef4444','#3b82f6','#6366f1'];
$currentCatId = $postData['category_id'] ?? '';
$currentStatus = $postData['status'] ?? 'published';
$currentScheduledAt = !empty($postData['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($postData['scheduled_at'])) : '';
if ($currentStatus === 'scheduled' && !empty($currentScheduledAt)) {
    $currentStatusUi = 'scheduled';
} elseif ($currentStatus === 'draft') {
    $currentStatusUi = 'draft';
} else {
    $currentStatusUi = 'published';
}
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
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Merriweather:wght@400;700&family=Playfair+Display:wght@400;600&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
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
.ed-toolbar{display:flex;align-items:center;gap:4px;padding:8px 12px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,var(--card-2),var(--card));flex-wrap:wrap}
.ed-color-btn{position:relative;flex-direction:column;gap:0;height:32px;padding:3px 4px 2px;width:auto;min-width:26px}
.ed-color-btn input[type=color]{position:absolute;opacity:0;inset:0;width:100%;height:100%;cursor:pointer;border:none;padding:0}
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
.ed-content{min-height:480px;padding:28px 32px;font-size:16px;line-height:1.7;color:var(--ink);outline:none;background:var(--card);border-top:1px solid var(--line)}
.ed-content:empty::before{content:attr(data-placeholder);color:var(--faint);font-style:italic}
.ed-content p{margin-bottom:1em}
.ed-content h1,.ed-content h2,.ed-content h3,.ed-content h4{font-family:var(--font);line-height:1.18;letter-spacing:-0.01em}
.ed-content h2{font-size:28px;font-weight:400;margin:.85em 0 .35em}
.ed-content h3{font-size:22px;font-weight:400;margin:.75em 0 .3em}
.ed-content figure{margin:12px auto;max-width:100%;clear:both}
.ed-content figure img{display:block;width:100%;max-width:100%;height:auto}
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
.publish-panel{overflow:visible;position:relative;z-index:20}
.sp-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--line)}
.sp-title{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:var(--ink)}
.sp-title .ico{width:14px;height:14px;color:var(--accent-2)}
.sp-meta{font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.sp-body{padding:14px 16px}
.adv-box{border:1px dashed var(--border);border-radius:12px;background:linear-gradient(180deg,var(--card),var(--card-2));overflow:hidden}
.adv-box summary{list-style:none;cursor:pointer;display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:14px 16px}
.adv-box summary::-webkit-details-marker{display:none}
.adv-copy{display:flex;flex-direction:column;gap:4px}
.adv-title{font-size:12.5px;font-weight:600;color:var(--ink)}
.adv-sub{font-size:11.5px;line-height:1.55;color:var(--muted)}
.adv-chip{flex-shrink:0;padding:5px 9px;border-radius:999px;background:var(--paper);border:1px solid var(--border);font-family:var(--mono);font-size:10.5px;color:var(--muted)}
.adv-panel{padding:0 16px 16px;border-top:1px solid var(--line)}
.adv-help{margin:12px 0 14px;padding:10px 12px;border-radius:10px;background:var(--accent-soft);font-size:11.5px;line-height:1.6;color:var(--ink-2)}
.status-switch{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:4px;background:var(--paper-2);border:1px solid var(--border);border-radius:8px}
.status-switch button{padding:7px 4px;font-size:11.5px;border-radius:5px;color:var(--muted);font-weight:500;transition:background .15s,color .15s;display:flex;align-items:center;justify-content:center;gap:5px}
.status-switch button.on{background:var(--card);color:var(--accent-2);box-shadow:0 1px 2px rgba(102,126,234,.12)}
.status-switch button.on.draft{color:var(--warn)}
.status-switch button.on.scheduled{color:#7c3aed}
.status-switch button .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.75}
.schedule-card{margin-top:14px;padding:14px;border:1px solid var(--border);border-radius:12px;background:linear-gradient(180deg,#f8fafc,rgba(255,255,255,.92));box-shadow:inset 0 1px 0 rgba(255,255,255,.75)}
.schedule-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
.schedule-title{font-size:12.5px;font-weight:650;color:var(--ink)}
.schedule-copy{font-size:11.5px;line-height:1.55;color:var(--muted);margin-top:3px}
.schedule-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#f8fafc;border:1px solid var(--border);font-family:var(--mono);font-size:10.5px;color:var(--muted);white-space:nowrap}
.schedule-badge .dot{width:6px;height:6px;border-radius:50%;background:currentColor}
.schedule-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end}
.schedule-field{margin:0}
.schedule-confirm{height:42px;white-space:nowrap}
.schedule-note{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:10px;font-size:11px;color:var(--muted)}
.schedule-note strong{color:var(--accent-2);font-weight:650;font-family:var(--mono);font-size:11px;text-align:right}
.schedule-card.is-confirmed{border-color:rgba(16,185,129,.32);background:linear-gradient(180deg,rgba(16,185,129,.08),rgba(255,255,255,.92))}
.schedule-card.is-confirmed .schedule-badge{color:var(--ok);background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.28)}
.schedule-picker-wrap{position:relative}
.schedule-picker-button{width:100%;height:42px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--card);display:flex;align-items:center;justify-content:space-between;gap:10px;font-family:var(--mono);font-size:12px;color:var(--ink);cursor:pointer;transition:border-color .15s,box-shadow .15s,background .15s}
.schedule-picker-button:hover{border-color:rgba(102,126,234,.45);background:#fff}
.schedule-picker-button.on{border-color:var(--accent);box-shadow:0 0 0 3px rgba(102,126,234,.13)}
.schedule-picker-button svg{width:14px;height:14px;color:var(--accent-2);flex-shrink:0}
.schedule-picker-popover{display:none;position:absolute;left:-140px;right:auto;top:calc(100% + 8px);z-index:120;width:min(420px,calc(100vw - 32px));padding:12px;border:1px solid rgba(102,126,234,.18);border-radius:12px;background:#fff;box-shadow:0 18px 46px rgba(31,41,55,.16)}
.schedule-picker-popover.on{display:block}
.schedule-picker-head{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:8px;margin-bottom:10px}
.schedule-picker-month{font-weight:650;font-size:13px;color:var(--ink);text-align:center}
.schedule-picker-nav{display:flex;gap:4px}
.schedule-picker-nav button{width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:var(--card);color:var(--body);display:inline-flex;align-items:center;justify-content:center}
.schedule-picker-nav button:hover{border-color:var(--accent);color:var(--accent-2)}
.schedule-picker-main{display:grid;grid-template-columns:minmax(0,1fr) 118px;gap:12px;align-items:start}
.schedule-calendar{min-width:0}
.schedule-picker-week,.schedule-picker-days{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.schedule-picker-week span{font-family:var(--mono);font-size:10px;color:var(--muted);text-align:center;padding:2px 0 4px}
.schedule-picker-day{height:27px;border-radius:7px;border:1px solid transparent;background:transparent;font-family:var(--mono);font-size:11.5px;color:var(--ink);cursor:pointer}
.schedule-picker-day:hover{background:var(--accent-soft);color:var(--accent-2)}
.schedule-picker-day.muted{color:var(--faint)}
.schedule-picker-day.selected{background:var(--accent);color:#fff}
.schedule-picker-day.disabled{color:var(--faint);cursor:not-allowed;background:transparent;opacity:.45}
.schedule-time-row{display:grid;grid-template-columns:1fr;gap:8px;padding:10px;border:1px solid var(--line);border-radius:10px;background:var(--subtle)}
.schedule-time-row::before{content:'\\010C as';font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.schedule-time-row select{height:36px;border:1px solid var(--border);border-radius:8px;background:var(--card);font-family:var(--mono);font-size:12px;color:var(--ink);padding:0 8px;width:100%}
.schedule-picker-actions{display:flex;justify-content:space-between;gap:8px;margin-top:10px}
.schedule-picker-actions button{height:32px;padding:0 10px;border-radius:7px;font-size:11.5px}
@media(max-width:900px){.schedule-picker-popover{position:relative;left:0;top:auto;margin-top:8px;width:100%}.schedule-picker-main{grid-template-columns:1fr}.schedule-time-row{grid-template-columns:1fr 1fr}.schedule-time-row::before{grid-column:1/-1}.schedule-grid{grid-template-columns:1fr}.schedule-confirm{width:100%}}
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
.cat-actions{margin-left:auto;display:flex;align-items:center;gap:4px;opacity:0;transition:opacity .15s}
.cat-item:hover .cat-actions{opacity:1}
.cat-action-btn{width:20px;height:20px;border-radius:4px;border:1px solid transparent;color:var(--muted);display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s}
.cat-action-btn:hover{background:var(--paper-2);color:var(--ink)}
.cat-delete-btn{color:var(--danger)}
.cat-delete-btn:hover{background:var(--danger-soft);color:var(--danger)}
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
body.dz-dragging .page-head,body.dz-dragging .title-field,body.dz-dragging .savebar{filter:blur(3px);opacity:.5;transition:filter .15s,opacity .15s;pointer-events:none}
body.dz-dragging .sp:not(.dz-target){filter:blur(3px);opacity:.5;transition:filter .15s,opacity .15s;pointer-events:none}
body.dz-dragging .sp.dz-target,body.dz-dragging .editor.dz-target{box-shadow:0 0 0 2px var(--accent),0 8px 32px rgba(102,126,234,.3);border-radius:14px;transition:box-shadow .15s}
body.dz-dragging .editor.dz-hover,body.dz-dragging .sp.dz-hover{box-shadow:0 0 0 3px var(--accent),0 12px 40px rgba(102,126,234,.45);border-radius:14px}
</style>
</head>
<body>
<div class="app">
  <aside class="side">
    <a class="brand" href="<?= ADMIN_URL ?>dashboard.php" aria-label="Přejít na dashboard"><div class="brand-mark" style="width:36px;height:36px;overflow:hidden;flex:0 0 36px;"><img src="../assets/img/blog-pro-logo.png" alt="" style="width:100%;height:100%;object-fit:cover;display:block;"></div><div><div class="brand-name"><?= e(SITE_NAME) ?></div><div class="brand-sub">CMS / v3.0</div></div></a>
    <div><div class="nav-label">Workspace</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>dashboard.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Přehled</a>
      <a href="<?= ADMIN_URL ?>posts.php" class="active"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l4 4v12H4z"/><path d="M16 4v4h4"/><path d="M8 13h8M8 17h5"/></svg>Příspěvky<span class="count"><?= $totalAll ?></span></a>
      <a href="<?= ADMIN_URL ?>media.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16V6a2 2 0 0 1 2-2h8l6 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="9" cy="11" r="1.5"/><path d="m4 18 5-5 5 5 3-3 3 3"/></svg>Média<span class="count"><?= $totalMedia ?></span></a>
    </nav></div>
    <div><div class="nav-label">Nastavení</div><nav class="nav">
      <a href="<?= ADMIN_URL ?>settings.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>Nastavení</a>
      <a href="<?= ADMIN_URL ?>logout.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Odhlásit</a>
    </nav></div>
    <div class="side-user"><div class="avatar"><?= $userInitials ?></div><div class="side-user-info"><div class="side-user-name"><?= e($_SESSION['username'] ?? '') ?></div><div class="side-user-role"><?= e($_SESSION['user_role'] ?? 'Editor') ?></div></div></div>
  </aside>
  <main class="main">
    <?php if ($error): ?><div style="background:var(--danger-soft);color:var(--danger);padding:12px 32px;font-size:13px;border-bottom:1px solid rgba(153,27,27,.15)"><?= e($error) ?></div><?php endif; ?>
    <form id="postForm" method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="status" id="statusInput" value="<?= e($currentStatusUi) ?>">
      <input type="hidden" name="scheduled_at" id="scheduledAtHidden" value="<?= e($currentScheduledAt) ?>">
      <input type="hidden" name="category_id" id="catInput" value="<?= e($currentCatId) ?>">
      <input type="hidden" name="featured_image_id" id="featuredImageId" value="<?= e($currentFeatured) ?>">
      <textarea name="content" id="contentInput" style="display:none"><?= e($currentContent) ?></textarea>
      <input type="hidden" name="meta_title" id="metaTitleInput" value="<?= e($currentMetaTitle) ?>">
      <input type="hidden" name="meta_description" id="metaDescInput" value="<?= e($currentMetaDesc) ?>">
      <input type="hidden" name="meta_keywords" id="metaKwInput" value="<?= e($currentMetaKw) ?>">
      <input type="hidden" name="excerpt" id="excerptInput" value="<?= e($currentExcerpt) ?>">
      <input type="hidden" name="featured_image_alt" id="featAltInput" value="<?= e($currentFeatAlt) ?>">
      <div class="topbar">
        <div class="crumb"><a href="<?= ADMIN_URL ?>dashboard.php" style="color:var(--muted)">Dashboard</a><span class="sep">/</span><a href="<?= ADMIN_URL ?>posts.php" style="color:var(--muted)">Příspěvky</a><span class="sep">/</span><span class="here">Upravit příspěvek</span></div>
        <div class="top-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-ghost btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Zpět</a></div>
      </div>
      <div class="content">
        <div class="page-head">
          <div>
            <div class="ph-meta"><div class="status-badge <?= $currentStatusUi ?>"><span class="dot"></span><?php $sl=['published'=>'Publikováno','scheduled'=>'Naplánováno','draft'=>'Koncept']; echo $sl[$currentStatusUi]??ucfirst($currentStatusUi); ?></div><span class="save-pill saved" id="savePill"><span class="dot"></span><span id="saveText">Uloženo</span></span></div>
            <h1 class="page-title">Upravit <em>příspěvek.</em></h1>
          </div>
          <div class="ph-actions"><button type="button" class="btn btn-primary btn-sm" id="topSave"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg><span class="action-label"><?= $currentStatusUi==='scheduled' ? 'Pl&aacute;novat' : 'Publikovat' ?></span></button></div>
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
                <button type="button" class="ed-select" onmousedown="saveColorRangeE()" onclick="formatBlockEdit(this)" title="Styl odstavce"><span id="blockLabel">Normální</span><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
                <select class="ed-select" style="min-width:100px" onmousedown="saveColorRangeE()" onchange="applyFontE(this.value)" title="Font">
                  <option value="">Font</option>
                  <option value="Arial,sans-serif">Arial</option>
                  <option value="Georgia,serif">Georgia</option>
                  <option value="'Times New Roman',serif">Times New Roman</option>
                  <option value="'Courier New',monospace">Courier New</option>
                  <option value="Verdana,sans-serif">Verdana</option>
                  <option value="'Trebuchet MS',sans-serif">Trebuchet</option>
                  <option value="Inter,sans-serif" style="font-family:Inter,sans-serif">Inter</option>
                  <option value="'Playfair Display',serif" style="font-family:'Playfair Display',serif">Playfair Display</option>
                  <option value="'Merriweather',serif" style="font-family:'Merriweather',serif">Merriweather</option>
                  <option value="'Space Grotesk',sans-serif" style="font-family:'Space Grotesk',sans-serif">Space Grotesk</option>
                  <option value="'IBM Plex Sans',sans-serif" style="font-family:'IBM Plex Sans',sans-serif">IBM Plex Sans</option>
                  <option value="'JetBrains Mono',monospace" style="font-family:'JetBrains Mono',monospace">JetBrains Mono</option>
                </select>
                <select class="ed-select" style="min-width:88px" onmousedown="saveColorRangeE()" onchange="applyFontSizeE(this.value)" title="Velikost písma">
                  <option value="">Velikost</option>
                  <option value="14px">14 px</option>
                  <option value="16px">16 px</option>
                  <option value="18px">18 px</option>
                  <option value="22px">22 px</option>
                  <option value="28px">28 px</option>
                  <option value="36px">36 px</option>
                </select>
                <div class="ed-divider"></div>
                <button type="button" class="ed-btn" title="Tučně" onclick="document.execCommand('bold')"><b>B</b></button>
                <button type="button" class="ed-btn" title="Kurzíva" onclick="document.execCommand('italic')"><i style="font-family:var(--serif)">I</i></button>
                <button type="button" class="ed-btn" title="Podtržení" onclick="document.execCommand('underline')" style="text-decoration:underline;">U</button>
                <button type="button" class="ed-btn" title="Přeškrtnutí" onclick="document.execCommand('strikeThrough')" style="text-decoration:line-through;">S</button>
                <button type="button" class="ed-btn ed-color-btn" title="Barva textu" onmousedown="saveColorRangeE()">
                  <span style="font-size:12px;font-weight:700;line-height:1;display:block">A</span>
                  <span id="fgBarE" style="width:16px;height:3px;background:#000;border-radius:1px;display:block;margin-top:1px"></span>
                  <input type="color" id="fgColorInE" value="#000000" onchange="applyFgColorE(this.value)">
                </button>
                <button type="button" class="ed-btn ed-color-btn" title="Barva pozadí textu" onmousedown="saveColorRangeE()">
                  <span style="font-size:10px;font-weight:700;line-height:1;display:block;background:#ff0;padding:0 2px">ab</span>
                  <span id="bgBarE" style="width:16px;height:3px;background:#ff0;border-radius:1px;display:block;margin-top:1px"></span>
                  <input type="color" id="bgColorInE" value="#ffff00" onchange="applyBgColorE(this.value)">
                </button>
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
                <button type="button" class="ed-btn" title="Vložit dokument" onclick="openMediaModalEdit('document')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit zvuk" onclick="openMediaModalEdit('audio')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></button>
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
            <div class="sp publish-panel"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg>Publikace</div></div><div class="sp-body">
              <div class="status-switch" id="statusSwitch"><button type="button" class="<?= $currentStatusUi==='published'?'on':'' ?>" data-val="published"><span class="dot"></span>Publikovat</button><button type="button" class="scheduled <?= $currentStatusUi==='scheduled'?'on':'' ?>" data-val="scheduled"><span class="dot"></span>Pl&aacute;n</button></div>
              <div id="scheduleBox" class="schedule-card" style="display:<?= $currentStatusUi==='scheduled'?'block':'none' ?>">
                <div class="schedule-head">
                  <div>
                    <div class="schedule-title">Napl&aacute;novat vyd&aacute;n&iacute;</div>
                    <div class="schedule-copy">Vyberte datum a &#269;as, p&#345;&iacute;sp&#283;vek se vyd&aacute; automaticky.</div>
                  </div>
                  <div class="schedule-badge"><span class="dot"></span><span id="schedulePreviewLabel"><?= $currentStatusUi==='scheduled' && !empty($currentScheduledAt) ? 'Term&iacute;n vybr&aacute;n' : 'Term&iacute;n nevybr&aacute;n' ?></span></div>
                </div>
                <div class="schedule-grid">
                  <label class="field schedule-field">
                    <span class="field-label">Datum a &#269;as publikace</span>
                    <input type="hidden" id="scheduledAtInput" value="<?= e($currentScheduledAt) ?>" min="<?= date('Y-m-d\TH:i') ?>">
                    <div class="schedule-picker-wrap">
                      <button type="button" class="schedule-picker-button" id="schedulePickerBtn"><span id="schedulePickerText"><?= $currentStatusUi==='scheduled' && !empty($currentScheduledAt) ? e(date('d.m.Y H:i', strtotime($currentScheduledAt))) : 'Vybrat datum a &#269;as' ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></button>
                      <div class="schedule-picker-popover" id="schedulePickerPopover">
                        <div class="schedule-picker-head"><button type="button" class="btn btn-ghost btn-sm" id="scheduleTodayBtn">Nejbli&#382;&#353;&iacute; term&iacute;n</button><div class="schedule-picker-month" id="schedulePickerMonth"></div><div class="schedule-picker-nav"><button type="button" id="schedulePrevMonth">&lsaquo;</button><button type="button" id="scheduleNextMonth">&rsaquo;</button></div></div>
                        <div class="schedule-picker-main"><div class="schedule-calendar"><div class="schedule-picker-week"><span>Po</span><span>&Uacute;t</span><span>St</span><span>&#268;t</span><span>P&aacute;</span><span>So</span><span>Ne</span></div>
                        <div class="schedule-picker-days" id="schedulePickerDays"></div></div>
                        <div class="schedule-time-row"><select id="scheduleHourSelect" aria-label="Hodina"></select><select id="scheduleMinuteSelect" aria-label="Minuta"></select></div></div>
                        <div class="schedule-picker-actions"><button type="button" class="btn btn-ghost btn-sm" id="scheduleClearBtn">Vymazat</button><button type="button" class="btn btn-primary btn-sm" id="scheduleApplyBtn">Pou&#382;&iacute;t term&iacute;n</button></div>
                      </div>
                    </div>
                  </label>
                  <button type="button" class="btn btn-ghost btn-sm schedule-confirm" id="scheduleConfirmBtn">Nejbli&#382;&#353;&iacute; term&iacute;n</button>
                </div>
                <div class="schedule-note"><span>Vybrat lze pouze budouc&iacute; term&iacute;n.</span><strong id="scheduleHumanLabel"><?= $currentStatusUi==='scheduled' && !empty($currentScheduledAt) ? e(date('d.m.Y H:i', strtotime($currentScheduledAt))) : 'Bez term&iacute;nu' ?></strong></div>
              </div>
              <div style="margin-top:14px"><div class="sp-row"><div class="sp-row-label">Autor</div><span class="sp-row-val"><?= e($postData['author_name'] ?? $_SESSION['username'] ?? '') ?></span></div><div class="sp-row"><div class="sp-row-label">Publikace</div><span class="sp-row-val" id="publishTimingLabel"><?= $currentStatusUi==='scheduled' && !empty($currentScheduledAt) ? e(str_replace('T', ' ', $currentScheduledAt)) : 'Ihned' ?></span></div><div class="sp-row"><div class="sp-row-label">Vytvořeno</div><span class="sp-row-val"><?= date('d.m.Y', strtotime($postData['created_at'] ?? 'now')) ?></span></div><div class="sp-row"><div class="sp-row-label">Upraveno</div><span class="sp-row-val"><?= date('d.m.Y H:i', strtotime($postData['updated_at'] ?? 'now')) ?></span></div></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kategorie</div><span class="sp-meta"><span id="catCount"><?= $currentCatId?1:0 ?></span> / <span id="catTotal"><?= count($categories) ?></span></span></div><div class="sp-body">
              <div class="cat-tools">
                <div class="cat-search-wrap"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="text" id="catSearchE" placeholder="Hledat nebo přidat…" oninput="filterCatsE(this.value)"></div>
                <button type="button" class="cat-add-btn" onclick="addCatFromSearchE()" title="Přidat kategorii"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg></button>
              </div>
              <div class="cat-list" id="catList"><?php foreach ($categories as $i => $cat): ?><div class="cat-item <?= ($cat['id']==$currentCatId)?'on':'' ?>" data-id="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>" onclick="selectCat(this)"><div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:<?= $catColors[$i%count($catColors)] ?>"></div><span class="cat-name"><?= e($cat['name']) ?></span><span class="cat-count"><?= $cat['post_count']??0 ?></span><span class="cat-actions"><button type="button" class="cat-action-btn cat-edit-btn" onclick="editCatInlineE(event,this)" title="Přejmenovat"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button><button type="button" class="cat-action-btn cat-delete-btn" onclick="deleteCatInlineE(event,this)" title="Smazat kategorii"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg></button></span></div><?php endforeach; ?></div>
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
            <div class="sp"><div class="seo-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>SEO &amp; sdílení</div><button type="button" class="seo-magic" onclick="generateSEOE()" title="Automaticky vyplnit SEO z obsahu"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>Auto SEO</button></div><div class="sp-body">
              <div class="field"><div class="field-label">SEO Title <span class="help" title="Titulek zobrazovaný ve výsledcích vyhledávání">?</span></div><input type="text" class="field-input" id="seoTitle" value="<?= e($currentMetaTitle) ?>" placeholder="Ponechte prázdné pro titulek…" maxlength="80"><div class="field-foot"><span>Optimum 50–60 znaků</span><span id="seoTitleCount" class="ok"><?= strlen($currentMetaTitle) ?> / 60</span></div></div>
              <div class="field"><div class="field-label">Meta Description <span class="help" title="Krátký popis ve výsledcích vyhledávání">?</span></div><textarea class="field-textarea" id="seoDesc" placeholder="Krátký popis…" maxlength="200"><?= e($currentMetaDesc) ?></textarea><div class="field-foot"><span>Optimum 150–160 znaků</span><span id="seoDescCount" class="ok"><?= strlen($currentMetaDesc) ?> / 160</span></div></div>
              <div class="field"><div class="field-label">Klíčová slova <span class="opt">(volitelné)</span></div><input type="text" class="field-input" id="seoKeywords" value="<?= e($currentMetaKw) ?>" placeholder="slovo 1, slovo 2…"></div>
              <div class="field-label" style="margin-top:6px">Náhled v Google</div>
              <div class="serp"><div class="serp-url"><?= parse_url(BASE_URL, PHP_URL_HOST) ?> › <span id="serpSlug"><?= e($postData['slug']??'clanek') ?></span></div><div class="serp-title" id="serpTitle"><?= e($currentMetaTitle?:$postData['title']) ?> — <?= e(SITE_NAME) ?></div><div class="serp-desc" id="serpDesc"><?= e($currentMetaDesc?:'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.') ?></div></div>
            </div></div>
          </div>
        </div>
        <div class="savebar">
          <div class="savebar-info"><span class="save-pill saved" id="savePill2"><span class="dot"></span><span id="saveText2">Uloženo</span></span><span style="color:var(--faint)">·</span><span class="mono" style="font-size:11.5px">Ctrl+S pro uložení</span></div>
          <div class="savebar-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-cancel btn-sm">Zrušit</a><button type="button" class="btn btn-ghost btn-sm" id="saveDraftBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Uložit jako koncept</button><button type="button" class="btn btn-primary btn-sm" id="saveBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg><span class="action-label"><?= $currentStatusUi==='scheduled' ? 'Pl&aacute;novat p&#345;&iacute;sp&#283;vek' : 'Publikovat p&#345;&iacute;sp&#283;vek' ?></span></button></div>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
const csrfToken='<?= e($csrfToken) ?>';
const nativeFetch=window.fetch.bind(window);
window.fetch=(input,init={})=>{if(init&&String(init.method||'GET').toUpperCase()==='POST'&&init.body instanceof FormData&&!init.body.has('csrf_token'))init.body.append('csrf_token',csrfToken);return nativeFetch(input,init);};
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
const scheduleBox=document.getElementById('scheduleBox');
const scheduledAtInput=document.getElementById('scheduledAtInput');
const scheduledAtHidden=document.getElementById('scheduledAtHidden');
const publishTimingLabel=document.getElementById('publishTimingLabel');
const schedulePreviewLabel=document.getElementById('schedulePreviewLabel');
const scheduleHumanLabel=document.getElementById('scheduleHumanLabel');
const scheduleConfirmBtn=document.getElementById('scheduleConfirmBtn');
const topSaveBtn=document.getElementById('topSave');
const bottomSaveBtn=document.getElementById('saveBtn');
const schedulePickerBtn=document.getElementById('schedulePickerBtn');
const schedulePickerText=document.getElementById('schedulePickerText');
const schedulePickerPopover=document.getElementById('schedulePickerPopover');
const schedulePickerDays=document.getElementById('schedulePickerDays');
const schedulePickerMonth=document.getElementById('schedulePickerMonth');
const scheduleHourSelect=document.getElementById('scheduleHourSelect');
const scheduleMinuteSelect=document.getElementById('scheduleMinuteSelect');
let scheduleViewDate=scheduledAtInput?.value?new Date(scheduledAtInput.value):new Date();
let scheduleSelectedDate=scheduledAtInput?.value?new Date(scheduledAtInput.value):null;
function pad2(n){return String(n).padStart(2,'0');}
function toLocalValue(date){return `${date.getFullYear()}-${pad2(date.getMonth()+1)}-${pad2(date.getDate())}T${pad2(date.getHours())}:${pad2(date.getMinutes())}`;}
function setActionLabel(btn,label){const target=btn?.querySelector('.action-label');if(target)target.textContent=label;else if(btn)btn.textContent=label;}
function updatePrimaryButtons(status){const scheduled=status==='scheduled';setActionLabel(topSaveBtn,scheduled?'Pl\u00e1novat':'Publikovat');setActionLabel(bottomSaveBtn,scheduled?'Pl\u00e1novat p\u0159\u00edsp\u011bvek':'Publikovat p\u0159\u00edsp\u011bvek');}
function formatScheduledLabel(value){if(!value)return 'Bez term\u00ednu';const date=new Date(value);if(Number.isNaN(date.getTime()))return value.replace('T',' ');return date.toLocaleString('cs-CZ',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});}
function getDefaultScheduledDate(){const next=new Date(Date.now()+30*60*1000);next.setSeconds(0,0);next.setMinutes(Math.ceil(next.getMinutes()/15)*15);return next;}
function fillScheduleTimeSelects(){if(!scheduleHourSelect||!scheduleMinuteSelect)return;scheduleHourSelect.innerHTML=Array.from({length:24},(_,h)=>`<option value="${pad2(h)}">${pad2(h)} h</option>`).join('');scheduleMinuteSelect.innerHTML=['00','15','30','45'].map(m=>`<option value="${m}">${m} min</option>`).join('');}
function setScheduledDate(date){scheduleSelectedDate=new Date(date);scheduleSelectedDate.setSeconds(0,0);scheduledAtInput.value=toLocalValue(scheduleSelectedDate);if(scheduleHourSelect)scheduleHourSelect.value=pad2(scheduleSelectedDate.getHours());if(scheduleMinuteSelect){const minute=Math.round(scheduleSelectedDate.getMinutes()/15)*15%60;scheduleSelectedDate.setMinutes(minute);scheduleMinuteSelect.value=pad2(minute);scheduledAtInput.value=toLocalValue(scheduleSelectedDate);}renderSchedulePicker();updateScheduleState();}
function clearScheduledDate(){scheduleSelectedDate=null;scheduledAtInput.value='';renderSchedulePicker();updateScheduleState();}
function renderSchedulePicker(){if(!schedulePickerDays||!schedulePickerMonth)return;const monthStart=new Date(scheduleViewDate.getFullYear(),scheduleViewDate.getMonth(),1);const start=new Date(monthStart);start.setDate(start.getDate()-((start.getDay()+6)%7));const today=new Date();today.setHours(0,0,0,0);schedulePickerMonth.textContent=monthStart.toLocaleDateString('cs-CZ',{month:'long',year:'numeric'});let html='';for(let i=0;i<42;i++){const d=new Date(start);d.setDate(start.getDate()+i);const disabled=d<today;const muted=d.getMonth()!==scheduleViewDate.getMonth();const selected=scheduleSelectedDate&&d.toDateString()===scheduleSelectedDate.toDateString();html+=`<button type="button" class="schedule-picker-day${muted?' muted':''}${selected?' selected':''}${disabled?' disabled':''}" data-date="${toLocalValue(d).slice(0,10)}" ${disabled?'disabled':''}>${d.getDate()}</button>`;}schedulePickerDays.innerHTML=html;}
function applyPickerDate(datePart){const base=scheduleSelectedDate||getDefaultScheduledDate();const [y,m,d]=datePart.split('-').map(Number);base.setFullYear(y,m-1,d);setScheduledDate(base);}
function applyPickerTime(){const base=scheduleSelectedDate||getDefaultScheduledDate();base.setHours(parseInt(scheduleHourSelect?.value||'0',10),parseInt(scheduleMinuteSelect?.value||'0',10),0,0);setScheduledDate(base);}
function openSchedulePicker(){schedulePickerPopover?.classList.add('on');schedulePickerBtn?.classList.add('on');renderSchedulePicker();}
function closeSchedulePicker(){schedulePickerPopover?.classList.remove('on');schedulePickerBtn?.classList.remove('on');}
function updateScheduleMin(){if(!scheduledAtInput)return;scheduledAtInput.min=toLocalValue(new Date());}
function updateScheduleState(){const status=document.getElementById('statusInput').value;const isScheduled=status==='scheduled';if(scheduleBox) scheduleBox.style.display=isScheduled?'block':'none';if(scheduledAtHidden) scheduledAtHidden.value=isScheduled?(scheduledAtInput?.value||''):'';const humanLabel=isScheduled&&scheduledAtInput?.value?formatScheduledLabel(scheduledAtInput.value):'Ihned';if(publishTimingLabel) publishTimingLabel.textContent=humanLabel;if(scheduleHumanLabel) scheduleHumanLabel.textContent=isScheduled&&scheduledAtInput?.value?humanLabel:'Bez term\u00ednu';if(schedulePickerText) schedulePickerText.textContent=isScheduled&&scheduledAtInput?.value?humanLabel:'Vybrat datum a \u010das';if(schedulePreviewLabel) schedulePreviewLabel.textContent=isScheduled&&scheduledAtInput?.value?'Term\u00edn vybr\u00e1n':'Term\u00edn nevybr\u00e1n';if(scheduleBox) scheduleBox.classList.toggle('is-confirmed',isScheduled&&!!scheduledAtInput?.value);updatePrimaryButtons(status);}
function validateScheduledAt(){if(document.getElementById('statusInput').value!=='scheduled') return true;if(!scheduledAtInput?.value){ alert('Vyberte datum a \u010das publikace.'); openSchedulePicker(); return false; }const selected=new Date(scheduledAtInput.value);if(Number.isNaN(selected.getTime()) || selected.getTime() <= Date.now()){ alert('Napl\u00e1novan\u00e9 publikov\u00e1n\u00ed mus\u00ed b\u00fdt v budoucnu.'); openSchedulePicker(); return false; }return true;}
fillScheduleTimeSelects();
if(scheduleSelectedDate){setScheduledDate(scheduleSelectedDate);}else{renderSchedulePicker();}
schedulePickerBtn?.addEventListener('click',openSchedulePicker);
document.getElementById('schedulePrevMonth')?.addEventListener('click',()=>{scheduleViewDate.setMonth(scheduleViewDate.getMonth()-1);renderSchedulePicker();});
document.getElementById('scheduleNextMonth')?.addEventListener('click',()=>{scheduleViewDate.setMonth(scheduleViewDate.getMonth()+1);renderSchedulePicker();});
document.getElementById('scheduleTodayBtn')?.addEventListener('click',()=>{const d=getDefaultScheduledDate();scheduleViewDate=new Date(d);setScheduledDate(d);markChanged();});
schedulePickerDays?.addEventListener('click',e=>{const btn=e.target.closest('.schedule-picker-day');if(!btn||btn.disabled)return;applyPickerDate(btn.dataset.date);markChanged();});
scheduleHourSelect?.addEventListener('change',()=>{applyPickerTime();markChanged();});
scheduleMinuteSelect?.addEventListener('change',()=>{applyPickerTime();markChanged();});
document.getElementById('scheduleClearBtn')?.addEventListener('click',()=>{clearScheduledDate();markChanged();});
document.getElementById('scheduleApplyBtn')?.addEventListener('click',()=>{if(!scheduledAtInput.value)setScheduledDate(getDefaultScheduledDate());closeSchedulePicker();markChanged();});
document.addEventListener('click',e=>{if(schedulePickerPopover?.classList.contains('on')&&!e.target.closest('.schedule-picker-wrap'))closeSchedulePicker();});
document.querySelectorAll('#statusSwitch button').forEach(btn=>{btn.addEventListener('click',()=>{document.querySelectorAll('#statusSwitch button').forEach(b=>b.classList.remove('on'));btn.classList.add('on');document.getElementById('statusInput').value=btn.dataset.val;if(btn.dataset.val==='scheduled'&&!scheduledAtInput.value)setScheduledDate(getDefaultScheduledDate());updateScheduleMin();updateScheduleState();markChanged();});});
scheduleConfirmBtn?.addEventListener('click',()=>{document.getElementById('statusInput').value='scheduled';const d=getDefaultScheduledDate();scheduleViewDate=new Date(d);setScheduledDate(d);updateScheduleMin();updateScheduleState();if(validateScheduledAt()){scheduleBox?.classList.add('is-confirmed');schedulePreviewLabel.textContent='Term\u00edn vybr\u00e1n';closeSchedulePicker();markChanged();}});
updateScheduleMin();
updateScheduleState();
let selectedCatId='<?= $currentCatId ?>';
function selectCat(el){document.querySelectorAll('.cat-item').forEach(i=>i.classList.remove('on'));el.classList.add('on');selectedCatId=el.dataset.id;document.getElementById('catInput').value=selectedCatId;document.getElementById('catCount').textContent='1';markChanged();}
const pill1=document.getElementById('savePill'),pill2=document.getElementById('savePill2'),txt1=document.getElementById('saveText'),txt2=document.getElementById('saveText2');
function markChanged(){pill1.classList.remove('saved');pill2.classList.remove('saved');txt1.textContent='Neuložené změny';txt2.textContent='Neuložené změny';}
function syncHiddenInputs(){document.getElementById('contentInput').value=edContent.innerHTML;document.getElementById('metaTitleInput').value=seoTitleEl?.value||'';document.getElementById('metaDescInput').value=seoDescEl?.value||'';document.getElementById('metaKwInput').value=document.getElementById('seoKeywords')?.value||'';document.getElementById('excerptInput').value=excerptEl?.value||'';document.getElementById('catInput').value=selectedCatId;if(scheduledAtHidden) scheduledAtHidden.value=document.getElementById('statusInput').value==='scheduled'?(scheduledAtInput?.value||''):'';}
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
function primarySubmitStatus(){return document.getElementById('statusInput').value==='scheduled'?'scheduled':'published';}
function submitForm(status){syncHiddenInputs();document.getElementById('statusInput').value=status;updateScheduleState();if(status==='scheduled' && !validateScheduledAt()) return;const label=status==='scheduled'?'Pl\u00e1nuji\u2026':status==='draft'?'Ukl\u00e1d\u00e1m koncept\u2026':'Publikuji\u2026';if(status==='draft'){const draftBtn=document.getElementById('saveDraftBtn');if(draftBtn){draftBtn.textContent=label;draftBtn.disabled=true;}}else{setActionLabel(bottomSaveBtn,label);if(bottomSaveBtn)bottomSaveBtn.disabled=true;}document.getElementById('postForm').submit();}
document.getElementById('saveBtn')?.addEventListener('click',()=>submitForm(primarySubmitStatus()));
document.getElementById('topSave')?.addEventListener('click',()=>submitForm(primarySubmitStatus()));
document.getElementById('saveDraftBtn')?.addEventListener('click',()=>submitForm('draft'));
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();submitForm(document.getElementById('statusInput').value);}});
function removeFeatured(){const p=document.getElementById('featPreview');const body=p?.closest('.sp-body')||document.getElementById('featuredInput')?.closest('.sp-body');if(p)p.remove();document.getElementById('featuredImageId').value='';const dz=document.createElement('div');dz.className='dropzone';dz.id='dropzone';dz.onclick=()=>document.getElementById('featuredInput').click();dz.innerHTML='<div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button>';body?.prepend(dz);markChanged();}
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
      item.innerHTML=`<div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:${colors[idx]}"></div><span class="cat-name">${data.name}</span><span class="cat-count">0</span><span class="cat-actions"><button type="button" class="cat-action-btn cat-edit-btn" onclick="editCatInlineE(event,this)" title="Přejmenovat"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button><button type="button" class="cat-action-btn cat-delete-btn" onclick="deleteCatInlineE(event,this)" title="Smazat kategorii"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg></button></span>`;
      item.addEventListener('click',function(e){if(e.target.closest('.cat-action-btn'))return;selectCat(this);});
      item.addEventListener('mouseenter',()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='1';});
      item.addEventListener('mouseleave',()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='0';});
      document.getElementById('catList').appendChild(item);
      document.getElementById('catSearchE').value='';
      const totalEl=document.getElementById('catTotal');
      if(totalEl) totalEl.textContent=String(parseInt(totalEl.textContent||'0',10)+1);
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
async function deleteCatInlineE(event, btn) {
  event.stopPropagation();
  const item = btn.closest('.cat-item');
  const name = item.querySelector('.cat-name')?.textContent || 'kategorii';
  const count = parseInt(item.querySelector('.cat-count')?.textContent || '0', 10);
  if (count > 0) {
    alert('Kategorii nelze smazat, protoze obsahuje prispevky.');
    return;
  }
  if (!confirm('Opravdu chcete smazat kategorii "' + name + '"?')) return;
  const fd = new FormData();
  fd.append('ajax_action', 'delete_category');
  fd.append('category_id', item.dataset.id);
  try {
    const r = await fetch(location.href, {method: 'POST', body: fd});
    const data = await r.json();
    if (data.success) {
      if (selectedCatId === item.dataset.id) {
        selectedCatId = '';
        document.getElementById('catInput').value = '';
        document.getElementById('catCount').textContent = '0';
      }
      item.remove();
      const totalEl = document.getElementById('catTotal');
      if (totalEl) totalEl.textContent = String(Math.max(0, parseInt(totalEl.textContent || '0', 10) - 1));
      markChanged();
    } else {
      alert(data.message || 'Chyba pri mazani kategorie');
    }
  } catch(e) {}
}
// ── Featured image upload ──────────────────────────────────────────────────
async function uploadFeaturedImage(file) {
  if (!file) return;
  if (!file.type.startsWith('image/')) {
    PostEditorUtils.showUploadAlert('Hlavní náhledový snímek musí být obrázek. Povolené jsou JPG, PNG, GIF a WebP.');
    return;
  }
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image',file);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      showFeaturedPreview(data.url, data.path);
      const featuredInput = document.getElementById('featuredInput');
      if (featuredInput) featuredInput.value = '';
    }
  } catch(e) {}
}
async function uploadArticleImageEdit(file, range) {
  if (!file || !file.type.startsWith('image/')) return;
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image', file);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      insertImageIntoEditorEdit(data.url, range);
      updateStats();
      markChanged();
    }
  } catch(e) {}
}
function escapeAttrEdit(value) {
  return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}
function insertImageIntoEditorEdit(url, range) {
  const ed = document.getElementById('edContent');
  ed.focus();
  const imgHtml = `<img src="${escapeAttrEdit(url)}" alt="" style="max-width:100%;border-radius:6px;margin:8px 0;"><br>`;
  if (range) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  document.execCommand('insertHTML', false, imgHtml);
}
function getDropRangeEdit(e) {
  if (document.caretRangeFromPoint) {
    return document.caretRangeFromPoint(e.clientX, e.clientY);
  }
  if (document.caretPositionFromPoint) {
    const pos = document.caretPositionFromPoint(e.clientX, e.clientY);
    if (pos) {
      const range = document.createRange();
      range.setStart(pos.offsetNode, pos.offset);
      range.collapse(true);
      return range;
    }
  }
  return null;
}
function showFeaturedPreview(url, path) {
  let p = document.getElementById('featPreview');
  if (!p) {
    const dz = document.getElementById('dropzone');
    p = document.createElement('div');
    p.className='feat-preview'; p.id='featPreview';
    p.innerHTML='<img src="" alt=""><button type="button" class="feat-remove" onclick="removeFeatured()"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    if (dz) dz.replaceWith(p);
    else document.getElementById('featuredInput')?.before(p);
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
  const editorEl = document.querySelector('.editor');
  const editorBody = document.getElementById('edContent');
  const getImageSp = () => (document.getElementById('dropzone') || document.getElementById('featPreview'))?.closest('.sp');
  const isFileDrag = e => e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files');

  function showBlur() {
    const sp = getImageSp();
    if (sp) sp.classList.add('dz-target');
    if (editorEl) editorEl.classList.add('dz-target');
    document.body.classList.add('dz-dragging');
  }
  function hideBlur() {
    document.body.classList.remove('dz-dragging');
    document.querySelectorAll('.dz-target,.dz-hover').forEach(el => el.classList.remove('dz-target','dz-hover'));
  }

  document.addEventListener('dragenter', e => {
    if(isFileDrag(e)){ dragCounter++; if(dragCounter===1) showBlur(); }
  });
  document.addEventListener('dragleave', e => {
    dragCounter--; if(dragCounter<=0){dragCounter=0; hideBlur();}
  });
  document.addEventListener('dragover', e => { if (isFileDrag(e)) e.preventDefault(); });
  document.addEventListener('drop', e => {
    if (!isFileDrag(e)) return;
    e.preventDefault();
    dragCounter=0;
    hideBlur();
  });
  document.addEventListener('dragend', () => { dragCounter=0; hideBlur(); });
  window.addEventListener('blur', () => { dragCounter=0; hideBlur(); });

  // Hover highlight on editor
  if (editorBody) {
    editorBody.addEventListener('dragover', e => { if (!isFileDrag(e)) return; e.preventDefault(); editorEl?.classList.add('dz-hover'); });
    editorBody.addEventListener('dragleave', () => editorEl?.classList.remove('dz-hover'));
    editorBody.addEventListener('drop', e => {
      if (!isFileDrag(e)) return;
      e.preventDefault(); e.stopPropagation(); dragCounter=0; hideBlur();
      const f=e.dataTransfer.files[0]; if(f) uploadArticleImageEdit(f, getDropRangeEdit(e));
    });
  }

  // Hover highlight on featured image panel
  const imageSp = getImageSp();
  if (imageSp) {
    imageSp.addEventListener('dragover', e => { if (!isFileDrag(e)) return; e.preventDefault(); imageSp.classList.add('dz-hover'); });
    imageSp.addEventListener('dragleave', () => imageSp.classList.remove('dz-hover'));
    imageSp.addEventListener('drop', e => {
      if (!isFileDrag(e)) return;
      e.preventDefault(); e.stopPropagation(); dragCounter=0; hideBlur();
      const f=e.dataTransfer.files[0]; if(f&&f.type.startsWith('image/')) uploadFeaturedImage(f);
    });
  }

  const dz = document.getElementById('dropzone');
  if(dz){
    dz.addEventListener('dragover', e=>{if (!isFileDrag(e)) return;e.preventDefault();dz.style.borderColor='var(--accent)';});
    dz.addEventListener('dragleave', ()=>{dz.style.borderColor='';});
    dz.addEventListener('drop', e=>{if (!isFileDrag(e)) return;e.preventDefault();e.stopPropagation();dragCounter=0;hideBlur();dz.style.borderColor='';const f=e.dataTransfer.files[0];if(f)uploadFeaturedImage(f);});
  }
})();
</script>
<script src="<?= ASSETS_URL ?>js/admin.js"></script>
<script src="<?= ASSETS_URL ?>js/post-editor.js?v=20260527-csrf-upload"></script>
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
// ── Font & color (edit) ────────────────────────────────────────────────────────
let colorRangeE = null;
function saveColorRangeE() { const sel=window.getSelection(); if(sel&&sel.rangeCount) colorRangeE=sel.getRangeAt(0).cloneRange(); }
function restoreColorRangeE() { if(!colorRangeE) return; const sel=window.getSelection(); if(sel){sel.removeAllRanges();sel.addRange(colorRangeE);} }
function applyFontE(font) { if(!font) return; restoreColorRangeE(); document.execCommand('styleWithCSS',false,true); document.execCommand('fontName',false,font); }
function applyFgColorE(c) { document.getElementById('fgBarE').style.background=c; restoreColorRangeE(); document.execCommand('styleWithCSS',false,true); document.execCommand('foreColor',false,c); }
function applyBgColorE(c) { document.getElementById('bgBarE').style.background=c; restoreColorRangeE(); document.execCommand('styleWithCSS',false,true); document.execCommand('hiliteColor',false,c); }
let mediaInsertTypeE='image', selectedGalleryUrlE=null, selectedGalleryDataE=null, savedRangeE=null, galleryItemsE=[];
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
<script>
function formatBlockEdit(tag){
  const cycle = ['p', 'h2', 'h3'];
  if (typeof tag !== 'string') {
    const nextIndex = (cycle.indexOf(document.getElementById('blockSelect')?.value || 'p') + 1) % cycle.length;
    tag = cycle[nextIndex];
    if (document.getElementById('blockSelect')) {
      document.getElementById('blockSelect').value = tag;
    }
  }
  if (!tag) return;
  restoreColorRangeE();
  document.execCommand('formatBlock', false, tag);
}
function applyFontE(font) {
  if (!font) return;
  restoreColorRangeE();
  document.execCommand('styleWithCSS', false, true);
  document.execCommand('fontName', false, font);
  PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
}
function applyFontSizeE(size) {
  if (!size) return;
  PostEditorUtils.applyFontSize(size, colorRangeE);
  PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
  updateStats();
  markChanged();
}
async function uploadFeaturedImage(file) {
  if (!file || !file.type.startsWith('image/')) {
    PostEditorUtils.showUploadAlert('Hlavní náhledový snímek musí být obrázek. Povolené jsou JPG, PNG, GIF a WebP.');
    return;
  }
  try {
    const data = await PostEditorUtils.uploadImageWithProgress({
      url: location.href,
      file,
      target: document.getElementById('dropzone') || document.getElementById('featPreview')?.parentElement,
      label: 'Nahrávám hlavní obrázek',
      prepareOptions: { maxDimension: 1600, quality: 0.84 }
    });
    showFeaturedPreview(data.url, data.path);
    const featuredInput = document.getElementById('featuredInput');
    if (featuredInput) featuredInput.value = '';
  } catch(e) {}
}
async function uploadArticleImageEdit(file, range) {
  if (!file) return;
  try {
    const data = await PostEditorUtils.uploadMediaWithProgress({
      url: location.href,
      file,
      target: document.querySelector('.editor'),
      label: 'Vkládám soubor do článku',
      prepareOptions: { maxDimension: 2200, quality: 0.82 }
    });
    insertMediaIntoEditorEdit(data, range);
    updateStats();
    markChanged();
  } catch(e) {}
}
function insertMediaIntoEditorEdit(data, range) {
  const ed = document.getElementById('edContent');
  ed.focus();
  if (range) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  document.execCommand('insertHTML', false, PostEditorUtils.buildMediaHtml(data));
  PostEditorUtils.normalizeEditorMarkup(ed);
}
function insertImageIntoEditorEdit(url, range) {
  const ed = document.getElementById('edContent');
  ed.focus();
  const imgHtml = PostEditorUtils.buildResponsiveImageHtml(url);
  if (range) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  document.execCommand('insertHTML', false, imgHtml);
  PostEditorUtils.normalizeEditorMarkup(ed);
}
function openMediaModalEdit(type){
  mediaInsertTypeE=type;
  selectedGalleryUrlE=null;
  selectedGalleryDataE=null;
  document.getElementById('mediaInsertBtnE').disabled=true;
  const titles={image:'Vložit obrázek',video:'Vložit video',document:'Vložit dokument',audio:'Vložit zvuk'};
  const accepts={image:'image/*',video:'video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov',document:'.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv',audio:'audio/mpeg,audio/wav,audio/ogg,audio/mp4,.mp3,.wav,.ogg,.m4a'};
  const hints={image:'JPG, PNG, WebP, GIF · max. 50 MB',video:'MP4, WebM, MOV · max. 50 MB',document:'PDF, Word, Excel, PowerPoint, TXT, CSV · max. 50 MB',audio:'MP3, WAV, OGG, M4A · max. 50 MB'};
  document.getElementById('mediaModalTitle').textContent=titles[type]||'Vložit soubor';
  document.getElementById('modalFileInputE').accept=accepts[type]||accepts.document;
  document.getElementById('modalAcceptHintE').textContent=hints[type]||hints.document;
  const sel=window.getSelection(); if(sel.rangeCount) savedRangeE=sel.getRangeAt(0).cloneRange();
  document.getElementById('mediaModal').classList.add('on');
  switchMediaTabEdit('upload', document.querySelector('.media-modal-tab'));
}
function renderGalleryE(items){
  const grid=document.getElementById('mediaGalleryGridE');
  const baseUrl='<?= rtrim(BASE_URL,"/") ?>';
  const filtered=items.filter(it=>PostEditorUtils.getMediaKind({mime_type:it.mime_type,original_name:it.original_name,filename:it.filename})===mediaInsertTypeE);
  window.currentMediaGalleryFilteredE=filtered;
  if(!filtered.length){grid.innerHTML='<div style="color:var(--muted);font-size:13px">Žádné soubory pro tento typ.</div>';return;}
  grid.innerHTML=filtered.map((it,index)=>{
    const kind=PostEditorUtils.getMediaKind({mime_type:it.mime_type,original_name:it.original_name,filename:it.filename});
    const ext=(it.original_name||it.filename||'file').split('.').pop().toUpperCase();
    const preview=kind==='image'?`<img src="${baseUrl}${it.path}" alt="" onerror="this.style.display='none'">`:`<div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--accent-2);font-family:var(--mono);font-size:12px">${ext}</div>`;
    return `<div class="media-grid-item" onclick="selectGalleryItemE(this,'${baseUrl}${it.path}',${index})" data-url="${it.path}">${preview}</div>`;
  }).join('');
}
function selectGalleryItemE(el,url,index=null){
  document.querySelectorAll('.media-grid-item').forEach(i=>i.classList.remove('selected'));
  el.classList.add('selected');
  selectedGalleryUrlE=url;
  selectedGalleryDataE=index!==null&&window.currentMediaGalleryFilteredE?window.currentMediaGalleryFilteredE[index]:null;
  document.getElementById('mediaInsertBtnE').disabled=false;
}
function confirmMediaInsertEdit(){
  if(!selectedGalleryUrlE) return;
  const ed=document.getElementById('edContent');
  ed.focus();
  if(savedRangeE){
    const sel=window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRangeE);
  }
  const baseUrl='<?= rtrim(BASE_URL,"/") ?>';
  const fullUrl=selectedGalleryUrlE.startsWith('http')?selectedGalleryUrlE:baseUrl+selectedGalleryUrlE;
  const data=Object.assign({}, selectedGalleryDataE || {}, {url:fullUrl,mime_type:selectedGalleryDataE?.mime_type||'',original_name:selectedGalleryDataE?.original_name||selectedGalleryDataE?.filename||'soubor'});
  const html=PostEditorUtils.buildMediaHtml(data);
  document.execCommand('insertHTML',false,html);
  PostEditorUtils.normalizeEditorMarkup(ed);
  closeMediaModalEdit();
  markChanged();
}
(function rewireModalUploadEdit(){
  const oldInput = document.getElementById('modalFileInputE');
  if (!oldInput) return;
  const nextInput = oldInput.cloneNode();
  oldInput.replaceWith(nextInput);
  nextInput.addEventListener('change', async function(){
    if(!this.files[0]) return;
    try{
      const data = await PostEditorUtils.uploadMediaWithProgress({
        url: location.href,
        file: this.files[0],
        target: document.getElementById('modalDzE'),
        label: 'Nahrávám soubor z počítače',
        prepareOptions: { maxDimension: 2200, quality: 0.82 }
      });
      selectedGalleryUrlE=data.url;
      selectedGalleryDataE=data;
      document.getElementById('mediaInsertBtnE').disabled=false;
      const kind=PostEditorUtils.getMediaKind(data);
      document.getElementById('modalDzE').innerHTML=kind==='image'
        ? `<img src="${data.url}" style="max-height:160px;border-radius:8px;max-width:100%;">`
        : `<div style="padding:24px;font-family:var(--mono);color:var(--accent-2)">${(data.original_name||data.filename||'Soubor').split('.').pop().toUpperCase()} nahrán</div>`;
    }catch(e){}
  });
})();
PostEditorUtils.mountImageToolbar({
  editorId: 'edContent',
  onChange: () => {
    updateStats();
    markChanged();
  }
});
PostEditorUtils.initToolbar({
  editorId: 'edContent',
  toolbarSelector: '.ed-toolbar',
  applyBlock: (tag) => formatBlockEdit(tag),
  onChange: () => {
    updateStats();
    markChanged();
  }
});
PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
</script>
</body>
</html>
