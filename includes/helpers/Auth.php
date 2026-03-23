<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    /**
     * Check if user is logged in and session is valid
     */
    public static function isLoggedIn(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // --- SESSION HIJACKING PROTECTION (Rule 6) ---
        // Validate User-Agent and IP to prevent stolen session usage
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!isset($_SESSION['secure_ua']) || !isset($_SESSION['secure_ip'])) {
            // First time check - should have been set at login
            return false; 
        }

        if ($_SESSION['secure_ua'] !== $userAgent || $_SESSION['secure_ip'] !== $userIp) {
            self::logout();
            return false;
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
            header("Location: " . SITE_URL . "/login.php");
            exit;
        }
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
        // Regeneration on login (high security)
        session_regenerate_id(true);

        $pdo = \DB::getInstance();
        $sessionId = session_id();

        // Update DB with current session ID for single-session enforcement
        $stmt = $pdo->prepare('UPDATE cp_users SET current_session_id = ?, last_pulse = NOW() WHERE id = ?');
        $stmt->execute([$sessionId, $user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // CSRF Marker and Security
        $_SESSION['secure_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['secure_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Logout user and clear session
     */
    public static function logout(): void {
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
            header("Location: " . SITE_URL . "/login.php");
            exit;
        }
    }


    /**
     * Check for session inactivity (from system settings or 2 hours default)
     */
    public static function checkInactivity(): void {
        if (!isset($_SESSION['user_id'])) return;

        global $platform_settings;
        $timeout = (int)($platform_settings['security_session_timeout'] ?? 120) * 60; // Value in minutes

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Single Session Check: Check if user already has an active session
     */
    public static function hasActiveSession(int $userId): bool {
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('SELECT current_session_id, last_pulse FROM cp_users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && !empty($user['current_session_id'])) {
            // Check if pulse is within last 5 minutes (user hasn't closed tab)
            $lastPulse = strtotime($user['last_pulse'] ?? '');
            if ((time() - $lastPulse) < 300) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear session from DB
     */
    public static function clearSessionFromDB(int $userId): void {
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('UPDATE cp_users SET current_session_id = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }

    /**
     * Check if IP is permanently blocked
     */
    public static function isIpBlocked(?string $ip = null): bool {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('SELECT 1 FROM cp_blocked_ips WHERE ip_address = ?');
        $stmt->execute([$ip]);
        return (bool)$stmt->fetch();
    }

    /**
     * Check Brute Force Protection
     */
    public static function checkBruteForce(?string $ip = null): bool {
        global $platform_settings;
        $max = (int)($platform_settings['security_max_attempts'] ?? 5);
        $time = (int)($platform_settings['security_lockout_time'] ?? 15);

        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('SELECT attempts, last_attempt FROM cp_login_attempts WHERE ip_address = ?');
        $stmt->execute([$ip]);
        $data = $stmt->fetch();

        if ($data && $data['attempts'] >= $max) {
            $lastAttempt = strtotime($data['last_attempt']);
            if ((time() - $lastAttempt) < ($time * 60)) {
                return false; // Blocked
            } else {
                self::resetAttempts($ip);
            }
        }
        return true;
    }

    public static function registerFailedAttempt(?string $ip = null): void {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('INSERT INTO cp_login_attempts (ip_address, attempts) VALUES (?, 1) 
                               ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP');
        $stmt->execute([$ip]);
    }

    public static function resetAttempts(?string $ip = null): void {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = \DB::getInstance();
        $stmt = $pdo->prepare('DELETE FROM cp_login_attempts WHERE ip_address = ?');
        $stmt->execute([$ip]);
    }
}

// Global inactivity check
Auth::checkInactivity();
