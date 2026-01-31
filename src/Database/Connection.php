<?php

namespace RentalPlatform\Database;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * 
 * Provides singleton database connection with connection pooling support
 */
class Connection
{
    private static ?PDO $instance = null;
    private static array $config;

    /**
     * Get database connection instance
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$config = require __DIR__ . '/../../config/database.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['database'],
                self::$config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    self::$config['options']
                );
            } catch (PDOException $e) {
                throw new PDOException(
                    "Database connection failed: " . $e->getMessage(),
                    (int)$e->getCode()
                );
            }
        }

        return self::$instance;
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Check if in transaction
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
}
