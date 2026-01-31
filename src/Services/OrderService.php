<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Order;
use RentalPlatform\Models\OrderItem;
use RentalPlatform\Models\AuditLog;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\OrderItemRepository;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\CartItemRepository;
use RentalPlatform\Repositories\ProductRepository;
use Exception;

/**
 * Order Service
 * 
 * Handles order lifecycle and status management
 */
class OrderService
{
    private OrderRepository $orderRepo;
    private OrderItemRepository $orderItemRepo;
    private AuditLogRepository $auditLogRepo;
    private CartRepository $cartRepo;
    private CartItemRepository $cartItemRepo;
    private ProductRepository $productRepo;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->orderItemRepo = new OrderItemRepository();
        $this->auditLogRepo = new AuditLogRepository();
        $this->cartRepo = new CartRepository();
        $this->cartItemRepo = new CartItemRepository();
        $this->productRepo = new ProductRepository();
        $this->notificationService = new NotificationService();
    }

    /**
     * Create orders from cart after payment verification
     * 
     * @param string $customerId
     * @param string $paymentId
     * @return array Array of created orders
     * @throws Exception
     */
    public function createOrdersFromCart(string $customerId, string $paymentId): array
    {
        // Get customer's cart
        $cart = $this->cartRepo->findByCustomerId($customerId);
        if (!$cart) {
            throw new Exception('Cart not found');
        }

        $cartItems = $this->cartItemRepo->findByCartId($cart->getId());
        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }

        // Group cart items by vendor
        $vendorGroups = $this->groupCartItemsByVendor($cartItems);
        
        $createdOrders = [];

        // Create separate order for each vendor
        foreach ($vendorGroups as $vendorId => $items) {
            $order = $this->createOrderForVendor($customerId, $vendorId, $paymentId, $items);
            $createdOrders[] = $order;
        }

        // Clear the cart after successful order creation
        $this->cartItemRepo->deleteByCartId($cart->getId());

        return $createdOrders;
    }

    /**
     * Create order for a specific vendor
     */
    private function createOrderForVendor(string $customerId, string $vendorId, string $paymentId, array $cartItems): Order
    {
        // Calculate totals
        $totalAmount = 0;
        $depositAmount = 0;

        foreach ($cartItems as $item) {
            $totalAmount += $item->getTotalPrice();
            // TODO: Add deposit calculation when deposit system is implemented
        }

        // Determine initial status based on verification requirement
        $initialStatus = $this->determineInitialStatus($cartItems);

        // Create order
        $order = Order::create(
            $customerId,
            $vendorId,
            $paymentId,
            $initialStatus,
            $totalAmount,
            $depositAmount
        );

        $this->orderRepo->create($order);

        // Create order items
        foreach ($cartItems as $cartItem) {
            $orderItem = OrderItem::createFromCartItem($order->getId(), $cartItem);
            $this->orderItemRepo->create($orderItem);
        }

        // Log order creation
        $this->logOrderStatusChange(
            $order->getId(),
            null,
            $initialStatus,
            $customerId,
            'Order created after payment verification'
        );

        // Send notifications
        $this->sendOrderCreationNotifications($order);

        return $order;
    }

    /**
     * Determine initial order status based on products' verification requirements
     */
    private function determineInitialStatus(array $cartItems): string
    {
        foreach ($cartItems as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            if ($product && $product->getVerificationRequired()) {
                return Order::STATUS_PENDING_VENDOR_APPROVAL;
            }
        }

        return Order::STATUS_AUTO_APPROVED;
    }

    /**
     * Group cart items by vendor
     */
    private function groupCartItemsByVendor(array $cartItems): array
    {
        $groups = [];

        foreach ($cartItems as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            if (!$product) {
                continue;
            }

            $vendorId = $product->getVendorId();
            if (!isset($groups[$vendorId])) {
                $groups[$vendorId] = [];
            }

            $groups[$vendorId][] = $item;
        }

        return $groups;
    }

    /**
     * Transition order status (Task 13.1)
     * 
     * @param string $orderId
     * @param string $newStatus
     * @param string $actorId
     * @param string $reason
     * @throws Exception
     */
    public function transitionOrderStatus(string $orderId, string $newStatus, string $actorId, string $reason = ''): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        $oldStatus = $order->getStatus();

        // Validate transition
        if (!$order->canTransitionTo($newStatus)) {
            throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }

        // Perform the transition
        $order->transitionTo($newStatus);
        $this->orderRepo->update($order);

        // Log the transition (Task 13.3)
        $this->logOrderStatusChange($orderId, $oldStatus, $newStatus, $actorId, $reason);

        // Send notifications (Task 13.4)
        $this->sendStatusChangeNotifications($order, $oldStatus, $newStatus);

        // Handle status-specific actions
        $this->handleStatusSpecificActions($order, $newStatus);
    }

    /**
     * Approve order (vendor action)
     */
    public function approveOrder(string $orderId, string $vendorId, string $reason = ''): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Verify current status
        if ($order->getStatus() !== Order::STATUS_PENDING_VENDOR_APPROVAL) {
            throw new Exception('Order is not pending approval');
        }

        $this->transitionOrderStatus($orderId, Order::STATUS_ACTIVE_RENTAL, $vendorId, $reason ?: 'Order approved by vendor');
    }

    /**
     * Reject order (vendor action)
     */
    public function rejectOrder(string $orderId, string $vendorId, string $reason): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Verify current status
        if ($order->getStatus() !== Order::STATUS_PENDING_VENDOR_APPROVAL) {
            throw new Exception('Order is not pending approval');
        }

        $this->transitionOrderStatus($orderId, Order::STATUS_REJECTED, $vendorId, $reason);
    }

    /**
     * Complete rental (vendor action)
     */
    public function completeRental(string $orderId, string $vendorId, string $reason = ''): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Verify current status
        if ($order->getStatus() !== Order::STATUS_ACTIVE_RENTAL) {
            throw new Exception('Order is not an active rental');
        }

        $this->transitionOrderStatus($orderId, Order::STATUS_COMPLETED, $vendorId, $reason ?: 'Rental completed by vendor');
    }

    /**
     * Auto-approve orders that don't require verification
     */
    public function processAutoApprovals(): array
    {
        $autoApprovedOrders = $this->orderRepo->findByStatus(Order::STATUS_AUTO_APPROVED);
        $results = [
            'total_found' => count($autoApprovedOrders),
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($autoApprovedOrders as $order) {
            try {
                $this->transitionOrderStatus(
                    $order->getId(),
                    Order::STATUS_ACTIVE_RENTAL,
                    'system',
                    'Auto-approved order activated'
                );
                $results['processed']++;
                
                // Log successful auto-approval
                error_log("Auto-approved order {$order->getOrderNumber()} (ID: {$order->getId()})");
                
            } catch (Exception $e) {
                $results['failed']++;
                $errorMessage = "Failed to auto-approve order {$order->getOrderNumber()} (ID: {$order->getId()}): " . $e->getMessage();
                $results['errors'][] = $errorMessage;
                
                // Log error but continue processing other orders
                error_log($errorMessage);
            }
        }

        return $results;
    }

    /**
     * Get orders by status (for monitoring and reporting)
     */
    public function getOrdersByStatus(string $status): array
    {
        return $this->orderRepo->findByStatus($status);
    }

    /**
     * Get orders for customer
     */
    public function getCustomerOrders(string $customerId): array
    {
        return $this->orderRepo->findByCustomerId($customerId);
    }

    /**
     * Get orders for vendor
     */
    public function getVendorOrders(string $vendorId): array
    {
        return $this->orderRepo->findByVendorId($vendorId);
    }

    /**
     * Get pending approvals for vendor
     */
    public function getVendorPendingApprovals(string $vendorId): array
    {
        return $this->orderRepo->getPendingApprovals($vendorId);
    }

    /**
     * Get active rentals for vendor
     */
    public function getVendorActiveRentals(string $vendorId): array
    {
        return $this->orderRepo->getActiveRentals($vendorId);
    }

    /**
     * Get order details with items
     */
    public function getOrderDetails(string $orderId): array
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        $items = $this->orderItemRepo->findWithProductDetails($orderId);
        $summary = $this->orderItemRepo->getOrderSummary($orderId);

        return [
            'order' => $order->toArray(),
            'items' => $items,
            'summary' => $summary
        ];
    }

    /**
     * Log order status change (Task 13.3)
     */
    private function logOrderStatusChange(string $orderId, ?string $oldStatus, string $newStatus, string $actorId, string $reason): void
    {
        $auditLog = AuditLog::create(
            $actorId,
            'Order',
            $orderId,
            'status_change',
            $oldStatus ? ['status' => $oldStatus] : null,
            ['status' => $newStatus, 'reason' => $reason]
        );

        $this->auditLogRepo->create($auditLog);
    }

    /**
     * Send order creation notifications
     */
    private function sendOrderCreationNotifications(Order $order): void
    {
        // Notify customer
        $this->notificationService->sendOrderCreatedNotification($order->getCustomerId(), $order);

        // Notify vendor if approval is required
        if ($order->requiresVendorApproval()) {
            $this->notificationService->sendApprovalRequestNotification($order->getVendorId(), $order);
        }
    }

    /**
     * Send status change notifications (Task 13.4)
     */
    private function sendStatusChangeNotifications(Order $order, string $oldStatus, string $newStatus): void
    {
        switch ($newStatus) {
            case Order::STATUS_ACTIVE_RENTAL:
                $this->notificationService->sendOrderApprovedNotification($order->getCustomerId(), $order);
                $this->notificationService->sendRentalActivatedNotification($order->getVendorId(), $order);
                break;

            case Order::STATUS_REJECTED:
                $this->notificationService->sendOrderRejectedNotification($order->getCustomerId(), $order);
                break;

            case Order::STATUS_COMPLETED:
                $this->notificationService->sendRentalCompletedNotification($order->getCustomerId(), $order);
                $this->notificationService->sendRentalCompletedNotification($order->getVendorId(), $order);
                break;

            case Order::STATUS_REFUNDED:
                $this->notificationService->sendRefundNotification($order->getCustomerId(), $order);
                break;
        }
    }

    /**
     * Handle status-specific actions
     */
    private function handleStatusSpecificActions(Order $order, string $newStatus): void
    {
        switch ($newStatus) {
            case Order::STATUS_REJECTED:
                // TODO: Initiate refund process (will be implemented in payment tasks)
                // TODO: Release inventory locks (will be implemented in inventory tasks)
                break;

            case Order::STATUS_COMPLETED:
                // TODO: Release inventory locks (will be implemented in inventory tasks)
                // TODO: Enable deposit processing (will be implemented in deposit tasks)
                break;

            case Order::STATUS_ACTIVE_RENTAL:
                // TODO: Create inventory locks (will be implemented in inventory tasks)
                break;
        }
    }
}