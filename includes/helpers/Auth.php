<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    /**
     * Get the real user IP, considering Cloudflare and proxies
     */
    private static function getRemoteIp(): string {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Check if user is logged in and session is valid
     */
    public static function isLoggedIn(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // --- SESSION HIJACKING PROTECTION ---
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp = self::getRemoteIp();

        if (!isset($_SESSION['secure_ua']) || !isset($_SESSION['secure_ip'])) {
            return false; 
        }

        // We only logout if User Agent changes. IP changes across Cloudflare are common, 
        // so we check but don't force-logout immediately unless both or UA change.
        if ($_SESSION['secure_ua'] !== $userAgent) {
            self::logout();
            return false;
        }

        // --- SINGLE SESSION PROTECTION (Logins Simultâneos) ---
        global $platform_settings;
        if (($platform_settings['security_single_session'] ?? '0') === '1') {
            try {
                $db = \DB::getInstance();
                $stmt = $db->prepare("SELECT current_session_id FROM cp_users WHERE id = ?");
                $stmt->execute([(int)$_SESSION['user_id']]);
                $db_session_id = $stmt->fetchColumn();

                // Se existir uma sessão diferente registrada no banco, esta sessão (antiga) é invalidada
                if ($db_session_id && $db_session_id !== session_id()) {
                    self::logout();
                    return false;
                }

                // Refresh the "pulse" to show other browsers this user is active
                $db->prepare("UPDATE cp_users SET last_pulse = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), (int)$_SESSION['user_id']]);
            } catch (\Exception $e) { }
        }

        return true;
    }

    /**
     * Check if user is a global administrator
     */
    public static function isAdmin(): bool {
        return self::isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrador';
    }

    /**
     * Redirect if not logged in
     */
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header("Location: " . SITE_URL . "/login");
            exit;
        }
    }

    /**
     * Check if a user (by DB ID) already has an active session registered.
     * Uses the "last_pulse" heartbeat in the DB for reliability.
     */
    public static function hasActiveSession(int $userId): bool {
        try {
            $db = \DB::getInstance();
            $stmt = $db->prepare("SELECT current_session_id, last_pulse FROM cp_users WHERE id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            if (!$row || !$row['current_session_id'] || !$row['last_pulse']) return false;

            // If last pulse was in the last 2 minutes, consider it ACTIVE
            $pulseTime = strtotime($row['last_pulse']);
            $diff = time() - $pulseTime;

            if ($diff < 120) { // 2 minutes window
                return true; 
            }

            // Stale session
            self::clearSessionFromDB($userId);
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear current_session_id from DB (used on logout or stale session cleanup)
     */
    public static function clearSessionFromDB(int $userId): void {
        try {
            $db = \DB::getInstance();
            $db->prepare("UPDATE cp_users SET current_session_id = NULL WHERE id = ?")->execute([$userId]);
        } catch (\Exception $e) {}
    }

    /**
     * Require Global Admin access
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header("Location: " . SITE_URL . "/dashboard");
            exit;
        }
    }

    /**
     * Login user and initialize secure session markers
     */
    public static function login(array $user): void {
        // Force session ID regeneration on login
        session_regenerate_id(true);
        $new_sid = session_id();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // Security markers
        $_SESSION['secure_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['secure_ip'] = self::getRemoteIp();

        // Single Session Protection - Update DB
        global $platform_settings;
        if (($platform_settings['security_single_session'] ?? '0') === '1') {
            try {
                $db = \DB::getInstance();
                $stmt = $db->prepare("UPDATE cp_users SET current_session_id = ?, last_login = NOW(), last_pulse = ? WHERE id = ?");
                $stmt->execute([$new_sid, date('Y-m-d H:i:s'), (int)$user['id']]);
            } catch (\Exception $e) {
                try {
                    $db->exec("ALTER TABLE cp_users ADD COLUMN current_session_id VARCHAR(255) DEFAULT NULL, ADD COLUMN last_pulse DATETIME DEFAULT NULL");
                    $db->prepare("UPDATE cp_users SET current_session_id = ?, last_login = NOW(), last_pulse = ? WHERE id = ?")->execute([$new_sid, date('Y-m-d H:i:s'), (int)$user['id']]);
                } catch (\Exception $e2) {}
            }
        }
    }

    /**
     * Logout user and clear session + remove from DB
     */
    public static function logout(): void {
        // Clear session ID from DB so next login is allowed
        if (isset($_SESSION['user_id'])) {
            self::clearSessionFromDB((int)$_SESSION['user_id']);
        }

        session_unset();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (!headers_sent()) {
            header("Location: " . SITE_URL . "/login");
            exit;
        }
    }

    /**
     * Check for session inactivity (2 hours)
     */
    public static function checkInactivity(): void {
        if (!isset($_SESSION['user_id'])) return;

        // Atualizar last_login ou similar para manter track de atividade se necessário
        $timeout = 7200; // 2 hours
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
            return;
        }
        $_SESSION['last_activity'] = time();
        
        // Se a proteção de sessão única estiver ativa, validamos a sessão a cada hit
        self::isLoggedIn();
    }
}

// Global inactivity check
Auth::checkInactivity();
