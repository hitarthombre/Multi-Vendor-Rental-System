<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Order Model
 * 
 * Represents a rental order in the system
 */
class Order
{
    // Order Status Constants
    public const STATUS_PAYMENT_SUCCESSFUL = 'Payment_Successful';
    public const STATUS_PENDING_VENDOR_APPROVAL = 'Pending_Vendor_Approval';
    public const STATUS_AUTO_APPROVED = 'Auto_Approved';
    public const STATUS_ACTIVE_RENTAL = 'Active_Rental';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_REFUNDED = 'Refunded';

    // Valid status transitions
    private const VALID_TRANSITIONS = [
        self::STATUS_PAYMENT_SUCCESSFUL => [
            self::STATUS_PENDING_VENDOR_APPROVAL,
            self::STATUS_AUTO_APPROVED
        ],
        self::STATUS_PENDING_VENDOR_APPROVAL => [
            self::STATUS_ACTIVE_RENTAL,
            self::STATUS_REJECTED
        ],
        self::STATUS_AUTO_APPROVED => [
            self::STATUS_ACTIVE_RENTAL
        ],
        self::STATUS_ACTIVE_RENTAL => [
            self::STATUS_COMPLETED
        ],
        self::STATUS_REJECTED => [
            self::STATUS_REFUNDED
        ],
        self::STATUS_COMPLETED => [],
        self::STATUS_REFUNDED => []
    ];

    // Deposit Status Constants
    public const DEPOSIT_STATUS_HELD = 'held';
    public const DEPOSIT_STATUS_RELEASED = 'released';
    public const DEPOSIT_STATUS_PARTIALLY_WITHHELD = 'partially_withheld';
    public const DEPOSIT_STATUS_FULLY_WITHHELD = 'fully_withheld';

    private string $id;
    private string $orderNumber;
    private string $customerId;
    private string $vendorId;
    private string $paymentId;
    private string $status;
    private float $totalAmount;
    private float $depositAmount;
    private string $depositStatus;
    private float $depositWithheldAmount;
    private ?string $depositReleaseReason;
    private ?string $depositProcessedAt;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $orderNumber,
        string $customerId,
        string $vendorId,
        string $paymentId,
        string $status,
        float $totalAmount,
        float $depositAmount = 0.0,
        string $depositStatus = self::DEPOSIT_STATUS_HELD,
        float $depositWithheldAmount = 0.0,
        ?string $depositReleaseReason = null,
        ?string $depositProcessedAt = null,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->orderNumber = $orderNumber;
        $this->customerId = $customerId;
        $this->vendorId = $vendorId;
        $this->paymentId = $paymentId;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
        $this->depositAmount = $depositAmount;
        $this->depositStatus = $depositStatus;
        $this->depositWithheldAmount = $depositWithheldAmount;
        $this->depositReleaseReason = $depositReleaseReason;
        $this->depositProcessedAt = $depositProcessedAt;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new order with generated ID and order number
     */
    public static function create(
        string $customerId,
        string $vendorId,
        string $paymentId,
        string $initialStatus,
        float $totalAmount,
        float $depositAmount = 0.0
    ): self {
        $id = UUID::generate();
        $orderNumber = self::generateOrderNumber();
        
        return new self(
            $id,
            $orderNumber,
            $customerId,
            $vendorId,
            $paymentId,
            $initialStatus,
            $totalAmount,
            $depositAmount
        );
    }

