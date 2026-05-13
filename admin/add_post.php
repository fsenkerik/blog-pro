<?php
define('BLOG_PRO', true);
require_once '../config.php';

if (isset($_POST['ajax_action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_start();
}

requireAuth();

$post = new Post();
$category = new Category();
$upload = new Upload();
$categories = $category->getAll();
$error = '';

if (isset($_POST['ajax_action'])) {
    ob_clean();
    header('Content-Type: application/json');
    try {
        if ($_POST['ajax_action'] === 'autosave_draft') {
            $title = trim($_POST['title'] ?? '');
            $content = Security::cleanHTML($_POST['content'] ?? '');
            $featuredImage = trim($_POST['featured_image_from_gallery'] ?? '');
            if (empty($title) && empty($content) && empty($featuredImage)) { echo json_encode(['success'=>false,'message'=>'Prázdný obsah']); exit; }
            $data = [
                'title' => $title ?: 'Bez názvu',
                'content' => $content,
                'excerpt' => $_POST['excerpt'] ?? '',
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'author_id' => $_SESSION['user_id'],
                'status' => 'draft',
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'meta_keywords' => $_POST['meta_keywords'] ?? '',
                'tags' => $_POST['tags'] ?? null,
                'featured_image_alt' => $_POST['featured_image_alt'] ?? null
            ];
            if ($featuredImage !== '') {
                $data['featured_image'] = $featuredImage;
            }
            $draftId = !empty($_POST['draft_id']) ? intval($_POST['draft_id']) : null;
            if ($draftId) {
                $result = $post->update($draftId, $data);
                if ($result['success']) echo json_encode(['success'=>true,'draft_id'=>$draftId,'time'=>date('H:i'),'message'=>'Koncept aktualizován']);
                else echo json_encode(['success'=>false,'message'=>'Chyba při ukládání']);
            } else {
                $result = $post->create($data);
                if ($result['success']) echo json_encode(['success'=>true,'draft_id'=>$result['id'],'time'=>date('H:i'),'message'=>'Koncept vytvořen']);
                else echo json_encode(['success'=>false,'message'=>'Chyba při vytváření']);
            }
            exit;
        }
        if ($_POST['ajax_action'] === 'add_category') {
            $name = trim($_POST['category_name'] ?? '');
            if (empty($name)) { echo json_encode(['success'=>false,'message'=>'Název kategorie nesmí být prázdný']); exit; }
            $db = new Database();
            $slug = mb_strtolower($name,'UTF-8');
            $slug = strtr($slug,['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z']);
            $slug = preg_replace('/[^a-z0-9\s-]/','', $slug);
            $slug = preg_replace('/[\s-]+/','-',$slug);
            $slug = trim($slug,'-');
            $db->query('SELECT id FROM categories WHERE slug = :slug');
            $db->bind(':slug',$slug);
            if ($db->fetch()) { echo json_encode(['success'=>false,'message'=>'Kategorie již existuje']); exit; }
            $db->query('INSERT INTO categories (name,slug) VALUES (:name,:slug)');
            $db->bind(':name',$name); $db->bind(':slug',$slug);
            if ($db->execute()) { $newId=$db->lastInsertId(); echo json_encode(['success'=>true,'id'=>$newId,'name'=>$name,'slug'=>$slug]); }
            else echo json_encode(['success'=>false,'message'=>'Chyba']);
            exit;
        }
        if ($_POST['ajax_action'] === 'upload_image') {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success'=>false,'message'=>'Chyba uploadu']);
                exit;
            }
            $uploadResult = $upload->uploadImage($_FILES['image'], true, true);
            if (!$uploadResult['success']) {
                echo json_encode(['success'=>false,'message'=>$uploadResult['message']??'Chyba uploadu']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'path'    => $uploadResult['path'],
                'url'     => rtrim(BASE_URL, '/') . '/' . ltrim($uploadResult['path'], '/'),
            ]);
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
            $res = $db->fetch();
            if ($res && $res['count'] > 0) { echo json_encode(['success'=>false,'message'=>'Nelze smazat — obsahuje '.$res['count'].' příspěvků']); exit; }
            $db->query('DELETE FROM categories WHERE id = :id');
            $db->bind(':id',$catId);
            echo json_encode(['success'=>$db->execute()]);
            exit;
        }
        echo json_encode(['success'=>false,'message'=>'Neznámá akce']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'Chyba: '.$e->getMessage()]);
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
            'author_id' => $_SESSION['user_id'],
            'status' => post('status','published'),
            'meta_title' => post('meta_title'),
            'meta_description' => post('meta_description'),
            'meta_keywords' => post('meta_keywords'),
            'tags' => post('tags') ?: null,
            'featured_image_alt' => post('featured_image_alt') ?: null,
        ];
        if (($data['status'] ?? 'published') === 'scheduled') {
            $scheduledAtInput = trim((string) post('scheduled_at'));
            if ($scheduledAtInput === '') {
                $error = 'Vyberte datum a �as publikace.';
            } else {
                $scheduledAt = strtotime($scheduledAtInput);
                if ($scheduledAt === false || $scheduledAt <= time()) {
                    $error = 'Napl�novan� publikov�n� mus� b�t v budoucnu.';
                } else {
                    $data['scheduled_at'] = date('Y-m-d H:i:s', $scheduledAt);
                    $data['status'] = 'scheduled';
                }
            }
        } else {
            $data['scheduled_at'] = null;
            $data['status'] = 'published';
        }
        if ($error === '' && isset($_FILES['featured_image']) && $_FILES['featured_image']['error']===UPLOAD_ERR_OK) {
            $uploadResult = $upload->uploadImage($_FILES['featured_image'],true,true);
            if ($uploadResult['success']) $data['featured_image'] = $uploadResult['path'];
        } elseif ($error === '' && !empty($_POST['featured_image_from_gallery'])) {
            $data['featured_image'] = $_POST['featured_image_from_gallery'];
        }
        if ($error === '') {
            $draftId = !empty($_POST['draft_id']) ? intval($_POST['draft_id']) : null;
            if ($draftId) { $result = $post->update($draftId,$data); $msg = $data['status'] === 'scheduled' ? 'Prispevek byl naplanovan!' : 'Prispevek byl publikovan!'; }
            else { $result = $post->create($data); $msg = $data['status'] === 'scheduled' ? 'Prispevek byl vytvoren a naplanovan!' : 'Prispevek byl vytvoren!'; }
            if ($result['success']) { setFlash('success',$msg); redirect(ADMIN_URL.'posts.php'); }
            else $error = $result['message'] ?? 'Nepodarilo se vytvorit prispevek';
        }
    }
}

