<?php
/**
 * Notification Processor Cron Job
 * 
 * Processes pending notifications and retries failed ones
 * Run this script every 5-10 minutes via cron
 * 
 * Usage: php cron/process-notifications.php
 */

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Helpers/UUID.php';
require_once __DIR__ . '/../src/Models/Notification.php';
require_once __DIR__ . '/../src/Repositories/NotificationRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/EmailService.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';

use RentalPlatform\Services\NotificationService;

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/notification-cron.log');

$startTime = microtime(true);
$logPrefix = "[" . date('Y-m-d H:i:s') . "] NOTIFICATION_CRON: ";

try {
    echo $logPrefix . "Starting notification processing...\n";
    
    $notificationService = new NotificationService();
    
    // Process pending notifications
    echo $logPrefix . "Processing pending notifications...\n";
    $pendingProcessed = $notificationService->processPendingNotifications(100);
    echo $logPrefix . "Processed {$pendingProcessed} pending notifications\n";
    
    // Retry failed notifications (with backoff - only retry failures older than 30 minutes)
    echo $logPrefix . "Retrying failed notifications...\n";
    $failedRetried = $notificationService->retryFailedNotifications(50);
    echo $logPrefix . "Retried {$failedRetried} failed notifications\n";
    
    // Clean up old notifications (older than 30 days)
    echo $logPrefix . "Cleaning up old notifications...\n";
    $notificationRepo = new \RentalPlatform\Repositories\NotificationRepository();
    $deleted = $notificationRepo->deleteOldNotifications(30);
    echo $logPrefix . "Deleted {$deleted} old notifications\n";
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo $logPrefix . "Notification processing completed in {$executionTime}ms\n";
    echo $logPrefix . "Summary: {$pendingProcessed} pending, {$failedRetried} retried, {$deleted} cleaned up\n";
    
    // Log to file as well
    error_log($logPrefix . "SUCCESS: Processed {$pendingProcessed} pending, retried {$failedRetried} failed, deleted {$deleted} old notifications in {$executionTime}ms");
    
} catch (Exception $e) {
    $errorMsg = $logPrefix . "ERROR: " . $e->getMessage();
    echo $errorMsg . "\n";
    error_log($errorMsg);
    error_log($logPrefix . "Stack trace: " . $e->getTraceAsString());
    exit(1);
}