<?php
define('BLOG_PRO', true);
require_once 'config.php';

$post = new Post();
$category = new Category();

$categorySlug = get('category');
$categoryId = null;
$currentCategory = null;

if ($categorySlug) {
    $currentCategory = $category->getBySlug($categorySlug);
    $categoryId = $currentCategory['id'] ?? null;
}

$posts = $post->getAll('published', 50, 0, $categoryId);
$categories = $category->getAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= SEO::generateMetaTags([
        'title' => $currentCategory ? $currentCategory['name'] . ' | ' . SITE_NAME : SITE_NAME,
        'description' => $currentCategory['description'] ?? SITE_DESCRIPTION
    ]) ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 20px; text-align: center; }
        header h1 { font-size: 42px; margin-bottom: 10px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .filters { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; justify-content: center; }
        .filter-btn { padding: 10px 20px; border: 2px solid #667eea; background: white; color: #667eea; border-radius: 25px; text-decoration: none; font-weight: 600; }
        .filter-btn:hover, .filter-btn.active { background: #667eea; color: white; }
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; }
        .post-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .post-card:hover { transform: translateY(-5px); }
        .post-image { width: 100%; height: 220px; object-fit: cover; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .post-content { padding: 25px; }
        .post-category { display: inline-block; background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-bottom: 12px; }
        .post-title { font-size: 22px; font-weight: 700; margin-bottom: 10px; }
        .post-date { color: #888; font-size: 14px; margin-bottom: 15px; }
        .post-excerpt { color: #666; }
        .read-more { display: inline-block; margin-top: 15px; color: #667eea; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <h1>📝 <?= SITE_NAME ?></h1>
        <p><?= SITE_DESCRIPTION ?></p>
    </header>

    <div class="container">
        <div class="filters">
            <a href="index.php" class="filter-btn <?= !$categorySlug ? 'active' : '' ?>">Vše</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= e($cat['slug']) ?>" 
                   class="filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                    <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts)): ?>
            <p style="text-align: center; padding: 60px; color: #999;">Zatím žádné příspěvky</p>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $p): ?>
                    <div class="post-card">
                        <?php if ($p['featured_image']): ?>
                            <img src="<?= e($p['featured_image']) ?>" alt="<?= e($p['title']) ?>" class="post-image">
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <span class="post-category"><?= e($p['category_name'] ?? 'Ostatní') ?></span>
                            <h2 class="post-title"><?= e($p['title']) ?></h2>
                            <div class="post-date"><?= formatDate($p['published_at']) ?></div>
                            <p class="post-excerpt"><?= e(truncate($p['excerpt'] ?? $p['content'], 150)) ?></p>
                            <a href="post.php?slug=<?= e($p['slug']) ?>" class="read-more">Číst více →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
