<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Invoice Line Item Model
 * 
 * Represents a single line item in an invoice
 * Requirements: 13.6, 14.3
 */
class InvoiceLineItem
{
    // Item Type Constants
    public const TYPE_RENTAL = 'Rental';
    public const TYPE_DEPOSIT = 'Deposit';
    public const TYPE_DELIVERY = 'Delivery';
    public const TYPE_FEE = 'Fee';
    public const TYPE_PENALTY = 'Penalty';

    private string $id;
    private string $invoiceId;
    private string $description;
    private string $itemType;
    private int $quantity;
    private float $unitPrice;
    private float $totalPrice;
    private float $taxRate;
    private float $taxAmount;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $invoiceId,
        string $description,
        string $itemType,
        int $quantity,
        float $unitPrice,
        float $totalPrice,
        float $taxRate = 0.0,
        float $taxAmount = 0.0,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->description = $description;
        $this->itemType = $itemType;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
        $this->taxRate = $taxRate;
        $this->taxAmount = $taxAmount;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new invoice line item
     */
    public static function create(
        string $invoiceId,
        string $description,
        string $itemType,
        int $quantity,
        float $unitPrice,
        float $taxRate = 0.0
    ): self {
        $id = UUID::generate();
        $totalPrice = $quantity * $unitPrice;
        $taxAmount = $totalPrice * ($taxRate / 100);
        
        return new self(
            $id,
            $invoiceId,
            $description,
            $itemType,
            $quantity,
            $unitPrice,
            $totalPrice,
            $taxRate,
            $taxAmount
        );
    }

    /**
     * Create a rental line item
     */
    public static function createRentalItem(
        string $invoiceId,
        string $productName,
        int $quantity,
        float $unitPrice,
        float $taxRate = 0.0
    ): self {
        return self::create(
            $invoiceId,
            "Rental: {$productName}",
            self::TYPE_RENTAL,
            $quantity,
            $unitPrice,
            $taxRate
        );
    }

    /**
     * Create a deposit line item
     */
    public static function createDepositItem(
        string $invoiceId,
        string $description,
        float $amount
    ): self {
        return self::create(
            $invoiceId,
            "Security Deposit: {$description}",
            self::TYPE_DEPOSIT,
            1,
            $amount,
            0.0 // Deposits are not taxed
        );
    }

    /**
     * Create a delivery fee line item
     */
    public static function createDeliveryItem(
        string $invoiceId,
        float $amount,
        float $taxRate = 0.0
    ): self {
        return self::create(
            $invoiceId,
            'Delivery Fee',
            self::TYPE_DELIVERY,
            1,
            $amount,
            $taxRate
        );
    }

    /**
     * Create a service fee line item
     */
    public static function createFeeItem(
        string $invoiceId,
        string $description,
        float $amount,
        float $taxRate = 0.0
    ): self {
        return self::create(
            $invoiceId,
            $description,
            self::TYPE_FEE,
            1,
            $amount,
            $taxRate
        );
    }

    /**
     * Create a penalty line item
     */
    public static function createPenaltyItem(
        string $invoiceId,
        string $description,
        float $amount
    ): self {
        return self::create(
            $invoiceId,
            "Penalty: {$description}",
            self::TYPE_PENALTY,
            1,
            $amount,
            0.0 // Penalties are not taxed
        );
    }

    /**
     * Check if this is a rental item
     */
    public function isRental(): bool
    {
        return $this->itemType === self::TYPE_RENTAL;
    }

    /**
     * Check if this is a deposit item
     */
    public function isDeposit(): bool
    {
        return $this->itemType === self::TYPE_DEPOSIT;
    }

    /**
     * Get total including tax
     */
    public function getTotalWithTax(): float
    {
        return $this->totalPrice + $this->taxAmount;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
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
            'invoice_id' => $this->invoiceId,
            'description' => $this->description,
            'item_type' => $this->itemType,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
            'tax_rate' => $this->taxRate,
            'tax_amount' => $this->taxAmount,
            'total_with_tax' => $this->getTotalWithTax(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
