<?php

namespace RentalPlatform\Helpers;

/**
 * Simple File-Based Cache Helper
 * 
 * Provides basic caching functionality using file system
 * Can be replaced with Redis/Memcached in production
 */
class Cache
{
    private static string $cacheDir = __DIR__ . '/../../cache';
    private static int $defaultTTL = 3600; // 1 hour

    /**
     * Initialize cache directory
     */
    private static function init(): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Get cache file path for a key
     */
    private static function getFilePath(string $key): string
    {
        $hash = md5($key);
        return self::$cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Set a cache value
     */
    public static function set(string $key, $value, int $ttl = null): bool
    {
        self::init();
        
        $ttl = $ttl ?? self::$defaultTTL;
        $expiry = time() + $ttl;
        
        $data = [
            'expiry' => $expiry,
            'value' => $value
        ];
        
        $filepath = self::getFilePath($key);
        $serialized = serialize($data);
        
        return file_put_contents($filepath, $serialized, LOCK_EX) !== false;
    }

    /**
     * Get a cache value
     */
    public static function get(string $key, $default = null)
    {
        self::init();
        
        $filepath = self::getFilePath($key);
        
        if (!file_exists($filepath)) {
            return $default;
        }
        
        $contents = file_get_contents($filepath);
        if ($contents === false) {
            return $default;
        }
        
        $data = unserialize($contents);
        
        if (!is_array($data) || !isset($data['expiry']) || !isset($data['value'])) {
            return $default;
        }
        
        // Check if expired
        if (time() > $data['expiry']) {
            self::delete($key);
            return $default;
        }
        
        return $data['value'];
    }

    /**
     * Check if a cache key exists and is not expired
     */
    public static function has(string $key): bool
    {
        self::init();
        
        $filepath = self::getFilePath($key);
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        $contents = file_get_contents($filepath);
        if ($contents === false) {
            return false;
        }
        
        $data = unserialize($contents);
        
        if (!is_array($data) || !isset($data['expiry'])) {
            return false;
        }
        
        // Check if expired
        if (time() > $data['expiry']) {
            self::delete($key);
            return false;
        }
        
        return true;
    }

    /**
     * Delete a cache entry
     */
    public static function delete(string $key): bool
    {
        self::init();
        
        $filepath = self::getFilePath($key);
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return true;
    }

    /**
     * Clear all cache entries
     */
    public static function clear(): bool
    {
        self::init();
        
        $files = glob(self::$cacheDir . '/*.cache');
        
        if ($files === false) {
            return false;
        }
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Get or set cache value (cache-aside pattern)
     */
    public static function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Clean up expired cache entries
     */
    public static function cleanup(): int
    {
        self::init();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $cleaned = 0;
        
        if ($files === false) {
            return 0;
        }
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            
            $data = unserialize($contents);
            
            if (!is_array($data) || !isset($data['expiry'])) {
                unlink($file);
                $cleaned++;
                continue;
            }
            
            // Delete if expired
            if (time() > $data['expiry']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    /**
     * Set cache directory
     */
    public static function setCacheDir(string $dir): void
    {
        self::$cacheDir = $dir;
    }

    /**
     * Set default TTL
     */
    public static function setDefaultTTL(int $ttl): void
    {
        self::$defaultTTL = $ttl;
    }
}
