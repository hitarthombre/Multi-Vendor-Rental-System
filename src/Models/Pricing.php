<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Pricing Model
 * 
 * Represents pricing rules for products and variants
 */
class Pricing
{
    private string $id;
    private string $productId;
    private ?string $variantId;
    private string $durationUnit;
    private float $pricePerUnit;
    private int $minimumDuration;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        string $id,
        string $productId,
        ?string $variantId,
        string $durationUnit,
        float $pricePerUnit,
        int $minimumDuration,
        string $createdAt,
        string $updatedAt
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->durationUnit = $durationUnit;
        $this->pricePerUnit = $pricePerUnit;
        $this->minimumDuration = $minimumDuration;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Create a new pricing rule
     */
    public static function create(
        string $productId,
        ?string $variantId,
        string $durationUnit,
        float $pricePerUnit,
        int $minimumDuration = 1
    ): self {
        $now = date('Y-m-d H:i:s');
        
        return new self(
            UUID::generate(),
            $productId,
            $variantId,
            $durationUnit,
            $pricePerUnit,
            $minimumDuration,
            $now,
            $now
        );
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    public function getDurationUnit(): string
    {
        return $this->durationUnit;
    }

    public function getPricePerUnit(): float
    {
        return $this->pricePerUnit;
    }

    public function getMinimumDuration(): int
    {
        return $this->minimumDuration;
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
    public function setPricePerUnit(float $pricePerUnit): void
    {
        $this->pricePerUnit = $pricePerUnit;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setMinimumDuration(int $minimumDuration): void
    {
        $this->minimumDuration = $minimumDuration;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'duration_unit' => $this->durationUnit,
            'price_per_unit' => $this->pricePerUnit,
            'minimum_duration' => $this->minimumDuration,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
