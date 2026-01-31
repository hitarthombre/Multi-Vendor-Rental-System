<?php
/**
 * Database Configuration
 * 
 * Configuration for MySQL database connection in XAMPP environment
 */

return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'rental_platform',
    'username' => 'root',
    'password' => '', // Default XAMPP MySQL password is empty
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
