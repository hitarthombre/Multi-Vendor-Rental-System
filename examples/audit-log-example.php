<?php

/**
 * Audit Log Module Usage Examples
 * 
 * This file demonstrates how to use the Audit Log module
 * for tracking sensitive actions in the Multi-Vendor Rental Platform.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Services\AuditLogger;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\AuditLogRepository;

// Get database connection
$db = Connection::getInstance();

// Create audit logger
$auditLogger = new AuditLogger($db);

echo "=== Audit Log Module Examples ===\n\n";

// Example 1: Log Order Status Change
echo "1. Logging Order Status Change\n";
echo "--------------------------------\n";

$orderId = 'order-' . uniqid();
$vendorId = 'vendor-' . uniqid();

$auditLogger->logOrderStatusChange(
    $orderId,
    'Pending_Vendor_Approval',
    'Active_Rental',
    $vendorId
);

echo "✓ Logged order status change from Pending_Vendor_Approval to Active_Rental\n\n";

// Example 2: Log Order Approval
echo "2. Logging Order Approval\n";
echo "-------------------------\n";

$auditLogger->logOrderApproval($orderId, $vendorId);
echo "✓ Logged order approval by vendor\n\n";

// Example 3: Log Payment Verification
echo "3. Logging Payment Verification\n";
echo "-------------------------------\n";

$paymentId = 'payment-' . uniqid();

$auditLogger->logPaymentVerification(
    $paymentId,
    true,
    [
        'amount' => 150.00,
        'currency' => 'INR',
        'method' => 'razorpay'
    ]
);

echo "✓ Logged successful payment verification\n\n";

// Example 4: Log Failed Login Attempt
echo "4. Logging Failed Login Attempt\n";
echo "-------------------------------\n";

$userId = 'user-' . uniqid();

$auditLogger->logLogin($userId, false, 'john_doe');
echo "✓ Logged failed login attempt\n\n";

// Example 5: Log Permission Denied
echo "5. Logging Permission Denied\n";
echo "----------------------------\n";

$auditLogger->logPermissionDenied('product', 'delete', $userId);
echo "✓ Logged permission denied event\n\n";

// Example 6: Log Document Upload
echo "6. Logging Document Upload\n";
echo "--------------------------\n";

$documentId = 'doc-' . uniqid();
$customerId = 'customer-' . uniqid();

$auditLogger->logDocumentUpload(
    $documentId,
    $orderId,
    'ID Proof',
    $customerId
);

echo "✓ Logged document upload\n\n";

// Example 7: Log Product Creation
echo "7. Logging Product Creation\n";
echo "---------------------------\n";

$productId = 'product-' . uniqid();

$auditLogger->logCreate(
    'Product',
    $productId,
    [
        'name' => 'Professional Camera',
        'price' => 500.00,
        'category' => 'Electronics'
    ],
    $vendorId
);

echo "✓ Logged product creation\n\n";

// Example 8: Log Product Update
echo "8. Logging Product Update\n";
echo "-------------------------\n";

$auditLogger->logUpdate(
    'Product',
    $productId,
    ['price' => 500.00, 'status' => 'Active'],
    ['price' => 550.00, 'status' => 'Active'],
    $vendorId
);

echo "✓ Logged product update\n\n";

// Example 9: Log Refund
echo "9. Logging Refund\n";
echo "-----------------\n";

$refundId = 'refund-' . uniqid();

$auditLogger->logRefund(
    $refundId,
    $orderId,
    150.00,
    'Order rejected by vendor'
);

echo "✓ Logged refund initiation\n\n";

// Example 10: Log Admin Action (Vendor Suspension)
echo "10. Logging Admin Action (Vendor Suspension)\n";
echo "--------------------------------------------\n";

$adminId = 'admin-' . uniqid();

$auditLogger->logVendorSuspend(
    $vendorId,
    'Multiple policy violations',
    $adminId
);

echo "✓ Logged vendor suspension by admin\n\n";

// Example 11: Query Audit Logs
echo "11. Querying Audit Logs\n";
echo "-----------------------\n";

$repository = $auditLogger->getRepository();

// Get recent logs
$recentLogs = $repository->findRecent(5);

echo "Recent audit logs:\n";
foreach ($recentLogs as $log) {
    echo "  - " . $log->getDescription() . "\n";
    echo "    Time: " . $log->getTimestamp()->format('Y-m-d H:i:s') . "\n";
    echo "    IP: " . $log->getIpAddress() . "\n";
    
    if ($log->hasChange()) {
        $changes = $log->getChanges();
        foreach ($changes as $field => $change) {
            echo "    Changed {$field}: {$change['old']} -> {$change['new']}\n";
        }
    }
    echo "\n";
}

// Example 12: Search Audit Logs
echo "12. Searching Audit Logs\n";
echo "------------------------\n";

// Search for all order-related logs
$orderLogs = $repository->search([
    'entity_type' => 'Order'
], 10);

echo "Found " . count($orderLogs) . " order-related audit logs\n\n";

// Example 13: Count Audit Logs
echo "13. Counting Audit Logs\n";
echo "-----------------------\n";

$totalLogs = $repository->count();
$orderStatusChanges = $repository->count(['action' => 'status_change']);

echo "Total audit logs: {$totalLogs}\n";
echo "Order status changes: {$orderStatusChanges}\n\n";

// Example 14: Generic Logging
echo "14. Generic Logging\n";
echo "-------------------\n";

$auditLogger->log(
    'CustomEntity',
    'entity-123',
    'custom_action',
    ['field1' => 'old_value'],
    ['field1' => 'new_value'],
    $userId
);

echo "✓ Logged custom action\n\n";

// Example 15: System Action (No User)
echo "15. System Action Logging\n";
echo "-------------------------\n";

$auditLogger->log(
    'System',
    'system-1',
    'automated_cleanup',
    null,
    ['cleaned_records' => 100],
    null  // No user ID for system actions
);

echo "✓ Logged automated system action\n\n";

echo "=== All Examples Completed Successfully ===\n";
echo "\nNote: These examples create actual audit log entries in the database.\n";
echo "You can view them in the audit_logs table or query them using the repository.\n";

