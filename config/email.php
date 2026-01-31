<?php
/**
 * Email Service Configuration
 * 
 * SMTP configuration for email notifications
 * Requirements: 23.3
 */

return [
    // SMTP Configuration
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls', // tls or ssl
        'username' => 'rentalhub.notifications@gmail.com',
        'password' => 'your-app-password-here', // Use App Password for Gmail
        'timeout' => 30,
    ],
    
    // Default Sender Information
    'from' => [
        'email' => 'rentalhub.notifications@gmail.com',
        'name' => 'RentalHub Platform',
    ],
    
    // Email Templates Configuration
    'templates' => [
        'path' => '/templates/email/',
        'extension' => '.html',
        'cache' => false, // Set to true in production
    ],
    
    // Notification Settings
    'notifications' => [
        'enabled' => true,
        'queue' => false, // Set to true for queue-based sending
        'retry_attempts' => 3,
        'retry_delay' => 300, // 5 minutes
    ],
    
    // Email Types Configuration
    'types' => [
        'order_created' => [
            'subject' => 'Order Confirmation - {{order_number}}',
            'template' => 'order_created',
            'priority' => 'high',
        ],
        'order_approved' => [
            'subject' => 'Order Approved - {{order_number}}',
            'template' => 'order_approved',
            'priority' => 'high',
        ],
        'order_rejected' => [
            'subject' => 'Order Rejected - {{order_number}}',
            'template' => 'order_rejected',
            'priority' => 'high',
        ],
        'payment_failure' => [
            'subject' => 'Payment Failed - Order Not Created',
            'template' => 'payment_failure',
            'priority' => 'urgent',
        ],
        'inventory_conflict' => [
            'subject' => 'Order Could Not Be Created - Inventory Conflict',
            'template' => 'inventory_conflict',
            'priority' => 'high',
        ],
        'vendor_timeout_reminder' => [
            'subject' => 'REMINDER: Pending Order Approval - Action Required',
            'template' => 'vendor_timeout_reminder',
            'priority' => 'high',
        ],
        'late_return_vendor' => [
            'subject' => 'Late Return Detected - Fee Application Available',
            'template' => 'late_return_vendor',
            'priority' => 'medium',
        ],
        'late_return_customer' => [
            'subject' => 'Late Return Notice - Additional Fees May Apply',
            'template' => 'late_return_customer',
            'priority' => 'high',
        ],
        'document_timeout_customer' => [
            'subject' => 'Document Upload Required - Order May Be Cancelled',
            'template' => 'document_timeout_customer',
            'priority' => 'urgent',
        ],
        'refund_failure_admin' => [
            'subject' => 'URGENT: Refund Processing Failed - Admin Intervention Required',
            'template' => 'refund_failure_admin',
            'priority' => 'urgent',
        ],
    ],
    
    // Testing Configuration
    'testing' => [
        'enabled' => true,
        'test_email' => 'test@rentalhub.com',
        'log_emails' => true,
        'log_path' => '/logs/email.log',
    ],
    
    // Security Settings
    'security' => [
        'verify_ssl' => true,
        'allow_self_signed' => false,
        'rate_limit' => [
            'enabled' => true,
            'max_per_hour' => 100,
            'max_per_day' => 1000,
        ],
    ],
];