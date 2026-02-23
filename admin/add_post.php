<?php
define('BLOG_PRO', true);
require_once '../config.php';

// Pro AJAX požadavky vypnout HTML output a chyby
if (isset($_POST['ajax_action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_start(); // Zachytit jakýkoliv output
}

requireAuth();

$post = new Post();
$category = new Category();
$upload = new Upload();
$categories = $category->getAll();

$error = '';
$success = '';

// Zpracování AJAX požadavků
if (isset($_POST['ajax_action'])) {
    ob_clean(); // Vyčistit buffer
    header('Content-Type: application/json');
    
    try {
        // AUTO-SAVE
        if ($_POST['ajax_action'] === 'autosave_draft') {
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            
            // Pokud je úplně prázdné, neukládat
            if (empty($title) && empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Prázdný obsah']);
                exit;
            }
            
            $data = [
                'title' => $title ?: 'Bez názvu',
                'content' => $content,
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'author_id' => $_SESSION['user_id'],
                'status' => 'draft',
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'meta_keywords' => $_POST['meta_keywords'] ?? ''
            ];
            
            $draftId = !empty($_POST['draft_id']) ? intval($_POST['draft_id']) : null;
            
            if ($draftId) {
                // Aktualizovat existující koncept
                $result = $post->update($draftId, $data);
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'draft_id' => $draftId,
                        'time' => date('H:i'),
                        'message' => 'Koncept aktualizován'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Chyba při ukládání']);
                }
            } else {
                // Vytvořit nový koncept
                $result = $post->create($data);
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'draft_id' => $result['id'],
                        'time' => date('H:i'),
                        'message' => 'Koncept vytvořen'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Chyba při vytváření']);
                }
            }
            exit;
        }
        
        if ($_POST['ajax_action'] === 'add_category') {
            $name = trim($_POST['category_name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Název kategorie nesmí být prázdný']);
                exit;
            }
            
            $db = new Database();
            
            // Vytvořit slug
            $slug = mb_strtolower($name, 'UTF-8');
            $slug = strtr($slug, [
                'á' => 'a', 'č' => 'c', 'ď' => 'd',
                'é' => 'e', 'ě' => 'e', 'í' => 'i',
                'ň' => 'n', 'ó' => 'o', 'ř' => 'r',
                'š' => 's', 'ť' => 't', 'ú' => 'u',
                'ů' => 'u', 'ý' => 'y', 'ž' => 'z'
            ]);
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-');
            
            // Zkontrolovat duplicitu
            $db->query("SELECT id FROM categories WHERE slug = :slug");
            $db->bind(':slug', $slug);
            if ($db->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Kategorie s tímto názvem již existuje']);
                exit;
            }
            
            $db->query("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
            $db->bind(':name', $name);
            $db->bind(':slug', $slug);
            
            if ($db->execute()) {
                $id = $db->lastInsertId();
                echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'slug' => $slug]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nepodařilo se vytvořit kategorii']);
            }
            exit;
        }
        
        if ($_POST['ajax_action'] === 'delete_category') {
            $id = intval($_POST['category_id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Neplatné ID kategorie']);
                exit;
            }
            
            $db = new Database();
            
            // Zkontrolovat jestli nemá příspěvky
            $db->query("SELECT COUNT(*) as count FROM posts WHERE category_id = :id");
            $db->bind(':id', $id);
            $result = $db->fetch();
            
            if ($result && $result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Nelze smazat kategorii - obsahuje ' . $result['count'] . ' příspěvků']);
                exit;
            }
            
            $db->query("DELETE FROM categories WHERE id = :id");
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nepodařilo se smazat kategorii']);
            }
            exit;
        }
        
        // Neznámá akce
        echo json_encode(['success' => false, 'message' => 'Neznámá akce']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
        exit;
    }
}