$userInitials = strtoupper(substr($_SESSION['username'] ?? 'U',0,2));
$catColors = ['#667eea','#764ba2','#5b21b6','#10b981','#f59e0b','#ef4444','#3b82f6','#6366f1'];
$baseUrl = rtrim(BASE_URL,'/').'/'; 
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nový příspěvek · <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Merriweather:wght@400;700&family=Playfair+Display:wght@400;600&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
<style>
.ed-grid{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:flex-start}
.ed-side{position:sticky;top:80px;display:flex;flex-direction:column;gap:16px}
.ph-meta{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:12px}
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
.ed-stat-val{font-family:var(--serif);font-size:22px;line-height:1;color:var(--ink);margin-top:2px;letter-spacing:-0.01em}
.ed-stat-val .unit{font-family:var(--mono);font-size:11px;color:var(--muted);margin-left:3px}
.sp{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(31,41,55,.03)}
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
.status-switch button.on.scheduled{color:var(--violet)}
.status-switch button .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.75}
.sp-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:12.5px;border-bottom:1px solid var(--line)}
.sp-row:last-child{border-bottom:none}
.sp-row-label{color:var(--muted);display:flex;align-items:center;gap:8px}
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
.savebar{position:sticky;bottom:0;z-index:30;margin:28px -32px -64px;padding:14px 32px;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-top:1px solid var(--border);display:flex;align-items:center;gap:12px}
.savebar-info{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--muted)}
.savebar-actions{margin-left:auto;display:flex;gap:8px}
.btn-cancel{background:transparent;color:var(--muted);border:1px solid transparent}
.btn-cancel:hover{color:var(--danger);border-color:var(--danger-soft);background:var(--danger-soft)}
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
    <form id="postForm" method="POST" enctype="multipart/form-data" action="">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
      <input type="hidden" name="status" id="statusInput" value="published">
      <input type="hidden" name="category_id" id="catInput" value="">
      <input type="hidden" name="draft_id" id="draftId" value="">
      <input type="hidden" name="scheduled_at" id="scheduledAtHidden" value="">
      <textarea name="content" id="contentInput" style="display:none"></textarea>
      <input type="hidden" name="meta_title" id="metaTitleInput" value="">
      <input type="hidden" name="meta_description" id="metaDescInput" value="">
      <input type="hidden" name="meta_keywords" id="metaKwInput" value="">
      <input type="hidden" name="excerpt" id="excerptInput" value="">
      <input type="hidden" name="featured_image_alt" id="featAltInput" value="">
      <div class="topbar">
        <div class="crumb"><a href="<?= ADMIN_URL ?>dashboard.php" style="color:var(--muted)">Blog Pro</a><span class="sep">/</span><a href="<?= ADMIN_URL ?>posts.php" style="color:var(--muted)">Příspěvky</a><span class="sep">/</span><span class="here">Nový příspěvek</span></div>
        <div class="top-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-ghost btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Zpět</a></div>
      </div>
      <div class="content">
        <div class="page-head">
          <div>
            <div class="ph-meta"><div class="eyebrow"><span class="pulse" style="background:var(--warn);box-shadow:0 0 0 3px rgba(146,64,14,.15)"></span>Koncept</div><span class="save-pill" id="savePill"><span class="dot"></span><span id="saveText">Neuloženo</span></span></div>
            <h1 class="page-title">Nový <em>příspěvek.</em></h1>
          </div>
          <div class="ph-actions"><button type="button" class="btn btn-primary btn-sm" id="topPublish"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg>Publikovat</button></div>
        </div>
        <div class="ed-grid">
          <div style="display:flex;flex-direction:column;gap:18px">
            <div class="title-field">
              <div class="title-label"><span>Titulek</span><span class="req">●</span><span class="form-label-sub">povinné · max. 90 znaků</span></div>
              <input type="text" name="title" class="title-input" id="titleInput" placeholder="Začněte úderným titulkem…" autocomplete="off">
              <div class="title-foot"><div class="slug-field"><span class="pfx"><?= parse_url(BASE_URL, PHP_URL_HOST) ?>/</span><input type="text" class="slug-val" id="slugDisplay" placeholder="automaticky-z-titulku" readonly></div><button type="button" class="slug-regen" id="slugRegen">Auto</button></div>
            </div>
            <div class="editor">
              <div class="ed-toolbar">
                <button type="button" class="ed-select" onmousedown="saveColorRange()" onclick="formatBlock(this)" title="Styl odstavce"><span id="blockLabel">Normální</span><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
                <select class="ed-select" style="min-width:100px" onmousedown="saveColorRange()" onchange="applyFont(this.value)" title="Font">
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
                <select class="ed-select" style="min-width:88px" onmousedown="saveColorRange()" onchange="applyFontSize(this.value)" title="Velikost písma">
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
                <button type="button" class="ed-btn ed-color-btn" title="Barva textu" onmousedown="saveColorRange()">
                  <span style="font-size:12px;font-weight:700;line-height:1;display:block">A</span>
                  <span id="fgBar" style="width:16px;height:3px;background:#000;border-radius:1px;display:block;margin-top:1px"></span>
                  <input type="color" id="fgColorIn" value="#000000" onchange="applyFgColor(this.value)">
                </button>
                <button type="button" class="ed-btn ed-color-btn" title="Barva pozadí textu" onmousedown="saveColorRange()">
                  <span style="font-size:10px;font-weight:700;line-height:1;display:block;background:#ff0;padding:0 2px">ab</span>
                  <span id="bgBar" style="width:16px;height:3px;background:#ff0;border-radius:1px;display:block;margin-top:1px"></span>
                  <input type="color" id="bgColorIn" value="#ffff00" onchange="applyBgColor(this.value)">
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
                <button type="button" class="ed-btn" title="Vložit odkaz" onclick="insertLink()"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit obrázek" onclick="openMediaModal('image')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></button>
                <button type="button" class="ed-btn" title="Vložit video" onclick="openMediaModal('video')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></button>
                <div style="margin-left:auto;display:flex;gap:4px;">
                  <button type="button" class="ed-btn" title="Zpět" onclick="document.execCommand('undo')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg></button>
                  <button type="button" class="ed-btn" title="Vpřed" onclick="document.execCommand('redo')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg></button>
                  <button type="button" class="ed-btn danger" title="Vyčistit formátování" onclick="document.execCommand('removeFormat')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3 7 21"/><path d="M21 9H8"/></svg></button>
                </div>
              </div>
              <div class="ed-content" id="edContent" contenteditable="true" data-placeholder="Začněte psát váš obsah…"></div>
              <div class="ed-stats">
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/></svg></div><div><div class="ed-stat-label">Slov</div><div class="ed-stat-val" id="statWords">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg></div><div><div class="ed-stat-label">Znaků</div><div class="ed-stat-val" id="statChars">0</div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div><div><div class="ed-stat-label">Čtení</div><div class="ed-stat-val" id="statRead">0<span class="unit">min</span></div></div></div>
                <div class="ed-stat"><div class="ed-stat-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div><div><div class="ed-stat-label">Čitelnost</div><div class="ed-stat-val" id="statReadability">—</div></div></div>
              </div>
            </div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>Perex</div><span class="sp-meta" id="excerptCount">0 / 280</span></div><div class="sp-body"><textarea class="field-textarea" id="excerptText" placeholder="Krátké uvedení článku…"></textarea></div></div>
          </div>
          <div class="ed-side">
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg>Publikace</div></div><div class="sp-body">
              <div class="status-switch" id="statusSwitch"><button type="button" class="on" data-val="published"><span class="dot"></span>Publikovat</button><button type="button" class="scheduled" data-val="scheduled"><span class="dot"></span>Pl�n</button></div>
              <div id="scheduleBox" style="display:none;margin-top:14px">
                <div class="field" style="margin-bottom:0">
                  <div class="field-label">Datum a �as publikace</div>
                  <input type="datetime-local" class="field-input" id="scheduledAtInput" min="<?= date('Y-m-d\TH:i') ?>">
                  <div class="field-foot"><span>Vyberte pouze budouc� term�n.</span><span id="schedulePreviewLabel" class="ok">Napl�nov�no</span></div>
                </div>
              </div>
              <div style="margin-top:14px"><div class="sp-row"><div class="sp-row-label">Autor</div><span class="sp-row-val"><?= e($_SESSION['username'] ?? '') ?></span></div><div class="sp-row"><div class="sp-row-label">Publikace</div><span class="sp-row-val" id="publishTimingLabel">Ihned</span></div></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kategorie</div><span class="sp-meta"><span id="catCount">0</span> / <?= count($categories) ?></span></div><div class="sp-body">
              <div class="cat-tools">
                <div class="cat-search-wrap"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="text" id="catSearch" placeholder="Hledat nebo přidat…" oninput="filterCats(this.value)"></div>
                <button type="button" class="cat-add-btn" onclick="addCatFromSearch()" title="Přidat kategorii"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg></button>
              </div>
              <div class="cat-list" id="catList"><?php foreach ($categories as $i => $cat): ?><div class="cat-item" data-id="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>" onclick="selectCat(this)"><div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:<?= $catColors[$i % count($catColors)] ?>"></div><span class="cat-name"><?= e($cat['name']) ?></span><span class="cat-count"><?= $cat['post_count'] ?? 0 ?></span><button type="button" class="cat-edit-btn" onclick="editCatInline(event,this)" title="Přejmenovat" style="margin-left:auto;width:20px;height:20px;border-radius:4px;border:1px solid transparent;color:var(--muted);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button></div><?php endforeach; ?></div>
            </div></div>
            <div class="sp"><div class="sp-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>Hlavní obrázek</div></div><div class="sp-body">
              <div class="dropzone" id="dropzone" onclick="document.getElementById('featuredInput').click()"><div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button></div>
              <input type="file" name="featured_image" id="featuredInput" accept="image/*" style="display:none">
              <input type="hidden" name="featured_image_from_gallery" id="galleryImage" value="">
              <div class="field" style="margin-top:10px">
                <div class="field-label">Alt text <span class="opt">(volitelné)</span> <span class="help" title="Popis obrázku pro vyhledávače a čtečky">?</span></div>
                <input type="text" class="field-input" id="featAltField" placeholder="Popis obrázku…" maxlength="255" oninput="document.getElementById('featAltInput').value=this.value">
              </div>
            </div></div>
            <div class="sp"><div class="seo-head"><div class="sp-title"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>SEO &amp; sdílení</div><button type="button" class="seo-magic" onclick="generateSEO()" title="Automaticky vyplnit SEO z obsahu"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>Auto SEO</button></div><div class="sp-body">
              <div class="field"><div class="field-label">SEO Title <span class="help" title="Titulek zobrazovaný ve výsledcích vyhledávání">?</span></div><input type="text" class="field-input" id="seoTitle" placeholder="Ponechte prázdné pro titulek…" maxlength="80"><div class="field-foot"><span>Optimum 50–60 znaků</span><span id="seoTitleCount" class="ok">0 / 60</span></div></div>
              <div class="field"><div class="field-label">Meta Description <span class="help" title="Krátký popis ve výsledcích vyhledávání">?</span></div><textarea class="field-textarea" id="seoDesc" placeholder="Krátký popis pro vyhledávače…" maxlength="200"></textarea><div class="field-foot"><span>Optimum 150–160 znaků</span><span id="seoDescCount" class="ok">0 / 160</span></div></div>
              <div class="field"><div class="field-label">Klíčová slova <span class="opt">(volitelné)</span></div><input type="text" class="field-input" id="seoKeywords" placeholder="slovo 1, slovo 2…"></div>
              <div class="field-label" style="margin-top:6px">Náhled v Google</div>
              <div class="serp"><div class="serp-url"><?= parse_url(BASE_URL, PHP_URL_HOST) ?> › <span id="serpSlug">novy-prispevek</span></div><div class="serp-title" id="serpTitle">Nový příspěvek — <?= e(SITE_NAME) ?></div><div class="serp-desc" id="serpDesc">Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.</div></div>
            </div></div>
          </div>
        </div>
        <div class="savebar">
          <div class="savebar-info"><span class="save-pill" id="savePill2"><span class="dot"></span><span id="saveText2">Neuloženo</span></span><span style="color:var(--faint)">·</span><span class="mono" style="font-size:11.5px">Ctrl+S uloží koncept</span></div>
          <div class="savebar-actions"><a href="<?= ADMIN_URL ?>posts.php" class="btn btn-cancel btn-sm">Zrušit</a><button type="button" class="btn btn-ghost btn-sm" id="saveDraftBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Uložit koncept</button><button type="button" class="btn btn-primary btn-sm" id="publishBtn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/></svg>Publikovat příspěvek</button></div>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
