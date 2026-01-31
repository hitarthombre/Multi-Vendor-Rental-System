<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * Refund Model
 * 
 * Represents a refund transaction via Razorpay
 */
class Refund
{
    private string $id;
    private string $paymentId;
    private string $orderId;
    private float $amount;
    private string $reason;
    private string $status; // 'pending', 'processing', 'completed', 'failed'
    private ?string $razorpayRefundId;
    private DateTime $createdAt;
    private ?DateTime $processedAt;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        string $id,
        string $paymentId,
        string $orderId,
        float $amount,
        string $reason,
        string $status = self::STATUS_PENDING,
        ?string $razorpayRefundId = null,
        ?DateTime $createdAt = null,
        ?DateTime $processedAt = null
    ) {
        $this->id = $id;
        $this->paymentId = $paymentId;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->status = $status;
        $this->razorpayRefundId = $razorpayRefundId;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->processedAt = $processedAt;
    }

    /**
     * Create a new refund
     */
    public static function create(
        string $paymentId,
        string $orderId,
        float $amount,
        string $reason
    ): self {
        return new self(
            \RentalPlatform\Helpers\UUID::generate(),
            $paymentId,
            $orderId,
            $amount,
            $reason,
            self::STATUS_PENDING
        );
    }

    /**
     * Mark refund as processing
     */
    public function markProcessing(string $razorpayRefundId): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->razorpayRefundId = $razorpayRefundId;
    }

    /**
     * Mark refund as completed
     */
    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processedAt = new DateTime();
    }

    /**
     * Mark refund as failed
     */
    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->processedAt = new DateTime();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRazorpayRefundId(): ?string
    {
        return $this->razorpayRefundId;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?DateTime
    {
        return $this->processedAt;
    }
}
