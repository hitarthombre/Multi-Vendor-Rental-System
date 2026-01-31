<?php
/**
 * File Storage Configuration
 * 
 * Configuration for secure file storage and access
 * Requirements: 23.5
 */

return [
    // Storage Paths
    'paths' => [
        'root' => '/xampp/htdocs/Multi-Vendor-Rental-System/storage',
        'public' => '/xampp/htdocs/Multi-Vendor-Rental-System/public/storage',
        'private' => '/xampp/htdocs/Multi-Vendor-Rental-System/storage/private',
        'temp' => '/xampp/htdocs/Multi-Vendor-Rental-System/storage/temp',
        'logs' => '/xampp/htdocs/Multi-Vendor-Rental-System/logs',
    ],
    
    // Document Storage
    'documents' => [
        'path' => '/storage/private/documents',
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'naming_strategy' => 'uuid', // uuid, timestamp, original
        'encryption' => false, // Set to true for sensitive documents
    ],
    
    // Product Images
    'product_images' => [
        'path' => '/public/storage/products',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'thumbnails' => [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600],
        ],
        'quality' => 85,
    ],
    
    // User Avatars
    'avatars' => [
        'path' => '/public/storage/avatars',
        'max_size' => 2 * 1024 * 1024, // 2MB
        'allowed_types' => ['jpg', 'jpeg', 'png'],
        'dimensions' => ['width' => 200, 'height' => 200],
        'quality' => 90,
    ],
    
    // Vendor Logos
    'vendor_logos' => [
        'path' => '/public/storage/vendors',
        'max_size' => 1 * 1024 * 1024, // 1MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'svg'],
        'dimensions' => ['width' => 300, 'height' => 100],
        'quality' => 95,
    ],
    
    // Temporary Files
    'temp' => [
        'path' => '/storage/temp',
        'cleanup_interval' => 3600, // 1 hour
        'max_age' => 86400, // 24 hours
    ],
    
    // Security Settings
    'security' => [
        'scan_uploads' => true,
        'quarantine_suspicious' => true,
        'virus_scan' => false, // Enable if antivirus is available
        'access_control' => [
            'documents' => 'private', // private, public, authenticated
            'images' => 'public',
            'logs' => 'admin_only',
        ],
    ],
    
    // Access Control
    'permissions' => [
        'documents' => [
            'customer' => ['read_own'],
            'vendor' => ['read_own'],
            'admin' => ['read_all', 'write_all', 'delete_all'],
        ],
        'product_images' => [
            'customer' => ['read_all'],
            'vendor' => ['read_all', 'write_own', 'delete_own'],
            'admin' => ['read_all', 'write_all', 'delete_all'],
        ],
    ],
    
    // Backup Configuration
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention' => 30, // days
        'path' => '/storage/backups',
        'compress' => true,
    ],
];