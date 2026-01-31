<?php

namespace RentalPlatform\Repositories;

use PDO;
use DateTime;
use RentalPlatform\Models\Payment;

/**
 * PaymentRepository
 * 
 * Handles database operations for payments
 */
class PaymentRepository
{
    private PDO $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \RentalPlatform\Database\Connection::getInstance();
    }

    /**
     * Create a new payment
     */
    public function create(Payment $payment): void
    {
        $sql = "INSERT INTO payments (
            id, razorpay_order_id, razorpay_payment_id, razorpay_signature,
            amount, currency, status, customer_id, metadata, created_at, verified_at
        ) VALUES (
            :id, :razorpay_order_id, :razorpay_payment_id, :razorpay_signature,
            :amount, :currency, :status, :customer_id, :metadata, :created_at, :verified_at
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $payment->getId(),
            'razorpay_order_id' => $payment->getRazorpayOrderId(),
            'razorpay_payment_id' => $payment->getRazorpayPaymentId(),
            'razorpay_signature' => $payment->getRazorpaySignature(),
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            'status' => $payment->getStatus(),
            'customer_id' => $payment->getCustomerId(),
            'metadata' => json_encode($payment->getMetadata()),
            'created_at' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            'verified_at' => $payment->getVerifiedAt() ? $payment->getVerifiedAt()->format('Y-m-d H:i:s') : null
        ]);
    }

    /**
     * Update payment
     */
    public function update(Payment $payment): void
    {
        $sql = "UPDATE payments SET
            razorpay_payment_id = :razorpay_payment_id,
            razorpay_signature = :razorpay_signature,
            status = :status,
            verified_at = :verified_at
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'razorpay_payment_id' => $payment->getRazorpayPaymentId(),
            'razorpay_signature' => $payment->getRazorpaySignature(),
            'status' => $payment->getStatus(),
            'verified_at' => $payment->getVerifiedAt() ? $payment->getVerifiedAt()->format('Y-m-d H:i:s') : null,
            'id' => $payment->getId()
        ]);
    }

    /**
     * Find payment by ID
     */
    public function findById(string $id): ?Payment
    {
        $sql = "SELECT * FROM payments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find payment by Razorpay order ID
     */
    public function findByRazorpayOrderId(string $razorpayOrderId): ?Payment
    {
        $sql = "SELECT * FROM payments WHERE razorpay_order_id = :razorpay_order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['razorpay_order_id' => $razorpayOrderId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find payment by Razorpay payment ID
     */
    public function findByRazorpayPaymentId(string $razorpayPaymentId): ?Payment
    {
        $sql = "SELECT * FROM payments WHERE razorpay_payment_id = :razorpay_payment_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['razorpay_payment_id' => $razorpayPaymentId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find all payments by customer ID
     */
    public function findByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM payments WHERE customer_id = :customer_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        
        $payments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payments[] = $this->hydrate($row);
        }
        
        return $payments;
    }

    /**
     * Hydrate payment from database row
     */
    private function hydrate(array $row): Payment
    {
        return new Payment(
            $row['id'],
            $row['razorpay_order_id'],
            (float)$row['amount'],
            $row['currency'],
            $row['status'],
            $row['customer_id'],
            $row['razorpay_payment_id'],
            $row['razorpay_signature'],
            json_decode($row['metadata'], true) ?? [],
            new DateTime($row['created_at']),
            $row['verified_at'] ? new DateTime($row['verified_at']) : null
        );
    }
}
