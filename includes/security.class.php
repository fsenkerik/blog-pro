<?php
/**
 * Security Class
 * CSRF ochrana, XSS prevence, input sanitizace
 */

class Security {
    
    /**
     * Generovat CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Ověřit CSRF token
     */
    public static function verifyToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Vytvořit HTML input s CSRF tokenem
     */
    public static function tokenInput() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitizace vstupu
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
            return $data;
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validace emailu
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Escapování pro HTML výstup
     */
    public static function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Prevence XSS v HTML obsahu (pro TinyMCE výstup)
     */
    public static function cleanHTML($html) {
        // Povolené tagy
        $allowed = '<p><br><strong><em><u><span><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><figure><figcaption><blockquote><code><pre><video><source>';
        
        // Odstranit nebezpečné tagy
        $html = strip_tags($html, $allowed);
        
        // Odstranit javascript: a data: v odkazech i src a href a zachovat jen bezpečnou hodnotu
        $html = preg_replace('/\s(href|src)=["\']?\s*(javascript:|data:)[^"\']*["\']/i', ' $1="#"', $html);
        
        // Odstranit on* atributy (onclick, onload, atd.)
        $html = preg_replace('/<([^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\'][^>]*>/i', '<$1>', $html);

        // Povolit jen bezpečné inline styly, které editor využívá.
        $html = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $allowedProperties = [
                'font-size',
                'line-height',
                'font-family',
                'color',
                'background-color',
                'text-align',
                'width',
                'max-width',
                'height',
                'display',
                'margin',
                'margin-left',
                'margin-right',
                'margin-top',
                'margin-bottom',
                'float',
                'clear',
                'border-radius'
            ];

            $safeDeclarations = [];
            foreach (explode(';', $matches[2]) as $declaration) {
                if (strpos($declaration, ':') === false) {
                    continue;
                }

                [$property, $value] = array_map('trim', explode(':', $declaration, 2));
                $property = strtolower($property);
                $valueLower = strtolower($value);

                if (!in_array($property, $allowedProperties, true)) {
                    continue;
                }

                if ($value === '' || preg_match('/expression|javascript:|url\s*\(/i', $valueLower)) {
                    continue;
                }

                $safeDeclarations[] = $property . ':' . $value;
            }

            if (!$safeDeclarations) {
                return '';
            }

            return 'style="' . implode(';', $safeDeclarations) . '"';
        }, $html);
        
        return $html;
    }
}
?>
