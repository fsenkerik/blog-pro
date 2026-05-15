<?php
/**
 * Database Class
 */

class Database {
    private $connection;
    private $stmt;
    private $lastExecuteOk = false;

    public function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $offset = (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('P');
            $this->connection->exec("SET time_zone = " . $this->connection->quote($offset));
        } catch (PDOException $e) {
            error_log(date('Y-m-d H:i:s') . " - DB Connection: " . $e->getMessage() . "\n", 3, ROOT_PATH . 'error.log');
            die('Chyba připojení k databázi.');
        }
    }

    public function query($sql) {
        $this->lastExecuteOk = false;
        try {
            $this->stmt = $this->connection->prepare($sql);
            return $this;
        } catch (PDOException $e) {
            $this->logError('Prepare', $e->getMessage());
            $this->stmt = null;
            return $this;
        }
    }

    public function bind($param, $value, $type = null) {
        if ($this->stmt === null) return $this;
        if (is_null($type)) {
            switch (true) {
                case is_int($value):  $type = PDO::PARAM_INT;  break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default:              $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    public function execute() {
        if ($this->stmt === null) return false;
        try {
            $this->lastExecuteOk = $this->stmt->execute();
            return $this->lastExecuteOk;
        } catch (\Throwable $e) {
            $this->lastExecuteOk = false;
            $this->logError('Execute', $e->getMessage());
            return false;
        }
    }

    public function fetchAll() {
        if ($this->stmt === null) return [];
        $this->execute();
        if (!$this->lastExecuteOk) return [];
        try {
            return $this->stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $this->logError('fetchAll', $e->getMessage());
            return [];
        }
    }

    public function fetch() {
        if ($this->stmt === null) return null;
        $this->execute();
        if (!$this->lastExecuteOk) return null;
        try {
            return $this->stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            $this->logError('fetch', $e->getMessage());
            return null;
        }
    }

    public function rowCount() {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() { return $this->connection->beginTransaction(); }
    public function commit()           { return $this->connection->commit(); }
    public function rollBack()         { return $this->connection->rollBack(); }

    public function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function logError($type, $message) {
        try {
            error_log(date('Y-m-d H:i:s') . " - DB $type: $message\n", 3, ROOT_PATH . 'error.log');
        } catch (\Throwable $e) {}
    }

    public function close() { $this->connection = null; }
    public function __destruct() { $this->close(); }
}
?>
