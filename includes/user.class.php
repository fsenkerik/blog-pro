<?php
/**
 * User Class
 * Správa uživatelů
 */

class User {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Získat uživatele podle ID
     */
    public function getById($id) {
        $this->db->query("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    
    /**
     * Získat všechny uživatele
     */
    public function getAll() {
        $this->db->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY username");
        return $this->db->fetchAll();
    }
}
?>
