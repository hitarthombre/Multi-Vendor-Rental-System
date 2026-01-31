<?php
/**
 * Auto-Approval Processor
 * 
 * This script processes orders that are marked as Auto_Approved and transitions them
 * to Active_Rental status. It should be run periodically via cron job.
 * 
 * Recommended cron schedule: Every 5 minutes
 * */5 * * * * /usr/bin/php /path/to/your/project/cron/process-auto-approvals.php
 */

require_once __DIR__ . '/../src/Services/OrderService.php';

use RentalPlatform\Services\OrderService;

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/auto-approval.log');

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    $startTime = microtime(true);
    $timestamp = date('Y-m-d H:i:s');
    
    echo "[$timestamp] Starting auto-approval processing...\n";
    
    $orderService = new OrderService();
    
    // Process auto-approvals and get results
    $results = $orderService->processAutoApprovals();
    
    echo "[$timestamp] Found {$results['total_found']} orders to auto-approve\n";
    
    if ($results['total_found'] > 0) {
        echo "[$timestamp] Successfully processed {$results['processed']} orders\n";
        
        if ($results['failed'] > 0) {
            echo "[$timestamp] Failed to process {$results['failed']} orders\n";
            foreach ($results['errors'] as $error) {
                echo "[$timestamp] Error: $error\n";
            }
        }
    } else {
        echo "[$timestamp] No orders to process\n";
    }
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "[$timestamp] Auto-approval processing completed in {$executionTime}ms\n";
    
    // Log summary to file
    $logMessage = "[$timestamp] Auto-approval summary: {$results['total_found']} found, {$results['processed']} processed, {$results['failed']} failed";
    error_log($logMessage);
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "[$timestamp] Error in auto-approval processing: " . $e->getMessage();
    
    echo $errorMessage . "\n";
    error_log($errorMessage);
    
    // Exit with error code for cron monitoring
    exit(1);
}

// Exit successfully
exit(0);