// Zpracování formuláře příspěvku
// Zpracování formuláře příspěvku
if (isPost() && !isset($_POST['ajax_action'])) {
    if (!verifyCsrf()) {
        $error = 'Neplatný CSRF token';
    } else {
        $data = [
            'title' => post('title'),
            'content' => post('content'),
            'excerpt' => post('excerpt'),
            'category_id' => post('category_id') ?: null,
            'author_id' => $_SESSION['user_id'],
            'status' => post('status', 'published'),
            'meta_title' => post('meta_title'),
            'meta_description' => post('meta_description'),
            'meta_keywords' => post('meta_keywords')
        ];
        
        // Zpracování plánovaného publikování
        if (post('publish_type') === 'scheduled' && post('scheduled_date') && post('scheduled_time')) {
            $scheduledDate = post('scheduled_date');
            $scheduledTime = post('scheduled_time');
            $data['scheduled_at'] = $scheduledDate . ' ' . $scheduledTime . ':00';
            $data['status'] = 'scheduled';
        } else {
            $data['scheduled_at'] = null;
        }
        
        // Featured image - z uploadu NEBO z galerie
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $upload->uploadImage($_FILES['featured_image'], true, true);
            if ($uploadResult['success']) {
                $data['featured_image'] = $uploadResult['path'];
            }
        } elseif (!empty($_POST['featured_image_from_gallery'])) {
            $data['featured_image'] = $_POST['featured_image_from_gallery'];
        }
        
        // KONTROLA: Existuje draft_id? Pokud ano, UPDATE místo CREATE!
        $draftId = !empty($_POST['draft_id']) ? intval($_POST['draft_id']) : null;
        
        if ($draftId) {
            // Aktualizovat existující koncept (změní status na published)
            $result = $post->update($draftId, $data);
            $successMessage = 'Příspěvek byl publikován!';
        } else {
            // Vytvořit nový příspěvek
            $result = $post->create($data);
            $successMessage = 'Příspěvek byl vytvořen!';
        }
        
        if ($result['success']) {
            setFlash('success', $successMessage);
            redirect(ADMIN_URL . 'dashboard.php');
        } else {
            $error = $result['message'] ?? 'Nepodařilo se vytvořit příspěvek';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nový příspěvek</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        .main-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* Levý panel kategorií */
        .categories-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #f7fafc 0%, #edf2f7 100%);
            border-right: 2px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .categories-header {
            padding: 20px;
            background: white;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .categories-header h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .category-actions {
            display: flex;
            gap: 8px;
        }
        
        .category-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .category-btn-add {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .category-btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
        }
        
        .category-btn-delete {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }
        
        .category-btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }
        
        .category-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .categories-list {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .category-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .category-item:hover {
            border-color: #cbd5e0;
            transform: translateX(4px);
        }
        
        .category-item.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            transform: translateX(8px) scale(1.02);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .category-item.selected::before {
            content: "✓";
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            font-size: 18px;
        }
        
        .category-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .category-none {
            font-style: italic;
            opacity: 0.7;
        }
        
        /* Hlavní obsah */
        .main-content {
            flex: 1;
            overflow-y: auto;
            background: #f7fafc;
        }
        
        .content-inner {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }
        
        /* Fullscreen modal */
        .fullscreen-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fullscreen-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        .modal-content::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #48bb78, #38a169);
        }
        
        .modal-content.delete::before {
            background: linear-gradient(90deg, #f56565, #e53e3e);
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            animation: iconPulse 1.5s infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .modal-content.delete .modal-icon {
            background: linear-gradient(135deg, #fed7d7 0%, #fc8181 100%);
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .modal-description {
            font-size: 15px;
            color: #718096;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 25px;
            transition: border-color 0.2s;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
        }
        
        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
        }
        
        .modal-btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }
        
        .modal-btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }
        
        .modal-btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .modal-btn-secondary:hover {
            background: #cbd5e0;
        }
        
        /* Tooltips */
        .tooltip-container {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }
        
        .tooltip-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            font-size: 13px;
            font-weight: bold;
            cursor: help;
            transition: transform 0.2s;
        }
        
        .tooltip-icon:hover {
            transform: scale(1.1);
            background: #5568d3;
        }
        
        .tooltip-text {
            visibility: hidden;
            width: 280px;
            max-width: calc(100vw - 40px);
            background-color: #2d3748;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 14px;
            position: absolute;
            z-index: 10000;
            left: 30px;
            top: -10px;
            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
            font-size: 13px;
            line-height: 1.6;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .tooltip-text::before {
            content: "";
            position: absolute;
            left: -8px;
            top: 15px;
            border-width: 8px;
            border-style: solid;
            border-color: transparent #2d3748 transparent transparent;
        }
        
        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Povinná pole */
        .required-field label {
            font-weight: 600;
            color: #2d3748;
        }
        
        .required-field label::after {
            content: " *";
            color: #e53e3e;
            font-weight: bold;
            font-size: 16px;
        }
        
        .required-field input,
        .required-field #editor {
            border: 2px solid #e2e8f0;
            transition: border-color 0.2s;
        }
        
        .required-field input:focus,
        .required-field #editor:focus-within {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .required-field input:invalid {
            border-color: #fc8181;
        }
        
        /* Rozšířený Quill toolbar */
        .ql-toolbar {
            background: #f7fafc;
            border: 2px solid #e2e8f0 !important;
            border-bottom: none !important;
            border-radius: 8px 8px 0 0;
            padding: 12px !important;
        }
        
        .ql-container {
            border: 2px solid #e2e8f0 !important;
            border-radius: 0 0 8px 8px;
            font-size: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        }
        
        .ql-editor {
            min-height: 400px;
            padding: 20px;
        }
        
        /* Quill toolbar tlačítka */
        .ql-toolbar button:hover,
        .ql-toolbar .ql-picker-label:hover {
            color: #667eea !important;
        }
        
        .ql-toolbar button.ql-active,
        .ql-toolbar .ql-picker-label.ql-active {
            color: #667eea !important;
        }
        
        .ql-toolbar .ql-stroke {
            stroke: #4a5568;
        }
        
        .ql-toolbar button:hover .ql-stroke,
        .ql-toolbar button.ql-active .ql-stroke {
            stroke: #667eea;
        }
        
        .ql-toolbar .ql-fill {
            fill: #4a5568;
        }
        
        .ql-toolbar button:hover .ql-fill,
        .ql-toolbar button.ql-active .ql-fill {
            fill: #667eea;
        }
        
        /* Vlastní fonty v editoru */
        .ql-font-arial { font-family: Arial, sans-serif; }
        .ql-font-georgia { font-family: Georgia, serif; }
        .ql-font-impact { font-family: Impact, sans-serif; }
        .ql-font-courier { font-family: 'Courier New', monospace; }
        .ql-font-verdana { font-family: Verdana, sans-serif; }
        .ql-font-times-new-roman { font-family: 'Times New Roman', serif; }
        .ql-font-comic-sans { font-family: 'Comic Sans MS', cursive; }
        .ql-font-trebuchet { font-family: 'Trebuchet MS', sans-serif; }
        .ql-font-palatino { font-family: 'Palatino Linotype', serif; }
        .ql-font-garamond { font-family: Garamond, serif; }
        
        /* Font picker labels */
        .ql-picker.ql-font .ql-picker-label[data-value="arial"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="arial"]::before {
            content: 'Arial';
            font-family: Arial, sans-serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
            content: 'Georgia';
            font-family: Georgia, serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="impact"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="impact"]::before {
            content: 'Impact';
            font-family: Impact, sans-serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="courier"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="courier"]::before {
            content: 'Courier';
            font-family: 'Courier New', monospace;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="verdana"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="verdana"]::before {
            content: 'Verdana';
            font-family: Verdana, sans-serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="times-new-roman"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="times-new-roman"]::before {
            content: 'Times New Roman';
            font-family: 'Times New Roman', serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="comic-sans"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="comic-sans"]::before {
            content: 'Comic Sans';
            font-family: 'Comic Sans MS', cursive;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="trebuchet"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="trebuchet"]::before {
            content: 'Trebuchet';
            font-family: 'Trebuchet MS', sans-serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="palatino"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="palatino"]::before {
            content: 'Palatino';
            font-family: 'Palatino Linotype', serif;
        }
        .ql-picker.ql-font .ql-picker-label[data-value="garamond"]::before,
        .ql-picker.ql-font .ql-picker-item[data-value="garamond"]::before {
            content: 'Garamond';
            font-family: Garamond, serif;
        }
        
        /* Velikosti textu */
        .ql-size-10px { font-size: 10px; }
        .ql-size-12px { font-size: 12px; }
        .ql-size-14px { font-size: 14px; }
        .ql-size-16px { font-size: 16px; }
        .ql-size-18px { font-size: 18px; }
        .ql-size-20px { font-size: 20px; }
        .ql-size-24px { font-size: 24px; }
        .ql-size-32px { font-size: 32px; }
        .ql-size-48px { font-size: 48px; }
        .ql-size-64px { font-size: 64px; }
        
        /* Náhled modal */
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .preview-modal.active {
            display: flex;
        }
        
        .preview-container {
            width: 90%;
            max-width: 1200px;
            height: 90%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .preview-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .preview-close {
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .preview-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .preview-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px 60px;
            background: #f7fafc;
        }
        
        .preview-article {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 60px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .preview-article h1 {
            font-size: 42px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 20px 0;
            line-height: 1.2;
        }
        
        .preview-article-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .preview-article-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .preview-featured-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 40px;
        }
        
        .preview-article-content {
            font-size: 18px;
            line-height: 1.8;
            color: #2d3748;
        }
        
        .preview-article-content p {
            margin-bottom: 20px;
        }
        
        .preview-article-content h2 {
            font-size: 32px;
            font-weight: 600;
            margin: 40px 0 20px 0;
            color: #1a202c;
        }
        
        .preview-article-content h3 {
            font-size: 24px;
            font-weight: 600;
            margin: 30px 0 15px 0;
            color: #2d3748;
        }
        
        .preview-article-content ul,
        .preview-article-content ol {
            margin: 20px 0;
            padding-left: 30px;
        }
        
        .preview-article-content li {
            margin-bottom: 10px;
        }
        
        .preview-article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .preview-article-content blockquote {
            border-left: 4px solid #667eea;
            padding-left: 20px;
            margin: 30px 0;
            font-style: italic;
            color: #4a5568;
        }
        
        /* Auto-save status */
        .autosave-status {
            position: fixed;
            top: 80px;
            right: 30px;
            background: white;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(66, 153, 225, 0.25);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #4299e1;
        }
        
        .autosave-status.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .autosave-status.saving {
            background: #ebf4ff;
            border-color: #4299e1;
        }
        
        .autosave-status.saving .autosave-text {
            color: #2b6cb0;
        }
        
        .autosave-status.saved {
            background: #ebf4ff;
            border-color: #4299e1;
        }
        
        .autosave-status.saved .autosave-text {
            color: #2b6cb0;
        }
        
        .autosave-icon {
            font-size: 20px;
            color: #4299e1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .autosave-status.saving .sync-icon {
            animation: smoothRotate 1.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        
        .autosave-text {
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Trvalý auto-save indikátor v headeru */
        .autosave-permanent {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #ebf4ff;
            border-radius: 20px;
            border: 2px solid #4299e1;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .autosave-permanent .save-icon {
            font-size: 16px;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: 20px;
            color: #4299e1;
        }
        
        /* SVG ikona pro šipky v kruhu */
        .sync-icon {
            width: 16px;
            height: 16px;
            transition: opacity 0.3s;
        }
        
        .sync-icon path {
            stroke: currentColor;
            fill: none;
        }
        
        .autosave-permanent .save-text {
            font-weight: 500;
            color: #2b6cb0;
        }
        
        /* Animace pro ukládání - točí se jen SVG! */
        .autosave-permanent.saving .sync-icon {
            animation: smoothRotate 1.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        
        @keyframes smoothRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Počítadlo slov */
        .word-counter {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            border: 2px solid #e2e8f0;
        }
        
        .counter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .counter-icon {
            font-size: 18px;
        }
        
        .counter-label {
            font-size: 13px;
            color: #718096;
        }
        
        .counter-value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }
        
        /* Upload zóny */
        .upload-zone {
            border: 3px dashed #cbd5e0;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s;
            background: #f7fafc;
        }
        .upload-zone:hover {
            border-color: #4299e1;
            background: #ebf4ff;
        }
        .upload-zone.drag-over {
            border-color: #4299e1;
            background: linear-gradient(135deg, #ebf4ff 0%, #e0e7ff 100%);
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(66, 153, 225, 0.3);
        }
        .upload-zone-icon { font-size: 48px; margin-bottom: 15px; }
        .upload-zone-title { font-size: 18px; font-weight: 600; color: #2d3748; margin-bottom: 10px; }
        .upload-zone-text { color: #718096; margin-bottom: 20px; }
        .upload-zone-buttons { display: flex; gap: 12px; justify-content: center; }
        .upload-btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .upload-btn-primary { background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); color: white; }
        .upload-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4); }
        .upload-btn-gallery { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .upload-btn-gallery:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4); }
        
        #dragOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); z-index: 9998; display: none; }
        #dragOverlay.active { display: block; }
        .upload-zone.drag-active, .ql-container.drag-active { position: relative; z-index: 9999; }
        .upload-zone.has-image .upload-zone-content { display: none; }
        .upload-zone.has-image { padding: 0; border: none; background: transparent; }
        
        .ql-container.drag-over-editor { position: relative; z-index: 10000; }
        .ql-container.drag-over-editor::after {
            content: "📸 Pusťte pro vložení do článku";
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(66, 153, 225, 0.95); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 600; border-radius: 8px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 0.95; } 50% { opacity: 1; } }
        
        /* Plánované publikování */
        .publish-schedule {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .schedule-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .schedule-option {
            flex: 1;
            cursor: pointer;
        }
        
        .schedule-option input[type="radio"] {
            display: none;
        }
        
        .schedule-option .option-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .schedule-option input[type="radio"]:checked + .option-label {
            border-color: #667eea;
            background: linear-gradient(135deg, #ebf4ff 0%, #e0e7ff 100%);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .schedule-option .option-icon {
            font-size: 24px;
        }
        
        .schedule-option .option-text {
            font-weight: 500;
            color: #2d3748;
        }
        
        .schedule-datetime {
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
        }
        
        /* AI Generování tlačítko */
        .ai-generate-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .ai-generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .ai-generate-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .ai-generate-btn .ai-icon {
            font-size: 18px;
            animation: sparkle 2s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <!-- Auto-save status -->
    <div class="autosave-status" id="autosaveStatus">
        <span class="autosave-icon">💾</span>
        <span class="autosave-text">Ukládání...</span>
    </div>
    
    <header class="admin-header">
        <div class="header-content">
            <h1>➕ Nový příspěvek</h1>
            <nav class="header-nav">
                <!-- Trvalý auto-save status -->
                <div class="autosave-permanent" id="autosavePermanent">
                    <span class="save-icon">⚪</span>
                    <span class="save-text">Neuloženo</span>
                </div>
                <a href="dashboard.php" class="header-link">← Dashboard</a>
            </nav>
        </div>
    </header>

    <div class="main-wrapper">
        <!-- Levý panel kategorií -->
        <div class="categories-sidebar">
            <div class="categories-header">
                <h3>📁 Kategorie</h3>
                <div class="category-actions">
                    <button class="category-btn category-btn-add" onclick="openAddCategoryModal()">
                        <span>➕</span> Přidat
                    </button>
                    <button class="category-btn category-btn-delete" id="deleteCategoryBtn" onclick="openDeleteCategoryModal()" disabled>
                        <span>➖</span> Smazat
                    </button>
                </div>
            </div>
            
            <div class="categories-list">
                <div class="category-item" data-id="" onclick="selectCategory('')">
                    <div class="category-name category-none">Bez kategorie</div>
                </div>
                <?php foreach ($categories as $cat): ?>
                <div class="category-item" data-id="<?= $cat['id'] ?>" onclick="selectCategory(<?= $cat['id'] ?>)">
                    <div class="category-name"><?= e($cat['name']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hlavní obsah -->
        <div class="main-content">
            <div class="content-inner">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <div class="card">
                    <form method="POST" enctype="multipart/form-data" id="postForm">
                        <?= Security::tokenInput() ?>
                        <input type="hidden" name="category_id" id="categoryInput" value="">
                        <input type="hidden" name="draft_id" id="draftIdInput" value="">

                        <div class="form-group required-field">
                            <label>Titulek</label>
                            <input type="text" name="title" required value="<?= e(post('title')) ?>" style="font-size: 18px; padding: 12px;">
                        </div>

                        <div class="form-group required-field">
                            <label>Obsah</label>
                            <div id="editor" style="height: 400px; border-radius: 8px;"></div>
                            <input type="hidden" name="content" id="content">
                            
                            <!-- Počítadlo slov -->
                            <div class="word-counter">
                                <div class="counter-item">
                                    <span class="counter-icon">📝</span>
                                    <div>
                                        <div class="counter-label">Slov</div>
                                        <div class="counter-value" id="wordCount">0</div>
                                    </div>
                                </div>
                                
                                <div class="counter-item">
                                    <span class="counter-icon">🔤</span>
                                    <div>
                                        <div class="counter-label">Znaků</div>
                                        <div class="counter-value" id="charCount">0</div>
                                    </div>
                                </div>
                                
                                <div class="counter-item">
                                    <span class="counter-icon">📖</span>
                                    <div>
                                        <div class="counter-label">Čtení</div>
                                        <div class="counter-value" id="readTime">0 min</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Hlavní obrázek</label>
                                
                                <div class="upload-zone" id="featuredDropZone">
                                    <div class="upload-zone-content" id="uploadZoneContent">
                                        <div class="upload-zone-icon">🖼️</div>
                                        <div class="upload-zone-title">Hlavní obrázek příspěvku</div>
                                        <div class="upload-zone-text">Přetáhněte obrázek sem nebo</div>
                                        <div class="upload-zone-buttons">
                                            <button type="button" class="upload-btn upload-btn-primary" onclick="document.getElementById('featuredImageInput').click()">
                                                📁 Vybrat soubor
                                            </button>
                                            <button type="button" class="upload-btn upload-btn-gallery" onclick="openMediaGallery()">
                                                🖼️ Z galerie
                                            </button>
                                        </div>
                                    </div>
                                    <div id="selectedImagePreview" style="display: none; position: relative;">
                                        <img src="" id="selectedImagePreviewImg" style="width: 100%; border-radius: 8px;">
                                        <button type="button" onclick="clearSelectedImage()" style="position: absolute; top: 10px; right: 10px; padding: 8px 16px; background: rgba(252,129,129,0.95); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                            ✕ Odebrat
                                        </button>
                                    </div>
                                    <input type="file" name="featured_image" id="featuredImageInput" accept="image/*" style="display: none;">
                                    <input type="hidden" name="featured_image_from_gallery" id="featuredImageFromGallery">
                                </div>
                                
                                <small style="color: #718096; display: block; margin-top: 10px;">Doporučená velikost: 1920x1080px</small>
                            </div>
                        </div>

                        <h3 style="margin: 40px 0 20px; font-size: 20px;">🔍 SEO Nastavení</h3>
                        
                        <button type="button" class="ai-generate-btn" onclick="generateSEO()" id="aiGenerateBtn">
                            <span class="ai-icon">🤖</span>
                            <span>Generovat SEO automaticky</span>
                        </button>
                        
                        <div class="form-group">
                            <label>
                                SEO Title
                                <span class="tooltip-container">
                                    <span class="tooltip-icon">?</span>
                                    <span class="tooltip-text">
                                        <strong>Co to je:</strong> Titulek který se zobrazí ve výsledcích Google.<br><br>
                                        <strong>Tip:</strong> Měl by mít 50-60 znaků a obsahovat klíčové slovo.<br><br>
                                        <strong>Příklad:</strong> "Jak pěstovat rajčata na balkóně - 5 tipů pro začátečníky"
                                    </span>
                                </span>
                            </label>
                            <input type="text" name="meta_title" value="<?= e(post('meta_title')) ?>" 
                                   placeholder="Ponechte prázdné pro použití titulku příspěvku">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                Meta Description
                                <span class="tooltip-container">
                                    <span class="tooltip-icon">?</span>
                                    <span class="tooltip-text">
                                        <strong>Co to je:</strong> Krátký popis který se zobrazí pod titulkem v Google.<br><br>
                                        <strong>Tip:</strong> 150-160 znaků, popisuje o čem článek je a láká ke kliknutí.<br><br>
                                        <strong>Příklad:</strong> "Naučte se pěstovat rajčata na balkóně bez zahrady. Praktické tipy pro výběr odrůdy, zalévání a péči."
                                    </span>
                                </span>
                            </label>
                            <textarea name="meta_description" rows="3" placeholder="Krátký popis článku pro vyhledávače..."><?= e(post('meta_description')) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                Klíčová slova
                                <span class="tooltip-container">
                                    <span class="tooltip-icon">?</span>
                                    <span class="tooltip-text">
                                        <strong>Co to je:</strong> Slova která lidé píšou do Googlu když hledají váš obsah.<br><br>
                                        <strong>Tip:</strong> 3-5 slov oddělených čárkou.<br><br>
                                        <strong>Příklad:</strong> "pěstování rajčat, balkón, tipy, péče"
                                    </span>
                                </span>
                            </label>
                            <input type="text" name="meta_keywords" id="metaKeywords" value="<?= e(post('meta_keywords')) ?>" 
                                   placeholder="klíčové slovo 1, klíčové slovo 2, ...">
                        </div>

                        <!-- Excerpt - automaticky generován -->
                        <input type="hidden" name="excerpt" value="">

                        <div style="display: flex; gap: 15px; margin-top: 40px; align-items: center;">
                            <button type="button" class="btn" onclick="openPreview()" style="padding: 14px 28px; font-size: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                                👁️ Náhled
                            </button>
                            
                            <div style="flex: 1; display: flex; gap: 15px;">
                                <button type="submit" name="status" value="published" class="btn btn-primary" style="flex: 1; padding: 16px 32px; font-size: 17px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);">
                                    ✅ Publikovat příspěvek
                                </button>
                                <button type="submit" name="status" value="draft" class="btn" style="padding: 16px 32px; font-size: 17px; background: linear-gradient(135deg, #a0aec0 0%, #718096 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                                    📝 Uložit jako koncept
                                </button>
                            </div>
                            
                            <a href="dashboard.php" class="btn btn-secondary" style="padding: 14px 28px; font-size: 16px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s;">
                                ✖ Zrušit
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pro přidání kategorie -->
    <div class="fullscreen-modal" id="addCategoryModal">
        <div class="modal-content">
            <div class="modal-icon">➕</div>
            <h2 class="modal-title">Přidat novou kategorii</h2>
            <p class="modal-description">Zadejte název nové kategorie pro váš blog</p>
            <input type="text" class="modal-input" id="newCategoryName" placeholder="Název kategorie...">
            <div class="modal-actions">
                <button class="modal-btn modal-btn-primary" onclick="addCategory()">
                    ✓ Přidat
                </button>
                <button class="modal-btn modal-btn-secondary" onclick="closeModal()">
                    Zrušit
                </button>
            </div>
        </div>
    </div>

    <!-- Modal pro smazání kategorie -->
    <div class="fullscreen-modal" id="deleteCategoryModal">
        <div class="modal-content delete">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">Smazat kategorii?</h2>
            <p class="modal-description" id="deleteCategoryText"></p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-danger" onclick="deleteCategory()">
                    ✓ Smazat
                </button>
                <button class="modal-btn modal-btn-secondary" onclick="closeModal()">
                    Zrušit
                </button>
            </div>
        </div>
    </div>

    <!-- Media Galerie Modal -->
    <div class="fullscreen-modal" id="mediaGalleryModal">
        <div class="modal-content" style="max-width: 1000px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-icon">🖼️</div>
            <h2 class="modal-title">Media Knihovna</h2>
            <input type="text" id="gallerySearch" placeholder="🔍 Hledat..." style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px;">
            <div id="galleryGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; overflow-y: auto; flex: 1; max-height: 500px;">
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #718096;">Načítám...</div>
            </div>
            <div id="galleryPagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 2px solid #e2e8f0;"></div>
            <div class="modal-actions" style="margin-top: 15px;">
                <button class="modal-btn modal-btn-secondary" onclick="closeMediaGallery()">Zavřít</button>
            </div>
        </div>
    </div>

    <!-- Náhled příspěvku -->
    <div class="preview-modal" id="previewModal">
        <div class="preview-container">
            <div class="preview-header">
                <div class="preview-title">
                    👁️ Náhled příspěvku
                </div>
                <button class="preview-close" onclick="closePreview()">✕</button>
            </div>
            <div class="preview-content">
                <article class="preview-article" id="previewArticle">
                    <!-- Obsah bude vložen JavaScriptem -->
                </article>
            </div>
        </div>
    </div>
    
    <div id="dragOverlay"></div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        let selectedCategoryId = '';
        
        // Registrace vlastních fontů
        var Font = Quill.import('formats/font');
        Font.whitelist = [
            'arial', 'georgia', 'impact', 'courier', 'verdana', 
            'times-new-roman', 'comic-sans', 'trebuchet', 'palatino', 'garamond'
        ];
        Quill.register(Font, true);
        
        // Registrace velikostí
        var Size = Quill.import('attributors/style/size');
        Size.whitelist = ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '32px', '48px', '64px'];
        Quill.register(Size, true);
        
        // Inicializace Quill editoru s rozšířenými funkcemi
        var quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Začněte psát váš úžasný obsah...',
            modules: {
                toolbar: {
                    container: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'font': Font.whitelist }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'script': 'sub'}, { 'script': 'super' }],
                        [{ 'align': [] }, { 'align': 'center' }, { 'align': 'right' }, { 'align': 'justify' }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image', 'video'],
                        ['clean']
                    ],
                    handlers: {
                        image: function() {
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.accept = 'image/*';
                            input.onchange = async () => {
                                const file = input.files[0];
                                const formData = new FormData();
                                formData.append('ajax_action', 'upload_media');
                                formData.append('file', file);
                                const res = await fetch('media.php', {method: 'POST', body: formData});
                                const data = await res.json();
                                if(data.success) {
                                    const range = this.quill.getSelection();
                                    this.quill.insertEmbed(range.index, 'image', '<?= BASE_URL ?>uploads/' + data.filename);
                                }
                            };
                            input.click();
                        }
                    }
                }
            }
        });
        
        // Při odeslání formuláře
        document.getElementById('postForm').onsubmit = function() {
            document.getElementById('content').value = quill.root.innerHTML;
            return true;
        };
        
        // ============================================
        // AUTO-SAVE & POČÍTADLO SLOV
        // ============================================
        
        let autoSaveInterval;
        let typingTimer;
        let lastSavedContent = '';
        let isDraft = false;
        let draftId = null;
        let isSubmitting = false;  // ← NOVÝ FLAG pro blokování auto-save
        
        const AUTOSAVE_INTERVAL = 30000; // 30 sekund (běžný interval)
        const TYPING_DELAY = 1000; // 1 sekunda po zastavení psaní
        
        // Počítadlo slov - update při psaní
        quill.on('text-change', function() {
            updateWordCount();
            
            // Označit jako neuloženo při změně
            const currentContent = document.querySelector('input[name="title"]').value + quill.root.innerHTML;
            if (currentContent !== lastSavedContent) {
                updatePermanentStatus('unsaved');
            }
            
            // SMART SAVE: Resetovat timer při psaní
            clearTimeout(typingTimer);
            
            // Po 1 sekundě nečinnosti - uložit (BEZ POPUP)
            typingTimer = setTimeout(() => {
                console.log('Uživatel přestal psát - ukládám (tichý save)...');
                autoSave(false); // false = bez popup
            }, TYPING_DELAY);
        });
        
        // Sledovat změny v titulku
        document.querySelector('input[name="title"]').addEventListener('input', function() {
            const currentContent = this.value + quill.root.innerHTML;
            if (currentContent !== lastSavedContent) {
                updatePermanentStatus('unsaved');
            }
            
            // SMART SAVE: Také pro titulek
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                console.log('Uživatel přestal psát titulek - ukládám (tichý save)...');
                autoSave(false); // false = bez popup
            }, TYPING_DELAY);
        });
        
        function updateWordCount() {
            const text = quill.getText().trim();
            
            // Počet slov
            const words = text.length > 0 ? text.split(/\s+/).length : 0;
            document.getElementById('wordCount').textContent = words;
            
            // Počet znaků (bez mezer)
            const chars = text.replace(/\s/g, '').length;
            document.getElementById('charCount').textContent = chars.toLocaleString();
            
            // Čas čtení (průměrně 200 slov/min)
            const readTime = Math.ceil(words / 200) || 0;
            const readTimeText = readTime === 1 ? '1 min' : readTime + ' min';
            document.getElementById('readTime').textContent = readTimeText;
        }
        
        // Spustit auto-save každých 30 sekund
        function startAutoSave() {
            autoSaveInterval = setInterval(() => {
                console.log('Pravidelný auto-save (30s interval) - s popup...');
                autoSave(true); // true = s popup
            }, AUTOSAVE_INTERVAL);
            console.log('Auto-save spuštěno: 30s interval (s popup) + 1s po zastavení (bez popup)');
        }
        
        // Auto-save funkce
        async function autoSave(showPopup = true) {
            // KONTROLA: Pokud probíhá submit, NEUKLÁDAT!
            if (isSubmitting) {
                console.log('⛔ Auto-save BLOKOVÁN - probíhá submit!');
                return;
            }
            
            const title = document.querySelector('input[name="title"]').value;
            const content = quill.root.innerHTML;
            
            // Neprázdný obsah nebo titulek
            if (!title && !content) {
                return;
            }
            
            // Pokud se nezměnilo, neukládat
            const currentContent = title + content;
            if (currentContent === lastSavedContent) {
                return;
            }
            
            // Zobrazit "Ukládání..." (popup jen pokud showPopup = true)
            if (showPopup) {
                showAutoSaveStatus('saving');
            }
            updatePermanentStatus('saving');
            
            try {
                // Připravit data
                const formData = new FormData();
                formData.append('ajax_action', 'autosave_draft');
                formData.append('title', title);
                formData.append('content', content);
                formData.append('category_id', document.getElementById('categoryInput').value);
                formData.append('meta_title', document.querySelector('input[name="meta_title"]').value);
                formData.append('meta_description', document.querySelector('textarea[name="meta_description"]').value);
                formData.append('meta_keywords', document.querySelector('input[name="meta_keywords"]').value);
                
                if (draftId) {
                    formData.append('draft_id', draftId);
                }
                
                // Odeslat AJAX
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    lastSavedContent = currentContent;
                    if (result.draft_id) {
                        draftId = result.draft_id;
                        isDraft = true;
                        // Uložit draft_id do formuláře!
                        document.getElementById('draftIdInput').value = result.draft_id;
                        console.log('✅ Draft ID uloženo:', result.draft_id);
                    }
                    
                    // Zobrazit "Uloženo" (popup jen pokud showPopup = true)
                    if (showPopup) {
                        showAutoSaveStatus('saved', result.time);
                    }
                    updatePermanentStatus('saved', result.time);
                    
                    console.log('Auto-save úspěšný:', result);
                } else {
                    updatePermanentStatus('unsaved');
                    console.error('Auto-save chyba:', result.message);
                }
            } catch (error) {
                updatePermanentStatus('unsaved');
                console.error('Auto-save error:', error);
            }
        }
        
        // Aktualizovat trvalý status indikátor
        function updatePermanentStatus(state, time) {
            const permanent = document.getElementById('autosavePermanent');
            const icon = permanent.querySelector('.save-icon');
            const text = permanent.querySelector('.save-text');
            
            // Odstranit všechny třídy
            permanent.classList.remove('unsaved', 'saving', 'saved');
            
            // Tenké šipky v kruhu (stroke-width: 1.5)
            const arrowSVG = `<svg class="sync-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6m0 0h-6m6 0l-3.5-3.5a9 9 0 1 0 2.5 6.5"/>
                <path d="M2.5 22v-6m0 0h6m-6 0l3.5 3.5a9 9 0 1 1-2.5-6.5"/>
            </svg>`;
            
            if (state === 'unsaved') {
                permanent.classList.add('unsaved');
                icon.innerHTML = `<span style="display: flex; align-items: center; gap: 2px;">☁️</span>`;
                text.textContent = 'Neuloženo';
            } else if (state === 'saving') {
                permanent.classList.add('saving');
                icon.innerHTML = `<span style="display: flex; align-items: center; gap: 3px;">${arrowSVG}<span style="font-size: 16px;">☁️</span></span>`;
                text.textContent = 'Ukládání...';
            } else if (state === 'saved') {
                permanent.classList.add('saved');
                icon.innerHTML = `<span style="display: flex; align-items: center; gap: 2px;">☁️</span>`;
                text.textContent = 'Uloženo';
            }
        }
        
        // Zobrazit auto-save status
        function showAutoSaveStatus(type, time) {
            const status = document.getElementById('autosaveStatus');
            const icon = status.querySelector('.autosave-icon');
            const text = status.querySelector('.autosave-text');
            
            status.classList.remove('saving', 'saved');
            
            const arrowSVG = `<svg class="sync-icon" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6m0 0h-6m6 0l-3.5-3.5a9 9 0 1 0 2.5 6.5"/>
                <path d="M2.5 22v-6m0 0h6m-6 0l3.5 3.5a9 9 0 1 1-2.5-6.5"/>
            </svg>`;
            
            if (type === 'saving') {
                status.classList.add('saving');
                icon.innerHTML = `<span style="display: flex; align-items: center; gap: 4px;">${arrowSVG}<span style="font-size: 18px;">☁️</span></span>`;
                text.textContent = 'Ukládání do cloudu...';
            } else if (type === 'saved') {
                status.classList.add('saved');
                icon.innerHTML = `<span style="display: flex; align-items: center; gap: 4px;"><span style="font-size: 18px;">☁️</span></span>`;
                text.textContent = 'Uloženo v cloudu';
            }
            
            // Zobrazit
            status.classList.add('show');
            
            // Skrýt po 3 sekundách
            setTimeout(() => {
                status.classList.remove('show');
            }, 3000);
        }
        
        // Spustit auto-save po načtení stránky
        setTimeout(() => {
            startAutoSave();
            updateWordCount(); // Iniciální počítadlo
        }, 1000);
        
        // Před zavřením stránky - varování pokud není uloženo
        window.addEventListener('beforeunload', function(e) {
            const title = document.querySelector('input[name="title"]').value;
            const content = quill.getText().trim();
            
            if ((title || content) && !isDraft) {
                e.preventDefault();
                e.returnValue = 'Máte neuložené změny. Opravdu chcete opustit stránku?';
                return e.returnValue;
            }
        });
        
        // ============================================
        // KATEGORIE (původní kód)
        // ============================================
        
        // Výběr kategorie
        function selectCategory(id) {
            selectedCategoryId = id;
            document.getElementById('categoryInput').value = id;
            
            // Vizuální označení
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            const selectedItem = document.querySelector(`.category-item[data-id="${id}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
            }
            
            // Povolit/zakázat tlačítko smazat
            document.getElementById('deleteCategoryBtn').disabled = !id;
        }
        
        // Otevřít modal přidání
        function openAddCategoryModal() {
            document.getElementById('addCategoryModal').classList.add('active');
            document.getElementById('newCategoryName').value = '';
            setTimeout(() => document.getElementById('newCategoryName').focus(), 100);
        }
        
        // Otevřít modal smazání
        function openDeleteCategoryModal() {
            if (!selectedCategoryId) return;
            
            const selectedItem = document.querySelector(`.category-item[data-id="${selectedCategoryId}"]`);
            const categoryName = selectedItem.querySelector('.category-name').textContent;
            
            document.getElementById('deleteCategoryText').textContent = 
                `Opravdu chcete smazat kategorii "${categoryName}"? Tato akce je nevratná.`;
            
            document.getElementById('deleteCategoryModal').classList.add('active');
        }
        
        // Zavřít modaly
        function closeModal() {
            document.querySelectorAll('.fullscreen-modal').forEach(modal => {
                modal.classList.remove('active');
            });
        }
        
        // Přidat kategorii (AJAX)
        async function addCategory() {
            const name = document.getElementById('newCategoryName').value.trim();
            if (!name) {
                alert('Zadejte název kategorie');
                return;
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax_action=add_category&category_name=${encodeURIComponent(name)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Přidat do seznamu
                    const newItem = document.createElement('div');
                    newItem.className = 'category-item';
                    newItem.setAttribute('data-id', result.id);
                    newItem.onclick = () => selectCategory(result.id);
                    newItem.innerHTML = `<div class="category-name">${escapeHtml(result.name)}</div>`;
                    
                    document.querySelector('.categories-list').appendChild(newItem);
                    
                    // Automaticky vybrat
                    selectCategory(result.id);
                    
                    closeModal();
                } else {
                    alert(result.message || 'Chyba při vytváření kategorie');
                }
            } catch (error) {
                alert('Chyba: ' + error.message);
            }
        }
        
        // Smazat kategorii (AJAX)
        async function deleteCategory() {
            if (!selectedCategoryId) return;
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax_action=delete_category&category_id=${selectedCategoryId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Odstranit ze seznamu
                    const item = document.querySelector(`.category-item[data-id="${selectedCategoryId}"]`);
                    if (item) {
                        item.remove();
                    }
                    
                    // Vybrat "Bez kategorie"
                    selectCategory('');
                    
                    closeModal();
                } else {
                    alert(result.message || 'Chyba při mazání kategorie');
                }
            } catch (error) {
                alert('Chyba: ' + error.message);
            }
        }
        
        // Helper funkce
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Zavření modalu při kliku mimo
        document.querySelectorAll('.fullscreen-modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
        
        // Enter v inputu = odeslat
        document.getElementById('newCategoryName').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                addCategory();
            }
        });
        
        // ESC = zavřít modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                closePreview();
            }
        });
        
        // ============================================
        // NÁHLED PŘÍSPĚVKU
        // ============================================
        
        function openPreview() {
            const title = document.querySelector('input[name="title"]').value || 'Bez názvu';
            const content = quill.root.innerHTML;
            const categoryId = document.getElementById('categoryInput').value;
            
            // Najít název kategorie
            let categoryName = '';
            if (categoryId) {
                const categoryItem = document.querySelector(`.category-item[data-id="${categoryId}"]`);
                if (categoryItem) {
                    categoryName = categoryItem.querySelector('.category-name').textContent;
                }
            }
            
            // Počet slov pro čas čtení
            const text = quill.getText().trim();
            const words = text.length > 0 ? text.split(/\s+/).length : 0;
            const readTime = Math.ceil(words / 200) || 1;
            
            // Sestavit náhled
            let previewHTML = `
                <h1>${escapeHtml(title)}</h1>
                <div class="preview-article-meta">
                    <span>📅 ${getCurrentDate()}</span>
                    ${categoryName ? `<span>📁 ${escapeHtml(categoryName)}</span>` : ''}
                    <span>📖 ${readTime} min čtení</span>
                    <span>📝 ${words} slov</span>
                </div>
            `;
            
            // Přidat featured image pokud existuje
            const fileInput = document.querySelector('input[name="featured_image"]');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgHTML = `<img src="${e.target.result}" class="preview-featured-image" alt="${escapeHtml(title)}">`;
                    document.getElementById('previewArticle').innerHTML = previewHTML + imgHTML + `<div class="preview-article-content">${content}</div>`;
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                previewHTML += `<div class="preview-article-content">${content}</div>`;
                document.getElementById('previewArticle').innerHTML = previewHTML;
            }
            
            // Zobrazit modal
            document.getElementById('previewModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closePreview() {
            document.getElementById('previewModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function getCurrentDate() {
            const now = new Date();
            const day = now.getDate();
            const month = now.getMonth() + 1;
            const year = now.getFullYear();
            return `${day}.${month}.${year}`;
        }
        
        // Zavřít náhled kliknutím mimo
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
        
        // ============================================
        // PLÁNOVANÉ PUBLIKOVÁNÍ
        // ============================================
        
        function toggleSchedule() {
            const isScheduled = document.querySelector('input[name="publish_type"]:checked').value === 'scheduled';
            const scheduleDatetime = document.getElementById('scheduleDatetime');
            const statusSelect = document.getElementById('postStatus');
            
            if (isScheduled) {
                scheduleDatetime.style.display = 'block';
                statusSelect.value = 'scheduled';
                
                // Aktuální datum a čas
                const now = new Date();
                const dateStr = now.toISOString().split('T')[0];
                const timeStr = now.toTimeString().substring(0, 5);
                
                document.getElementById('scheduledDate').value = dateStr;
                document.getElementById('scheduledTime').value = timeStr;
            } else {
                scheduleDatetime.style.display = 'none';
                statusSelect.value = 'published';
            }
        }
        
        // ============================================
        // CHYTRÉ GENEROVÁNÍ SEO (bez API)
        // ============================================
        
        function generateSEO() {
            const title = document.querySelector('input[name="title"]').value.trim();
            const content = quill.getText().trim();
            
            if (!title || content.length < 50) {
                alert('❌ Nejdříve napište titulek a nějaký obsah článku (minimálně 50 znaků)');
                return;
            }
            
            const btn = document.getElementById('aiGenerateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-icon">⏳</span><span>Generuji...</span>';
            
            // Simulace načítání (vypadá to profesionálně)
            setTimeout(() => {
                // 1. SEO TITLE (50-60 znaků)
                let seoTitle = title;
                if (seoTitle.length > 60) {
                    seoTitle = seoTitle.substring(0, 57) + '...';
                } else if (seoTitle.length < 50) {
                    // Přidat rok nebo krátký popisek
                    const year = new Date().getFullYear();
                    if ((seoTitle + ' - ' + year).length <= 60) {
                        seoTitle = seoTitle + ' - ' + year;
                    }
                }
                
                // 2. META DESCRIPTION (150-160 znaků)
                let metaDesc = content
                    .replace(/\n+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .substring(0, 157);
                
                // Ukončit na poslední celé slovo
                const lastSpace = metaDesc.lastIndexOf(' ');
                if (lastSpace > 100) {
                    metaDesc = metaDesc.substring(0, lastSpace);
                }
                metaDesc += '...';
                
                // 3. KEYWORDS (nejčastější slova z článku)
                const keywords = extractKeywords(title + ' ' + content);
                
                // Vyplnit formulář
                document.querySelector('input[name="meta_title"]').value = seoTitle;
                document.querySelector('textarea[name="meta_description"]').value = metaDesc;
                document.getElementById('metaKeywords').value = keywords;
                
                btn.disabled = false;
                btn.innerHTML = '<span class="ai-icon">✅</span><span>Vygenerováno! Klikněte pro nové generování</span>';
                
                setTimeout(() => {
                    btn.innerHTML = '<span class="ai-icon">🤖</span><span>Generovat SEO automaticky</span>';
                }, 3000);
                
            }, 1500); // 1.5s delay pro realistický efekt
        }
        
        // Extrakce klíčových slov
        function extractKeywords(text) {
            // Odstranit diakritiku a převést na malá
            const normalized = text.toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
            
            // Rozdělit na slova
            const words = normalized
                .replace(/[^\w\s]/g, ' ')
                .split(/\s+/)
                .filter(word => word.length > 3); // Min 4 znaky
            
            // Stopwords (ignorovat časté slova)
            const stopwords = ['jsou', 'jsme', 'který', 'která', 'které', 'tento', 'tato', 'toto', 
                              'také', 'nebo', 'ale', 'jeho', 'její', 'jejich', 'moje', 'tvoje',
                              'jako', 'více', 'méně', 'když', 'byla', 'bylo', 'byly', 'mohl',
                              'může', 'musí', 'můžeme', 'with', 'that', 'this', 'from', 'have'];
            
            // Spočítat četnost
            const frequency = {};
            words.forEach(word => {
                if (!stopwords.includes(word)) {
                    frequency[word] = (frequency[word] || 0) + 1;
                }
            });
            
            // Seřadit podle četnosti
            const sorted = Object.entries(frequency)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 7) // Top 7 slov
                .map(entry => entry[0]);
            
            return sorted.join(', ');
        }
        
        // ============================================
        // EXCERPT - AUTOMATICKÉ GENEROVÁNÍ
        // ============================================
        
        // Při odeslání formuláře automaticky vygenerovat excerpt
       // PŘIDEJ DO submit handleru v add_post.php (řádek ~2129)

        document.getElementById('postForm').addEventListener('submit', function(e) {
        // NASTAVIT FLAG - blokuj auto-save!
        isSubmitting = true;
        console.log('🛑 Submit začíná - auto-save BLOKOVÁN!');
        
        // Zastavit interval
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
            console.log('⏹️ Auto-save interval VYPNUT!');
        }
        
        // Nastavit obsah z editoru
        document.getElementById('content').value = quill.root.innerHTML;
            // Automaticky vygenerovat excerpt pokud není vyplněn
            const excerptField = document.querySelector('input[name="excerpt"]');
            if (!excerptField.value || excerptField.value.trim() === '') {
                const content = quill.getText().trim();
                if (content.length > 0) {
                    let excerpt = content.substring(0, 160);
                    const lastSpace = excerpt.lastIndexOf(' ');
                    if (lastSpace > 0) {
                        excerpt = excerpt.substring(0, lastSpace);
                    }
                    excerpt += '...';
                    excerptField.value = excerpt;
                }
            }
        });
        
        // ==========================================
        // MEDIA GALERIE (FIXED)
        // ==========================================
        let currentGallerySearch = '';
        
        async function openMediaGallery(page = 1) {
            document.getElementById('mediaGalleryModal').classList.add('active');
            await loadGallery(page, currentGallerySearch);
            
            // Připojit search listener po otevření modalu
            const searchInput = document.getElementById('gallerySearch');
            if(searchInput && !searchInput.dataset.listenerAttached) {
                searchInput.dataset.listenerAttached = 'true';
                searchInput.addEventListener('input', function(e) {
                    currentGallerySearch = e.target.value;
                    console.log('🔎 Search:', currentGallerySearch);
                    loadGallery(1, currentGallerySearch);
                });
            }
        }
        
        async function loadGallery(page, search = '') {
            console.log('🔍 Loading gallery - Page:', page, 'Search:', search);
            const res = await fetch(`get_media_ajax.php?page=${page}&per_page=9&search=${encodeURIComponent(search)}`);
            const data = await res.json();
            console.log('📦 Gallery data:', data);
            console.log('📊 Items count:', data.items ? data.items.length : 0);
            console.log('📋 Items:', data.items);
            console.log('🐛 DEBUG INFO:', data.debug);
            const grid = document.getElementById('galleryGrid');
            const pagination = document.getElementById('galleryPagination');
            
            if(!grid) {
                console.error('❌ galleryGrid element not found!');
                return;
            }
            
            if(!data.items || data.items.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #718096;">Žádné obrázky</div>';
                pagination.innerHTML = '';
                return;
            }
            
            grid.innerHTML = data.items.map(item => `
                <div onclick="selectImageFromGallery('${item.path}')" style="cursor: pointer; border: 3px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.05)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)'">
                    <img src="<?= BASE_URL ?>${item.path}" style="width: 100%; height: 200px; object-fit: cover;">
                    <div style="padding: 10px; font-size: 12px; color: #4a5568; text-align: center; background: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.original_name}</div>
                </div>
            `).join('');
            
            console.log('✅ Grid HTML updated, items rendered:', data.items.length);
            
            const totalPages = Math.ceil(data.total / 9);
            if(totalPages > 1) {
                pagination.innerHTML = `
                    <button onclick="loadGallery(${page - 1}, currentGallerySearch)" ${page <= 1 ? 'disabled' : ''} style="padding: 8px 16px; border: 2px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">← Předchozí</button>
                    <span style="padding: 8px 16px; color: #718096;">Stránka ${page} z ${totalPages}</span>
                    <button onclick="loadGallery(${page + 1}, currentGallerySearch)" ${page >= totalPages ? 'disabled' : ''} style="padding: 8px 16px; border: 2px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">Další →</button>
                `;
            } else {
                pagination.innerHTML = '';
            }
        }
        
        function closeMediaGallery() {
            document.getElementById('mediaGalleryModal').classList.remove('active');
            currentGallerySearch = '';
            document.getElementById('gallerySearch').value = '';
        }
        
        function selectImageFromGallery(path) {
            document.getElementById('featuredImageFromGallery').value = path;
            document.getElementById('uploadZoneContent').style.display = 'none';
            document.getElementById('selectedImagePreview').style.display = 'block';
            document.getElementById('selectedImagePreviewImg').src = '<?= BASE_URL ?>' + path;
            featuredZone.classList.add('has-image');
            document.getElementById('featuredImageInput').value = '';
            closeMediaGallery();
        }
        
        function clearSelectedImage() {
            document.getElementById('featuredImageFromGallery').value = '';
            document.getElementById('uploadZoneContent').style.display = 'block';
            document.getElementById('selectedImagePreview').style.display = 'none';
            featuredZone.classList.remove('has-image');
            document.getElementById('featuredImageInput').value = '';
        }
        
        // ==========================================
        // DRAG & DROP - DEFINITIVNÍ FIX
        // ==========================================
        const featuredZone = document.getElementById('featuredDropZone');
        const editorContainer = document.querySelector('.ql-container');
        const overlay = document.getElementById('dragOverlay');
        let isDragging = false;
        
        // Detekce začátku drag
        window.addEventListener('dragenter', (e) => {
            if(!isDragging && e.dataTransfer.types.includes('Files')) {
                isDragging = true;
                overlay.classList.add('active');
                featuredZone.classList.add('drag-active');
                editorContainer.classList.add('drag-active');
            }
        }, true);
        
        // Detekce konce drag (opuštění okna)
        window.addEventListener('dragleave', (e) => {
            if(e.target === document.documentElement || e.clientX <= 0 || e.clientY <= 0) {
                isDragging = false;
                overlay.classList.remove('active');
                featuredZone.classList.remove('drag-active');
                editorContainer.classList.remove('drag-active');
                featuredZone.classList.remove('drag-over');
                editorContainer.classList.remove('drag-over-editor');
            }
        }, true);
        
        // Drop nebo escape
        window.addEventListener('drop', () => {
            isDragging = false;
            overlay.classList.remove('active');
            featuredZone.classList.remove('drag-active');
            editorContainer.classList.remove('drag-active');
            featuredZone.classList.remove('drag-over');
            editorContainer.classList.remove('drag-over-editor');
        }, true);
        
        // Featured zone hover
        featuredZone.addEventListener('dragenter', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if(isDragging) featuredZone.classList.add('drag-over');
        });
        featuredZone.addEventListener('dragleave', (e) => {
            if(!featuredZone.contains(e.relatedTarget)) {
                featuredZone.classList.remove('drag-over');
            }
        });
        featuredZone.addEventListener('dragover', (e) => { e.preventDefault(); e.stopPropagation(); });
        featuredZone.addEventListener('drop', handleFeaturedDrop);
        
        // Editor hover
        editorContainer.addEventListener('dragenter', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if(isDragging) editorContainer.classList.add('drag-over-editor');
        });
        editorContainer.addEventListener('dragleave', (e) => {
            if(!editorContainer.contains(e.relatedTarget)) {
                editorContainer.classList.remove('drag-over-editor');
            }
        });
        editorContainer.addEventListener('dragover', (e) => { e.preventDefault(); e.stopPropagation(); });
        editorContainer.addEventListener('drop', handleEditorDrop);
        
        document.getElementById('featuredImageInput').addEventListener('change', function(e) {
            if(e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('uploadZoneContent').style.display = 'none';
                    document.getElementById('selectedImagePreview').style.display = 'block';
                    document.getElementById('selectedImagePreviewImg').src = e.target.result;
                    featuredZone.classList.add('has-image');
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        async function handleFeaturedDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            const file = e.dataTransfer.files[0];
            if(!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('uploadZoneContent').style.display = 'none';
                document.getElementById('selectedImagePreview').style.display = 'block';
                document.getElementById('selectedImagePreviewImg').src = e.target.result;
                featuredZone.classList.add('has-image');
            };
            reader.readAsDataURL(file);
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('featuredImageInput').files = dt.files;
        }
        
        async function handleEditorDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            const file = e.dataTransfer.files[0];
            if(!file || !file.type.startsWith('image/')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'upload_media');
            formData.append('file', file);
            const res = await fetch('media.php', {method: 'POST', body: formData});
            const data = await res.json();
            if(data.success) {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', '<?= BASE_URL ?>uploads/' + data.filename);
            }
        }
    </script>
</body>
</html>