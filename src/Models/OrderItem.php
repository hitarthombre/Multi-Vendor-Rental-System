<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * OrderItem Model
 * 
 * Represents an item within an order
 */
class OrderItem
{
    private string $id;
    private string $orderId;
    private string $productId;
    private ?string $variantId;
    private string $rentalPeriodId;
    private int $quantity;
    private float $unitPrice;
    private float $totalPrice;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $orderId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $unitPrice,
        float $totalPrice
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->rentalPeriodId = $rentalPeriodId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
    }

    /**
     * Create a new order item instance with generated ID
     */
    public static function create(
        string $orderId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $unitPrice
    ): self {
        $id = UUID::generate();
        $totalPrice = $unitPrice * $quantity;
        
        return new self(
            $id,
            $orderId,
            $productId,
            $variantId,
            $rentalPeriodId,
            $quantity,
            $unitPrice,
            $totalPrice
        );
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    public function getRentalPeriodId(): string
    {
        return $this->rentalPeriodId;
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

    // Setters
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->totalPrice = $this->unitPrice * $quantity;
    }

    public function setUnitPrice(float $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $unitPrice * $this->quantity;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'rental_period_id' => $this->rentalPeriodId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice
        ];
    }
}