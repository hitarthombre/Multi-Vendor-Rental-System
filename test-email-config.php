<?php
/**
 * Email Configuration Test Script
 * 
 * Tests email service configuration and connectivity
 */

require_once 'config/email.php';
require_once 'src/Services/EmailService.php';

use RentalPlatform\Services\EmailService;

echo "=== Email Configuration Test ===\n\n";

try {
    // Load email configuration
    $config = require 'config/email.php';
    echo "✓ Email configuration loaded successfully\n";
    
    // Test SMTP connection
    echo "Testing SMTP connection...\n";
    $emailService = new EmailService();
    
    // Test email sending
    $testEmail = $config['testing']['test_email'];
    $subject = 'RentalHub Email Configuration Test';
    $message = "
        <h2>Email Configuration Test</h2>
        <p>This is a test email to verify that the email service is configured correctly.</p>
        <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>SMTP Host:</strong> {$config['smtp']['host']}</p>
        <p><strong>SMTP Port:</strong> {$config['smtp']['port']}</p>
        <p>If you received this email, the configuration is working properly.</p>
    ";
    
    $result = $emailService->sendEmail($testEmail, 'Test User', $subject, $message);
    
    if ($result) {
        echo "✓ Test email sent successfully to: $testEmail\n";
        echo "✓ Email service is configured correctly\n";
    } else {
        echo "✗ Failed to send test email\n";
        echo "Please check your SMTP configuration\n";
    }
    
    // Display configuration summary
    echo "\n=== Configuration Summary ===\n";
    echo "SMTP Host: {$config['smtp']['host']}\n";
    echo "SMTP Port: {$config['smtp']['port']}\n";
    echo "Encryption: {$config['smtp']['encryption']}\n";
    echo "From Email: {$config['from']['email']}\n";
    echo "From Name: {$config['from']['name']}\n";
    echo "Notifications Enabled: " . ($config['notifications']['enabled'] ? 'Yes' : 'No') . "\n";
    echo "Retry Attempts: {$config['notifications']['retry_attempts']}\n";
    
} catch (Exception $e) {
    echo "✗ Email configuration test failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check SMTP credentials in config/email.php\n";
    echo "2. Ensure Gmail App Password is configured correctly\n";
    echo "3. Verify firewall allows SMTP connections\n";
    echo "4. Check if 2FA is enabled and App Password is used\n";
}

echo "\n=== Test Complete ===\n";