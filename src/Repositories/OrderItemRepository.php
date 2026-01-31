<?php

namespace RentalPlatform\Repositories;

use RentalPlatform\Models\OrderItem;
use RentalPlatform\Database\Connection;
use PDO;

/**
 * OrderItem Repository
 * 
 * Handles database operations for order items
 */
class OrderItemRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Create a new order item
     */
    public function create(OrderItem $orderItem): void
    {
        $sql = "INSERT INTO order_items (
            id, order_id, product_id, variant_id, rental_period_id,
            quantity, unit_price, total_price, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $orderItem->getId(),
            $orderItem->getOrderId(),
            $orderItem->getProductId(),
            $orderItem->getVariantId(),
            $orderItem->getRentalPeriodId(),
            $orderItem->getQuantity(),
            $orderItem->getUnitPrice(),
            $orderItem->getTotalPrice(),
            $orderItem->getCreatedAt()
        ]);
    }

    /**
     * Find order item by ID
     */
    public function findById(string $id): ?OrderItem
    {
        $sql = "SELECT * FROM order_items WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToOrderItem($row);
    }

    /**
     * Find order items by order ID
     */
    public function findByOrderId(string $orderId): array
    {
        $sql = "SELECT * FROM order_items WHERE order_id = ? ORDER BY created_at";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $this->mapRowToOrderItem($row);
        }

        return $items;
    }

    /**
     * Find order items with product details
     */
    public function findWithProductDetails(string $orderId): array
    {
        $sql = "SELECT 
            oi.*,
            p.name as product_name,
            p.description as product_description,
            p.images as product_images,
            rp.start_datetime,
            rp.end_datetime,
            rp.duration_value,
            rp.duration_unit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN rental_periods rp ON oi.rental_period_id = rp.id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = $this->mapRowToOrderItem($row);
            $itemArray = $item->toArray();
            
            // Add product details
            $itemArray['product_name'] = $row['product_name'];
            $itemArray['product_description'] = $row['product_description'];
            $itemArray['product_images'] = $row['product_images'] ? json_decode($row['product_images'], true) : [];
            $itemArray['start_datetime'] = $row['start_datetime'];
            $itemArray['end_datetime'] = $row['end_datetime'];
            $itemArray['duration_value'] = (int)$row['duration_value'];
            $itemArray['duration_unit'] = $row['duration_unit'];
            
            $items[] = $itemArray;
        }

        return $items;
    }

    /**
     * Get order summary
     */
    public function getOrderSummary(string $orderId): array
    {
        $sql = "SELECT 
            COUNT(*) as total_items,
            SUM(quantity) as total_quantity,
            SUM(total_price) as total_amount
        FROM order_items 
        WHERE order_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_items' => (int)$row['total_items'],
            'total_quantity' => (int)$row['total_quantity'],
            'total_amount' => (float)$row['total_amount']
        ];
    }

    /**
     * Delete order items by order ID
     */
    public function deleteByOrderId(string $orderId): void
    {
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
    }

    /**
     * Delete order item
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM order_items WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Map database row to OrderItem object
     */
    private function mapRowToOrderItem(array $row): OrderItem
    {
        return new OrderItem(
            $row['id'],
            $row['order_id'],
            $row['product_id'],
            $row['variant_id'],
            $row['rental_period_id'],
            (int)$row['quantity'],
            (float)$row['unit_price'],
            (float)$row['total_price'],
            $row['created_at']
        );
    }
}