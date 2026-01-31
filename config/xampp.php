<?php
/**
 * XAMPP Environment Configuration
 * 
 * Configuration settings for XAMPP development environment
 * Requirements: 23.1, 23.2
 */

return [
    // Apache Configuration
    'apache' => [
        'port' => 8081,
        'document_root' => '/xampp/htdocs/Multi-Vendor-Rental-System/public',
        'server_name' => 'localhost',
        'base_url' => 'http://localhost:8081',
    ],
    
    // MySQL Configuration
    'mysql' => [
        'port' => 3306,
        'host' => 'localhost',
        'username' => 'root',
        'password' => '', // Default XAMPP password
        'database' => 'rental_platform',
    ],
    
    // phpMyAdmin Configuration
    'phpmyadmin' => [
        'url' => 'http://localhost/phpmyadmin',
        'username' => 'root',
        'password' => '',
    ],
    
    // PHP Configuration
    'php' => [
        'version' => '8.1+',
        'extensions' => [
            'pdo',
            'pdo_mysql',
            'mbstring',
            'openssl',
            'curl',
            'gd',
            'fileinfo',
            'json',
        ],
        'settings' => [
            'upload_max_filesize' => '10M',
            'post_max_size' => '10M',
            'max_execution_time' => 300,
            'memory_limit' => '256M',
        ],
    ],
    
    // Security Settings
    'security' => [
        'display_errors' => false, // Set to true only in development
        'log_errors' => true,
        'error_log' => '/xampp/htdocs/Multi-Vendor-Rental-System/logs/php_errors.log',
    ],
];