<?php
/**
 * Category Class
 * Správa kategorií
 */

class Category {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Získat všechny kategorie
     */
    public function getAll() {
        $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $this->db->fetchAll();
    }
    
    /**
     * Získat kategorii podle ID
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM categories WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    
    /**
     * Získat kategorii podle slug
     */
    public function getBySlug($slug) {
        $this->db->query("SELECT * FROM categories WHERE slug = :slug");
        $this->db->bind(':slug', $slug);
        return $this->db->fetch();
    }
}
?>
