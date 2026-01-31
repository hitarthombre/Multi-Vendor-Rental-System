<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;
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
    private ?string $variantId;
    private string $productId;
    private string $vendorId;
    private int $quantity;
    private float $pricePerUnit;
    private DateTime $startDate;
    private DateTime $endDate;
    private string $createdAt;

    public function __construct(
        string $id,
        string $cartId,
        ?string $variantId,
        string $productId,
        string $vendorId,
        int $quantity,
        float $pricePerUnit,
        $startDate,
        $endDate,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->variantId = $variantId;
        $this->productId = $productId;
        $this->vendorId = $vendorId;
        $this->quantity = $quantity;
        $this->pricePerUnit = $pricePerUnit;
        $this->startDate = $startDate instanceof DateTime ? $startDate : new DateTime($startDate);
        $this->endDate = $endDate instanceof DateTime ? $endDate : new DateTime($endDate);
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    /**
     * Create a new cart item
     */
    public static function create(
        string $cartId,
        ?string $variantId,
        string $productId,
        string $vendorId,
        int $quantity,
        float $pricePerUnit,
        $startDate,
        $endDate
    ): self {
        return new self(
            UUID::generate(),
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

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getCartId(): string
    {
        return $this->cartId;
    }

    public function getVariantId(): ?string
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // Update methods
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function updatePrice(float $pricePerUnit): void
    {
        $this->pricePerUnit = $pricePerUnit;
    }

    public function updateRentalPeriod(DateTime $startDate, DateTime $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cartId,
            'variant_id' => $this->variantId,
            'product_id' => $this->productId,
            'vendor_id' => $this->vendorId,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->pricePerUnit,
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt
        ];
    }
}
