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
    private InvoiceService $invoiceService;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->orderItemRepo = new OrderItemRepository();
        $this->auditLogRepo = new AuditLogRepository();
        $this->cartRepo = new CartRepository();
        $this->cartItemRepo = new CartItemRepository();
        $this->productRepo = new ProductRepository();
        $this->notificationService = new NotificationService();
        $this->invoiceService = new InvoiceService();
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
            $totalAmount += $item->getTentativePrice() * $item->getQuantity();
            
            // Calculate deposit for this item (Task 18.2, Requirement 14.2)
            $product = $this->productRepo->findById($item->getProductId());
            if ($product && $product->getSecurityDeposit() > 0) {
                $depositAmount += $product->getSecurityDeposit() * $item->getQuantity();
            }
        }

        // Add deposit to total amount (Task 18.2, Requirement 14.2)
        $totalAmount += $depositAmount;

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

        // Generate invoice after order creation (Requirement 13.3)
        try {
            $invoice = $this->invoiceService->generateInvoiceForOrder($order->getId());
            // Auto-finalize invoice for confirmed orders
            $this->invoiceService->finalizeInvoice($invoice->getId(), $customerId);
        } catch (\Exception $e) {
            // Log error but don't fail order creation
            error_log("Failed to generate invoice for order {$order->getId()}: " . $e->getMessage());
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
    public function completeRental(string $orderId, string $vendorId, string $reason = '', bool $releaseDeposit = true, float $penaltyAmount = 0.0, string $penaltyReason = ''): void
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

        // Transition order status to completed
        $this->transitionOrderStatus($orderId, Order::STATUS_COMPLETED, $vendorId, $reason ?: 'Rental completed by vendor');

        // Release inventory locks (Task 19.1 - Requirement 25.2)
        $this->releaseInventoryLocks($orderId);

        // Process deposit (Task 19.1 - Requirements 25.3, 25.4)
        if ($order->getDepositAmount() > 0) {
            $this->processDepositOnCompletion($order, $releaseDeposit, $penaltyAmount, $penaltyReason, $vendorId);
        }

        // Send completion notifications (Task 19.2 - Requirement 25.6)
        $this->sendCompletionNotifications($order);
    }

    /**
     * Release inventory locks for an order
     */
    private function releaseInventoryLocks(string $orderId): void
    {
        try {
            // Import the InventoryLockRepository
            require_once __DIR__ . '/../Repositories/InventoryLockRepository.php';
            $inventoryLockRepo = new \RentalPlatform\Repositories\InventoryLockRepository();
            
            // Release all locks for this order
            $inventoryLockRepo->releaseByOrderId($orderId);
            
            // Log the inventory release
            error_log("Released inventory locks for order: $orderId");
            
        } catch (Exception $e) {
            // Log error but don't fail the completion
            error_log("Failed to release inventory locks for order $orderId: " . $e->getMessage());
        }
    }

    /**
     * Process deposit on rental completion
     */
    private function processDepositOnCompletion(Order $order, bool $releaseDeposit, float $penaltyAmount, string $penaltyReason, string $vendorId): void
    {
        $depositAmount = $order->getDepositAmount();
        
        if ($releaseDeposit && $penaltyAmount == 0) {
            // Full deposit release
            $this->logDepositAction($order->getId(), 'released', $depositAmount, 'Deposit released - no damages', $vendorId);
        } elseif ($penaltyAmount > 0) {
            // Partial release with penalty
            if ($penaltyAmount > $depositAmount) {
                throw new Exception('Penalty amount cannot exceed deposit amount');
            }
            
            $releasedAmount = $depositAmount - $penaltyAmount;
            $this->logDepositAction($order->getId(), 'penalty_applied', $penaltyAmount, $penaltyReason, $vendorId);
            
            if ($releasedAmount > 0) {
                $this->logDepositAction($order->getId(), 'partial_release', $releasedAmount, 'Remaining deposit released after penalty', $vendorId);
            }
        } else {
            // Full deposit withheld
            $this->logDepositAction($order->getId(), 'withheld', $depositAmount, $penaltyReason ?: 'Deposit withheld by vendor', $vendorId);
        }
    }

    /**
     * Log deposit actions for audit trail
     */
    private function logDepositAction(string $orderId, string $action, float $amount, string $reason, string $actorId): void
    {
        $auditLog = AuditLog::create(
            $actorId,
            'Deposit',
            $orderId,
            $action,
            null,
            [
                'amount' => $amount,
                'reason' => $reason,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        $this->auditLogRepo->create($auditLog);
    }

    /**
     * Send completion notifications
     */
    private function sendCompletionNotifications(Order $order): void
    {
        // Notify customer of completion
        $this->notificationService->sendRentalCompletedNotification($order->getCustomerId(), $order);
        
        // Notify vendor of completion
        $this->notificationService->sendRentalCompletedNotification($order->getVendorId(), $order);
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
                // Deposit can now be processed by vendor (Task 18.5, Requirement 25.3)
                break;

            case Order::STATUS_ACTIVE_RENTAL:
                // TODO: Create inventory locks (will be implemented in inventory tasks)
                break;
        }
    }

    /**
     * Release deposit fully (Task 18.5, Requirements 14.6, 25.3)
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param string $reason
     * @throws Exception
     */
    public function releaseDeposit(string $orderId, string $vendorId, string $reason = 'Rental completed without issues'): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Check if order is completed
        if ($order->getStatus() !== Order::STATUS_COMPLETED) {
            throw new Exception('Can only process deposit for completed orders');
        }

        // Release deposit
        $order->releaseDeposit($reason);
        $this->orderRepo->update($order);

        // Log the action
        $this->auditLogger->log(
            'deposit_released',
            'Order',
            $orderId,
            $vendorId,
            [
                'order_number' => $order->getOrderNumber(),
                'deposit_amount' => $order->getDepositAmount(),
                'reason' => $reason
            ]
        );

        // TODO: Send notification to customer about deposit release
    }

    /**
     * Withhold deposit partially or fully (Task 18.5, Requirements 14.7, 25.4)
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param float $amount
     * @param string $reason
     * @throws Exception
     */
    public function withholdDeposit(string $orderId, string $vendorId, float $amount, string $reason): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Check if order is completed
        if ($order->getStatus() !== Order::STATUS_COMPLETED) {
            throw new Exception('Can only process deposit for completed orders');
        }

        // Withhold deposit
        $order->withholdDeposit($amount, $reason);
        $this->orderRepo->update($order);

        // Log the action
        $this->auditLogger->log(
            'deposit_withheld',
            'Order',
            $orderId,
            $vendorId,
            [
                'order_number' => $order->getOrderNumber(),
                'deposit_amount' => $order->getDepositAmount(),
                'withheld_amount' => $amount,
                'refund_amount' => $order->getDepositRefundAmount(),
                'reason' => $reason
            ]
        );

        // TODO: Send notification to customer about deposit withholding
        // TODO: Process partial refund if applicable
    }
}
