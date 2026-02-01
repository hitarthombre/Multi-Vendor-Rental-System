<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Order;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Notification;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\NotificationRepository;
use RentalPlatform\Services\EmailService;
use Exception;

/**
 * Notification Service
 * 
 * Handles sending notifications for order status changes
 */
class NotificationService
{
    private UserRepository $userRepository;
    private NotificationRepository $notificationRepository;
    private EmailService $emailService;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->notificationRepository = new NotificationRepository();
        $this->emailService = new EmailService();
    }
    /**
     * Send payment success notification to customer
     * Requirements: 8.1
     */
    public function sendPaymentSuccessNotification(string $customerId, $payment): void
    {
        $subject = "Payment Successful - Order Confirmation";
        $message = "
            <h2>Payment Successful</h2>
            <p>Your payment has been processed successfully!</p>
            <p><strong>Payment Details:</strong></p>
            <ul>
                <li>Payment ID: {$payment->getId()}</li>
                <li>Amount: ₹" . number_format($payment->getAmount(), 2) . "</li>
                <li>Status: Verified</li>
            </ul>
            <p>Your orders have been created and you will receive separate confirmation emails for each order.</p>
            <p>Thank you for your purchase!</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'payment_success', $subject, $message);
    }

    /**
     * Send admin notification for new orders
     * Requirements: 8.4
     */
    public function sendAdminNewOrdersNotification(array $orders): void
    {
        $subject = "New Orders Created - " . count($orders) . " Order(s)";
        
        $ordersList = '';
        $totalAmount = 0;
        foreach ($orders as $order) {
            $ordersList .= "<li>Order {$order->getOrderNumber()} - ₹" . number_format($order->getTotalAmount(), 2) . " - {$order->getStatus()}</li>";
            $totalAmount += $order->getTotalAmount();
        }
        
        $message = "
            <h2>New Orders Created</h2>
            <p><strong>Number of Orders:</strong> " . count($orders) . "</p>
            <p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>
            <p><strong>Orders:</strong></p>
            <ul>{$ordersList}</ul>
            <p>Please review these orders in the admin dashboard.</p>
            <p>System Administrator</p>
        ";

        // Send to admin email (in production, this would be configurable)
        $this->emailService->sendEmail('admin@rentalhub.com', 'Admin', $subject, $message);
    }

    /**
     * Send order created notification to customer
     */
    public function sendOrderCreatedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Confirmation - {$order->getOrderNumber()}";
        $message = $this->buildOrderCreatedMessage($order);

        $this->sendNotification($customerId, 'order_created', $subject, $message);
    }

    /**
     * Send approval request notification to vendor
     */
    public function sendApprovalRequestNotification(string $vendorId, Order $order): void
    {
        $subject = "New Order Requires Approval - {$order->getOrderNumber()}";
        $message = $this->buildApprovalRequestMessage($order);

        $this->sendNotification($vendorId, 'approval_request', $subject, $message);
    }

    /**
     * Send order approved notification to customer
     */
    public function sendOrderApprovedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Approved - {$order->getOrderNumber()}";
        $message = $this->buildOrderApprovedMessage($order);

        $this->sendNotification($customerId, 'order_approved', $subject, $message);
    }

    /**
     * Send order rejected notification to customer
     */
    public function sendOrderRejectedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Rejected - {$order->getOrderNumber()}";
        $message = $this->buildOrderRejectedMessage($order);

        $this->sendNotification($customerId, 'order_rejected', $subject, $message);
    }

    /**
     * Send rental activated notification to vendor
     */
    public function sendRentalActivatedNotification(string $vendorId, Order $order): void
    {
        $subject = "Rental Activated - {$order->getOrderNumber()}";
        $message = $this->buildRentalActivatedMessage($order);

        $this->sendNotification($vendorId, 'rental_activated', $subject, $message);
    }

    /**
     * Send rental completed notification
     */
    public function sendRentalCompletedNotification(string $userId, Order $order): void
    {
        $subject = "Rental Completed - {$order->getOrderNumber()}";
        $message = $this->buildRentalCompletedMessage($order);

        $this->sendNotification($userId, 'rental_completed', $subject, $message);
    }

    /**
     * Send refund notification to customer
     */
    public function sendRefundNotification(string $customerId, Order $order): void
    {
        $subject = "Refund Processed - {$order->getOrderNumber()}";
        $message = $this->buildRefundMessage($order);

        $this->sendNotification($customerId, 'refund_processed', $subject, $message);
    }

    /**
     * Send payment failure notification (Task 28.1)
     */
    public function sendPaymentFailureNotification(string $customerId, string $paymentId, string $reason): void
    {
        $subject = 'Payment Failed - Order Not Created';
        $message = "
            <h2>Payment Failed</h2>
            <p>We're sorry, but your payment could not be processed.</p>
            <p><strong>Payment ID:</strong> {$paymentId}</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>Your cart has been preserved. Please try again or contact support if the issue persists.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'payment_failure', $subject, $message);
    }

    /**
     * Send inventory conflict notification (Task 28.3)
     */
    public function sendInventoryConflictNotification(string $customerId, array $conflictingItems): void
    {
        $subject = 'Order Could Not Be Created - Inventory Conflict';
        
        $itemsList = '';
        foreach ($conflictingItems as $item) {
            $itemsList .= "<li>{$item['product_name']} - {$item['conflict_reason']}</li>";
        }
        
        $message = "
            <h2>Order Creation Failed</h2>
            <p>We're sorry, but your order could not be created due to inventory conflicts:</p>
            <ul>{$itemsList}</ul>
            <p>These items may have been rented by another customer while you were checking out.</p>
            <p>Please review your cart and try again with alternative dates or products.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'inventory_conflict', $subject, $message);
    }

    /**
     * Send refund failure notification to admin (Task 28.5)
     */
    public function sendRefundFailureNotification(string $orderId, string $paymentId, float $refundAmount, string $reason): void
    {
        $subject = 'URGENT: Refund Processing Failed - Admin Intervention Required';
        $message = "
            <h2>Refund Processing Failed</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Payment ID:</strong> {$paymentId}</p>
            <p><strong>Refund Amount:</strong> ₹" . number_format($refundAmount, 2) . "</p>
            <p><strong>Failure Reason:</strong> {$reason}</p>
            <p>This refund requires immediate admin intervention. Please process manually.</p>
            <p>System Administrator</p>
        ";

        // Send to admin email (in production, this would be configurable)
        $this->emailService->sendEmail('admin@rentalhub.com', 'Admin', $subject, $message);
    }

    /**
     * Send vendor timeout reminder (Task 28.6)
     */
    public function sendVendorTimeoutReminder(string $vendorId, string $orderId, int $hoursOverdue): void
    {
        $subject = 'REMINDER: Pending Order Approval - Action Required';
        $message = "
            <h2>Order Approval Overdue</h2>
            <p>You have a pending order that requires your approval:</p>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Hours Overdue:</strong> {$hoursOverdue}</p>
            <p>Please log in to your vendor dashboard to review and approve/reject this order.</p>
            <p>If no action is taken within 72 hours, the order may be automatically cancelled.</p>
            <p><a href='http://localhost:8081/vendor/dashboard.php'>Review Order</a></p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($vendorId, 'vendor_timeout_reminder', $subject, $message);
    }

    /**
     * Send late return notification to vendor (Task 28.7)
     */
    public function sendLateReturnNotification(string $vendorId, string $orderId, int $daysLate, float $lateFeePerDay): void
    {
        $totalLateFee = $daysLate * $lateFeePerDay;
        $subject = 'Late Return Detected - Fee Application Available';
        $message = "
            <h2>Late Return Detected</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Days Late:</strong> {$daysLate}</p>
            <p><strong>Suggested Late Fee:</strong> ₹" . number_format($totalLateFee, 2) . " ({$daysLate} days × ₹" . number_format($lateFeePerDay, 2) . ")</p>
            <p>You can apply late fees through your vendor dashboard if appropriate.</p>
            <p><a href='http://localhost:8081/vendor/dashboard.php'>Manage Order</a></p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($vendorId, 'late_return_vendor', $subject, $message);
    }

    /**
     * Send late return notification to customer (Task 28.7)
     */
    public function sendLateReturnCustomerNotification(string $customerId, string $orderId, int $daysLate, float $lateFeePerDay): void
    {
        $totalLateFee = $daysLate * $lateFeePerDay;
        $subject = 'Late Return Notice - Additional Fees May Apply';
        $message = "
            <h2>Late Return Notice</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Days Late:</strong> {$daysLate}</p>
            <p>Your rental period has ended but the item has not been returned. Late fees may apply:</p>
            <p><strong>Potential Late Fee:</strong> ₹" . number_format($totalLateFee, 2) . "</p>
            <p>Please return the item as soon as possible to minimize additional charges.</p>
            <p>If you have already returned the item, please contact the vendor immediately.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'late_return_customer', $subject, $message);
    }

    /**
     * Send document upload timeout notification to customer (Task 28.8)
     */
    public function sendDocumentTimeoutNotification(string $customerId, string $orderId, int $hoursOverdue, array $missingDocuments): void
    {
        $docsList = implode(', ', $missingDocuments);
        $subject = 'Document Upload Required - Order May Be Cancelled';
        $message = "
            <h2>Document Upload Overdue</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Hours Overdue:</strong> {$hoursOverdue}</p>
            <p><strong>Missing Documents:</strong> {$docsList}</p>
            <p>Your order requires document verification, but the required documents have not been uploaded.</p>
            <p>Please upload the missing documents immediately to prevent order cancellation.</p>
            <p>If documents are not uploaded soon, your order will be cancelled with a full refund.</p>
            <p><a href='http://localhost:8081/customer/dashboard.php'>Upload Documents</a></p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'document_timeout_customer', $subject, $message);
    }

    /**
     * Send document timeout notification to vendor (Task 28.8)
     */
    public function sendDocumentTimeoutVendorNotification(string $vendorId, string $orderId, int $hoursOverdue, array $missingDocuments): void
    {
        $docsList = implode(', ', $missingDocuments);
        $subject = 'Customer Document Upload Overdue - Order May Be Cancelled';
        $message = "
            <h2>Customer Document Upload Overdue</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Hours Overdue:</strong> {$hoursOverdue}</p>
            <p><strong>Missing Documents:</strong> {$missingDocuments}</p>
            <p>The customer has not uploaded required verification documents for this order.</p>
            <p>The order may be automatically cancelled with a refund if documents are not received soon.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($vendorId, 'document_timeout_vendor', $subject, $message);
    }

    /**
     * Send late fee notification to customer
     */
    public function sendLateFeeNotification(string $customerId, string $orderId, float $lateFeeAmount, string $reason): void
    {
        $subject = 'Late Fee Applied to Your Order';
        $message = "
            <h2>Late Fee Applied</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Late Fee Amount:</strong> ₹" . number_format($lateFeeAmount, 2) . "</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>This fee has been added to your order due to late return or other policy violations.</p>
            <p>If you believe this fee was applied in error, please contact customer support.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'late_fee_applied', $subject, $message);
    }

    /**
     * Send refund initiated notification
     */
    public function sendRefundInitiatedNotification(string $customerId, string $orderId, float $refundAmount, string $reason): void
    {
        $subject = 'Refund Initiated for Your Order';
        $message = "
            <h2>Refund Initiated</h2>
            <p><strong>Order ID:</strong> {$orderId}</p>
            <p><strong>Refund Amount:</strong> ₹" . number_format($refundAmount, 2) . "</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>Your refund is being processed and should appear in your account within 5-7 business days.</p>
            <p>You will receive a confirmation email once the refund is completed.</p>
            <p>Best regards,<br>RentalHub Team</p>
        ";

        $this->sendNotification($customerId, 'refund_initiated', $subject, $message);
    }

    /**
     * Send notification with email delivery
     * 
     * This method:
     * 1. Stores notification in database
     * 2. Sends email via SMTP
     * 3. Handles retry logic for failed deliveries
     * 4. Logs all notification attempts
     */
    private function sendNotification(string $userId, string $eventType, string $subject, string $message): void
    {
        try {
            // Create notification record
            $notification = Notification::create($userId, $eventType, $subject, $message);
            $this->notificationRepository->create($notification);

            // Get user details for email
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                error_log("NOTIFICATION ERROR: User not found for ID: {$userId}");
                $notification->markAsFailed();
                $this->notificationRepository->update($notification);
                return;
            }

            // Send email
            $emailSent = $this->emailService->sendEmail(
                $user->getEmail(),
                $user->getUsername(),
                $subject,
                $message
            );

            if ($emailSent) {
                $notification->markAsSent();
                error_log("NOTIFICATION SENT [{$eventType}] TO {$user->getEmail()}: {$subject}");
            } else {
                $notification->markAsFailed();
                error_log("NOTIFICATION FAILED [{$eventType}] TO {$user->getEmail()}: {$subject}");
            }

            // Update notification status
            $this->notificationRepository->update($notification);

        } catch (Exception $e) {
            error_log("NOTIFICATION ERROR: " . $e->getMessage());
            
            // Try to mark as failed if notification was created
            if (isset($notification)) {
                try {
                    $notification->markAsFailed();
                    $this->notificationRepository->update($notification);
                } catch (Exception $updateError) {
                    error_log("Failed to update notification status: " . $updateError->getMessage());
                }
            }
        }
    }

    /**
     * Process pending notifications (for batch processing)
     * 
     * @param int $limit Maximum number of notifications to process
     * @return int Number of notifications processed
     */
    public function processPendingNotifications(int $limit = 50): int
    {
        $pendingNotifications = $this->notificationRepository->getPendingNotifications($limit);
        $processed = 0;

        foreach ($pendingNotifications as $notification) {
            try {
                $user = $this->userRepository->findById($notification->getUserId());
                if (!$user) {
                    $notification->markAsFailed();
                    $this->notificationRepository->update($notification);
                    continue;
                }

                $emailSent = $this->emailService->sendEmail(
                    $user->getEmail(),
                    $user->getUsername(),
                    $notification->getSubject(),
                    $notification->getBody()
                );

                if ($emailSent) {
                    $notification->markAsSent();
                } else {
                    $notification->markAsFailed();
                }

                $this->notificationRepository->update($notification);
                $processed++;

            } catch (Exception $e) {
                error_log("Error processing notification {$notification->getId()}: " . $e->getMessage());
                
                try {
                    $notification->markAsFailed();
                    $this->notificationRepository->update($notification);
                } catch (Exception $updateError) {
                    error_log("Failed to update notification status: " . $updateError->getMessage());
                }
            }
        }

        return $processed;
    }

    /**
     * Retry failed notifications
     * 
     * @param int $limit Maximum number of notifications to retry
     * @param int $backoffMinutes Minimum minutes since last failure before retry
     * @return int Number of notifications retried
     */
    public function retryFailedNotifications(int $limit = 20, int $backoffMinutes = 30): int
    {
        $failedNotifications = $this->notificationRepository->getFailedNotifications($limit, $backoffMinutes);
        $retried = 0;

        foreach ($failedNotifications as $notification) {
            try {
                $user = $this->userRepository->findById($notification->getUserId());
                if (!$user) {
                    continue; // Skip if user not found
                }

                $emailSent = $this->emailService->sendEmail(
                    $user->getEmail(),
                    $user->getUsername(),
                    $notification->getSubject(),
                    $notification->getBody()
                );

                if ($emailSent) {
                    $notification->markAsSent();
                    $this->notificationRepository->update($notification);
                    $retried++;
                    error_log("NOTIFICATION RETRY SUCCESS: {$notification->getId()} to {$user->getEmail()}");
                } else {
                    // Keep as failed, will be retried later
                    error_log("NOTIFICATION RETRY FAILED: {$notification->getId()} to {$user->getEmail()}");
                }

            } catch (Exception $e) {
                error_log("Error retrying notification {$notification->getId()}: " . $e->getMessage());
            }
        }

        return $retried;
    }

    /**
     * Send test notification
     * 
     * @param string $userId
     * @return bool
     */
    public function sendTestNotification(string $userId): bool
    {
        try {
            $subject = "Test Notification - Multi-Vendor Rental Platform";
            $message = "
                <h2>Test Notification</h2>
                <p>This is a test notification to verify that the email system is working correctly.</p>
                <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p>If you received this email, the notification system is functioning properly.</p>
            ";

            $this->sendNotification($userId, 'test_notification', $subject, $message);
            return true;

        } catch (Exception $e) {
            error_log("Test notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->notificationRepository->getStatistics();
    }

    /**
     * Get notifications by event type
     * 
     * @param string $eventType
     * @param string|null $status
     * @param int $limit
     * @return array
     */
    public function getNotificationsByEventType(string $eventType, ?string $status = null, int $limit = 50): array
    {
        $notifications = $this->notificationRepository->findByEventType($eventType, $status, $limit);
        return array_map(fn($notification) => $notification->toArray(), $notifications);
    }

    /**
     * Get user notifications
     * 
     * @param string $userId
     * @param int $limit
     * @return array
     */
    public function getUserNotifications(string $userId, int $limit = 100): array
    {
        $notifications = $this->notificationRepository->findByUserId($userId, $limit);
        return array_map(fn($notification) => $notification->toArray(), $notifications);
    }

    /**
     * Clean up old notifications
     * 
     * @param int $daysOld
     * @return int Number of deleted notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        return $this->notificationRepository->deleteOldNotifications($daysOld);
    }

    /**
     * Health check for notification system
     * 
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            $stats = $this->getStatistics();
            $pendingCount = $stats[Notification::STATUS_PENDING]['count'] ?? 0;
            $failedCount = $stats[Notification::STATUS_FAILED]['count'] ?? 0;
            $sentCount = $stats[Notification::STATUS_SENT]['count'] ?? 0;
            
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'statistics' => $stats,
                'pending_notifications' => $pendingCount,
                'failed_notifications' => $failedCount,
                'sent_notifications' => $sentCount,
                'warnings' => []
            ];
            
            // Check for issues
            if ($pendingCount > 100) {
                $health['warnings'][] = "High number of pending notifications: {$pendingCount}";
            }
            
            if ($failedCount > 50) {
                $health['warnings'][] = "High number of failed notifications: {$failedCount}";
                $health['status'] = 'warning';
            }
            
            // Test email service
            try {
                $testResult = $this->emailService->sendTestEmail('test@example.com');
                $health['email_service'] = $testResult ? 'working' : 'failed';
                if (!$testResult) {
                    $health['status'] = 'error';
                    $health['warnings'][] = 'Email service test failed';
                }
            } catch (Exception $e) {
                $health['email_service'] = 'error';
                $health['status'] = 'error';
                $health['warnings'][] = 'Email service error: ' . $e->getMessage();
            }
            
            return $health;
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ];
        }
    }

    // Message building methods for better email templates

    private function buildOrderCreatedMessage(Order $order): string
    {
        $message = "
            <h2>Order Confirmation</h2>
            <p>Your order <strong>{$order->getOrderNumber()}</strong> has been created successfully.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: " . ucwords(str_replace('_', ' ', $order->getStatus())) . "</li>
            </ul>
        ";

        if ($order->requiresVendorApproval()) {
            $message .= "<p><strong>Next Steps:</strong> Your order is now pending vendor approval. You will receive another email once the vendor reviews your order.</p>";
        } else {
            $message .= "<p><strong>Good News:</strong> Your rental is now active and ready!</p>";
        }

        return $message;
    }

    private function buildApprovalRequestMessage(Order $order): string
    {
        return "
            <h2>New Order Requires Your Approval</h2>
            <p>A new order <strong>{$order->getOrderNumber()}</strong> requires your approval.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Customer ID: {$order->getCustomerId()}</li>
            </ul>
            <p><strong>Action Required:</strong> Please log in to your vendor dashboard to review and approve or reject this order.</p>
        ";
    }

    private function buildOrderApprovedMessage(Order $order): string
    {
        return "
            <h2>Great News! Your Order Has Been Approved</h2>
            <p>Your order <strong>{$order->getOrderNumber()}</strong> has been approved by the vendor.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Active Rental</li>
            </ul>
            <p><strong>Next Steps:</strong> Your rental is now active. Please coordinate with the vendor for pickup/delivery arrangements.</p>
        ";
    }

    private function buildOrderRejectedMessage(Order $order): string
    {
        return "
            <h2>Order Update - Order Rejected</h2>
            <p>Unfortunately, your order <strong>{$order->getOrderNumber()}</strong> has been rejected by the vendor.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Rejected</li>
            </ul>
            <p><strong>Refund Information:</strong> A full refund will be processed automatically. Please allow 3-5 business days for the refund to appear in your account.</p>
        ";
    }

    private function buildRentalActivatedMessage(Order $order): string
    {
        return "
            <h2>Rental Activated</h2>
            <p>Order <strong>{$order->getOrderNumber()}</strong> has been activated and the rental period has begun.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Active Rental</li>
            </ul>
            <p><strong>Reminder:</strong> Please ensure proper care of the rental items and return them on time.</p>
        ";
    }

    private function buildRentalCompletedMessage(Order $order): string
    {
        return "
            <h2>Rental Completed Successfully</h2>
            <p>The rental for order <strong>{$order->getOrderNumber()}</strong> has been completed successfully.</p>
            <p><strong>Order Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Total Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Completed</li>
            </ul>
            <p><strong>Thank You:</strong> Thank you for using our rental platform. We hope you had a great experience!</p>
        ";
    }

    private function buildRefundMessage(Order $order): string
    {
        return "
            <h2>Refund Processed</h2>
            <p>A refund has been processed for your order <strong>{$order->getOrderNumber()}</strong>.</p>
            <p><strong>Refund Details:</strong></p>
            <ul>
                <li>Order Number: {$order->getOrderNumber()}</li>
                <li>Refund Amount: ₹" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Refunded</li>
            </ul>
            <p><strong>Processing Time:</strong> Please allow 3-5 business days for the refund to appear in your account.</p>
        ";
    }
}