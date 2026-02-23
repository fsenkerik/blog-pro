<?php
/**
 * SEO Class
 * Meta tagy, Open Graph, sitemap generování
 */

class SEO {
    
    /**
     * Generovat meta tagy pro stránku
     */
    public static function generateMetaTags($data = []) {
        $title = $data['title'] ?? SITE_NAME;
        $description = $data['description'] ?? SITE_DESCRIPTION;
        $keywords = $data['keywords'] ?? SITE_KEYWORDS;
        $image = $data['image'] ?? null;
        $url = $data['url'] ?? BASE_URL;
        $type = $data['type'] ?? 'website';
        
        $html = '';
        
        // Basic meta tags
        $html .= '<title>' . self::escape($title) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . self::escape($description) . '">' . "\n";
        $html .= '<meta name="keywords" content="' . self::escape($keywords) . '">' . "\n";
        
        // Open Graph
        $html .= '<meta property="og:title" content="' . self::escape($title) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . self::escape($description) . '">' . "\n";
        $html .= '<meta property="og:type" content="' . $type . '">' . "\n";
        $html .= '<meta property="og:url" content="' . self::escape($url) . '">' . "\n";
        $html .= '<meta property="og:site_name" content="' . SITE_NAME . '">' . "\n";
        
        if ($image) {
            $imageUrl = strpos($image, 'http') === 0 ? $image : BASE_URL . $image;
            $html .= '<meta property="og:image" content="' . self::escape($imageUrl) . '">' . "\n";
        }
        
        // Twitter Card
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:title" content="' . self::escape($title) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . self::escape($description) . '">' . "\n";
        
        if ($image) {
            $imageUrl = strpos($image, 'http') === 0 ? $image : BASE_URL . $image;
            $html .= '<meta name="twitter:image" content="' . self::escape($imageUrl) . '">' . "\n";
        }
        
        return $html;
    }
    
    /**
     * Generovat strukturovaná data pro článek (JSON-LD)
     */
    public static function generateArticleSchema($post) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => $post['excerpt'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $post['author_name'] ?? 'Admin'
            ],
            'datePublished' => $post['published_at'] ?? $post['created_at'],
            'dateModified' => $post['updated_at'] ?? $post['created_at']
        ];
        
        if (!empty($post['featured_image'])) {
            $imageUrl = strpos($post['featured_image'], 'http') === 0 
                ? $post['featured_image'] 
                : BASE_URL . $post['featured_image'];
            $schema['image'] = $imageUrl;
        }
        
        if (!empty($post['slug'])) {
            $schema['url'] = BASE_URL . 'post/' . $post['slug'];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
    
    /**
     * Generovat sitemap.xml
     */
    public static function generateSitemap() {
        global $db;
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Hlavní stránka
        $xml .= self::addSitemapUrl(BASE_URL, date('Y-m-d'), '1.0', 'daily');
        
        // Příspěvky
        $db->query("SELECT slug, updated_at, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC");
        $posts = $db->fetchAll();
        
        foreach ($posts as $post) {
            $url = BASE_URL . 'post/' . $post['slug'];
            $lastmod = $post['updated_at'] ?? $post['published_at'];
            $xml .= self::addSitemapUrl($url, date('Y-m-d', strtotime($lastmod)), '0.8', 'weekly');
        }
        
        // Kategorie
        $db->query("SELECT slug FROM categories");
        $categories = $db->fetchAll();
        
        foreach ($categories as $category) {
            $url = BASE_URL . 'category/' . $category['slug'];
            $xml .= self::addSitemapUrl($url, date('Y-m-d'), '0.6', 'weekly');
        }
        
        $xml .= '</urlset>';
        
        // Uložit do souboru
        file_put_contents(ROOT_PATH . 'sitemap.xml', $xml);
        
        return true;
    }
    
    /**
     * Přidat URL do sitemapy
     */
    private static function addSitemapUrl($loc, $lastmod, $priority, $changefreq) {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . self::escape($loc) . "</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <priority>$priority</priority>\n";
        $xml .= "    <changefreq>$changefreq</changefreq>\n";
        $xml .= "  </url>\n";
        return $xml;
    }
    
    /**
     * Escape pro XML/HTML
     */
    private static function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    
    /**
     * Generovat robots.txt
     */
    public static function generateRobots() {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /includes/\n";
        $content .= "Disallow: /backups/\n\n";
        $content .= "Sitemap: " . BASE_URL . "sitemap.xml\n";
        
        file_put_contents(ROOT_PATH . 'robots.txt', $content);
        
        return true;
    }
}
?>
