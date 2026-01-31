<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Order;
use Exception;

/**
 * Notification Service
 * 
 * Handles sending notifications for order status changes
 */
class NotificationService
{
    /**
     * Send order created notification to customer
     */
    public function sendOrderCreatedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Confirmation - {$order->getOrderNumber()}";
        $message = "Your order {$order->getOrderNumber()} has been created successfully. ";
        
        if ($order->requiresVendorApproval()) {
            $message .= "It is now pending vendor approval.";
        } else {
            $message .= "Your rental is now active.";
        }

        $this->sendNotification($customerId, 'order_created', $subject, $message);
    }

    /**
     * Send approval request notification to vendor
     */
    public function sendApprovalRequestNotification(string $vendorId, Order $order): void
    {
        $subject = "New Order Requires Approval - {$order->getOrderNumber()}";
        $message = "A new order {$order->getOrderNumber()} requires your approval. Please review and approve or reject the order.";

        $this->sendNotification($vendorId, 'approval_request', $subject, $message);
    }

    /**
     * Send order approved notification to customer
     */
    public function sendOrderApprovedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Approved - {$order->getOrderNumber()}";
        $message = "Great news! Your order {$order->getOrderNumber()} has been approved and your rental is now active.";

        $this->sendNotification($customerId, 'order_approved', $subject, $message);
    }

    /**
     * Send order rejected notification to customer
     */
    public function sendOrderRejectedNotification(string $customerId, Order $order): void
    {
        $subject = "Order Rejected - {$order->getOrderNumber()}";
        $message = "Unfortunately, your order {$order->getOrderNumber()} has been rejected by the vendor. A refund will be processed shortly.";

        $this->sendNotification($customerId, 'order_rejected', $subject, $message);
    }

    /**
     * Send rental activated notification to vendor
     */
    public function sendRentalActivatedNotification(string $vendorId, Order $order): void
    {
        $subject = "Rental Activated - {$order->getOrderNumber()}";
        $message = "Order {$order->getOrderNumber()} has been activated. The rental period has begun.";

        $this->sendNotification($vendorId, 'rental_activated', $subject, $message);
    }

    /**
     * Send rental completed notification
     */
    public function sendRentalCompletedNotification(string $userId, Order $order): void
    {
        $subject = "Rental Completed - {$order->getOrderNumber()}";
        $message = "The rental for order {$order->getOrderNumber()} has been completed successfully.";

        $this->sendNotification($userId, 'rental_completed', $subject, $message);
    }

    /**
     * Send refund notification to customer
     */
    public function sendRefundNotification(string $customerId, Order $order): void
    {
        $subject = "Refund Processed - {$order->getOrderNumber()}";
        $message = "A refund has been processed for your order {$order->getOrderNumber()}. Please allow 3-5 business days for the refund to appear in your account.";

        $this->sendNotification($customerId, 'refund_processed', $subject, $message);
    }

    /**
     * Send notification (placeholder implementation)
     * 
     * In a real implementation, this would:
     * 1. Store notification in database
     * 2. Send email via SMTP
     * 3. Handle retry logic for failed deliveries
     * 4. Support different notification channels (email, SMS, push)
     */
    private function sendNotification(string $userId, string $eventType, string $subject, string $message): void
    {
        // For now, just log the notification
        // In a real implementation, this would send actual emails
        error_log("NOTIFICATION [{$eventType}] TO {$userId}: {$subject} - {$message}");

        // TODO: Implement actual email sending when email service is configured
        // TODO: Store notification in database for tracking
        // TODO: Add retry logic for failed deliveries
    }
}