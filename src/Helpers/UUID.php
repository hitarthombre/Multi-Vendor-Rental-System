<?php

namespace RentalPlatform\Helpers;

/**
 * UUID Generator
 * 
 * Generates RFC 4122 compliant UUIDs (version 4)
 */
class UUID
{
    /**
     * Generate a version 4 (random) UUID
     * 
     * @return string UUID in format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     */
    public static function generate(): string
    {
        $data = random_bytes(16);

        // Set version to 0100 (version 4)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate UUID format
     * 
     * @param string $uuid
     * @return bool
     */
    public static function isValid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        ) === 1;
    }
}
