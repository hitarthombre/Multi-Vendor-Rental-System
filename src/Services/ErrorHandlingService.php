<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\AuditLog;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Repositories\PaymentRepository;
use RentalPlatform\Repositories\InventoryLockRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Services\NotificationService;
use Exception;
use DateTime;

/**
 * Error Handling Service
 * 
 * Handles system errors and edge cases according to Requirement 24
 */
class ErrorHandlingService
{
    private AuditLogRepository $auditLogRepo;
    private PaymentRepository $paymentRepo;
    private InventoryLockRepository $inventoryLockRepo;
    private OrderRepository $orderRepo;
    private NotificationService $notificationService;

    public function __construct()
    {
        $db = \RentalPlatform\Database\Connection::getInstance();
        $this->auditLogRepo = new AuditLogRepository($db);
        $this->paymentRepo = new PaymentRepository();
        $this->inventoryLockRepo = new InventoryLockRepository();
        $this->orderRepo = new OrderRepository();
        $this->notificationService = new NotificationService();
    }

    /**
     * Task 28.1: Handle payment verification failure
     * Requirement 24.1: IF payment verification fails, THEN THE System SHALL NOT create any Rental_Order and SHALL notify the Customer
     * 
     * @param string $paymentId
     * @param string $customerId
     * @param string $failureReason
     * @param array $cartData
     * @throws Exception
     */
    public function handlePaymentVerificationFailure(
        string $paymentId,
        string $customerId,
        string $failureReason,
        array $cartData = []
    ): void {
        // Log the payment failure
        $this->logError(
            'payment_verification_failure',
            'Payment',
            $paymentId,
            'system',
            [
                'customer_id' => $customerId,
                'failure_reason' => $failureReason,
                'cart_preserved' => !empty($cartData),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        // Mark payment as failed
        $payment = $this->paymentRepo->findById($paymentId);
        if ($payment) {
            $payment->fail();
            $this->paymentRepo->update($payment);
        }

        // Notify customer about payment failure
        $this->notificationService->sendPaymentFailureNotification(
            $customerId,
            $paymentId,
            $failureReason
        );

        // Cart is automatically preserved since no order creation occurs
        error_log("Payment verification failed for payment $paymentId: $failureReason");
    }

    /**
     * Task 28.3: Handle inventory conflicts during order creation
     * Requirement 24.2: IF inventory conflicts occur during order creation, THEN THE System SHALL reject the conflicting order and notify the Customer
     * 
     * @param string $customerId
     * @param array $conflictingItems
     * @param string $orderId
     * @throws Exception
     */
    public function handleInventoryConflict(
        string $customerId,
        array $conflictingItems,
        string $orderId = null
    ): void {
        // Log the inventory conflict
        $this->logError(
            'inventory_conflict',
            'Order',
            $orderId ?? 'pending',
            'system',
            [
                'customer_id' => $customerId,
                'conflicting_items' => $conflictingItems,
                'conflict_detected_at' => date('Y-m-d H:i:s')
            ]
        );

        // If order was partially created, mark it as rejected
        if ($orderId) {
            $order = $this->orderRepo->findById($orderId);
            if ($order) {
                $order->transitionTo(\RentalPlatform\Models\Order::STATUS_REJECTED);
                $this->orderRepo->update($order);
            }
        }

        // Notify customer about inventory conflict
        $this->notificationService->sendInventoryConflictNotification(
            $customerId,
            $conflictingItems
        );

        error_log("Inventory conflict detected for customer $customerId: " . json_encode($conflictingItems));
    }

    /**
     * Task 28.5: Handle refund failure
     * Requirement 24.3: IF a refund initiation fails, THEN THE System SHALL log the error and allow Administrator intervention
     * 
     * @param string $orderId
     * @param string $paymentId
     * @param float $refundAmount
     * @param string $failureReason
     * @param array $refundDetails
     * @throws Exception
     */
    public function handleRefundFailure(
        string $orderId,
        string $paymentId,
        float $refundAmount,
        string $failureReason,
        array $refundDetails = []
    ): void {
        // Log the refund failure with detailed information
        $this->logError(
            'refund_failure',
            'Payment',
            $paymentId,
            'system',
            [
                'order_id' => $orderId,
                'refund_amount' => $refundAmount,
                'failure_reason' => $failureReason,
                'refund_details' => $refundDetails,
                'requires_admin_intervention' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        // Create admin intervention record
        $this->createAdminInterventionRecord(
            'refund_failure',
            $orderId,
            [
                'payment_id' => $paymentId,
                'refund_amount' => $refundAmount,
                'failure_reason' => $failureReason,
                'priority' => 'high'
            ]
        );

        // Notify admin about refund failure
        $this->notificationService->sendRefundFailureNotification(
            $orderId,
            $paymentId,
            $refundAmount,
            $failureReason
        );

        error_log("Refund failure for order $orderId, payment $paymentId: $failureReason");
    }

    /**
     * Task 28.6: Handle vendor timeout (delayed approvals)
     * Requirement 24.4: IF a Vendor does not respond to approval requests within a defined time, THEN THE System SHALL send reminders
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param int $hoursOverdue
     * @param bool $enableAutoCancellation
     * @throws Exception
     */
    public function handleVendorTimeout(
        string $orderId,
        string $vendorId,
        int $hoursOverdue,
        bool $enableAutoCancellation = false
    ): void {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Log the vendor timeout
        $this->logError(
            'vendor_timeout',
            'Order',
            $orderId,
            'system',
            [
                'vendor_id' => $vendorId,
                'hours_overdue' => $hoursOverdue,
                'order_status' => $order->getStatus(),
                'auto_cancellation_enabled' => $enableAutoCancellation,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        // Send reminder to vendor
        $this->notificationService->sendVendorTimeoutReminder(
            $vendorId,
            $orderId,
            $hoursOverdue
        );

        // Auto-cancel if enabled and severely overdue (e.g., 72+ hours)
        if ($enableAutoCancellation && $hoursOverdue >= 72) {
            $this->autoCancel($orderId, $vendorId, "Auto-cancelled due to vendor timeout ($hoursOverdue hours)");
        }

        error_log("Vendor timeout for order $orderId, vendor $vendorId: $hoursOverdue hours overdue");
    }

    /**
     * Task 28.7: Handle late returns
     * Requirement 24.5: IF a rental period ends but the asset is not returned, THEN THE System SHALL allow the Vendor to apply late fees
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param int $daysLate
     * @param float $lateFeePerDay
     * @throws Exception
     */
    public function handleLateReturn(
        string $orderId,
        string $vendorId,
        int $daysLate,
        float $lateFeePerDay = 0.0
    ): void {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Log the late return
        $this->logError(
            'late_return',
            'Order',
            $orderId,
            'system',
            [
                'vendor_id' => $vendorId,
                'days_late' => $daysLate,
                'late_fee_per_day' => $lateFeePerDay,
                'total_late_fee' => $daysLate * $lateFeePerDay,
                'order_status' => $order->getStatus(),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        // Notify vendor about late return and fee application option
        $this->notificationService->sendLateReturnNotification(
            $vendorId,
            $orderId,
            $daysLate,
            $lateFeePerDay
        );

        // Notify customer about late return
        $this->notificationService->sendLateReturnCustomerNotification(
            $order->getCustomerId(),
            $orderId,
            $daysLate,
            $lateFeePerDay
        );

        error_log("Late return detected for order $orderId: $daysLate days late");
    }

    /**
     * Task 28.8: Handle document upload timeout
     * Requirement 24.6: IF a Customer does not upload required documents within a defined time, THEN THE System SHALL allow order cancellation with refund
     * 
     * @param string $orderId
     * @param string $customerId
     * @param int $hoursOverdue
     * @param array $missingDocuments
     * @throws Exception
     */
    public function handleDocumentUploadTimeout(
        string $orderId,
        string $customerId,
        int $hoursOverdue,
        array $missingDocuments = []
    ): void {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Log the document upload timeout
        $this->logError(
            'document_upload_timeout',
            'Order',
            $orderId,
            'system',
            [
                'customer_id' => $customerId,
                'hours_overdue' => $hoursOverdue,
                'missing_documents' => $missingDocuments,
                'order_status' => $order->getStatus(),
                'cancellation_eligible' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        // Notify customer about document timeout and cancellation option
        $this->notificationService->sendDocumentTimeoutNotification(
            $customerId,
            $orderId,
            $hoursOverdue,
            $missingDocuments
        );

        // Notify vendor about potential cancellation
        $this->notificationService->sendDocumentTimeoutVendorNotification(
            $order->getVendorId(),
            $orderId,
            $hoursOverdue,
            $missingDocuments
        );

        error_log("Document upload timeout for order $orderId: $hoursOverdue hours overdue");
    }

    /**
     * Task 28.9: Log system errors
     * Requirement 24.7: WHEN any system error occurs, THE System SHALL log the error with timestamp and context for debugging
     * 
     * @param string $errorType
     * @param string $entityType
     * @param string $entityId
     * @param string $actorId
     * @param array $context
     * @throws Exception
     */
    public function logError(
        string $errorType,
        string $entityType,
        string $entityId,
        string $actorId,
        array $context = []
    ): void {
        // Add standard error context
        $errorContext = array_merge($context, [
            'error_type' => $errorType,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'System'
            ]
        ]);

        // Create audit log entry
        $auditLog = AuditLog::create(
            $actorId,
            $entityType,
            $entityId,
            'error_logged',
            null,
            $errorContext
        );

        $this->auditLogRepo->create($auditLog);

        // Also log to PHP error log for immediate visibility
        error_log("SYSTEM ERROR [$errorType]: " . json_encode($errorContext));
    }

    /**
     * Auto-cancel an order due to timeout
     */
    private function autoCancel(string $orderId, string $vendorId, string $reason): void
    {
        try {
            $orderService = new OrderService();
            $orderService->transitionOrderStatus(
                $orderId,
                \RentalPlatform\Models\Order::STATUS_CANCELLED,
                'system',
                $reason
            );

            // Log the auto-cancellation
            $this->logError(
                'auto_cancellation',
                'Order',
                $orderId,
                'system',
                [
                    'vendor_id' => $vendorId,
                    'reason' => $reason,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            );

        } catch (Exception $e) {
            // Log the failure to auto-cancel
            $this->logError(
                'auto_cancellation_failed',
                'Order',
                $orderId,
                'system',
                [
                    'vendor_id' => $vendorId,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            );
        }
    }

    /**
     * Create admin intervention record
     */
    private function createAdminInterventionRecord(
        string $interventionType,
        string $entityId,
        array $details
    ): void {
        $interventionData = array_merge($details, [
            'intervention_type' => $interventionType,
            'entity_id' => $entityId,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'priority' => $details['priority'] ?? 'medium'
        ]);

        // Log as audit entry for admin review
        $auditLog = AuditLog::create(
            'system',
            'AdminIntervention',
            $entityId,
            'intervention_required',
            null,
            $interventionData
        );

        $this->auditLogRepo->create($auditLog);
    }

    /**
     * Get pending admin interventions
     */
    public function getPendingAdminInterventions(): array
    {
        // This would typically query a dedicated interventions table
        // For now, we'll use audit logs with intervention_required action
        return $this->auditLogRepo->findByAction('intervention_required');
    }

    /**
     * Mark admin intervention as resolved
     */
    public function resolveAdminIntervention(string $interventionId, string $adminId, string $resolution): void
    {
        $this->logError(
            'admin_intervention_resolved',
            'AdminIntervention',
            $interventionId,
            $adminId,
            [
                'resolution' => $resolution,
                'resolved_at' => date('Y-m-d H:i:s')
            ]
        );
    }
}