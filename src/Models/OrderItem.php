<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * OrderItem Model
 * 
 * Represents an item within a rental order
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
    private string $createdAt;

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
        float $totalPrice,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->rentalPeriodId = $rentalPeriodId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new order item with generated ID
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

    /**
     * Create from cart item
     */
    public static function createFromCartItem(string $orderId, CartItem $cartItem): self
    {
        return self::create(
            $orderId,
            $cartItem->getProductId(),
            $cartItem->getVariantId(),
            $cartItem->getRentalPeriodId(),
            $cartItem->getQuantity(),
            $cartItem->getTentativePrice()
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
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
            'total_price' => $this->totalPrice,
            'created_at' => $this->createdAt
        ];
    }
}