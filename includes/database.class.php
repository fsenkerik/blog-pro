<?php
/**
 * Database Class
 * Bezpečná práce s MySQL databází pomocí PDO
 */

class Database {
    private $connection;
    private $stmt;
    private $error;
    
    public function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError('Database Connection', $e->getMessage());
            die('Chyba připojení k databázi. Zkontrolujte konfiguraci.');
        }
    }
    
    /**
     * Připraví SQL dotaz
     */
    public function query($sql) {
    try {
        $this->stmt = $this->connection->prepare($sql);
        return $this;
    } catch (PDOException $e) {
        $this->logError('Query Prepare', $e->getMessage());
        $this->stmt = null; // Nastavit na null místo return false
        return $this;
    }
}
    
    /**
     * Bindování hodnot
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Vykonání dotazu
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->logError('Query Execute', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vrátí všechny řádky
     */
    public function fetchAll() {
    if ($this->stmt === null) {
        return [];
    }
    $this->execute();
    return $this->stmt->fetchAll();
    }

    public function fetch() {
        if ($this->stmt === null) {
            return null;
        }
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Vrátí počet řádků
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Vrátí ID posledního vloženého záznamu
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Začátek transakce
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transakce
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transakce
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    /**
     * Escapování hodnot
     */
    public function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    
    /**
     * Logování chyb
     */
    private function logError($type, $message) {
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO error_logs (error_type, error_message, ip_address, user_id) 
                 VALUES (?, ?, ?, ?)"
            );
            
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt->execute([$type, $message, $ipAddress, $userId]);
        } catch (PDOException $e) {
            // Fallback to file logging
            error_log(date('Y-m-d H:i:s') . " - $type: $message\n", 3, ROOT_PATH . 'error.log');
        }
    }
    
    /**
     * Zavření spojení
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Destruktor
     */
    public function __destruct() {
        $this->close();
    }
}
?>
