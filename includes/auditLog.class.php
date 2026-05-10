<?php
/**
 * Audit Log Class
 * Sledování všech akcí uživatelů
 */

class AuditLog {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Zalogovat akci
     */
    public function log($action, $entityType, $entityId = null, $entityName = null, $details = null) {
        $this->db->query(
            "INSERT INTO audit_log (user_id, action, entity_type, entity_id, entity_name, details, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :entity_name, :details, :ip)"
        );
        
        $this->db->bind(':user_id', $_SESSION['user_id'] ?? 0);
        $this->db->bind(':action', $action);
        $this->db->bind(':entity_type', $entityType);
        $this->db->bind(':entity_id', $entityId);
        $this->db->bind(':entity_name', $entityName);
        $this->db->bind(':details', $details ? json_encode($details) : null);
        $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
        
        return $this->db->execute();
    }
    
    /**
     * Získat poslední aktivity
     */
    public function getRecent($limit = 50, $filters = []) {
        $sql = "SELECT a.*, u.username, u.role
                FROM audit_log a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE 1=1";
        $params = [];
        
        // Filtr: uživatel
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        
        // Filtr: akce
        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $params[':action'] = $filters['action'];
        }
        
        // Filtr: období
        if (!empty($filters['period'])) {
            switch ($filters['period']) {
                case 'today':
                    $sql .= " AND DATE(a.created_at) = CURDATE()";
                    break;
                case 'week':
                    $sql .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }
        
        // Filtr: vyhledávání
        if (!empty($filters['search'])) {
            $sql .= " AND (a.entity_name LIKE :search_entity OR u.username LIKE :search_user)";
            $search = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['search']) . '%';
            $params[':search_entity'] = $search;
            $params[':search_user'] = $search;
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit;
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    /**
     * Statistiky
     */
    public function getStats($period = 'today') {
        $dateCondition = "DATE(created_at) = CURDATE()";
        
        if ($period === 'week') {
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
        
        $this->db->query("
            SELECT 
                action,
                COUNT(*) as count
            FROM audit_log
            WHERE $dateCondition
            GROUP BY action
        ");
        
        $results = $this->db->fetchAll();
        
        $stats = [
            'create' => 0,
            'update' => 0,
            'delete' => 0,
            'login' => 0,
            'backup' => 0,
            'upload' => 0,
            'total' => 0
        ];
        
        foreach ($results as $row) {
            $stats[$row['action']] = $row['count'];
            $stats['total'] += $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Export do CSV
     */
    public function exportCSV($filters = []) {
        $data = $this->getRecent(10000, $filters);
        
        $csv = "Čas,Uživatel,Role,Akce,Typ,Název,IP adresa\n";
        
        foreach ($data as $row) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $row['created_at'],
                $row['username'],
                $row['role'],
                $row['action'],
                $row['entity_type'],
                $row['entity_name'] ?? '',
                $row['ip_address']
            );
        }
        
        return $csv;
    }
}
?>
