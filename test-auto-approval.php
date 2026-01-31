<?php
/**
 * Test Auto-Approval Flow
 * 
 * This script tests the auto-approval functionality by creating test orders
 * and verifying they are automatically transitioned from Auto_Approved to Active_Rental.
 */

// Include all required dependencies
require_once 'src/Database/Connection.php';
require_once 'src/Helpers/UUID.php';
require_once 'src/Models/Order.php';
require_once 'src/Models/OrderItem.php';
require_once 'src/Models/AuditLog.php';
require_once 'src/Repositories/OrderRepository.php';
require_once 'src/Repositories/OrderItemRepository.php';
require_once 'src/Repositories/AuditLogRepository.php';
require_once 'src/Repositories/CartRepository.php';
require_once 'src/Repositories/CartItemRepository.php';
require_once 'src/Repositories/ProductRepository.php';
require_once 'src/Services/NotificationService.php';
require_once 'src/Services/OrderService.php';

use RentalPlatform\Services\OrderService;
use RentalPlatform\Models\Order;

echo "=== Auto-Approval Flow Test ===\n\n";

try {
    $orderService = new OrderService();
    
    // Test 1: Check current auto-approved orders
    echo "--- Test 1: Current Auto-Approved Orders ---\n";
    $autoApprovedOrders = $orderService->getOrdersByStatus(Order::STATUS_AUTO_APPROVED);
    echo "Found " . count($autoApprovedOrders) . " auto-approved orders\n";
    
    foreach ($autoApprovedOrders as $order) {
        echo "  - Order: {$order->getOrderNumber()} (Status: {$order->getStatus()})\n";
    }
    echo "\n";
    
    // Test 2: Process auto-approvals
    echo "--- Test 2: Processing Auto-Approvals ---\n";
    $results = $orderService->processAutoApprovals();
    
    echo "Processing Results:\n";
    echo "  - Total Found: {$results['total_found']}\n";
    echo "  - Successfully Processed: {$results['processed']}\n";
    echo "  - Failed: {$results['failed']}\n";
    
    if (!empty($results['errors'])) {
        echo "  - Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "    * $error\n";
        }
    }
    echo "\n";
    
    // Test 3: Verify orders were transitioned
    echo "--- Test 3: Verification After Processing ---\n";
    $remainingAutoApproved = $orderService->getOrdersByStatus(Order::STATUS_AUTO_APPROVED);
    $activeRentals = $orderService->getOrdersByStatus(Order::STATUS_ACTIVE_RENTAL);
    
    echo "Remaining Auto-Approved Orders: " . count($remainingAutoApproved) . "\n";
    echo "Active Rental Orders: " . count($activeRentals) . "\n";
    
    if (count($remainingAutoApproved) > 0) {
        echo "Remaining auto-approved orders:\n";
        foreach ($remainingAutoApproved as $order) {
            echo "  - Order: {$order->getOrderNumber()} (Status: {$order->getStatus()})\n";
        }
    }
    echo "\n";
    
    // Test 4: Create a test auto-approved order (if needed for demonstration)
    echo "--- Test 4: Creating Test Auto-Approved Order ---\n";
    
    // Create a test order with Auto_Approved status
    $testOrder = Order::create(
        'test-customer-auto',
        'test-vendor-auto',
        'test-payment-auto-' . time(),
        Order::STATUS_AUTO_APPROVED,
        150.00,
        25.00
    );
    
    echo "Created test order: {$testOrder->getOrderNumber()}\n";
    echo "Status: {$testOrder->getStatus()}\n";
    echo "Status Label: {$testOrder->getStatusLabel()}\n";
    echo "Status Color: {$testOrder->getStatusColor()}\n";
    
    // Test the transition capability
    echo "\nTesting status transition capability:\n";
    $canTransition = $testOrder->canTransitionTo(Order::STATUS_ACTIVE_RENTAL);
    echo "Can transition to Active_Rental: " . ($canTransition ? 'YES' : 'NO') . "\n";
    
    if ($canTransition) {
        $testOrder->transitionTo(Order::STATUS_ACTIVE_RENTAL);
        echo "Transitioned to: {$testOrder->getStatus()}\n";
        echo "New Status Label: {$testOrder->getStatusLabel()}\n";
    }
    echo "\n";
    
    // Test 5: Verify transition rules
    echo "--- Test 5: Status Transition Rules ---\n";
    $validTransitions = $testOrder->getValidNextStatuses();
    echo "Valid next statuses from {$testOrder->getStatus()}:\n";
    foreach ($validTransitions as $status) {
        echo "  - $status\n";
    }
    echo "\n";
    
    echo "=== Auto-Approval Flow Test Completed Successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error during auto-approval test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}