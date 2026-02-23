<?php
define('BLOG_PRO', true);
require_once '../config.php';
requireAuth();

$auth = new Auth();
$post = new Post();
$category = new Category();
$media = new Media();

// Filtry
$filter = $_GET['filter'] ?? 'all'; // all, published, draft
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;

// Statistiky
$totalPublishedCount = $post->count('published');
$totalDraftsCount = $post->count('draft');
$totalMedia = $media->getCount('');

// Získání příspěvků podle filtru
if ($filter === 'draft') {
    $allPosts = $post->getAll('draft', $perPage, ($page - 1) * $perPage, $categoryFilter);
    $totalCount = $post->count('draft', $categoryFilter);
} elseif ($filter === 'published') {
    $allPosts = $post->getAll('published', $perPage, ($page - 1) * $perPage, $categoryFilter);
    $totalCount = $post->count('published', $categoryFilter);
} else {
    // All - spojíme published + draft
    $publishedPostsArray = $post->getAll('published', 100, 0, $categoryFilter); // Načteme všechny
    $totalDraftsCountArray = $post->getAll('draft', 100, 0, $categoryFilter);
    
    // Sloučíme a seřadíme podle created_at
    $allPostsCombined = array_merge($publishedPostsArray, $totalDraftsCountArray);
    usort($allPostsCombined, function($a, $b) {
        $timeA = strtotime($a['published_at'] ?? $a['created_at']);
        $timeB = strtotime($b['published_at'] ?? $b['created_at']);
        return $timeB - $timeA; // Nejnovější první
    });
    
    // Pagination na kombinovaných výsledcích
    $totalCount = count($allPostsCombined);
    $allPosts = array_slice($allPostsCombined, ($page - 1) * $perPage, $perPage);
}

