<?php

namespace RentalPlatform\Repositories;

use PDO;
use DateTime;
use RentalPlatform\Models\Refund;

/**
 * RefundRepository
 * 
 * Handles database operations for refunds
 */
class RefundRepository
{
    private PDO $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \RentalPlatform\Database\Connection::getInstance();
    }

    /**
     * Create a new refund
     */
    public function create(Refund $refund): void
    {
        $sql = "INSERT INTO refunds (
            id, payment_id, order_id, amount, reason, status,
            razorpay_refund_id, created_at, processed_at
        ) VALUES (
            :id, :payment_id, :order_id, :amount, :reason, :status,
            :razorpay_refund_id, :created_at, :processed_at
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $refund->getId(),
            'payment_id' => $refund->getPaymentId(),
            'order_id' => $refund->getOrderId(),
            'amount' => $refund->getAmount(),
            'reason' => $refund->getReason(),
            'status' => $refund->getStatus(),
            'razorpay_refund_id' => $refund->getRazorpayRefundId(),
            'created_at' => $refund->getCreatedAt()->format('Y-m-d H:i:s'),
            'processed_at' => $refund->getProcessedAt() ? $refund->getProcessedAt()->format('Y-m-d H:i:s') : null
        ]);
    }

    /**
     * Update refund
     */
    public function update(Refund $refund): void
    {
        $sql = "UPDATE refunds SET
            status = :status,
            razorpay_refund_id = :razorpay_refund_id,
            processed_at = :processed_at
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $refund->getStatus(),
            'razorpay_refund_id' => $refund->getRazorpayRefundId(),
            'processed_at' => $refund->getProcessedAt() ? $refund->getProcessedAt()->format('Y-m-d H:i:s') : null,
            'id' => $refund->getId()
        ]);
    }

    /**
     * Find refund by ID
     */
    public function findById(string $id): ?Refund
    {
        $sql = "SELECT * FROM refunds WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find refund by order ID
     */
    public function findByOrderId(string $orderId): ?Refund
    {
        $sql = "SELECT * FROM refunds WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find refund by payment ID
     */
    public function findByPaymentId(string $paymentId): ?Refund
    {
        $sql = "SELECT * FROM refunds WHERE payment_id = :payment_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['payment_id' => $paymentId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find all refunds with a specific status
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM refunds WHERE status = :status ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        
        $refunds = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $refunds[] = $this->hydrate($row);
        }
        
        return $refunds;
    }

    /**
     * Hydrate refund from database row
     */
    private function hydrate(array $row): Refund
    {
        return new Refund(
            $row['id'],
            $row['payment_id'],
            $row['order_id'],
            (float)$row['amount'],
            $row['reason'],
            $row['status'],
            $row['razorpay_refund_id'],
            new DateTime($row['created_at']),
            $row['processed_at'] ? new DateTime($row['processed_at']) : null
        );
    }
}
