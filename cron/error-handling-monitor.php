<?php
/**
 * Error Handling Monitor Cron Job
 * 
 * Handles Tasks 28.6, 28.7, 28.8 - Vendor timeouts, late returns, document timeouts
 * 
 * Run this script periodically (e.g., every hour) to monitor and handle:
 * - Vendor approval timeouts
 * - Late returns
 * - Document upload timeouts
 * 
 * Usage: php cron/error-handling-monitor.php
 */

require_once __DIR__ . '/../src/Services/ErrorHandlingService.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/DocumentRepository.php';

use RentalPlatform\Services\ErrorHandlingService;
use RentalPlatform\Services\OrderService;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\DocumentRepository;
use RentalPlatform\Models\Order;

echo "Starting Error Handling Monitor - " . date('Y-m-d H:i:s') . "\n";

$errorHandlingService = new ErrorHandlingService();
$orderService = new OrderService();
$orderRepo = new OrderRepository();
$documentRepo = new DocumentRepository();

$results = [
    'vendor_timeouts' => 0,
    'late_returns' => 0,
    'document_timeouts' => 0,
    'errors' => []
];

try {
    // Task 28.6: Check for vendor approval timeouts
    echo "Checking vendor approval timeouts...\n";
    $pendingOrders = $orderRepo->findByStatus(Order::STATUS_PENDING_VENDOR_APPROVAL);
    
    foreach ($pendingOrders as $order) {
        $createdAt = $order->getCreatedAt();
        $hoursElapsed = (time() - $createdAt->getTimestamp()) / 3600;
        
        // Send reminder after 24 hours, escalate after 48 hours
        if ($hoursElapsed >= 24) {
            try {
                $errorHandlingService->handleVendorTimeout(
                    $order->getId(),
                    $order->getVendorId(),
                    (int)$hoursElapsed,
                    $hoursElapsed >= 72 // Enable auto-cancellation after 72 hours
                );
                $results['vendor_timeouts']++;
                echo "  - Handled vendor timeout for order {$order->getOrderNumber()} ({$hoursElapsed} hours)\n";
            } catch (Exception $e) {
                $results['errors'][] = "Vendor timeout handling failed for order {$order->getId()}: " . $e->getMessage();
                echo "  - ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Task 28.7: Check for late returns
    echo "Checking for late returns...\n";
    $activeRentals = $orderRepo->findByStatus(Order::STATUS_ACTIVE_RENTAL);
    
    foreach ($activeRentals as $order) {
        // Get order items to check rental end dates
        $orderItems = $orderService->getOrderDetails($order->getId())['items'];
        
        foreach ($orderItems as $item) {
            if (isset($item['end_date'])) {
                $endDate = new DateTime($item['end_date']);
                $now = new DateTime();
                
                if ($now > $endDate) {
                    $daysLate = $now->diff($endDate)->days;
                    $lateFeePerDay = 100.0; // Default late fee - could be configurable per product
                    
                    try {
                        $errorHandlingService->handleLateReturn(
                            $order->getId(),
                            $order->getVendorId(),
                            $daysLate,
                            $lateFeePerDay
                        );
                        $results['late_returns']++;
                        echo "  - Handled late return for order {$order->getOrderNumber()} ({$daysLate} days late)\n";
                    } catch (Exception $e) {
                        $results['errors'][] = "Late return handling failed for order {$order->getId()}: " . $e->getMessage();
                        echo "  - ERROR: " . $e->getMessage() . "\n";
                    }
                    break; // Only process once per order
                }
            }
        }
    }
    
    // Task 28.8: Check for document upload timeouts
    echo "Checking document upload timeouts...\n";
    $ordersRequiringDocs = $orderRepo->getOrdersRequiringDocuments();
    
    foreach ($ordersRequiringDocs as $order) {
        $createdAt = $order->getCreatedAt();
        $hoursElapsed = (time() - $createdAt->getTimestamp()) / 3600;
        
        // Check if documents are still missing after 48 hours
        if ($hoursElapsed >= 48) {
            try {
                $documents = $documentRepo->findByOrderId($order->getId());
                $requiredDocTypes = ['identity_proof', 'address_proof']; // Could be configurable
                $uploadedDocTypes = array_map(fn($doc) => $doc->getDocumentType(), $documents);
                $missingDocs = array_diff($requiredDocTypes, $uploadedDocTypes);
                
                if (!empty($missingDocs)) {
                    $errorHandlingService->handleDocumentUploadTimeout(
                        $order->getId(),
                        $order->getCustomerId(),
                        (int)$hoursElapsed,
                        $missingDocs
                    );
                    $results['document_timeouts']++;
                    echo "  - Handled document timeout for order {$order->getOrderNumber()} ({$hoursElapsed} hours)\n";
                }
            } catch (Exception $e) {
                $results['errors'][] = "Document timeout handling failed for order {$order->getId()}: " . $e->getMessage();
                echo "  - ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Summary
    echo "\nError Handling Monitor Summary:\n";
    echo "- Vendor timeouts handled: {$results['vendor_timeouts']}\n";
    echo "- Late returns handled: {$results['late_returns']}\n";
    echo "- Document timeouts handled: {$results['document_timeouts']}\n";
    echo "- Errors encountered: " . count($results['errors']) . "\n";
    
    if (!empty($results['errors'])) {
        echo "\nErrors:\n";
        foreach ($results['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    // Log the monitoring run
    $errorHandlingService->logError(
        'monitoring_run_completed',
        'System',
        'cron',
        'system',
        [
            'vendor_timeouts_handled' => $results['vendor_timeouts'],
            'late_returns_handled' => $results['late_returns'],
            'document_timeouts_handled' => $results['document_timeouts'],
            'errors_count' => count($results['errors']),
            'run_duration_seconds' => time() - $_SERVER['REQUEST_TIME']
        ]
    );
    
} catch (Exception $e) {
    echo "CRITICAL ERROR in error handling monitor: " . $e->getMessage() . "\n";
    
    // Log critical error
    try {
        $errorHandlingService->logError(
            'monitoring_critical_error',
            'System',
            'cron',
            'system',
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]
        );
    } catch (Exception $logError) {
        echo "Failed to log critical error: " . $logError->getMessage() . "\n";
    }
    
    exit(1);
}

echo "Error Handling Monitor completed - " . date('Y-m-d H:i:s') . "\n";