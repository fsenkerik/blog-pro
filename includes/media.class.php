<?php
/**
 * Media Class
 * Správa media knihovny
 */

class Media {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Získat všechny média s stránkováním
     */
    public function getAll($search = '', $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        
        if (!empty($search)) {
            // ESCAPE search pro bezpečnost
            $searchEscaped = str_replace(['%', '_'], ['\%', '\_'], $search);
            $searchParam = '%' . $searchEscaped . '%';
            
            // SQL s LIKE
            $sql = "SELECT * FROM media 
                    WHERE original_name LIKE :search1 
                    OR filename LIKE :search2 
                    ORDER BY uploaded_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $this->db->query($sql);
            $this->db->bind(':search1', $searchParam, PDO::PARAM_STR);
            $this->db->bind(':search2', $searchParam, PDO::PARAM_STR);
            $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            
            return $this->db->fetchAll();
        }
        
        // Bez search
        $sql = "SELECT * FROM media ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset";
        $this->db->query($sql);
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->fetchAll();
    }
    
    /**
     * Získat celkový počet médií
     */
    public function getCount($search = '') {
        if (!empty($search)) {
            $searchEscaped = str_replace(['%', '_'], ['\%', '\_'], $search);
            $searchParam = '%' . $searchEscaped . '%';
            
            $sql = "SELECT COUNT(*) as count FROM media 
                    WHERE original_name LIKE :search1 
                    OR filename LIKE :search2";
            
            $this->db->query($sql);
            $this->db->bind(':search1', $searchParam, PDO::PARAM_STR);
            $this->db->bind(':search2', $searchParam, PDO::PARAM_STR);
            
            $result = $this->db->fetch();
            return $result['count'] ?? 0;
        }
        
        $sql = "SELECT COUNT(*) as count FROM media";
        $this->db->query($sql);
        
        if (!empty($search)) {
            // EXPLICITNĚ PDO::PARAM_STR!
            $this->db->bind(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        
        // Database třída má fetch() která interně volá execute()!
        $result = $this->db->fetch();
        return $result['count'] ?? 0;
        
        if (!empty($search)) {
            $this->db->bind(':search', '%' . $search . '%');
        }
        
        $result = $this->db->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Získat jedno médium
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM media WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    
    /**
     * Přidat médium do databáze
     */
    public function add($data) {
        $this->db->query("
            INSERT INTO media (filename, original_name, path, mime_type, size, width, height)
            VALUES (:filename, :original_name, :path, :mime_type, :size, :width, :height)
        ");
        
        $this->db->bind(':filename', $data['filename']);
        $this->db->bind(':original_name', $data['original_name']);
        $this->db->bind(':path', $data['path']);
        $this->db->bind(':mime_type', $data['mime_type'] ?? null);
        $this->db->bind(':size', $data['size'] ?? null);
        $this->db->bind(':width', $data['width'] ?? null);
        $this->db->bind(':height', $data['height'] ?? null);
        
        if ($this->db->execute()) {
            return [
                'success' => true,
                'id' => $this->db->lastInsertId()
            ];
        }
        
        return ['success' => false];
    }
    
    /**
     * Smazat médium
     */
    public function delete($id) {
        // Získat info o souboru
        $media = $this->getById($id);
        if (!$media) {
            return ['success' => false, 'message' => 'Médium nenalezeno'];
        }
        
        // Smazat soubor z disku
        $filepath = ROOT_PATH . $media['path'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        
        // Smazat z databáze
        $this->db->query("DELETE FROM media WHERE id = :id");
        $this->db->bind(':id', $id);
        
        if ($this->db->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Chyba při mazání'];
    }
    
    /**
     * Smazat více médií najednou
     */
    public function deleteMultiple($ids) {
        $deleted = 0;
        $errors = 0;
        
        foreach ($ids as $id) {
            $result = $this->delete($id);
            if ($result['success']) {
                $deleted++;
            } else {
                $errors++;
            }
        }
        
        return [
            'success' => $errors === 0,
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }
    
    /**
     * Získat statistiky
     */
    public function getStats() {
        // Počet souborů
        $this->db->query("SELECT COUNT(*) as count FROM media");
        $count = $this->db->fetch()['count'];
        
        // Celková velikost
        $this->db->query("SELECT SUM(size) as total_size FROM media");
        $totalSize = $this->db->fetch()['total_size'] ?? 0;
        
        return [
            'count' => $count,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize)
        ];
    }
    
    /**
     * Formátovat velikost souboru
     */
    private function formatBytes($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}