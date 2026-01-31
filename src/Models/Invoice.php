<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Invoice Model
 * 
 * Represents an immutable financial record for a rental order
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
 */
class Invoice
{
    // Invoice Status Constants
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_FINALIZED = 'Finalized';

    private string $id;
    private string $invoiceNumber;
    private string $orderId;
    private string $vendorId;
    private string $customerId;
    private float $subtotal;
    private float $taxAmount;
    private float $totalAmount;
    private string $status;
    private ?string $finalizedAt;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $invoiceNumber,
        string $orderId,
        string $vendorId,
        string $customerId,
        float $subtotal,
        float $taxAmount,
        float $totalAmount,
        string $status = self::STATUS_DRAFT,
        ?string $finalizedAt = null,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->invoiceNumber = $invoiceNumber;
        $this->orderId = $orderId;
        $this->vendorId = $vendorId;
        $this->customerId = $customerId;
        $this->subtotal = $subtotal;
        $this->taxAmount = $taxAmount;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->finalizedAt = $finalizedAt;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new invoice
     */
    public static function create(
        string $orderId,
        string $vendorId,
        string $customerId,
        float $subtotal,
        float $taxAmount,
        float $totalAmount
    ): self {
        $id = UUID::generate();
        $invoiceNumber = self::generateInvoiceNumber();
        
        return new self(
            $id,
            $invoiceNumber,
            $orderId,
            $vendorId,
            $customerId,
            $subtotal,
            $taxAmount,
            $totalAmount
        );
    }

    /**
     * Generate a unique invoice number
     */
    private static function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(substr(UUID::generate(), 0, 8));
    }

    /**
     * Finalize the invoice (make it immutable)
     * 
     * @throws \RuntimeException if invoice is already finalized
     */
    public function finalize(): void
    {
        if ($this->isFinalized()) {
            throw new \RuntimeException('Invoice is already finalized and cannot be modified');
        }

        $this->status = self::STATUS_FINALIZED;
        $this->finalizedAt = date('Y-m-d H:i:s');
        $this->touch();
    }

    /**
     * Check if invoice is finalized
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Check if invoice is draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Update the invoice's updated timestamp
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

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFinalizedAt(): ?string
    {
        return $this->finalizedAt;
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
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'order_id' => $this->orderId,
            'vendor_id' => $this->vendorId,
            'customer_id' => $this->customerId,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'finalized_at' => $this->finalizedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
