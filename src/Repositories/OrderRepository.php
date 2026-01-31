<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Order;
use RentalPlatform\Models\OrderItem;

/**
 * Order Repository
 * 
 * Handles database operations for Order entities
 */
class OrderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new order
     * 
     * @param Order $order
     * @return bool
     * @throws PDOException
     */
    public function create(Order $order): bool
    {
        $sql = "INSERT INTO orders (id, order_number, customer_id, vendor_id, payment_id, 
                status, total_amount, deposit_amount, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $order->getId(),
                $order->getOrderNumber(),
                $order->getCustomerId(),
                $order->getVendorId(),
                $order->getPaymentId(),
                $order->getStatus(),
                $order->getTotalAmount(),
                $order->getDepositAmount(),
                $order->getCreatedAt(),
                $order->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create order: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Create order item
     * 
     * @param OrderItem $orderItem
     * @return bool
     * @throws PDOException
     */
    public function createOrderItem(OrderItem $orderItem): bool
    {
        $sql = "INSERT INTO order_items (id, order_id, product_id, variant_id, 
                rental_period_id, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $orderItem->getId(),
                $orderItem->getOrderId(),
                $orderItem->getProductId(),
                $orderItem->getVariantId(),
                $orderItem->getRentalPeriodId(),
                $orderItem->getQuantity(),
                $orderItem->getUnitPrice(),
                $orderItem->getTotalPrice()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create order item: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find order by ID
     * 
     * @param string $id
     * @return Order|null
     */
    public function findById(string $id): ?Order
    {
        $sql = "SELECT * FROM orders WHERE id = ? LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find order by order number
     * 
     * @param string $orderNumber
     * @return Order|null
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $sql = "SELECT * FROM orders WHERE order_number = ? LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderNumber]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find orders by payment ID
     * 
     * @param string $paymentId
     * @return Order[]
     */
    public function findByPaymentId(string $paymentId): array
    {
        $sql = "SELECT * FROM orders WHERE payment_id = ? ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paymentId]);
        
        $orders = [];
        while ($data = $stmt->fetch()) {
            $orders[] = $this->hydrate($data);
        }
        
        return $orders;
    }

    /**
     * Find orders by customer ID
     * 
     * @param string $customerId
     * @param string|null $status Filter by status (optional)
     * @return Order[]
     */
    public function findByCustomerId(string $customerId, ?string $status = null): array
    {
        if ($status !== null) {
            $sql = "SELECT * FROM orders WHERE customer_id = ? AND status = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerId, $status]);
        } else {
            $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerId]);
        }
        
        $orders = [];
        while ($data = $stmt->fetch()) {
            $orders[] = $this->hydrate($data);
        }
        
        return $orders;
    }

    /**
     * Find orders by vendor ID
     * 
     * @param string $vendorId
     * @param string|null $status Filter by status (optional)
     * @return Order[]
     */
    public function findByVendorId(string $vendorId, ?string $status = null): array
    {
        if ($status !== null) {
            $sql = "SELECT * FROM orders WHERE vendor_id = ? AND status = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId, $status]);
        } else {
            $sql = "SELECT * FROM orders WHERE vendor_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
        }
        
        $orders = [];
        while ($data = $stmt->fetch()) {
            $orders[] = $this->hydrate($data);
        }
        
        return $orders;
    }

    /**
     * Find order items by order ID
     * 
     * @param string $orderId
     * @return OrderItem[]
     */
    public function findOrderItems(string $orderId): array
    {
        $sql = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        
        $items = [];
        while ($data = $stmt->fetch()) {
            $items[] = $this->hydrateOrderItem($data);
        }
        
        return $items;
    }

    /**
     * Update order
     * 
     * @param Order $order
     * @return bool
     * @throws PDOException
     */
    public function update(Order $order): bool
    {
        $sql = "UPDATE orders 
                SET status = ?, 
                    total_amount = ?, 
                    deposit_amount = ?,
                    updated_at = ?
                WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $order->getStatus(),
                $order->getTotalAmount(),
                $order->getDepositAmount(),
                date('Y-m-d H:i:s'),
                $order->getId()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update order: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Check if order number exists
     * 
     * @param string $orderNumber
     * @return bool
     */
    public function orderNumberExists(string $orderNumber): bool
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE order_number = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderNumber]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Count orders by status
     * 
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE status = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get orders requiring vendor approval
     * 
     * @param string $vendorId
     * @return Order[]
     */
    public function findPendingApproval(string $vendorId): array
    {
        return $this->findByVendorId($vendorId, Order::STATUS_PENDING_VENDOR_APPROVAL);
    }

    /**
     * Get active rentals for vendor
     * 
     * @param string $vendorId
     * @return Order[]
     */
    public function findActiveRentals(string $vendorId): array
    {
        return $this->findByVendorId($vendorId, Order::STATUS_ACTIVE_RENTAL);
    }

    /**
     * Hydrate order from database row
     * 
     * @param array $data
     * @return Order
     */
    private function hydrate(array $data): Order
    {
        return new Order(
            $data['id'],
            $data['order_number'],
            $data['customer_id'],
            $data['vendor_id'],
            $data['payment_id'],
            $data['status'],
            (float)$data['total_amount'],
            $data['deposit_amount'] ? (float)$data['deposit_amount'] : null,
            $data['created_at'],
            $data['updated_at']
        );
    }

    /**
     * Hydrate order item from database row
     * 
     * @param array $data
     * @return OrderItem
     */
    private function hydrateOrderItem(array $data): OrderItem
    {
        return new OrderItem(
            $data['id'],
            $data['order_id'],
            $data['product_id'],
            $data['variant_id'],
            $data['rental_period_id'],
            (int)$data['quantity'],
            (float)$data['unit_price'],
            (float)$data['total_price']
        );
    }
}