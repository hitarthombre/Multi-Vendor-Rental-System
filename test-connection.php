#!/usr/bin/env php
<?php
/**
 * Database Connection Test Script
 * 
 * Tests the database connection and displays configuration
 */

require_once __DIR__ . '/src/Database/Connection.php';

use RentalPlatform\Database\Connection;

echo "Multi-Vendor Rental Platform - Database Connection Test\n";
echo str_repeat('=', 80) . "\n\n";

try {
    // Load configuration
    $config = require __DIR__ . '/config/database.php';
    
    echo "Configuration:\n";
    echo "  Host: {$config['host']}\n";
    echo "  Port: {$config['port']}\n";
    echo "  Database: {$config['database']}\n";
    echo "  Username: {$config['username']}\n";
    echo "  Charset: {$config['charset']}\n\n";
    
    // Test connection
    echo "Testing connection...\n";
    $db = Connection::getInstance();
    
    echo "✓ Connection successful!\n\n";
    
    // Get MySQL version
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "MySQL Version: {$result['version']}\n";
    
    // Check if database exists
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "Current Database: {$result['db_name']}\n\n";
    
    // Check if migrations table exists
    $stmt = $db->query("SHOW TABLES LIKE 'migrations'");
    $migrationTableExists = $stmt->rowCount() > 0;
    
    if ($migrationTableExists) {
        echo "✓ Migrations table exists\n";
        
        // Count executed migrations
        $stmt = $db->query("SELECT COUNT(*) as count FROM migrations");
        $result = $stmt->fetch();
        echo "  Executed migrations: {$result['count']}\n";
    } else {
        echo "⚠ Migrations table does not exist\n";
        echo "  Run: php migrate.php\n";
    }
    
    echo "\n";
    
    // List all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Database Tables (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "  - {$table}\n";
        }
    } else {
        echo "No tables found in database.\n";
        echo "Run migrations to create schema: php migrate.php\n";
    }
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Connection test complete!\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "  1. Ensure MySQL is running in XAMPP\n";
    echo "  2. Check database credentials in config/database.php\n";
    echo "  3. Run setup-database.php to create the database\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
