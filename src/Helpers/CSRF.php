<?php

namespace RentalPlatform\Helpers;

use RentalPlatform\Auth\Session;

/**
 * CSRF Protection Helper
 * 
 * Provides CSRF token generation and validation
 */
class CSRF
{
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_TIME_NAME = 'csrf_token_time';
    private const TOKEN_LIFETIME = 3600; // 1 hour

    /**
     * Generate a new CSRF token
     */
    public static function generateToken(): string
    {
        Session::start();
        
        $token = bin2hex(random_bytes(32));
        
        Session::set(self::TOKEN_NAME, $token);
        Session::set(self::TOKEN_TIME_NAME, time());
        
        return $token;
    }

    /**
     * Get the current CSRF token (generate if not exists)
     */
    public static function getToken(): string
    {
        Session::start();
        
        $token = Session::get(self::TOKEN_NAME);
        $tokenTime = Session::get(self::TOKEN_TIME_NAME);
        
        // Generate new token if doesn't exist or expired
        if (!$token || !$tokenTime || (time() - $tokenTime) > self::TOKEN_LIFETIME) {
            return self::generateToken();
        }
        
        return $token;
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken(string $token): bool
    {
        Session::start();
        
        $sessionToken = Session::get(self::TOKEN_NAME);
        $tokenTime = Session::get(self::TOKEN_TIME_NAME);
        
        if (!$sessionToken || !$tokenTime) {
            return false;
        }
        
        // Check if token expired
        if ((time() - $tokenTime) > self::TOKEN_LIFETIME) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateRequest(): bool
    {
        $token = $_POST[self::TOKEN_NAME] ?? $_GET[self::TOKEN_NAME] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return self::validateToken($token);
    }

    /**
     * Get HTML input field for CSRF token
     */
    public static function getTokenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get token name for forms
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }

    /**
     * Require valid CSRF token or die
     */
    public static function requireToken(): void
    {
        if (!self::validateRequest()) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}
