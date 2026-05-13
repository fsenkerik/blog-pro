<?php
/**
 * Post Class
 * Správa příspěvků s SEO optimalizací
 */

class Post {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Získat všechny příspěvky
     */
    public function getAll($status = 'published', $limit = null, $offset = 0, $categoryId = null) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                       u.username as author_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.status = :status";
        
        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
        }
        
        $sql .= " ORDER BY p.menu_order ASC, p.published_at DESC, p.created_at DESC";
        
        // OPRAVA: LIMIT přímo v query (ne jako placeholder)
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $this->db->query($sql);
        $this->db->bind(':status', $status);
        
        if ($categoryId) {
            $this->db->bind(':category_id', $categoryId);
        }
        
        // ODSTRANĚNO: bind pro limit a offset
        
        return $this->db->fetchAll();
    }
    
    /**
     * Získat příspěvek podle ID
     */
    public function getById($id) {
        $this->db->query(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    u.username as author_name, u.email as author_email
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.id = :id"
        );
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    
    /**
     * Získat příspěvek podle slug
     */
    public function getBySlug($slug) {
        $this->db->query(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    u.username as author_name
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.slug = :slug AND p.status = 'published'"
        );
        $this->db->bind(':slug', $slug);
        return $this->db->fetch();
    }
    
    /**
     * Vytvořit nový příspěvek
     */
    public function create($data) {
        // Generovat slug pokud není
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        } else {
            $data['slug'] = $this->sanitizeSlug($data['slug']);
        }
        
        // Generovat excerpt pokud není
        if (empty($data['excerpt'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }
        
        // SEO defaults
        if (empty($data['meta_title'])) {
            $data['meta_title'] = $data['title'];
        }
        if (empty($data['meta_description'])) {
            $data['meta_description'] = $data['excerpt'];
        }
        
        // Zahrnout nové sloupce pouze pokud mají hodnotu (ochrana proti chybějícím sloupcům v DB)
        $xCols = $xVals = '';
        if (!empty($data['tags']))               { $xCols .= ', tags';               $xVals .= ', :tags'; }
        if (!empty($data['featured_image_alt'])) { $xCols .= ', featured_image_alt'; $xVals .= ', :image_alt'; }

        $this->db->query(
            "INSERT INTO posts
            (title, slug, content, excerpt, featured_image, category_id, author_id, status,
             meta_title, meta_description, meta_keywords{$xCols}, published_at, scheduled_at)
            VALUES
            (:title, :slug, :content, :excerpt, :image, :category, :author, :status,
             :meta_title, :meta_desc, :meta_keys{$xVals}, :published, :scheduled_at)"
        );

        $this->db->bind(':title', $data['title']);
        $this->db->bind(':slug', $data['slug']);
        $this->db->bind(':content', $data['content']);
        $this->db->bind(':excerpt', $data['excerpt']);
        $this->db->bind(':image', $data['featured_image'] ?? null);
        $this->db->bind(':category', $data['category_id'] ?? null);
        $this->db->bind(':author', $data['author_id']);
        $this->db->bind(':status', $data['status'] ?? 'draft');
        $this->db->bind(':meta_title', $data['meta_title']);
        $this->db->bind(':meta_desc', $data['meta_description']);
        $this->db->bind(':meta_keys', $data['meta_keywords'] ?? '');
        if (!empty($data['tags']))               $this->db->bind(':tags', $data['tags']);
        if (!empty($data['featured_image_alt'])) $this->db->bind(':image_alt', $data['featured_image_alt']);
        
        $publishedAt = ($data['status'] ?? 'draft') === 'published' 
            ? date('Y-m-d H:i:s') 
            : null;
        $this->db->bind(':published', $publishedAt);
        $this->db->bind(':scheduled_at', $data['scheduled_at'] ?? null);
        
        if ($this->db->execute()) {
            $postId = $this->db->lastInsertId();
            
            // Zalogovat do audit_log
            global $auditLog;
            if (isset($auditLog)) {
                $auditLog->log('create', 'post', $postId, $data['title']);
            }
            
            return [
                'success' => true,
                'id' => $postId,
                'slug' => $data['slug']
            ];
        }
        
        return ['success' => false, 'message' => 'Nepodařilo se vytvořit příspěvek'];
    }
    
    /**
     * Aktualizovat příspěvek
     */
    public function update($id, $data) {
        // Generovat slug pokud se změnil titulek
        if (!empty($data['title']) && empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title'], $id);
        } elseif (!empty($data['slug'])) {
            $data['slug'] = $this->sanitizeSlug($data['slug']);
        }
        
        // Generovat excerpt
        if (empty($data['excerpt']) && !empty($data['content'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }
        
        $sql = "UPDATE posts SET
                title = :title,
                slug = :slug,
                content = :content,
                excerpt = :excerpt,
                category_id = :category,
                status = :status,
                meta_title = :meta_title,
                meta_description = :meta_desc,
                meta_keywords = :meta_keys";

        // Zahrnout nové sloupce pouze pokud DB sloupce existují (bezpečný fallback)
        if (array_key_exists('tags', $data))               $sql .= ", tags = :tags";
        if (array_key_exists('featured_image_alt', $data)) $sql .= ", featured_image_alt = :image_alt";
        if (array_key_exists('scheduled_at', $data))       $sql .= ", scheduled_at = :scheduled_at";

        if (isset($data['featured_image'])) {
            $sql .= ", featured_image = :image";
        }
        
        // Aktualizovat published_at pokud se mění na published
        if (isset($data['status']) && $data['status'] === 'published') {
            // Zjistit zda už bylo publikováno
            $this->db->query("SELECT published_at FROM posts WHERE id = :id");
            $this->db->bind(':id', $id);
            $current = $this->db->fetch();
            
            if (!$current['published_at']) {
                $sql .= ", published_at = :published_at";
            }
        } elseif (isset($data['status']) && $data['status'] === 'scheduled') {
            $sql .= ", published_at = NULL";
        }
        
        $sql .= " WHERE id = :id";
        
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':slug', $data['slug']);
        $this->db->bind(':content', $data['content']);
        $this->db->bind(':excerpt', $data['excerpt']);
        $this->db->bind(':category', $data['category_id'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'draft');
        $this->db->bind(':meta_title', $data['meta_title'] ?? $data['title']);
        $this->db->bind(':meta_desc', $data['meta_description'] ?? $data['excerpt']);
        $this->db->bind(':meta_keys', $data['meta_keywords'] ?? '');
        if (array_key_exists('tags', $data))               $this->db->bind(':tags', $data['tags']);
        if (array_key_exists('featured_image_alt', $data)) $this->db->bind(':image_alt', $data['featured_image_alt']);
        if (array_key_exists('scheduled_at', $data))       $this->db->bind(':scheduled_at', $data['scheduled_at']);

        if (isset($data['featured_image'])) {
            $this->db->bind(':image', $data['featured_image']);
        }

        if (isset($data['status']) && $data['status'] === 'published' && strpos($sql, ':published_at') !== false) {
            $this->db->bind(':published_at', date('Y-m-d H:i:s'));
        }

        if ($this->db->execute()) {
                // Zalogovat do audit_log
                global $auditLog;
                if (isset($auditLog)) {
                    $auditLog->log('update', 'post', $id, $data['title']);
                }
                
                return ['success' => true, 'slug' => $data['slug']];
            }
        
        return ['success' => false, 'message' => 'Nepodařilo se aktualizovat příspěvek'];
    }
    
    /**
     * Smazat příspěvek
     */
    // OPRAVENÁ metoda delete() v Post.class.php
// NEMAZAT fyzický soubor - pouze odpojit od příspěvku

        /**
 * Smazat příspěvek
 */
        /**
         * Smazat příspěvek
         */
        public function delete($id) {
            // Získat příspěvek
            $post = $this->getById($id);
            
            // Smazat z databáze
            $this->db->query("DELETE FROM posts WHERE id = :id");
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                // Zalogovat do audit_log
                global $auditLog;
                if (isset($auditLog) && $post) {
                    $auditLog->log('delete', 'post', $id, $post['title']);
                }
                
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Nepodařilo se smazat příspěvek'];
        }

        /**
         * Vyhledávání
         */
    
    /**
     * Vyhledávání
     */
    public function search($query, $limit = 20) {
        // OPRAVA: LIMIT přímo v query
        $sql = "SELECT p.*, c.name as category_name, u.username as author_name
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.status = 'published' 
             AND MATCH(p.title, p.content, p.excerpt) AGAINST(:query IN NATURAL LANGUAGE MODE)
             ORDER BY p.published_at DESC
             LIMIT " . (int)$limit;
        
        $this->db->query($sql);
        $this->db->bind(':query', $query);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Počet příspěvků
     */
    public function count($status = 'published', $categoryId = null) {
        $sql = "SELECT COUNT(*) as total FROM posts WHERE status = :status";
        
        if ($categoryId) {
            $sql .= " AND category_id = :category_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':status', $status);
        
        if ($categoryId) {
            $this->db->bind(':category_id', $categoryId);
        }
        
        $result = $this->db->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Generovat unikátní slug
     */
    private function generateSlug($title, $excludeId = null) {
        $slug = $this->sanitizeSlug($title);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Sanitizace slug
     */
    private function sanitizeSlug($text) {
        // Převést na lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // České znaky
        $text = strtr($text, [
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
            'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's',
            'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z'
        ]);
        
        // Odstranit speciální znaky
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Mezery na pomlčky
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        return trim($text, '-');
    }
    
    /**
     * Kontrola existence slug
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM posts WHERE slug = :slug";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':slug', $slug);
        
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        $result = $this->db->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Generovat excerpt z obsahu
     */
    private function generateExcerpt($content, $length = 200) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '...';
        }
        
        return trim($text);
    }
}
?>
