<?php
/**
 * Razorpay Payment Gateway Configuration
 * 
 * Configuration for Razorpay integration
 * Requirements: 23.4
 */

return [
    // Test Environment Configuration
    'test' => [
        'key_id' => 'rzp_test_S6DaGQn3cdtVFp',
        'key_secret' => 'OiZT21gCnxns0Gk5rND4P9W4',
        'webhook_secret' => '', // Set this when configuring webhooks
    ],
    
    // Production Environment Configuration (for future use)
    'live' => [
        'key_id' => '', // Set production key ID
        'key_secret' => '', // Set production key secret
        'webhook_secret' => '', // Set production webhook secret
    ],
    
    // Current Environment
    'environment' => 'test', // Change to 'live' for production
    
    // Webhook Configuration
    'webhooks' => [
        'endpoint' => '/api/webhooks/razorpay.php',
        'events' => [
            'payment.captured',
            'payment.failed',
            'refund.created',
            'refund.processed',
        ],
    ],
    
    // Payment Configuration
    'payment' => [
        'currency' => 'INR',
        'timeout' => 900, // 15 minutes
        'description' => 'Multi-Vendor Rental Platform Payment',
        'theme' => [
            'color' => '#3B82F6', // Blue theme
        ],
        'prefill' => [
            'method' => 'card',
        ],
    ],
    
    // Refund Configuration
    'refund' => [
        'speed' => 'normal', // normal or optimum
        'notes' => [
            'reason' => 'Order cancellation',
        ],
    ],
    
    // Security Settings
    'security' => [
        'verify_signature' => true,
        'log_requests' => true,
        'log_responses' => true,
    ],
];