<?php
define('BLOG_PRO', true);
require_once '../config.php';

// AJAX upload
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_media') {
    error_reporting(0);
    ob_start();
    header('Content-Type: application/json');
    
    requireAuth();
    
    if (!isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'Žádný soubor']);
        exit;
    }
    
    $upload = new Upload();
    $result = $upload->uploadImage($_FILES['file'], true, true); // saveToMedia = true
    
    if ($result['success']) {
        $media = new Media();
        $stats = $media->getStats();
        echo json_encode([
            'success' => true,
            'message' => 'Soubor nahrán',
            'filename' => $result['filename'],
            'stats' => $stats
        ]);
    } else {
        echo json_encode($result);
    }
    exit;
}

// AJAX delete
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_media') {
    error_reporting(0);
    ob_start();
    header('Content-Type: application/json');
    
    requireAuth();
    
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'Žádné ID']);
        exit;
    }
    
    $media = new Media();
    $result = $media->deleteMultiple($ids);
    
    if ($result['success']) {
        $stats = $media->getStats();
        echo json_encode([
            'success' => true,
            'message' => 'Smazáno ' . $result['deleted'] . ' souborů',
            'stats' => $stats
        ]);
    } else {
        echo json_encode($result);
    }
    exit;
}

requireAuth();

$media = new Media();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? max(6, min(48, intval($_GET['per_page']))) : 12;

