<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * CartItem Model
 * 
 * Represents an item in a shopping cart
 */
class CartItem
{
    private string $id;
    private string $cartId;
    private string $variantId;
    private string $productId;
    private string $vendorId;
    private int $quantity;
    private float $pricePerUnit;
    private DateTime $startDate;
    private DateTime $endDate;
    private DateTime $createdAt;

    public function __construct(
        string $id,
        string $cartId,
        string $variantId,
        string $productId,
        string $vendorId,
        int $quantity,
        float $pricePerUnit,
        DateTime $startDate,
        DateTime $endDate,
        ?DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->variantId = $variantId;
        $this->productId = $productId;
        $this->vendorId = $vendorId;
        $this->quantity = $quantity;
        $this->pricePerUnit = $pricePerUnit;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    /**
     * Create a new cart item
     */
    public static function create(
        string $cartId,
        string $variantId,
        string $productId,
        string $vendorId,
        int $quantity,
        float $pricePerUnit,
        DateTime $startDate,
        DateTime $endDate
    ): self {
        return new self(
            \RentalPlatform\Helpers\UUID::generate(),
            $cartId,
            $variantId,
            $productId,
            $vendorId,
            $quantity,
            $pricePerUnit,
            $startDate,
            $endDate
        );
    }

    /**
     * Calculate subtotal for this item
     */
    public function getSubtotal(): float
    {
        return $this->pricePerUnit * $this->quantity;
    }

    /**
     * Calculate rental duration in days
     */
    public function getRentalDuration(): int
    {
        $interval = $this->startDate->diff($this->endDate);
        return max(1, $interval->days);
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

    public function getVariantId(): string
    {
        return $this->variantId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPricePerUnit(): float
    {
        return $this->pricePerUnit;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    // Setters
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setPricePerUnit(float $pricePerUnit): void
    {
        $this->pricePerUnit = $pricePerUnit;
    }

    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function setEndDate(DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }
}