const slugify=s=>s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9 -]/g,'').trim().replace(/\s+/g,'-').slice(0,80)||'novy-prispevek';
const titleEl=document.getElementById('titleInput'),slugDisp=document.getElementById('slugDisplay'),serpSlug=document.getElementById('serpSlug'),serpTitle=document.getElementById('serpTitle'),edContent=document.getElementById('edContent');
let slugDirty=false;
titleEl.addEventListener('input',()=>{if(!slugDirty){const s=slugify(titleEl.value);slugDisp.value=s;serpSlug.textContent=s;}serpTitle.textContent=(titleEl.value||'Nový příspěvek')+' — <?= e(SITE_NAME) ?>';markUnsaved();});
document.getElementById('slugRegen').addEventListener('click',()=>{slugDirty=false;const s=slugify(titleEl.value);slugDisp.value=s;serpSlug.textContent=s;});
function updateStats(){const txt=(edContent.innerText||'').trim();const words=txt?txt.split(/\s+/).filter(Boolean).length:0;document.getElementById('statWords').textContent=words.toLocaleString('cs-CZ');document.getElementById('statChars').textContent=txt.length.toLocaleString('cs-CZ');document.getElementById('statRead').innerHTML=Math.max(0,Math.round(words/220))+'<span class="unit">min</span>';document.getElementById('statReadability').textContent=words>800?'A':words>300?'B':words>50?'C':'—';}
edContent.addEventListener('input',()=>{updateStats();markUnsaved();});updateStats();
const excerptEl=document.getElementById('excerptText'),excerptCount=document.getElementById('excerptCount');
excerptEl?.addEventListener('input',()=>{excerptCount.textContent=excerptEl.value.length+' / 280';});
const seoTitleEl=document.getElementById('seoTitle'),seoDescEl=document.getElementById('seoDesc'),seoTitleCount=document.getElementById('seoTitleCount'),seoDescCount=document.getElementById('seoDescCount'),serpDescEl=document.getElementById('serpDesc');
seoTitleEl?.addEventListener('input',()=>{const n=seoTitleEl.value.length;seoTitleCount.textContent=n+' / 60';seoTitleCount.className=n>60?'warn':'ok';});
seoDescEl?.addEventListener('input',()=>{const n=seoDescEl.value.length;seoDescCount.textContent=n+' / 160';seoDescCount.className=n>160?'warn':'ok';serpDescEl.textContent=seoDescEl.value||'Krátký popis příspěvku se zobrazí ve výsledcích vyhledávání.';});
const statusLabels={published:'Publikovat',scheduled:'Napl�nov�no'};
const statusColors={published:'var(--ok)',scheduled:'var(--violet)'};
const scheduleBox=document.getElementById('scheduleBox');
const scheduledAtInput=document.getElementById('scheduledAtInput');
const scheduledAtHidden=document.getElementById('scheduledAtHidden');
const publishTimingLabel=document.getElementById('publishTimingLabel');
function updateScheduleMin(){
  if(!scheduledAtInput)return;
  const now=new Date();
  now.setSeconds(0,0);
  const offset=now.getTimezoneOffset();
  scheduledAtInput.min=new Date(now.getTime()-offset*60000).toISOString().slice(0,16);
}
function updateScheduleState(){
  const val=document.getElementById('statusInput').value;
  const isScheduled=val==='scheduled';
  if(scheduleBox) scheduleBox.style.display=isScheduled?'block':'none';
  if(scheduledAtHidden) scheduledAtHidden.value=isScheduled?(scheduledAtInput?.value||''):'';
  if(publishTimingLabel) publishTimingLabel.textContent=isScheduled&&scheduledAtInput?.value?scheduledAtInput.value.replace('T',' '):'Ihned';
  const eyebrow=document.querySelector('.ph-meta .eyebrow');
  if(eyebrow){ eyebrow.innerHTML=`<span class="pulse" style="background:${statusColors[val]??'var(--ok)'};box-shadow:0 0 0 3px rgba(102,126,234,.15)"></span>${statusLabels[val]??val}`; }
}
function validateScheduledAt(){
  if(document.getElementById('statusInput').value!=='scheduled') return true;
  if(!scheduledAtInput?.value){ alert('Vyberte datum a �as publikace.'); scheduledAtInput?.focus(); return false; }
  const selected=new Date(scheduledAtInput.value);
  if(Number.isNaN(selected.getTime()) || selected.getTime() <= Date.now()){ alert('Napl�novan� publikov�n� mus� b�t v budoucnu.'); scheduledAtInput?.focus(); return false; }
  return true;
}
document.querySelectorAll('#statusSwitch button').forEach(btn=>{btn.addEventListener('click',()=>{
  document.querySelectorAll('#statusSwitch button').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  document.getElementById('statusInput').value=btn.dataset.val;
  updateScheduleMin();
  updateScheduleState();
  markUnsaved();
});});
scheduledAtInput?.addEventListener('input',()=>{updateScheduleMin();updateScheduleState();markUnsaved();});
updateScheduleMin();
updateScheduleState();
let selectedCatId='';
function selectCat(el){document.querySelectorAll('.cat-item').forEach(i=>i.classList.remove('on'));el.classList.add('on');selectedCatId=el.dataset.id;document.getElementById('catInput').value=selectedCatId;document.getElementById('catCount').textContent='1';}
const pill1=document.getElementById('savePill'),pill2=document.getElementById('savePill2'),txt1=document.getElementById('saveText'),txt2=document.getElementById('saveText2');
let saveTimer=null,draftId='',isSaving=false;
function markUnsaved(){pill1.classList.remove('saved');pill2.classList.remove('saved');txt1.textContent='Neuloženo';txt2.textContent='Neuloženo';clearTimeout(saveTimer);saveTimer=setTimeout(autoSave,3000);}
async function autoSave(){if(isSaving)return;isSaving=true;syncHiddenInputs();const fd=new FormData();fd.append('ajax_action','autosave_draft');fd.append('title',titleEl.value);fd.append('content',edContent.innerHTML);fd.append('category_id',selectedCatId);fd.append('meta_title',document.getElementById('metaTitleInput').value);fd.append('meta_description',document.getElementById('metaDescInput').value);fd.append('meta_keywords',document.getElementById('metaKwInput').value);fd.append('excerpt',document.getElementById('excerptInput').value);fd.append('featured_image_alt',document.getElementById('featAltInput').value);fd.append('featured_image_from_gallery',document.getElementById('galleryImage')?.value||'');if(draftId)fd.append('draft_id',draftId);try{const r=await fetch(location.href,{method:'POST',body:fd});const data=await r.json();if(data.success){draftId=data.draft_id;document.getElementById('draftId').value=draftId;const t=new Date().toLocaleTimeString('cs-CZ',{hour:'2-digit',minute:'2-digit'});pill1.classList.add('saved');pill2.classList.add('saved');txt1.textContent='Uloženo · '+t;txt2.textContent='Koncept uložen · '+t;}}catch(e){}finally{isSaving=false;}}
function syncHiddenInputs(){document.getElementById('contentInput').value=edContent.innerHTML;document.getElementById('metaTitleInput').value=seoTitleEl?.value||'';document.getElementById('metaDescInput').value=seoDescEl?.value||'';document.getElementById('metaKwInput').value=document.getElementById('seoKeywords')?.value||'';document.getElementById('excerptInput').value=excerptEl?.value||'';if(scheduledAtHidden) scheduledAtHidden.value=document.getElementById('statusInput').value==='scheduled'?(scheduledAtInput?.value||''):'';}
// ── Tags ─────────────────────────────────────────────────────────────────────
let tags = [];
function getTags(){return tags;}
function renderTags(){const wrap=document.getElementById('tagWrap');const field=document.getElementById('tagField');wrap.querySelectorAll('.tagchip').forEach(c=>c.remove());tags.forEach((t,i)=>{const chip=document.createElement('span');chip.className='tagchip';chip.innerHTML=t+`<button type="button" onclick="removeTag(${i})" title="Odebrat"><svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>`;wrap.insertBefore(chip,field);});}
function addTag(val){val=(val||'').trim().replace(/,/g,'').substring(0,40);if(val&&!tags.includes(val)){tags.push(val);renderTags();}document.getElementById('tagField').value='';}
function removeTag(i){tags.splice(i,1);renderTags();}
function handleTagKey(e){if(e.key==='Enter'||e.key===','){e.preventDefault();addTag(e.target.value);}else if(e.key==='Backspace'&&!e.target.value&&tags.length){removeTag(tags.length-1);}}
// ── Auto-SEO ──────────────────────────────────────────────────────────────────
function generateSEO(){const title=titleEl.value.trim();const text=(edContent.innerText||'').replace(/\s+/g,' ').trim();if(!title&&!text)return;const seoTitle=title.length>60?title.substring(0,57)+'…':title;const excerpt=text.substring(0,160);const words=text.split(/\s+/).filter(Boolean).slice(0,8).join(', ');if(seoTitleEl){seoTitleEl.value=seoTitle;seoTitleEl.dispatchEvent(new Event('input'));}if(seoDescEl){seoDescEl.value=excerpt;seoDescEl.dispatchEvent(new Event('input'));}const kwEl=document.getElementById('seoKeywords');if(kwEl&&!kwEl.value)kwEl.value=words;}
function submitForm(status){
  syncHiddenInputs();
  document.getElementById('statusInput').value=status;
  updateScheduleState();
  if(status==='scheduled' && !validateScheduledAt()) return;
  const publishBtn=document.getElementById('publishBtn');
  const saveDraftBtn=document.getElementById('saveDraftBtn');
  const label=status==='published'?'Publikuji�':status==='scheduled'?'Pl�nuji�':'Ukl�d�m koncept�';
  if(publishBtn&&(status==='published'||status==='scheduled')){publishBtn.textContent=label;publishBtn.disabled=true;}
  if(saveDraftBtn&&status==='draft'){saveDraftBtn.textContent=label;saveDraftBtn.disabled=true;}
  document.getElementById('postForm').submit();
}
document.getElementById('publishBtn')?.addEventListener('click',()=>submitForm(document.getElementById('statusInput').value));
document.getElementById('topPublish')?.addEventListener('click',()=>submitForm(document.getElementById('statusInput').value));
document.getElementById('saveDraftBtn')?.addEventListener('click',()=>submitForm('draft'));
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();submitForm('draft');}});
// ── Category filter + inline edit ──────────────────────────────────────────
function filterCats(q) {
  document.querySelectorAll('#catList .cat-item').forEach(el => {
    const name = (el.dataset.name||el.querySelector('.cat-name')?.textContent||'').toLowerCase();
    el.style.display = name.includes(q.toLowerCase()) ? '' : 'none';
  });
}
document.querySelectorAll('#catList .cat-item').forEach(el => {
  el.addEventListener('mouseenter', () => { const b=el.querySelector('.cat-edit-btn'); if(b) b.style.opacity='1'; });
  el.addEventListener('mouseleave', () => { const b=el.querySelector('.cat-edit-btn'); if(b) b.style.opacity='0'; });
});
async function addCatFromSearch() {
  const name = (document.getElementById('catSearch')?.value||'').trim();
  if (!name) { document.getElementById('catSearch')?.focus(); return; }
  const fd = new FormData(); fd.append('ajax_action','add_category'); fd.append('category_name',name);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      const colors = <?= json_encode($catColors) ?>;
      const idx = document.querySelectorAll('#catList .cat-item').length % colors.length;
      const item = document.createElement('div');
      item.className = 'cat-item'; item.dataset.id = data.id; item.dataset.name = data.name;
      item.innerHTML = `<div class="cat-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="cat-swatch" style="background:${colors[idx]}"></div><span class="cat-name">${data.name}</span><span class="cat-count">0</span><button type="button" class="cat-edit-btn" onclick="editCatInline(event,this)" title="Přejmenovat" style="margin-left:auto;width:20px;height:20px;border-radius:4px;border:1px solid transparent;color:var(--muted);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>`;
      item.addEventListener('click', function(e){if(e.target.closest('.cat-edit-btn'))return;selectCat(this);});
      item.addEventListener('mouseenter', ()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='1';});
      item.addEventListener('mouseleave', ()=>{const b=item.querySelector('.cat-edit-btn');if(b)b.style.opacity='0';});
      document.getElementById('catList').appendChild(item);
      document.getElementById('catSearch').value='';
      filterCats('');
    } else { alert(data.message||'Chyba'); }
  } catch(e) {}
}
async function editCatInline(event, btn) {
  event.stopPropagation();
  const item = btn.closest('.cat-item');
  const nameSpan = item.querySelector('.cat-name');
  const oldName = nameSpan.textContent;
  const newName = prompt('Nový název kategorie:', oldName);
  if (!newName || newName === oldName) return;
  const fd = new FormData(); fd.append('ajax_action','edit_category'); fd.append('category_id',item.dataset.id); fd.append('category_name',newName);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) { nameSpan.textContent=newName; item.dataset.name=newName; }
    else alert(data.message||'Chyba přejmenování');
  } catch(e) {}
}
// ── Featured image upload (drag & drop + file input) ──────────────────────
async function uploadFeaturedImage(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image', file);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      showFeaturedPreview(data.url);
      document.getElementById('galleryImage').value = data.path;
      const featuredInput = document.getElementById('featuredInput');
      if (featuredInput) featuredInput.value = '';
      markUnsaved();
      autoSave();
    }
  } catch(e) {}
}
async function uploadArticleImage(file, range) {
  if (!file || !file.type.startsWith('image/')) return;
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image', file);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      insertImageIntoEditor(data.url, range);
      updateStats();
      markUnsaved();
      autoSave();
    }
  } catch(e) {}
}
function escapeAttr(value) {
  return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}
