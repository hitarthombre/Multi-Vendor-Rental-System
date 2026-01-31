<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Models\User;

/**
 * Session Manager
 * 
 * Handles secure session management for authenticated users
 */
class Session
{
    private const SESSION_KEY_USER_ID = 'user_id';
    private const SESSION_KEY_USERNAME = 'username';
    private const SESSION_KEY_EMAIL = 'email';
    private const SESSION_KEY_ROLE = 'role';
    private const SESSION_KEY_LAST_ACTIVITY = 'last_activity';
    private const SESSION_KEY_IP_ADDRESS = 'ip_address';
    private const SESSION_KEY_USER_AGENT = 'user_agent';

    /**
     * Session timeout in seconds (30 minutes)
     */
    private const SESSION_TIMEOUT = 1800;

    /**
     * Start session if not already started
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session settings (only if headers not sent)
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_secure', '0'); // Set to 1 in production with HTTPS
                ini_set('session.cookie_samesite', 'Strict');
            }
            
            // Suppress warning if headers already sent (e.g., in testing)
            @session_start();
        }
    }

    /**
     * Create session for authenticated user
     * 
     * @param User $user
     * @return void
     */
    public static function create(User $user): void
    {
        self::start();
        
        // Regenerate session ID to prevent session fixation (only if session is active)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        // Store user information in session
        $_SESSION[self::SESSION_KEY_USER_ID] = $user->getId();
        $_SESSION[self::SESSION_KEY_USERNAME] = $user->getUsername();
        $_SESSION[self::SESSION_KEY_EMAIL] = $user->getEmail();
        $_SESSION[self::SESSION_KEY_ROLE] = $user->getRole();
        $_SESSION[self::SESSION_KEY_LAST_ACTIVITY] = time();
        $_SESSION[self::SESSION_KEY_IP_ADDRESS] = self::getClientIp();
        $_SESSION[self::SESSION_KEY_USER_AGENT] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        
        if (!isset($_SESSION[self::SESSION_KEY_USER_ID])) {
            return false;
        }
        
        // Check session timeout
        if (self::isExpired()) {
            self::destroy();
            return false;
        }
        
        // Check if IP address or user agent changed (potential session hijacking)
        if (!self::validateSessionIntegrity()) {
            self::destroy();
            return false;
        }
        
        // Update last activity time
        $_SESSION[self::SESSION_KEY_LAST_ACTIVITY] = time();
        
        return true;
    }

    /**
     * Check if session has expired
     * 
     * @return bool
     */
    private static function isExpired(): bool
    {
        if (!isset($_SESSION[self::SESSION_KEY_LAST_ACTIVITY])) {
            return true;
        }
        
        $inactiveTime = time() - $_SESSION[self::SESSION_KEY_LAST_ACTIVITY];
        return $inactiveTime > self::SESSION_TIMEOUT;
    }

    /**
     * Validate session integrity (check for session hijacking)
     * 
     * @return bool
     */
    private static function validateSessionIntegrity(): bool
    {
        // Check IP address
        if (isset($_SESSION[self::SESSION_KEY_IP_ADDRESS])) {
            if ($_SESSION[self::SESSION_KEY_IP_ADDRESS] !== self::getClientIp()) {
                return false;
            }
        }
        
        // Check user agent
        if (isset($_SESSION[self::SESSION_KEY_USER_AGENT])) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION[self::SESSION_KEY_USER_AGENT] !== $currentUserAgent) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get authenticated user ID
     * 
     * @return string|null
     */
    public static function getUserId(): ?string
    {
        self::start();
        return $_SESSION[self::SESSION_KEY_USER_ID] ?? null;
    }

    /**
     * Get authenticated username
     * 
     * @return string|null
     */
    public static function getUsername(): ?string
    {
        self::start();
        return $_SESSION[self::SESSION_KEY_USERNAME] ?? null;
    }

    /**
     * Get authenticated user email
     * 
     * @return string|null
     */
    public static function getEmail(): ?string
    {
        self::start();
        return $_SESSION[self::SESSION_KEY_EMAIL] ?? null;
    }

    /**
     * Get authenticated user role
     * 
     * @return string|null
     */
    public static function getRole(): ?string
    {
        self::start();
        return $_SESSION[self::SESSION_KEY_ROLE] ?? null;
    }

    /**
     * Check if user has a specific role
     * 
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        return self::getRole() === $role;
    }

    /**
     * Check if user is a customer
     * 
     * @return bool
     */
    public static function isCustomer(): bool
    {
        return self::hasRole(User::ROLE_CUSTOMER);
    }

    /**
     * Check if user is a vendor
     * 
     * @return bool
     */
    public static function isVendor(): bool
    {
        return self::hasRole(User::ROLE_VENDOR);
    }

    /**
     * Check if user is an administrator
     * 
     * @return bool
     */
    public static function isAdministrator(): bool
    {
        return self::hasRole(User::ROLE_ADMINISTRATOR);
    }

    /**
     * Destroy session (logout)
     * 
     * @return void
     */
    public static function destroy(): void
    {
        self::start();
        
        // Unset all session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session (only if active)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    private static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get session data as array
     * 
     * @return array
     */
    public static function toArray(): array
    {
        self::start();
        
        return [
            'user_id' => self::getUserId(),
            'username' => self::getUsername(),
            'email' => self::getEmail(),
            'role' => self::getRole(),
            'last_activity' => $_SESSION[self::SESSION_KEY_LAST_ACTIVITY] ?? null,
            'is_authenticated' => self::isAuthenticated()
        ];
    }
}