$mediaItems = $media->getAll($search, $page, $perPage);
$totalCount = $media->getCount($search);
$totalPages = ceil($totalCount / $perPage);
$stats = $media->getStats();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Knihovna</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <style>
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            background: #f7fafc;
        }
        
        .media-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .media-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .media-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            color: #2d3748;
        }
        
        .media-stats {
            display: flex;
            gap: 30px;
            color: #718096;
            font-size: 14px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .upload-area {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .upload-area:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .upload-area.dragging {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .upload-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .upload-input {
            display: none;
        }
        
        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        
        .toolbar-pagination {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pagination-btn-small {
            width: 36px;
            height: 36px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .pagination-btn-small:hover:not(:disabled) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .pagination-btn-small:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .pagination-info-small {
            color: #718096;
            font-size: 14px;
            min-width: 80px;
            text-align: center;
        }
        
        .per-page-select-small {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-delete {
            padding: 10px 20px;
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }
        
        .btn-delete:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .media-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .media-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .media-item.selected {
            box-shadow: 0 0 0 3px #667eea;
        }
        
        .media-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 24px;
            height: 24px;
            z-index: 10;
            cursor: pointer;
        }
        
        .media-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f7fafc;
        }
        
        .media-info {
            padding: 15px;
        }
        
        .media-name {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #718096;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pagination-btn {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            color: #2d3748;
        }
        
        .pagination-btn:hover:not(:disabled) {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        
        .pagination-info {
            color: #718096;
            font-size: 14px;
            margin: 0 15px;
        }
        
        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 2px solid #e2e8f0;
        }
        
        .per-page-selector select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
        }
        
        /* Delete Modal */
        .delete-modal {
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
            animation: fadeIn 0.3s ease;
        }
        
        .delete-modal.active {
            display: flex;
        }
        
        .delete-modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .delete-modal-content::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #f56565, #e53e3e);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        .delete-modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            background: linear-gradient(135deg, #fed7d7 0%, #fc8181 100%);
        }
        
        .delete-modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .delete-modal-text {
            font-size: 15px;
            color: #718096;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .delete-modal-actions {
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
        
        .modal-btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }
        
        .modal-btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }
        
        .modal-btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .modal-btn-cancel:hover {
            background: #cbd5e0;
        }
        
        /* Upload Preview */
        .upload-previews {
            display: none;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
        }
        
        .upload-previews.active {
            display: grid;
        }
        
        .upload-preview-item {
            position: relative;
            background: #f7fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }
        
        .upload-preview-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .upload-preview-name {
            padding: 8px;
            font-size: 12px;
            color: #4a5568;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .upload-preview-progress {
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .upload-preview-progress.complete {
            width: 100%;
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.8) 0%, rgba(56, 161, 105, 0.8) 100%);
        }
        
        .upload-progress-text {
            color: white;
            font-weight: 600;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .upload-actions {
            display: none;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .upload-actions.active {
            display: flex;
        }
        
        .upload-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .upload-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .upload-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .upload-btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .upload-btn-secondary:hover {
            background: #cbd5e0;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <h1>🖼️ Media Knihovna</h1>
            <nav class="header-nav">
                <a href="dashboard.php" class="header-link">← Dashboard</a>
                <a href="add_post.php" class="header-link">➕ Nový příspěvek</a>
            </nav>
        </div>
    </header>

    <div class="media-container">
        <div class="media-header">
            <h1>📁 Vaše média</h1>
            <div class="media-stats">
                <div class="stat-item">
                    <span>📊</span>
                    <span>Celkem: <strong id="totalCount"><?= $stats['count'] ?></strong> souborů</span>
                </div>
                <div class="stat-item">
                    <span>💾</span>
                    <span>Velikost: <strong id="totalSize"><?= $stats['total_size_formatted'] ?></strong></span>
                </div>
            </div>
        </div>

        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">📤</div>
            <div class="upload-text">Klikněte nebo přetáhněte obrázky</div>
            <div class="upload-hint">Podporované formáty: JPG, PNG, GIF, WebP (max 50MB)</div>
            <input type="file" class="upload-input" id="uploadInput" multiple accept="image/*">
        </div>

        <div class="upload-previews" id="uploadPreviews"></div>
        
        <div class="upload-actions" id="uploadActions">
            <button class="upload-btn upload-btn-primary" onclick="startUpload()">
                📤 Nahrát vybrané soubory
            </button>
            <button class="upload-btn upload-btn-secondary" onclick="cancelUpload()">
                ✖ Zrušit
            </button>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <input type="text" placeholder="🔍 Hledat podle názvu..." id="searchInput" value="<?= e($search) ?>">
            </div>
            
            <div class="toolbar-pagination">
                <button class="pagination-btn-small" <?= $page <= 1 ? 'disabled' : '' ?> onclick="goToPage(<?= $page - 1 ?>)">←</button>
                <span class="pagination-info-small">Str. <?= $page ?>/<?= max(1, $totalPages) ?></span>
                <button class="pagination-btn-small" <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $page + 1 ?>)">→</button>
                <select class="per-page-select-small" onchange="changePerPage(this.value)">
                    <option value="6" <?= $perPage == 6 ? 'selected' : '' ?>>6</option>
                    <option value="12" <?= $perPage == 12 ? 'selected' : '' ?>>12</option>
                    <option value="24" <?= $perPage == 24 ? 'selected' : '' ?>>24</option>
                    <option value="48" <?= $perPage == 48 ? 'selected' : '' ?>>48</option>
                </select>
            </div>
            
            <div class="toolbar-actions">
                <button class="btn-delete" id="deleteBtn" onclick="openDeleteModal()" disabled>
                    🗑️ Smazat (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>

        <div class="media-grid" id="mediaGrid">
            <?php if (empty($mediaItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🖼️</div>
                    <h3>Žádná média</h3>
                    <p>Nahrajte první obrázky pomocí formuláře výše</p>
                </div>
            <?php else: ?>
                <?php foreach ($mediaItems as $item): ?>
                    <div class="media-item" data-id="<?= $item['id'] ?>">
                        <input type="checkbox" class="media-checkbox" onchange="updateSelection()">
                        <img src="<?= BASE_URL . $item['path'] ?>" class="media-preview" alt="<?= e($item['original_name']) ?>">
                        <div class="media-info">
                            <div class="media-name" title="<?= e($item['original_name']) ?>">
                                <?= e($item['original_name']) ?>
                            </div>
                            <div class="media-meta">
                                <span><?= $item['width'] ?>×<?= $item['height'] ?></span>
                                <span><?= number_format($item['size'] / 1024, 0) ?> KB</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">⚠️</div>
            <h2 class="delete-modal-title">Smazat vybrané obrázky?</h2>
            <p class="delete-modal-text" id="deleteModalText">
                Opravdu chcete smazat <strong id="deleteCount">0</strong> obrázků? Tato akce je nevratná.
            </p>
            <div class="delete-modal-actions">
                <button class="modal-btn modal-btn-danger" onclick="confirmDelete()">
                    ✓ Smazat
                </button>
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    Zrušit
                </button>
            </div>
        </div>
    </div>

    <script>
        // Upload area
        const uploadArea = document.getElementById('uploadArea');
        const uploadInput = document.getElementById('uploadInput');
        const uploadPreviews = document.getElementById('uploadPreviews');
        const uploadActions = document.getElementById('uploadActions');
        let selectedFiles = [];
        
        uploadArea.addEventListener('click', () => uploadInput.click());
        
        // Drag & drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragging');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragging');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragging');
            handleFiles(e.dataTransfer.files);
        });
        
        uploadInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        // Handle files - zobrazit preview
        function handleFiles(files) {
            selectedFiles = Array.from(files);
            uploadPreviews.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                uploadPreviews.classList.remove('active');
                uploadActions.classList.remove('active');
                return;
            }
            
            uploadPreviews.classList.add('active');
            uploadActions.classList.add('active');
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'upload-preview-item';
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="upload-preview-image" alt="${file.name}">
                        <div class="upload-preview-name">${file.name}</div>
                        <div class="upload-preview-progress" id="progress-${index}">
                            <span class="upload-progress-text">Čeká...</span>
                        </div>
                    `;
                    uploadPreviews.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }
        
        // Start upload
        async function startUpload() {
            const uploadBtn = document.querySelector('.upload-btn-primary');
            uploadBtn.disabled = true;
            uploadBtn.textContent = '⏳ Nahrávám...';
            
            for (let i = 0; i < selectedFiles.length; i++) {
                const file = selectedFiles[i];
                const progressBar = document.getElementById(`progress-${i}`);
                const progressText = progressBar.querySelector('.upload-progress-text');
                
                progressText.textContent = 'Nahrávám...';
                
                const formData = new FormData();
                formData.append('ajax_action', 'upload_media');
                formData.append('file', file);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        progressBar.classList.add('complete');
                        progressText.textContent = '✓ Hotovo';
                    } else {
                        progressText.textContent = '✗ Chyba';
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    progressText.textContent = '✗ Chyba';
                }
            }
            
            // Po 2 sekundách reload
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
        
        // Cancel upload
        function cancelUpload() {
            selectedFiles = [];
            uploadPreviews.innerHTML = '';
            uploadPreviews.classList.remove('active');
            uploadActions.classList.remove('active');
            uploadInput.value = '';
        }
        
        // ====================================
        // KLIKNUTÍ NA OBRÁZEK = TOGGLE CHECKBOX
        // ====================================
        document.addEventListener('click', function(e) {
            // Pokud kliknuto přímo na checkbox, nedělat nic navíc
            if (e.target.classList.contains('media-checkbox')) {
                updateSelection();
                return;
            }
            
            const mediaItem = e.target.closest('.media-item');
            if (mediaItem) {
                e.preventDefault();
                const checkbox = mediaItem.querySelector('.media-checkbox');
                checkbox.checked = !checkbox.checked;
                updateSelection();
            }
        });
        
        // Selection
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.media-checkbox:checked');
            const count = checkboxes.length;
            
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('deleteBtn').disabled = count === 0;
            
            // Vizuální označení
            document.querySelectorAll('.media-item').forEach(item => {
                const checkbox = item.querySelector('.media-checkbox');
                if (checkbox.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        }
        
        // ====================================
        // DELETE MODAL
        // ====================================
        function openDeleteModal() {
            const checkboxes = document.querySelectorAll('.media-checkbox:checked');
            const count = checkboxes.length;
            
            document.getElementById('deleteCount').textContent = count;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        async function confirmDelete() {
            const checkboxes = document.querySelectorAll('.media-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => {
                return cb.closest('.media-item').dataset.id;
            });
            
            try {
                const formData = new FormData();
                formData.append('ajax_action', 'delete_media');
                ids.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Chyba: ' + result.message);
                    closeDeleteModal();
                }
            } catch (error) {
                console.error('Delete error:', error);
                closeDeleteModal();
            }
        }
        
        // Zavřít modal kliknutím mimo nebo ESC
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
        
        // ====================================
        // LIVE SEARCH (okamžité vyhledávání BEZ RELOAD)
        // ====================================
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const mediaItems = document.querySelectorAll('.media-item');
            
            mediaItems.forEach(item => {
                const name = item.querySelector('.media-name').textContent.toLowerCase();
                if (name.includes(search)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // ====================================
        // PAGINATION
        // ====================================
        function goToPage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = '?' + params.toString();
        }
        
        function changePerPage(perPage) {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', perPage);
            params.delete('page'); // Reset na stránku 1
            window.location.href = '?' + params.toString();
        }
    </script>
</body>
</html>