function insertImageIntoEditor(url, range) {
  const ed = document.getElementById('edContent');
  ed.focus();
  const imgHtml = `<img src="${escapeAttr(url)}" alt="" style="max-width:100%;border-radius:6px;margin:8px 0;"><br>`;
  if (range) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  document.execCommand('insertHTML', false, imgHtml);
}
function getDropRange(e) {
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
function showFeaturedPreview(url) {
  const target = document.getElementById('featPreviewAdd') || document.getElementById('dropzone');
  const sp = target?.closest('.sp-body') || target?.parentNode;
  const existing = document.getElementById('featPreviewAdd');
  if (existing) existing.remove();
  const dz = document.getElementById('dropzone');
  const wrap = document.createElement('div');
  wrap.id = 'featPreviewAdd';
  wrap.style.cssText = 'border-radius:10px;overflow:hidden;margin-bottom:10px;position:relative;';
  wrap.innerHTML = `<img src="${url}" alt="" style="width:100%;display:block;max-height:160px;object-fit:cover;">
    <button type="button" onclick="removeFeaturedAdd()" style="position:absolute;top:8px;right:8px;width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;border:none;">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>`;
  if (dz) dz.replaceWith(wrap);
  else if (sp) sp.prepend(wrap);
}
function removeFeaturedAdd() {
  const wrap = document.getElementById('featPreviewAdd');
  if (wrap) {
    const dz = document.createElement('div');
    dz.className='dropzone'; dz.id='dropzone';
    dz.innerHTML = document.querySelector('template#dzTemplate')?.innerHTML || '<div class="dz-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div><div class="dz-text">Přetáhněte obrázek příspěvku</div><div class="dz-sub">JPG, PNG nebo WebP · max. 8 MB</div><button type="button" class="dz-btn primary">Vybrat obrázek</button>';
    dz.onclick = () => document.getElementById('featuredInput').click();
    wrap.replaceWith(dz);
    document.getElementById('galleryImage').value='';
  }
}
document.getElementById('featuredInput')?.addEventListener('change',function(){
  if(this.files[0]) uploadFeaturedImage(this.files[0]);
});
// Drag & drop with blur overlay
(function(){
  let dragCounter = 0;
  const editorEl = document.querySelector('.editor');
  const editorBody = document.getElementById('edContent');
  const getImageSp = () => (document.getElementById('dropzone') || document.getElementById('featPreviewAdd'))?.closest('.sp');
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
    if (isFileDrag(e)) { dragCounter++; if(dragCounter===1) showBlur(); }
  });
  document.addEventListener('dragleave', e => {
    dragCounter--; if (dragCounter <= 0) { dragCounter=0; hideBlur(); }
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
      const f = e.dataTransfer.files[0];
      if(f && f.type.startsWith('image/')) uploadArticleImage(f, getDropRange(e));
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
      const f = e.dataTransfer.files[0]; if(f && f.type.startsWith('image/')) uploadFeaturedImage(f);
    });
  }

  const dz = document.getElementById('dropzone');
  if (dz) {
    dz.addEventListener('dragover', e => { if (!isFileDrag(e)) return; e.preventDefault(); dz.style.borderColor='var(--accent)'; });
    dz.addEventListener('dragleave', () => { dz.style.borderColor=''; });
    dz.addEventListener('drop', e => { if (!isFileDrag(e)) return; e.preventDefault(); e.stopPropagation(); dragCounter=0; hideBlur(); dz.style.borderColor=''; const f=e.dataTransfer.files[0]; if(f) uploadFeaturedImage(f); });
  }
})();
</script>
<script src="<?= ASSETS_URL ?>js/admin.js"></script>
<script src="<?= ASSETS_URL ?>js/post-editor.js"></script>
<!-- Media insert modal -->
<div class="media-modal" id="mediaModal">
  <div class="media-modal-box">
    <div class="media-modal-head">
      <span style="font-size:14px;font-weight:600;color:var(--ink)" id="mediaModalTitle">Vložit obrázek</span>
      <button type="button" onclick="closeMediaModal()" style="width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--muted);border:1px solid var(--border)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <div class="media-modal-tabs">
      <div class="media-modal-tab on" onclick="switchMediaTab('upload',this)">Z počítače</div>
      <div class="media-modal-tab" onclick="switchMediaTab('gallery',this)">Z galerie</div>
    </div>
    <div class="media-modal-body" id="mediaModalBody">
      <div id="mediaTabUpload">
        <div class="media-modal-dz" id="modalDz" onclick="document.getElementById('modalFileInput').click()">
          <div style="margin-bottom:10px;color:var(--accent-2)"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div style="font-size:13.5px;font-weight:500;color:var(--ink-2);margin-bottom:4px">Přetáhněte soubor nebo klikněte pro výběr</div>
          <div style="font-size:11.5px;color:var(--muted)" id="modalAcceptHint">JPG, PNG, WebP, GIF · max. 8 MB</div>
        </div>
        <input type="file" id="modalFileInput" style="display:none" accept="image/*">
      </div>
      <div id="mediaTabGallery" style="display:none">
        <input type="text" id="mediaGallerySearch" placeholder="Hledat v galerii…" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;color:var(--ink);background:var(--card);margin-bottom:12px;box-sizing:border-box" oninput="filterGallery(this.value)">
        <div class="media-grid" id="mediaGalleryGrid"><div style="color:var(--muted);font-size:13px;padding:8px">Načítám…</div></div>
      </div>
    </div>
    <div style="padding:12px 20px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:8px;flex-shrink:0">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeMediaModal()">Zrušit</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="confirmMediaInsert()" id="mediaInsertBtn" disabled>Vložit</button>
    </div>
  </div>
