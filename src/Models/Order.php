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
    private string $id;
    private string $orderNumber;
    private string $customerId;
    private string $vendorId;
    private string $paymentId;
    private string $status;
    private float $totalAmount;
    private ?float $depositAmount;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Valid order statuses
     */
    public const STATUS_PAYMENT_SUCCESSFUL = 'Payment_Successful';
    public const STATUS_PENDING_VENDOR_APPROVAL = 'Pending_Vendor_Approval';
    public const STATUS_AUTO_APPROVED = 'Auto_Approved';
    public const STATUS_ACTIVE_RENTAL = 'Active_Rental';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_REFUNDED = 'Refunded';

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
        ?float $depositAmount = null,
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
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new order instance with generated ID and order number
     */
    public static function create(
        string $customerId,
        string $vendorId,
        string $paymentId,
        string $status,
        float $totalAmount,
        ?float $depositAmount = null
    ): self {
        $id = UUID::generate();
        $orderNumber = self::generateOrderNumber();
        
        return new self(
            $id,
            $orderNumber,
            $customerId,
            $vendorId,
            $paymentId,
            $status,
            $totalAmount,
            $depositAmount
        );
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(UUID::generate(), 0, 8));
    }

    /**
     * Check if status is valid
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_PAYMENT_SUCCESSFUL,
            self::STATUS_PENDING_VENDOR_APPROVAL,
            self::STATUS_AUTO_APPROVED,
            self::STATUS_ACTIVE_RENTAL,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_REFUNDED
        ], true);
    }

    /**
     * Get valid status transitions
     */
    public static function getValidTransitions(): array
    {
        return [
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
            self::STATUS_COMPLETED => [], // Terminal state
            self::STATUS_REFUNDED => []   // Terminal state
        ];
    }

    /**
     * Check if status transition is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = self::getValidTransitions();
        return in_array($newStatus, $validTransitions[$this->status] ?? []);
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

    public function getDepositAmount(): ?float
    {
        return $this->depositAmount;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    // Setters
    public function setStatus(string $status): void
    {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }
        
        if (!$this->canTransitionTo($status)) {
            throw new \InvalidArgumentException("Invalid status transition from {$this->status} to {$status}");
        }
        
        $this->status = $status;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setTotalAmount(float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setDepositAmount(?float $depositAmount): void
    {
        $this->depositAmount = $depositAmount;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if order belongs to a specific customer
     */
    public function belongsToCustomer(string $customerId): bool
    {
        return $this->customerId === $customerId;
    }

    /**
     * Check if order belongs to a specific vendor
     */
    public function belongsToVendor(string $vendorId): bool
    {
        return $this->vendorId === $vendorId;
    }

    /**
     * Check if order is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REFUNDED
        ]);
    }

    /**
     * Check if order requires vendor approval
     */
    public function requiresApproval(): bool
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
            'total_amount' => $this->totalAmount,
            'deposit_amount' => $this->depositAmount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}