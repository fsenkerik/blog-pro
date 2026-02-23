<?php
/**
 * Auth Class
 * Autentizace, session management, rate limiting
 */

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Přihlášení uživatele
     */
    public function login($username, $password) {
        // Rate limiting check
        if (!$this->checkRateLimit($username)) {
            return [
                'success' => false,
                'message' => 'Příliš mnoho pokusů o přihlášení. Zkuste to za 15 minut.'
            ];
        }
        
        // Získat uživatele
        $this->db->query("SELECT * FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $username);
        $user = $this->db->fetch();
        
        if (!$user) {
            $this->logLoginAttempt($username, false);
            return [
                'success' => false,
                'message' => 'Nesprávné uživatelské jméno nebo heslo'
            ];
        }
        
        // Ověření hesla
        if (!password_verify($password, $user['password'])) {
            $this->logLoginAttempt($username, false);
            return [
                'success' => false,
                'message' => 'Nesprávné uživatelské jméno nebo heslo'
            ];
        }
        // Úspěšné přihlášení
        $this->createSession($user);
        $this->updateLastLogin($user['id']);
        $this->logLoginAttempt($username, true);

        // Zaznamenat do monitoringu
        global $sessionTracker, $auditLog;
        if (isset($sessionTracker) && isset($auditLog)) {
            $sessionTracker->recordLogin($user['id']);
            $auditLog->log('login', 'user', $user['id'], $user['username']);
        }

        return [
            'success' => true,
            'message' => 'Přihlášení úspěšné',
            'user' => $user
        ];
    }
    /**
     * Odhlášení uživatele
     */
        public function logout() {
            // Nastavit logout_at místo smazání
            if (isset($_SESSION['session_id'])) {
                $this->db->query("UPDATE sessions SET logout_at = NOW() WHERE id = :id");
                $this->db->bind(':id', $_SESSION['session_id']);
                $this->db->execute();
            }
            
            // Zaznamenat odhlášení do audit_log
            global $auditLog;
            if (isset($auditLog) && isset($_SESSION['user_id'])) {
                $auditLog->log('logout', 'user', $_SESSION['user_id'], $_SESSION['username'], 'Uživatel se odhlásil');
            }
            
            // Vymazat session
            $_SESSION = [];
            
            // Zničit session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
            return true;
        }

        /**
         * Kontrola přihlášení
         */
        public function isLoggedIn() {
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                return false;
            }
        // Kontrola session v databázi
        if (isset($_SESSION['session_id'])) {
            $this->db->query("SELECT * FROM sessions WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $_SESSION['session_id']);
            $this->db->bind(':user_id', $_SESSION['user_id']);
            $session = $this->db->fetch();
            
            if (!$session) {
                $this->logout();
                return false;
            }
            
            // Kontrola timeout
            $lastActivity = strtotime($session['last_activity']);
            if (time() - $lastActivity > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
            
            // Aktualizovat last_activity
            $this->updateSessionActivity($_SESSION['session_id']);
        }
        
        return true;
    }
    
    /**
     * Kontrola role uživatele
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === $role;
    }
    /**
     * Získat roli uživatele
     */
    public function getRole() {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Je IT role?
     */
    public function isIT() {
        return $this->getRole() === 'it';
    }

    /**
     * Může přistupovat k monitoringu?
     */
    public function canAccessMonitoring() {
        return $this->isIT();
    }
    
    /**
     * Kontrola oprávnění (admin nebo vlastní obsah)
     */
    public function canEdit($authorId) {
    if (!$this->isLoggedIn()) {
        return false;
    }
    
            // IT a Admin mohou upravovat vše, Editor jen vlastní
            return $_SESSION['user_role'] === 'it' 
                || $_SESSION['user_role'] === 'admin' 
                || $_SESSION['user_id'] == $authorId;
    }

    /**
     * Může mazat příspěvky?
     */
    public function canDelete($authorId) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // IT a Admin mohou mazat vše, Editor jen vlastní
        return $this->isIT() 
            || $this->hasRole('admin') 
            || $_SESSION['user_id'] == $authorId;
    }
        
    /**
     * Vytvoření session
     */
        private function createSession($user) {
            $sessionId = bin2hex(random_bytes(32));
            
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['session_id'] = $sessionId;
            
            // Uložit do databáze
            $this->db->query(
                "INSERT INTO sessions (id, user_id, ip_address, user_agent, login_at) 
                VALUES (:id, :user_id, :ip, :ua, NOW())"
            );
            $this->db->bind(':id', $sessionId);
            $this->db->bind(':user_id', $user['id']);
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR']);
            $this->db->bind(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $result = $this->db->execute();
            
            // DEBUG - TADY! PŘED ZÁVĚREČNOU }
            echo "<pre>";
            echo "Session created:\n";
            echo "ID: $sessionId\n";
            echo "User: {$user['id']}\n";
            echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
            if (!$result) {
                echo "Error info:\n";
                print_r($this->db->errorInfo());
            }
            echo "</pre>";
            die();
            
        }
    
    /**
     * Aktualizace last_activity
     */
    private function updateSessionActivity($sessionId) {
        $this->db->query("UPDATE sessions SET last_activity = NOW() WHERE id = :id");
        $this->db->bind(':id', $sessionId);
        $this->db->execute();
    }
    
    /**
     * Aktualizace last_login
     */
    private function updateLastLogin($userId) {
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
        $this->db->bind(':id', $userId);
        $this->db->execute();
    }
    
    /**
     * Rate limiting - ochrana proti brute force
     */
    private function checkRateLimit($username) {
        $cacheKey = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$cacheKey];
        
        // Reset po 15 minutách
        if (time() - $data['first_attempt'] > LOGIN_TIMEOUT) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Kontrola limitu
        return $data['attempts'] < MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Logování pokusů o přihlášení
     */
    private function logLoginAttempt($username, $success) {
        $cacheKey = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        
        if (!$success) {
            if (!isset($_SESSION[$cacheKey])) {
                $_SESSION[$cacheKey] = [
                    'attempts' => 0,
                    'first_attempt' => time()
                ];
            }
            $_SESSION[$cacheKey]['attempts']++;
        } else {
            // Reset při úspěchu
            unset($_SESSION[$cacheKey]);
        }
    }
    
    /**
     * Změna hesla
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Ověřit současné heslo
        $this->db->query("SELECT password FROM users WHERE id = :id");
        $this->db->bind(':id', $userId);
        $user = $this->db->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Současné heslo je nesprávné'
            ];
        }
        
        // Validace nového hesla
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'Nové heslo musí mít alespoň 6 znaků'
            ];
        }
        
        // Změnit heslo
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', $userId);
        
        if ($this->db->execute()) {
            return [
                'success' => true,
                'message' => 'Heslo bylo úspěšně změněno'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Nepodařilo se změnit heslo'
        ];
    }
    
    /**
     * Čištění starých sessions
     */
    public function cleanupOldSessions() {
        $this->db->query("CALL cleanup_old_sessions()");
        return $this->db->execute();
    }
}
?>
