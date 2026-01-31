#!/usr/bin/env php
<?php
/**
 * Database Setup Script
 * 
 * Creates the database if it doesn't exist and runs initial setup
 */

$config = require __DIR__ . '/config/database.php';

try {
    // Connect without database selection to create database
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "Connected to MySQL server.\n";
    
    // Create database if it doesn't exist
    $dbName = $config['database'];
    $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` 
            CHARACTER SET {$config['charset']} 
            COLLATE {$config['collation']}";
    
    $pdo->exec($sql);
    echo "Database '{$dbName}' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE `{$dbName}`");
    echo "Selected database '{$dbName}'.\n";
    
    echo "\nDatabase setup complete!\n";
    echo "You can now run migrations using: php migrate.php\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
