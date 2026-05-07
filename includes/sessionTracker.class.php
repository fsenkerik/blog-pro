<?php
class SessionTracker {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function recordLogin($userId) {
        // Session row was already inserted by Auth::createSession()
        // Just link the tracker to the same session ID.
        if (!empty($_SESSION['session_id'])) {
            $_SESSION['session_tracker_id'] = $_SESSION['session_id'];
        }
        return true;
    }

    public function updateActivity() {
        if (!empty($_SESSION['session_tracker_id'])) {
            $this->db->query("UPDATE sessions SET last_activity = NOW() WHERE id = :id");
            $this->db->bind(':id', $_SESSION['session_tracker_id']);
            $this->db->execute();
        }
    }

    public function recordLogout() {
        if (!empty($_SESSION['session_tracker_id'])) {
            $this->db->query("UPDATE sessions SET logout_at = NOW() WHERE id = :id");
            $this->db->bind(':id', $_SESSION['session_tracker_id']);
            $this->db->execute();
        }
    }

    public function getActiveSessions($minutesThreshold = 15) {
        $sql = "SELECT s.id, s.user_id, s.ip_address, s.login_at, s.last_activity, s.logout_at,
                       u.username, u.role,
                       TIMESTAMPDIFF(MINUTE, s.last_activity, NOW()) as minutes_ago
                FROM sessions s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.logout_at IS NULL
                ORDER BY s.last_activity DESC
                LIMIT 100";
        $this->db->query($sql);
        $results = $this->db->fetchAll();
        if ($results) {
            foreach ($results as &$row) {
                $row['is_active'] = ($row['minutes_ago'] <= $minutesThreshold) ? 1 : 0;
            }
        }
        return $results ?: [];
    }

    public function getActiveCount($minutesThreshold = 15) {
        $sql = "SELECT COUNT(*) as count FROM sessions
                WHERE logout_at IS NULL
                AND TIMESTAMPDIFF(MINUTE, last_activity, NOW()) <= " . (int)$minutesThreshold;
        $this->db->query($sql);
        $result = $this->db->fetch();
        return $result['count'] ?? 0;
    }

    public function cleanup() {
        $this->db->query("UPDATE sessions SET logout_at = last_activity
            WHERE logout_at IS NULL AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        return $this->db->execute();
    }
}
?>
