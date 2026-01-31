<?php
/**
 * Logging Configuration
 * 
 * Configuration for error logging and log management
 */

return [
    // Log Levels (PSR-3 compliant)
    'levels' => [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ],
    
    // Log Files Configuration
    'files' => [
        'error' => [
            'path' => '/logs/error.log',
            'level' => 'error',
            'max_size' => 10 * 1024 * 1024, // 10MB
            'rotate' => true,
            'keep_files' => 5,
        ],
        'application' => [
            'path' => '/logs/application.log',
            'level' => 'info',
            'max_size' => 50 * 1024 * 1024, // 50MB
            'rotate' => true,
            'keep_files' => 10,
        ],
        'security' => [
            'path' => '/logs/security.log',
            'level' => 'warning',
            'max_size' => 20 * 1024 * 1024, // 20MB
            'rotate' => true,
            'keep_files' => 30,
        ],
        'payment' => [
            'path' => '/logs/payment.log',
            'level' => 'info',
            'max_size' => 25 * 1024 * 1024, // 25MB
            'rotate' => true,
            'keep_files' => 15,
        ],
        'email' => [
            'path' => '/logs/email.log',
            'level' => 'info',
            'max_size' => 10 * 1024 * 1024, // 10MB
            'rotate' => true,
            'keep_files' => 7,
        ],
        'audit' => [
            'path' => '/logs/audit.log',
            'level' => 'info',
            'max_size' => 100 * 1024 * 1024, // 100MB
            'rotate' => true,
            'keep_files' => 365, // Keep for 1 year
        ],
    ],
    
    // Log Format
    'format' => [
        'timestamp_format' => 'Y-m-d H:i:s',
        'message_format' => '[{timestamp}] {level}: {message} {context}',
        'include_trace' => true,
        'include_request_id' => true,
    ],
    
    // Error Handling
    'error_handling' => [
        'log_errors' => true,
        'display_errors' => false, // Set to true only in development
        'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
        'log_php_errors' => true,
        'php_error_log' => '/logs/php_errors.log',
    ],
    
    // Performance Logging
    'performance' => [
        'enabled' => true,
        'slow_query_threshold' => 1.0, // seconds
        'memory_threshold' => 128 * 1024 * 1024, // 128MB
        'log_file' => '/logs/performance.log',
    ],
    
    // Security Logging
    'security' => [
        'log_failed_logins' => true,
        'log_access_attempts' => true,
        'log_privilege_escalation' => true,
        'log_file_access' => true,
        'suspicious_activity_threshold' => 5,
    ],
    
    // Log Rotation
    'rotation' => [
        'enabled' => true,
        'schedule' => 'daily', // daily, weekly, monthly
        'compress' => true,
        'cleanup_old' => true,
        'max_age_days' => 90,
    ],
    
    // Monitoring and Alerts
    'monitoring' => [
        'enabled' => true,
        'alert_on_critical' => true,
        'alert_email' => 'admin@rentalhub.com',
        'error_threshold' => 10, // errors per minute
        'disk_space_threshold' => 90, // percentage
    ],
];