$categories = $category->getAll();
$totalPages = ceil($totalCount / $perPage);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Blog Pro Admin</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <style>
        /* Moderní statistiky */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; gap: 20px; align-items: center; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .stat-card.green { border-left: 4px solid #48bb78; }
        .stat-card.blue { border-left: 4px solid #4299e1; }
        .stat-card.purple { border-left: 4px solid #9f7aea; }
        .stat-card.orange { border-left: 4px solid #ed8936; }
        .stat-icon { font-size: 40px; }
        .stat-info h3 { margin: 0 0 5px; font-size: 14px; color: #718096; font-weight: 500; }
        .stat-value { font-size: 32px; font-weight: 700; color: #2d3748; }
        
        /* Card header */
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .card-header h2 { margin: 0; }
        
        /* Filter tabs */
        .filter-tabs { display: flex; gap: 10px; }
        .tab { padding: 10px 20px; border-radius: 8px; text-decoration: none; color: #4a5568; font-weight: 600; background: #f7fafc; transition: all 0.2s; }
        .tab:hover { background: #e2e8f0; }
        .tab.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        
        /* Category filters */
        .category-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px; padding: 20px; background: #f7fafc; border-radius: 8px; }
        .category-badge { padding: 8px 16px; border-radius: 20px; text-decoration: none; color: #4a5568; background: white; border: 2px solid #e2e8f0; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .category-badge:hover { border-color: #667eea; color: #667eea; }
        .category-badge.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-color: transparent; }
        
        /* Pagination */
        .pagination, .pagination-top { display: flex; justify-content: center; align-items: center; gap: 10px; padding: 20px 0; }
        .pagination-top { margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .pagination { margin-top: 30px; border-top: 2px solid #e2e8f0; }
        .page-btn { padding: 10px 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s; font-size: 14px; }
        .page-btn:hover:not(.disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .page-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
        .page-info { color: #718096; font-weight: 600; padding: 0 10px; }
        
        /* Delete Modal */
        .delete-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .delete-modal.active { display: flex; animation: fadeIn 0.3s; }
        .delete-modal-content { background: white; padding: 40px; border-radius: 16px; max-width: 500px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .delete-modal-icon { font-size: 80px; margin-bottom: 20px; }
        .delete-modal-title { font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 10px; }
        .delete-modal-text { color: #718096; margin-bottom: 30px; font-size: 16px; }
        .delete-modal-actions { display: flex; gap: 15px; justify-content: center; }
        .modal-btn { padding: 14px 32px; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.2s; }
        .modal-btn-danger { background: linear-gradient(135deg, #fc8181, #f56565); color: white; }
        .modal-btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4); }
        .modal-btn-cancel { background: #e2e8f0; color: #4a5568; }
        .modal-btn-cancel:hover { background: #cbd5e0; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        /* Quick Search */
        .quick-search-bar { margin-bottom: 30px; }
        .search-container { position: relative; max-width: 600px; margin: 0 auto; }
        #quickSearch { width: 100%; padding: 16px 50px 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; transition: all 0.3s; background: white; }
        #quickSearch:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e2e8f0; border-radius: 12px; margin-top: 8px; max-height: 400px; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.1); display: none; z-index: 1000; }
        .search-results.active { display: block; animation: slideDown 0.2s; }
        .search-result-item { padding: 16px 20px; border-bottom: 1px solid #f7fafc; cursor: pointer; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
        .search-result-item:hover { background: #f7fafc; transform: translateX(4px); }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-content { flex: 1; }
        .search-result-title { font-weight: 600; color: #2d3748; margin-bottom: 4px; }
        .search-result-meta { font-size: 13px; color: #718096; }
        .search-result-badge { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-right: 8px; }
        .search-result-badge.published { background: #c6f6d5; color: #22543d; }
        .search-result-badge.draft { background: #fef5e7; color: #744210; }
        .search-no-results { padding: 30px; text-align: center; color: #a0aec0; font-size: 14px; }
        .search-loading { padding: 20px; text-align: center; color: #667eea; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Bulk Actions - dva řádky */
        .bulk-actions-row { display: flex; align-items: center; gap: 15px; padding: 20px; background: #f7fafc; border-radius: 8px; margin-top: 15px; flex-wrap: wrap; }
        .select-all-label { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: white; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .select-all-label:hover { border-color: #667eea; transform: translateY(-1px); }
        .bulk-actions-buttons { display: none; align-items: center; gap: 10px; flex: 1; }
        .bulk-actions-buttons.active { display: flex; animation: slideIn 0.3s; }
        .bulk-count { font-weight: 700; color: #667eea; font-size: 15px; }
        .bulk-btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 14px; }
        .bulk-btn-delete { background: linear-gradient(135deg, #fc8181, #f56565); color: white; }
        .bulk-btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,101,101,0.4); }
        .bulk-btn-publish { background: linear-gradient(135deg, #48bb78, #38a169); color: white; }
        .bulk-btn-publish:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(72,187,120,0.4); }
        .bulk-btn-cancel { padding: 10px 16px; background: #e2e8f0; color: #4a5568; }
        .bulk-btn-cancel:hover { background: #cbd5e0; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        
        /* Reorder Mode */
        #dragOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1; pointer-events: none; }
        body.reorder-mode #dragOverlay { display: block; }
        
        /* Blur specifické elementy - NE posts-grid */
        body.reorder-mode header,
        body.reorder-mode .quick-search-bar,
        body.reorder-mode .stats-grid,
        body.reorder-mode .filter-tabs,
        body.reorder-mode .category-filters,
        body.reorder-mode .bulk-actions-row,
        body.reorder-mode .pagination,
        body.reorder-mode .pagination-top {
            filter: blur(4px);
            opacity: 0.5;
        }
        
        .post-card { position: relative; transition: all 0.3s; }
        
        /* Posts grid - OSTRÉ, nad overlay */
        body.reorder-mode .posts-grid {
            position: relative;
            z-index: 10;
            filter: none !important;
        }
        
        body.reorder-mode .post-card { 
            outline: 4px dashed #667eea; 
            outline-offset: 8px; 
            transition: all 0.2s; 
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            filter: none !important;
        }
        
        /* Clickable overlay přes každou kartu */
        body.reorder-mode .post-card::after {
            content: '';
            position: absolute;
            top: -15px;
            left: -15px;
            right: -15px;
            bottom: -15px;
            background: transparent;
            z-index: 10;
            cursor: pointer;
        }
        
        body.reorder-mode .post-card.reorder-source::after {
            cursor: not-allowed;
        }
        
        body.reorder-mode .post-card.reorder-source { 
            outline-color: #fc8181; 
            outline-width: 5px; 
            outline-style: solid; 
            transform: scale(1.05); 
            box-shadow: 0 15px 40px rgba(252,129,129,0.4); 
            z-index: 10000;
            background: linear-gradient(135deg, rgba(252,129,129,0.1), rgba(252,129,129,0.05));
        }
        
        body.reorder-mode .post-card.reorder-target { 
            outline-color: #48bb78 !important; 
            outline-style: solid !important; 
            outline-width: 6px !important; 
            transform: scale(1.03); 
            z-index: 100;
            box-shadow: 0 8px 30px rgba(72,187,120,0.3);
        }
        
        body.reorder-mode .post-card.reorder-target::after {
            background: rgba(72,187,120,0.15);
        }
        
        .btn-reorder { background: linear-gradient(135deg, #9f7aea, #805ad5); color: white; }
        .btn-reorder:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(159,122,234,0.4); }
        
        /* Reorder Instructions */
        .reorder-instructions { position: fixed; top: 100px; left: 50%; transform: translateX(-50%); background: white; padding: 25px 35px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); z-index: 10001; display: none; max-width: 500px; }
        .reorder-instructions.active { display: block; animation: slideDown 0.3s; }
        .reorder-instructions h3 { margin: 0 0 15px; color: #667eea; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .reorder-instructions p { margin: 8px 0; color: #4a5568; line-height: 1.6; }
        .reorder-instructions .legend { display: flex; gap: 20px; margin: 15px 0; }
        .reorder-instructions .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .reorder-instructions .legend-box { width: 30px; height: 20px; border-radius: 4px; }
        .reorder-instructions button { margin-top: 15px; padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .reorder-instructions button:hover { background: #cbd5e0; }
        
        .post-checkbox { position: absolute; top: 15px; right: 15px; width: 22px; height: 22px; cursor: pointer; z-index: 10; accent-color: #667eea; }
        
        /* Header improvements */
        .header-link.primary { background: linear-gradient(135deg, #48bb78, #38a169); padding: 10px 20px; border-radius: 8px; color: white !important; font-weight: 600; }
        .header-link.user { color: #667eea; font-weight: 600; }
        .header-link.logout { color: #fc8181; }
        
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <h1>📊 Dashboard</h1>
            <nav class="header-nav">
                <a href="add_post.php" class="header-link primary">✏️ Nový článek</a>
                <a href="media.php" class="header-link">🖼️ Galerie</a>
                <a href="settings.php" class="header-link">⚙️ Nastavení</a>
                <span class="header-link user"><?= e($_SESSION['username']) ?></span>
                <a href="logout.php" class="header-link logout">Odhlásit</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Search Bar -->
        <div class="quick-search-bar">
            <div class="search-container">
                <input type="text" 
                       id="quickSearch" 
                       placeholder="🔍 Rychlé hledání v příspěvcích..." 
                       autocomplete="off">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>

        <!-- Statistiky -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Publikováno</h3>
                    <div class="stat-value"><?= $totalPublishedCount ?></div>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <h3>Koncepty</h3>
                    <div class="stat-value"><?= $totalDraftsCount ?></div>
                </div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">📁</div>
                <div class="stat-info">
                    <h3>Kategorie</h3>
                    <div class="stat-value"><?= count($categories) ?></div>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">🖼️</div>
                <div class="stat-info">
                    <h3>Média</h3>
                    <div class="stat-value"><?= $totalMedia ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Příspěvky</h2>
                <div class="filter-tabs">
                    <a href="?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">
                        Všechny (<?= $totalPublishedCount + $totalDraftsCount ?>)
                    </a>
                    <a href="?filter=published" class="tab <?= $filter === 'published' ? 'active' : '' ?>">
                        ✅ Publikováno (<?= $totalPublishedCount ?>)
                    </a>
                    <a href="?filter=draft" class="tab <?= $filter === 'draft' ? 'active' : '' ?>">
                        📝 Koncepty (<?= $totalDraftsCount ?>)
                    </a>
                </div>
            </div>
            
            <!-- Filtry kategorií -->
            <?php if (!empty($categories)): ?>
            <div class="category-filters">
                <a href="?filter=<?= $filter ?>" class="category-badge <?= !$categoryFilter ? 'active' : '' ?>">
                    Všechny kategorie
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?filter=<?= $filter ?>&category=<?= $cat['id'] ?>" 
                       class="category-badge <?= $categoryFilter == $cat['id'] ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Bulk Actions - druhý řádek -->
            <div class="bulk-actions-row">
                <label class="select-all-label">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    <span>Vybrat vše</span>
                </label>
                <div class="bulk-actions-buttons" id="bulkActionsButtons">
                    <span class="bulk-count" id="bulkCount">0 vybráno</span>
                    <button onclick="bulkDelete()" class="bulk-btn bulk-btn-delete">🗑️ Smazat vybrané</button>
                    <button onclick="bulkPublish()" class="bulk-btn bulk-btn-publish">✅ Publikovat vybrané</button>
                    <button onclick="clearSelection()" class="bulk-btn-cancel">✕ Zrušit výběr</button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Pagination nahoře -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-top">
                <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=1" 
                   class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                    ⏮ První
                </a>
                <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= max(1, $page - 1) ?>" 
                   class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                    ← Předchozí
                </a>
                <span class="page-info">Stránka <?= $page ?> z <?= $totalPages ?></span>
                <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= min($totalPages, $page + 1) ?>" 
                   class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    Další →
                </a>
                <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= $totalPages ?>" 
                   class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    Poslední ⏭
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (empty($allPosts)): ?>
                <p style="text-align: center; padding: 40px; color: #999;">
                    Zatím žádné příspěvky. <a href="add_post.php">Vytvořte první příspěvek</a>
                </p>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($allPosts as $p): ?>
                        <div class="post-card" data-post-id="<?= $p['id'] ?>">
                            <input type="checkbox" class="post-checkbox" value="<?= $p['id'] ?>" onchange="updateBulkSelection()">
                            <?php if ($p['featured_image']): ?>
                                <img src="<?= BASE_URL . e($p['featured_image']) ?>" alt="<?= e($p['title']) ?>">
                            <?php endif; ?>
                            
                            <div class="post-card-content">
                                <span class="badge badge-primary">
                                    <?php 
                                    $catName = $p['category_name'] ?? 'Bez kategorie';
                                    echo e(is_array($catName) ? 'Bez kategorie' : $catName); 
                                    ?>
                                </span>
                                <span class="badge badge-<?= $p['status'] === 'published' ? 'success' : 'warning' ?>">
                                    <?= $p['status'] === 'published' ? 'Publikováno' : 'Koncept' ?>
                                </span>
                                
                                <h3><?= e($p['title']) ?></h3>
                                <div class="post-meta">
                                    <?= formatDate($p['published_at'] ?? $p['created_at']) ?> | 
                                    <?= e($p['author_name']) ?>
                                </div>
                                <p><?= e(truncate($p['excerpt'] ?? $p['content'], 100)) ?></p>
                                
                                <div class="post-actions">
                                    <a href="edit_post.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-small">Upravit</a>
                                    <button onclick="startReorder(<?= $p['id'] ?>)" class="btn btn-reorder btn-small">↕ Přesunout</button>
                                    <?php if ($auth->canEdit($p['author_id'])): ?>
                                        <button onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes($p['title']) ?>')" 
                                                class="btn btn-danger btn-small">
                                            Smazat
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=1" 
                       class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        ⏮ První
                    </a>
                    <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= max(1, $page - 1) ?>" 
                       class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        ← Předchozí
                    </a>
                    <span class="page-info">Stránka <?= $page ?> z <?= $totalPages ?></span>
                    <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= min($totalPages, $page + 1) ?>" 
                       class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        Další →
                    </a>
                    <a href="?filter=<?= $filter ?><?= $categoryFilter ? '&category='.$categoryFilter : '' ?>&page=<?= $totalPages ?>" 
                       class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        Poslední ⏭
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Drag Overlay -->
    <div id="dragOverlay"></div>
    
    <!-- Delete Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">⚠️</div>
            <h3 class="delete-modal-title">Smazat příspěvek?</h3>
            <p class="delete-modal-text" id="deleteModalText"></p>
            <div class="delete-modal-actions">
                <button class="modal-btn modal-btn-danger" onclick="executeDelete()">✓ Ano, smazat</button>
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Zrušit</button>
            </div>
        </div>
    </div>
    
    <!-- Bulk Delete Modal -->
    <div class="delete-modal" id="bulkDeleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">🗑️</div>
            <h3 class="delete-modal-title">Hromadné smazání</h3>
            <p class="delete-modal-text" id="bulkDeleteModalText"></p>
            <div class="delete-modal-actions">
                <button class="modal-btn modal-btn-danger" onclick="executeBulkDelete()">✓ Ano, smazat vše</button>
                <button class="modal-btn modal-btn-cancel" onclick="closeBulkDeleteModal()">Zrušit</button>
            </div>
        </div>
    </div>
    
    <!-- Reorder Instructions -->
    <div class="reorder-instructions" id="reorderInstructions">
        <h3><span style="font-size: 28px;">↕</span> Režim přesouvání</h3>
        <p>🖱️ <strong>Klikněte na článek</strong> kam chcete přesunout červený příspěvek</p>
        <div class="legend">
            <div class="legend-item">
                <div class="legend-box" style="border: 4px solid #fc8181;"></div>
                <span>Přesouváš</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="border: 4px dashed #667eea;"></div>
                <span>Kam můžeš</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="border: 4px solid #48bb78;"></div>
                <span>Cíl (hover)</span>
            </div>
        </div>
        <p style="font-size: 13px; color: #718096;">💡 Můžete scrollovat | ESC = Zrušit</p>
        <button onclick="cancelReorder()">✕ Zrušit přesouvání</button>
    </div>
    
    <script>
        // Quick Search
        let searchTimeout = null;
        const searchInput = document.getElementById('quickSearch');
        const searchResults = document.getElementById('searchResults');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }
            
            searchResults.innerHTML = '<div class="search-loading">Hledám...</div>';
            searchResults.classList.add('active');
            
            searchTimeout = setTimeout(() => {
                fetch('search_posts.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.results.length > 0) {
                            searchResults.innerHTML = data.results.map(post => `
                                <div class="search-result-item" onclick="window.location.href='edit_post.php?id=${post.id}'">
                                    <div class="search-result-content">
                                        <div class="search-result-title">${escapeHtml(post.title)}</div>
                                        <div class="search-result-meta">
                                            <span class="search-result-badge ${post.status}">${post.status === 'published' ? 'Publikováno' : 'Koncept'}</span>
                                            ${post.category_name} • ${post.created_at}
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            searchResults.innerHTML = '<div class="search-no-results">😕 Nenalezeny žádné příspěvky</div>';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        searchResults.innerHTML = '<div class="search-no-results">❌ Chyba při hledání</div>';
                    });
            }, 300);
        });
        
        // Close search on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                searchResults.classList.remove('active');
            }
        });
        
        // Escape closes search
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchResults.classList.remove('active');
                searchInput.blur();
            }
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Delete Modal
        const currentFilter = '<?= $filter ?>';
        const currentCategory = <?= $categoryFilter ? $categoryFilter : 'null' ?>;
        const currentPage = <?= $page ?>;
        
        function confirmDelete(postId, postTitle) {
            deletePostId = postId;
            document.getElementById('deleteModalText').textContent = 
                'Opravdu chcete smazat příspěvek "' + postTitle + '"? Tato akce je nevratná.';
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deletePostId = null;
        }
        
        function executeDelete() {
            if (!deletePostId) return;
            
            // Sestavit URL s filtry
            let redirectUrl = 'delete_post.php?id=' + deletePostId;
            redirectUrl += '&return_filter=' + currentFilter;
            if (currentCategory) {
                redirectUrl += '&return_category=' + currentCategory;
            }
            redirectUrl += '&return_page=' + currentPage;
            
            window.location.href = redirectUrl;
        }
        
        // ESC zavře modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
        
        // Klik mimo modal zavře
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        
        // === BULK ACTIONS ===
        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = checkbox.checked);
            updateBulkSelection();
        }
        
        function updateBulkSelection() {
            const checked = document.querySelectorAll('.post-checkbox:checked');
            const buttons = document.getElementById('bulkActionsButtons');
            const count = document.getElementById('bulkCount');
            count.textContent = checked.length + ' vybráno';
            if (checked.length > 0) buttons.classList.add('active');
            else buttons.classList.remove('active');
        }
        
        function clearSelection() {
            document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkSelection();
        }
        
        function bulkDelete() {
            const ids = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return;
            
            // Otevři bulk delete modal
            const count = ids.length;
            const postWord = count === 1 ? 'příspěvek' : (count < 5 ? 'příspěvky' : 'příspěvků');
            document.getElementById('bulkDeleteModalText').textContent = 
                `Opravdu chcete smazat ${count} ${postWord}? Tato akce je nevratná.`;
            document.getElementById('bulkDeleteModal').classList.add('active');
        }
        
        function closeBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').classList.remove('active');
        }
        
        function executeBulkDelete() {
            const ids = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return;
            window.location.href = 'bulk_action.php?action=delete&ids=' + ids.join(',') + '&return=' + encodeURIComponent(window.location.search);
        }
        
        function bulkPublish() {
            const ids = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return;
            window.location.href = 'bulk_action.php?action=publish&ids=' + ids.join(',') + '&return=' + encodeURIComponent(window.location.search);
        }
        
        // === REORDER MODE - SIMPLE VERSION ===
        let reorderMode = false;
        let reorderSourceId = null;
        let reorderSourceCard = null;
        
        function startReorder(postId) {
            reorderMode = true;
            reorderSourceId = postId;
            
            document.body.classList.add('reorder-mode');
            document.getElementById('dragOverlay').style.display = 'block';
            document.getElementById('reorderInstructions').classList.add('active');
            
            reorderSourceCard = document.querySelector(`[data-post-id="${postId}"]`);
            if (reorderSourceCard) {
                reorderSourceCard.classList.add('reorder-source');
            }
            
            // Přidej event listenery
            const cards = document.querySelectorAll('.post-card');
            
            cards.forEach(card => {
                if (card.dataset.postId != postId) {
                    // Použij ::after overlay pro click
                    card.addEventListener('click', function(e) {
                        handleReorderClick(card);
                    });
                    
                    card.addEventListener('mouseenter', function() {
                        card.classList.add('reorder-target');
                    });
                    
                    card.addEventListener('mouseleave', function() {
                        card.classList.remove('reorder-target');
                    });
                }
            });
            
        }
        
        function handleReorderClick(targetCard) {
            
            if (!reorderMode || !reorderSourceCard) {
                return;
            }
            
            if (targetCard === reorderSourceCard) {
                return;
            }
            
            const postsGrid = document.querySelector('.posts-grid');
            const allCards = Array.from(postsGrid.querySelectorAll('.post-card'));
            const sourceIndex = allCards.indexOf(reorderSourceCard);
            const targetIndex = allCards.indexOf(targetCard);
            
            
            // Insert logic
            if (sourceIndex < targetIndex) {
                targetCard.parentNode.insertBefore(reorderSourceCard, targetCard.nextSibling);
            } else {
                targetCard.parentNode.insertBefore(reorderSourceCard, targetCard);
            }
            
            cancelReorder();
            saveOrderSilent();
        }
        
        function cancelReorder() {
            reorderMode = false;
            reorderSourceId = null;
            reorderSourceCard = null;
            
            document.body.classList.remove('reorder-mode');
            document.getElementById('dragOverlay').style.display = 'none';
            document.getElementById('reorderInstructions').classList.remove('active');
            
            document.querySelectorAll('.post-card').forEach(card => {
                card.classList.remove('reorder-source', 'reorder-target');
                // Remove all event listeners by cloning
                const newCard = card.cloneNode(true);
                card.parentNode.replaceChild(newCard, card);
            });
            
        }
        
        function saveOrderSilent() {
            const order = Array.from(document.querySelectorAll('.post-card')).map(el => el.dataset.postId);
            
            fetch('save_order.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({order})
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    const feedback = document.createElement('div');
                    feedback.textContent = '✓ Pořadí uloženo';
                    feedback.style.cssText = 'position:fixed;top:80px;right:20px;background:#48bb78;color:white;padding:15px 25px;border-radius:8px;font-weight:600;z-index:99999;box-shadow:0 4px 12px rgba(72,187,120,0.4);animation:slideIn 0.3s;';
                    document.body.appendChild(feedback);
                    setTimeout(() => feedback.remove(), 2500);
                }
            }).catch(err => {
                console.error('❌ Save error:', err);
                alert('Chyba při ukládání pořadí');
            });
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (reorderMode) cancelReorder();
                closeBulkDeleteModal();
            }
        });
        
        // Klik mimo bulk modal zavře
        document.getElementById('bulkDeleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeBulkDeleteModal();
        });
    </script>
</body>
</html>