<?php
class Auth {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function login($username, $password) {
        if (!$this->checkRateLimit($username)) {
            return ['success' => false, 'message' => 'Příliš mnoho pokusů. Zkuste to za 15 minut.'];
        }

        $this->db->query("SELECT * FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $username);
        $user = $this->db->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->logLoginAttempt($username, false);
            return ['success' => false, 'message' => 'Nesprávné uživatelské jméno nebo heslo'];
        }

        $this->createSession($user);
        $this->updateLastLogin($user['id']);
        $this->logLoginAttempt($username, true);

        global $sessionTracker, $auditLog;
        if (isset($sessionTracker)) $sessionTracker->recordLogin($user['id']);
        if (isset($auditLog))       $auditLog->log('login', 'user', $user['id'], $user['username']);

        return ['success' => true, 'message' => 'Přihlášení úspěšné', 'user' => $user];
    }

    public function logout() {
        if (isset($_SESSION['session_id'])) {
            $this->db->query("UPDATE sessions SET logout_at = NOW() WHERE id = :id");
            $this->db->bind(':id', $_SESSION['session_id']);
            $this->db->execute();
        }
        global $auditLog;
        if (isset($auditLog) && isset($_SESSION['user_id'])) {
            $auditLog->log('logout', 'user', $_SESSION['user_id'], $_SESSION['username']);
        }
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) setcookie(session_name(), '', time() - 3600, '/');
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) return false;
        if (isset($_SESSION['session_id'])) {
            $this->db->query("SELECT * FROM sessions WHERE id = :id AND user_id = :uid");
            $this->db->bind(':id', $_SESSION['session_id']);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $session = $this->db->fetch();
            if (!$session) { $this->logout(); return false; }
            if (time() - strtotime($session['last_activity']) > SESSION_LIFETIME) { $this->logout(); return false; }
            $this->updateSessionActivity($_SESSION['session_id']);
        }
        return true;
    }

    public function hasRole($role) {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === $role;
    }

    public function getRole() {
        return $_SESSION['user_role'] ?? null;
    }

    public function isIT() {
        return $this->getRole() === 'IT';
    }

    public function isAdmin() {
        return $this->getRole() === 'admin';
    }

    public function canAccessMonitoring() {
        if (!$this->isLoggedIn()) return false;
        $role = $this->getRole();
        if ($role === 'admin') return true;
        if ($role === 'IT') {
            // Check per-user monitoring_access flag
            $this->db->query("SELECT monitoring_access FROM users WHERE id = :id");
            $this->db->bind(':id', $_SESSION['user_id']);
            $user = $this->db->fetch();
            return $user && !empty($user['monitoring_access']);
        }
        return false;
    }

    public function canEdit($authorId) {
        if (!$this->isLoggedIn()) return false;
        $role = $this->getRole();
        return in_array($role, ['admin', 'IT']) || $_SESSION['user_id'] == $authorId;
    }

    public function canDelete($authorId) {
        if (!$this->isLoggedIn()) return false;
        $role = $this->getRole();
        return in_array($role, ['admin', 'IT']) || $_SESSION['user_id'] == $authorId;
    }

    private function createSession($user) {
        $sessionId = bin2hex(random_bytes(32));
        $_SESSION['logged_in']  = true;
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['session_id'] = $sessionId;

        $this->db->query("INSERT INTO sessions (id, user_id, ip_address, user_agent, login_at) VALUES (:id, :uid, :ip, :ua, NOW())");
        $this->db->bind(':id',  $sessionId);
        $this->db->bind(':uid', $user['id']);
        $this->db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '');
        $this->db->bind(':ua',  $_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->db->execute();
    }

    private function updateSessionActivity($sessionId) {
        $this->db->query("UPDATE sessions SET last_activity = NOW() WHERE id = :id");
        $this->db->bind(':id', $sessionId);
        $this->db->execute();
    }

    private function updateLastLogin($userId) {
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
        $this->db->bind(':id', $userId);
        $this->db->execute();
    }

    private function checkRateLimit($username) {
        $key = 'login_attempts_' . md5($username . ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (!isset($_SESSION[$key])) $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        if (time() - $_SESSION[$key]['first_attempt'] > LOGIN_TIMEOUT) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
            return true;
        }
        return $_SESSION[$key]['attempts'] < MAX_LOGIN_ATTEMPTS;
    }

    private function logLoginAttempt($username, $success) {
        $key = 'login_attempts_' . md5($username . ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (!$success) {
            if (!isset($_SESSION[$key])) $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
            $_SESSION[$key]['attempts']++;
        } else {
            unset($_SESSION[$key]);
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $this->db->query("SELECT password FROM users WHERE id = :id");
        $this->db->bind(':id', $userId);
        $user = $this->db->fetch();
        if (!$user || !password_verify($currentPassword, $user['password']))
            return ['success' => false, 'message' => 'Současné heslo je nesprávné'];
        if (strlen($newPassword) < 6)
            return ['success' => false, 'message' => 'Nové heslo musí mít alespoň 6 znaků'];
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $userId);
        return $this->db->execute()
            ? ['success' => true,  'message' => 'Heslo bylo úspěšně změněno']
            : ['success' => false, 'message' => 'Nepodařilo se změnit heslo'];
    }

    public function cleanupOldSessions() {
        $this->db->query("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND logout_at IS NOT NULL");
        return $this->db->execute();
    }

    // ===== User management =====

    public function getAllUsers() {
        $this->db->query("SELECT id, username, email, role, monitoring_access, created_at, last_login FROM users ORDER BY created_at DESC");
        return $this->db->fetchAll();
    }

    public function createUser($username, $password, $email, $role) {
        $this->db->query("SELECT id FROM users WHERE username = :u");
        $this->db->bind(':u', $username);
        if ($this->db->fetch()) return ['success' => false, 'message' => 'Uživatelské jméno již existuje'];
        $this->db->query("INSERT INTO users (username, password, email, role, monitoring_access) VALUES (:u, :p, :e, :r, :m)");
        $this->db->bind(':u', $username);
        $this->db->bind(':p', password_hash($password, PASSWORD_DEFAULT));
        $this->db->bind(':e', $email);
        $this->db->bind(':r', $role);
        $this->db->bind(':m', ($role === 'IT') ? 1 : 0);
        return $this->db->execute()
            ? ['success' => true,  'message' => 'Uživatel vytvořen']
            : ['success' => false, 'message' => 'Nepodařilo se vytvořit uživatele'];
    }

    public function deleteUser($userId) {
        if ($userId == ($_SESSION['user_id'] ?? 0)) return ['success' => false, 'message' => 'Nemůžeš smazat sám sebe'];
        $this->db->query("DELETE FROM users WHERE id = :id");
        $this->db->bind(':id', $userId);
        return $this->db->execute()
            ? ['success' => true,  'message' => 'Uživatel smazán']
            : ['success' => false, 'message' => 'Nepodařilo se smazat uživatele'];
    }
}
?>