</div>
<script>
// ── Block format dropdown ────────────────────────────────────────────────────
const blockCycle = ['p','h2','h3'];
let blockIdx = 0;
const blockLabels = {p:'Normální',h2:'Nadpis 2',h3:'Nadpis 3'};
function formatBlock(btn) {
  blockIdx = (blockIdx+1) % blockCycle.length;
  const tag = blockCycle[blockIdx];
  document.execCommand('formatBlock',false,tag);
  document.getElementById('blockLabel').textContent = blockLabels[tag];
}
function insertLink() {
  const url = prompt('URL odkazu:','https://');
  if (url) document.execCommand('createLink',false,url);
}
// ── Font & color ──────────────────────────────────────────────────────────────
let colorRange = null;
function saveColorRange() {
  const sel = window.getSelection();
  if (sel && sel.rangeCount) colorRange = sel.getRangeAt(0).cloneRange();
}
function restoreColorRange() {
  if (!colorRange) return;
  const sel = window.getSelection();
  if (sel) { sel.removeAllRanges(); sel.addRange(colorRange); }
}
function applyFont(font) {
  if (!font) return;
  restoreColorRange();
  document.execCommand('styleWithCSS', false, true);
  document.execCommand('fontName', false, font);
}
function applyFgColor(c) {
  document.getElementById('fgBar').style.background = c;
  restoreColorRange();
  document.execCommand('styleWithCSS', false, true);
  document.execCommand('foreColor', false, c);
}
function applyBgColor(c) {
  document.getElementById('bgBar').style.background = c;
  restoreColorRange();
  document.execCommand('styleWithCSS', false, true);
  document.execCommand('hiliteColor', false, c);
}
// ── Media insert modal ───────────────────────────────────────────────────────
let mediaInsertType = 'image';
let selectedGalleryUrl = null;
let savedRange = null;
function openMediaModal(type) {
  mediaInsertType = type;
  selectedGalleryUrl = null;
  document.getElementById('mediaInsertBtn').disabled = true;
  document.getElementById('mediaModalTitle').textContent = type==='image'?'Vložit obrázek':'Vložit video';
  document.getElementById('modalFileInput').accept = type==='image'?'image/*':'video/*';
  document.getElementById('modalAcceptHint').textContent = type==='image'?'JPG, PNG, WebP, GIF · max. 8 MB':'MP4, WebM · max. 50 MB';
  // Save cursor position
  const sel = window.getSelection();
  if (sel.rangeCount) savedRange = sel.getRangeAt(0).cloneRange();
  document.getElementById('mediaModal').classList.add('on');
  switchMediaTab('upload', document.querySelector('.media-modal-tab'));
}
function closeMediaModal() { document.getElementById('mediaModal').classList.remove('on'); }
function switchMediaTab(tab, el) {
  document.querySelectorAll('.media-modal-tab').forEach(t=>t.classList.remove('on'));
  el.classList.add('on');
  document.getElementById('mediaTabUpload').style.display = tab==='upload'?'':'none';
  document.getElementById('mediaTabGallery').style.display = tab==='gallery'?'':'none';
  if (tab==='gallery') loadGallery();
}
document.getElementById('modalFileInput').addEventListener('change', async function() {
  if (!this.files[0]) return;
  const fd = new FormData();
  fd.append('ajax_action','upload_image');
  fd.append('image', this.files[0]);
  try {
    const r = await fetch(location.href,{method:'POST',body:fd});
    const data = await r.json();
    if (data.success) {
      selectedGalleryUrl = data.url;
      document.getElementById('mediaInsertBtn').disabled = false;
      document.getElementById('modalDz').innerHTML = `<img src="${data.url}" style="max-height:160px;border-radius:8px;max-width:100%;">`;
    }
  } catch(e) {}
});
let galleryItems = [];
async function loadGallery() {
  const grid = document.getElementById('mediaGalleryGrid');
  if (galleryItems.length) { renderGallery(galleryItems); return; }
  grid.innerHTML = '<div style="color:var(--muted);font-size:13px;padding:8px">Načítám…</div>';
  try {
    const r = await fetch('get_media_ajax.php?action=list&limit=60');
    const data = await r.json();
    galleryItems = data.items || data || [];
    renderGallery(galleryItems);
  } catch(e) { grid.innerHTML = '<div style="color:var(--muted);font-size:13px">Chyba načítání galerie.</div>'; }
}
function renderGallery(items) {
  const grid = document.getElementById('mediaGalleryGrid');
  if (!items.length) { grid.innerHTML = '<div style="color:var(--muted);font-size:13px">Galerie je prázdná.</div>'; return; }
  grid.innerHTML = items.filter(it=>it.mime_type&&it.mime_type.startsWith(mediaInsertType==='image'?'image':'video')).map(it=>`
    <div class="media-grid-item" onclick="selectGalleryItem(this,'<?= rtrim(BASE_URL,'/') ?>'+it.path)" data-url="<?= rtrim(BASE_URL,'/') ?>${it.path}">
      <img src="<?= rtrim(BASE_URL,'/') ?>${it.path}" alt="${it.original_name||''}">
    </div>`).join('');
  // Re-render with correct URLs
  grid.innerHTML = items.filter(it=>mediaInsertType==='image'?(it.mime_type||'').startsWith('image'):(it.mime_type||'').startsWith('video')).map(it=>`
    <div class="media-grid-item" onclick="selectGalleryItem(this,'<?= rtrim(BASE_URL,'/') ?>'+encodeURI('${it.path}'))" data-url="${it.path}">
      <img src="<?= rtrim(BASE_URL,'/') ?>${it.path}" alt="" onerror="this.style.display='none'">
    </div>`).join('');
}
function filterGallery(q) {
  const filtered = galleryItems.filter(it=>(it.original_name||it.filename||'').toLowerCase().includes(q.toLowerCase()));
  renderGallery(filtered);
}
function selectGalleryItem(el, url) {
  document.querySelectorAll('.media-grid-item').forEach(i=>i.classList.remove('selected'));
  el.classList.add('selected');
  selectedGalleryUrl = url || el.dataset.url;
  document.getElementById('mediaInsertBtn').disabled = false;
}
function confirmMediaInsert() {
  if (!selectedGalleryUrl) return;
  const ed = document.getElementById('edContent');
  ed.focus();
  if (savedRange) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
  let html;
  const baseUrl = '<?= rtrim(BASE_URL,"/") ?>';
  const fullUrl = selectedGalleryUrl.startsWith('http') ? selectedGalleryUrl : baseUrl + selectedGalleryUrl;
  if (mediaInsertType === 'image') {
    html = `<img src="${fullUrl}" alt="" style="max-width:100%;border-radius:6px;margin:8px 0;">`;
  } else {
    html = `<video src="${fullUrl}" controls style="max-width:100%;border-radius:6px;margin:8px 0;"></video>`;
  }
  document.execCommand('insertHTML', false, html);
  closeMediaModal();
  markUnsaved();
}
document.getElementById('mediaModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeMediaModal(); });
// Drag & drop in modal
const modalDz = document.getElementById('modalDz');
modalDz.addEventListener('dragover', e=>{e.preventDefault();modalDz.style.borderColor='var(--accent)';});
modalDz.addEventListener('dragleave', ()=>modalDz.style.borderColor='');
modalDz.addEventListener('drop', e=>{
  e.preventDefault(); modalDz.style.borderColor='';
  const f = e.dataTransfer.files[0];
  if (f) { const dt = new DataTransfer(); dt.items.add(f); document.getElementById('modalFileInput').files = dt.files; document.getElementById('modalFileInput').dispatchEvent(new Event('change')); }
});
</script>
<script>
function formatBlock(tag) {
  const cycle = ['p', 'h2', 'h3'];
  if (typeof tag !== 'string') {
    const nextIndex = (cycle.indexOf(document.getElementById('blockSelect')?.value || 'p') + 1) % cycle.length;
    tag = cycle[nextIndex];
    if (document.getElementById('blockSelect')) {
      document.getElementById('blockSelect').value = tag;
    }
  }
  if (!tag) return;
  restoreColorRange();
  document.execCommand('formatBlock', false, tag);
}
function applyFont(font) {
  if (!font) return;
  restoreColorRange();
  document.execCommand('styleWithCSS', false, true);
  document.execCommand('fontName', false, font);
  PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
}
function applyFontSize(size) {
  if (!size) return;
  PostEditorUtils.applyFontSize(size, colorRange);
  PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
  updateStats();
  markUnsaved();
}
async function uploadFeaturedImage(file) {
  if (!file || !file.type.startsWith('image/')) return;
  try {
    const data = await PostEditorUtils.uploadImageWithProgress({
      url: location.href,
      file,
      target: document.getElementById('dropzone') || document.getElementById('featPreviewAdd')?.parentElement,
      label: 'Nahrávám hlavní obrázek',
      prepareOptions: { maxDimension: 1600, quality: 0.84 }
    });
    showFeaturedPreview(data.url);
    document.getElementById('galleryImage').value = data.path;
    const featuredInput = document.getElementById('featuredInput');
    if (featuredInput) featuredInput.value = '';
    markUnsaved();
    autoSave();
  } catch(e) {}
}
async function uploadArticleImage(file, range) {
  if (!file || !file.type.startsWith('image/')) return;
  try {
    const data = await PostEditorUtils.uploadImageWithProgress({
      url: location.href,
      file,
      target: document.querySelector('.editor'),
      label: 'Vkládám obrázek do článku',
      prepareOptions: { maxDimension: 2200, quality: 0.82 }
    });
    insertImageIntoEditor(data.url, range);
    updateStats();
    markUnsaved();
    autoSave();
  } catch(e) {}
}
function insertImageIntoEditor(url, range) {
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
function confirmMediaInsert() {
  if (!selectedGalleryUrl) return;
  const ed = document.getElementById('edContent');
  ed.focus();
  if (savedRange) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
  const baseUrl = '<?= rtrim(BASE_URL,"/") ?>';
  const fullUrl = selectedGalleryUrl.startsWith('http') ? selectedGalleryUrl : baseUrl + selectedGalleryUrl;
  const html = mediaInsertType === 'image'
    ? PostEditorUtils.buildResponsiveImageHtml(fullUrl)
    : `<video src="${fullUrl}" controls style="max-width:100%;border-radius:6px;margin:8px 0;"></video>`;
  document.execCommand('insertHTML', false, html);
  PostEditorUtils.normalizeEditorMarkup(ed);
  closeMediaModal();
  markUnsaved();
}
(function rewireModalUpload() {
  const oldInput = document.getElementById('modalFileInput');
  if (!oldInput) return;
  const nextInput = oldInput.cloneNode();
  oldInput.replaceWith(nextInput);
  nextInput.addEventListener('change', async function() {
    if (!this.files[0]) return;
    try {
      const data = await PostEditorUtils.uploadImageWithProgress({
        url: location.href,
        file: this.files[0],
        target: document.getElementById('modalDz'),
        label: 'Nahrávám obrázek z počítače',
        prepareOptions: { maxDimension: 2200, quality: 0.82 }
      });
      selectedGalleryUrl = data.url;
      document.getElementById('mediaInsertBtn').disabled = false;
      document.getElementById('modalDz').innerHTML = `<img src="${data.url}" style="max-height:160px;border-radius:8px;max-width:100%;">`;
    } catch(e) {}
  });
})();
PostEditorUtils.mountImageToolbar({
  editorId: 'edContent',
  onChange: () => {
    updateStats();
    markUnsaved();
    autoSave();
  }
});
PostEditorUtils.initToolbar({
  editorId: 'edContent',
  toolbarSelector: '.ed-toolbar',
  applyBlock: (tag) => formatBlock(tag),
  onChange: () => {
    updateStats();
    markUnsaved();
  }
});
PostEditorUtils.normalizeEditorMarkup(document.getElementById('edContent'));
</script>
</body>
</html>
