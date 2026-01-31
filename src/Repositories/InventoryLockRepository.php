<?php

namespace RentalPlatform\Repositories;

use PDO;
use DateTime;
use RentalPlatform\Models\InventoryLock;

/**
 * InventoryLockRepository
 * 
 * Handles database operations for inventory locks
 */
class InventoryLockRepository
{
    private PDO $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \RentalPlatform\Database\Connection::getInstance();
    }

    /**
     * Create a new inventory lock
     */
    public function create(InventoryLock $lock): void
    {
        $sql = "INSERT INTO inventory_locks (
            id, variant_id, order_id, start_date, end_date, 
            status, created_at, released_at
        ) VALUES (
            :id, :variant_id, :order_id, :start_date, :end_date,
            :status, :created_at, :released_at
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $lock->getId(),
            'variant_id' => $lock->getVariantId(),
            'order_id' => $lock->getOrderId(),
            'start_date' => $lock->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $lock->getEndDate()->format('Y-m-d H:i:s'),
            'status' => $lock->getStatus(),
            'created_at' => $lock->getCreatedAt()->format('Y-m-d H:i:s'),
            'released_at' => $lock->getReleasedAt() ? $lock->getReleasedAt()->format('Y-m-d H:i:s') : null
        ]);
    }

    /**
     * Find lock by ID
     */
    public function findById(string $id): ?InventoryLock
    {
        $sql = "SELECT * FROM inventory_locks WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find all active locks for a variant
     */
    public function findActiveByVariantId(string $variantId): array
    {
        $sql = "SELECT * FROM inventory_locks 
                WHERE variant_id = :variant_id 
                AND status = :status
                ORDER BY start_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'variant_id' => $variantId,
            'status' => InventoryLock::STATUS_ACTIVE
        ]);
        
        $locks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $locks[] = $this->hydrate($row);
        }
        
        return $locks;
    }

    /**
     * Find locks by order ID
     */
    public function findByOrderId(string $orderId): array
    {
        $sql = "SELECT * FROM inventory_locks WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        
        $locks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $locks[] = $this->hydrate($row);
        }
        
        return $locks;
    }

    /**
     * Check if variant is available for a time period
     * 
     * @param string $variantId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int $quantity Required quantity
     * @return bool True if available, false if locked
     */
    public function isAvailable(string $variantId, DateTime $startDate, DateTime $endDate, int $quantity = 1): bool
    {
        // Get all active locks that overlap with the requested period
        $sql = "SELECT COUNT(*) as lock_count 
                FROM inventory_locks 
                WHERE variant_id = :variant_id 
                AND status = :status
                AND start_date < :end_date 
                AND end_date > :start_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'variant_id' => $variantId,
            'status' => InventoryLock::STATUS_ACTIVE,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $lockCount = (int)$result['lock_count'];
        
        // Get variant stock quantity
        $variantSql = "SELECT stock_quantity FROM variants WHERE id = :id";
        $variantStmt = $this->db->prepare($variantSql);
        $variantStmt->execute(['id' => $variantId]);
        $variantRow = $variantStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variantRow) {
            return false;
        }
        
        $stockQuantity = (int)$variantRow['stock_quantity'];
        
        // Available if: (stock - locked) >= required quantity
        return ($stockQuantity - $lockCount) >= $quantity;
    }

    /**
     * Release a lock
     */
    public function release(InventoryLock $lock): void
    {
        $lock->release();
        
        $sql = "UPDATE inventory_locks 
                SET status = :status, released_at = :released_at 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $lock->getStatus(),
            'released_at' => $lock->getReleasedAt()->format('Y-m-d H:i:s'),
            'id' => $lock->getId()
        ]);
    }

    /**
     * Release all locks for an order
     */
    public function releaseByOrderId(string $orderId): void
    {
        $locks = $this->findByOrderId($orderId);
        
        foreach ($locks as $lock) {
            if ($lock->isActive()) {
                $this->release($lock);
            }
        }
    }

    /**
     * Hydrate a lock from database row
     */
    private function hydrate(array $row): InventoryLock
    {
        return new InventoryLock(
            $row['id'],
            $row['variant_id'],
            $row['order_id'],
            new DateTime($row['start_date']),
            new DateTime($row['end_date']),
            $row['status'],
            new DateTime($row['created_at']),
            $row['released_at'] ? new DateTime($row['released_at']) : null
        );
    }
}
