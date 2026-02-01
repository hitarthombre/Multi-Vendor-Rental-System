<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * Payment Model
 * 
 * Represents a payment transaction via Razorpay
 */
class Payment
{
    private string $id;
    private string $razorpayOrderId;
    private ?string $razorpayPaymentId;
    private ?string $razorpaySignature;
    private float $amount;
    private string $currency;
    private string $status; // 'created', 'authorized', 'captured', 'failed'
    private ?string $customerId;
    private array $metadata;
    private DateTime $createdAt;
    private ?DateTime $verifiedAt;

    public const STATUS_CREATED = 'Pending';
    public const STATUS_AUTHORIZED = 'Pending';
    public const STATUS_CAPTURED = 'Verified';
    public const STATUS_FAILED = 'Failed';

    public function __construct(
        string $id,
        string $razorpayOrderId,
        float $amount,
        string $currency = 'INR',
        string $status = self::STATUS_CREATED,
        ?string $customerId = null,
        ?string $razorpayPaymentId = null,
        ?string $razorpaySignature = null,
        array $metadata = [],
        ?DateTime $createdAt = null,
        ?DateTime $verifiedAt = null
    ) {
        $this->id = $id;
        $this->razorpayOrderId = $razorpayOrderId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->customerId = $customerId;
        $this->razorpayPaymentId = $razorpayPaymentId;
        $this->razorpaySignature = $razorpaySignature;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->verifiedAt = $verifiedAt;
    }

    /**
     * Create a new payment intent
     */
    public static function create(
        string $razorpayOrderId,
        float $amount,
        ?string $customerId = null,
        array $metadata = []
    ): self {
        return new self(
            \RentalPlatform\Helpers\UUID::generate(),
            $razorpayOrderId,
            $amount,
            'INR',
            self::STATUS_CREATED,
            $customerId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Mark payment as verified
     */
    public function verify(string $razorpayPaymentId, string $razorpaySignature): void
    {
        $this->razorpayPaymentId = $razorpayPaymentId;
        $this->razorpaySignature = $razorpaySignature;
        $this->status = self::STATUS_CAPTURED;
        $this->verifiedAt = new DateTime();
    }

    /**
     * Mark payment as failed
     */
    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
    }

    /**
     * Check if payment is verified
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_CAPTURED && $this->verifiedAt !== null;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getRazorpayOrderId(): string
    {
        return $this->razorpayOrderId;
    }

    public function getRazorpayPaymentId(): ?string
    {
        return $this->razorpayPaymentId;
    }

    public function getRazorpaySignature(): ?string
    {
        return $this->razorpaySignature;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getVerifiedAt(): ?DateTime
    {
        return $this->verifiedAt;
    }
}
