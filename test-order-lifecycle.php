<?php
require_once __DIR__ . '/src/Models/Order.php';
require_once __DIR__ . '/src/Helpers/UUID.php';

use RentalPlatform\Models\Order;

echo "=== Order Model Test ===\n\n";

try {
    echo "--- Testing Order Status Transitions ---\n";
    
    // Create a test order
    $testOrder = Order::create(
        'test-customer-123',
        'test-vendor-456',
        'test-payment-789',
        Order::STATUS_PENDING_VENDOR_APPROVAL,
        100.00,
        20.00
    );
    
    echo "✅ Test order created: {$testOrder->getOrderNumber()}\n";
    echo "   Initial status: {$testOrder->getStatusLabel()}\n";
    
    // Test valid transitions
    echo "\n--- Testing Valid Transitions ---\n";
    
    $validTransitions = $testOrder->getValidNextStatuses();
    echo "Valid next statuses from {$testOrder->getStatusLabel()}:\n";
    foreach ($validTransitions as $status) {
        echo "  - {$status}\n";
    }
    
    // Test transition validation
    echo "\n--- Testing Transition Validation ---\n";
    
    $canApprove = $testOrder->canTransitionTo(Order::STATUS_ACTIVE_RENTAL);
    echo "Can transition to Active Rental: " . ($canApprove ? 'YES' : 'NO') . "\n";
    
    $canComplete = $testOrder->canTransitionTo(Order::STATUS_COMPLETED);
    echo "Can transition to Completed: " . ($canComplete ? 'YES' : 'NO') . "\n";
    
    // Test invalid transition
    try {
        $testOrder->transitionTo(Order::STATUS_COMPLETED);
        echo "❌ ERROR: Invalid transition was allowed\n";
    } catch (Exception $e) {
        echo "✅ Invalid transition correctly rejected: {$e->getMessage()}\n";
    }
    
    // Test valid transition
    try {
        $testOrder->transitionTo(Order::STATUS_ACTIVE_RENTAL);
        echo "✅ Valid transition successful: {$testOrder->getStatusLabel()}\n";
    } catch (Exception $e) {
        echo "❌ ERROR: Valid transition failed: {$e->getMessage()}\n";
    }
    
    // Test status helper methods
    echo "\n--- Testing Status Helper Methods ---\n";
    echo "Is active: " . ($testOrder->isActive() ? 'YES' : 'NO') . "\n";
    echo "Is completed: " . ($testOrder->isCompleted() ? 'YES' : 'NO') . "\n";
    echo "Requires approval: " . ($testOrder->requiresVendorApproval() ? 'YES' : 'NO') . "\n";
    
    // Test status colors and labels
    echo "\n--- Testing Status Display ---\n";
    $allStatuses = [
        Order::STATUS_PAYMENT_SUCCESSFUL,
        Order::STATUS_PENDING_VENDOR_APPROVAL,
        Order::STATUS_AUTO_APPROVED,
        Order::STATUS_ACTIVE_RENTAL,
        Order::STATUS_COMPLETED,
        Order::STATUS_REJECTED,
        Order::STATUS_REFUNDED
    ];
    
    foreach ($allStatuses as $status) {
        $tempOrder = Order::create('test', 'test', 'test', $status, 100);
        echo "Status: {$status}\n";
        echo "  Label: {$tempOrder->getStatusLabel()}\n";
        echo "  Color: {$tempOrder->getStatusColor()}\n";
    }
    
    // Test complete lifecycle
    echo "\n--- Testing Complete Lifecycle ---\n";
    $lifecycleOrder = Order::create('test', 'test', 'test', Order::STATUS_PENDING_VENDOR_APPROVAL, 100);
    echo "1. Created: {$lifecycleOrder->getStatusLabel()}\n";
    
    $lifecycleOrder->transitionTo(Order::STATUS_ACTIVE_RENTAL);
    echo "2. Approved: {$lifecycleOrder->getStatusLabel()}\n";
    
    $lifecycleOrder->transitionTo(Order::STATUS_COMPLETED);
    echo "3. Completed: {$lifecycleOrder->getStatusLabel()}\n";
    
    echo "\n--- Testing Order Number Generation ---\n";
    $order1 = Order::create('test', 'test', 'test', Order::STATUS_PAYMENT_SUCCESSFUL, 100);
    $order2 = Order::create('test', 'test', 'test', Order::STATUS_PAYMENT_SUCCESSFUL, 100);
    
    echo "Order 1: {$order1->getOrderNumber()}\n";
    echo "Order 2: {$order2->getOrderNumber()}\n";
    echo "Unique: " . ($order1->getOrderNumber() !== $order2->getOrderNumber() ? 'YES' : 'NO') . "\n";
    
    echo "\n=== All Tests Completed Successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}