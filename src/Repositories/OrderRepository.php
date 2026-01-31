<?php

namespace RentalPlatform\Repositories;

use RentalPlatform\Models\Order;
use RentalPlatform\Database\Connection;
use PDO;
use Exception;

/**
 * Order Repository
 * 
 * Handles database operations for orders
 */
class OrderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Create a new order
     */
    public function create(Order $order): void
    {
        $sql = "INSERT INTO orders (
            id, order_number, customer_id, vendor_id, payment_id, 
            status, total_amount, deposit_amount, deposit_status, 
            deposit_withheld_amount, deposit_release_reason, deposit_processed_at,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $order->getId(),
            $order->getOrderNumber(),
            $order->getCustomerId(),
            $order->getVendorId(),
            $order->getPaymentId(),
            $order->getStatus(),
            $order->getTotalAmount(),
            $order->getDepositAmount(),
            $order->getDepositStatus(),
            $order->getDepositWithheldAmount(),
            $order->getDepositReleaseReason(),
            $order->getDepositProcessedAt(),
            $order->getCreatedAt(),
            $order->getUpdatedAt()
        ]);
    }

    /**
     * Find order by ID
     */
    public function findById(string $id): ?Order
    {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToOrder($row);
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $sql = "SELECT * FROM orders WHERE order_number = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderNumber]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToOrder($row);
    }

    /**
     * Find orders by customer ID
     */
    public function findByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerId]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Find orders by vendor ID
     */
    public function findByVendorId(string $vendorId): array
    {
        $sql = "SELECT * FROM orders WHERE vendor_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Find orders by status
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Find orders by vendor ID and status
     */
    public function findByVendorIdAndStatus(string $vendorId, string $status): array
    {
        $sql = "SELECT * FROM orders WHERE vendor_id = ? AND status = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId, $status]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Find orders by customer ID and status (Task 22.6)
     */
    public function findByCustomerIdAndStatus(string $customerId, string $status): array
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ? AND status = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerId, $status]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Find orders by customer ID with filters (Task 22.6)
     * 
     * Requirements:
     * - 16.7: Preserve completed rental records for historical reference
     * 
     * @param string $customerId
     * @param array $filters
     * @return array
     */
    public function findByCustomerIdWithFilters(string $customerId, array $filters = []): array
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ?";
        $params = [$customerId];
        
        // Add status filter
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        // Add date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Add amount range filter
        if (!empty($filters['min_amount'])) {
            $sql .= " AND total_amount >= ?";
            $params[] = $filters['min_amount'];
        }
        
        if (!empty($filters['max_amount'])) {
            $sql .= " AND total_amount <= ?";
            $params[] = $filters['max_amount'];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        // Add limit if specified
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->mapRowToOrder($row);
        }

        return $orders;
    }

    /**
     * Update order
     */
    public function update(Order $order): void
    {
        $sql = "UPDATE orders SET 
            status = ?, 
            total_amount = ?, 
            deposit_amount = ?, 
            deposit_status = ?,
            deposit_withheld_amount = ?,
            deposit_release_reason = ?,
            deposit_processed_at = ?,
            updated_at = ?
        WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $order->getStatus(),
            $order->getTotalAmount(),
            $order->getDepositAmount(),
            $order->getDepositStatus(),
            $order->getDepositWithheldAmount(),
            $order->getDepositReleaseReason(),
            $order->getDepositProcessedAt(),
            $order->getUpdatedAt(),
            $order->getId()
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(string $orderId, string $newStatus): void
    {
        $sql = "UPDATE orders SET status = ?, updated_at = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$newStatus, date('Y-m-d H:i:s'), $orderId]);
    }

    /**
     * Get orders requiring vendor approval
     */
    public function getPendingApprovals(string $vendorId): array
    {
        return $this->findByVendorIdAndStatus($vendorId, Order::STATUS_PENDING_VENDOR_APPROVAL);
    }

    /**
     * Get active rentals for vendor
     */
    public function getActiveRentals(string $vendorId): array
    {
        return $this->findByVendorIdAndStatus($vendorId, Order::STATUS_ACTIVE_RENTAL);
    }

    /**
     * Get order statistics for vendor
     */
    public function getVendorStatistics(string $vendorId): array
    {
        $sql = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            SUM(deposit_amount) as deposit_amount
        FROM orders 
        WHERE vendor_id = ? 
        GROUP BY status";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = [
                'count' => (int)$row['count'],
                'total_amount' => (float)$row['total_amount'],
                'deposit_amount' => (float)$row['deposit_amount']
            ];
        }

        return $stats;
    }

    /**
     * Get order statistics for admin
     */
    public function getAdminStatistics(): array
    {
        $sql = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        FROM orders 
        GROUP BY status";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = [
                'count' => (int)$row['count'],
                'total_amount' => (float)$row['total_amount']
            ];
        }

        return $stats;
    }

    /**
     * Check if order number exists
     */
    public function orderNumberExists(string $orderNumber): bool
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE order_number = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderNumber]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Delete order (for testing purposes only)
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM orders WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Map database row to Order object
     */
    private function mapRowToOrder(array $row): Order
    {
        return new Order(
            $row['id'],
            $row['order_number'],
            $row['customer_id'],
            $row['vendor_id'],
            $row['payment_id'],
            $row['status'],
            (float)$row['total_amount'],
            (float)$row['deposit_amount'],
            $row['deposit_status'] ?? Order::DEPOSIT_STATUS_HELD,
            (float)($row['deposit_withheld_amount'] ?? 0.0),
            $row['deposit_release_reason'] ?? null,
            $row['deposit_processed_at'] ?? null,
            $row['created_at'],
            $row['updated_at']
        );
    }

    /**
     * Get orders requiring documents (for Task 28.8)
     */
    public function getOrdersRequiringDocuments(): array
    {
        $sql = "SELECT o.* FROM orders o 
                INNER JOIN order_items oi ON o.id = oi.order_id 
                INNER JOIN products p ON oi.product_id = p.id 
                WHERE o.status IN ('Pending_Vendor_Approval', 'Active_Rental') 
                AND p.verification_required = 1
                GROUP BY o.id
                ORDER BY o.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->hydrate($row);
        }
        
        return $orders;
    }
}
