<?php

namespace RentalPlatform\Helpers;

/**
 * Security Headers Helper
 * 
 * Provides methods to set security-related HTTP headers
 */
class SecurityHeaders
{
    /**
     * Set all security headers
     */
    public static function setAll(): void
    {
        self::setContentSecurityPolicy();
        self::setXFrameOptions();
        self::setXContentTypeOptions();
        self::setXXSSProtection();
        self::setReferrerPolicy();
        self::setPermissionsPolicy();
    }

    /**
     * Set Content Security Policy header
     */
    public static function setContentSecurityPolicy(): void
    {
        if (headers_sent()) {
            return;
        }

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://checkout.razorpay.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.razorpay.com",
            "frame-src 'self' https://api.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'"
        ];

        header('Content-Security-Policy: ' . implode('; ', $csp));
    }

    /**
     * Set X-Frame-Options header (prevent clickjacking)
     */
    public static function setXFrameOptions(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
    }

    /**
     * Set X-Content-Type-Options header (prevent MIME sniffing)
     */
    public static function setXContentTypeOptions(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Set X-XSS-Protection header
     */
    public static function setXXSSProtection(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-XSS-Protection: 1; mode=block');
    }

    /**
     * Set Referrer-Policy header
     */
    public static function setReferrerPolicy(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Set Permissions-Policy header
     */
    public static function setPermissionsPolicy(): void
    {
        if (headers_sent()) {
            return;
        }

        $policies = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=(self)',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ];

        header('Permissions-Policy: ' . implode(', ', $policies));
    }

    /**
     * Set Strict-Transport-Security header (HSTS)
     * Only use in production with HTTPS
     */
    public static function setHSTS(int $maxAge = 31536000): void
    {
        if (headers_sent()) {
            return;
        }

        // Only set HSTS if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=$maxAge; includeSubDomains; preload");
        }
    }
}
