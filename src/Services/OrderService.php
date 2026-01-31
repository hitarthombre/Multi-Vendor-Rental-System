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
use RentalPlatform\Repositories\PaymentRepository;
use RentalPlatform\Services\ErrorHandlingService;
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
    private PaymentRepository $paymentRepo;
    private NotificationService $notificationService;
    private InvoiceService $invoiceService;
    private ErrorHandlingService $errorHandlingService;
    private \RentalPlatform\Repositories\InventoryLockRepository $inventoryLockRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->orderItemRepo = new OrderItemRepository();
        $this->auditLogRepo = new AuditLogRepository();
        $this->cartRepo = new CartRepository();
        $this->cartItemRepo = new CartItemRepository();
        $this->productRepo = new ProductRepository();
        $this->paymentRepo = new PaymentRepository();
        $this->notificationService = new NotificationService();
        $this->invoiceService = new InvoiceService();
        $this->errorHandlingService = new ErrorHandlingService();
        $this->inventoryLockRepo = new \RentalPlatform\Repositories\InventoryLockRepository();
    }

    /**
     * Create orders from cart after payment verification
     * Enhanced with error handling for Tasks 28.1 and 28.3
     * 
     * @param string $customerId
     * @param string $paymentId
     * @return array Array of created orders
     * @throws Exception
     */
    public function createOrdersFromCart(string $customerId, string $paymentId): array
    {
        // Task 28.1: Verify payment before creating orders (Requirement 24.1)
        $payment = $this->paymentRepo->findById($paymentId);
        if (!$payment) {
            $this->errorHandlingService->handlePaymentVerificationFailure(
                $paymentId,
                $customerId,
                'Payment not found'
            );
            throw new Exception('Payment not found');
        }

        if (!$payment->isVerified()) {
            $this->errorHandlingService->handlePaymentVerificationFailure(
                $paymentId,
                $customerId,
                'Payment not verified'
            );
            throw new Exception('Payment verification failed - order creation prevented');
        }

        // Get customer's cart
        $cart = $this->cartRepo->findByCustomerId($customerId);
        if (!$cart) {
            throw new Exception('Cart not found');
        }

        $cartItems = $this->cartItemRepo->findByCartId($cart->getId());
        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }

        // Task 28.3: Check for inventory conflicts before creating orders (Requirement 24.2)
        $conflictingItems = $this->checkInventoryConflicts($cartItems);
        if (!empty($conflictingItems)) {
            $this->errorHandlingService->handleInventoryConflict(
                $customerId,
                $conflictingItems
            );
            throw new Exception('Inventory conflicts detected - order creation rejected');
        }

        // Group cart items by vendor
        $vendorGroups = $this->groupCartItemsByVendor($cartItems);
        
        $createdOrders = [];

        // Create separate order for each vendor
        foreach ($vendorGroups as $vendorId => $items) {
            try {
                $order = $this->createOrderForVendor($customerId, $vendorId, $paymentId, $items);
                $createdOrders[] = $order;
            } catch (Exception $e) {
                // If any order creation fails, handle the error
                $this->errorHandlingService->logError(
                    'order_creation_failed',
                    'Order',
                    'pending',
                    'system',
                    [
                        'customer_id' => $customerId,
                        'vendor_id' => $vendorId,
                        'payment_id' => $paymentId,
                        'error' => $e->getMessage()
                    ]
                );
                throw $e;
            }
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
     * Check for inventory conflicts before order creation (Task 28.3)
     * 
     * @param array $cartItems
     * @return array Array of conflicting items
     */
    private function checkInventoryConflicts(array $cartItems): array
    {
        $conflictingItems = [];
        
        foreach ($cartItems as $cartItem) {
            $variantId = $cartItem->getVariantId();
            $startDate = $cartItem->getStartDate();
            $endDate = $cartItem->getEndDate();
            $quantity = $cartItem->getQuantity();
            
            // Check if inventory is available for this time period
            if (!$this->inventoryLockRepo->isAvailable($variantId, $startDate, $endDate, $quantity)) {
                $product = $this->productRepo->findById($cartItem->getProductId());
                $conflictingItems[] = [
                    'product_id' => $cartItem->getProductId(),
                    'product_name' => $product ? $product->getName() : 'Unknown Product',
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'conflict_reason' => 'Insufficient inventory for requested time period'
                ];
            }
        }
        
        return $conflictingItems;
    }

    /**
     * Apply late fee to an order (Task 28.7)
     * Requirement 24.5: Allow vendor to apply late fees for overdue returns
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param float $lateFeeAmount
     * @param string $reason
     * @throws Exception
     */
    public function applyLateFee(string $orderId, string $vendorId, float $lateFeeAmount, string $reason): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Verify order is completed or overdue
        if (!in_array($order->getStatus(), [Order::STATUS_COMPLETED, Order::STATUS_OVERDUE])) {
            throw new Exception('Late fees can only be applied to completed or overdue orders');
        }

        // Log the late fee application
        $this->logOrderStatusChange(
            $orderId,
            $order->getStatus(),
            $order->getStatus(), // Status doesn't change, but we log the fee
            $vendorId,
            "Late fee applied: ₹{$lateFeeAmount} - {$reason}"
        );

        // Create invoice line item for late fee
        try {
            $invoice = $this->invoiceService->getInvoiceByOrderId($orderId);
            if ($invoice) {
                $this->invoiceService->addServiceCharge(
                    $invoice->getId(),
                    "Late Return Fee: {$reason}",
                    'fee',
                    $lateFeeAmount
                );
            }
        } catch (Exception $e) {
            // Log error but don't fail the late fee application
            $this->errorHandlingService->logError(
                'late_fee_invoice_error',
                'Order',
                $orderId,
                $vendorId,
                [
                    'late_fee_amount' => $lateFeeAmount,
                    'error' => $e->getMessage()
                ]
            );
        }

        // Notify customer about late fee
        $this->notificationService->sendLateFeeNotification(
            $order->getCustomerId(),
            $orderId,
            $lateFeeAmount,
            $reason
        );
    }

    /**
     * Cancel order due to document upload timeout (Task 28.8)
     * Requirement 24.6: Allow order cancellation with refund for missing documents
     * 
     * @param string $orderId
     * @param string $reason
     * @param bool $processRefund
     * @throws Exception
     */
    public function cancelOrderForDocumentTimeout(string $orderId, string $reason, bool $processRefund = true): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Transition to cancelled status
        $this->transitionOrderStatus($orderId, Order::STATUS_CANCELLED, 'system', $reason);

        // Release inventory locks
        $this->releaseInventoryLocks($orderId);

        // Process refund if requested
        if ($processRefund) {
            try {
                // Initiate refund process
                $this->initiateRefund($orderId, $order->getTotalAmount(), $reason);
            } catch (Exception $e) {
                // Handle refund failure (Task 28.5)
                $this->errorHandlingService->handleRefundFailure(
                    $orderId,
                    $order->getPaymentId(),
                    $order->getTotalAmount(),
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Initiate refund process
     * 
     * @param string $orderId
     * @param float $refundAmount
     * @param string $reason
     * @throws Exception
     */
    private function initiateRefund(string $orderId, float $refundAmount, string $reason): void
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Create refund invoice
        $invoice = $this->invoiceService->getInvoiceByOrderId($orderId);
        if ($invoice) {
            $this->invoiceService->createRefundInvoice(
                $invoice->getId(),
                $refundAmount,
                $reason
            );
        }

        // Log refund initiation
        $this->logOrderStatusChange(
            $orderId,
            $order->getStatus(),
            Order::STATUS_REFUNDED,
            'system',
            "Refund initiated: ₹{$refundAmount} - {$reason}"
        );

        // Notify customer about refund
        $this->notificationService->sendRefundInitiatedNotification(
            $order->getCustomerId(),
            $orderId,
            $refundAmount,
            $reason
        );
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
     * Get customer order history with filtering (Task 22.6)
     * 
     * Requirements:
     * - 16.7: Preserve completed rental records for historical reference
     * 
     * @param string $customerId
     * @param array $filters Optional filters (status, date_range, etc.)
     * @return array
     */
    public function getCustomerOrderHistory(string $customerId, array $filters = []): array
    {
        return $this->orderRepo->findByCustomerIdWithFilters($customerId, $filters);
    }

    /**
     * Get customer orders by status
     * 
     * @param string $customerId
     * @param string $status
     * @return array
     */
    public function getCustomerOrdersByStatus(string $customerId, string $status): array
    {
        return $this->orderRepo->findByCustomerIdAndStatus($customerId, $status);
    }

    /**
     * Get customer completed orders (historical records)
     * 
     * @param string $customerId
     * @return array
     */
    public function getCustomerCompletedOrders(string $customerId): array
    {
        return $this->orderRepo->findByCustomerIdAndStatus($customerId, Order::STATUS_COMPLETED);
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
     * Get comprehensive order review data for vendors
     */
    public function getVendorOrderReviewData(string $orderId, string $vendorId): array
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Verify vendor ownership
        if ($order->getVendorId() !== $vendorId) {
            throw new Exception('Unauthorized: Order does not belong to this vendor');
        }

        // Get order items with product details
        $items = $this->orderItemRepo->findWithProductDetails($orderId);
        $summary = $this->orderItemRepo->getOrderSummary($orderId);

        // Get customer details
        $userRepo = new \RentalPlatform\Repositories\UserRepository();
        $customer = $userRepo->findById($order->getCustomerId());

        // Get uploaded documents
        $documentRepo = new \RentalPlatform\Repositories\DocumentRepository();
        $documents = $documentRepo->findByOrderId($orderId);

        // Get payment details
        $paymentRepo = new \RentalPlatform\Repositories\PaymentRepository();
        $payment = $paymentRepo->findById($order->getPaymentId());

        return [
            'order' => $order->toArray(),
            'items' => $items,
            'summary' => $summary,
            'customer' => $customer ? [
                'id' => $customer->getId(),
                'username' => $customer->getUsername(),
                'email' => $customer->getEmail(),
                'created_at' => $customer->getCreatedAt()
            ] : null,
            'documents' => array_map(function($doc) {
                return [
                    'id' => $doc->getId(),
                    'document_type' => $doc->getDocumentType(),
                    'file_name' => $doc->getFileName(),
                    'file_size' => $doc->getFileSize(),
                    'mime_type' => $doc->getMimeType(),
                    'uploaded_at' => $doc->getCreatedAt()
                ];
            }, $documents),
            'payment' => $payment ? [
                'id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'currency' => $payment->getCurrency(),
                'status' => $payment->getStatus(),
                'verified_at' => $payment->getVerifiedAt(),
                'created_at' => $payment->getCreatedAt()
            ] : null
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