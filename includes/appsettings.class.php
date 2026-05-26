<?php

class AppSettings {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function get(string $key, string $default = ''): string {
        $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        $row = $this->db->fetch();

        if (!$row || $row['setting_value'] === null) {
            return $default;
        }

        return (string)$row['setting_value'];
    }

    public function getMany(array $defaults): array {
        $settings = $defaults;

        if (empty($defaults)) {
            return $settings;
        }

        $placeholders = [];
        foreach (array_keys($defaults) as $index => $key) {
            $placeholders[] = ':key' . $index;
        }

        $this->db->query(
            "SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN (" . implode(',', $placeholders) . ")"
        );

        foreach (array_keys($defaults) as $index => $key) {
            $this->db->bind(':key' . $index, $key);
        }

        foreach ($this->db->fetchAll() as $row) {
            $settings[$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function set(string $key, string $value): bool {
        $this->db->query(
            "INSERT INTO app_settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP"
        );
        $this->db->bind(':key', $key);
        $this->db->bind(':value', $value);
        return $this->db->execute();
    }

    public function setMany(array $values): bool {
        foreach ($values as $key => $value) {
            if (!$this->set((string)$key, (string)$value)) {
                return false;
            }
        }

        return true;
    }
}