    /**
     * Generate a unique order number
     */
    private static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(UUID::generate(), 0, 8));
    }

    /**
     * Check if a status transition is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Get all valid next statuses for current status
     */
    public function getValidNextStatuses(): array
    {
        return self::VALID_TRANSITIONS[$this->status] ?? [];
    }

    /**
     * Transition to a new status
     * 
     * @throws \InvalidArgumentException if transition is not valid
     */
    public function transitionTo(string $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from {$this->status} to {$newStatus}"
            );
        }

        $this->status = $newStatus;
        $this->touch();
    }

    /**
     * Check if order requires vendor approval
     */
    public function requiresVendorApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_VENDOR_APPROVAL;
    }

    /**
     * Check if order is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE_RENTAL;
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if order is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PAYMENT_SUCCESSFUL => 'Payment Successful',
            self::STATUS_PENDING_VENDOR_APPROVAL => 'Pending Approval',
            self::STATUS_AUTO_APPROVED => 'Auto Approved',
            self::STATUS_ACTIVE_RENTAL => 'Active Rental',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REFUNDED => 'Refunded',
            default => $this->status
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PAYMENT_SUCCESSFUL => 'blue',
            self::STATUS_PENDING_VENDOR_APPROVAL => 'yellow',
            self::STATUS_AUTO_APPROVED => 'green',
            self::STATUS_ACTIVE_RENTAL => 'green',
            self::STATUS_COMPLETED => 'gray',
            self::STATUS_REJECTED => 'red',
            self::STATUS_REFUNDED => 'purple',
            default => 'gray'
        };
    }

    /**
     * Update the order's updated timestamp
     */
    public function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getDepositAmount(): float
    {
        return $this->depositAmount;
    }

    public function getDepositStatus(): string
    {
        return $this->depositStatus;
    }

    public function getDepositWithheldAmount(): float
    {
        return $this->depositWithheldAmount;
    }

    public function getDepositReleaseReason(): ?string
    {
        return $this->depositReleaseReason;
    }

    public function getDepositProcessedAt(): ?string
    {
        return $this->depositProcessedAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Release deposit fully (Task 18.5, Requirements 14.6, 25.3)
     */
    public function releaseDeposit(string $reason = 'Rental completed without issues'): void
    {
        if ($this->depositAmount <= 0) {
            throw new \Exception('No deposit to release');
        }

        if ($this->depositStatus !== self::DEPOSIT_STATUS_HELD) {
            throw new \Exception('Deposit has already been processed');
        }

        $this->depositStatus = self::DEPOSIT_STATUS_RELEASED;
        $this->depositWithheldAmount = 0.0;
        $this->depositReleaseReason = $reason;
        $this->depositProcessedAt = date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Withhold deposit partially or fully (Task 18.5, Requirements 14.7, 25.4)
     */
    public function withholdDeposit(float $amount, string $reason): void
    {
        if ($this->depositAmount <= 0) {
            throw new \Exception('No deposit to withhold');
        }

        if ($this->depositStatus !== self::DEPOSIT_STATUS_HELD) {
            throw new \Exception('Deposit has already been processed');
        }

        if ($amount < 0 || $amount > $this->depositAmount) {
            throw new \Exception('Invalid withhold amount');
        }

        $this->depositWithheldAmount = $amount;
        $this->depositReleaseReason = $reason;
        $this->depositProcessedAt = date('Y-m-d H:i:s');
        
        if ($amount >= $this->depositAmount) {
            $this->depositStatus = self::DEPOSIT_STATUS_FULLY_WITHHELD;
        } else {
            $this->depositStatus = self::DEPOSIT_STATUS_PARTIALLY_WITHHELD;
        }
        
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if deposit can be processed
     */
    public function canProcessDeposit(): bool
    {
        return $this->status === self::STATUS_COMPLETED 
            && $this->depositAmount > 0 
            && $this->depositStatus === self::DEPOSIT_STATUS_HELD;
    }

    /**
     * Get deposit refund amount
     */
    public function getDepositRefundAmount(): float
    {
        if ($this->depositStatus === self::DEPOSIT_STATUS_RELEASED) {
            return $this->depositAmount;
        }

        if ($this->depositStatus === self::DEPOSIT_STATUS_PARTIALLY_WITHHELD) {
            return $this->depositAmount - $this->depositWithheldAmount;
        }

        return 0.0;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'customer_id' => $this->customerId,
            'vendor_id' => $this->vendorId,
            'payment_id' => $this->paymentId,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'total_amount' => $this->totalAmount,
            'deposit_amount' => $this->depositAmount,
            'deposit_status' => $this->depositStatus,
            'deposit_withheld_amount' => $this->depositWithheldAmount,
            'deposit_release_reason' => $this->depositReleaseReason,
            'deposit_processed_at' => $this->depositProcessedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}