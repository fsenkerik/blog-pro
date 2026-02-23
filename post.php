<?php
define('BLOG_PRO', true);
require_once 'config.php';

$postObj = new Post();
$slug = get('slug');

if (!$slug) {
    redirect(BASE_URL);
}

$post = $postObj->getBySlug($slug);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    echo "Příspěvek nenalezen";
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= SEO::generateMetaTags([
        'title' => $post['meta_title'] ?? $post['title'],
        'description' => $post['meta_description'] ?? $post['excerpt'],
        'keywords' => $post['meta_keywords'] ?? '',
        'image' => $post['featured_image'] ?? null,
        'url' => postUrl($post['slug']),
        'type' => 'article'
    ]) ?>
    <?= SEO::generateArticleSchema($post) ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.8; color: #333; background: #f9fafb; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .post { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .post-category { display: inline-block; background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 13px; margin-bottom: 15px; }
        .post-title { font-size: 36px; margin-bottom: 15px; line-height: 1.3; }
        .post-meta { color: #888; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        .post-image { width: 100%; border-radius: 8px; margin: 30px 0; }
        .post-content { font-size: 17px; line-height: 1.8; }
        .post-content p { margin-bottom: 20px; }
        .post-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 20px 0; }
        .post-content h1, .post-content h2, .post-content h3 { margin: 30px 0 15px; }
        .back-link { display: inline-block; margin-top: 30px; color: #667eea; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <header>
        <h1><?= SITE_NAME ?></h1>
    </header>

    <div class="container">
        <article class="post">
            <span class="post-category"><?= e($post['category_name'] ?? 'Ostatní') ?></span>
            <h1 class="post-title"><?= e($post['title']) ?></h1>
            <div class="post-meta">
                <?= formatDate($post['published_at']) ?> | <?= e($post['author_name']) ?>
            </div>
            
            <?php if ($post['featured_image']): ?>
                <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="post-image">
            <?php endif; ?>
            
            <div class="post-content">
                <?= $post['content'] ?>
            </div>
            
            <a href="<?= BASE_URL ?>" class="back-link">← Zpět na blog</a>
        </article>
    </div>
</body>
</html>
