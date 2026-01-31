<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * CartItem Model
 * 
 * Represents an item in a customer's shopping cart
 */
class CartItem
{
    private string $id;
    private string $cartId;
    private string $productId;
    private ?string $variantId;
    private string $rentalPeriodId;
    private int $quantity;
    private float $tentativePrice;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $cartId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $tentativePrice,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->rentalPeriodId = $rentalPeriodId;
        $this->quantity = $quantity;
        $this->tentativePrice = $tentativePrice;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new cart item with generated ID
     */
    public static function create(
        string $cartId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $tentativePrice
    ): self {
        $id = UUID::generate();
        
        return new self(
            $id,
            $cartId,
            $productId,
            $variantId,
            $rentalPeriodId,
            $quantity,
            $tentativePrice
        );
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
        $this->touch();
    }

    /**
     * Update tentative price
     */
    public function updatePrice(float $price): void
    {
        $this->tentativePrice = $price;
        $this->touch();
    }

    /**
     * Update rental period
     */
    public function updateRentalPeriod(string $rentalPeriodId): void
    {
        $this->rentalPeriodId = $rentalPeriodId;
        $this->touch();
    }

    /**
     * Get total price for this item
     */
    public function getTotalPrice(): float
    {
        return $this->tentativePrice * $this->quantity;
    }

    /**
     * Update the item's updated timestamp
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

    public function getCartId(): string
    {
        return $this->cartId;
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

    public function getTentativePrice(): float
    {
        return $this->tentativePrice;
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
            'cart_id' => $this->cartId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'rental_period_id' => $this->rentalPeriodId,
            'quantity' => $this->quantity,
            'tentative_price' => $this->tentativePrice,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}