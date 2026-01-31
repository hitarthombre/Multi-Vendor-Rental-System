<?php
/**
 * Logging Setup Script
 * 
 * Sets up error logging configuration and tests logging functionality
 */

require_once 'config/logging.php';
require_once 'src/Services/LoggingService.php';

use RentalPlatform\Services\LoggingService;

echo "=== Logging Setup Script ===\n\n";

try {
    $config = require 'config/logging.php';
    
    // Create logs directory
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        if (mkdir($logsDir, 0755, true)) {
            echo "✓ Created logs directory\n";
        } else {
            echo "✗ Failed to create logs directory\n";
            exit(1);
        }
    } else {
        echo "✓ Logs directory already exists\n";
    }
    
    // Create .htaccess to protect logs directory
    $htaccessContent = "# Deny all access to log files\nOrder Deny,Allow\nDeny from all\n";
    $htaccessPath = $logsDir . '/.htaccess';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "✓ Protected logs directory\n";
    } else {
        echo "✗ Failed to protect logs directory\n";
    }
    
    // Create index.php to prevent directory listing
    $indexContent = "<?php\nheader('HTTP/1.0 403 Forbidden');\nexit('Access denied');\n";
    $indexPath = $logsDir . '/index.php';
    
    if (file_put_contents($indexPath, $indexContent)) {
        echo "✓ Added directory protection\n";
    } else {
        echo "✗ Failed to add directory protection\n";
    }
    
    // Configure PHP error logging
    echo "\nConfiguring PHP error logging...\n";
    
    $phpErrorLog = $logsDir . '/php_errors.log';
    ini_set('log_errors', 1);
    ini_set('error_log', $phpErrorLog);
    ini_set('display_errors', $config['error_handling']['display_errors'] ? 1 : 0);
    ini_set('error_reporting', $config['error_handling']['error_reporting']);
    
    echo "✓ PHP error logging configured\n";
    echo "✓ Error log file: $phpErrorLog\n";
    echo "✓ Display errors: " . ($config['error_handling']['display_errors'] ? 'Enabled' : 'Disabled') . "\n";
    
    // Test logging service
    echo "\nTesting logging service...\n";
    
    $logger = new LoggingService();
    
    // Test different log levels
    $logger->info('Logging service initialized', ['test' => true]);
    $logger->warning('This is a test warning', ['component' => 'setup']);
    $logger->error('This is a test error', ['component' => 'setup']);
    
    // Test specialized logging
    $logger->security('test_security_event', ['action' => 'setup_test']);
    $logger->audit('test_action', 'System', 'setup', 'admin', ['test' => true]);
    $logger->performance('setup_test', 0.1, memory_get_usage(), ['operation' => 'test']);
    
    echo "✓ Basic logging tests completed\n";
    
    // Display log file information
    echo "\n=== Log Files Configuration ===\n";
    
    foreach ($config['files'] as $name => $fileConfig) {
        $logPath = __DIR__ . $fileConfig['path'];
        echo "Log: $name\n";
        echo "  Path: {$fileConfig['path']}\n";
        echo "  Level: {$fileConfig['level']}\n";
        echo "  Max Size: " . ($fileConfig['max_size'] / 1024 / 1024) . "MB\n";
        echo "  Rotation: " . ($fileConfig['rotate'] ? 'Enabled' : 'Disabled') . "\n";
        echo "  Keep Files: {$fileConfig['keep_files']}\n";
        
        if (file_exists($logPath)) {
            echo "  Current Size: " . round(filesize($logPath) / 1024, 2) . "KB\n";
        } else {
            echo "  Status: Not created yet\n";
        }
        echo "\n";
    }
    
    // Test log statistics
    echo "=== Log Statistics ===\n";
    $stats = $logger->getLogStatistics();
    
    foreach ($stats as $logName => $stat) {
        echo "$logName: {$stat['size_mb']}MB, {$stat['lines']} lines, modified: {$stat['modified']}\n";
    }
    
    // Create log rotation cron job suggestion
    echo "\n=== Cron Job Setup ===\n";
    echo "Add this to your crontab for daily log rotation:\n";
    echo "0 2 * * * php " . __DIR__ . "/cron/rotate-logs.php\n\n";
    
    echo "✓ Logging setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Logging setup failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check directory permissions (logs directory should be writable)\n";
    echo "2. Ensure PHP has write access to the logs directory\n";
    echo "3. Verify disk space availability\n";
    echo "4. Check PHP configuration for error logging\n";
}

echo "\n=== Setup Complete ===\n";