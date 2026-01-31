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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Total Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
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
                <li>Refund Amount: $" . number_format($order->getTotalAmount(), 2) . "</li>
                <li>Status: Refunded</li>
            </ul>
            <p><strong>Processing Time:</strong> Please allow 3-5 business days for the refund to appear in your account.</p>
        ";
    }
}