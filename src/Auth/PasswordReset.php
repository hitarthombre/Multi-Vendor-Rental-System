<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Helpers\UUID;

/**
 * Password Reset Service
 * 
 * Handles password reset token generation and validation
 */
class PasswordReset
{
    private const TOKEN_EXPIRY_HOURS = 1; // 1 hour expiry
    
    /**
     * Generate a secure password reset token
     * 
     * @return array ['token' => string, 'expiry' => string]
     */
    public static function generateToken(): array
    {
        $token = bin2hex(random_bytes(32)); // 64 character token
        $expiry = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));
        
        return [
            'token' => $token,
            'expiry' => $expiry
        ];
    }
    
    /**
     * Validate if token is still valid (not expired)
     * 
     * @param string $expiry Token expiry datetime
     * @return bool
     */
    public static function isTokenValid(string $expiry): bool
    {
        $expiryTime = strtotime($expiry);
        $currentTime = time();
        
        return $currentTime < $expiryTime;
    }
    
    /**
     * Hash token for secure storage
     * 
     * @param string $token
     * @return string
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
    
    /**
     * Verify token matches hash
     * 
     * @param string $token
     * @param string $hash
     * @return bool
     */
    public static function verifyToken(string $token, string $hash): bool
    {
        return hash_equals($hash, self::hashToken($token));
    }
}
