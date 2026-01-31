<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Order;
use RentalPlatform\Models\OrderItem;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Services\AuditLogger;
use Exception;

/**
 * Order Service
 * 
 * Handles order creation, vendor-wise splitting, and status management
 */
class OrderService
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->productRepository = new ProductRepository();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * Create orders from verified payment
     * 
     * Task 12.1: Order creation after payment verification
     * Task 12.3: Vendor-wise order splitting
     * Task 12.5: Initial order status assignment
     * 
     * @param string $paymentId Verified payment ID
     * @param array $cartItems Cart items grouped by vendor
     * @param string $customerId Customer ID
     * @return Order[] Created orders (one per vendor)
     * @throws Exception
     */
    public function createOrdersFromPayment(string $paymentId, array $cartItems, string $customerId): array
    {
        $orders = [];
        
        try {
            // Group cart items by vendor (Task 12.3: Vendor-wise order splitting)
            $vendorGroups = $this->groupCartItemsByVendor($cartItems);
            
            foreach ($vendorGroups as $vendorId => $items) {
                // Calculate total amount for this vendor's items
                $totalAmount = $this->calculateVendorTotal($items);
                
                // Determine initial status based on verification requirements (Task 12.5)
                $initialStatus = $this->determineInitialStatus($items);
                
                // Create order for this vendor (Task 12.1)
                $order = Order::create(
                    $customerId,
                    $vendorId,
                    $paymentId,
                    $initialStatus,
                    $totalAmount
                );
                
                // Ensure unique order number
                $order = $this->ensureUniqueOrderNumber($order);
                
                // Save order to database
                if (!$this->orderRepository->create($order)) {
                    throw new Exception("Failed to create order for vendor {$vendorId}");
                }
                
                // Create order items
                foreach ($items as $item) {
                    $orderItem = OrderItem::create(
                        $order->getId(),
                        $item['product_id'],
                        $item['variant_id'] ?? null,
                        $item['rental_period_id'],
                        $item['quantity'],
                        $item['unit_price']
                    );
                    
                    if (!$this->orderRepository->createOrderItem($orderItem)) {
                        throw new Exception("Failed to create order item for product {$item['product_id']}");
                    }
                }
                
                // Log order creation
                $this->auditLogger->log(
                    $customerId,
                    'Order',
                    $order->getId(),
                    'created',
                    null,
                    $order->toArray()
                );
                
                $orders[] = $order;
            }
            
            return $orders;
            
        } catch (Exception $e) {
            // Log error
            $this->auditLogger->log(
                $customerId,
                'Order',
                null,
                'creation_failed',
                null,
                ['error' => $e->getMessage(), 'payment_id' => $paymentId]
            );
            
            throw $e;
        }
    }

    /**
     * Group cart items by vendor
     * 
     * @param array $cartItems
     * @return array Grouped by vendor_id
     * @throws Exception
     */
    private function groupCartItemsByVendor(array $cartItems): array
    {
        $vendorGroups = [];
        
        foreach ($cartItems as $item) {
            // Get product to determine vendor
            $product = $this->productRepository->findById($item['product_id']);
            
            if (!$product) {
                throw new Exception("Product not found: {$item['product_id']}");
            }
            
            if (!$product->isActive()) {
                throw new Exception("Product is not active: {$item['product_id']}");
            }
            
            $vendorId = $product->getVendorId();
            
            if (!isset($vendorGroups[$vendorId])) {
                $vendorGroups[$vendorId] = [];
            }
            
            // Add vendor_id to item for convenience
            $item['vendor_id'] = $vendorId;
            $item['verification_required'] = $product->isVerificationRequired();
            
            $vendorGroups[$vendorId][] = $item;
        }
        
        return $vendorGroups;
    }

    /**
     * Calculate total amount for vendor's items
     * 
     * @param array $items
     * @return float
     */
    private function calculateVendorTotal(array $items): float
    {
        $total = 0.0;
        
        foreach ($items as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }
        
        return $total;
    }

    /**
     * Determine initial order status based on verification requirements
     * 
     * Task 12.5: Initial order status assignment
     * 
     * @param array $items
     * @return string
     */
    private function determineInitialStatus(array $items): string
    {
        // Check if any item requires verification
        foreach ($items as $item) {
            if ($item['verification_required']) {
                return Order::STATUS_PENDING_VENDOR_APPROVAL;
            }
        }
        
        // If no items require verification, auto-approve
        return Order::STATUS_AUTO_APPROVED;
    }

    /**
     * Ensure order number is unique
     * 
     * @param Order $order
     * @return Order
     */
    private function ensureUniqueOrderNumber(Order $order): Order
    {
        $attempts = 0;
        $maxAttempts = 10;
        
        while ($this->orderRepository->orderNumberExists($order->getOrderNumber()) && $attempts < $maxAttempts) {
            // Generate new order number
            $newOrderNumber = Order::generateOrderNumber();
            
            // Create new order instance with unique number
            $order = new Order(
                $order->getId(),
                $newOrderNumber,
                $order->getCustomerId(),
                $order->getVendorId(),
                $order->getPaymentId(),
                $order->getStatus(),
                $order->getTotalAmount(),
                $order->getDepositAmount(),
                $order->getCreatedAt(),
                $order->getUpdatedAt()
            );
            
            $attempts++;
        }
        
        if ($attempts >= $maxAttempts) {
            throw new Exception("Failed to generate unique order number after {$maxAttempts} attempts");
        }
        
        return $order;
    }

    /**
     * Update order status
     * 
     * @param string $orderId
     * @param string $newStatus
     * @param string $actorId
     * @return bool
     * @throws Exception
     */
    public function updateOrderStatus(string $orderId, string $newStatus, string $actorId): bool
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new Exception("Order not found: {$orderId}");
        }
        
        $oldStatus = $order->getStatus();
        
        // Validate status transition
        if (!$order->canTransitionTo($newStatus)) {
            throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }
        
        // Update status
        $order->setStatus($newStatus);
        
        // Save to database
        if (!$this->orderRepository->update($order)) {
            throw new Exception("Failed to update order status");
        }
        
        // Log status change
        $this->auditLogger->log(
            $actorId,
            'Order',
            $orderId,
            'status_changed',
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
        
        return true;
    }

    /**
     * Get orders by customer
     * 
     * @param string $customerId
     * @param string|null $status
     * @return Order[]
     */
    public function getCustomerOrders(string $customerId, ?string $status = null): array
    {
        return $this->orderRepository->findByCustomerId($customerId, $status);
    }

    /**
     * Get orders by vendor
     * 
     * @param string $vendorId
     * @param string|null $status
     * @return Order[]
     */
    public function getVendorOrders(string $vendorId, ?string $status = null): array
    {
        return $this->orderRepository->findByVendorId($vendorId, $status);
    }

    /**
     * Get order with items
     * 
     * @param string $orderId
     * @return array|null
     */
    public function getOrderWithItems(string $orderId): ?array
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            return null;
        }
        
        $items = $this->orderRepository->findOrderItems($orderId);
        
        return [
            'order' => $order,
            'items' => $items
        ];
    }

    /**
     * Get pending approval orders for vendor
     * 
     * @param string $vendorId
     * @return Order[]
     */
    public function getPendingApprovalOrders(string $vendorId): array
    {
        return $this->orderRepository->findPendingApproval($vendorId);
    }

    /**
     * Get active rentals for vendor
     * 
     * @param string $vendorId
     * @return Order[]
     */
    public function getActiveRentals(string $vendorId): array
    {
        return $this->orderRepository->findActiveRentals($vendorId);
    }
}